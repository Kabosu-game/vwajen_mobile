<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Video;
use App\Services\FeedService;
use Illuminate\Http\Request;

class HashtagController extends Controller
{
    public function show(Request $request, string $hashtag)
    {
        $tag = Hashtag::where('name', mb_strtolower($hashtag))->where('is_blocked', false)->firstOrFail();
        $viewer = $request->user();
        $tab = $request->query('tab') === 'videos' ? 'videos' : ($request->query('tab') === 'recent' ? 'recent' : 'top');

        if ($tab === 'videos') {
            $items = Video::whereHas('hashtags', fn ($q) => $q->whereKey($tag->id))->visibleTo($viewer)->published()->with('user')->latest()->paginate(24);
        } else {
            $q = Post::whereHas('hashtags', fn ($q) => $q->whereKey($tag->id))->visibleTo($viewer)->audienceFor($viewer)->withCardRelations($viewer);
            $items = ($tab === 'top' ? $q->orderByDesc('likes_count')->latest() : $q->latest())->paginate(20);
        }
        $items->withQueryString();
        $following = $viewer && $viewer->followedHashtags()->whereKey($tag->id)->exists();

        return view('social.hashtag', compact('tag', 'items', 'tab', 'following'));
    }

    public function follow(Request $request, string $hashtag)
    {
        $tag = Hashtag::where('name', mb_strtolower($hashtag))->firstOrFail();
        $result = $request->user()->followedHashtags()->toggle($tag->id);
        FeedService::forget($request->user());
        $active = ! empty($result['attached']);

        return $this->reply($request, ['active' => $active], $active ? __('Vous suivez #:tag', ['tag' => $tag->name]) : __('Vous ne suivez plus #:tag', ['tag' => $tag->name]));
    }
}
