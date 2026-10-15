<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment_replacement_logs_table')) {
            return;
        }

        Schema::create('equipment_replacement_logs_table', function (Blueprint $table) {
            $table->bigIncrements('replacement_log_id');
            $table->unsignedBigInteger('replacement_log_report_id')->nullable()->index('replacement_logs_report_idx');
            $table->unsignedBigInteger('replacement_log_report_item_id')->nullable();
            $table->unsignedBigInteger('replacement_log_replaced_equipment_id')->nullable()->index('replacement_logs_replaced_idx');
            $table->string('replacement_log_replaced_label', 255)->nullable();
            $table->string('replacement_log_replaced_asset_tag', 120)->nullable();
            $table->unsignedBigInteger('replacement_log_replacement_equipment_id')->nullable()->index('replacement_logs_replacement_idx');
            $table->string('replacement_log_replacement_label', 255)->nullable();
            $table->string('replacement_log_replacement_asset_tag', 120)->nullable();
            $table->string('replacement_log_fulfillment_type', 30)->index('replacement_logs_type_idx');
            $table->boolean('replacement_log_stock_was_available')->default(false);
            $table->unsignedBigInteger('replacement_log_procurement_request_id')->nullable();
            $table->unsignedBigInteger('replacement_log_from_room_id')->nullable();
            $table->unsignedBigInteger('replacement_log_to_room_id')->nullable();
            $table->text('replacement_log_notes')->nullable();
            $table->unsignedBigInteger('replacement_log_performed_by')->nullable();
            $table->timestamp('replacement_log_performed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_replacement_logs_table');
    }
};
