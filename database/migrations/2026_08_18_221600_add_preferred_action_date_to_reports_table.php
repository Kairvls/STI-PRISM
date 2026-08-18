<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reports_table')) {
            return;
        }

        if (Schema::hasColumn('reports_table', 'report_preferred_action_date')) {
            return;
        }

        Schema::table('reports_table', function (Blueprint $table) {
            $table->date('report_preferred_action_date')
                ->nullable()
                ->after('report_urgency_level');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('reports_table')) {
            return;
        }

        if (!Schema::hasColumn('reports_table', 'report_preferred_action_date')) {
            return;
        }

        Schema::table('reports_table', function (Blueprint $table) {
            $table->dropColumn('report_preferred_action_date');
        });
    }
};
