<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The tag, serial, and QR that tell one equipment unit from another with the same name.
 */
class EquipmentIdentity
{
    /** @var list<string>|null */
    private static ?array $reservedAssetTagCache = null;
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

    /**
     * One unique asset tag per unit. Bulk keeps a single stock row and quantity,
     * and each unit takes the next number after the stored tag.
     *
     * @return list<string>
     */
    public static function unitTags(object $row): array
    {
        if (isset($row->grouped_units) && is_array($row->grouped_units) && $row->grouped_units !== []) {
            return array_values(array_filter(array_map(
                fn ($unit) => trim((string) (is_array($unit) ? ($unit['tag'] ?? '') : '')),
                $row->grouped_units
            )));
        }

        $tag = trim((string) ($row->equipment_asset_tag ?? ''));
        $isBulk = ($row->equipment_tracking_mode ?? 'Individual') === 'Bulk';
        $qty = max(1, (int) ($row->equipment_quantity ?? 1));

        if ($tag === '' || ! $isBulk || $qty <= 1) {
            return $tag !== '' ? [$tag] : [];
        }

        if (! preg_match('/^(.*?)(\d+)$/', $tag, $matches)) {
            $tags = [];
            for ($unit = 1; $unit <= $qty; $unit++) {
                $tags[] = $tag.' - '.str_pad((string) $unit, 3, '0', STR_PAD_LEFT);
            }

            return $tags;
        }

        $prefix = $matches[1];
        $width = strlen($matches[2]);
        $start = (int) $matches[2];
        $tags = [];
        for ($offset = 0; $offset < $qty; $offset++) {
            $tags[] = $prefix.str_pad((string) ($start + $offset), $width, '0', STR_PAD_LEFT);
        }

        return $tags;
    }

    /**
     * Short label for lists: one tag, or the first through last number in a bulk pile.
     */
    public static function unitTagLabel(object $row): string
    {
        $tags = self::unitTags($row);
        if ($tags === []) {
            return '';
        }
        if (count($tags) === 1) {
            return $tags[0];
        }

        $first = $tags[0];
        $last = $tags[count($tags) - 1];
        if (
            preg_match('/^(.*?)(\d+)$/', $first, $start)
            && preg_match('/^(.*?)(\d+)$/', $last, $end)
            && $start[1] === $end[1]
            && ((int) $end[2] - (int) $start[2] + 1) === count($tags)
        ) {
            return $first.' – '.$end[2];
        }

        return $first.' · '.count($tags).' IDs';
    }

    /**
     * Every asset tag that is already spoken for, including each unit inside a bulk pile.
     *
     * @return list<string>
     */
    public static function reservedAssetTags(): array
    {
        if (self::$reservedAssetTagCache !== null) {
            return self::$reservedAssetTagCache;
        }

        $rows = DB::table('equipment_table')
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->get(['equipment_asset_tag', 'equipment_tracking_mode', 'equipment_quantity']);

        $tags = [];
        foreach ($rows as $row) {
            foreach (self::unitTags($row) as $tag) {
                if ($tag !== '') {
                    $tags[] = $tag;
                }
            }
        }

        return self::$reservedAssetTagCache = array_values(array_unique($tags));
    }

    public static function tagIsReserved(string $tag): bool
    {
        $key = mb_strtolower(trim($tag));
        if ($key === '') {
            return false;
        }

        foreach (self::reservedAssetTags() as $reserved) {
            if (mb_strtolower($reserved) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * One uniqueness line per borrowed unit. Bulk mode stays one pile; each borrowed unit keeps its own id.
     *
     * @return list<string>
     */
    public static function borrowedUnitLabels(object $row, int $quantity): array
    {
        $quantity = max(1, $quantity);
        $isBulk = ($row->equipment_tracking_mode ?? 'Individual') === 'Bulk';

        if (! $isBulk) {
            $label = self::label($row);

            return [$label !== '' ? $label : 'No asset tag / serial'];
        }

        $tags = array_slice(self::unitTags($row), 0, $quantity);
        if ($tags !== []) {
            return array_values($tags);
        }

        $labels = [];
        for ($unit = 1; $unit <= $quantity; $unit++) {
            $labels[] = 'Unit '.$unit;
        }

        return $labels;
    }

    /**
     * One profile target per unit id. Separate records open their own profile.
     * A single bulk row opens the shared profile for the chosen tag.
     *
     * @return list<array{id:int,tag:string,synthetic:bool}>
     */
    public static function unitProfiles(object $row): array
    {
        if (isset($row->grouped_units) && is_array($row->grouped_units) && $row->grouped_units !== []) {
            return array_values(array_map(fn ($unit) => [
                'id' => (int) ($unit['id'] ?? 0),
                'tag' => trim((string) ($unit['tag'] ?? '')),
                'synthetic' => (bool) ($unit['synthetic'] ?? false),
            ], $row->grouped_units));
        }

        $tags = self::unitTags($row);
        $id = (int) ($row->equipment_id ?? 0);
        if (count($tags) <= 1 || $id < 1) {
            return [];
        }

        return array_map(fn (string $tag) => [
            'id' => $id,
            'tag' => $tag,
            'synthetic' => true,
        ], $tags);
    }

    /**
     * All Equipment: same name in the same room is one bulk row. Each unit keeps its own id.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function presentAllEquipment(\Illuminate\Support\Collection $rows): \Illuminate\Support\Collection
    {
        $buckets = [];
        foreach ($rows as $row) {
            $name = mb_strtolower(trim((string) ($row->equipment_name ?? '')));
            $room = (int) ($row->equipment_room_id ?? 0);
            $status = (string) ($row->equipment_inventory_status ?? '');
            $condition = (string) ($row->equipment_condition_status ?? '');
            $buckets[$name.'|'.$room.'|'.$status.'|'.$condition][] = $row;
        }

        $presented = [];
        foreach ($buckets as $items) {
            usort($items, function ($left, $right) {
                return strcmp((string) ($left->equipment_asset_tag ?? ''), (string) ($right->equipment_asset_tag ?? ''));
            });

            if (count($items) < 2) {
                $presented[] = $items[0];
                continue;
            }

            $primary = $items[0];
            $units = [];
            $quantity = 0;
            $newest = (string) ($primary->equipment_created_at ?? '');
            foreach ($items as $item) {
                $quantity += max(1, (int) ($item->equipment_quantity ?? 1));
                $created = (string) ($item->equipment_created_at ?? '');
                if ($created > $newest) {
                    $newest = $created;
                }
                $tags = self::unitTags($item);
                if ($tags === []) {
                    $missing = max(1, (int) ($item->equipment_quantity ?? 1));
                    $fallback = trim((string) ($item->equipment_asset_tag ?? ''));
                    $tags = array_fill(0, $missing, $fallback !== '' ? $fallback : 'No asset tag');
                }
                $synthetic = count($tags) > 1 || (($item->equipment_tracking_mode ?? '') === 'Bulk' && (int) ($item->equipment_quantity ?? 1) > 1);
                foreach ($tags as $tag) {
                    $units[] = [
                        'id' => (int) $item->equipment_id,
                        'tag' => $tag,
                        'synthetic' => $synthetic,
                    ];
                }
            }

            $primary->equipment_quantity = $quantity;
            $primary->equipment_tracking_mode = 'Bulk';
            $primary->equipment_created_at = $newest;
            $primary->grouped_units = $units;
            $primary->grouped_bulk = true;
            $presented[] = $primary;
        }

        usort($presented, function ($left, $right) {
            return strcmp((string) ($right->equipment_created_at ?? ''), (string) ($left->equipment_created_at ?? ''));
        });

        return collect($presented)->values();
    }
}
