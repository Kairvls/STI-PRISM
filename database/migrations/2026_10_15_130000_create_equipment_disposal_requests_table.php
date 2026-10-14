<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment_disposal_requests_table')) {
            return;
        }

        Schema::create('equipment_disposal_requests_table', function (Blueprint $table) {
            $table->bigIncrements('disposal_request_id');
            $table->json('disposal_request_equipment_ids');
            $table->text('disposal_request_reason');
            $table->string('disposal_request_method', 80)->nullable();
            $table->string('disposal_request_location', 255)->nullable();
            $table->decimal('disposal_request_residual_value', 12, 2)->nullable();
            $table->string('disposal_request_status', 20)->default('Pending')->index('disposal_requests_status_idx');
            $table->unsignedBigInteger('disposal_request_requested_by')->nullable();
            $table->timestamp('disposal_request_requested_at')->nullable();
            $table->unsignedBigInteger('disposal_request_reviewed_by')->nullable();
            $table->timestamp('disposal_request_reviewed_at')->nullable();
            $table->text('disposal_request_review_note')->nullable();
            $table->text('disposal_request_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_disposal_requests_table');
    }
};
