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
        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_submitted_by')) {
                $table->bigInteger('authority_purchase_submitted_by')
                    ->nullable()
                    ->after('authority_purchase_authorized_by_signature');
            }

            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_submitted_at')) {
                $table->dateTime('authority_purchase_submitted_at')
                    ->nullable()
                    ->after('authority_purchase_submitted_by');
            }
        });
    }

    public function down(): void
    {
        // Do not drop: owned by 2026_07_17_000002.
    }
};
