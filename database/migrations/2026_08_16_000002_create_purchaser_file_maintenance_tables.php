<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_table')) {
            Schema::create('brands_table', function (Blueprint $table) {
                $table->bigIncrements('brand_id');
                $table->string('brand_name', 150);
                $table->string('brand_status', 20)->default('Active');
                $table->dateTime('brand_created_at')->nullable();
                $table->dateTime('brand_updated_at')->nullable();
                $table->unique('brand_name');
            });
        }

        if (!Schema::hasTable('uom_table')) {
            Schema::create('uom_table', function (Blueprint $table) {
                $table->bigIncrements('uom_id');
                $table->string('uom_name', 50);
                $table->string('uom_description', 255)->nullable();
                $table->dateTime('uom_created_at')->nullable();
                $table->dateTime('uom_updated_at')->nullable();
                $table->unique('uom_name');
            });
        }

        if (!Schema::hasTable('item_categories_table')) {
            Schema::create('item_categories_table', function (Blueprint $table) {
                $table->bigIncrements('item_category_id');
                $table->string('item_category_name', 150);
                $table->string('item_category_description', 255)->nullable();
                $table->string('item_category_status', 20)->default('Active');
                $table->dateTime('item_category_created_at')->nullable();
                $table->dateTime('item_category_updated_at')->nullable();
                $table->unique('item_category_name');
            });
        }

        if (!Schema::hasTable('item_subcategories_table')) {
            Schema::create('item_subcategories_table', function (Blueprint $table) {
                $table->bigIncrements('item_subcategory_id');
                $table->unsignedBigInteger('item_category_id');
                $table->string('item_subcategory_name', 150);
                $table->string('item_subcategory_description', 255)->nullable();
                $table->string('item_subcategory_status', 20)->default('Active');
                $table->dateTime('item_subcategory_created_at')->nullable();
                $table->dateTime('item_subcategory_updated_at')->nullable();
                $table->unique(['item_category_id', 'item_subcategory_name'], 'item_subcategories_category_name_unique');
                $table->foreign('item_category_id', 'item_subcategories_category_fk')
                    ->references('item_category_id')
                    ->on('item_categories_table')
                    ->restrictOnDelete();
            });
        }

        if (
            Schema::hasTable('requisition_issue_slip_items_table')
            && Schema::hasTable('uom_table')
            && !Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
        ) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->unsignedBigInteger('ris_item_uom_id')->nullable()->after('ris_item_name_description');
                $table->foreign('ris_item_uom_id', 'ris_items_uom_fk')
                    ->references('uom_id')
                    ->on('uom_table')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('requisition_issue_slip_items_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
        ) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->dropForeign('ris_items_uom_fk');
                $table->dropColumn('ris_item_uom_id');
            });
        }

        Schema::dropIfExists('item_subcategories_table');
        Schema::dropIfExists('item_categories_table');
        Schema::dropIfExists('uom_table');
        Schema::dropIfExists('brands_table');
    }
};
