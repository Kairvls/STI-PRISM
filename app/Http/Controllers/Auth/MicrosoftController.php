<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    |--------------------------------------------------------------------------
    */

    public function handleMicrosoftCallback(): RedirectResponse
    {
        try {
            $code = (string) request('code', '');

            if ($code === '') {
                $error = (string) request('error_description', request('error', 'Login was cancelled.'));

                return redirect('/')
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

                return redirect('/')
                    ->with('error', 'Microsoft login failed. Check Azure app credentials and redirect URI, then try again.');
            }

            $accessToken = (string) $tokenResponse->json('access_token', '');

            if ($accessToken === '') {
                return redirect('/')
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

                return redirect('/')
                    ->with('error', 'Microsoft login failed. Could not read your Microsoft profile.');
            }

            $graphUser = $graphResponse->json();

            $email = strtolower(trim((string) (
                $graphUser['mail']
                ?? $graphUser['userPrincipalName']
                ?? ''
            )));

            if ($email === '') {
                return redirect('/')
                    ->with('error', 'Microsoft account did not return an email address.');
            }

            $user = User::whereRaw('LOWER(user_email_address) = ?', [$email])->first();

            if (! $user) {
                return redirect('/')
                    ->with(
                        'error',
                        'Your Microsoft account is not registered in PRISM. Ask an admin to add your Office 365 email first.'
                    );
            }

            Auth::login($user);
            request()->session()->regenerate();

            return match ((int) $user->user_role_id) {
                1 => redirect('/admin/dashboard'),
                2 => redirect('/maintenance/dashboard'),
                3 => redirect('/purchaser/dashboard'),
                4 => redirect('/president/dashboard'),
                5 => redirect('/accounting/dashboard'),
                6 => redirect('/receiving/dashboard'),
                default => redirect('/'),
            };
        } catch (\Throwable $e) {
            Log::warning('Microsoft login failed', [
                'type' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return redirect('/')
                ->with(
                    'error',
                    'Microsoft login failed. Check Azure app credentials and redirect URI, then try again.'
                );
        }
    }
}
