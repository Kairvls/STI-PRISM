<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fixed role IDs referenced by App\Support\RoleAccess and WorkflowNotifier::ROLE_IDS.
 */
class RoleSeeder extends Seeder
{
    public const ROLES = [
        1 => 'Administrator',
        2 => 'Maintenance Personnel',
        3 => 'Purchaser',
        4 => 'President',
        5 => 'Accounting',
        6 => 'Receiving Officer',
        7 => 'School Administrator',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $id => $name) {
            DB::table('roles_table')->updateOrInsert(['role_id' => $id], ['role_name' => $name]);
        }
    }
}
