<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('canvass_table')
            && ! Schema::hasColumn('canvass_table', 'canvass_admin_signature')
        ) {
            Schema::table('canvass_table', function (Blueprint $table) {
                $table->longText('canvass_admin_signature')->nullable()->after('canvass_purchaser_signature');
            });
        }

        if (! Schema::hasTable('canvass_item_allocations_table')) {
            Schema::create('canvass_item_allocations_table', function (Blueprint $table) {
                $table->bigIncrements('allocation_id');
                $table->unsignedBigInteger('canvass_item_id')->index();
                $table->unsignedBigInteger('canvass_offer_id')->index();
                $table->unsignedInteger('approved_offer_units')->default(1);
                $table->decimal('approved_requested_units', 10, 2)->default(1.00);
                $table->decimal('approved_unit_cost', 14, 2)->default(0.00);
                $table->decimal('approved_amount', 14, 2)->default(0.00);
                $table->dateTime('allocation_created_at')->nullable();
                $table->dateTime('allocation_updated_at')->nullable();

                $table->foreign('canvass_item_id', 'canvass_alloc_item_fk')
                    ->references('canvass_item_id')
                    ->on('canvass_items_table')
                    ->cascadeOnDelete();

                $table->foreign('canvass_offer_id', 'canvass_alloc_offer_fk')
                    ->references('canvass_offer_id')
                    ->on('canvass_offers_table')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canvass_item_allocations_table');

        if (
            Schema::hasTable('canvass_table')
            && Schema::hasColumn('canvass_table', 'canvass_admin_signature')
        ) {
            Schema::table('canvass_table', function (Blueprint $table) {
                $table->dropColumn('canvass_admin_signature');
            });
        }
    }
};
