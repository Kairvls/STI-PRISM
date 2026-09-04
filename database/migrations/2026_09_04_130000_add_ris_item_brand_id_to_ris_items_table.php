<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('requisition_issue_slip_items_table')) {
            return;
        }

        if (!Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->unsignedBigInteger('ris_item_brand_id')->nullable()->after('ris_item_name_description');

                if (Schema::hasTable('brands_table')) {
                    $table->foreign('ris_item_brand_id')
                        ->references('brand_id')
                        ->on('brands_table')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('requisition_issue_slip_items_table')) {
            return;
        }

        if (Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                try {
                    $table->dropForeign(['ris_item_brand_id']);
                } catch (\Throwable $e) {
                    // Foreign key may not exist on some environments.
                }
                $table->dropColumn('ris_item_brand_id');
            });
        }
    }
};
