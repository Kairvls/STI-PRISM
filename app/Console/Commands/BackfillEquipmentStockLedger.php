<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillEquipmentStockLedger extends Command
{
    protected $signature = 'inventory:backfill-ledger';

    protected $description = 'Write an opening-balance movement for equipment that has none yet';

    public function handle(): int
    {
        if (! Schema::hasTable('equipment_stock_movements') || ! Schema::hasTable('equipment_table')) {
            $this->warn('Stock ledger tables are missing. Run migrations first.');

            return self::SUCCESS;
        }

        $written = 0;

        DB::table('equipment_table')
            ->orderBy('equipment_id')
            ->select(['equipment_id', 'equipment_quantity', 'equipment_room_id'])
            ->chunkById(200, function ($rows) use (&$written) {
                $ids = $rows->pluck('equipment_id')->map(fn ($id) => (int) $id)->all();
                $already = DB::table('equipment_stock_movements')
                    ->whereIn('equipment_id', $ids)
                    ->distinct()
                    ->pluck('equipment_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($rows as $row) {
                    $id = (int) $row->equipment_id;
                    if (in_array($id, $already, true)) {
                        continue;
                    }
                    $qty = max(0, (int) ($row->equipment_quantity ?? 0));
                    if ($qty < 1) {
                        continue;
                    }
                    DB::table('equipment_stock_movements')->insert([
                        'equipment_id' => $id,
                        'movement_type' => 'opening_balance',
                        'quantity_delta' => $qty,
                        'room_id' => $row->equipment_room_id ?: null,
                        'remarks' => 'Opening balance before the stock ledger',
                        'created_at' => now(),
                    ]);
                    $written++;
                }
            }, 'equipment_id');

        $this->info('Opening balances written: '.$written);

        return self::SUCCESS;
    }
};
