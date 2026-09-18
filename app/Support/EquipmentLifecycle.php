<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EquipmentLifecycle
{
    public const DEFAULT_USEFUL_LIFE_YEARS = 5;

    /**
     * Assets within 1 year of (or past) useful life, excluding disposed.
     */
    public static function agingAlerts(int $limit = 8): Collection
    {
        if (! Schema::hasTable('equipment_table')) {
            return collect();
        }

        try {
            $lifeExpr = Schema::hasColumn('equipment_table', 'equipment_useful_life_years')
                ? 'COALESCE(equipment_useful_life_years, '.self::DEFAULT_USEFUL_LIFE_YEARS.')'
                : (string) self::DEFAULT_USEFUL_LIFE_YEARS;

            $query = DB::table('equipment_table')
                ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
                ->whereRaw('COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at) IS NOT NULL')
                ->where(function ($q) {
                    $q->whereNull('equipment_inventory_status')
                        ->orWhereNotIn('equipment_inventory_status', ['Disposed']);
                })
                ->select(
                    'equipment_table.equipment_id',
                    'equipment_table.equipment_name',
                    'equipment_table.equipment_inventory_status',
                    'equipment_table.equipment_condition_status',
                    'equipment_table.equipment_asset_tag',
                    'rooms_table.room_name',
                    DB::raw("{$lifeExpr} as useful_life_years"),
                    DB::raw('TIMESTAMPDIFF(YEAR, COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at), CURDATE()) as age_years'),
                    DB::raw("({$lifeExpr} - TIMESTAMPDIFF(YEAR, COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at), CURDATE())) as years_remaining")
                )
                ->havingRaw('years_remaining <= 1')
                ->orderBy('years_remaining');

            if ($limit > 0) {
                $query->limit($limit);
            }

            return $query->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function suggestAction(int $yearsRemaining, ?string $inventoryStatus = null): array
    {
        $alreadyMarked = strcasecmp((string) $inventoryStatus, 'For Replacement') === 0;

        if ($alreadyMarked) {
            return [
                'label' => 'Marked for replacement',
                'hint' => 'Awaiting procurement',
                'tone' => 'alert',
            ];
        }

        if ($yearsRemaining < 0) {
            return [
                'label' => 'Replace overdue',
                'hint' => abs($yearsRemaining).'y past lifespan',
                'tone' => 'alert',
            ];
        }

        if ($yearsRemaining === 0) {
            return [
                'label' => 'Replace this year',
                'hint' => 'End of useful life',
                'tone' => 'alert',
            ];
        }

        return [
            'label' => 'Plan replacement',
            'hint' => '~'.$yearsRemaining.'y left',
            'tone' => 'warn',
        ];
    }
}
