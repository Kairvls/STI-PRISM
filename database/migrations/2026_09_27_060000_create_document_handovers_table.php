<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_handovers_table')) {
            return;
        }

        Schema::create('document_handovers_table', function (Blueprint $table) {
            $table->id('handover_id');
            $table->string('handover_document_type', 10);
            $table->unsignedBigInteger('handover_document_id');
            $table->unsignedBigInteger('handover_from_user_id');
            $table->unsignedBigInteger('handover_to_user_id');
            $table->string('handover_status', 20)->default('Pending');
            $table->text('handover_note')->nullable();
            $table->text('handover_response_note')->nullable();
            $table->timestamp('handover_created_at')->nullable();
            $table->timestamp('handover_responded_at')->nullable();

            $table->index(['handover_document_type', 'handover_document_id', 'handover_status'], 'handover_doc_status_idx');
            $table->index(['handover_to_user_id', 'handover_status'], 'handover_to_status_idx');
            $table->index(['handover_from_user_id', 'handover_status'], 'handover_from_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_handovers_table');
    }
};
