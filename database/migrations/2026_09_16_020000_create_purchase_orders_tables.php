<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders_table')) {
            Schema::create('purchase_orders_table', function (Blueprint $table) {
                $table->bigIncrements('purchase_order_id');
                $table->string('purchase_order_number', 50)->nullable();
                $table->string('purchase_order_status', 30)->default('Draft');
                $table->unsignedBigInteger('purchase_order_created_by')->nullable();
                $table->unsignedBigInteger('purchase_order_submitted_by')->nullable();
                $table->dateTime('purchase_order_submitted_at')->nullable();
                $table->unsignedBigInteger('purchase_order_approved_by')->nullable();
                $table->dateTime('purchase_order_approved_at')->nullable();
                $table->text('purchase_order_revision_reason')->nullable();
                $table->unsignedTinyInteger('purchase_order_is_archived')->default(0);
                $table->dateTime('purchase_order_created_at')->nullable();
                $table->dateTime('purchase_order_updated_at')->nullable();

                $table->unique('purchase_order_number', 'po_number_unique');
                $table->index('purchase_order_status', 'po_status_idx');
                $table->index('purchase_order_created_by', 'po_created_by_idx');
            });
        }

        if (! Schema::hasTable('purchase_order_atps_table')) {
            Schema::create('purchase_order_atps_table', function (Blueprint $table) {
                $table->bigIncrements('purchase_order_atp_id');
                $table->unsignedBigInteger('purchase_order_id');
                $table->unsignedBigInteger('authority_purchase_id');
                $table->dateTime('created_at')->nullable();

                $table->index('purchase_order_id', 'po_atp_po_id_idx');
                $table->unique('authority_purchase_id', 'po_atp_atp_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_atps_table');
        Schema::dropIfExists('purchase_orders_table');
    }
};
