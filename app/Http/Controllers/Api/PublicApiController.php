<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commitment;
use App\Models\Debate;
use App\Models\Election;
use App\Models\Event;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;

/** API publique v1 (lecture seule) : données civiques ouvertes. */
class PublicApiController extends Controller
{
    private function candidateData(User $u): array
    {
        $p = $u->candidateProfile;

        return [
            'username' => $u->username, 'name' => $p?->full_name ?? $u->name, 'verified' => $u->is_verified,
            'party' => $u->show_political ? $p?->party : null, 'position_sought' => $p?->position_sought,
            'constituency' => $p?->constituency, 'department' => $p?->department, 'election_year' => $p?->election_year,
            'photo' => $p?->photoUrl(), 'profile_url' => route('candidates.show', $u->username),
        ];
    }

    public function candidates(Request $request)
    {
        $q = User::where('account_type', 'candidate')->where('status', 'active')->whereHas('candidateProfile')->with('candidateProfile')
            ->when($request->query('department'), fn ($q, $d) => $q->whereHas('candidateProfile', fn ($c) => $c->where('department', $d)))
            ->when($request->query('position'), fn ($q, $p) => $q->whereHas('candidateProfile', fn ($c) => $c->where('position_sought', $p)))
            ->orderBy('name')->paginate(min(100, (int) $request->query('per_page', 50)));

        return response()->json($q->through(fn ($u) => $this->candidateData($u)));
    }

    public function candidate(string $username)
    {
        $u = User::where('username', $username)->where('account_type', 'candidate')->with('candidateProfile.sources')->firstOrFail();

        return response()->json($this->candidateData($u) + [
            'biography' => $u->candidateProfile?->biography,
            'career' => $u->candidateProfile?->career,
            'sources' => $u->candidateProfile?->sources->map(fn ($s) => ['name' => $s->name, 'url' => $s->url, 'status' => $s->status, 'published_on' => $s->published_on?->toDateString()]),
        ]);
    }

    public function program(string $username)
    {
        $u = User::where('username', $username)->where('account_type', 'candidate')->firstOrFail();
        $program = $u->programs()->where('status', 'published')->with(['currentVersion.proposals.category', 'currentVersion.proposals.sources'])->latest('published_at')->firstOrFail();

        return response()->json([
            'title' => $program->title, 'summary' => $program->summary, 'version' => $program->currentVersion?->version_number,
            'published_at' => $program->currentVersion?->published_at?->toIso8601String(), 'url' => $program->url(),
            'proposals' => $program->currentVersion?->proposals->map(fn ($p) => [
                'category' => $p->category->slug, 'title' => $p->title, 'description' => $p->description, 'timeline' => $p->timeline, 'budget' => $p->budget,
                'sources' => $p->sources->map(fn ($s) => ['name' => $s->name, 'url' => $s->url, 'status' => $s->status]),
            ]),
        ]);
    }

    public function categories()
    {
        return response()->json(Category::active()->get(['slug', 'name_ht', 'name_fr', 'name_en']));
    }

    public function questions(Request $request)
    {
        $q = Question::where('is_hidden', false)->with(['candidate:id,username,name', 'category:id,slug'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('candidate'), fn ($q, $c) => $q->whereHas('candidate', fn ($x) => $x->where('username', $c)))
            ->latest()->paginate(50);

        return response()->json($q->through(fn ($x) => ['id' => $x->id, 'title' => $x->title, 'status' => $x->status, 'supports' => $x->supports_count,
            'candidate' => $x->candidate?->username, 'category' => $x->category?->slug, 'created_at' => $x->created_at->toIso8601String(), 'url' => $x->url()]));
    }

    public function debates()
    {
        return response()->json(Debate::where('is_hidden', false)->with('participants.user:id,username,name')->latest('scheduled_at')->paginate(50)
            ->through(fn ($d) => ['id' => $d->id, 'title' => $d->title, 'status' => $d->status, 'scheduled_at' => $d->scheduled_at->toIso8601String(),
                'participants' => $d->participants->pluck('user.username'), 'url' => $d->url()]));
    }

    public function events(Request $request)
    {
        return response()->json(Event::where('is_hidden', false)->where('visibility', 'public')
            ->when($request->query('department'), fn ($q, $d) => $q->where('department', $d))->upcoming()->paginate(50)
            ->through(fn ($e) => ['id' => $e->id, 'title' => $e->title, 'starts_at' => $e->starts_at->toIso8601String(), 'timezone' => $e->timezone,
                'online' => $e->is_online, 'city' => $e->city, 'department' => $e->department, 'country' => $e->country, 'lat' => $e->lat, 'lng' => $e->lng, 'url' => $e->url()]));
    }

    public function officials()
    {
        return response()->json(User::where('account_type', 'official')->where('status', 'active')->whereHas('officialProfile')->with('officialProfile')->paginate(50)
            ->through(fn ($u) => ['username' => $u->username, 'name' => $u->officialProfile->full_name, 'office' => $u->officialProfile->office,
                'institution' => $u->officialProfile->institution, 'constituency' => $u->officialProfile->constituency, 'department' => $u->officialProfile->department,
                'mandate_start' => $u->officialProfile->mandate_start?->toDateString(), 'mandate_end' => $u->officialProfile->mandate_end?->toDateString()]));
    }

    public function commitments(string $username)
    {
        $u = User::where('username', $username)->where('account_type', 'official')->firstOrFail();

        return response()->json(Commitment::where('user_id', $u->id)->with(['sources', 'updates'])->get()->map(fn ($c) => [
            'title' => $c->title, 'status' => $c->status, 'made_on' => $c->made_on?->toDateString(), 'due_on' => $c->due_on?->toDateString(),
            'sources' => $c->sources->map(fn ($s) => ['name' => $s->name, 'url' => $s->url, 'status' => $s->status]),
            'updates' => $c->updates->map(fn ($x) => ['status' => $x->status, 'note' => $x->note, 'date' => $x->created_at->toDateString()]),
        ]));
    }

    public function elections()
    {
        return response()->json(Election::orderByDesc('held_on')->get(['id', 'name', 'type', 'held_on', 'round', 'results_published', 'source_name', 'source_url']));
    }

    public function results(int $id)
    {
        $e = Election::where('results_published', true)->findOrFail($id);

        return response()->json($e->results()->get(['candidate_name', 'party', 'constituency', 'department', 'votes', 'percentage', 'elected']));
    }

    public function stats()
    {
        return response()->json([
            'users' => User::where('status', 'active')->count(),
            'candidates' => User::where('account_type', 'candidate')->where('status', 'active')->count(),
            'verified_candidates' => User::where('account_type', 'candidate')->where('is_verified', true)->count(),
            'questions' => Question::count(),
            'questions_answered' => Question::where('status', 'answered')->count(),
            'debates' => Debate::count(),
            'events_upcoming' => Event::where('starts_at', '>=', now())->count(),
        ]);
    }
}
