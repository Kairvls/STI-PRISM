<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('physical_suppliers_table') && !Schema::hasColumn('physical_suppliers_table', 'landline_number')) {
            DB::statement("ALTER TABLE physical_suppliers_table ADD COLUMN landline_number VARCHAR(50) DEFAULT NULL AFTER contact_number");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('physical_suppliers_table') && Schema::hasColumn('physical_suppliers_table', 'landline_number')) {
            Schema::table('physical_suppliers_table', function ($table) {
                $table->dropColumn('landline_number');
            });
        }
    }
};
