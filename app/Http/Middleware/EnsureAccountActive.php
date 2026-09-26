<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Comptes suspendus / bannis : accès limité à la page de restriction, aux appels, à l'export et à la déconnexion. */
class EnsureAccountActive
{
    private const ALLOWED = ['account.restricted', 'appeals.*', 'logout', 'settings.export', 'settings.sanctions', 'pages.*', 'locale.switch'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->status === 'suspended' && $user->suspended_until && $user->suspended_until->isPast()) {
            $user->forceFill(['status' => 'active', 'suspended_until' => null, 'status_reason' => null])->save();
        }

        if ($user->status !== 'active' && ! $request->routeIs(...self::ALLOWED)) {
            return $request->expectsJson()
                ? response()->json(['message' => __('Votre compte est restreint.')], 403)
                : redirect()->route('account.restricted');
        }

        return $next($request);
    }
}
