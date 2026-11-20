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

        if (! Schema::hasColumn('borrowing_records_table', 'borrowing_overdue_notified_at')) {
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $after = Schema::hasColumn('borrowing_records_table', 'borrowing_decided_by')
                    ? 'borrowing_decided_by'
                    : (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_at')
                        ? 'borrowing_decided_at'
                        : 'borrowing_status');
                $table->timestamp('borrowing_overdue_notified_at')->nullable()->after($after);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        if (Schema::hasColumn('borrowing_records_table', 'borrowing_overdue_notified_at')) {
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->dropColumn('borrowing_overdue_notified_at');
            });
        }
    }
};
