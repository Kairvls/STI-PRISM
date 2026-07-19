<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers_table', function (Blueprint $table) {
            // ADDED SUPPLIERS MODULE: supports deactivate/reactivate without deleting referenced suppliers.
            if (!Schema::hasColumn('suppliers_table', 'supplier_is_active')) {
                $table->boolean('supplier_is_active')->default(true)->after('supplier_store_type');
            }
        });

        $this->addIndexIfMissing('suppliers_table', 'idx_supplier_is_active', 'supplier_is_active');
    }

    public function down(): void
    {
        Schema::table('suppliers_table', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_supplier_is_active');
            } catch (Throwable $exception) {
                // ADDED SUPPLIERS MODULE: ignore missing index during rollback.
            }

            if (Schema::hasColumn('suppliers_table', 'supplier_is_active')) {
                $table->dropColumn('supplier_is_active');
            }
        });
    }

    private function addIndexIfMissing(string $table, string $indexName, string $column): void
    {
        $exists = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]))->isNotEmpty();

        if (!$exists) {
            DB::statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$column})");
        }
    }
};
