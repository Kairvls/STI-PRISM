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
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
                $table->string('borrowing_transaction_no', 32)->nullable()->after('borrowing_record_id');
                $table->index('borrowing_transaction_no', 'borrowing_transaction_no_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')) {
                $table->dropIndex('borrowing_transaction_no_idx');
                $table->dropColumn('borrowing_transaction_no');
            }
        });
    }
};
