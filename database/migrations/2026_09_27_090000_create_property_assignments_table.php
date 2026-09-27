<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('property_assignments_table')) {
            return;
        }

        Schema::create('property_assignments_table', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->unsignedBigInteger('assignment_equipment_id');
            $table->unsignedBigInteger('assignment_reporter_id');
            $table->unsignedBigInteger('assignment_room_id')->nullable();
            $table->unsignedBigInteger('assignment_slot_id')->nullable();
            $table->string('assignment_status', 32)->default('Active');
            $table->string('assignment_document_no', 64)->nullable();
            $table->text('assignment_notes')->nullable();
            $table->unsignedBigInteger('assignment_issued_by')->nullable();
            $table->timestamp('assignment_issued_at')->useCurrent();
            $table->timestamp('assignment_acknowledged_at')->nullable();
            $table->string('assignment_signature_path', 255)->nullable();
            $table->unsignedBigInteger('assignment_returned_by')->nullable();
            $table->timestamp('assignment_returned_at')->nullable();
            $table->string('assignment_return_condition', 64)->nullable();
            $table->text('assignment_return_notes')->nullable();
            $table->timestamp('assignment_created_at')->useCurrent();
            $table->timestamp('assignment_updated_at')->nullable();

            $table->index(['assignment_equipment_id', 'assignment_status'], 'pa_equipment_status_idx');
            $table->index(['assignment_reporter_id', 'assignment_status'], 'pa_reporter_status_idx');
            $table->index('assignment_room_id', 'pa_room_idx');
            $table->index('assignment_slot_id', 'pa_slot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_assignments_table');
    }
};
