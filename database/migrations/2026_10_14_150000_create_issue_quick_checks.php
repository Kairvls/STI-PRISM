<?php

use App\Support\IssueChecks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('issue_checks_table')) {
            Schema::create('issue_checks_table', function (Blueprint $table) {
                $table->bigIncrements('issue_check_id');
                $table->unsignedBigInteger('issue_template_id');
                $table->unsignedSmallInteger('issue_check_order')->default(0);
                // troubleshoot: "That fixed it" / "Still not working"; question: Yes / No.
                $table->string('issue_check_kind', 16)->default('troubleshoot');
                $table->string('issue_check_question', 255);
                $table->string('issue_check_hint', 255)->nullable();
                $table->string('issue_check_severity_if_yes', 10)->nullable();
                $table->string('issue_check_severity_if_no', 10)->nullable();
                $table->timestamp('issue_check_created_at')->nullable();

                $table->index(['issue_template_id', 'issue_check_order']);
            });
        }

        if (! Schema::hasTable('issue_self_fixes_table')) {
            Schema::create('issue_self_fixes_table', function (Blueprint $table) {
                $table->bigIncrements('issue_self_fix_id');
                $table->unsignedBigInteger('issue_self_fix_reporter_id')->nullable();
                $table->string('issue_self_fix_employee_id', 20)->nullable();
                $table->unsignedBigInteger('issue_self_fix_equipment_id')->nullable();
                $table->unsignedBigInteger('issue_self_fix_room_id')->nullable();
                $table->unsignedBigInteger('issue_self_fix_template_id')->nullable();
                $table->string('issue_self_fix_issue', 255)->nullable();
                $table->unsignedBigInteger('issue_self_fix_check_id')->nullable();
                $table->string('issue_self_fix_check_question', 255)->nullable();
                $table->string('issue_self_fix_source', 10)->default('web');
                $table->timestamp('issue_self_fix_created_at')->nullable();

                $table->index('issue_self_fix_created_at');
                $table->index('issue_self_fix_equipment_id');
                $table->index('issue_self_fix_template_id');
            });
        }

        if (Schema::hasTable('report_items_table') && ! Schema::hasColumn('report_items_table', 'report_item_checks')) {
            Schema::table('report_items_table', function (Blueprint $table) {
                $table->json('report_item_checks')->nullable()->after('report_item_problem_description');
            });
        }

        if (Schema::hasTable('issue_templates_table')) {
            IssueChecks::seedDefaults();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('report_items_table') && Schema::hasColumn('report_items_table', 'report_item_checks')) {
            Schema::table('report_items_table', function (Blueprint $table) {
                $table->dropColumn('report_item_checks');
            });
        }

        Schema::dropIfExists('issue_self_fixes_table');
        Schema::dropIfExists('issue_checks_table');
    }
};
