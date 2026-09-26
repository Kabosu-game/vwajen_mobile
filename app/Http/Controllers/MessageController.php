<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\AntiSpam;
use App\Services\MediaService;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Messagerie privée : conversations individuelles et de groupe, texte, images, vidéos. */
class MessageController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    private function participant(Request $request, Conversation $conversation): ConversationParticipant
    {
        $p = $conversation->participants()->where('user_id', $request->user()->id)->whereNull('left_at')->first();
        abort_unless($p, 404);

        return $p;
    }

    private function listFor(User $user, ?string $search = null)
    {
        return Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id)->whereNull('left_at'))
            ->with(['users', 'latestMessage.user', 'participants'])
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%$search%")
                ->orWhereHas('users', fn ($u) => $u->where('users.id', '!=', $user->id)->where(fn ($x) => $x->where('name', 'like', "%$search%")->orWhere('username', 'like', "%$search%")))))
            ->orderByDesc('last_message_at')->get()
            ->filter(function ($c) use ($user) {
                $p = $c->participants->firstWhere('user_id', $user->id);

                return ! $p->cleared_at || ($c->last_message_at && $c->last_message_at->gt($p->cleared_at));
            })->values();
    }

    public function index(Request $request)
    {
        return view('messages.index', ['conversations' => $this->listFor($request->user(), $request->query('q')), 'active' => null, 'messages' => collect()]);
    }

    public function create(Request $request)
    {
        if ($username = $request->query('user')) {
            $other = User::where('username', $username)->firstOrFail();

            return $this->openDirect($request->user(), $other);
        }

        return view('messages.create');
    }

    private function openDirect(User $me, User $other)
    {
        abort_if($me->id === $other->id, 422);
        abort_unless($other->allowsInteraction('allow_messages', $me), 403, __(':name ne reçoit pas de messages de votre part.', ['name' => $other->name]));

        $conversation = Conversation::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $me->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $other->id))->first();

        if (! $conversation) {
            $conversation = DB::transaction(function () use ($me, $other) {
                $c = Conversation::create(['type' => 'direct', 'created_by' => $me->id]);
                $c->participants()->createMany([['user_id' => $me->id], ['user_id' => $other->id]]);

                return $c;
            });
        } else {
            $conversation->participants()->where('user_id', $me->id)->update(['left_at' => null]);
        }

        return redirect()->route('messages.show', $conversation);
    }

    /** Crée une conversation (directe si un seul destinataire, sinon groupe). */
    public function store(Request $request)
    {
        $me = $request->user();
        $data = $request->validate([
            'usernames' => ['required', 'array', 'min:1', 'max:50'],
            'usernames.*' => ['string'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);
        $users = User::whereIn('username', array_map(fn ($u) => ltrim($u, '@'), $data['usernames']))->where('id', '!=', $me->id)->where('status', 'active')->get();
        abort_if($users->isEmpty(), 422, __('Aucun destinataire valide.'));

        if ($users->count() === 1 && empty($data['name'])) {
            return $this->openDirect($me, $users->first());
        }

        $allowed = $users->filter(fn ($u) => $u->allowsInteraction('allow_messages', $me));
        $conversation = DB::transaction(function () use ($me, $allowed, $data) {
            $c = Conversation::create(['type' => 'group', 'name' => $data['name'] ?? null, 'created_by' => $me->id, 'last_message_at' => now()]);
            $c->participants()->create(['user_id' => $me->id, 'is_admin' => true]);
            foreach ($allowed as $u) {
                $c->participants()->create(['user_id' => $u->id]);
            }

            return $c;
        });

        return redirect()->route('messages.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $me = $request->user();
        $p = $this->participant($request, $conversation);
        $p->update(['last_read_at' => now()]);
        $conversation->load('users');

        $messages = $conversation->messages()->with(['user', 'shared'])
            ->when($p->cleared_at, fn ($q) => $q->where('created_at', '>', $p->cleared_at))
            ->when($request->query('q'), fn ($q, $s) => $q->where('body', 'like', "%$s%"))
            ->latest('id')->limit(100)->get()->reverse()->values();

        $other = $conversation->isGroup() ? null : $conversation->otherUser($me);
        $blocked = $other && $me->isBlockedBetween($other);

        return view('messages.index', [
            'conversations' => $this->listFor($me),
            'active' => $conversation,
            'messages' => $messages,
            'participant' => $p,
            'blocked' => $blocked,
            'other' => $other,
        ]);
    }

    public function send(Request $request, Conversation $conversation, MediaService $media, AntiSpam $spam)
    {
        $me = $request->user();
        $this->participant($request, $conversation);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:'.config('vwajen.limits.message_length'), 'required_without:media'],
            'media' => ['nullable', 'file', 'mimes:'.MediaService::IMAGE_MIMES.','.MediaService::VIDEO_MIMES, 'max:102400'],
        ]);

        if (! $conversation->isGroup()) {
            $other = $conversation->otherUser($me);
            abort_if($other && $me->isBlockedBetween($other), 403, __('Vous ne pouvez pas écrire à cet utilisateur.'));
            abort_if($other && ! $other->allowsInteraction('allow_messages', $me), 403, __(':name ne reçoit pas de messages de votre part.', ['name' => $other->name]));
        }
        if (! empty($data['body'])) {
            $spam->checkContent($me, $data['body']);
        }

        $mediaPath = $mediaType = null;
        if ($file = $request->file('media')) {
            $isImage = str_starts_with((string) $file->getMimeType(), 'image/');
            $mediaType = $isImage ? 'image' : 'video';
            $mediaPath = $isImage ? $media->storeImage($file, 'messages/'.date('Y/m'), 1600) : $media->storeFile($file, 'messages/'.date('Y/m'));
        }

        $message = Message::create(['conversation_id' => $conversation->id, 'user_id' => $me->id, 'body' => $data['body'] ?? null,
            'media_type' => $mediaType, 'media_path' => $mediaPath]);
        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()->where('user_id', $me->id)->update(['last_read_at' => now()]);

        $recipients = User::whereIn('id', $conversation->participants()->where('user_id', '!=', $me->id)->whereNull('left_at')->where('muted', false)->select('user_id'))->get();
        foreach ($recipients as $r) {
            // Une notification par conversation non lue (évite le spam de notifications).
            $url = route('messages.show', $conversation);
            $hasUnread = $r->unreadNotifications()->latest()->limit(50)->get()
                ->contains(fn ($n) => ($n->data['type'] ?? null) === 'message' && ($n->data['url'] ?? null) === $url);
            if (! $hasUnread) {
                $this->notifier->send($r, 'message', $me, ':name vous a envoyé un message', ['name' => $me->name], route('messages.show', $conversation), $message);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['html' => view('messages.bubble', ['m' => $message->load('user'), 'me' => $me])->render(), 'id' => $message->id]);
        }

        return back();
    }

    /** Nouveaux messages depuis un identifiant (actualisation légère). */
    public function poll(Request $request, Conversation $conversation)
    {
        $me = $request->user();
        $p = $this->participant($request, $conversation);
        $messages = $conversation->messages()->with(['user', 'shared'])->where('id', '>', (int) $request->query('after', 0))->orderBy('id')->limit(50)->get();
        if ($messages->isNotEmpty()) {
            $p->update(['last_read_at' => now()]);
        }

        return response()->json([
            'html' => $messages->map(fn ($m) => view('messages.bubble', ['m' => $m, 'me' => $me])->render())->implode(''),
            'last' => $messages->last()?->id,
        ]);
    }

    /** Suppression de la conversation (pour soi). */
    public function destroy(Request $request, Conversation $conversation)
    {
        $p = $this->participant($request, $conversation);
        $p->update(['cleared_at' => now()]);

        return redirect()->route('messages.index')->with('status', __('Conversation supprimée.'));
    }

    public function addParticipants(Request $request, Conversation $conversation)
    {
        $p = $this->participant($request, $conversation);
        abort_unless($conversation->isGroup() && $p->is_admin, 403);
        $data = $request->validate(['usernames' => ['required', 'array'], 'usernames.*' => ['string']]);
        foreach (User::whereIn('username', $data['usernames'])->get() as $u) {
            if ($u->allowsInteraction('allow_messages', $request->user())) {
                ConversationParticipant::updateOrCreate(['conversation_id' => $conversation->id, 'user_id' => $u->id], ['left_at' => null]);
            }
        }

        return back()->with('status', __('Participants ajoutés.'));
    }

    public function leave(Request $request, Conversation $conversation)
    {
        $p = $this->participant($request, $conversation);
        abort_unless($conversation->isGroup(), 422);
        $p->update(['left_at' => now()]);

        return redirect()->route('messages.index')->with('status', __('Vous avez quitté le groupe.'));
    }

    public function rename(Request $request, Conversation $conversation)
    {
        $p = $this->participant($request, $conversation);
        abort_unless($conversation->isGroup() && $p->is_admin, 403);
        $conversation->update($request->validate(['name' => ['required', 'string', 'max:100']]));

        return back();
    }

    public function deleteMessage(Request $request, Message $message)
    {
        abort_unless($message->user_id === $request->user()->id, 403);
        $message->delete();

        return $this->reply($request, ['deleted' => true]);
    }

    /** Recherche dans les conversations (messages). */
    public function search(Request $request)
    {
        $me = $request->user();
        $q = trim((string) $request->query('q'));
        $results = $q === '' ? collect() : Message::whereIn('conversation_id', ConversationParticipant::where('user_id', $me->id)->whereNull('left_at')->select('conversation_id'))
            ->where('body', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%')
            ->with(['user', 'conversation.users'])->latest()->limit(50)->get();

        return view('messages.search', compact('q', 'results'));
    }
}
