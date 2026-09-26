<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Like;
use App\Models\Live;
use App\Models\Post;
use App\Models\Question;
use App\Models\Report;
use App\Models\Repost;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Statistiques de la plateforme (tableau de bord admin, export, API). */
class StatsService
{
    public function totals(?Carbon $from = null, ?Carbon $to = null): array
    {
        $range = fn ($q, $col = 'created_at') => $q->when($from, fn ($q) => $q->where($col, '>=', $from))->when($to, fn ($q) => $q->where($col, '<=', $to));

        return [
            'users' => User::count(),
            'new_users' => $range(User::query())->count(),
            'active_users_1' => DB::table('user_activity_days')->where('day', today()->toDateString())->count(),
            'active_users_7' => DB::table('user_activity_days')->where('day', '>=', today()->subDays(6)->toDateString())->distinct('user_id')->count('user_id'),
            'active_users_30' => DB::table('user_activity_days')->where('day', '>=', today()->subDays(29)->toDateString())->distinct('user_id')->count('user_id'),
            'posts' => $range(Post::query())->count(),
            'likes' => $range(Like::query())->count(),
            'comments' => $range(Comment::query())->count(),
            'reposts' => $range(Repost::query())->count(),
            'videos' => $range(Video::longs())->count(),
            'shorts' => $range(Video::shorts())->count(),
            'views' => $range(DB::table('views'))->count(),
            'lives' => $range(Live::query())->count(),
            'live_viewers' => (int) $range(Live::query())->sum('total_viewers'),
            'questions' => $range(Question::query())->count(),
            'answers' => $range(Answer::query())->count(),
            'events' => $range(Event::query())->count(),
            'reports' => $range(Report::query())->count(),
            'reports_pending' => Report::where('status', 'pending')->count(),
            'candidates' => User::where('account_type', 'candidate')->count(),
            'verified_candidates' => User::where('account_type', 'candidate')->where('is_verified', true)->count(),
        ];
    }

    /** Série quotidienne sur N jours pour une table. */
    public function daily(string $table, int $days = 30, string $column = 'created_at', array $where = []): array
    {
        $rows = DB::table($table)->where($column, '>=', today()->subDays($days - 1))
            ->when($where, fn ($q) => $q->where($where))
            ->selectRaw("DATE($column) d, COUNT(*) c")->groupBy('d')->pluck('c', 'd');
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = today()->subDays($i)->toDateString();
            $out[$d] = (int) ($rows[$d] ?? 0);
        }

        return $out;
    }

    public function activeDaily(int $days = 30): array
    {
        $rows = DB::table('user_activity_days')->where('day', '>=', today()->subDays($days - 1))->selectRaw('day d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = today()->subDays($i)->toDateString();
            $out[$d] = (int) ($rows[$d] ?? 0);
        }

        return $out;
    }

    /** Statistiques par candidat. */
    public function candidates(): Collection
    {
        return User::where('account_type', 'candidate')->with('candidateProfile')->get()->map(fn ($u) => [
            'user' => $u,
            'followers' => $u->followers_count,
            'posts' => Post::where('user_id', $u->id)->count(),
            'videos' => Video::where('user_id', $u->id)->count(),
            'lives' => Live::where('user_id', $u->id)->count(),
            'questions' => Question::where('candidate_id', $u->id)->count(),
            'answered' => Question::where('candidate_id', $u->id)->where('status', 'answered')->count(),
            'likes' => (int) Post::where('user_id', $u->id)->sum('likes_count'),
        ])->sortBy(fn ($r) => mb_strtolower($r['user']->name))->values();
    }

    public function breakdowns(): array
    {
        return [
            'account_types' => User::selectRaw('account_type, COUNT(*) c')->groupBy('account_type')->pluck('c', 'account_type'),
            'countries' => User::selectRaw('country, COUNT(*) c')->groupBy('country')->orderByDesc('c')->limit(10)->pluck('c', 'country'),
            'departments' => User::whereNotNull('department')->selectRaw('department, COUNT(*) c')->groupBy('department')->orderByDesc('c')->pluck('c', 'department'),
            'locales' => User::selectRaw('locale, COUNT(*) c')->groupBy('locale')->pluck('c', 'locale'),
            'report_reasons' => Report::selectRaw('reason, COUNT(*) c')->groupBy('reason')->orderByDesc('c')->pluck('c', 'reason'),
        ];
    }
}
