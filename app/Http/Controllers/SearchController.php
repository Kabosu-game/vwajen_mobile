<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\User;
use App\Services\AiService;
use App\Services\SearchService;
use Illuminate\Http\Request;

/** Recherche globale / par type (utilisateurs, candidats, publications, vidéos, Shorts, événements, débats, questions, hashtags). */
class SearchController extends Controller
{
    public function index(Request $request, SearchService $search, AiService $ai)
    {
        $q = trim((string) $request->query('q'));
        $type = in_array($request->query('type'), SearchService::TYPES, true) ? $request->query('type') : 'all';
        $filters = $request->only(['period', 'sort', 'verified', 'account_type', 'country', 'department', 'position', 'media', 'from', 'status', 'when']);
        $smart = $request->boolean('smart') && $ai->enabled();
        $results = null;
        $terms = [];

        if (mb_strlen($q) >= 1) {
            $results = $type === 'all' ? $search->global($q, $request->user(), $smart) : $search->search($type, $q, $filters, $request->user(), $smart);
            $terms = $smart ? $search->terms($q, true) : [];
        }

        return view('search.index', compact('q', 'type', 'filters', 'results', 'smart', 'terms') + ['aiEnabled' => $ai->enabled()]);
    }

    /** Autocomplétion : utilisateurs et hashtags. */
    public function suggest(Request $request)
    {
        $q = ltrim(trim((string) $request->query('q')), '@#');
        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }
        $like = str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $users = User::where('status', 'active')->where('searchable', true)
            ->where(fn ($w) => $w->where('username', 'like', $like)->orWhere('name', 'like', $like))
            ->orderByDesc('is_verified')->orderByDesc('followers_count')->limit(6)->get()
            ->map(fn ($u) => ['type' => 'user', 'label' => $u->name, 'sub' => '@'.$u->username, 'value' => $u->username,
                'avatar' => $u->avatarUrl(), 'verified' => $u->is_verified, 'url' => $u->profileUrl()]);
        $tags = Hashtag::where('is_blocked', false)->where('name', 'like', mb_strtolower($like))->orderByDesc('uses_count')->limit(5)->get()
            ->map(fn ($h) => ['type' => 'hashtag', 'label' => '#'.$h->name, 'sub' => short_number($h->uses_count), 'value' => $h->name, 'url' => $h->url()]);

        return response()->json($users->concat($tags)->values());
    }
}
