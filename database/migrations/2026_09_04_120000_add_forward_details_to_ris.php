<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_details')) {
                $table->text('ris_forward_details')->nullable()->after('ris_direct_approval_by');
            }
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path')) {
                $table->string('ris_forward_attachment_path', 500)->nullable()->after('ris_forward_details');
            }
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_name')) {
                $table->string('ris_forward_attachment_name', 255)->nullable()->after('ris_forward_attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            foreach ([
                'ris_forward_details',
                'ris_forward_attachment_path',
                'ris_forward_attachment_name',
            ] as $column) {
                if (Schema::hasColumn('requisition_issue_slip_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
