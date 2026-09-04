<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_reason')) {
                $table->text('ris_direct_approval_reason')->nullable()->after('ris_rejection_reason');
            }
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_proof_path')) {
                $table->string('ris_direct_approval_proof_path', 500)->nullable()->after('ris_direct_approval_reason');
            }
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_proof_name')) {
                $table->string('ris_direct_approval_proof_name', 255)->nullable()->after('ris_direct_approval_proof_path');
            }
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_at')) {
                $table->timestamp('ris_direct_approval_at')->nullable()->after('ris_direct_approval_proof_name');
            }
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_by')) {
                $table->unsignedBigInteger('ris_direct_approval_by')->nullable()->after('ris_direct_approval_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            $columns = [
                'ris_direct_approval_reason',
                'ris_direct_approval_proof_path',
                'ris_direct_approval_proof_name',
                'ris_direct_approval_at',
                'ris_direct_approval_by',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('requisition_issue_slip_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
