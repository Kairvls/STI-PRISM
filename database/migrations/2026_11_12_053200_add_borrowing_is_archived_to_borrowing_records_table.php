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
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_is_archived')) {
                $table->boolean('borrowing_is_archived')->default(false)->after('borrowing_status');
                $table->index('borrowing_is_archived', 'borrowing_records_archived_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_is_archived')) {
                $table->dropIndex('borrowing_records_archived_idx');
                $table->dropColumn('borrowing_is_archived');
            }
        });
    }
};
