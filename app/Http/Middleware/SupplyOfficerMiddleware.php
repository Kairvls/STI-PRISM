<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use App\Support\SupplyOfficerMenuSeen;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SupplyOfficerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (! RoleAccess::hasRole(RoleAccess::SUPPLY_OFFICER, Auth::user())) {
            abort(403);
        }

        RoleAccess::enterPortal('supply-officer');
        SupplyOfficerMenuSeen::capture($request);

        return $next($request);
    }
}
