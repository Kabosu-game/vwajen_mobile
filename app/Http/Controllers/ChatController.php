<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Debate;
use App\Models\Live;
use App\Models\LiveViewer;
use App\Services\AntiSpam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Chat en direct des lives et débats (le chat d'un débat est celui de son live). */
class ChatController extends Controller
{
    private function live(string $type, int $id): Live
    {
        return $type === 'debate' ? Debate::findOrFail($id)->live()->firstOrFail() : Live::findOrFail($id);
    }

    public function index(Request $request, string $type, int $id)
    {
        $live = $this->live($type, $id);
        $canModerate = $live->canModerate($request->user());
        $messages = ChatMessage::where('chatable_type', 'live')->where('chatable_id', $live->id)
            ->where('id', '>', (int) $request->query('after', 0))
            ->when(! $canModerate, fn ($q) => $q->where('is_hidden', false))
            ->with('user:id,name,username,avatar,is_verified')->orderBy('id')->limit(100)->get();

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id, 'body' => e($m->body), 'hidden' => $m->is_hidden, 'pinned' => $m->is_pinned,
                'user' => ['name' => $m->user->name, 'username' => $m->user->username, 'avatar' => $m->user->avatarUrl(),
                    'verified' => $m->user->is_verified, 'host' => $live->isHost($m->user)],
                'time' => $m->created_at->format('H:i'),
            ]),
            'pinned' => optional(ChatMessage::where('chatable_type', 'live')->where('chatable_id', $live->id)->where('is_pinned', true)->where('is_hidden', false)->latest('id')->first(),
                fn ($m) => ['id' => $m->id, 'body' => e($m->body), 'name' => $m->user->name]),
            'enabled' => $live->chat_enabled,
            'slow' => $live->slow_mode_seconds,
            'followers_only' => $live->chat_followers_only,
        ]);
    }

    public function store(Request $request, string $type, int $id, AntiSpam $spam)
    {
        $user = $request->user();
        $live = $this->live($type, $id);
        $data = $request->validate(['body' => ['required', 'string', 'max:500']]);

        abort_unless($live->chat_enabled || $live->canModerate($user), 403, __('Le chat est désactivé.'));
        abort_if(LiveViewer::where('live_id', $live->id)->where('user_id', $user->id)->where('is_banned', true)->exists(), 403, __('Vous avez été bloqué de ce live.'));
        abort_if($live->user && $user->isBlockedBetween($live->user), 403);
        if ($live->chat_followers_only && ! $live->isHost($user) && ! $user->isFollowing($live->user)) {
            abort(403, __('Chat réservé aux abonnés.'));
        }
        if ($live->slow_mode_seconds && ! $live->canModerate($user)) {
            $key = "chat.slow.{$live->id}.{$user->id}";
            abort_if(Cache::has($key), 429, __('Mode lent : patientez avant d\'envoyer un autre message.'));
            Cache::put($key, 1, $live->slow_mode_seconds);
        }
        $spam->checkContent($user, $data['body']);

        $msg = ChatMessage::create(['chatable_type' => 'live', 'chatable_id' => $live->id, 'user_id' => $user->id, 'body' => $data['body'], 'created_at' => now()]);
        $live->increment('comments_count');

        return response()->json(['id' => $msg->id]);
    }

    public function hide(Request $request, ChatMessage $message)
    {
        $live = $message->chatable;
        abort_unless($live instanceof Live && $live->canModerate($request->user()), 403);
        $message->update(['is_hidden' => ! $message->is_hidden]);

        return response()->json(['hidden' => $message->is_hidden]);
    }

    public function pin(Request $request, ChatMessage $message)
    {
        $live = $message->chatable;
        abort_unless($live instanceof Live && $live->canModerate($request->user()), 403);
        ChatMessage::where('chatable_type', 'live')->where('chatable_id', $live->id)->where('id', '!=', $message->id)->update(['is_pinned' => false]);
        $message->update(['is_pinned' => ! $message->is_pinned]);

        return response()->json(['pinned' => $message->is_pinned]);
    }
}
