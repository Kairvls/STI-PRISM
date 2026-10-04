<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reports_table') && !Schema::hasColumn('reports_table', 'report_severity')) {
            Schema::table('reports_table', function (Blueprint $table) {
                $table->string('report_severity', 10)->nullable()->after('report_urgency_level');
                $table->string('report_severity_reason', 500)->nullable()->after('report_severity');
                $table->boolean('report_severity_is_manual')->default(false)->after('report_severity_reason');
                $table->boolean('report_safety_hazard')->default(false)->after('report_severity_is_manual');
                $table->index('report_severity');
            });

            // Reports filed before auto-detection keep the reporter's original choice.
            DB::table('reports_table')
                ->where('report_urgency_level', 'Urgent')
                ->update([
                    'report_severity' => 'High',
                    'report_severity_reason' => 'Reporter marked it Urgent (filed before automatic priority).',
                ]);

            DB::table('reports_table')
                ->whereNull('report_severity')
                ->update([
                    'report_severity' => 'Medium',
                    'report_severity_reason' => 'Reporter marked it Non-Urgent (filed before automatic priority).',
                ]);
        }

        if (Schema::hasTable('issue_templates_table') && !Schema::hasColumn('issue_templates_table', 'issue_template_severity')) {
            // NULL means "decide from the issue name"; maintenance can pin a level per issue.
            Schema::table('issue_templates_table', function (Blueprint $table) {
                $table->string('issue_template_severity', 10)->nullable()->after('issue_template_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reports_table') && Schema::hasColumn('reports_table', 'report_severity')) {
            Schema::table('reports_table', function (Blueprint $table) {
                $table->dropIndex(['report_severity']);
                $table->dropColumn([
                    'report_severity',
                    'report_severity_reason',
                    'report_severity_is_manual',
                    'report_safety_hazard',
                ]);
            });
        }

        if (Schema::hasTable('issue_templates_table') && Schema::hasColumn('issue_templates_table', 'issue_template_severity')) {
            Schema::table('issue_templates_table', function (Blueprint $table) {
                $table->dropColumn('issue_template_severity');
            });
        }
    }
};
