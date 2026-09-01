<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reports_table')) {
            return;
        }

        if (Schema::hasColumn('reports_table', 'report_logged_by')) {
            return;
        }

        Schema::table('reports_table', function (Blueprint $table) {
            $table->bigInteger('report_logged_by')->nullable()->after('report_reporter_employee_id');
            $table->foreign('report_logged_by')
                ->references('user_id')
                ->on('users_table')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reports_table') || ! Schema::hasColumn('reports_table', 'report_logged_by')) {
            return;
        }

        Schema::table('reports_table', function (Blueprint $table) {
            $table->dropForeign(['report_logged_by']);
            $table->dropColumn('report_logged_by');
        });
    }
};
