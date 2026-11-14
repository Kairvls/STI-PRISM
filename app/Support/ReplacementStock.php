<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stockroom units that can stand in for equipment sent "For Replacement",
 * and the log that maps each replaced unit to how it was fulfilled.
 */
class ReplacementStock
{
    public const TABLE = 'equipment_replacement_logs_table';

    public const STOCK_SWAP = 'Inventory Stock';

    public const PROCUREMENT = 'Procurement';

    /** Marked Replaced with no successor unit (scrap / no swap). */
    public const NO_SUCCESSOR = 'No Successor';

    public const FULFILLMENT_TYPES = [
        self::STOCK_SWAP,
        self::PROCUREMENT,
        self::NO_SUCCESSOR,
    ];

    private const USABLE_STATUSES = ['active', 'available', 'in stock'];

    private const UNUSABLE_CONDITIONS = ['damaged', 'defective', 'poor', 'for repair', 'disposed'];

    private const MAX_CANDIDATES = 25;

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * Stock units for one report item, best match first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function candidatesFor(object $item): Collection
    {
        $replacedId = (int) ($item->report_item_equipment_id ?? 0);
        $replaced = $replacedId > 0
            ? DB::table('equipment_table')->where('equipment_id', $replacedId)->first()
            : null;

        $name = trim((string) ($replaced->equipment_name ?? $item->report_item_unlisted_equipment_name ?? ''));
        $categoryId = (int) ($replaced->equipment_category_id ?? 0);

        if ($name === '') {
            return collect();
        }

        $query = DB::table('equipment_table as e')
            ->join('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->where('r.room_type', RoomCategories::STORAGE_TYPE)
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where(fn ($w) => $w->whereNull('r.room_is_archived')->orWhere('r.room_is_archived', false))
            )
            ->whereRaw('LOWER(TRIM(e.equipment_inventory_status)) IN ('.implode(',', array_fill(0, count(self::USABLE_STATUSES), '?')).')', self::USABLE_STATUSES)
            ->where(function ($q) {
                $q->whereNull('e.equipment_condition_status')
                    ->orWhereRaw('LOWER(TRIM(e.equipment_condition_status)) NOT IN ('.implode(',', array_fill(0, count(self::UNUSABLE_CONDITIONS), '?')).')', self::UNUSABLE_CONDITIONS);
            })
            ->where(function ($q) {
                $q->where('e.equipment_tracking_mode', 'Individual')
                    ->orWhere('e.equipment_quantity', '<=', 1)
                    ->orWhereNull('e.equipment_quantity');
            })
            ->when($replacedId > 0, fn ($q) => $q->where('e.equipment_id', '!=', $replacedId));

        // A category can hold different items (desktops, keyboards, mice), so the name must match too.
        $needle = mb_strtolower($name);
        $query
            ->when($categoryId > 0, fn ($q) => $q->where('e.equipment_category_id', $categoryId))
            ->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(TRIM(e.equipment_name)) = ?', [$needle])
                    ->orWhereRaw('LOWER(e.equipment_name) LIKE ?', ['%'.$needle.'%'])
                    ->orWhereRaw("? LIKE CONCAT('%', LOWER(TRIM(e.equipment_name)), '%') AND CHAR_LENGTH(TRIM(e.equipment_name)) >= 3", [$needle]);
            });

        $rows = $query
            ->orderBy('e.equipment_id')
            ->limit(200)
            ->get([
                'e.*',
                'r.room_name as stock_room_name',
            ]);

        if ($rows->isEmpty()) {
            return collect();
        }

        $locked = TransferRequests::pendingEquipmentIds() + DisposalRequests::pendingEquipmentIds();
        $loans = TransferLocks::openLoans($rows->pluck('equipment_id')->all());

        return $rows
            ->reject(fn ($row) => isset($locked[(int) $row->equipment_id]) || $loans->has((int) $row->equipment_id))
            ->map(fn ($row) => self::present($row, $replaced, $name))
            ->sortBy([
                ['score', 'desc'],
                ['warranty_days_left', 'desc'],
                ['equipment_id', 'asc'],
            ])
            ->take(self::MAX_CANDIDATES)
            ->values();
    }

    public static function candidateIdsFor(object $item): array
    {
        return self::candidatesFor($item)->pluck('equipment_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Storage units that can stand in for this equipment, best match first.
     *
     * @return list<array{name: string, asset_tag: ?string, serial: ?string, storage: string, condition: string, match: string}>
     */
    public static function briefForEquipment(int $equipmentId): array
    {
        if ($equipmentId <= 0 || ! Schema::hasTable('equipment_table')) {
            return [];
        }

        return self::candidatesFor((object) ['report_item_equipment_id' => $equipmentId])
            ->map(fn ($row) => [
                'name' => (string) ($row['name'] ?? 'Equipment'),
                'asset_tag' => $row['asset_tag'] ?? null,
                'serial' => $row['serial'] ?? null,
                'storage' => (string) ($row['storage'] ?? 'Storage'),
                'condition' => (string) ($row['condition'] ?? 'Not set'),
                'match' => (string) ($row['match'] ?? 'Similar item'),
            ])
            ->all();
    }

    /**
     * Stock check payload for the "For Replacement" picker.
     *
     * @param  Collection<int, object>  $items  report items being sent for replacement
     */
    public static function summary(Collection $items): array
    {
        $rows = $items->map(function ($item) {
            $candidates = self::candidatesFor($item);

            return [
                'report_item_id' => (int) $item->report_item_id,
                'name' => ReportItems::displayName($item),
                'asset_tag' => $item->equipment_asset_tag ?? null,
                'brand' => $item->equipment_brand_name ?? null,
                'model' => $item->equipment_model ?? null,
                'room' => $item->item_room_name ?? $item->room_name ?? null,
                'candidates' => $candidates->all(),
            ];
        })->values();

        return [
            'has_stock' => $rows->contains(fn ($row) => $row['candidates'] !== []),
            'items' => $rows->all(),
        ];
    }

    public static function log(array $data): void
    {
        if (! self::tableReady()) {
            return;
        }

        DB::table(self::TABLE)->insert(array_merge([
            'replacement_log_performed_by' => Auth::id(),
            'replacement_log_performed_at' => now(),
        ], $data));
    }

    /**
     * Stock unit swapped in for a replaced unit (report swap or computer-set swap).
     *
     * @param  array{from_room_id?: int|null, to_room_id?: int|null, notes?: string|null, report_id?: int|null, report_item_id?: int|null, performed_by?: int|null}  $extra
     */
    public static function logStockSwap(object $replaced, object $replacement, array $extra = []): void
    {
        self::log([
            'replacement_log_replaced_equipment_id' => $replaced->equipment_id ?? null,
            'replacement_log_replaced_label' => self::label($replaced),
            'replacement_log_replaced_asset_tag' => $replaced->equipment_asset_tag ?? null,
            'replacement_log_replacement_equipment_id' => $replacement->equipment_id ?? null,
            'replacement_log_replacement_label' => self::label($replacement),
            'replacement_log_replacement_asset_tag' => $replacement->equipment_asset_tag ?? null,
            'replacement_log_fulfillment_type' => self::STOCK_SWAP,
            'replacement_log_stock_was_available' => true,
            'replacement_log_from_room_id' => $extra['from_room_id'] ?? ($replacement->equipment_room_id ?? null),
            'replacement_log_to_room_id' => $extra['to_room_id'] ?? ($replaced->equipment_room_id ?? null),
            'replacement_log_notes' => $extra['notes'] ?? null,
            'replacement_log_report_id' => $extra['report_id'] ?? null,
            'replacement_log_report_item_id' => $extra['report_item_id'] ?? null,
            'replacement_log_performed_by' => $extra['performed_by'] ?? Auth::id(),
        ]);
    }

    /**
     * For Replacement → Replaced with no successor equipment.
     *
     * @param  array{performed_by?: int|null}  $extra
     */
    public static function logNoSuccessor(object $equipment, string $reason, array $extra = []): void
    {
        self::log([
            'replacement_log_replaced_equipment_id' => $equipment->equipment_id ?? null,
            'replacement_log_replaced_label' => self::label($equipment),
            'replacement_log_replaced_asset_tag' => $equipment->equipment_asset_tag ?? null,
            'replacement_log_fulfillment_type' => self::NO_SUCCESSOR,
            'replacement_log_stock_was_available' => false,
            'replacement_log_to_room_id' => $equipment->equipment_room_id ?? null,
            'replacement_log_notes' => $reason !== '' ? $reason : null,
            'replacement_log_performed_by' => $extra['performed_by'] ?? Auth::id(),
        ]);
    }

    /**
     * Latest replacement log rows for the dashboard preview.
     *
     * @return array{href: string, total: int, rows: list<array<string, string>>}
     */
    public static function dashboardPreview(int $limit = 8): array
    {
        $href = route('admin.equipment.replacements');
        if (! self::tableReady()) {
            return ['href' => $href, 'total' => 0, 'rows' => []];
        }

        $total = (int) DB::table(self::TABLE)->count();
        $logs = DB::table(self::TABLE.' as l')
            ->orderByDesc('l.replacement_log_performed_at')
            ->orderByDesc('l.replacement_log_id')
            ->limit($limit)
            ->get([
                'l.replacement_log_replaced_label',
                'l.replacement_log_replaced_asset_tag',
                'l.replacement_log_replacement_label',
                'l.replacement_log_replacement_asset_tag',
                'l.replacement_log_fulfillment_type',
                'l.replacement_log_performed_at',
            ]);

        return [
            'href' => $href,
            'total' => $total,
            'rows' => $logs->map(function ($row) {
                $type = (string) ($row->replacement_log_fulfillment_type ?? '');
                $replaced = trim((string) ($row->replacement_log_replaced_label ?? '')) ?: 'Equipment';
                $replacedTag = trim((string) ($row->replacement_log_replaced_asset_tag ?? ''));
                $replacement = trim((string) ($row->replacement_log_replacement_label ?? ''));
                $replacementTag = trim((string) ($row->replacement_log_replacement_asset_tag ?? ''));
                $when = $row->replacement_log_performed_at
                    ? Carbon::parse($row->replacement_log_performed_at)->format('M j, Y')
                    : '';

                return [
                    'replaced' => $replaced,
                    'replaced_tag' => $replacedTag,
                    'replacement' => $replacement,
                    'replacement_tag' => $replacementTag,
                    'type' => self::fulfillmentLabel($type),
                    'date' => $when,
                ];
            })->all(),
        ];
    }

    public static function fulfillmentLabel(string $type): string
    {
        return match ($type) {
            self::STOCK_SWAP => 'Swapped from inventory stock',
            self::PROCUREMENT => 'Procurement request',
            self::NO_SUCCESSOR => 'Marked Replaced (no successor)',
            default => $type,
        };
    }

    public static function label(?object $equipment, ?string $fallback = null): ?string
    {
        if (! $equipment) {
            return $fallback;
        }

        $model = trim(implode(' ', array_filter([
            $equipment->equipment_brand_name ?? null,
            $equipment->equipment_model ?? null,
        ])));

        return trim(($equipment->equipment_name ?? 'Equipment').($model !== '' ? ' ('.$model.')' : ''));
    }

    /**
     * Replacement log rows that involve this unit, newest first.
     */
    public static function forEquipment(int $equipmentId): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return DB::table(self::TABLE.' as l')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'l.replacement_log_performed_by')
            ->where(function ($q) use ($equipmentId) {
                $q->where('l.replacement_log_replaced_equipment_id', $equipmentId)
                    ->orWhere('l.replacement_log_replacement_equipment_id', $equipmentId);
            })
            ->orderByDesc('l.replacement_log_performed_at')
            ->get(['l.*', 'u.user_full_name as performed_by_name']);
    }

    private static function present(object $row, ?object $replaced, string $name): array
    {
        $same = fn (?string $a, ?string $b) => trim(mb_strtolower((string) $a)) !== ''
            && trim(mb_strtolower((string) $a)) === trim(mb_strtolower((string) $b));

        $sameName = $same($row->equipment_name, $name);
        $sameModel = $replaced && $same($row->equipment_model ?? null, $replaced->equipment_model ?? null);
        $sameBrand = $replaced && $same($row->equipment_brand_name ?? null, $replaced->equipment_brand_name ?? null);

        $score = ($sameName ? 2 : 0) + ($sameModel ? 2 : 0) + ($sameBrand ? 1 : 0);
        $match = match (true) {
            $sameModel && ($sameName || $sameBrand) => 'Same model',
            $sameName => 'Same item',
            default => 'Similar item',
        };

        $warranty = ! empty($row->equipment_warranty_expiration)
            ? Carbon::parse($row->equipment_warranty_expiration)
            : null;
        $daysLeft = $warranty ? (int) now()->startOfDay()->diffInDays($warranty, false) : -99999;

        return [
            'equipment_id' => (int) $row->equipment_id,
            'name' => $row->equipment_name,
            'brand' => $row->equipment_brand_name,
            'model' => $row->equipment_model,
            'asset_tag' => $row->equipment_asset_tag,
            'serial' => $row->equipment_serial_number,
            'storage' => trim(($row->stock_room_name ?? 'Storage')
                .(filled($row->equipment_placement_zone ?? null) && $row->equipment_placement_zone !== 'Holding'
                    ? ' · '.$row->equipment_placement_zone
                    : '')),
            'condition' => $row->equipment_condition_status ?: 'Not set',
            'warranty' => $warranty
                ? ($daysLeft >= 0 ? 'Until '.$warranty->format('M j, Y') : 'Expired '.$warranty->format('M j, Y'))
                : 'No warranty on file',
            'warranty_active' => $warranty !== null && $daysLeft >= 0,
            'warranty_days_left' => $daysLeft,
            'match' => $match,
            'score' => $score,
            'view_url' => '/maintenance/equipment/view/'.(int) $row->equipment_id,
        ];
    }
}
