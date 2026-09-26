<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appeal;
use App\Models\AuditLog;
use App\Models\Live;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\AiService;
use App\Services\StatsService;
use App\Services\VideoProcessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    /** Vue générale. */
    public function index(StatsService $stats)
    {
        return view('admin.dashboard', [
            'totals' => $stats->totals(),
            'signups' => $stats->daily('users', 30),
            'active' => $stats->activeDaily(30),
            'posts' => $stats->daily('posts', 30),
            'pendingReports' => Report::where('status', 'pending')->with(['reporter'])->orderByRaw("priority = 'high' desc")->latest()->limit(8)->get(),
            'pendingVerifications' => VerificationRequest::where('status', 'pending')->with('user')->latest()->limit(8)->get(),
            'pendingAppeals' => Appeal::where('status', 'pending')->count(),
            'liveNow' => Live::where('status', 'live')->with('user')->get(),
            'recentUsers' => User::latest()->limit(8)->get(),
            'recentAudit' => AuditLog::with('user')->latest('id')->limit(10)->get(),
        ]);
    }

    /** Monitoring système. */
    public function monitoring()
    {
        $dbOk = true;
        $dbTime = null;
        try {
            $t = microtime(true);
            DB::select('SELECT 1');
            $dbTime = round((microtime(true) - $t) * 1000, 1);
        } catch (\Throwable) {
            $dbOk = false;
        }
        $cacheOk = true;
        try {
            Cache::put('monitor.ping', 1, 10);
            $cacheOk = Cache::get('monitor.ping') === 1;
        } catch (\Throwable) {
            $cacheOk = false;
        }

        $disk = storage_path();
        $logFile = storage_path('logs/laravel.log');
        $recentErrors = [];
        if (is_file($logFile)) {
            $fh = fopen($logFile, 'r');
            fseek($fh, max(0, filesize($logFile) - 200000));
            $tail = stream_get_contents($fh);
            fclose($fh);
            preg_match_all('/^\[(\d{4}-\d{2}-\d{2} [\d:]+)\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.{0,300})/m', $tail, $m, PREG_SET_ORDER);
            $recentErrors = array_slice(array_reverse($m), 0, 20);
        }

        return view('admin.monitoring', [
            'checks' => [
                'database' => ['ok' => $dbOk, 'detail' => $dbTime !== null ? $dbTime.' ms' : null],
                'cache' => ['ok' => $cacheOk, 'detail' => config('cache.default')],
                'queue' => ['ok' => true, 'detail' => config('queue.default').' — '.DB::table('jobs')->count().' '.__('en attente').', '.DB::table('failed_jobs')->count().' '.__('en échec')],
                'storage_link' => ['ok' => is_link(public_path('storage')) || is_dir(public_path('storage')), 'detail' => 'public/storage'],
                'ffmpeg' => ['ok' => app(VideoProcessor::class)->available(), 'detail' => config('vwajen.ffmpeg') ?: __('non configuré')],
                'webpush' => ['ok' => (bool) config('vwajen.vapid.public'), 'detail' => config('vwajen.vapid.public') ? 'VAPID' : __('non configuré')],
                'ai' => ['ok' => app(AiService::class)->enabled(), 'detail' => config('vwajen.ai.model')],
                'mail' => ['ok' => config('mail.default') !== 'log', 'detail' => config('mail.default')],
                'sms' => ['ok' => config('vwajen.sms.driver') !== 'log', 'detail' => config('vwajen.sms.driver')],
            ],
            'env' => [
                'PHP' => PHP_VERSION, 'Laravel' => app()->version(), 'Environnement' => app()->environment(),
                'Debug' => config('app.debug') ? 'on' : 'off', 'Timezone' => config('app.timezone'),
                'Disque libre' => round(disk_free_space($disk) / 1073741824, 1).' Go',
                'Médias' => round(collect(Storage::disk('public')->allFiles())->sum(fn ($f) => Storage::disk('public')->size($f)) / 1048576, 1).' Mo',
                'Sessions actives' => DB::table('sessions')->where('last_activity', '>=', now()->subMinutes(15)->timestamp)->count(),
                'Mémoire' => round(memory_get_usage(true) / 1048576, 1).' Mo',
            ],
            'logErrors' => $recentErrors,
        ]);
    }
}
