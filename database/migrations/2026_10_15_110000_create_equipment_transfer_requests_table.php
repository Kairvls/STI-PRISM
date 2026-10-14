<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment_transfer_requests_table')) {
            return;
        }

        Schema::create('equipment_transfer_requests_table', function (Blueprint $table) {
            $table->bigIncrements('transfer_request_id');
            $table->string('transfer_request_kind', 20)->default('single');
            $table->json('transfer_request_equipment_ids');
            $table->unsignedBigInteger('transfer_request_room_id');
            $table->string('transfer_request_destination_type', 20)->nullable();
            $table->unsignedBigInteger('transfer_request_department_id')->nullable();
            $table->unsignedBigInteger('transfer_request_custodian_id')->nullable();
            $table->text('transfer_request_remarks')->nullable();
            $table->string('transfer_request_status', 20)->default('Pending')->index('transfer_requests_status_idx');
            $table->unsignedBigInteger('transfer_request_requested_by')->nullable();
            $table->timestamp('transfer_request_requested_at')->nullable();
            $table->unsignedBigInteger('transfer_request_reviewed_by')->nullable();
            $table->timestamp('transfer_request_reviewed_at')->nullable();
            $table->text('transfer_request_review_note')->nullable();
            $table->text('transfer_request_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_transfer_requests_table');
    }
};
