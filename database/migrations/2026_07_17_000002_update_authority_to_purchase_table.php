<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_created_by')) {
                $table->bigInteger('authority_purchase_created_by')->nullable()->after('authority_purchase_supplier_id');
            }

            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_submitted_by')) {
                $table->bigInteger('authority_purchase_submitted_by')->nullable()->after('authority_purchase_authorized_by_signature');
            }

            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_submitted_at')) {
                $table->dateTime('authority_purchase_submitted_at')->nullable()->after('authority_purchase_submitted_by');
            }

            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_rejection_reason')) {
                $table->text('authority_purchase_rejection_reason')->nullable()->after('authority_purchase_status');
            }

            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
                $table->boolean('authority_purchase_is_archived')->default(false)->after('authority_purchase_rejection_reason');
            }

            if (!Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_updated_at')) {
                $table->dateTime('authority_purchase_updated_at')->nullable()->after('authority_purchase_created_at');
            }
        });

        DB::statement('ALTER TABLE authority_to_purchase_table MODIFY authority_purchase_created_by BIGINT NULL');
        DB::statement('ALTER TABLE authority_to_purchase_table MODIFY authority_purchase_submitted_by BIGINT NULL');
    }

    public function down(): void
    {
        Schema::table('authority_to_purchase_table', function (Blueprint $table) {
            foreach ([
                'authority_purchase_created_by',
                'authority_purchase_submitted_by',
                'authority_purchase_submitted_at',
                'authority_purchase_rejection_reason',
                'authority_purchase_is_archived',
                'authority_purchase_updated_at',
            ] as $column) {
                if (Schema::hasColumn('authority_to_purchase_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
