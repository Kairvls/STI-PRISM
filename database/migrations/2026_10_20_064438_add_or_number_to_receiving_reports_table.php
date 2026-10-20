<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_reports_table', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_reports_table', 'receiving_report_or_number')) {
                $table->string('receiving_report_or_number', 100)->nullable()->after('receiving_report_invoice_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receiving_reports_table', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_or_number')) {
                $table->dropColumn('receiving_report_or_number');
            }
        });
    }
};
