<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_stock_movements')) {
            Schema::create('equipment_stock_movements', function (Blueprint $table) {
                $table->bigIncrements('movement_id');
                $table->bigInteger('equipment_id');
                $table->string('movement_type', 40);
                $table->integer('quantity_delta');
                $table->bigInteger('room_id')->nullable();
                $table->string('reference_type', 80)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->string('remarks', 1000)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['equipment_id', 'created_at'], 'eq_stock_movements_equipment_idx');
                $table->index(['reference_type', 'reference_id'], 'eq_stock_movements_reference_idx');
            });
        }

        if (
            Schema::hasTable('equipment_categories_table')
            && ! Schema::hasColumn('equipment_categories_table', 'equipment_category_tracking_policy')
        ) {
            Schema::table('equipment_categories_table', function (Blueprint $table) {
                $table->string('equipment_category_tracking_policy', 20)->nullable()->after('equipment_category_name');
            });
        }

        if (
            Schema::hasTable('disposal_records_table')
            && ! Schema::hasColumn('disposal_records_table', 'disposal_condition_before')
        ) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                $table->string('disposal_condition_before', 50)->nullable()->after('disposal_reason');
            });
        }

        if (Schema::hasTable('equipment_table') && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE equipment_table MODIFY equipment_inventory_status ENUM('Active','Borrowed','Under Maintenance','For Replacement','Replaced','Disposed','Pending Disposal') DEFAULT 'Active'");
        }

        $this->markConsumableCategories();
        $this->backfillOpeningBalances();
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_stock_movements');

        if (Schema::hasColumn('equipment_categories_table', 'equipment_category_tracking_policy')) {
            Schema::table('equipment_categories_table', function (Blueprint $table) {
                $table->dropColumn('equipment_category_tracking_policy');
            });
        }

        if (Schema::hasColumn('disposal_records_table', 'disposal_condition_before')) {
            Schema::table('disposal_records_table', function (Blueprint $table) {
                $table->dropColumn('disposal_condition_before');
            });
        }
    }

    private function markConsumableCategories(): void
    {
        if (! Schema::hasColumn('equipment_categories_table', 'equipment_category_tracking_policy')) {
            return;
        }

        $needles = [
            'consumable', 'consumables', 'supply', 'supplies', 'office supply', 'office supplies',
            'ink', 'toner', 'cartridge', 'paper', 'bond paper', 'cleaning', 'janitorial', 'chemical',
        ];

        DB::table('equipment_categories_table')
            ->whereNull('equipment_category_tracking_policy')
            ->orderBy('equipment_category_id')
            ->get(['equipment_category_id', 'equipment_category_name'])
            ->each(function ($row) use ($needles) {
                $name = mb_strtolower(trim((string) $row->equipment_category_name));
                foreach ($needles as $needle) {
                    if ($name !== '' && str_contains($name, $needle)) {
                        DB::table('equipment_categories_table')
                            ->where('equipment_category_id', $row->equipment_category_id)
                            ->update(['equipment_category_tracking_policy' => 'stores']);

                        return;
                    }
                }
            });
    }

    private function backfillOpeningBalances(): void
    {
        if (! Schema::hasTable('equipment_table') || ! Schema::hasTable('equipment_stock_movements')) {
            return;
        }

        DB::table('equipment_table')
            ->orderBy('equipment_id')
            ->select(['equipment_id', 'equipment_quantity', 'equipment_room_id'])
            ->chunkById(200, function ($rows) {
                $ids = $rows->pluck('equipment_id')->map(fn ($id) => (int) $id)->all();
                $already = DB::table('equipment_stock_movements')
                    ->whereIn('equipment_id', $ids)
                    ->distinct()
                    ->pluck('equipment_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $inserts = [];
                foreach ($rows as $row) {
                    $id = (int) $row->equipment_id;
                    if (in_array($id, $already, true)) {
                        continue;
                    }
                    $qty = max(0, (int) ($row->equipment_quantity ?? 0));
                    if ($qty < 1) {
                        continue;
                    }
                    $inserts[] = [
                        'equipment_id' => $id,
                        'movement_type' => 'opening_balance',
                        'quantity_delta' => $qty,
                        'room_id' => $row->equipment_room_id ?: null,
                        'reference_type' => null,
                        'reference_id' => null,
                        'performed_by' => null,
                        'remarks' => 'Opening balance before the stock ledger',
                        'created_at' => now(),
                    ];
                }

                if ($inserts !== []) {
                    DB::table('equipment_stock_movements')->insert($inserts);
                }
            }, 'equipment_id');
    }
};
