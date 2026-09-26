<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Post;
use App\Models\PostEdit;
use App\Models\Upload;
use App\Models\View;
use App\Services\AntiSpam;
use App\Services\AuditLogger;
use App\Services\ContentService;
use App\Services\FeedService;
use App\Services\MediaService;
use App\Services\Notifier;
use App\Support\TextParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Publier / modifier / supprimer une Vwa (texte, photos, vidéos, liens, sondages). */
class PostController extends Controller
{
    public function __construct(private MediaService $media, private ContentService $content, private AntiSpam $spam) {}

    public function show(Request $request, Post $post)
    {
        $user = $request->user();
        abort_if($post->is_hidden && ! $user?->isStaff() && $user?->id !== $post->user_id, 404);
        abort_unless(Post::whereKey($post->id)->audienceFor($user)->exists() || $user?->isStaff(), 403);
        abort_if($user && $post->user && $user->isBlockedBetween($post->user), 403);

        $post = Post::withCardRelations($user)->findOrFail($post->id);
        $this->recordView($request, $post);

        $comments = $post->rootComments()->where('is_hidden', false)
            ->with(['user', 'replies.user'])->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $user?->id)])
            ->latest()->paginate(20);

        return view('posts.show', compact('post', 'comments'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->spam->ensureCanPublish($user);
        $limits = config('vwajen.limits');

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:'.$limits['post_length']],
            'images' => ['nullable', 'array', 'max:'.$limits['post_images']],
            'images.*' => ['image', 'mimes:'.MediaService::IMAGE_MIMES, 'max:'.$limits['image_kb']],
            'alts' => ['nullable', 'array'],
            'alts.*' => ['nullable', 'string', 'max:250'],
            'video' => ['nullable', 'file', 'mimes:'.MediaService::VIDEO_MIMES, 'max:'.($limits['video_mb'] * 1024)],
            'video_upload' => ['nullable', 'uuid'],
            'visibility' => ['nullable', 'in:public,followers,community'],
            'community_id' => ['nullable', 'exists:communities,id'],
            'quote_of_id' => ['nullable', 'exists:posts,id'],
            'poll_options' => ['nullable', 'array', 'min:2', 'max:4'],
            'poll_options.*' => ['nullable', 'string', 'max:100'],
            'poll_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'poll_multiple' => ['nullable', 'boolean'],
            'lang' => ['nullable', 'in:ht,fr,en'],
        ]);

        $pollOptions = array_values(array_filter($data['poll_options'] ?? [], fn ($o) => trim((string) $o) !== ''));
        $hasContent = filled($data['body'] ?? null) || $request->hasFile('images') || $request->hasFile('video')
            || filled($data['video_upload'] ?? null) || count($pollOptions) >= 2 || filled($data['quote_of_id'] ?? null);
        if (! $hasContent) {
            return back()->withErrors(['body' => __('Votre Vwa est vide.')])->withInput();
        }
        $this->spam->checkContent($user, $data['body'] ?? null);

        $community = null;
        if (! empty($data['community_id'])) {
            $community = Community::findOrFail($data['community_id']);
            abort_unless($community->isMember($user), 403, __('Rejoignez la communauté pour y publier.'));
        }

        $post = DB::transaction(function () use ($data, $user, $request, $pollOptions, $community) {
            $url = TextParser::firstUrl($data['body'] ?? null);
            $post = Post::create([
                'user_id' => $user->id,
                'community_id' => $community?->id,
                'quote_of_id' => $data['quote_of_id'] ?? null,
                'body' => $data['body'] ?? null,
                'link_url' => $url,
                'visibility' => $community ? ($community->visibility === 'private' ? 'community' : 'public') : ($data['visibility'] ?? 'public'),
                'lang' => $data['lang'] ?? $user->locale,
                'country' => $user->country,
            ]);

            foreach ($request->file('images', []) as $i => $file) {
                $path = $this->media->storeImage($file, 'posts/'.date('Y/m'));
                [$w, $h] = $this->media->imageSize($path);
                $post->media()->create(['type' => 'image', 'path' => $path, 'alt' => $data['alts'][$i] ?? null,
                    'width' => $w, 'height' => $h, 'position' => $i]);
            }

            if ($request->hasFile('video') || ! empty($data['video_upload'])) {
                $path = $request->hasFile('video')
                    ? $this->media->storeFile($request->file('video'), 'posts/'.date('Y/m'))
                    : $this->media->adoptUpload(Upload::where('uuid', $data['video_upload'])->where('user_id', $user->id)->firstOrFail(), 'posts/'.date('Y/m'));
                $post->media()->create(['type' => 'video', 'path' => $path, 'position' => 10]);
            }

            if (count($pollOptions) >= 2) {
                $poll = Poll::create([
                    'post_id' => $post->id,
                    'multiple' => (bool) ($data['poll_multiple'] ?? false),
                    'ends_at' => now()->addHours((int) ($data['poll_hours'] ?? 24)),
                ]);
                foreach ($pollOptions as $i => $label) {
                    $poll->options()->create(['label' => $label, 'position' => $i]);
                }
            }

            $user->increment('posts_count');

            return $post;
        });

        if ($post->link_url) {
            $post->update(TextParser::linkPreview($post->link_url));
        }
        $this->content->syncAll($post, $post->body, $user);

        if ($post->quote_of_id && $post->quoteOf) {
            app(Notifier::class)->send($post->quoteOf->user, 'repost', $user, ':name a cité votre Vwa', ['name' => $user->name], $post->url(), $post);
        }
        FeedService::forget($user);

        if ($request->expectsJson()) {
            return response()->json(['id' => $post->id, 'url' => $post->url(),
                'html' => view('partials.post-card', ['post' => Post::withCardRelations($user)->find($post->id)])->render()]);
        }

        return back()->with('status', __('Votre Vwa a été publiée.'));
    }

    public function edit(Request $request, Post $post)
    {
        abort_unless($post->canBeEditedBy($request->user()), 403);

        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $user = $request->user();
        abort_unless($post->canBeEditedBy($user), 403);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:'.config('vwajen.limits.post_length')],
            'visibility' => ['nullable', 'in:public,followers,community'],
        ]);
        $this->spam->checkContent($user, $data['body'] ?? null);

        if (($data['body'] ?? null) !== $post->body) {
            PostEdit::create(['post_id' => $post->id, 'body' => $post->body]); // historique des modifications
            $url = TextParser::firstUrl($data['body'] ?? null);
            $post->fill(['body' => $data['body'], 'edited_at' => now(), 'link_url' => $url]);
            if ($url && $url !== $post->getOriginal('link_url')) {
                $post->fill(TextParser::linkPreview($url) + ['link_title' => null, 'link_description' => null, 'link_image' => null]);
            }
        }
        if (! empty($data['visibility']) && ! $post->community_id) {
            $post->visibility = $data['visibility'];
        }
        $post->save();
        $this->content->syncAll($post, $post->body, $user);

        return redirect()->route('posts.show', $post)->with('status', __('Vwa modifiée.'));
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();
        abort_unless($post->canBeDeletedBy($user), 403);
        if ($user->id !== $post->user_id) {
            AuditLogger::log('post.delete', $post);
        }
        $post->delete();
        $post->user->decrement('posts_count');
        FeedService::forget($post->user);

        return $request->expectsJson()
            ? response()->json(['deleted' => true])
            : redirect()->route('home')->with('status', __('Vwa supprimée.'));
    }

    public function vote(Request $request, Poll $poll)
    {
        $user = $request->user();
        abort_unless($poll->isOpen(), 422, __('Ce sondage est terminé.'));
        $data = $request->validate(['options' => ['required', 'array', 'min:1'], 'options.*' => ['integer']]);
        $optionIds = $poll->options()->whereIn('id', $data['options'])->pluck('id')->all();
        abort_if(! $optionIds || (! $poll->multiple && count($optionIds) > 1), 422);
        abort_if($poll->votes()->where('user_id', $user->id)->exists(), 422, __('Vous avez déjà voté.'));

        DB::transaction(function () use ($poll, $optionIds, $user) {
            foreach ($optionIds as $id) {
                PollVote::create(['poll_id' => $poll->id, 'poll_option_id' => $id, 'user_id' => $user->id]);
                $poll->options()->whereKey($id)->increment('votes_count');
            }
            $poll->increment('votes_count');
        });

        $poll->load('options');
        if ($request->expectsJson()) {
            return response()->json(['html' => view('partials.poll', ['poll' => $poll, 'post' => $poll->post])->render()]);
        }

        return back();
    }

    public function preview(Request $request)
    {
        $url = $request->query('url');
        abort_unless(filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https?://~', $url), 422);

        return response()->json(TextParser::linkPreview($url));
    }

    private function recordView(Request $request, Post $post): void
    {
        $key = 'viewed.post.'.$post->id;
        if ($request->session()->has($key)) {
            return;
        }
        $request->session()->put($key, true);
        $post->increment('views_count');
        View::create(['user_id' => $request->user()?->id, 'viewable_type' => 'post', 'viewable_id' => $post->id,
            'session_hash' => hash('sha256', $request->session()->getId()), 'created_at' => now()]);
    }
}
