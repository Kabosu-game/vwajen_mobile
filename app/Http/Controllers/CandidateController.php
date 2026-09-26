<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Models\Category;
use App\Models\ChangeLog;
use App\Models\Debate;
use App\Models\Event;
use App\Models\Live;
use App\Models\Post;
use App\Models\Question;
use App\Models\Source;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Video;
use App\Services\ChangeLogger;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateController extends Controller
{
    public const TABS = ['about', 'program', 'proposals', 'posts', 'videos', 'lives', 'events', 'debates', 'questions', 'sources', 'history'];

    public function index(Request $request)
    {
        $q = User::query()->where('users.account_type', 'candidate')->where('users.status', 'active')
            ->whereHas('candidateProfile')->with('candidateProfile');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")
                ->orWhereHas('candidateProfile', fn ($c) => $c->where('full_name', 'like', "%$s%")->orWhere('party', 'like', "%$s%")));
        }
        foreach (['department', 'position_sought', 'constituency', 'party'] as $f) {
            if ($v = $request->query($f)) {
                $q->whereHas('candidateProfile', fn ($c) => $c->where($f, $v));
            }
        }
        if ($request->boolean('verified')) {
            $q->where('is_verified', true);
        }

        // Ordre alphabétique : aucun classement ni mise en avant.
        $candidates = $q->join('candidate_profiles', 'candidate_profiles.user_id', '=', 'users.id')
            ->orderBy('candidate_profiles.full_name')->select('users.*')->paginate(24)->withQueryString();
        $parties = CandidateProfile::whereNotNull('party')->distinct()->orderBy('party')->pluck('party');

        return view('candidates.index', compact('candidates', 'parties'));
    }

    public function show(Request $request, User $user)
    {
        abort_unless($user->account_type === 'candidate' && $user->candidateProfile, 404);
        $viewer = $request->user();
        $profile = $user->candidateProfile()->with('sources')->first();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'about';

        $program = $user->programs()->whereIn('status', ['published', 'archived'])->with(['currentVersion.proposals.category', 'currentVersion.proposals.sources', 'documents', 'sources'])
            ->orderByRaw("status = 'published' desc")->latest('published_at')->first();

        $data = match ($tab) {
            'posts' => Post::where('posts.user_id', $user->id)->visibleTo($viewer)->audienceFor($viewer)->withCardRelations($viewer)->latest()->paginate(20),
            'videos' => Video::where('user_id', $user->id)->published()->visibleTo($viewer)->latest()->paginate(18),
            'lives' => Live::where('user_id', $user->id)->visibleTo($viewer)->latest()->paginate(18),
            'events' => Event::where('user_id', $user->id)->visibleTo($viewer)->latest('starts_at')->paginate(18),
            'debates' => Debate::whereHas('participants', fn ($q) => $q->where('user_id', $user->id)->where('status', 'accepted'))->visibleTo($viewer)->latest('scheduled_at')->paginate(18),
            'questions' => Question::where('candidate_id', $user->id)->visibleTo($viewer)->with(['user', 'answers.user'])
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->orderByDesc('supports_count')->paginate(20),
            'sources' => Source::where(fn ($q) => $q->where(fn ($q) => $q->where('sourceable_type', 'candidate')->where('sourceable_id', $profile->id))
                ->orWhere(fn ($q) => $q->where('sourceable_type', 'program')->whereIn('sourceable_id', $user->programs()->select('id')))
                ->orWhere(fn ($q) => $q->where('sourceable_type', 'proposal')->whereIn('sourceable_id',
                    DB::table('proposals')->join('program_versions', 'program_versions.id', '=', 'proposals.program_version_id')
                        ->join('programs', 'programs.id', '=', 'program_versions.program_id')->where('programs.user_id', $user->id)->select('proposals.id'))))
                ->latest()->paginate(30),
            'history' => ChangeLog::where(fn ($q) => $q->where('subject_type', 'candidate')->where('subject_id', $profile->id))
                ->orWhere(fn ($q) => $q->where('subject_type', 'program')->whereIn('subject_id', $user->programs()->select('id')))
                ->with('user')->latest()->paginate(30),
            default => null,
        };
        if ($data) {
            $data->withQueryString();
        }

        $stats = [
            'questions' => Question::where('candidate_id', $user->id)->count(),
            'answered' => Question::where('candidate_id', $user->id)->where('status', 'answered')->count(),
            'proposals' => $program?->currentVersion?->proposals->count() ?? 0,
        ];
        $categories = Category::allActive();

        return view('candidates.show', compact('user', 'profile', 'tab', 'program', 'data', 'stats', 'categories'));
    }

    public function register(Request $request)
    {
        $user = $request->user();
        $profile = $user->candidateProfile;

        return view('candidates.register', compact('user', 'profile'));
    }

    /** Inscription comme candidat + demande de vérification avec documents justificatifs. */
    public function storeRegistration(Request $request, MediaService $media)
    {
        $user = $request->user();
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'max:8192'],
            'biography' => ['required', 'string', 'max:5000'],
            'career' => ['nullable', 'string', 'max:20000'],
            'party' => ['nullable', 'string', 'max:150'],
            'position_sought' => ['required', 'in:'.implode(',', array_keys(config('vwajen.positions')))],
            'constituency' => ['nullable', 'string', 'max:150'],
            'department' => ['required', 'in:'.implode(',', array_keys(config('vwajen.departments')))],
            'city' => ['nullable', 'string', 'max:100'],
            'election_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'website' => ['nullable', 'url', 'max:255'],
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_labels' => ['nullable', 'array'],
            'document_labels.*' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($user, $data, $request, $media) {
            $profileData = collect($data)->only(['full_name', 'biography', 'career', 'party', 'position_sought', 'constituency', 'department', 'city', 'election_year', 'website'])->all();
            if ($request->hasFile('photo')) {
                $profileData['photo'] = $media->storeImage($request->file('photo'), 'candidates', 800);
            }
            $profile = CandidateProfile::updateOrCreate(['user_id' => $user->id], $profileData + ['status' => 'pending']);
            ChangeLogger::diff($profile, CandidateProfile::TRACKED);
            $user->forceFill(['account_type' => 'candidate', 'department' => $data['department']])->save();

            $vr = VerificationRequest::create(['user_id' => $user->id, 'type' => 'candidate', 'message' => $data['message'] ?? null]);
            foreach ($request->file('documents') as $i => $file) {
                $vr->documents()->create([
                    'label' => $data['document_labels'][$i] ?? __('Document justificatif'),
                    'path' => $file->store('verifications/'.$user->id, 'local'), // disque privé
                    'mime' => $file->getMimeType(),
                ]);
            }
        });

        return redirect()->route('candidate.dashboard')->with('status', __('Profil candidat créé. Votre demande de vérification est en cours d\'examen.'));
    }
}
