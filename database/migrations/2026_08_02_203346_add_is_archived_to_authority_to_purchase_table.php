<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns already added by 2026_07_17_000002_update_authority_to_purchase_table.
 * Kept as a guarded no-op so environments that already recorded this migration stay valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
            Schema::table('authority_to_purchase_table', function (Blueprint $table) {
                $table->boolean('authority_purchase_is_archived')
                    ->default(false)
                    ->after('authority_purchase_rejection_reason');
            });
        }
    }

    public function down(): void
    {
        // Do not drop: owned by 2026_07_17_000002.
    }
};
