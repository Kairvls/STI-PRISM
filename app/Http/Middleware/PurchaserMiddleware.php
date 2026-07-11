<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PurchaserMiddleware
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
        // USER MUST BE PURCHASER
        // user_role_id = 3
        // =====================================================

        if ((int) Auth::user()->user_role_id !== 3) {

            abort(403);

        }


        // =====================================================
        // ALLOW REQUEST
        // =====================================================

        return $next($request);
    }
}