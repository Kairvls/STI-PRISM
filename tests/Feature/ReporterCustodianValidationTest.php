<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ReporterCustodianValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_reporter_rejected_when_person_mismatch()
    {
        // Insert a custodian with a specific name/email
        DB::table('custodians_table')->insert([
            'custodian_full_name' => 'Ana Reyes',
            'custodian_employee_id' => 'OMC00180F',
            'custodian_email_address' => 'ana@example.com',
            'custodian_contact_number' => '09101234567',
            'custodian_status' => 'Active',
        ]);

        $resp = $this->post('/admin/reporters/store', [
            'employee_id' => 'OMC00180F',
            'first_name' => 'Different',
            'last_name' => 'Name',
            'type' => 'Faculty',
            'email' => 'other@example.com',
            'contact' => '09109876543',
        ]);
        // Ensure we're authenticated as an admin user so controller redirects don't send us to login
        $userId = DB::table('users_table')->insertGetId([
            'user_role_id' => 1,
            'user_username' => 'testadmin',
            'user_full_name' => 'Test Admin',
            'user_email_address' => 'admin@example.com',
            'user_password' => 'secret',
        ]);
        $user = \App\Models\User::find($userId);
        $this->actingAs($user);

        $resp = $this->post('/admin/reporters/store', [
            'employee_id' => 'OMC00180F',
            'first_name' => 'Different',
            'last_name' => 'Name',
            'type' => 'Faculty',
            'email' => 'other@example.com',
            'contact' => '09109876543',
        ]);

        $resp->assertSessionHasErrors();
    }

    public function test_creating_person_rejected_when_reporter_mismatch()
    {
        // Insert a reporter with specific details
        DB::table('reporters_table')->insert([
            'reporter_full_name' => 'Eleanor Vance',
            'reporter_employee_id' => 'OMC00999F',
            'reporter_email_address' => 'eleanor@example.com',
            'reporter_contact_number' => '09101112222',
            'reporter_status' => 'Active',
            'reporter_created_at' => now(),
        ]);

        $resp = $this->post('/admin/property-assignments/people', [
            'custodian_first_name' => 'Eleanor',
            'custodian_last_name' => 'Different',
            'custodian_employment_type' => 'Faculty',
            'custodian_employee_id' => 'OMC00999F',
            'custodian_email_address' => 'mismatch@example.com',
            'custodian_contact_number' => '09109998888',
            'custodian_status' => 'Active',
        ]);
        // Authenticate user before posting
        $userId2 = DB::table('users_table')->insertGetId([
            'user_role_id' => 1,
            'user_username' => 'testadmin2',
            'user_full_name' => 'Test Admin 2',
            'user_email_address' => 'admin2@example.com',
            'user_password' => 'secret',
        ]);
        $user2 = \App\Models\User::find($userId2);
        $this->actingAs($user2);

        $resp = $this->post('/admin/property-assignments/people', [
            'custodian_first_name' => 'Eleanor',
            'custodian_last_name' => 'Different',
            'custodian_employment_type' => 'Faculty',
            'custodian_employee_id' => 'OMC00999F',
            'custodian_email_address' => 'mismatch@example.com',
            'custodian_contact_number' => '09109998888',
            'custodian_status' => 'Active',
        ]);

        $resp->assertSessionHasErrors();
    }

    public function test_names_reject_digits_and_symbols_in_person_forms()
    {
        $userId = DB::table('users_table')->insertGetId([
            'user_role_id' => 1,
            'user_username' => 'namevalidator',
            'user_full_name' => 'Name Validator Admin',
            'user_email_address' => 'namevalidator@example.com',
            'user_password' => 'secret',
        ]);

        $this->actingAs(\App\Models\User::find($userId));

        $response = $this->post('/admin/property-assignments/people', [
            'custodian_first_name' => 'Ana.',
            'custodian_last_name' => 'Reyes',
            'custodian_employment_type' => 'Faculty',
            'custodian_employee_id' => 'OMC00127F',
            'custodian_email_address' => 'ana@example.com',
            'custodian_contact_number' => '09123456789',
            'custodian_status' => 'Active',
        ]);

        $response->assertSessionHasErrors('custodian_first_name');

        $response = $this->post('/admin/property-assignments/people', [
            'custodian_first_name' => 'Ana',
            'custodian_last_name' => 'Reyes1',
            'custodian_employment_type' => 'Faculty',
            'custodian_employee_id' => 'OMC00128F',
            'custodian_email_address' => 'ana2@example.com',
            'custodian_contact_number' => '09123456788',
            'custodian_status' => 'Active',
        ]);

        $response->assertSessionHasErrors('custodian_last_name');
    }

    public function test_adding_reporter_saves_employment_basis()
    {
        $userId = DB::table('users_table')->insertGetId([
            'user_role_id' => 1,
            'user_username' => 'reporterbasis',
            'user_full_name' => 'Reporter Basis Admin',
            'user_email_address' => 'reporterbasis@example.com',
            'user_password' => 'secret',
        ]);

        $this->actingAs(\App\Models\User::find($userId));

        $response = $this->post('/admin/reporters/store', [
            'employee_id' => 'OMC00127S',
            'first_name' => 'Anna',
            'last_name' => 'Smith',
            'type' => 'Staff',
            'employment_basis' => 'Part time',
            'email' => 'anna.smith@example.com',
            'contact' => '09123456789',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reporters_table', [
            'reporter_employee_id' => 'OMC00127S',
            'reporter_employment_basis' => 'Part time',
        ]);
    }
}
