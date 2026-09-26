<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request, StatsService $stats)
    {
        $days = in_array((int) $request->query('days'), [7, 30, 90, 365], true) ? (int) $request->query('days') : 30;
        $from = now()->subDays($days);

        return view('admin.stats', [
            'days' => $days,
            'totals' => $stats->totals($from),
            'series' => [
                __('Nouveaux utilisateurs') => $stats->daily('users', $days),
                __('Utilisateurs actifs') => $stats->activeDaily($days),
                __('Publications') => $stats->daily('posts', $days),
                __('Likes') => $stats->daily('likes', $days),
                __('Commentaires') => $stats->daily('comments', $days),
                __('Reposts') => $stats->daily('reposts', $days),
                __('Vues') => $stats->daily('views', $days),
                __('Questions') => $stats->daily('questions', $days),
                __('Signalements') => $stats->daily('reports', $days),
            ],
            'breakdowns' => $stats->breakdowns(),
            'candidates' => $stats->candidates(),
        ]);
    }

    /** Export CSV des statistiques. */
    public function export(Request $request, StatsService $stats)
    {
        $days = (int) $request->query('days', 30);
        $series = [
            'users' => $stats->daily('users', $days), 'active' => $stats->activeDaily($days), 'posts' => $stats->daily('posts', $days),
            'likes' => $stats->daily('likes', $days), 'comments' => $stats->daily('comments', $days), 'reposts' => $stats->daily('reposts', $days),
            'views' => $stats->daily('views', $days), 'videos' => $stats->daily('videos', $days), 'lives' => $stats->daily('lives', $days),
            'questions' => $stats->daily('questions', $days), 'answers' => $stats->daily('answers', $days), 'events' => $stats->daily('events', $days),
            'reports' => $stats->daily('reports', $days),
        ];

        return response()->streamDownload(function () use ($series) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['date'], array_keys($series)));
            foreach (array_keys(reset($series)) as $date) {
                fputcsv($out, array_merge([$date], array_map(fn ($s) => $s[$date], $series)));
            }
            fclose($out);
        }, 'vwajen-stats-'.Carbon::now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
