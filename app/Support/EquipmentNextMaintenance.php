<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds next_maintenance_date / has_overdue_schedule to an equipment_table query
 * from the item's open (Active / Overdue) maintenance schedules.
 */
class EquipmentNextMaintenance
{
    public const OPEN_STATUSES = ['Active', 'Overdue'];

    public static function apply(Builder $query): Builder
    {
        if (! Schema::hasTable('maintenance_schedules_table')) {
            return $query;
        }

        $openSchedules = fn () => DB::table('maintenance_schedules_table')
            ->whereColumn('maintenance_schedules_table.maintenance_schedule_equipment_id', 'equipment_table.equipment_id')
            ->whereIn('maintenance_schedules_table.maintenance_schedule_status', self::OPEN_STATUSES);

        return $query->addSelect([
            'next_maintenance_date' => $openSchedules()
                ->selectRaw('MIN(maintenance_schedules_table.maintenance_schedule_next_date)'),
            'has_overdue_schedule' => $openSchedules()
                ->selectRaw("MAX(maintenance_schedules_table.maintenance_schedule_status = 'Overdue')"),
        ]);
    }

    public static function isOverdue(object $row): bool
    {
        if (! filled($row->next_maintenance_date ?? null)) {
            return false;
        }

        return ! empty($row->has_overdue_schedule)
            || Carbon::parse($row->next_maintenance_date)->lt(today());
    }
}
