<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id')) {
                $table->unsignedBigInteger('borrowing_borrower_custodian_id')->nullable()->after('borrowing_borrower_department');
                $table->index('borrowing_borrower_custodian_id', 'borrowing_borrower_custodian_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_borrower_custodian_id')) {
                $table->dropIndex('borrowing_borrower_custodian_idx');
                $table->dropColumn('borrowing_borrower_custodian_id');
            }
        });
    }
};
