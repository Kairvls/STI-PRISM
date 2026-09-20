<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            // Person identifier: employee ID or email
            'login' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * HANDLE AUTHENTICATION
     *
     * Authenticates the person (employee ID or email + password).
     * Roles are not credentials — primary role only chooses the home dashboard.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = Str::lower(trim($this->string('login')->toString()));
        $password = $this->input('password');

        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->whereRaw('LOWER(user_employee_id) = ?', [$login])
                    ->orWhereRaw('LOWER(user_email_address) = ?', [$login]);
            })
            ->first();

        if (! $user || ! Hash::check($password, (string) $user->user_password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Incorrect employee ID / email or password.',
            ])->redirectTo(url()->previous());
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * CHECK RATE LIMIT
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * THROTTLE KEY
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('login')->toString()).'|'.$this->ip()
        );
    }
}
