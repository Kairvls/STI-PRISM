<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // USER NOT LOGGED IN
        if (!Auth::check()) {

            return redirect('/login');

        }

        // GET LOGGED-IN USER
        $user = Auth::user();

        // NOT ADMIN
        if ($user->user_role_id != 1) {

            abort(403);

        }

        return $next($request);
    }
}