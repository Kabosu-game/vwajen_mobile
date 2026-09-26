<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use App\Models\Event;
use App\Models\Hashtag;
use App\Models\Live;
use App\Models\Post;
use App\Models\Question;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Découvrir : utilisateurs, candidats, vidéos, Shorts, lives, débats, événements, questions, tendances. */
class DiscoverController extends Controller
{
    public const SECTIONS = ['all', 'users', 'candidates', 'videos', 'shorts', 'lives', 'debates', 'events', 'questions', 'recent'];

    public function index(Request $request)
    {
        $viewer = $request->user();
        $section = in_array($request->query('section'), self::SECTIONS, true) ? $request->query('section') : 'all';
        $limit = $section === 'all' ? 6 : 24;
        $exclude = $viewer ? array_merge($viewer->followingIds(), [$viewer->id], $viewer->blockedIdsBothWays()) : [];

        $data = ['section' => $section, 'trends' => $this->trendingHashtags(10)];
        $want = fn (string $s) => $section === 'all' || $section === $s;

        if ($want('users')) {
            // Suggestions : amis d'amis, puis comptes populaires de la même zone.
            $fof = $viewer ? DB::table('follows')->whereIn('follower_id', $viewer->followingIds() ?: [0])->where('status', 'accepted')
                ->whereNotIn('following_id', $exclude ?: [0])->select('following_id', DB::raw('COUNT(*) c'))->groupBy('following_id')
                ->orderByDesc('c')->limit($limit)->pluck('following_id')->all() : [];
            $data['users'] = User::where('status', 'active')->where('searchable', true)->whereNotIn('id', $exclude ?: [0])
                ->when($fof, fn ($q) => $q->orderByRaw('FIELD(id, '.implode(',', array_map('intval', $fof)).') = 0'))
                ->when($viewer?->country, fn ($q, $c) => $q->orderByRaw('country = ? desc', [$c]))
                ->orderByDesc('followers_count')->limit($limit)->get();
        }
        if ($want('candidates')) {
            $data['candidates'] = User::where('account_type', 'candidate')->where('status', 'active')->whereHas('candidateProfile')
                ->with('candidateProfile')->inRandomOrder()->limit($limit)->get(); // ordre aléatoire : aucune mise en avant
        }
        if ($want('videos')) {
            $data['videos'] = Video::visibleTo($viewer)->published()->longs()->with('user')
                ->where('created_at', '>=', now()->subDays(30))->orderByDesc('views_count')->limit($limit)->get();
        }
        if ($want('shorts')) {
            $data['shorts'] = Video::visibleTo($viewer)->published()->shorts()->with('user')
                ->where('created_at', '>=', now()->subDays(14))->orderByDesc('likes_count')->limit($section === 'all' ? 8 : 24)->get();
        }
        if ($want('lives')) {
            $data['lives'] = Live::visibleTo($viewer)->whereIn('status', ['live', 'scheduled'])->with('user')
                ->orderByRaw("status = 'live' desc")->orderBy('scheduled_at')->limit($limit)->get();
        }
        if ($want('debates')) {
            $data['debates'] = Debate::visibleTo($viewer)->whereIn('status', ['live', 'scheduled'])->with('participants.user')
                ->orderByRaw("status = 'live' desc")->orderBy('scheduled_at')->limit($limit)->get();
        }
        if ($want('events')) {
            $data['events'] = Event::visibleTo($viewer)->upcoming()->with('user')
                ->when($viewer?->department, fn ($q, $d) => $q->orderByRaw('department = ? desc', [$d]))->limit($limit)->get();
        }
        if ($want('questions')) {
            $data['questions'] = Question::visibleTo($viewer)->where('status', 'open')->with(['user', 'candidate'])
                ->orderByDesc('supports_count')->limit($limit)->get();
        }
        if ($want('recent')) {
            $data['recent'] = Post::visibleTo($viewer)->audienceFor($viewer)->where('visibility', 'public')->withCardRelations($viewer)
                ->latest()->limit($section === 'all' ? 5 : 30)->get();
        }

        return view('discover.index', $data);
    }

    public function trends(Request $request)
    {
        $viewer = $request->user();
        $popular = Post::visibleTo($viewer)->audienceFor($viewer)->where('visibility', 'public')->popular(2)->withCardRelations($viewer)->limit(20)->get();

        return view('discover.trends', ['hashtags' => $this->trendingHashtags(30), 'popular' => $popular]);
    }

    /** Hashtags populaires : utilisations des 48 dernières heures. */
    public function trendingHashtags(int $limit)
    {
        // Le cache ne contient que des tableaux (les objets ne sont pas désérialisés, par sécurité).
        $rows = Cache::remember("trends.$limit", now()->addMinutes(10), fn () => Hashtag::query()
            ->where('is_blocked', false)
            ->join('hashtaggables', 'hashtaggables.hashtag_id', '=', 'hashtags.id')
            ->where('hashtaggables.created_at', '>=', now()->subHours(48))
            ->select('hashtags.id', 'hashtags.name', DB::raw('COUNT(*) as recent_uses'))
            ->groupBy('hashtags.id', 'hashtags.name')->orderByDesc('recent_uses')->limit($limit)->get()
            ->map(fn ($h) => ['id' => $h->id, 'name' => $h->name, 'recent_uses' => (int) $h->recent_uses])->all());

        return collect($rows)->map(fn ($r) => (object) $r);
    }
}
