<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PurchaserApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! RoleAccess::hasRole(RoleAccess::PURCHASER, Auth::user())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
