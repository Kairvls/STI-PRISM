<?php

namespace App\Support;

/**
 * Shared equipment shape for Building Layout / Room Interior Layout.
 */
class LayoutEquipmentPayload
{
    public static function fromRow(object $row, ?string $imageUrl = null): array
    {
        $payload = [
            'id' => (int) ($row->equipment_id ?? 0),
            'name' => $row->equipment_name ?? null,
            'category' => $row->equipment_category_id ?? null,
            'category_name' => $row->equipment_category_name ?? null,
            'quantity' => (int) ($row->equipment_quantity ?? 1),
            'tracking_mode' => $row->equipment_tracking_mode ?: 'Individual',
            'condition' => $row->equipment_condition_status ?? null,
            'inventory_status' => $row->equipment_inventory_status ?? null,
            'asset_tag' => $row->equipment_asset_tag ?? null,
            'serial_number' => $row->equipment_serial_number ?? null,
            'brand' => $row->equipment_brand_name ?? null,
            'model' => $row->equipment_model ?? null,
            'location' => $row->equipment_current_location ?? null,
            'placement_zone' => $row->equipment_placement_zone ?? null,
            'acquired_date' => self::dateString($row->equipment_acquired_date ?? null),
            'warranty_expiration' => self::dateString($row->equipment_warranty_expiration ?? null),
            'room_name' => $row->room_name ?? null,
            'room_type' => $row->room_type ?? null,
            'view_url' => isset($row->equipment_id)
                ? EquipmentViewReturn::viewUrl((int) $row->equipment_id)
                : null,
            'image_url' => $imageUrl,
        ];

        return $payload;
    }

    public static function fromModel($equipment): array
    {
        $warranty = $equipment->equipment_warranty_expiration ?? null;
        $purchaseDate = $equipment->equipment_purchase_date ?? null;
        $acquiredDate = $equipment->equipment_acquired_date ?? null;
        $createdAt = $equipment->equipment_created_at ?? null;

        return [
            'id' => $equipment->equipment_id,
            'name' => $equipment->equipment_name,
            'category' => $equipment->equipment_category_id,
            'category_name' => optional($equipment->category)->equipment_category_name
                ?? ($equipment->equipment_category_name ?? null),
            'quantity' => (int) ($equipment->equipment_quantity ?? 1),
            'tracking_mode' => $equipment->equipment_tracking_mode ?: 'Individual',
            'condition' => $equipment->equipment_condition_status,
            'inventory_status' => $equipment->equipment_inventory_status,
            'asset_tag' => $equipment->equipment_asset_tag,
            'serial_number' => $equipment->equipment_serial_number,
            'brand' => $equipment->equipment_brand_name,
            'model' => $equipment->equipment_model,
            'location' => $equipment->equipment_current_location,
            'placement_zone' => $equipment->equipment_placement_zone,
            'purchase_date' => self::dateString($purchaseDate),
            'acquired_date' => self::dateString($acquiredDate),
            'warranty_expiration' => self::dateString($warranty),
            'purchase_cost' => isset($equipment->equipment_purchase_cost)
                && $equipment->equipment_purchase_cost !== null
                && $equipment->equipment_purchase_cost !== ''
                    ? (float) $equipment->equipment_purchase_cost
                    : null,
            'created_at' => self::dateString($createdAt),
            'view_url' => EquipmentViewReturn::viewUrl((int) $equipment->equipment_id),
            'x' => (int) ($equipment->equipment_position_x ?? 50),
            'y' => (int) ($equipment->equipment_position_y ?? 50),
            'width' => (int) ($equipment->equipment_width ?? 120),
            'height' => (int) ($equipment->equipment_height ?? 96),
            'rotation' => (int) ($equipment->equipment_rotation ?? 0),
        ];
    }

    private static function dateString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
