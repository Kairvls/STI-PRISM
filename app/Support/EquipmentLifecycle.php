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

    /**
     * Every non-disposed asset with a known start date, soonest to expire first.
     */
    public static function catalog(): Collection
    {
        if (! Schema::hasTable('equipment_table')) {
            return collect();
        }

        try {
            $defaultYears = self::DEFAULT_USEFUL_LIFE_YEARS;
            $lifeExpr = Schema::hasColumn('equipment_table', 'equipment_useful_life_years')
                ? 'COALESCE(e.equipment_useful_life_years, '.$defaultYears.')'
                : (string) $defaultYears;
            $startExpr = 'COALESCE(e.equipment_purchase_date, e.equipment_acquired_date, e.equipment_created_at)';

            $query = DB::table('equipment_table as e')
                ->whereRaw($startExpr.' IS NOT NULL')
                ->where(function ($q) {
                    $q->whereNull('e.equipment_inventory_status')
                        ->orWhereNotIn('e.equipment_inventory_status', ['Disposed']);
                });

            if (Schema::hasTable('rooms_table')) {
                $query->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id');
            }

            $select = array_merge([
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_inventory_status',
                DB::raw($startExpr.' as lifespan_start'),
                DB::raw($lifeExpr.' as useful_life_years'),
                DB::raw('TIMESTAMPDIFF(YEAR, '.$startExpr.', CURDATE()) as age_years'),
                DB::raw('('.$lifeExpr.' - TIMESTAMPDIFF(YEAR, '.$startExpr.', CURDATE())) as years_remaining'),
            ], EquipmentIdentity::columns('e'));

            if (Schema::hasTable('rooms_table')) {
                $select[] = 'e.equipment_room_id';
                $select[] = 'r.room_name';
            }
            if (Schema::hasColumn('equipment_table', 'equipment_useful_life_years')) {
                $select[] = 'e.equipment_useful_life_years as set_useful_life_years';
            }

            return self::nearestFirst($query->get($select));
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Soonest end of useful life first. Same year-count breaks on the actual end date.
     */
    public static function nearestFirst(Collection $rows): Collection
    {
        return $rows->sortBy(function ($row) {
            $yearsLeft = (int) ($row->years_remaining ?? 0);
            $life = (int) ($row->useful_life_years ?? self::DEFAULT_USEFUL_LIFE_YEARS);
            $end = PHP_INT_MAX;
            if (! empty($row->lifespan_start)) {
                try {
                    $end = \Carbon\Carbon::parse($row->lifespan_start)->addYears(max(0, $life))->getTimestamp();
                } catch (\Throwable) {
                    $end = PHP_INT_MAX;
                }
            }

            return [$yearsLeft, $end, mb_strtolower((string) ($row->equipment_name ?? ''))];
        })->values();
    }

    public static function suggestAction(int $yearsRemaining, ?string $inventoryStatus = null): array
    {
        $alreadyMarked = in_array(
            strtolower(trim((string) $inventoryStatus)),
            ['for replacement', 'replaced'],
            true
        );

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
