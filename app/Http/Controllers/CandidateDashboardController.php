<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\CandidateProfile;
use App\Models\Debate;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Live;
use App\Models\Post;
use App\Models\Question;
use App\Models\Video;
use App\Services\ChangeLogger;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Tableau de bord candidat : statistiques, contenus, questions, programme, lives, événements, débats. */
class CandidateDashboardController extends Controller
{
    public function index(Request $request, ?string $section = 'overview')
    {
        $user = $request->user();
        abort_unless(in_array($user->account_type, ['candidate', 'official', 'organization'], true) || $user->candidateProfile, 403);
        $section ??= 'overview';
        $profile = $user->candidateProfile;

        $stats = [
            'followers' => $user->followers_count,
            'new_followers_30' => Follow::where('following_id', $user->id)->where('status', 'accepted')->where('created_at', '>=', now()->subDays(30))->count(),
            'posts' => Post::where('user_id', $user->id)->count(),
            'post_likes' => (int) Post::where('user_id', $user->id)->sum('likes_count'),
            'post_views' => (int) Post::where('user_id', $user->id)->sum('views_count'),
            'videos' => Video::where('user_id', $user->id)->longs()->count(),
            'shorts' => Video::where('user_id', $user->id)->shorts()->count(),
            'video_views' => (int) Video::where('user_id', $user->id)->sum('views_count'),
            'lives' => Live::where('user_id', $user->id)->count(),
            'live_viewers' => (int) Live::where('user_id', $user->id)->sum('total_viewers'),
            'questions' => Question::where('candidate_id', $user->id)->count(),
            'questions_open' => Question::where('candidate_id', $user->id)->where('status', 'open')->count(),
            'answers' => Answer::where('user_id', $user->id)->count(),
            'events' => Event::where('user_id', $user->id)->count(),
            'debates' => Debate::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))->count(),
            'profile_views' => DB::table('views')->where('viewable_type', 'user')->where('viewable_id', $user->id)->count(),
        ];

        $followersChart = Follow::where('following_id', $user->id)->where('status', 'accepted')->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->orderBy('d')->pluck('c', 'd');

        $viewData = compact('user', 'profile', 'section', 'stats', 'followersChart');

        $viewData += match ($section) {
            'posts' => ['items' => Post::where('user_id', $user->id)->latest()->paginate(20)],
            'videos' => ['items' => Video::where('user_id', $user->id)->longs()->latest()->paginate(20)],
            'shorts' => ['items' => Video::where('user_id', $user->id)->shorts()->latest()->paginate(20)],
            'lives' => ['items' => Live::where('user_id', $user->id)->latest()->paginate(20)],
            'questions' => ['items' => Question::where('candidate_id', $user->id)->with('user')
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->orderByRaw("status = 'open' desc")->orderByDesc('supports_count')->paginate(20)->withQueryString()],
            'program' => ['programs' => $user->programs()->with('currentVersion')->withCount('versions')->latest()->get()],
            'events' => ['items' => Event::where('user_id', $user->id)->latest('starts_at')->paginate(20)],
            'debates' => ['items' => Debate::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                ->with(['participants' => fn ($q) => $q->where('user_id', $user->id)])->latest('scheduled_at')->paginate(20)],
            'notifications' => ['items' => $user->notifications()->latest()->paginate(30)],
            'analytics' => $this->analytics($user),
            default => [],
        };

        return view('candidate.dashboard', $viewData);
    }

    /** Analytics avancés : engagement, meilleurs contenus, audience par pays, activité par heure. */
    private function analytics($user): array
    {
        $posts = Post::where('user_id', $user->id);
        $totalViews = max(1, (int) (clone $posts)->sum('views_count'));
        $engagement = (int) (clone $posts)->selectRaw('SUM(likes_count + comments_count + reposts_count + shares_count) e')->value('e');
        $viewsDaily = [];
        $rows = DB::table('views')->where(fn ($q) => $q->where(fn ($w) => $w->where('viewable_type', 'post')->whereIn('viewable_id', Post::where('user_id', $user->id)->select('id')))
            ->orWhere(fn ($w) => $w->where('viewable_type', 'video')->whereIn('viewable_id', Video::where('user_id', $user->id)->select('id'))))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $viewsDaily[$d] = (int) ($rows[$d] ?? 0);
        }

        return [
            'engagementRate' => round($engagement * 100 / $totalViews, 1),
            'viewsDaily' => $viewsDaily,
            'topPosts' => (clone $posts)->orderByRaw('(likes_count + comments_count * 2 + reposts_count * 3 + shares_count * 2) desc')->limit(10)->get(),
            'topVideos' => Video::where('user_id', $user->id)->orderByDesc('views_count')->limit(5)->get(),
            'audienceCountries' => DB::table('follows')->join('users', 'users.id', '=', 'follows.follower_id')->where('follows.following_id', $user->id)
                ->where('follows.status', 'accepted')->selectRaw('users.country, COUNT(*) c')->groupBy('users.country')->orderByDesc('c')->limit(10)->pluck('c', 'country'),
            'audienceDepartments' => DB::table('follows')->join('users', 'users.id', '=', 'follows.follower_id')->where('follows.following_id', $user->id)
                ->whereNotNull('users.department')->selectRaw('users.department, COUNT(*) c')->groupBy('users.department')->orderByDesc('c')->pluck('c', 'department'),
            'byHour' => (clone $posts)->selectRaw('HOUR(created_at) h, AVG(likes_count + comments_count) e')->groupBy('h')->pluck('e', 'h'),
            'watchTime' => (int) DB::table('views')->where('viewable_type', 'video')->whereIn('viewable_id', Video::where('user_id', $user->id)->select('id'))->sum('watch_seconds'),
        ];
    }

    /** Export CSV des performances des contenus (outil avancé pour organisations et candidats). */
    public function exportAnalytics(Request $request)
    {
        $user = $request->user();

        return response()->streamDownload(function () use ($user) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['type', 'id', 'date', 'titre', 'vues', 'likes', 'commentaires', 'reposts', 'partages', 'url']);
            foreach (Post::where('user_id', $user->id)->latest()->cursor() as $p) {
                fputcsv($out, ['post', $p->id, $p->created_at->toDateTimeString(), mb_substr((string) $p->body, 0, 80), $p->views_count, $p->likes_count, $p->comments_count, $p->reposts_count, $p->shares_count, $p->url()]);
            }
            foreach (Video::where('user_id', $user->id)->latest()->cursor() as $v) {
                fputcsv($out, [$v->kind, $v->id, $v->created_at->toDateTimeString(), $v->title, $v->views_count, $v->likes_count, $v->comments_count, $v->reposts_count, $v->shares_count, $v->url()]);
            }
            fclose($out);
        }, 'vwajen-analytics-'.$user->username.'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Gestion du profil candidat (les champs importants sont historisés publiquement). */
    public function updateProfile(Request $request, MediaService $media)
    {
        $user = $request->user();
        $profile = $user->candidateProfile;
        abort_unless($profile, 404);

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
            'change_note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('photo')) {
            $media->delete($profile->photo);
            $data['photo'] = $media->storeImage($request->file('photo'), 'candidates', 800);
        }
        $note = $data['change_note'] ?? null;
        unset($data['change_note']);
        $profile->update($data);
        ChangeLogger::diff($profile, CandidateProfile::TRACKED, $note);

        return back()->with('status', __('Profil candidat mis à jour.'));
    }
}
