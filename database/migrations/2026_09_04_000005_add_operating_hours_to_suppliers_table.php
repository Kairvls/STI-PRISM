<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suppliers_table')) {
            return;
        }

        if (!Schema::hasColumn('suppliers_table', 'operating_hours')) {
            DB::statement("ALTER TABLE suppliers_table ADD COLUMN operating_hours VARCHAR(255) DEFAULT NULL AFTER supplier_store_type");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('suppliers_table') && Schema::hasColumn('suppliers_table', 'operating_hours')) {
            Schema::table('suppliers_table', function ($table) {
                $table->dropColumn('operating_hours');
            });
        }
    }
};
