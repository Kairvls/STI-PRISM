<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('request_check_atps_table')) {
            Schema::create('request_check_atps_table', function (Blueprint $table) {
                $table->bigIncrements('request_check_atp_id');
                $table->unsignedBigInteger('request_check_id');
                $table->unsignedBigInteger('authority_purchase_id');
                $table->dateTime('created_at')->nullable();

                $table->unique(['request_check_id', 'authority_purchase_id'], 'rfc_atp_pair_unique');
                $table->index('authority_purchase_id', 'rfc_atp_atp_idx');
            });
        }

        if (
            Schema::hasTable('request_check_table')
            && ! Schema::hasColumn('request_check_table', 'request_check_purchase_order_id')
        ) {
            Schema::table('request_check_table', function (Blueprint $table) {
                $table->unsignedBigInteger('request_check_purchase_order_id')->nullable()->index('rfc_po_idx');
            });
        }

        if (Schema::hasTable('request_check_table') && Schema::hasTable('request_check_atps_table')) {
            $now = now();
            DB::table('request_check_table')
                ->whereNotNull('request_check_authority_purchase_id')
                ->orderBy('request_check_id')
                ->select('request_check_id', 'request_check_authority_purchase_id')
                ->chunk(500, function ($rows) use ($now) {
                    $insert = [];
                    foreach ($rows as $row) {
                        $insert[] = [
                            'request_check_id' => (int) $row->request_check_id,
                            'authority_purchase_id' => (int) $row->request_check_authority_purchase_id,
                            'created_at' => $now,
                        ];
                    }
                    if ($insert !== []) {
                        DB::table('request_check_atps_table')->insertOrIgnore($insert);
                    }
                });
        }

        // Links to cancelled POs are kept as history, so an ATP may appear on more than one PO.
        if (Schema::hasTable('purchase_order_atps_table') && $this->hasIndex('purchase_order_atps_table', 'po_atp_atp_unique')) {
            Schema::table('purchase_order_atps_table', function (Blueprint $table) {
                $table->dropUnique('po_atp_atp_unique');
                $table->index('authority_purchase_id', 'po_atp_atp_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('request_check_atps_table');

        if (
            Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_purchase_order_id')
        ) {
            Schema::table('request_check_table', function (Blueprint $table) {
                $table->dropIndex('rfc_po_idx');
                $table->dropColumn('request_check_purchase_order_id');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            return DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]) !== [];
        } catch (\Throwable $e) {
            return false;
        }
    }
};
