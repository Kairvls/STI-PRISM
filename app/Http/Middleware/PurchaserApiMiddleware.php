<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PurchaserApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) Auth::user()->user_role_id !== 3) {
            return response()->json([
                'message' => 'Only Purchaser can access this.',
            ], 403);
        }

        return $next($request);
    }
}
