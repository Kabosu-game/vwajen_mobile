<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Services\AntiSpam;
use App\Services\ContentService;
use App\Services\Notifier;
use App\Support\Morph;
use Illuminate\Http\Request;

/** Commentaires et réponses aux commentaires, pour tout contenu interactif. */
class CommentController extends Controller
{
    public function index(Request $request, string $type, int $id)
    {
        $model = $this->interactable($type, $id);
        $user = $request->user();
        $comments = $model->rootComments()->where('is_hidden', false)
            ->when($user, fn ($q) => $q->whereNotIn('user_id', $user->blockedIdsBothWays()))
            ->with(['user', 'replies.user'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $user?->id)])
            ->orderByDesc($request->query('sort') === 'top' ? 'likes_count' : 'created_at')
            ->paginate(20)->withQueryString();

        return view('partials.comments', ['comments' => $comments, 'target' => $model, 'type' => $type]);
    }

    public function store(Request $request, string $type, int $id, AntiSpam $spam, ContentService $content, Notifier $notifier)
    {
        $user = $request->user();
        $spam->ensureCanPublish($user);
        $model = $this->interactable($type, $id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:'.config('vwajen.limits.comment_length')],
            'parent_id' => ['nullable', 'integer'],
        ]);

        if ($model->user && ! $model->user->allowsInteraction('allow_comments', $user) && $user->id !== $model->user_id) {
            abort(403, __('L\'auteur a limité les commentaires.'));
        }
        abort_if(($model->allow_comments ?? true) === false, 403, __('Les commentaires sont désactivés.'));
        abort_if(($model->is_locked ?? false), 403, __('Cette discussion est verrouillée.'));
        $spam->checkContent($user, $data['body']);

        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = Comment::where('commentable_type', $type)->where('commentable_id', $id)->findOrFail($data['parent_id']);
            if ($parent->parent_id) {
                $parent = $parent->parent; // un seul niveau de réponses
            }
        }

        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_type' => $type,
            'commentable_id' => $id,
            'parent_id' => $parent?->id,
            'body' => $data['body'],
        ]);
        if (array_key_exists('comments_count', $model->getAttributes())) {
            $model->increment('comments_count');
        }
        $parent?->increment('replies_count');
        $content->syncMentions($comment, $comment->body, $user, $comment->url());

        if ($parent) {
            $notifier->send($parent->user, 'comment', $user, ':name a répondu à votre commentaire', ['name' => $user->name], $comment->url(), $comment);
        }
        if ($model->user && $model->user_id !== $parent?->user_id) {
            $notifier->send($model->user, 'comment', $user, ':name a commenté votre :type', ['name' => $user->name, 'type' => '__:'.Morph::key($type)], $comment->url(), $comment);
        }

        if ($request->expectsJson()) {
            return response()->json(['html' => view('partials.comment', ['comment' => $comment->load('user'), 'target' => $model, 'type' => $type])->render()]);
        }

        return back()->with('status', __('Commentaire publié.'));
    }

    public function update(Request $request, Comment $comment, AntiSpam $spam)
    {
        abort_unless($request->user()->id === $comment->user_id, 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:'.config('vwajen.limits.comment_length')]]);
        $spam->checkContent($request->user(), $data['body']);
        $comment->update(['body' => $data['body'], 'edited_at' => now()]);

        return $this->reply($request, ['html' => (string) render_text($comment->body)], __('Commentaire modifié.'));
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();
        $target = $comment->commentable;
        $canDelete = $user->id === $comment->user_id || $user->hasPermission('content.delete')
            || ($target && ($target->user_id ?? null) === $user->id); // l'auteur du contenu peut supprimer les commentaires
        abort_unless($canDelete, 403);

        $comment->delete();
        if ($target && array_key_exists('comments_count', $target->getAttributes()) && $target->comments_count > 0) {
            $target->decrement('comments_count');
        }
        if ($comment->parent_id) {
            Comment::whereKey($comment->parent_id)->where('replies_count', '>', 0)->decrement('replies_count');
        }

        return $this->reply($request, ['deleted' => true], __('Commentaire supprimé.'));
    }

    public function like(Request $request, Comment $comment, Notifier $notifier)
    {
        $user = $request->user();
        $existing = Like::where(['user_id' => $user->id, 'likeable_type' => 'comment', 'likeable_id' => $comment->id])->first();
        if ($existing) {
            $existing->delete();
            $comment->likes_count > 0 && $comment->decrement('likes_count');
        } else {
            Like::create(['user_id' => $user->id, 'likeable_type' => 'comment', 'likeable_id' => $comment->id]);
            $comment->increment('likes_count');
            $notifier->send($comment->user, 'like', $user, ':name a aimé votre commentaire', ['name' => $user->name], $comment->url(), $comment);
        }

        return $this->reply($request, ['active' => ! $existing, 'count' => $comment->fresh()->likes_count]);
    }
}
