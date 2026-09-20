<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\RoleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Log in with Office 365');
        $response->assertDontSee('Employee ID or email');
    }

    public function test_admin_login_screen_is_office365_only(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Admin sign in');
        $response->assertSee('Log in with Office 365');
        $response->assertSee('admin=1');
        $response->assertDontSee('Employee ID or email');
    }

    public function test_admin_login_marks_admin_intent(): void
    {
        $this->get('/admin/login')->assertOk();

        $this->assertSame('admin', session('login_intent'));
    }

    public function test_staff_login_clears_admin_intent(): void
    {
        $this->withSession(['login_intent' => 'admin'])
            ->get('/login')
            ->assertOk();

        $this->assertNull(session('login_intent'));
    }

    public function test_guest_admin_routes_redirect_to_admin_login(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_cannot_open_admin_dashboard(): void
    {
        $user = $this->makeStaffUser([
            'user_employee_id' => 'EMP-2001',
            'user_email_address' => 'staff.demo@sti.edu.ph',
            'user_username' => 'staff.demo',
            'user_role_id' => RoleAccess::PURCHASER,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_password_login_is_disabled(): void
    {
        $this->makeStaffUser([
            'user_employee_id' => 'EMP-1001',
            'user_email_address' => 'alven.demo@sti.edu.ph',
            'user_role_id' => RoleAccess::PURCHASER,
        ]);

        $response = $this->from('/')->post('/login', [
            'login' => 'alven.demo@sti.edu.ph',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_users_can_logout(): void
    {
        $user = $this->makeStaffUser([
            'user_employee_id' => 'EMP-1005',
            'user_email_address' => 'logout.demo@sti.edu.ph',
            'user_role_id' => RoleAccess::PURCHASER,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStaffUser(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'user_role_id' => RoleAccess::PURCHASER,
            'user_employee_id' => 'EMP-0000',
            'user_username' => 'demo.user',
            'user_full_name' => 'Demo User',
            'user_email_address' => 'demo@sti.edu.ph',
            'user_contact_number' => '09171234567',
            'user_password' => Hash::make('password'),
        ], $overrides));
    }
}
