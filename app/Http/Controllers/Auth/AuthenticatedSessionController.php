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
     * Password login is disabled — Office 365 SSO only.
     */
    public function store(LoginRequest $request): RedirectResponse
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

        return redirect('/');
    }
}
