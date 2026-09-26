<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Comparaison informative des programmes : côte à côte, par thème, avec sources et informations manquantes.
 * Aucun classement, aucun score : les candidats sont affichés par ordre alphabétique.
 */
class CompareController extends Controller
{
    public function index(Request $request)
    {
        $usernames = array_slice(array_filter((array) $request->query('c', [])), 0, 4);
        $categorySlugs = array_filter((array) $request->query('t', []));

        $allCandidates = User::where('account_type', 'candidate')->where('status', 'active')->whereHas('candidateProfile')
            ->with('candidateProfile')->get()->sortBy(fn ($u) => $u->candidateProfile->full_name)->values();

        $candidates = User::whereIn('username', $usernames)->where('account_type', 'candidate')
            ->with(['candidateProfile.sources', 'programs' => fn ($q) => $q->whereIn('status', ['published', 'archived'])
                ->with(['currentVersion.proposals.category', 'currentVersion.proposals.sources', 'documents'])->latest('published_at')])
            ->get()->sortBy(fn ($u) => mb_strtolower($u->candidateProfile?->full_name ?? $u->name))->values();

        $categories = Category::allActive();
        $shownCategories = $categorySlugs ? $categories->whereIn('slug', $categorySlugs)->values() : $categories;

        // matrix[category_id][user_id] = Collection<Proposal>
        $matrix = [];
        foreach ($candidates as $c) {
            $program = $c->programs->firstWhere('status', 'published') ?? $c->programs->first();
            $proposals = $program?->currentVersion?->proposals ?? collect();
            foreach ($shownCategories as $cat) {
                $matrix[$cat->id][$c->id] = $proposals->where('category_id', $cat->id)->values();
            }
        }

        $missing = [];
        foreach ($candidates as $c) {
            $p = $c->candidateProfile;
            $missing[$c->id] = array_values(array_filter([
                ! $c->programs->count() ? __('Programme') : null,
                ! $p?->party ? __('Affiliation politique déclarée') : null,
                ! $p?->constituency ? __('Circonscription') : null,
                ! $p?->career ? __('Parcours') : null,
                ! $c->is_verified ? __('Vérification du compte') : null,
            ]));
        }

        return view('compare.index', compact('allCandidates', 'candidates', 'categories', 'shownCategories', 'matrix', 'missing', 'usernames', 'categorySlugs'));
    }
}
