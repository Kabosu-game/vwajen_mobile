<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\Upload;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoSubtitle;
use App\Models\View;
use App\Services\AiService;
use App\Services\AntiSpam;
use App\Services\ContentService;
use App\Services\FeedService;
use App\Services\MediaService;
use App\Services\Notifier;
use App\Services\TranscriptionService;
use App\Services\VideoProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Vidéos longues, courtes (Shorts) et replays : upload, enregistrement, miniatures, sous-titres, qualités. */
class VideoController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request)
    {
        $viewer = $request->user();
        $sort = $request->query('sort') === 'popular' ? 'popular' : 'recent';
        $kind = in_array($request->query('kind'), ['long', 'replay'], true) ? $request->query('kind') : null;
        $videos = Video::visibleTo($viewer)->published()->longs()->with('user')
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->when($sort === 'popular', fn ($q) => $q->where('created_at', '>=', now()->subDays(30))->orderByDesc('views_count'), fn ($q) => $q->latest())
            ->paginate(24)->withQueryString();

        return view('videos.index', compact('videos', 'sort', 'kind'));
    }

    public function show(Request $request, Video $video)
    {
        $viewer = $request->user();
        abort_if(($video->is_hidden || $video->visibility === 'private') && ! $video->canBeManagedBy($viewer), 404);
        abort_if($viewer && $video->user && $viewer->isBlockedBetween($video->user), 403);
        if ($video->isShort() && ! $request->query('player')) {
            return redirect()->route('shorts.show', $video);
        }
        $video->load(['user', 'subtitles', 'source']);
        $video->liked = $video->isLikedBy($viewer);
        $video->bookmarked = $video->isBookmarkedBy($viewer);
        $video->reposted = $video->isRepostedBy($viewer);

        $comments = $video->rootComments()->where('is_hidden', false)->with(['user', 'replies.user'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $viewer?->id)])->latest()->paginate(20);
        $related = Video::visibleTo($viewer)->published()->longs()->where('id', '!=', $video->id)
            ->where(fn ($q) => $q->where('user_id', $video->user_id)->orWhereIn('id', DB::table('hashtaggables')->where('hashtaggable_type', 'video')
                ->whereIn('hashtag_id', $video->hashtags()->pluck('hashtags.id'))->select('hashtaggable_id')))
            ->with('user')->latest()->limit(8)->get();

        return view('videos.show', compact('video', 'comments', 'related'));
    }

    public function create(Request $request)
    {
        return view('videos.create', ['kind' => $request->query('kind') === 'short' ? 'short' : 'long']);
    }

    public function store(Request $request, AntiSpam $spam, ContentService $content, Notifier $notifier)
    {
        $user = $request->user();
        $spam->ensureCanPublish($user);
        $limits = config('vwajen.limits');
        $data = $request->validate([
            'kind' => ['required', 'in:short,long'],
            'title' => ['required_if:kind,long', 'nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'upload' => ['nullable', 'uuid', 'required_without:file'],
            'file' => ['nullable', 'file', 'mimes:'.MediaService::VIDEO_MIMES, 'max:'.($limits['video_mb'] * 1024)],
            'thumbnail' => ['nullable', 'image', 'max:8192'],
            'visibility' => ['nullable', 'in:public,unlisted,private'],
            'allow_comments' => ['nullable', 'boolean'],
            'lang' => ['nullable', 'in:ht,fr,en'],
            'subtitle' => ['nullable', 'file', 'max:2048'],
            'subtitle_lang' => ['nullable', 'in:ht,fr,en'],
        ]);
        $spam->checkContent($user, ($data['title'] ?? '').' '.($data['description'] ?? ''), 'description');

        $dir = 'videos/'.date('Y/m');
        $path = $request->hasFile('file')
            ? $this->media->storeFile($request->file('file'), $dir)
            : $this->media->adoptUpload(Upload::where('uuid', $data['upload'])->where('user_id', $user->id)->firstOrFail(), $dir);

        $video = Video::create([
            'user_id' => $user->id,
            'kind' => $data['kind'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'path' => $path,
            'thumbnail' => $request->hasFile('thumbnail') ? $this->media->storeImage($request->file('thumbnail'), 'thumbnails', 1280) : null,
            'visibility' => $data['visibility'] ?? 'public',
            'allow_comments' => $request->boolean('allow_comments', true),
            'lang' => $data['lang'] ?? $user->locale,
            'country' => $user->country,
            'size' => Storage::disk('public')->size($path),
            'processing_status' => 'pending',
        ]);
        if ($request->hasFile('subtitle')) {
            $this->saveSubtitle($video, $request->file('subtitle'), $data['subtitle_lang'] ?? $video->lang);
        }
        $content->syncAll($video, ($video->title ?? '').' '.$video->description, $user);

        // Compression, qualités multiples et miniature (ffmpeg) après la réponse HTTP.
        dispatch(fn () => app(VideoProcessor::class)->process($video))->afterResponse();

        if ($video->visibility === 'public') {
            $followers = User::whereIn('id', Follow::where('following_id', $user->id)->where('status', 'accepted')->where('notify', true)->select('follower_id'));
            $notifier->broadcast($followers, 'system', $user, ':name a publié une nouvelle vidéo', ['name' => $user->name], $video->url(), $video);
        }
        FeedService::forget($user);

        if ($request->expectsJson()) {
            return response()->json(['url' => $video->url()]);
        }

        return redirect()->to($video->url())->with('status', __('Vidéo publiée. Le traitement peut prendre quelques minutes.'));
    }

    public function edit(Request $request, Video $video)
    {
        abort_unless($video->user_id === $request->user()->id, 403);
        $video->load('subtitles');

        return view('videos.edit', compact('video'));
    }

    public function update(Request $request, Video $video, ContentService $content)
    {
        abort_unless($video->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', 'image', 'max:8192'],
            'visibility' => ['required', 'in:public,unlisted,private'],
            'allow_comments' => ['nullable', 'boolean'],
        ]);
        if ($request->hasFile('thumbnail')) {
            $this->media->delete($video->thumbnail);
            $data['thumbnail'] = $this->media->storeImage($request->file('thumbnail'), 'thumbnails', 1280);
        }
        $data['allow_comments'] = $request->boolean('allow_comments');
        $video->update($data);
        $content->syncAll($video, ($video->title ?? '').' '.$video->description, $request->user());

        return redirect()->to($video->url())->with('status', __('Vidéo mise à jour.'));
    }

    public function destroy(Request $request, Video $video)
    {
        abort_unless($video->canBeManagedBy($request->user()), 403);
        $video->delete();

        return redirect()->route($video->isShort() ? 'shorts.index' : 'videos.index')->with('status', __('Vidéo supprimée.'));
    }

    /** Sous-titres : fichiers WebVTT ou SRT (convertis en VTT). */
    public function addSubtitle(Request $request, Video $video)
    {
        abort_unless($video->user_id === $request->user()->id, 403);
        $data = $request->validate(['file' => ['required', 'file', 'max:2048'], 'lang' => ['required', 'in:ht,fr,en']]);
        $this->saveSubtitle($video, $request->file('file'), $data['lang']);

        return back()->with('status', __('Sous-titres ajoutés.'));
    }

    private function saveSubtitle(Video $video, $file, string $lang, bool $auto = false): void
    {
        $content = file_get_contents($file->getRealPath());
        $this->storeVtt($video, self::toVtt($content), $lang, $auto);
    }

    private function storeVtt(Video $video, string $vtt, string $lang, bool $auto): void
    {
        $path = 'subtitles/'.$video->id.'-'.$lang.'-'.Str::random(8).'.vtt';
        Storage::disk('public')->put($path, $vtt);
        VideoSubtitle::where('video_id', $video->id)->where('lang', $lang)->get()->each(function ($s) {
            Storage::disk('public')->delete($s->path);
            $s->delete();
        });
        $video->subtitles()->create(['lang' => $lang, 'label' => config("vwajen.locales.$lang.name", $lang).($auto ? ' (auto)' : ''), 'path' => $path, 'is_auto' => $auto]);
    }

    public static function toVtt(string $content): string
    {
        $content = str_replace("\r", '', preg_replace('/^\xEF\xBB\xBF/', '', $content));
        if (str_starts_with(ltrim($content), 'WEBVTT')) {
            return $content;
        }

        return "WEBVTT\n\n".preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $content);
    }

    public function removeSubtitle(Request $request, VideoSubtitle $subtitle)
    {
        abort_unless($subtitle->video->user_id === $request->user()->id, 403);
        Storage::disk('public')->delete($subtitle->path);
        $subtitle->delete();

        return back()->with('status', __('Sous-titres supprimés.'));
    }

    /** Sous-titres automatiques : transcription puis traduction dans les autres langues. */
    public function autoSubtitles(Request $request, Video $video, TranscriptionService $transcriber, AiService $ai)
    {
        abort_unless($video->user_id === $request->user()->id, 403);
        abort_unless($transcriber->enabled(), 503, __('La transcription automatique n\'est pas configurée.'));

        $result = $transcriber->transcribeFile(Storage::disk('public')->path($video->path), $video->lang);
        abort_unless($result, 502, __('Transcription impossible pour le moment.'));
        $video->update(['transcript' => $result['text']]);
        $this->storeVtt($video, $result['vtt'], $video->lang ?? 'ht', true);

        foreach (array_diff(['ht', 'fr', 'en'], [$video->lang ?? 'ht']) as $lang) {
            if ($ai->enabled() && ($translated = $ai->translateVtt($result['vtt'], $lang))) {
                $this->storeVtt($video, $translated, $lang, true);
            }
        }

        return back()->with('status', __('Sous-titres automatiques générés.'));
    }

    /** Compte une vue (une fois par session) et le temps de visionnage. */
    public function view(Request $request, Video $video)
    {
        $seconds = min(36000, max(0, (int) $request->input('seconds', 0)));
        $key = 'viewed.video.'.$video->id;
        if (! $request->session()->has($key)) {
            $request->session()->put($key, true);
            $video->increment('views_count');
            View::create(['user_id' => $request->user()?->id, 'viewable_type' => 'video', 'viewable_id' => $video->id,
                'session_hash' => hash('sha256', $request->session()->getId()), 'watch_seconds' => $seconds, 'created_at' => now()]);
        } elseif ($seconds) {
            View::where('viewable_type', 'video')->where('viewable_id', $video->id)
                ->where('session_hash', hash('sha256', $request->session()->getId()))->latest('id')->limit(1)
                ->update(['watch_seconds' => $seconds]);
        }

        return response()->json(['ok' => true]);
    }
}
