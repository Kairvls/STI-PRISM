<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('room_inspection_schedules_table')) {
            Schema::create('room_inspection_schedules_table', function (Blueprint $table) {
                $table->bigIncrements('room_inspection_schedule_id');
                $table->unsignedBigInteger('room_inspection_schedule_room_id')->index('room_inspection_schedules_room_idx');
                $table->string('room_inspection_schedule_title');
                $table->text('room_inspection_schedule_notes')->nullable();
                $table->json('room_inspection_schedule_checklist')->nullable();
                $table->string('room_inspection_schedule_frequency', 30);
                $table->date('room_inspection_schedule_next_date')->nullable();
                $table->timestamp('room_inspection_schedule_last_date')->nullable();
                $table->string('room_inspection_schedule_status', 20)->default('Active')->index('room_inspection_schedules_status_idx');
                $table->unsignedBigInteger('room_inspection_schedule_created_by')->nullable();
                $table->timestamp('room_inspection_schedule_created_at')->nullable();
                $table->timestamp('room_inspection_schedule_updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('room_inspection_logs_table')) {
            Schema::create('room_inspection_logs_table', function (Blueprint $table) {
                $table->bigIncrements('room_inspection_log_id');
                $table->unsignedBigInteger('room_inspection_log_schedule_id')->nullable()->index('room_inspection_logs_schedule_idx');
                $table->unsignedBigInteger('room_inspection_log_room_id')->index('room_inspection_logs_room_idx');
                $table->string('room_inspection_log_title')->nullable();
                $table->string('room_inspection_log_result', 30);
                $table->json('room_inspection_log_checklist')->nullable();
                $table->unsignedInteger('room_inspection_log_issue_count')->default(0);
                $table->text('room_inspection_log_findings')->nullable();
                $table->text('room_inspection_log_action_taken')->nullable();
                $table->string('room_inspection_log_proof_image')->nullable();
                $table->date('room_inspection_log_scheduled_for')->nullable();
                $table->unsignedBigInteger('room_inspection_log_inspected_by')->nullable();
                $table->timestamp('room_inspection_log_inspected_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_inspection_logs_table');
        Schema::dropIfExists('room_inspection_schedules_table');
    }
};
