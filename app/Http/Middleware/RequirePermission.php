<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage : ->middleware('permission:users.manage') ou 'permission:staff' pour tout membre de l'équipe. */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        foreach ($permissions as $permission) {
            if ($permission === 'staff' ? $user->isStaff() : $user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, __('Accès refusé.'));
    }
}
