<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use App\Support\SchoolAdminMenuSeen;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SchoolAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (! RoleAccess::hasRole(RoleAccess::SCHOOL_ADMIN, Auth::user())) {
            abort(403);
        }

        RoleAccess::enterPortal('school-admin');

        SchoolAdminMenuSeen::capture($request);

        return $next($request);
    }
}
