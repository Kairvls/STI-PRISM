<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminLoginGate;
use App\Support\RoleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO MICROSOFT
    |--------------------------------------------------------------------------
    */

    public function redirectToMicrosoft()
    {
        if (request()->boolean('admin') || AdminLoginGate::isAdminIntent()) {
            AdminLoginGate::markAdminIntent();

            $key = $this->adminRateKey();
            if (RateLimiter::tooManyAttempts($key, 8)) {
                $seconds = RateLimiter::availableIn($key);

                return redirect()
                    ->route('admin.login')
                    ->with('error', "Too many admin sign-in attempts. Try again in {$seconds} seconds.");
            }

            RateLimiter::hit($key, 60);
        } else {
            AdminLoginGate::clearIntent();
        }

        // Pick an account, then force fresh auth (password + MFA).
        // max_age=0 = must re-authenticate; avoids silent SSO when status is "Signed in".
        // Do not combine prompt values (Azure AADSTS90023).
        return Socialite::driver('microsoft')
            ->with([
                'prompt' => 'select_account',
                'max_age' => '0',
            ])
            ->redirect();
    }

    /*
    |--------------------------------------------------------------------------
    | MICROSOFT CALLBACK
    | Person login by Office 365 email (password + MFA on Microsoft).
    | Roles are not credentials — primary role chooses the home dashboard;
    | additional roles are available via the portal switcher.
    | Admin intent (/admin/login) requires Administrator role (+ optional email allowlist).
    |--------------------------------------------------------------------------
    */

    public function handleMicrosoftCallback(): RedirectResponse
    {
        $adminIntent = AdminLoginGate::isAdminIntent();
        $failRedirect = $adminIntent ? route('admin.login') : '/';

        try {
            $code = (string) request('code', '');

            if ($code === '') {
                $error = (string) request('error_description', request('error', 'Login was cancelled.'));
                if ($adminIntent) {
                    AdminLoginGate::recordLogin(null, 'Failed', 'Admin O365 login cancelled or denied: '.$error);
                }

                return redirect($failRedirect)
                    ->with('error', 'Microsoft login failed: '.$error);
            }

            $tenant = config('services.microsoft.tenant', 'common');
            $tokenResponse = Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token",
                [
                    'client_id' => config('services.microsoft.client_id'),
                    'client_secret' => config('services.microsoft.client_secret'),
                    'code' => $code,
                    'redirect_uri' => config('services.microsoft.redirect'),
                    'grant_type' => 'authorization_code',
                    'scope' => 'openid profile email User.Read offline_access',
                ]
            );

            if (! $tokenResponse->successful()) {
                Log::warning('Microsoft token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->json() ?? $tokenResponse->body(),
                ]);

                return redirect($failRedirect)
                    ->with('error', 'Microsoft login failed. Check Azure app credentials and redirect URI, then try again.');
            }

            $accessToken = (string) $tokenResponse->json('access_token', '');

            if ($accessToken === '') {
                return redirect($failRedirect)
                    ->with('error', 'Microsoft login failed. No access token returned.');
            }

            $graphResponse = Http::withToken($accessToken)
                ->acceptJson()
                ->get('https://graph.microsoft.com/v1.0/me');

            if (! $graphResponse->successful()) {
                Log::warning('Microsoft Graph /me failed', [
                    'status' => $graphResponse->status(),
                    'body' => $graphResponse->json() ?? $graphResponse->body(),
                ]);

                return redirect($failRedirect)
                    ->with('error', 'Microsoft login failed. Could not read your Microsoft profile.');
            }

            $graphUser = $graphResponse->json();

            $email = strtolower(trim((string) (
                $graphUser['mail']
                ?? $graphUser['userPrincipalName']
                ?? ''
            )));

            if ($email === '') {
                return redirect($failRedirect)
                    ->with('error', 'Microsoft account did not return an email address.');
            }

            $user = User::whereRaw('LOWER(user_email_address) = ?', [$email])->first();

            if (! $user) {
                if ($adminIntent) {
                    AdminLoginGate::recordLogin(null, 'Failed', 'Admin O365 login: email not registered ('.$email.').');
                }

                return redirect($failRedirect)
                    ->with(
                        'error',
                        'Your Microsoft account is not registered in PaAyo. Ask an admin to add your Office 365 email first.'
                    );
            }

            if ($adminIntent) {
                if (! RoleAccess::isAdmin($user)) {
                    AdminLoginGate::recordLogin(
                        (int) $user->user_id,
                        'Failed',
                        'Admin O365 login denied: account is not an Administrator ('.$email.').'
                    );
                    AdminLoginGate::clearIntent();

                    return redirect()
                        ->route('admin.login')
                        ->with('error', 'This account is not an Administrator. Use the main staff sign-in instead.');
                }

                if (! AdminLoginGate::emailIsAllowed($email)) {
                    AdminLoginGate::recordLogin(
                        (int) $user->user_id,
                        'Failed',
                        'Admin O365 login denied: email not on ADMIN_ALLOWED_EMAILS ('.$email.').'
                    );
                    AdminLoginGate::clearIntent();

                    return redirect()
                        ->route('admin.login')
                        ->with('error', 'This Administrator account is not allowed to sign in here.');
                }
            } elseif (RoleAccess::isAdmin($user)) {
                // Staff login must not admit administrators (and must not reveal /admin/login).
                AdminLoginGate::recordLogin(
                    (int) $user->user_id,
                    'Failed',
                    'Administrator blocked from staff O365 login ('.$email.').'
                );
                AdminLoginGate::clearIntent();

                return redirect('/')
                    ->with('error', 'Administrators can sign in on the admin page.');
            }

            Auth::login($user);
            request()->session()->regenerate();
            request()->session()->put('attention_popup_token', (string) Str::uuid());

            if ($adminIntent) {
                RateLimiter::clear($this->adminRateKey());
                AdminLoginGate::recordLogin(
                    (int) $user->user_id,
                    'Success',
                    'Administrator signed in via Office 365 (/admin/login).'
                );
                AdminLoginGate::clearIntent();

                return redirect('/admin/dashboard');
            }

            AdminLoginGate::clearIntent();

            return redirect(RoleAccess::dashboardPath((int) $user->user_role_id));
        } catch (\Throwable $e) {
            Log::warning('Microsoft login failed', [
                'type' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            if ($adminIntent) {
                AdminLoginGate::recordLogin(null, 'Failed', 'Admin O365 login exception: '.$e->getMessage());
            }

            return redirect($failRedirect)
                ->with(
                    'error',
                    'Microsoft login failed. Check Azure app credentials and redirect URI, then try again.'
                );
        }
    }

    private function adminRateKey(): string
    {
        return 'admin-o365-login:'.request()->ip();
    }
}
