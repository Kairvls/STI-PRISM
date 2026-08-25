<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MicrosoftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO MICROSOFT
    |--------------------------------------------------------------------------
    */

    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    /*
    |--------------------------------------------------------------------------
    | MICROSOFT CALLBACK
    |--------------------------------------------------------------------------
    */

    public function handleMicrosoftCallback()
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | GET MICROSOFT USER
            |--------------------------------------------------------------------------
            */

            $microsoftUser = Socialite::driver('microsoft')->user();

            /*
            |--------------------------------------------------------------------------
            | FIND USER USING EMAIL
            |--------------------------------------------------------------------------
            */

            $user = User::where(
                'user_email_address',
                $microsoftUser->getEmail()
            )->first();

            /*
            |--------------------------------------------------------------------------
            | USER NOT FOUND
            |--------------------------------------------------------------------------
            */

            if(!$user){

                return redirect('/')
                    ->with(
                        'error',
                        'Microsoft account is not registered.'
                    );

            }

            /*
            |--------------------------------------------------------------------------
            | LOGIN USER
            |--------------------------------------------------------------------------
            */

            Auth::login($user);
            request()->session()->regenerate();
            request()->session()->put('attention_popup_token', (string) \Illuminate\Support\Str::uuid());

            /*
            |--------------------------------------------------------------------------
            | ROLE REDIRECT
            |--------------------------------------------------------------------------
            */

            if($user->user_role_id == 1){

                return redirect('/admin/dashboard');

            }

            elseif($user->user_role_id == 2){

                return redirect('/maintenance/dashboard');

            }

            elseif($user->user_role_id == 3){

                return redirect('/purchaser/dashboard');

            }

            elseif($user->user_role_id == 4){

                return redirect('/president/dashboard');

            }

            elseif($user->user_role_id == 5){

                return redirect('/accounting/dashboard');

            }

            elseif($user->user_role_id == 6){

                return redirect('/receiving/dashboard');

            }

            return redirect('/dashboard');

        }

        catch (\Exception $e) {

            return redirect('/')
                ->with(
                    'error',
                    'Microsoft login failed.'
                );

        }
    }
}