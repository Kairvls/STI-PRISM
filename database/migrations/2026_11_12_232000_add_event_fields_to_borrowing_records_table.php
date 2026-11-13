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
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_event_name')) {
                $table->string('borrowing_event_name', 150)->nullable()->after('borrowing_purpose');
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_event_type')) {
                $table->string('borrowing_event_type', 40)->nullable()->after('borrowing_event_name');
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_event_location')) {
                $table->string('borrowing_event_location', 150)->nullable()->after('borrowing_event_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            foreach (['borrowing_event_location', 'borrowing_event_type', 'borrowing_event_name'] as $column) {
                if (Schema::hasColumn('borrowing_records_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
