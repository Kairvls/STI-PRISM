<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('online_suppliers_table')) {
            return;
        }

        if (!Schema::hasColumn('online_suppliers_table', 'contact_person')) {
            DB::statement("ALTER TABLE online_suppliers_table ADD COLUMN contact_person VARCHAR(255) DEFAULT NULL AFTER shop_name");
        }

        if (!Schema::hasColumn('online_suppliers_table', 'email_address')) {
            DB::statement("ALTER TABLE online_suppliers_table ADD COLUMN email_address VARCHAR(255) DEFAULT NULL AFTER contact_person");
        }

        if (!Schema::hasColumn('online_suppliers_table', 'contact_number')) {
            DB::statement("ALTER TABLE online_suppliers_table ADD COLUMN contact_number VARCHAR(50) DEFAULT NULL AFTER email_address");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('online_suppliers_table')) {
            return;
        }

        foreach (['contact_number', 'email_address', 'contact_person'] as $column) {
            if (Schema::hasColumn('online_suppliers_table', $column)) {
                Schema::table('online_suppliers_table', function ($table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
