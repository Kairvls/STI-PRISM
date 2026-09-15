<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return;
        }

        if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_urgency')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_type')) {
                    $table->string('ris_urgency', 20)
                        ->default('Non-Urgent')
                        ->after('ris_request_type');
                } else {
                    $table->string('ris_urgency', 20)->default('Non-Urgent');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_urgency')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->dropColumn('ris_urgency');
            });
        }
    }
};
