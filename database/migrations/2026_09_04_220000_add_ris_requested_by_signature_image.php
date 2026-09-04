<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_requested_by_signature_image')) {
                $table->longText('ris_requested_by_signature_image')
                    ->nullable()
                    ->after('ris_requested_by_signature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_requested_by_signature_image')) {
                $table->dropColumn('ris_requested_by_signature_image');
            }
        });
    }
};
