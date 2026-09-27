<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Purchaser portal. Administrators may also run the procurement workflow from the admin sidebar.
 */
class PurchaserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        $isPurchaser = RoleAccess::hasRole(RoleAccess::PURCHASER, $user);

        if (! $isPurchaser && ! RoleAccess::isAdmin($user)) {
            abort(403);
        }

        if ($isPurchaser && $request->is('purchaser/dashboard')) {
            RoleAccess::enterPortal('purchaser');
        }

        return $next($request);
    }
}
