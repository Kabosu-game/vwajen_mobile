<?php

namespace App\Http\Controllers;

use App\Models\Election;

/** Archives électorales et résultats électoraux publics. */
class ElectionController extends Controller
{
    public function index()
    {
        $elections = Election::withCount('results')->orderByDesc('held_on')->paginate(20);

        return view('elections.index', compact('elections'));
    }

    public function show(Election $election)
    {
        $results = $election->results_published
            ? $election->results()->with('user')->orderBy('constituency')->orderByDesc('votes')->get()->groupBy(fn ($r) => $r->constituency ?: __('National'))
            : collect();

        return view('elections.show', compact('election', 'results'));
    }
}
