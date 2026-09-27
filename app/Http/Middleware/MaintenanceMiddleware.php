<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * "maintenance"       → Maintenance role only.
 * "maintenance:admin" → Maintenance role or an Administrator (shared modules shown in the admin portal).
 */
class MaintenanceMiddleware
{
    public function handle(Request $request, Closure $next, string ...$alsoAllow): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        $isMaintenance = RoleAccess::hasRole(RoleAccess::MAINTENANCE, $user);

        if (! $isMaintenance && ! (in_array('admin', $alsoAllow, true) && RoleAccess::isAdmin($user))) {
            abort(403);
        }

        if ($isMaintenance && $request->is('maintenance/dashboard')) {
            RoleAccess::enterPortal('maintenance');
        }

        return $next($request);
    }
}
