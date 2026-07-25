<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // =========================================
        // UPDATE LOGGED IN USER'S ACTIVITY
        // =========================================

        if (auth()->check()) {

            $user = auth()->user();

            $user->last_active_at = now();

            $user->save();
        }

        return $next($request);
    }
}