<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AdminLoginGate;
use App\Support\RoleAccess;
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
        AdminLoginGate::clearIntent();

        return view('auth.login');
    }

    /**
     * Quiet Office 365-only admin entry (not linked from the public landing).
     */
    public function createAdmin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            if (RoleAccess::isAdmin(Auth::user())) {
                return redirect('/admin/dashboard');
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        AdminLoginGate::markAdminIntent();

        return view('auth.admin-login');
    }

    /**
     * HANDLE LOGIN REQUEST
     * Password login is disabled — Office 365 SSO only (person by email + MFA).
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect('/')
            ->with(
                'error',
                'Password login is disabled. Please use Log in with Office 365.'
            );
    }

    /**
     * HANDLE LOGOUT
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        AdminLoginGate::clearIntent();

        return redirect('/');
    }
}
