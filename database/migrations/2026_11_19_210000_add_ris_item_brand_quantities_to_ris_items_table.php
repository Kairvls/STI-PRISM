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

        if (! Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_quantities')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $after = Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_ids')
                    ? 'ris_item_brand_ids'
                    : 'ris_item_brand_id';
                $table->json('ris_item_brand_quantities')->nullable()->after($after);
            });
        }

        // Backfill: put full requested qty on the primary brand.
        if (
            Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_quantities')
        ) {
            DB::table('requisition_issue_slip_items_table')
                ->whereNotNull('ris_item_brand_id')
                ->whereNull('ris_item_brand_quantities')
                ->orderBy('ris_item_id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $qty = max(1, (int) ($row->ris_quantity_requested ?? 1));
                        DB::table('requisition_issue_slip_items_table')
                            ->where('ris_item_id', $row->ris_item_id)
                            ->update([
                                'ris_item_brand_quantities' => json_encode([
                                    (string) $row->ris_item_brand_id => $qty,
                                ]),
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

        if (Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_quantities')) {
            Schema::table('requisition_issue_slip_items_table', function (Blueprint $table) {
                $table->dropColumn('ris_item_brand_quantities');
            });
        }
    }
};
