<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * The tag, serial, and QR that tell one equipment unit from another with the same name.
 */
class EquipmentIdentity
{
    /**
     * @return list<string>
     */
    public static function columns(string $table = 'equipment_table'): array
    {
        $names = [
            'equipment_id',
            'equipment_asset_tag',
            'equipment_serial_number',
            'equipment_qr_code',
            'equipment_tracking_mode',
            'equipment_quantity',
        ];

        return array_values(array_map(
            fn (string $column) => $table.'.'.$column,
            array_filter($names, fn (string $column) => Schema::hasColumn('equipment_table', $column))
        ));
    }

    public static function label(object $row): string
    {
        $tag = trim((string) ($row->equipment_asset_tag ?? ''));
        $serial = trim((string) ($row->equipment_serial_number ?? ''));
        $qr = trim((string) ($row->equipment_qr_code ?? ''));
        $isBulk = ($row->equipment_tracking_mode ?? 'Individual') === 'Bulk';
        $qty = (int) ($row->equipment_quantity ?? 0);

        $parts = array_values(array_filter([
            $tag !== '' ? 'Tag '.$tag : null,
            (! $isBulk && $serial !== '') ? 'Serial '.$serial : null,
            $qr !== '' ? 'QR '.$qr : null,
            ($isBulk && $qty > 0) ? 'Qty '.$qty : null,
        ]));

        if ($parts !== []) {
            return implode(' · ', $parts);
        }

        $known = filled($row->equipment_id ?? null) || filled($row->equipment_name ?? null);

        return $known ? 'No asset tag / serial' : '';
    }
}
