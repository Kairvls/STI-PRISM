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

        if (!Schema::hasColumn('online_suppliers_table', 'store_url')) {
            DB::statement("ALTER TABLE online_suppliers_table ADD COLUMN store_url VARCHAR(500) DEFAULT NULL AFTER shop_name");
        }

        if (!Schema::hasColumn('online_suppliers_table', 'seller_id')) {
            DB::statement("ALTER TABLE online_suppliers_table ADD COLUMN seller_id VARCHAR(100) DEFAULT NULL AFTER store_url");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('online_suppliers_table') && Schema::hasColumn('online_suppliers_table', 'seller_id')) {
            Schema::table('online_suppliers_table', function ($table) {
                $table->dropColumn('seller_id');
            });
        }

        if (Schema::hasTable('online_suppliers_table') && Schema::hasColumn('online_suppliers_table', 'store_url')) {
            Schema::table('online_suppliers_table', function ($table) {
                $table->dropColumn('store_url');
            });
        }
    }
};
