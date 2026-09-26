<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EquipmentConditionHistory
{
    public static function record(
        int $equipmentId,
        ?string $from,
        ?string $to,
        ?string $source = null,
        ?string $remarks = null,
        ?int $userId = null
    ): void {
        if (! Schema::hasTable('equipment_condition_history_table')) {
            return;
        }

        $from = trim((string) $from);
        $to = trim((string) $to);
        if ($to === '' || strcasecmp($from, $to) === 0) {
            return;
        }

        DB::table('equipment_condition_history_table')->insert([
            'equipment_id' => $equipmentId,
            'condition_from' => $from !== '' ? $from : null,
            'condition_to' => $to,
            'changed_by' => $userId ?? Auth::id(),
            'change_source' => $source,
            'remarks' => $remarks,
            'created_at' => now(),
        ]);
    }

    public static function forEquipment(int $equipmentId, int $limit = 50): Collection
    {
        if (! Schema::hasTable('equipment_condition_history_table')) {
            return collect();
        }

        return DB::table('equipment_condition_history_table as h')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'h.changed_by')
            ->where('h.equipment_id', $equipmentId)
            ->orderByDesc('h.created_at')
            ->limit($limit)
            ->get([
                'h.*',
                'u.user_full_name as changed_by_name',
            ]);
    }
}
