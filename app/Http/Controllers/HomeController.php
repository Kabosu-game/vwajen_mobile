<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use App\Models\Live;
use App\Services\FeedService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public const MODES = ['personalized', 'chronological', 'popular', 'recent'];

    public function index(Request $request, FeedService $feed)
    {
        $user = $request->user();
        $mode = in_array($request->query('feed'), self::MODES, true) ? $request->query('feed') : ($user?->feed_mode ?? 'popular');
        if ($user && $request->has('feed') && $user->feed_mode !== $mode) {
            $user->forceFill(['feed_mode' => $mode])->saveQuietly();
        }

        $items = $feed->feed($mode, $user, max(1, (int) $request->query('page', 1)));

        if ($request->ajax() || $request->query('partial')) {
            return view('partials.feed-items', compact('items'));
        }

        $liveNow = Live::where('status', 'live')->visibleTo($user)->with('user')->latest('started_at')->limit(8)->get();
        $upcomingDebates = Debate::where('status', 'scheduled')->where('scheduled_at', '>=', now())->visibleTo($user)
            ->orderBy('scheduled_at')->limit(3)->get();

        return view('home', compact('items', 'mode', 'liveNow', 'upcomingDebates'));
    }
}
