<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Discussion;
use App\Models\Event;
use App\Models\Live;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

/** Vwajèn Mond : section diaspora (profils par pays, communautés, événements internationaux, discussions, lives, contenu localisé). */
class MondController extends Controller
{
    public function index(Request $request)
    {
        $viewer = $request->user();
        $countries = User::where('status', 'active')->where('country', '!=', 'HT')->selectRaw('country, COUNT(*) c')
            ->groupBy('country')->orderByDesc('c')->pluck('c', 'country');

        return view('mond.index', [
            'countries' => $countries,
            'communities' => Community::where('is_diaspora', true)->where('is_hidden', false)->orderByDesc('members_count')->limit(9)->get(),
            'events' => Event::visibleTo($viewer)->where('country', '!=', 'HT')->upcoming()->with('user')->limit(6)->get(),
            'lives' => Live::visibleTo($viewer)->whereIn('status', ['live', 'scheduled'])->where('country', '!=', 'HT')->with('user')->limit(6)->get(),
            'posts' => Post::visibleTo($viewer)->audienceFor($viewer)->where('visibility', 'public')->where('posts.country', '!=', 'HT')
                ->withCardRelations($viewer)->latest()->limit(10)->get(),
            'discussions' => Discussion::whereHas('community', fn ($q) => $q->where('is_diaspora', true)->where('visibility', 'public'))
                ->where('is_hidden', false)->with(['user', 'community'])->latest()->limit(8)->get(),
        ]);
    }

    public function country(Request $request, string $country)
    {
        $viewer = $request->user();
        $tab = in_array($request->query('tab'), ['people', 'posts', 'events', 'communities', 'lives'], true) ? $request->query('tab') : 'posts';

        $items = match ($tab) {
            'people' => User::where('status', 'active')->where('country', $country)->where('searchable', true)
                ->when($request->query('city'), fn ($q, $c) => $q->where('city', 'like', "%$c%"))->orderByDesc('followers_count')->paginate(30),
            'events' => Event::visibleTo($viewer)->where('country', $country)->upcoming()->paginate(18),
            'communities' => Community::where('country', $country)->where('is_hidden', false)->orderByDesc('members_count')->paginate(18),
            'lives' => Live::visibleTo($viewer)->where('country', $country)->with('user')->latest()->paginate(18),
            default => Post::visibleTo($viewer)->audienceFor($viewer)->where('visibility', 'public')->where('posts.country', $country)
                ->withCardRelations($viewer)->latest()->paginate(20),
        };
        $items->withQueryString();
        $members = User::where('status', 'active')->where('country', $country)->count();
        $cities = User::where('status', 'active')->where('country', $country)->whereNotNull('city')->selectRaw('city, COUNT(*) c')
            ->groupBy('city')->orderByDesc('c')->limit(12)->pluck('c', 'city');

        return view('mond.country', compact('country', 'tab', 'items', 'members', 'cities'));
    }
}
