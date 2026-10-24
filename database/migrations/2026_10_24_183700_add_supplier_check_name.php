<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers_table') || Schema::hasColumn('suppliers_table', 'supplier_check_name')) {
            return;
        }

        DB::statement('ALTER TABLE suppliers_table ADD COLUMN supplier_check_name VARCHAR(255) DEFAULT NULL AFTER supplier_store_type');
    }

    public function down(): void
    {
        if (Schema::hasTable('suppliers_table') && Schema::hasColumn('suppliers_table', 'supplier_check_name')) {
            Schema::table('suppliers_table', function ($table) {
                $table->dropColumn('supplier_check_name');
            });
        }
    }
};
