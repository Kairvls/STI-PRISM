<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('back_orders_table')) {
            // One row per RR line with missing or damaged units; resolved by a replacement row on the same RR.
            Schema::create('back_orders_table', function (Blueprint $table) {
                $table->bigIncrements('back_order_id');
                $table->unsignedBigInteger('back_order_receiving_report_id');
                $table->unsignedBigInteger('back_order_receiving_report_item_id')->nullable();
                $table->unsignedBigInteger('back_order_root_receiving_report_id');
                $table->unsignedBigInteger('back_order_atp_id')->nullable();
                $table->unsignedBigInteger('back_order_request_check_id')->nullable();
                $table->string('back_order_payment_path', 30)->nullable();
                $table->unsignedBigInteger('back_order_supplier_id')->nullable();
                $table->string('back_order_supplier_name')->nullable();
                $table->string('back_order_article', 500);
                $table->string('back_order_unit', 50)->nullable();
                $table->decimal('back_order_unit_price', 15, 2)->default(0);
                $table->unsignedInteger('back_order_quantity');
                $table->string('back_order_type', 20)->default('short');
                $table->string('back_order_reason', 30)->nullable();
                $table->text('back_order_remarks')->nullable();
                $table->text('back_order_images')->nullable();
                $table->string('back_order_status', 30)->default('open');
                $table->unsignedBigInteger('back_order_replacement_supplier_id')->nullable();
                $table->string('back_order_replacement_supplier_name')->nullable();
                $table->decimal('back_order_refund_amount', 15, 2)->nullable();
                $table->string('back_order_refund_reference')->nullable();
                $table->text('back_order_refund_images')->nullable();
                $table->unsignedBigInteger('back_order_updated_by')->nullable();
                $table->dateTime('back_order_created_at')->useCurrent();
                $table->dateTime('back_order_updated_at')->nullable();
                $table->dateTime('back_order_resolved_at')->nullable();

                $table->index('back_order_receiving_report_id', 'bo_rr_idx');
                $table->index('back_order_root_receiving_report_id', 'bo_root_rr_idx');
                $table->index('back_order_status', 'bo_status_idx');
            });
        }

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_damaged_qty')) {
                $table->unsignedInteger('receiving_report_item_damaged_qty')->default(0);
            }
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_damage_remarks')) {
                $table->string('receiving_report_item_damage_remarks', 500)->nullable();
            }
            if (! Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_back_order_id')) {
                $table->unsignedBigInteger('receiving_report_item_back_order_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('back_orders_table');

        Schema::table('receiving_report_items_table', function (Blueprint $table) {
            foreach (['receiving_report_item_damaged_qty', 'receiving_report_item_damage_remarks', 'receiving_report_item_back_order_id'] as $column) {
                if (Schema::hasColumn('receiving_report_items_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
