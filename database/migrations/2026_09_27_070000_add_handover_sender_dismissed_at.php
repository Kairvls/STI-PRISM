<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_handovers_table') && ! Schema::hasColumn('document_handovers_table', 'handover_sender_dismissed_at')) {
            Schema::table('document_handovers_table', function (Blueprint $table) {
                $table->timestamp('handover_sender_dismissed_at')->nullable()->after('handover_responded_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_handovers_table') && Schema::hasColumn('document_handovers_table', 'handover_sender_dismissed_at')) {
            Schema::table('document_handovers_table', function (Blueprint $table) {
                $table->dropColumn('handover_sender_dismissed_at');
            });
        }
    }
};
