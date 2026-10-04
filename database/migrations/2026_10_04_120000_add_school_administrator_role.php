<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SCHOOL_ADMIN_ROLE_ID = 7;

    private const IN_FLIGHT_STATUSES = ['Pending', 'Submitted', 'Under Review', 'Resubmitted', 'Accepted'];

    public function up(): void
    {
        if (! Schema::hasTable('roles_table')) {
            return;
        }

        DB::table('roles_table')->updateOrInsert(
            ['role_id' => self::SCHOOL_ADMIN_ROLE_ID],
            ['role_name' => 'School Administrator']
        );

        // RIS review moved from the Administrator to the School Administrator. In-flight RIS still
        // assigned to someone without the new role go back to the shared School Administrator queue.
        if (! Schema::hasTable('requisition_issue_slip_table')
            || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_reviewer_id')) {
            return;
        }

        $schoolAdminIds = DB::table('users_table')
            ->where('user_role_id', self::SCHOOL_ADMIN_ROLE_ID)
            ->pluck('user_id');

        if (Schema::hasTable('user_roles_table')) {
            $schoolAdminIds = $schoolAdminIds->merge(
                DB::table('user_roles_table')->where('role_id', self::SCHOOL_ADMIN_ROLE_ID)->pluck('user_id')
            );
        }

        DB::table('requisition_issue_slip_table')
            ->whereIn('ris_status', self::IN_FLIGHT_STATUSES)
            ->whereNotNull('ris_assigned_reviewer_id')
            ->whereNotIn('ris_assigned_reviewer_id', $schoolAdminIds->map(fn ($id) => (int) $id)->unique()->values()->all() ?: [0])
            ->update(['ris_assigned_reviewer_id' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles_table')) {
            return;
        }

        if (Schema::hasTable('user_roles_table')) {
            DB::table('user_roles_table')->where('role_id', self::SCHOOL_ADMIN_ROLE_ID)->delete();
        }

        $inUse = DB::table('users_table')->where('user_role_id', self::SCHOOL_ADMIN_ROLE_ID)->exists();
        if (! $inUse) {
            DB::table('roles_table')->where('role_id', self::SCHOOL_ADMIN_ROLE_ID)->delete();
        }
    }
};
