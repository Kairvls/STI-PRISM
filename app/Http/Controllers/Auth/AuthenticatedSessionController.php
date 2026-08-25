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
        // =====================================================
        // AUTHENTICATE USER HERE
        // LoginRequest checks employee ID, password, and role
        // =====================================================

        $request->authenticate();


        // =====================================================
        // REGENERATE SESSION HERE
        // Protects the session after successful login
        // =====================================================

        $request->session()->regenerate();

        // New token each login so attention popups show again.
        $request->session()->put('attention_popup_token', (string) \Illuminate\Support\Str::uuid());


        // =====================================================
        // GET AUTHENTICATED USER HERE
        // =====================================================

        $user = Auth::user();


        // =====================================================
        // REDIRECT USER BASED ON ROLE HERE
        //
        // 1 = Admin
        // 2 = Maintenance Personnel
        // 3 = Purchaser
        // 4 = President
        // 5 = Accounting
        // 6 = Receiving Officer
        // =====================================================

        return match ((int) $user->user_role_id) {

            // ADMIN
            1 => redirect('/admin/dashboard'),

            // MAINTENANCE PERSONNEL
            2 => redirect('/maintenance/dashboard'),

            // PURCHASER
            3 => redirect('/purchaser/dashboard'),

            // PRESIDENT
            4 => redirect('/president/dashboard'),

            // ACCOUNTING
            5 => redirect('/accounting/dashboard'),

            // RECEIVING OFFICER
            6 => redirect('/receiving/dashboard'),

            // UNKNOWN ROLE
            default => $this->logoutUnknownRole($request),
        };
    }


    /**
     * =====================================================
     * LOGOUT USER WHEN ROLE IS INVALID HERE
     * =====================================================
     */
    private function logoutUnknownRole(
        LoginRequest $request
    ): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login')
            ->withErrors([
                'user_employee_id' =>
                    'Your account does not have a valid system role.',
            ]);
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
        return redirect('http://127.0.0.1:8000/');
    }
}