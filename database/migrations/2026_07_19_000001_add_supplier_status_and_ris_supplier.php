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

        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            // ADDED RIS MODULE: optional preferred supplier; final supplier is still selected in ATP.
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_supplier_id')) {
                $table->bigInteger('ris_supplier_id')->nullable()->after('ris_procurement_request_id');
            }
        });

        $this->addIndexIfMissing('suppliers_table', 'idx_supplier_is_active', 'supplier_is_active');
        $this->addIndexIfMissing('requisition_issue_slip_table', 'idx_ris_supplier_id', 'ris_supplier_id');
        $this->addForeignIfMissing(
            'requisition_issue_slip_table',
            'fk_ris_supplier',
            'ris_supplier_id',
            'suppliers_table',
            'supplier_id'
        );
    }

    public function down(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_ris_supplier');
            } catch (Throwable $exception) {
                // ADDED RIS MODULE: ignore missing FK during rollback.
            }

            try {
                $table->dropIndex('idx_ris_supplier_id');
            } catch (Throwable $exception) {
                // ADDED RIS MODULE: ignore missing index during rollback.
            }

            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_supplier_id')) {
                $table->dropColumn('ris_supplier_id');
            }
        });

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

    private function addForeignIfMissing(
        string $table,
        string $foreignName,
        string $column,
        string $referencesTable,
        string $referencesColumn
    ): void {
        $exists = collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $foreignName]
        ))->isNotEmpty();

        if (!$exists) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$foreignName} FOREIGN KEY ({$column}) REFERENCES {$referencesTable} ({$referencesColumn}) ON DELETE SET NULL");
        }
    }
};
