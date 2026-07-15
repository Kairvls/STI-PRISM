<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountingMiddleware
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
        // USER MUST BE ACCOUNTING
        //
        // user_role_id = 5
        // =====================================================

        if ((int) Auth::user()->user_role_id !== 5) {

            abort(403);

        }

        // =====================================================
        // ALLOW REQUEST
        // =====================================================

        return $next($request);
    }
}