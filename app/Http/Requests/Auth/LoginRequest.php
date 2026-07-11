<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
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

            // =====================================================
            // EMPLOYEE ID VALIDATION HERE
            // =====================================================

            'user_employee_id' => [
                'required',
                'string',
            ],


            // =====================================================
            // PASSWORD VALIDATION HERE
            // =====================================================

            'password' => [
                'required',
                'string',
            ],


            // =====================================================
            // SELECTED LOGIN ROLE VALIDATION HERE
            // =====================================================

            'login_role_id' => [
                'required',
                'integer',
                'in:1,2,3,4,5,6',
            ],

        ];
    }


    /**
     * HANDLE AUTHENTICATION
     */
    public function authenticate(): void
    {
        // =====================================================
        // CHECK RATE LIMIT HERE
        // =====================================================

        $this->ensureIsNotRateLimited();


        // =====================================================
        // GET LOGIN DATA HERE
        // =====================================================

        $employeeId = $this->string(
            'user_employee_id'
        )->toString();


        $roleId = (int) $this->input(
            'login_role_id'
        );


        // =====================================================
        // ATTEMPT LOGIN HERE
        // EMPLOYEE ID + ROLE + PASSWORD MUST MATCH SAME USER
        // =====================================================

        if (! Auth::attempt([

            // EMPLOYEE ID
            'user_employee_id' => $this->input('user_employee_id'),

            // SELECTED ROLE
            'user_role_id' => $this->integer('login_role_id'),

            // PASSWORD
            'password' => $this->input('password'),

            ],

            $this->boolean('remember')

        )) {

            // =====================================================
            // RECORD FAILED LOGIN ATTEMPT HERE
            // =====================================================

            RateLimiter::hit(
                $this->throttleKey()
            );


            // =====================================================
            // RETURN LOGIN ERROR HERE
            // =====================================================

            throw ValidationException::withMessages([

                'user_employee_id'
                    => 'Incorrect User ID, Password, or Staff Role.',

            ])->redirectTo(
                url()->previous()
            );
        }


        // =====================================================
        // CLEAR RATE LIMIT HERE
        // =====================================================

        RateLimiter::clear(
            $this->throttleKey()
        );
    }


    /**
     * CHECK RATE LIMIT
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts(

            $this->throttleKey(),

            5

        )) {

            return;
        }


        // =====================================================
        // FIRE LOCKOUT EVENT HERE
        // =====================================================

        event(
            new Lockout($this)
        );


        // =====================================================
        // GET REMAINING LOCKOUT TIME HERE
        // =====================================================

        $seconds = RateLimiter::availableIn(

            $this->throttleKey()

        );


        // =====================================================
        // RETURN RATE LIMIT ERROR HERE
        // =====================================================

        throw ValidationException::withMessages([

            'user_employee_id' => trans(

                'auth.throttle',

                [

                    'seconds'
                        => $seconds,

                    'minutes'
                        => ceil($seconds / 60),

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

                $this->string(
                    'user_employee_id'
                )

            )

            . '|'

            . $this->input(
                'login_role_id'
            )

            . '|'

            . $this->ip()

        );
    }
}