<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * DISPLAY LOGIN PAGE
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * HANDLE LOGIN REQUEST
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // AUTHENTICATE USER
        $request->authenticate();

        // REGENERATE SESSION
        $request->session()->regenerate();

        // GET CURRENT LOGGED-IN USER
        $user = Auth::user();

        /**
         * ROLE IDS
         *
         * 1 = Admin
         * 2 = Maintenance Personnel
         * 3 = Purchaser
         * 4 = President
         * 5 = Accounting
         * 6 = Receiving Officer
         */

        // ADMIN
        if ($user->user_role_id == 1) {

            return redirect('/admin/dashboard');

        }

        // MAINTENANCE PERSONNEL
        elseif ($user->user_role_id == 2) {

            return redirect('/maintenance/dashboard');

        }

        // PURCHASER
        elseif ($user->user_role_id == 3) {

            return redirect('/purchaser/dashboard');

        }

        // PRESIDENT
        elseif ($user->user_role_id == 4) {

            return redirect('/president/dashboard');

        }

        // ACCOUNTING
        elseif ($user->user_role_id == 5) {

            return redirect('/accounting/dashboard');

        }

        // RECEIVING OFFICER
        elseif ($user->user_role_id == 6) {

            return redirect('/receiving/dashboard');

        }

        // FALLBACK
        return redirect('/dashboard');
    }

    /**
     * HANDLE LOGOUT
     */
    public function destroy(Request $request): RedirectResponse
    {
        // LOGOUT USER
        Auth::guard('web')->logout();

        // INVALIDATE SESSION
        $request->session()->invalidate();

        // REGENERATE CSRF TOKEN
        $request->session()->regenerateToken();

        // REDIRECT TO HOME PAGE
        return redirect('/');
    }
}