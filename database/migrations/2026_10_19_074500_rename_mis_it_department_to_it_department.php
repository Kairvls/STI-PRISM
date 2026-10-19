<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments_table')) {
            return;
        }

        $existsIt = DB::table('departments_table')
            ->whereRaw('LOWER(department_name) = ?', ['it department'])
            ->exists();

        if ($existsIt) {
            DB::table('departments_table')
                ->where('department_name', 'MIS / IT')
                ->delete();

            return;
        }

        DB::table('departments_table')
            ->where('department_name', 'MIS / IT')
            ->update([
                'department_name' => 'IT Department',
                'department_updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('departments_table')) {
            return;
        }

        $existsMis = DB::table('departments_table')
            ->where('department_name', 'MIS / IT')
            ->exists();

        if ($existsMis) {
            return;
        }

        DB::table('departments_table')
            ->where('department_name', 'IT Department')
            ->update([
                'department_name' => 'MIS / IT',
                'department_updated_at' => now(),
            ]);
    }
};
