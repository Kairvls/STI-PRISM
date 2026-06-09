<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * AUTHORIZE REQUEST
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * VALIDATION RULES
     */
    public function rules(): array
    {
        return [

            // USER EMPLOYEE ID
            'user_employee_id' => [
                'required',
                'string',
            ],

            // PASSWORD
            'password' => [
                'required',
                'string',
            ],

        ];
    }

    /**
     * HANDLE AUTHENTICATION
     */
    public function authenticate(): void
    {
        // CHECK RATE LIMITER
        $this->ensureIsNotRateLimited();

        /*
        |--------------------------------------------------------------------------
        | CUSTOM LOGIN
        |--------------------------------------------------------------------------
        */

        if (! Auth::attempt([

            // EMPLOYEE ID
            'user_employee_id' => $this->user_employee_id,

            'user_role_id' => $this->login_role_id,

            // PASSWORD
            'password' => $this->password,

        ], $this->boolean('remember'))) {

            // FAILED ATTEMPTS
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([

                'user_employee_id' =>
                    'Incorrect User ID or Password.',

            ])->redirectTo(url()->previous());
        }

        // CLEAR FAILED ATTEMPTS
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * RATE LIMIT
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts(
            $this->throttleKey(),
            5
        )) {

            return;

        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn(
            $this->throttleKey()
        );

        throw ValidationException::withMessages([

            'user_employee_id' => trans(
                'auth.throttle',
                [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]
            ),

        ]);
    }

    /**
     * THROTTLE KEY
     */
    public function throttleKey(): string
    {
        return Str::transliterate(

            Str::lower(
                $this->string('user_employee_id')
            ) . '|' . $this->ip()

        );
    }
}