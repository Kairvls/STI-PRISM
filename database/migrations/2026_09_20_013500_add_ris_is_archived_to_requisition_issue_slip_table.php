<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->boolean('ris_is_archived')
                    ->default(false)
                    ->after('ris_updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->dropColumn('ris_is_archived');
            });
        }
    }
};
