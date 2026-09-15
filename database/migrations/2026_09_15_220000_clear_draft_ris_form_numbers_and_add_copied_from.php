<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return;
        }

        if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_copied_from_id')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->unsignedBigInteger('ris_copied_from_id')->nullable()->after('ris_id');
            });
        }

        // Drafts should not hold a RIS No. — numbers are assigned only on submit.
        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Draft')
            ->whereNotNull('ris_form_number')
            ->update([
                'ris_form_number' => null,
                'ris_updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_copied_from_id')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->dropColumn('ris_copied_from_id');
            });
        }
    }
};
