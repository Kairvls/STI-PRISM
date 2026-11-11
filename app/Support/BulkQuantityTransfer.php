<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split a Bulk quantity pile across rooms (e.g. deploy 5 of 20 chairs).
 */
class BulkQuantityTransfer
{
    public static function isBulk(object $equipment): bool
    {
        return ($equipment->equipment_tracking_mode ?? EquipmentTrackingModes::INDIVIDUAL) === EquipmentTrackingModes::BULK
            && (int) ($equipment->equipment_quantity ?? 1) > 1;
    }

    public static function resolveQuantity(object $equipment, ?int $requested): int
    {
        $stock = max(1, (int) ($equipment->equipment_quantity ?? 1));
        if (! self::isBulk($equipment)) {
            return $stock;
        }

        $qty = $requested !== null && $requested > 0 ? $requested : $stock;

        return min($stock, max(1, $qty));
    }

    /**
     * When moving only part of a Bulk pile, reduce the source and return the
     * equipment row that should land in the destination (merged or newly created).
     */
    public static function preparePartialMove(object $equipment, int $quantity, int $destinationRoomId): object
    {
        $stock = max(1, (int) ($equipment->equipment_quantity ?? 1));
        $quantity = min($stock, max(1, $quantity));

        if ($quantity >= $stock || ! self::isBulk($equipment)) {
            return $equipment;
        }

        DB::table('equipment_table')
            ->where('equipment_id', $equipment->equipment_id)
            ->update(['equipment_quantity' => $stock - $quantity]);

        $existing = self::findMergeTarget($equipment, $destinationRoomId);
        if ($existing) {
            DB::table('equipment_table')
                ->where('equipment_id', $existing->equipment_id)
                ->update([
                    'equipment_quantity' => (int) ($existing->equipment_quantity ?? 0) + $quantity,
                ]);

            return DB::table('equipment_table')->where('equipment_id', $existing->equipment_id)->first();
        }

        $copy = (array) $equipment;
        $insert = [];
        foreach ($copy as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'equipment_')) {
                continue;
            }
            if (in_array($key, ['equipment_id'], true)) {
                continue;
            }
            $insert[$key] = $value;
        }

        $insert['equipment_quantity'] = $quantity;
        $insert['equipment_tracking_mode'] = EquipmentTrackingModes::BULK;
        $insert['equipment_room_id'] = $destinationRoomId;
        $insert['equipment_placement_zone'] = 'Holding';
        $insert['equipment_current_location'] = 'Holding';
        $insert['equipment_position_x'] = 50;
        $insert['equipment_position_y'] = 90;

        foreach (['workstation_slot_id', 'workstation_id', 'workstation_template_slot_id'] as $slotCol) {
            if (Schema::hasColumn('equipment_table', $slotCol)) {
                $insert[$slotCol] = null;
            }
        }

        // Avoid unique collisions on identifiers for the split-off pile.
        foreach (['equipment_asset_tag', 'equipment_qr_code', 'equipment_serial_number'] as $uniqueCol) {
            if (array_key_exists($uniqueCol, $insert)) {
                $insert[$uniqueCol] = null;
            }
        }

        $insert = collect($insert)
            ->filter(fn ($value, $key) => Schema::hasColumn('equipment_table', (string) $key))
            ->all();

        $newId = (int) DB::table('equipment_table')->insertGetId($insert);

        return DB::table('equipment_table')->where('equipment_id', $newId)->first();
    }

    private static function findMergeTarget(object $source, int $destinationRoomId): ?object
    {
        $query = DB::table('equipment_table')
            ->where('equipment_room_id', $destinationRoomId)
            ->where('equipment_tracking_mode', EquipmentTrackingModes::BULK)
            ->where('equipment_name', $source->equipment_name ?? null)
            ->where('equipment_id', '!=', (int) $source->equipment_id)
            ->whereNotIn('equipment_inventory_status', [EquipmentInventoryStatuses::DISPOSED]);

        if (Schema::hasColumn('equipment_table', 'equipment_category_id')) {
            $query->where('equipment_category_id', $source->equipment_category_id ?? null);
        }
        if (Schema::hasColumn('equipment_table', 'equipment_brand_name')) {
            $query->where('equipment_brand_name', $source->equipment_brand_name ?? null);
        }
        if (Schema::hasColumn('equipment_table', 'equipment_model')) {
            $query->where('equipment_model', $source->equipment_model ?? null);
        }
        if (Schema::hasColumn('equipment_table', 'equipment_condition_status')) {
            $query->where('equipment_condition_status', $source->equipment_condition_status ?? null);
        }

        return $query->orderBy('equipment_id')->first();
    }
}
