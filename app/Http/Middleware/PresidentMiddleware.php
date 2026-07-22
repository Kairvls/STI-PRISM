<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PresidentMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response
    {
        // =====================================================
        // USER MUST BE LOGGED IN
        // =====================================================

        if (!Auth::check()) {

            return redirect('/login');

        }

        // =====================================================
        // USER MUST BE PRESIDENT
        //
        // user_role_id = 4
        // =====================================================

        if ((int) Auth::user()->user_role_id !== 4) {

            abort(403);

        }

        // =====================================================
        // ALLOW REQUEST
        // =====================================================

        return $next($request);
    }
}