<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\ContentTranslation;
use App\Models\Conversation;
use App\Models\HiddenContent;
use App\Models\Like;
use App\Models\Message;
use App\Models\Repost;
use App\Models\Share;
use App\Models\User;
use App\Services\AiService;
use App\Services\FeedService;
use App\Services\Notifier;
use App\Support\Morph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    private function bump(Model $model, string $column, int $by): void
    {
        if (array_key_exists($column, $model->getAttributes())) {
            $by > 0 ? $model->increment($column, $by) : ($model->{$column} > 0 ? $model->decrement($column, -$by) : null);
        }
    }

    public function like(Request $request, string $type, int $id)
    {
        $user = $request->user();
        $model = $this->interactable($type, $id);

        $existing = Like::where(['user_id' => $user->id, 'likeable_type' => $type, 'likeable_id' => $id])->first();
        if ($existing) {
            $existing->delete();
            $this->bump($model, 'likes_count', -1);
            $liked = false;
        } else {
            Like::create(['user_id' => $user->id, 'likeable_type' => $type, 'likeable_id' => $id]);
            $this->bump($model, 'likes_count', 1);
            $liked = true;
            if ($model->user) {
                $this->notifier->send($model->user, 'like', $user, ':name a aimé votre :type', ['name' => $user->name, 'type' => '__:'.Morph::key($type)], $model->url(), $model);
            }
        }

        return $this->reply($request, ['active' => $liked, 'count' => (int) $model->fresh()->likes_count]);
    }

    public function bookmark(Request $request, string $type, int $id)
    {
        $user = $request->user();
        $model = $this->interactable($type, $id);
        $existing = Bookmark::where(['user_id' => $user->id, 'bookmarkable_type' => $type, 'bookmarkable_id' => $id])->first();
        if ($existing) {
            $existing->delete();
            $this->bump($model, 'bookmarks_count', -1);
        } else {
            Bookmark::create(['user_id' => $user->id, 'bookmarkable_type' => $type, 'bookmarkable_id' => $id]);
            $this->bump($model, 'bookmarks_count', 1);
        }

        return $this->reply($request, ['active' => ! $existing], $existing ? __('Retiré des enregistrements.') : __('Enregistré.'));
    }

    public function repost(Request $request, string $type, int $id)
    {
        $user = $request->user();
        $model = $this->interactable($type, $id);
        abort_if(($model->visibility ?? 'public') !== 'public', 403, __('Ce contenu ne peut pas être reposté.'));

        $existing = Repost::where(['user_id' => $user->id, 'repostable_type' => $type, 'repostable_id' => $id])->first();
        if ($existing) {
            $existing->delete();
            $this->bump($model, 'reposts_count', -1);
        } else {
            Repost::create(['user_id' => $user->id, 'repostable_type' => $type, 'repostable_id' => $id]);
            $this->bump($model, 'reposts_count', 1);
            if ($model->user) {
                $this->notifier->send($model->user, 'repost', $user, ':name a reposté votre :type', ['name' => $user->name, 'type' => '__:'.Morph::key($type)], $model->url(), $model);
            }
        }
        FeedService::forget($user);

        return $this->reply($request, ['active' => ! $existing, 'count' => (int) ($model->fresh()->reposts_count ?? 0)],
            $existing ? __('Repost annulé.') : __('Reposté.'));
    }

    /** Journalise un partage (copie de lien, partage natif, WhatsApp, etc.). */
    public function share(Request $request, string $type, int $id)
    {
        $data = $request->validate(['channel' => ['required', 'in:link,native,whatsapp,facebook,x,telegram,email,internal,message']]);
        $model = Morph::findOrFail($type, $id, array_merge(Morph::INTERACTABLE, ['user', 'community']));
        Share::create(['user_id' => $request->user()?->id, 'shareable_type' => $type, 'shareable_id' => $id, 'channel' => $data['channel']]);
        $this->bump($model, 'shares_count', 1);

        return response()->json(['ok' => true]);
    }

    /** Partage interne : envoyer un contenu dans une conversation privée. */
    public function sendInternal(Request $request, string $type, int $id)
    {
        $user = $request->user();
        $model = Morph::findOrFail($type, $id, array_merge(Morph::INTERACTABLE, ['user', 'community']));
        $data = $request->validate(['conversation_id' => ['required', 'integer'], 'body' => ['nullable', 'string', 'max:500']]);
        $conversation = Conversation::whereKey($data['conversation_id'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id)->whereNull('left_at'))->firstOrFail();

        Message::create(['conversation_id' => $conversation->id, 'user_id' => $user->id, 'body' => $data['body'] ?? null,
            'shared_type' => $type, 'shared_id' => $id]);
        $conversation->update(['last_message_at' => now()]);
        Share::create(['user_id' => $user->id, 'shareable_type' => $type, 'shareable_id' => $id, 'channel' => 'message']);
        $this->bump($model, 'shares_count', 1);

        return $this->reply($request, ['ok' => true], __('Envoyé dans la conversation.'));
    }

    /** Masquer un contenu pour soi. */
    public function hide(Request $request, string $type, int $id)
    {
        Morph::findOrFail($type, $id, Morph::INTERACTABLE);
        HiddenContent::firstOrCreate(['user_id' => $request->user()->id, 'hideable_type' => $type, 'hideable_id' => $id]);
        FeedService::forget($request->user());

        return $this->reply($request, ['hidden' => true], __('Contenu masqué. Vous ne le verrez plus.'));
    }

    public function likers(Request $request, string $type, int $id)
    {
        $model = Morph::findOrFail($type, $id, array_merge(Morph::INTERACTABLE, ['comment']));
        $users = User::whereIn('id', $model->likes()->select('user_id'))->where('status', 'active')->paginate(30);

        return view('social.user-list', ['users' => $users, 'title' => __('Mentions J\'aime')]);
    }

    /** Traduction automatique d'un contenu (IA) dans la langue de l'interface. */
    public function translate(Request $request, string $type, int $id, AiService $ai)
    {
        $model = Morph::findOrFail($type, $id, array_merge(Morph::INTERACTABLE, ['comment']));
        $locale = app()->getLocale();
        $field = $model->body !== null ? 'body' : ($model->description !== null ? 'description' : 'title');
        $source = (string) $model->{$field};
        abort_if($source === '', 422);

        $cached = ContentTranslation::where(['translatable_type' => $type, 'translatable_id' => $id, 'field' => $field, 'locale' => $locale])->value('value');
        if (! $cached) {
            abort_unless($ai->enabled(), 503, __('La traduction automatique n\'est pas activée.'));
            $cached = $ai->translate($source, $locale);
            abort_unless($cached, 502, __('Traduction indisponible pour le moment.'));
            ContentTranslation::updateOrCreate(['translatable_type' => $type, 'translatable_id' => $id, 'field' => $field, 'locale' => $locale], ['value' => $cached]);
        }

        return response()->json(['html' => (string) render_text($cached)]);
    }
}
