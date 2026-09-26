<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Models\Upload;
use App\Services\MediaService;
use App\Services\TranscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Podcasts et streaming audio. */
class PodcastController extends Controller
{
    public function index(Request $request)
    {
        $podcasts = Podcast::where('is_hidden', false)->with('user')->withCount('episodes')
            ->when($request->query('category'), fn ($q, $c) => $q->whereHas('category', fn ($x) => $x->where('slug', $c)))
            ->latest()->paginate(18)->withQueryString();
        $latest = PodcastEpisode::whereNotNull('published_at')->whereHas('podcast', fn ($q) => $q->where('is_hidden', false))
            ->with('podcast.user')->latest('published_at')->limit(10)->get();

        return view('podcasts.index', ['podcasts' => $podcasts, 'latest' => $latest, 'categories' => Category::allActive()]);
    }

    public function show(Podcast $podcast)
    {
        abort_if($podcast->is_hidden, 404);
        $podcast->load(['user', 'episodes', 'category']);

        return view('podcasts.show', compact('podcast'));
    }

    public function create()
    {
        return view('podcasts.create', ['categories' => Category::allActive()]);
    }

    public function store(Request $request, MediaService $media)
    {
        abort_unless($request->user()->hasVerifiedEmail(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'lang' => ['required', 'in:ht,fr,en'],
        ]);
        $data['cover'] = $request->hasFile('cover') ? $media->storeImage($request->file('cover'), 'podcasts', 1000) : null;
        $podcast = Podcast::create($data + ['user_id' => $request->user()->id]);

        return redirect()->route('podcasts.show', $podcast)->with('status', __('Podcast créé. Ajoutez votre premier épisode.'));
    }

    public function storeEpisode(Request $request, Podcast $podcast, MediaService $media, TranscriptionService $transcriber)
    {
        abort_unless($podcast->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'audio' => ['nullable', 'file', 'mimes:mp3,m4a,ogg,wav,webm,aac', 'max:512000', 'required_without:upload'],
            'upload' => ['nullable', 'uuid'],
            'transcribe' => ['nullable', 'boolean'],
        ]);
        $path = $request->hasFile('audio')
            ? $media->storeFile($request->file('audio'), 'podcasts/audio')
            : $media->adoptUpload(Upload::where('uuid', $data['upload'])->where('user_id', $request->user()->id)->firstOrFail(), 'podcasts/audio');

        $episode = $podcast->episodes()->create(['title' => $data['title'], 'description' => $data['description'] ?? null,
            'audio_path' => $path, 'published_at' => now()]);

        if ($request->boolean('transcribe') && $transcriber->enabled()) {
            dispatch(function () use ($episode) {
                $r = app(TranscriptionService::class)->transcribeFile(Storage::disk('public')->path($episode->audio_path));
                if ($r) {
                    $episode->update(['transcript' => $r['text']]);
                }
            })->afterResponse();
        }

        return back()->with('status', __('Épisode publié.'));
    }

    public function play(PodcastEpisode $episode)
    {
        $episode->increment('plays_count');

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Podcast $podcast)
    {
        abort_unless($podcast->user_id === $request->user()->id || $request->user()->hasPermission('content.delete'), 403);
        $podcast->delete();

        return redirect()->route('podcasts.index')->with('status', __('Podcast supprimé.'));
    }
}
