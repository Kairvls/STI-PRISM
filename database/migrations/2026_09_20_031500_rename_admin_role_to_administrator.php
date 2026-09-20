<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles_table')) {
            return;
        }

        DB::table('roles_table')
            ->where('role_id', 1)
            ->where('role_name', 'Admin')
            ->update(['role_name' => 'Administrator']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles_table')) {
            return;
        }

        DB::table('roles_table')
            ->where('role_id', 1)
            ->where('role_name', 'Administrator')
            ->update(['role_name' => 'Admin']);
    }
};
