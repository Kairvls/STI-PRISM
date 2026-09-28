<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_revision_notes_table')) {
            return;
        }

        // One row per "return for revision" on ATP / PO / RFC / LIQ / RR, with optional proof images.
        Schema::create('document_revision_notes_table', function (Blueprint $table) {
            $table->bigIncrements('document_revision_id');
            $table->string('document_type', 10);
            $table->unsignedBigInteger('document_id');
            $table->string('revision_kind', 30)->default('revision');
            $table->text('revision_remarks');
            $table->text('revision_images')->nullable();
            $table->unsignedBigInteger('revision_requested_by')->nullable();
            $table->dateTime('revision_created_at')->useCurrent();

            $table->index(['document_type', 'document_id', 'revision_kind'], 'doc_revision_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_revision_notes_table');
    }
};
