<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Debate;
use App\Models\DebateParticipant;
use App\Models\DebateQuestion;
use App\Models\Follow;
use App\Models\Live;
use App\Models\LiveParticipant;
use App\Models\Reminder;
use App\Models\User;
use App\Services\AiService;
use App\Services\AntiSpam;
use App\Services\MediaService;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DebateController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    private function canCreate(User $user): bool
    {
        return $user->hasPermission('debates.manage') || ($user->is_verified && in_array($user->account_type, ['organization', 'candidate', 'official'], true));
    }

    public function index(Request $request)
    {
        $viewer = $request->user();
        $tab = in_array($request->query('tab'), ['upcoming', 'live', 'replays', 'archived'], true) ? $request->query('tab') : 'upcoming';
        $q = Debate::visibleTo($viewer)->with(['participants.user.candidateProfile', 'moderator', 'category']);
        $q = match ($tab) {
            'live' => $q->where('status', 'live'),
            'replays' => $q->where('status', 'ended')->whereNull('archived_at')->latest('scheduled_at'),
            'archived' => $q->whereNotNull('archived_at')->latest('scheduled_at'),
            default => $q->where('status', 'scheduled')->orderBy('scheduled_at'),
        };
        $debates = $q->paginate(12)->withQueryString();
        $liveCount = Debate::where('status', 'live')->count();

        return view('debates.index', compact('debates', 'tab', 'liveCount'));
    }

    public function create(Request $request)
    {
        abort_unless($this->canCreate($request->user()), 403, __('Seuls les comptes vérifiés peuvent organiser un débat.'));

        return view('debates.form', ['debate' => new Debate(['duration_minutes' => 90, 'public_questions' => true, 'chat_enabled' => true]),
            'categories' => Category::allActive(), 'candidates' => $this->candidateOptions()]);
    }

    private function candidateOptions()
    {
        return User::whereIn('account_type', ['candidate', 'official'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'username']);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'rules' => ['nullable', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'constituency' => ['nullable', 'string', 'max:150'],
            'moderator' => ['nullable', 'string', 'max:40'],
            'participants' => ['required', 'array', 'min:2', 'max:12'],
            'participants.*' => ['integer', 'exists:users,id'],
            'playback_url' => ['nullable', 'url', 'max:2048'],
            'public_questions' => ['nullable', 'boolean'],
            'chat_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request, MediaService $media)
    {
        $user = $request->user();
        abort_unless($this->canCreate($user), 403);
        $data = $request->validate($this->rules());
        $moderator = ! empty($data['moderator']) ? User::where('username', ltrim($data['moderator'], '@'))->firstOrFail() : $user;

        $debate = DB::transaction(function () use ($data, $user, $moderator, $request, $media) {
            $cover = $request->hasFile('cover') ? $media->storeImage($request->file('cover'), 'debates', 1280) : null;
            $live = Live::create([
                'user_id' => $user->id, 'kind' => 'video', 'title' => $data['title'], 'description' => $data['description'] ?? null,
                'cover' => $cover, 'status' => 'scheduled', 'scheduled_at' => $data['scheduled_at'], 'stream_key' => Str::random(40),
                'playback_url' => $data['playback_url'] ?? null, 'chat_enabled' => $request->boolean('chat_enabled', true), 'country' => 'HT',
            ]);
            $debate = Debate::create([
                'user_id' => $user->id, 'moderator_id' => $moderator->id, 'live_id' => $live->id,
                'category_id' => $data['category_id'] ?? null, 'title' => $data['title'], 'description' => $data['description'] ?? null,
                'rules' => $data['rules'] ?? null, 'cover' => $cover, 'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'], 'constituency' => $data['constituency'] ?? null,
                'playback_url' => $data['playback_url'] ?? null,
                'public_questions' => $request->boolean('public_questions', true), 'chat_enabled' => $request->boolean('chat_enabled', true),
            ]);
            if ($moderator->id !== $user->id) {
                LiveParticipant::create(['live_id' => $live->id, 'user_id' => $moderator->id, 'role' => 'guest', 'status' => 'accepted']);
            }
            foreach (array_unique($data['participants']) as $i => $pid) {
                DebateParticipant::create(['debate_id' => $debate->id, 'user_id' => $pid, 'speaking_order' => $i + 1,
                    'status' => $pid === $user->id ? 'accepted' : 'invited']);
                if ($pid === $user->id) {
                    continue;
                }
                LiveParticipant::create(['live_id' => $live->id, 'user_id' => $pid, 'role' => 'guest', 'status' => 'invited']);
            }

            return $debate;
        });

        $debate->load('participants.user');
        foreach ($debate->participants as $p) {
            $this->notifier->send($p->user, 'debate', $user, ':name vous invite au débat « :title »', ['name' => $user->name, 'title' => $debate->title], $debate->url(), $debate);
        }
        $this->notifier->broadcast($this->notifier->followersOf($user), 'debate', $user, 'Nouveau débat programmé : « :title »',
            ['title' => $debate->title], $debate->url(), $debate);

        return redirect()->route('debates.show', $debate)->with('status', __('Débat programmé. Les candidats ont été invités.'));
    }

    public function show(Request $request, Debate $debate)
    {
        $user = $request->user();
        abort_if($debate->is_hidden && ! $user?->isStaff() && ! $debate->canManage($user), 404);
        $debate->load(['participants.user.candidateProfile', 'moderator', 'category', 'live.participants', 'replay.subtitles', 'user']);
        $questions = $debate->publicQuestions()->with(['user', 'target'])->where('status', '!=', 'rejected')
            ->orderByRaw("FIELD(status, 'selected', 'pending', 'answered')")->orderByDesc('votes_count')->limit(100)->get();
        $votedIds = $user ? DB::table('debate_question_votes')->where('user_id', $user->id)->whereIn('debate_question_id', $questions->pluck('id'))->pluck('debate_question_id')->all() : [];
        $myParticipation = $user ? $debate->participants->firstWhere('user_id', $user->id) : null;
        $reminded = $user && Reminder::where(['user_id' => $user->id, 'remindable_type' => 'debate', 'remindable_id' => $debate->id])->exists();

        $rtc = ['iceServers' => array_values(array_filter([
            ['urls' => config('vwajen.webrtc.stun')],
            config('vwajen.webrtc.turn_url') ? ['urls' => config('vwajen.webrtc.turn_url'), 'username' => config('vwajen.webrtc.turn_username'),
                'credential' => config('vwajen.webrtc.turn_credential')] : null,
        ]))];

        return view('debates.show', compact('debate', 'questions', 'votedIds', 'myParticipation', 'reminded', 'rtc') + ['reactions' => LiveController::REACTIONS]);
    }

    public function edit(Request $request, Debate $debate)
    {
        abort_unless($debate->canManage($request->user()), 403);

        return view('debates.form', ['debate' => $debate->load('participants'), 'categories' => Category::allActive(), 'candidates' => $this->candidateOptions()]);
    }

    public function update(Request $request, Debate $debate, MediaService $media)
    {
        abort_unless($debate->canManage($request->user()), 403);
        $rules = $this->rules();
        $rules['participants'] = ['nullable', 'array'];
        $data = $request->validate($rules);
        if ($request->hasFile('cover')) {
            $data['cover'] = $media->storeImage($request->file('cover'), 'debates', 1280);
        }
        $moderatorId = ! empty($data['moderator']) ? User::where('username', ltrim($data['moderator'], '@'))->value('id') : $debate->moderator_id;
        $fields = collect($data)->only(['title', 'description', 'rules', 'cover', 'scheduled_at', 'duration_minutes', 'category_id', 'constituency', 'playback_url'])->all();
        $debate->update($fields + ['moderator_id' => $moderatorId, 'public_questions' => $request->boolean('public_questions'), 'chat_enabled' => $request->boolean('chat_enabled')]);
        $debate->live?->update(collect($fields)->only(['title', 'description', 'cover', 'scheduled_at', 'playback_url'])->all() + ['chat_enabled' => $request->boolean('chat_enabled')]);

        if ($debate->wasChanged('scheduled_at')) {
            foreach ($debate->participants()->with('user')->get() as $p) {
                $this->notifier->send($p->user, 'debate', $request->user(), 'Le débat « :title » a été reprogrammé', ['title' => $debate->title], $debate->url(), $debate);
            }
        }

        return redirect()->route('debates.show', $debate)->with('status', __('Débat mis à jour.'));
    }

    public function destroy(Request $request, Debate $debate)
    {
        abort_unless($debate->canManage($request->user()), 403);
        $debate->live?->update(['status' => 'cancelled']);
        $debate->update(['status' => 'cancelled']);
        $debate->delete();

        return redirect()->route('debates.index')->with('status', __('Débat supprimé.'));
    }

    public function invite(Request $request, Debate $debate)
    {
        abort_unless($debate->canManage($request->user()), 403);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $candidate = User::findOrFail($data['user_id']);
        DebateParticipant::firstOrCreate(['debate_id' => $debate->id, 'user_id' => $candidate->id],
            ['status' => 'invited', 'speaking_order' => $debate->participants()->count() + 1]);
        LiveParticipant::firstOrCreate(['live_id' => $debate->live_id, 'user_id' => $candidate->id], ['role' => 'guest', 'status' => 'invited']);
        $this->notifier->send($candidate, 'debate', $request->user(), ':name vous invite au débat « :title »', ['name' => $request->user()->name, 'title' => $debate->title], $debate->url(), $debate);

        return back()->with('status', __('Invitation envoyée.'));
    }

    public function removeParticipant(Request $request, Debate $debate, DebateParticipant $participant)
    {
        abort_unless($debate->canManage($request->user()) && $participant->debate_id === $debate->id, 403);
        LiveParticipant::where('live_id', $debate->live_id)->where('user_id', $participant->user_id)->update(['status' => 'removed']);
        $participant->delete();

        return back()->with('status', __('Participant retiré.'));
    }

    /** Participation des candidats : accepter / décliner. */
    public function respond(Request $request, Debate $debate)
    {
        $p = DebateParticipant::where('debate_id', $debate->id)->where('user_id', $request->user()->id)->firstOrFail();
        $accept = $request->input('answer') === 'accept';
        $p->update(['status' => $accept ? 'accepted' : 'declined']);
        LiveParticipant::updateOrCreate(['live_id' => $debate->live_id, 'user_id' => $p->user_id], ['role' => 'guest', 'status' => $accept ? 'accepted' : 'declined']);
        $this->notifier->send($debate->user, 'debate', $request->user(), $accept ? ':name a accepté votre débat « :title »' : ':name a décliné votre débat « :title »',
            ['name' => $request->user()->name, 'title' => $debate->title], $debate->url(), $debate);

        if ($accept) {
            $this->notifier->broadcast($this->notifier->followersOf($request->user()), 'debate', $request->user(),
                ':name participera au débat « :title »', ['name' => $request->user()->name, 'title' => $debate->title], $debate->url(), $debate);
        }

        return back()->with('status', $accept ? __('Participation confirmée.') : __('Invitation déclinée.'));
    }

    public function start(Request $request, Debate $debate)
    {
        abort_unless($debate->canManage($request->user()), 403);
        $debate->update(['status' => 'live']);
        $debate->live?->update(['status' => 'live', 'started_at' => now(), 'ended_at' => null]);

        $audience = User::whereIn('id', Reminder::where(['remindable_type' => 'debate', 'remindable_id' => $debate->id])->select('user_id'))
            ->orWhereIn('id', Follow::whereIn('following_id', $debate->participants()->where('status', 'accepted')->select('user_id'))->where('status', 'accepted')->select('follower_id'));
        $this->notifier->broadcast($audience, 'debate', null, 'Le débat « :title » commence maintenant', ['title' => $debate->title], $debate->url(), $debate);

        return $this->reply($request, ['status' => 'live'], __('Le débat est en direct.'));
    }

    public function end(Request $request, Debate $debate)
    {
        abort_unless($debate->canManage($request->user()), 403);
        $debate->update(['status' => 'ended']);
        $debate->live?->update(['status' => 'ended', 'ended_at' => now()]);

        return $this->reply($request, ['status' => 'ended'], __('Débat terminé. Le replay pourra être publié.'));
    }

    public function archive(Request $request, Debate $debate)
    {
        abort_unless($debate->canManage($request->user()), 403);
        $debate->update(['archived_at' => $debate->archived_at ? null : now()]);

        return back()->with('status', $debate->archived_at ? __('Débat archivé.') : __('Débat désarchivé.'));
    }

    /** Questions du public. */
    public function askQuestion(Request $request, Debate $debate, AntiSpam $spam)
    {
        abort_unless($debate->public_questions && in_array($debate->status, ['scheduled', 'live'], true), 403, __('Les questions sont fermées.'));
        $data = $request->validate(['body' => ['required', 'string', 'min:5', 'max:500'], 'target_user_id' => ['nullable', 'exists:users,id']]);
        $spam->checkContent($request->user(), $data['body']);
        DebateQuestion::create(['debate_id' => $debate->id, 'user_id' => $request->user()->id, 'body' => $data['body'], 'target_user_id' => $data['target_user_id'] ?? null]);

        return back()->with('status', __('Question envoyée au modérateur.'));
    }

    public function voteQuestion(Request $request, DebateQuestion $question)
    {
        $exists = DB::table('debate_question_votes')->where(['debate_question_id' => $question->id, 'user_id' => $request->user()->id])->exists();
        if ($exists) {
            DB::table('debate_question_votes')->where(['debate_question_id' => $question->id, 'user_id' => $request->user()->id])->delete();
            $question->votes_count > 0 && $question->decrement('votes_count');
        } else {
            DB::table('debate_question_votes')->insert(['debate_question_id' => $question->id, 'user_id' => $request->user()->id]);
            $question->increment('votes_count');
        }

        return $this->reply($request, ['active' => ! $exists, 'count' => $question->fresh()->votes_count]);
    }

    /** Le modérateur sélectionne / marque répondue / rejette une question du public. */
    public function questionStatus(Request $request, DebateQuestion $question)
    {
        abort_unless($question->debate->canManage($request->user()), 403);
        $data = $request->validate(['status' => ['required', 'in:pending,selected,answered,rejected']]);
        $question->update($data);

        return $this->reply($request, ['status' => $question->status], __('Question mise à jour.'));
    }

    /** Notifications avant le débat. */
    public function remind(Request $request, Debate $debate)
    {
        $attrs = ['user_id' => $request->user()->id, 'remindable_type' => 'debate', 'remindable_id' => $debate->id];
        $existing = Reminder::where($attrs)->exists();
        if ($existing) {
            Reminder::where($attrs)->delete();
        } else {
            foreach ([60 * 24, 60, 10] as $minutes) {
                $at = $debate->scheduled_at->copy()->subMinutes($minutes);
                if ($at->isFuture()) {
                    Reminder::firstOrCreate($attrs + ['remind_at' => $at]);
                }
            }
        }

        return $this->reply($request, ['active' => ! $existing], $existing ? __('Rappels supprimés.') : __('Vous serez notifié 24 h, 1 h et 10 min avant.'));
    }

    /** Résumé automatique neutre du débat (IA). */
    public function summary(Request $request, Debate $debate, AiService $ai)
    {
        abort_unless($debate->canManage($request->user()) && $debate->status === 'ended', 403);
        abort_unless($ai->enabled(), 503, __('Les résumés automatiques ne sont pas activés.'));

        $material = collect();
        if ($t = $debate->replay?->transcript ?? $debate->live?->transcript) {
            $material->push("TRANSCRIPT:\n".$t);
        }
        $material->push("PUBLIC QUESTIONS:\n".$debate->publicQuestions()->whereIn('status', ['selected', 'answered'])->pluck('body')->implode("\n"));
        if ($debate->live) {
            $material->push("CHAT (sample):\n".ChatMessage::where('chatable_type', 'live')->where('chatable_id', $debate->live_id)
                ->where('is_hidden', false)->latest('id')->limit(300)->pluck('body')->reverse()->implode("\n"));
        }

        $summary = $ai->summarizeDebate($debate->title, $debate->participants()->with('user')->get()->pluck('user.name')->all(), $material->implode("\n\n"), app()->getLocale());
        abort_unless($summary, 502, __('Résumé indisponible pour le moment.'));
        $debate->update(['summary' => $summary]);

        return back()->with('status', __('Résumé généré.'));
    }
}
