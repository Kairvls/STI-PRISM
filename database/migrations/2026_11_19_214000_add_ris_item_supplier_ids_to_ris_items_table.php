<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requisition_issue_slip_items_table')) {
            return;
        }

        if (! Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_ids')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->json('ris_item_supplier_ids')->nullable()->after('ris_item_supplier_id');
            });
        }

        if (
            Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_ids')
        ) {
            DB::table('requisition_issue_slip_items_table')
                ->whereNotNull('ris_item_supplier_id')
                ->whereNull('ris_item_supplier_ids')
                ->orderBy('ris_item_id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('requisition_issue_slip_items_table')
                            ->where('ris_item_id', $row->ris_item_id)
                            ->update([
                                'ris_item_supplier_ids' => json_encode([(int) $row->ris_item_supplier_id]),
                            ]);
                    }
                }, 'ris_item_id');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('requisition_issue_slip_items_table')) {
            return;
        }

        if (Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_ids')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->dropColumn('ris_item_supplier_ids');
            });
        }
    }
};
