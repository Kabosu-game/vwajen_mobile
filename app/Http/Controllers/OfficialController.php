<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ChangeLog;
use App\Models\Commitment;
use App\Models\CommitmentUpdate;
use App\Models\Event;
use App\Models\Post;
use App\Models\PublicRecord;
use App\Models\Question;
use App\Models\User;
use App\Services\ChangeLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Suivi des responsables publics : profil, activités, déclarations, documents, engagements documentés, historique. */
class OfficialController extends Controller
{
    public const RECORD_TYPES = ['activity', 'declaration', 'document', 'vote', 'appointment'];

    public const COMMITMENT_STATUSES = ['not_started', 'in_progress', 'partially', 'fulfilled', 'not_fulfilled'];

    public function index(Request $request)
    {
        $officials = User::where('account_type', 'official')->where('status', 'active')->whereHas('officialProfile')
            ->with('officialProfile')
            ->when($request->query('department'), fn ($q, $d) => $q->whereHas('officialProfile', fn ($o) => $o->where('department', $d)))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhereHas('officialProfile', fn ($o) => $o->where('office', 'like', "%$s%")->orWhere('institution', 'like', "%$s%"))))
            ->orderBy('name')->paginate(24)->withQueryString();

        return view('officials.index', compact('officials'));
    }

    private function canDocument(?User $viewer, User $official): bool
    {
        return $viewer && ($viewer->id === $official->id || $viewer->hasPermission('officials.manage'));
    }

    public function show(Request $request, User $user)
    {
        abort_unless($user->account_type === 'official' && $user->officialProfile, 404);
        $viewer = $request->user();
        $tab = in_array($request->query('tab'), ['overview', 'posts', 'activities', 'declarations', 'documents', 'events', 'questions', 'commitments', 'history'], true)
            ? $request->query('tab') : 'overview';

        $items = match ($tab) {
            'posts' => Post::where('posts.user_id', $user->id)->visibleTo($viewer)->audienceFor($viewer)->withCardRelations($viewer)->latest()->paginate(20),
            'activities' => PublicRecord::where('user_id', $user->id)->where('is_hidden', false)->whereIn('type', ['activity', 'vote', 'appointment'])->with('sources')->latest('occurred_on')->paginate(20),
            'declarations' => PublicRecord::where('user_id', $user->id)->where('is_hidden', false)->where('type', 'declaration')->with('sources')->latest('occurred_on')->paginate(20),
            'documents' => PublicRecord::where('user_id', $user->id)->where('is_hidden', false)->where('type', 'document')->with(['documents', 'sources'])->latest('occurred_on')->paginate(20),
            'events' => Event::where('user_id', $user->id)->visibleTo($viewer)->latest('starts_at')->paginate(18),
            'questions' => Question::where('candidate_id', $user->id)->visibleTo($viewer)->with(['user', 'answers.user'])->orderByDesc('supports_count')->paginate(20),
            'commitments' => Commitment::where('user_id', $user->id)->with(['updates.user', 'sources', 'category'])->latest()->paginate(30),
            'history' => ChangeLog::where(fn ($q) => $q->where('subject_type', 'official')->where('subject_id', $user->officialProfile->id))
                ->orWhere(fn ($q) => $q->where('subject_type', 'commitment')->whereIn('subject_id', Commitment::where('user_id', $user->id)->select('id')))
                ->with('user')->latest()->paginate(30),
            default => null,
        };
        $items?->withQueryString();

        $commitmentStats = Commitment::where('user_id', $user->id)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('officials.show', ['user' => $user->load('officialProfile.sources'), 'tab' => $tab, 'items' => $items,
            'commitmentStats' => $commitmentStats, 'canDocument' => $this->canDocument($viewer, $user),
            'categories' => Category::allActive()]);
    }

    public function storeRecord(Request $request, User $user)
    {
        abort_unless($this->canDocument($request->user(), $user), 403);
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', self::RECORD_TYPES)],
            'title' => ['required', 'string', 'max:250'],
            'body' => ['nullable', 'string', 'max:20000'],
            'occurred_on' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);
        $record = PublicRecord::create(collect($data)->only(['type', 'title', 'body', 'occurred_on', 'location'])->all()
            + ['user_id' => $user->id, 'created_by' => $request->user()->id]);
        if ($file = $request->file('file')) {
            $record->documents()->create(['user_id' => $request->user()->id, 'title' => $data['title'], 'path' => $file->store('public-records', 'public'),
                'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'published_on' => $data['occurred_on'] ?? now()]);
        }
        if (! empty($data['source_name'])) {
            $record->sources()->create(['user_id' => $request->user()->id, 'name' => $data['source_name'], 'url' => $data['source_url'] ?? null,
                'status' => $request->user()->id === $user->id ? 'provided' : 'unverified', 'provided_by_candidate' => $request->user()->id === $user->id]);
        }

        return back()->with('status', __('Élément ajouté à l\'historique public.'));
    }

    public function destroyRecord(Request $request, PublicRecord $record)
    {
        abort_unless($request->user()->hasPermission('officials.manage') || ($record->created_by === $request->user()->id && $record->created_at->gt(now()->subDay())), 403);
        $record->update(['is_hidden' => true]); // conservé pour l'historique

        return back()->with('status', __('Élément retiré.'));
    }

    public function storeCommitment(Request $request, User $user, Notifier $notifier)
    {
        abort_unless($this->canDocument($request->user(), $user), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'made_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date'],
            'source_name' => ['required', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);
        $commitment = Commitment::create(collect($data)->only(['title', 'description', 'category_id', 'made_on', 'due_on'])->all()
            + ['user_id' => $user->id, 'created_by' => $request->user()->id]);
        $commitment->sources()->create(['user_id' => $request->user()->id, 'name' => $data['source_name'], 'url' => $data['source_url'] ?? null,
            'status' => 'unverified']);
        CommitmentUpdate::create(['commitment_id' => $commitment->id, 'user_id' => $request->user()->id, 'status' => 'not_started', 'note' => __('Engagement documenté')]);

        return back()->with('status', __('Engagement ajouté au suivi.'));
    }

    public function updateCommitment(Request $request, Commitment $commitment)
    {
        abort_unless($this->canDocument($request->user(), $commitment->user), 403);
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::COMMITMENT_STATUSES)],
            'note' => ['required', 'string', 'max:2000'],
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);
        $old = $commitment->status;
        $commitment->update(['status' => $data['status']]);
        CommitmentUpdate::create(['commitment_id' => $commitment->id, 'user_id' => $request->user()->id, 'status' => $data['status'], 'note' => $data['note']]);
        ChangeLogger::record($commitment, 'status', $old, $data['status'], $data['note']);
        if (! empty($data['source_name'])) {
            $commitment->sources()->create(['user_id' => $request->user()->id, 'name' => $data['source_name'], 'url' => $data['source_url'] ?? null, 'status' => 'unverified']);
        }

        return back()->with('status', __('Suivi de l\'engagement mis à jour.'));
    }
}
