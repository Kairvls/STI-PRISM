<?php

use App\Support\ReplacementRequestBasket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_requests_table')) {
            return;
        }

        if (! Schema::hasTable('procurement_request_items_table')) {
            Schema::create('procurement_request_items_table', function (Blueprint $table) {
                $table->bigIncrements('procurement_request_item_id');
                $table->unsignedBigInteger('procurement_request_id');
                $table->unsignedBigInteger('report_id');
                $table->unsignedBigInteger('report_item_id')->nullable();
                $table->unsignedBigInteger('equipment_id')->nullable();
                $table->string('unlisted_equipment_name', 255)->nullable();
                $table->dateTime('created_at')->nullable();

                $table->index('procurement_request_id', 'pri_request_id_idx');
                $table->index('report_id', 'pri_report_id_idx');
                $table->unique('report_item_id', 'pri_report_item_unique');
            });
        }

        ReplacementRequestBasket::backfillExisting();
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_request_items_table');
    }
};
