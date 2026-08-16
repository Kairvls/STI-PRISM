<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('requisition_issue_slip_items_table')
            || !Schema::hasTable('suppliers_table')
        ) {
            return;
        }

        if (!Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->bigInteger('ris_item_supplier_id')->nullable()->after('ris_item_name_description');
            });
        }

        Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
            $table->foreign('ris_item_supplier_id', 'ris_items_supplier_fk')
                ->references('supplier_id')
                ->on('suppliers_table')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('requisition_issue_slip_items_table')
            || !Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')
        ) {
            return;
        }

        Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
            $table->dropForeign('ris_items_supplier_fk');
            $table->dropColumn('ris_item_supplier_id');
        });
    }
};
