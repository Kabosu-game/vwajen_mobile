<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\Question;
use App\Models\Source;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Observatoire citoyen (indicateurs de redevabilité) et données civiques publiques. */
class ObservatoryController extends Controller
{
    public function index()
    {
        $data = Cache::remember('observatory', now()->addMinutes(30), function () {
            $candidates = User::where('account_type', 'candidate')->where('status', 'active')->with('candidateProfile')->get();
            // Taux de réponse par candidat — présentation alphabétique, sans classement.
            $responsiveness = $candidates->map(function ($c) {
                $received = Question::where('candidate_id', $c->id)->count();
                $answered = Question::where('candidate_id', $c->id)->where('status', 'answered')->count();

                return ['username' => $c->username, 'name' => $c->candidateProfile?->full_name ?? $c->name,
                    'received' => $received, 'answered' => $answered, 'rate' => $received ? round($answered * 100 / $received) : null];
            })->sortBy(fn ($r) => mb_strtolower($r['name']))->values()->all();

            // Uniquement des tableaux dans le cache (pas d'objets désérialisés).
            return [
                'questions' => Question::count(),
                'answered' => Question::where('status', 'answered')->count(),
                'open' => Question::where('status', 'open')->count(),
                'sources' => Source::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all(),
                'commitments' => Commitment::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all(),
                'topCategories' => Question::join('categories', 'categories.id', '=', 'questions.category_id')
                    ->selectRaw('categories.slug, categories.name_ht, categories.name_fr, categories.name_en, COUNT(*) c')
                    ->groupBy('categories.slug', 'categories.name_ht', 'categories.name_fr', 'categories.name_en')->orderByDesc('c')->limit(13)->get()
                    ->map(fn ($r) => $r->only(['slug', 'name_ht', 'name_fr', 'name_en', 'c']))->all(),
                'responsiveness' => $responsiveness,
            ];
        });
        $data['sources'] = collect($data['sources']);
        $data['commitments'] = collect($data['commitments']);
        $data['topCategories'] = collect($data['topCategories'])->map(fn ($r) => (object) $r);

        return view('observatory.index', $data);
    }

    public function civicData()
    {
        $departments = collect(config('vwajen.departments'))->map(function ($d, $slug) {
            return $d + [
                'slug' => $slug,
                'candidates' => User::where('account_type', 'candidate')->where('department', $slug)->count(),
                'officials' => DB::table('official_profiles')->where('department', $slug)->count(),
                'members' => User::where('department', $slug)->where('status', 'active')->count(),
            ];
        });

        return view('observatory.civic', compact('departments'));
    }
}
