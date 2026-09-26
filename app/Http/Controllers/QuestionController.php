<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Upload;
use App\Models\User;
use App\Models\Video;
use App\Services\AntiSpam;
use App\Services\ContentService;
use App\Services\MediaService;
use App\Services\Notifier;
use App\Services\VideoProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** « Kesyon pou kandida yo » : questions publiques ou adressées à un candidat, soutiens, réponses texte/vidéo. */
class QuestionController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    public function index(Request $request)
    {
        $viewer = $request->user();
        $status = in_array($request->query('status'), ['open', 'answered', 'closed'], true) ? $request->query('status') : null;
        $q = Question::visibleTo($viewer)->with(['user', 'candidate.candidateProfile', 'category'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->query('candidate'), fn ($q, $u) => $q->whereHas('candidate', fn ($c) => $c->where('username', $u)))
            ->when($request->query('category'), fn ($q, $c) => $q->whereHas('category', fn ($x) => $x->where('slug', $c)))
            ->when($request->query('scope') === 'public', fn ($q) => $q->whereNull('candidate_id'))
            ->when($request->query('scope') === 'targeted', fn ($q) => $q->whereNotNull('candidate_id'));

        $questions = ($request->query('sort') === 'recent' ? $q->latest() : $q->orderByDesc('supports_count')->latest())
            ->paginate(20)->withQueryString();
        $counts = Question::visibleTo($viewer)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $categories = Category::allActive();

        return view('questions.index', compact('questions', 'status', 'counts', 'categories'));
    }

    public function create(Request $request)
    {
        $candidate = $request->query('to') ? User::where('username', $request->query('to'))->whereIn('account_type', ['candidate', 'official'])->first() : null;
        $candidates = User::whereIn('account_type', ['candidate', 'official'])->where('status', 'active')->orderBy('name')->get(['id', 'name', 'username']);

        return view('questions.create', ['candidate' => $candidate, 'candidates' => $candidates, 'categories' => Category::allActive()]);
    }

    public function store(Request $request, AntiSpam $spam, ContentService $content)
    {
        $user = $request->user();
        $spam->ensureCanPublish($user);
        $data = $request->validate([
            'title' => ['required', 'string', 'min:10', 'max:250'],
            'body' => ['nullable', 'string', 'max:3000'],
            'candidate_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);
        $spam->checkContent($user, $data['title'].' '.($data['body'] ?? ''), 'title');

        if (! empty($data['candidate_id'])) {
            $candidate = User::findOrFail($data['candidate_id']);
            abort_unless(in_array($candidate->account_type, ['candidate', 'official'], true), 422);
            abort_if($candidate->isBlockedBetween($user), 403);
        }

        $question = Question::create($data + ['user_id' => $user->id]);
        $question->supporters()->attach($user->id);
        $question->increment('supports_count');
        $content->syncAll($question, $question->title.' '.$question->body, $user);

        if ($question->candidate) {
            $this->notifier->send($question->candidate, 'question', $user, ':name vous a posé une question : « :title »',
                ['name' => $user->name, 'title' => Str::limit($question->title, 80)], $question->url(), $question);
        }

        return redirect()->route('questions.show', $question)->with('status', __('Question publiée.'));
    }

    public function show(Request $request, Question $question)
    {
        $viewer = $request->user();
        abort_if($question->is_hidden && ! $viewer?->isStaff() && $viewer?->id !== $question->user_id, 404);
        $question->load(['user', 'candidate.candidateProfile', 'category', 'answers.user', 'answers.video']);
        $comments = $question->rootComments()->where('is_hidden', false)->with(['user', 'replies.user'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $viewer?->id)])->latest()->paginate(20);
        $supported = $question->isSupportedBy($viewer);
        $related = Question::visibleTo($viewer)->where('id', '!=', $question->id)
            ->where(fn ($q) => $q->where('category_id', $question->category_id)->orWhere('candidate_id', $question->candidate_id))
            ->orderByDesc('supports_count')->limit(5)->get();

        return view('questions.show', compact('question', 'comments', 'supported', 'related'));
    }

    public function support(Request $request, Question $question)
    {
        abort_if($question->status === 'closed', 422, __('Cette question est fermée.'));
        $result = $question->supporters()->toggle($request->user()->id);
        $active = ! empty($result['attached']);
        $active ? $question->increment('supports_count') : ($question->supports_count > 0 && $question->decrement('supports_count'));

        return $this->reply($request, ['active' => $active, 'count' => $question->fresh()->supports_count],
            $active ? __('Vous soutenez cette question.') : __('Soutien retiré.'));
    }

    /** Réponse du candidat : texte et/ou vidéo. */
    public function answer(Request $request, Question $question, MediaService $media, VideoProcessor $processor)
    {
        $user = $request->user();
        abort_unless($question->canBeAnsweredBy($user), 403, __('Seul le candidat concerné peut répondre.'));
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:20000', 'required_without_all:video,video_upload'],
            'video' => ['nullable', 'file', 'mimes:'.MediaService::VIDEO_MIMES, 'max:'.(config('vwajen.limits.video_mb') * 1024)],
            'video_upload' => ['nullable', 'uuid'],
        ]);

        $answer = DB::transaction(function () use ($request, $data, $question, $user, $media) {
            $video = null;
            if ($request->hasFile('video') || ! empty($data['video_upload'])) {
                $path = $request->hasFile('video')
                    ? $media->storeFile($request->file('video'), 'videos/'.date('Y/m'))
                    : $media->adoptUpload(Upload::where('uuid', $data['video_upload'])->where('user_id', $user->id)->firstOrFail(), 'videos/'.date('Y/m'));
                $video = Video::create([
                    'user_id' => $user->id, 'kind' => 'long', 'path' => $path,
                    'title' => __('Réponse : :q', ['q' => Str::limit($question->title, 180)]),
                    'processing_status' => 'pending', 'source_type' => 'question', 'source_id' => $question->id,
                ]);
            }
            $answer = Answer::create(['question_id' => $question->id, 'user_id' => $user->id, 'body' => $data['body'] ?? null, 'video_id' => $video?->id]);
            $question->update(['status' => 'answered', 'answered_at' => $question->answered_at ?? now()]);
            $question->increment('answers_count');

            return $answer;
        });
        if ($answer->video) {
            dispatch(fn () => app(VideoProcessor::class)->process($answer->video))->afterResponse();
        }

        // Notification au demandeur + aux soutiens
        $this->notifier->send($question->user, 'candidate_answer', $user, ':name a répondu à votre question', ['name' => $user->name], $answer->url(), $question);
        $this->notifier->send($question->supporters()->where('users.id', '!=', $question->user_id)->get(), 'question_answered', $user,
            ':name a répondu à une question que vous soutenez', ['name' => $user->name], $answer->url(), $question);

        return redirect()->to($answer->url())->with('status', __('Réponse publiée.'));
    }

    public function close(Request $request, Question $question)
    {
        $user = $request->user();
        abort_unless(in_array($user->id, [$question->user_id, $question->candidate_id], true) || $user->hasPermission('moderation.manage'), 403);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $question->update(['status' => 'closed', 'closed_at' => now(), 'close_reason' => $data['reason'] ?? null]);

        return back()->with('status', __('Question fermée.'));
    }

    public function reopen(Request $request, Question $question)
    {
        $user = $request->user();
        abort_unless($user->id === $question->user_id || $user->hasPermission('moderation.manage'), 403);
        $question->update(['status' => $question->answers_count ? 'answered' : 'open', 'closed_at' => null, 'close_reason' => null]);

        return back()->with('status', __('Question rouverte.'));
    }

    public function destroy(Request $request, Question $question)
    {
        $user = $request->user();
        abort_unless(($user->id === $question->user_id && $question->answers_count === 0) || $user->hasPermission('content.delete'), 403);
        $question->delete();

        return redirect()->route('questions.index')->with('status', __('Question supprimée.'));
    }

    /** Historique des questions de l'utilisateur. */
    public function mine(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'supported' ? 'supported' : 'asked';
        $questions = ($tab === 'supported'
            ? Question::whereHas('supporters', fn ($q) => $q->where('users.id', $user->id))->where('user_id', '!=', $user->id)
            : Question::where('user_id', $user->id))
            ->with(['candidate', 'category'])->latest()->paginate(20)->withQueryString();

        return view('questions.mine', compact('questions', 'tab'));
    }
}
