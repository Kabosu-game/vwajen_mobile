<?php

namespace App\Http\Middleware;

use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Utilisateurs actifs (statistiques) + déconnexion des appareils révoqués à distance. */
class TrackActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $revoked = UserDevice::where('user_id', $user->id)->where('session_id', $request->session()->getId())
                ->whereNotNull('revoked_at')->exists();
            if ($revoked) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', __('Cet appareil a été déconnecté à distance.'));
            }

            if (! $user->last_active_at || $user->last_active_at->lt(now()->subMinutes(5))) {
                $user->forceFill(['last_active_at' => now()])->saveQuietly();
                DB::table('user_activity_days')->insertOrIgnore(['user_id' => $user->id, 'day' => now()->toDateString()]);
            }
        }

        return $next($request);
    }
}
