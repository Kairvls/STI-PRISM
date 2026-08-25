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
            if (!Schema::hasColumn('suppliers_table', 'supplier_is_blacklisted')) {
                $table->boolean('supplier_is_blacklisted')->default(false)->after('supplier_is_active');
            }
            if (!Schema::hasColumn('suppliers_table', 'supplier_blacklist_reason')) {
                $table->text('supplier_blacklist_reason')->nullable()->after('supplier_is_blacklisted');
            }
            if (!Schema::hasColumn('suppliers_table', 'supplier_blacklisted_at')) {
                $table->timestamp('supplier_blacklisted_at')->nullable()->after('supplier_blacklist_reason');
            }
            if (!Schema::hasColumn('suppliers_table', 'supplier_blacklisted_by')) {
                $table->unsignedBigInteger('supplier_blacklisted_by')->nullable()->after('supplier_blacklisted_at');
            }
        });

        $this->addIndexIfMissing('suppliers_table', 'idx_supplier_is_blacklisted', 'supplier_is_blacklisted');

        if (!Schema::hasTable('supplier_notes_table')) {
            Schema::create('supplier_notes_table', function (Blueprint $table) {
                $table->bigIncrements('supplier_note_id');
                $table->unsignedBigInteger('supplier_id');
                $table->unsignedBigInteger('supplier_note_user_id')->nullable();
                $table->enum('supplier_note_type', ['note', 'blacklist', 'unblacklist'])->default('note');
                $table->text('supplier_note_body');
                $table->timestamp('created_at')->useCurrent();

                $table->index('supplier_id', 'idx_supplier_notes_supplier_id');
                $table->index('supplier_note_type', 'idx_supplier_notes_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_notes_table');

        Schema::table('suppliers_table', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_supplier_is_blacklisted');
            } catch (Throwable $exception) {
                // ignore missing index
            }

            $columns = [
                'supplier_is_blacklisted',
                'supplier_blacklist_reason',
                'supplier_blacklisted_at',
                'supplier_blacklisted_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('suppliers_table', $column)) {
                    $table->dropColumn($column);
                }
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
