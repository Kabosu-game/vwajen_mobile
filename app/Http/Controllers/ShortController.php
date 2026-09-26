<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;

/** Vwajèn Shorts : vidéos verticales courtes, défilement vertical, découverte. */
class ShortController extends Controller
{
    public function index(Request $request)
    {
        $shorts = $this->batch($request, []);

        return view('shorts.index', ['shorts' => $shorts, 'start' => null]);
    }

    public function show(Request $request, Video $video)
    {
        abort_unless($video->isShort(), 404);
        abort_if(($video->is_hidden || $video->visibility !== 'public') && ! $video->canBeManagedBy($request->user()), 404);
        $first = Video::withViewerState($request->user())->with('user')->find($video->id);
        $shorts = collect([$first])->merge($this->batch($request, [$video->id]));

        return view('shorts.index', ['shorts' => $shorts, 'start' => $video]);
    }

    /** Lot suivant (JSON + HTML) pour le défilement infini. */
    public function feed(Request $request)
    {
        $exclude = array_map('intval', array_slice(explode(',', (string) $request->query('exclude')), -300));
        $shorts = $this->batch($request, $exclude);

        return response()->json([
            'html' => view('shorts.items', ['shorts' => $shorts])->render(),
            'ids' => $shorts->pluck('id'),
        ]);
    }

    public function create()
    {
        return redirect()->route('videos.create', ['kind' => 'short']);
    }

    /** Mélange : abonnements, populaires récents et nouveautés (découverte). */
    private function batch(Request $request, array $exclude)
    {
        $user = $request->user();
        $base = fn () => Video::shorts()->published()->visibleTo($user)->withViewerState($user)->with('user')
            ->when($exclude, fn ($q) => $q->whereNotIn('videos.id', $exclude));

        $following = $user ? $base()->whereIn('user_id', $user->followingIds())->latest()->limit(4)->get() : collect();
        $popular = $base()->where('created_at', '>=', now()->subDays(14))->orderByDesc('likes_count')->limit(4)->get();
        $fresh = $base()->latest()->limit(4)->get();

        return $following->merge($popular)->merge($fresh)->unique('id')->shuffle()->values();
    }
}
