<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        // Expand status enum for online request workflow.
        DB::statement("ALTER TABLE borrowing_records_table MODIFY borrowing_status ENUM('Pending','Borrowed','Returned','Overdue','Denied') NOT NULL DEFAULT 'Borrowed'");

        if (! Schema::hasColumn('borrowing_records_table', 'borrowing_rejection_reason')) {
            Schema::table('borrowing_records_table', function ($table) {
                $table->string('borrowing_rejection_reason', 500)->nullable()->after('borrowing_remarks');
            });
        }

        if (! Schema::hasColumn('borrowing_records_table', 'borrowing_decided_at')) {
            Schema::table('borrowing_records_table', function ($table) {
                $table->timestamp('borrowing_decided_at')->nullable()->after('borrowing_rejection_reason');
            });
        }

        if (! Schema::hasColumn('borrowing_records_table', 'borrowing_decided_by')) {
            Schema::table('borrowing_records_table', function ($table) {
                $table->unsignedBigInteger('borrowing_decided_by')->nullable()->after('borrowing_decided_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        // Move pending/denied back so the narrower enum can apply.
        DB::table('borrowing_records_table')
            ->whereIn('borrowing_status', ['Pending', 'Denied'])
            ->update(['borrowing_status' => 'Returned']);

        DB::statement("ALTER TABLE borrowing_records_table MODIFY borrowing_status ENUM('Borrowed','Returned','Overdue') NOT NULL DEFAULT 'Borrowed'");

        Schema::table('borrowing_records_table', function ($table) {
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_by')) {
                $table->dropColumn('borrowing_decided_by');
            }
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_decided_at')) {
                $table->dropColumn('borrowing_decided_at');
            }
            if (Schema::hasColumn('borrowing_records_table', 'borrowing_rejection_reason')) {
                $table->dropColumn('borrowing_rejection_reason');
            }
        });
    }
};
