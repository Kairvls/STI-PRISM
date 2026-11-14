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

        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_president_id')) {
                $after = Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_reviewer_id')
                    ? 'ris_assigned_reviewer_id'
                    : null;
                if ($after) {
                    $table->unsignedBigInteger('ris_assigned_president_id')->nullable()->after($after);
                } else {
                    $table->unsignedBigInteger('ris_assigned_president_id')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return;
        }

        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_president_id')) {
                $table->dropColumn('ris_assigned_president_id');
            }
        });
    }
};
