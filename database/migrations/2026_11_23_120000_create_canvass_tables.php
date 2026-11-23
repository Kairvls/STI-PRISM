<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('canvass_table')) {
            Schema::create('canvass_table', function (Blueprint $table) {
                $table->bigIncrements('canvass_id');
                $table->string('canvass_number', 64)->nullable()->unique();
                $table->string('canvass_request_no', 32)->nullable()->index();
                $table->unsignedBigInteger('canvass_procurement_request_id')->nullable()->index();
                $table->text('canvass_purpose')->nullable();
                $table->string('canvass_urgency', 20)->default('Non-Urgent');
                $table->string('canvass_status', 40)->default('Draft')->index();
                $table->unsignedBigInteger('canvass_owner_user_id')->nullable()->index();
                $table->longText('canvass_purchaser_signature')->nullable();
                $table->unsignedBigInteger('canvass_submitted_by')->nullable();
                $table->dateTime('canvass_submitted_at')->nullable();
                $table->unsignedBigInteger('canvass_review_started_by')->nullable();
                $table->dateTime('canvass_review_started_at')->nullable();
                $table->unsignedBigInteger('canvass_reviewed_by')->nullable();
                $table->dateTime('canvass_reviewed_at')->nullable();
                $table->text('canvass_review_note')->nullable();
                $table->unsignedBigInteger('canvass_ris_id')->nullable()->index();
                $table->boolean('canvass_is_archived')->default(false)->index();
                $table->dateTime('canvass_archived_at')->nullable();
                $table->dateTime('canvass_created_at')->nullable();
                $table->dateTime('canvass_updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('canvass_items_table')) {
            Schema::create('canvass_items_table', function (Blueprint $table) {
                $table->bigIncrements('canvass_item_id');
                $table->unsignedBigInteger('canvass_id');
                $table->unsignedBigInteger('item_id')->nullable()->index();
                $table->string('canvass_item_name', 255);
                $table->unsignedBigInteger('uom_id')->nullable()->index();
                $table->unsignedInteger('canvass_item_quantity')->default(1);
                $table->string('canvass_item_status', 30)->default('Pending')->index();
                $table->text('canvass_item_review_note')->nullable();
                $table->unsignedBigInteger('canvass_item_selected_offer_id')->nullable()->index();
                $table->unsignedSmallInteger('canvass_item_sort_order')->default(0);
                $table->dateTime('canvass_item_created_at')->nullable();
                $table->dateTime('canvass_item_updated_at')->nullable();

                $table->foreign('canvass_id', 'canvass_items_canvass_fk')
                    ->references('canvass_id')
                    ->on('canvass_table')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('canvass_suppliers_table')) {
            Schema::create('canvass_suppliers_table', function (Blueprint $table) {
                $table->bigIncrements('canvass_supplier_id');
                $table->unsignedBigInteger('canvass_item_id');
                $table->unsignedBigInteger('supplier_id')->index();
                $table->unsignedSmallInteger('canvass_supplier_sort_order')->default(0);
                $table->dateTime('canvass_supplier_created_at')->nullable();
                $table->dateTime('canvass_supplier_updated_at')->nullable();

                $table->unique(['canvass_item_id', 'supplier_id'], 'canvass_suppliers_item_supplier_unique');
                $table->foreign('canvass_item_id', 'canvass_suppliers_item_fk')
                    ->references('canvass_item_id')
                    ->on('canvass_items_table')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('canvass_offers_table')) {
            Schema::create('canvass_offers_table', function (Blueprint $table) {
                $table->bigIncrements('canvass_offer_id');
                $table->unsignedBigInteger('canvass_supplier_id');
                $table->unsignedBigInteger('brand_id')->index();
                $table->unsignedBigInteger('offer_uom_id')->index();
                $table->decimal('offer_units_per_pack', 10, 2)->default(1.00);
                $table->decimal('offer_unit_price', 14, 2)->default(0.00);
                $table->unsignedSmallInteger('offer_sort_order')->default(0);
                $table->dateTime('offer_created_at')->nullable();
                $table->dateTime('offer_updated_at')->nullable();

                $table->unique(['canvass_supplier_id', 'brand_id', 'offer_uom_id'], 'canvass_offers_supplier_brand_uom_unique');
                $table->foreign('canvass_supplier_id', 'canvass_offers_supplier_fk')
                    ->references('canvass_supplier_id')
                    ->on('canvass_suppliers_table')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('canvass_attachments_table')) {
            Schema::create('canvass_attachments_table', function (Blueprint $table) {
                $table->bigIncrements('canvass_attachment_id');
                $table->unsignedBigInteger('canvass_id');
                $table->string('canvass_attachment_original_name', 255);
                $table->string('canvass_attachment_stored_path', 500);
                $table->string('canvass_attachment_mime_type', 120)->nullable();
                $table->unsignedBigInteger('canvass_attachment_size')->default(0);
                $table->unsignedBigInteger('canvass_attachment_uploaded_by')->nullable();
                $table->dateTime('canvass_attachment_created_at')->nullable();

                $table->foreign('canvass_id', 'canvass_attachments_canvass_fk')
                    ->references('canvass_id')
                    ->on('canvass_table')
                    ->cascadeOnDelete();
            });
        }

        if (
            Schema::hasTable('requisition_issue_slip_table')
            && ! Schema::hasColumn('requisition_issue_slip_table', 'ris_canvass_id')
        ) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->unsignedBigInteger('ris_canvass_id')->nullable()->after('ris_procurement_request_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_canvass_id')
        ) {
            Schema::table('requisition_issue_slip_table', function (Blueprint $table) {
                $table->dropColumn('ris_canvass_id');
            });
        }

        Schema::dropIfExists('canvass_attachments_table');
        Schema::dropIfExists('canvass_offers_table');
        Schema::dropIfExists('canvass_suppliers_table');
        Schema::dropIfExists('canvass_items_table');
        Schema::dropIfExists('canvass_table');
    }
};
