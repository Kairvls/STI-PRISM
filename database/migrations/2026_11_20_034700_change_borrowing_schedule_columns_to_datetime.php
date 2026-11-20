<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        // Preserve existing calendar dates as midnight local datetimes.
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_date')) {
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->dateTime('borrowing_date')->nullable()->change();
            });
        }

        if (Schema::hasColumn('borrowing_records_table', 'borrowing_expected_return_date')) {
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->dateTime('borrowing_expected_return_date')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        if (Schema::hasColumn('borrowing_records_table', 'borrowing_date')) {
            // Strip time when rolling back to date.
            DB::statement('UPDATE borrowing_records_table SET borrowing_date = DATE(borrowing_date) WHERE borrowing_date IS NOT NULL');
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->date('borrowing_date')->nullable()->change();
            });
        }

        if (Schema::hasColumn('borrowing_records_table', 'borrowing_expected_return_date')) {
            DB::statement('UPDATE borrowing_records_table SET borrowing_expected_return_date = DATE(borrowing_expected_return_date) WHERE borrowing_expected_return_date IS NOT NULL');
            Schema::table('borrowing_records_table', function (Blueprint $table) {
                $table->date('borrowing_expected_return_date')->nullable()->change();
            });
        }
    }
};
