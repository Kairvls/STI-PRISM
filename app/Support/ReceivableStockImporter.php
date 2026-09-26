<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * One-click import of pending completed RR lines into Maintenance inventory stock.
 */
class ReceivableStockImporter
{
    /**
     * @param  array<int, int>|null  $itemIds  Null/empty = all pending lines
     * @return array{
     *   stocked_lines: int,
     *   stocked_qty: int,
     *   created_ids: array<int, int>,
     *   skipped: array<int, array{id:int, article:string, reason:string}>,
     *   errors: array<int, string>
     * }
     */
    public static function stockPending(
        int $storageRoomId,
        ?int $categoryId = null,
        ?array $itemIds = null,
        ?int $stockedBy = null
    ): array {
        $result = [
            'stocked_lines' => 0,
            'stocked_qty' => 0,
            'created_ids' => [],
            'skipped' => [],
            'errors' => [],
        ];

        if ($storageRoomId < 1 || ! Schema::hasTable('equipment_table')) {
            $result['errors'][] = 'A storage / stockroom is required before stocking.';

            return $result;
        }

        $room = DB::table('rooms_table')->where('room_id', $storageRoomId)->first();
        if (! $room || ! RoomCategories::isStorageType($room->room_type ?? null)) {
            $result['errors'][] = 'Choose a Storage / Stockroom as the destination.';

            return $result;
        }

        $defaultCategoryId = $categoryId && $categoryId > 0
            ? $categoryId
            : self::defaultCategoryId();

        if (! $defaultCategoryId) {
            $result['errors'][] = 'Create at least one equipment category before stocking.';

            return $result;
        }

        $pending = ReceivableStockLines::pending();
        if (is_array($itemIds) && $itemIds !== []) {
            $wanted = collect($itemIds)->map(fn ($id) => (int) $id)->filter()->all();
            $pending = $pending->whereIn('receiving_report_item_id', $wanted)->values();
        }

        if ($pending->isEmpty()) {
            $result['errors'][] = 'No pending RR lines to stock.';

            return $result;
        }

        $stockedBy = $stockedBy ?? Auth::id();
        $categories = self::categoryLookup();

        DB::transaction(function () use (
            $pending,
            $storageRoomId,
            $defaultCategoryId,
            $categories,
            $stockedBy,
            &$result
        ) {
            foreach ($pending as $line) {
                $itemId = (int) $line->receiving_report_item_id;
                $qty = max(0, (int) ($line->remaining_qty ?? $line->quantity ?? 0));
                $article = trim((string) ($line->article ?? 'Received item'));

                if ($qty < 1) {
                    $result['skipped'][] = [
                        'id' => $itemId,
                        'article' => $article,
                        'reason' => 'Nothing left to stock',
                    ];
                    continue;
                }

                // Re-check remaining inside the transaction.
                $fresh = ReceivableStockLines::findPendingItem($itemId);
                if (! $fresh) {
                    $result['skipped'][] = [
                        'id' => $itemId,
                        'article' => $article,
                        'reason' => 'Already stocked',
                    ];
                    continue;
                }
                $qty = max(0, (int) ($fresh->remaining_qty ?? $fresh->quantity ?? 0));
                if ($qty < 1) {
                    $result['skipped'][] = [
                        'id' => $itemId,
                        'article' => $article,
                        'reason' => 'Already stocked',
                    ];
                    continue;
                }

                $categoryId = self::resolveCategoryId($article, $defaultCategoryId, $categories);
                $name = self::uniqueBulkName($article, $storageRoomId, (string) ($fresh->rr_number ?? ''));
                $condition = self::mapCondition($fresh->condition ?? null);
                $unitCost = $fresh->unit_cost !== null ? (float) $fresh->unit_cost : null;
                $purchaseDate = $fresh->purchase_date
                    ? substr((string) $fresh->purchase_date, 0, 10)
                    : now()->toDateString();
                $lotCode = 'RR'.$fresh->receiving_report_id.'-L'.$itemId;

                $payload = [
                    'equipment_category_id' => $categoryId,
                    'equipment_room_id' => $storageRoomId,
                    'equipment_name' => $name,
                    'equipment_quantity' => $qty,
                    'equipment_tracking_mode' => 'Bulk',
                    'equipment_condition_status' => $condition,
                    'equipment_inventory_status' => 'Active',
                    'equipment_purchase_date' => $purchaseDate,
                    'equipment_acquired_date' => now()->toDateString(),
                    'equipment_purchase_cost' => $unitCost !== null ? round($unitCost * $qty, 2) : null,
                    'equipment_is_borrowable' => 0,
                    'equipment_placement_zone' => 'Holding',
                    'equipment_current_location' => 'Holding',
                    'equipment_position_x' => 50,
                    'equipment_position_y' => 90,
                    'equipment_created_at' => now(),
                ];

                if (Schema::hasColumn('equipment_table', 'equipment_supplier_id') && ! empty($fresh->supplier_id)) {
                    $payload['equipment_supplier_id'] = (int) $fresh->supplier_id;
                }
                if (Schema::hasColumn('equipment_table', 'equipment_stocked_by') && $stockedBy) {
                    $payload['equipment_stocked_by'] = $stockedBy;
                }
                if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
                    $payload['equipment_receiving_report_item_id'] = $itemId;
                }
                if (Schema::hasColumn('equipment_table', 'equipment_stock_lot_code')) {
                    $payload['equipment_stock_lot_code'] = $lotCode;
                }

                $equipmentId = (int) DB::table('equipment_table')->insertGetId(
                    collect($payload)
                        ->filter(fn ($value, $column) => Schema::hasColumn('equipment_table', $column))
                        ->all()
                );

                EquipmentQrCodes::assignIfEligible($equipmentId);

                if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                    DB::table('receiving_report_items_table')
                        ->where('receiving_report_item_id', $itemId)
                        ->where(function ($q) {
                            $q->whereNull('receiving_report_item_equipment_id')
                                ->orWhere('receiving_report_item_equipment_id', 0);
                        })
                        ->update([
                            'receiving_report_item_equipment_id' => $equipmentId,
                        ]);
                }

                $replacesId = self::resolveReplacedEquipmentIdFromRr((int) $fresh->receiving_report_id);
                if (
                    $replacesId > 0
                    && Schema::hasColumn('equipment_table', 'equipment_replaces_id')
                ) {
                    DB::table('equipment_table')
                        ->where('equipment_id', $equipmentId)
                        ->update(['equipment_replaces_id' => $replacesId]);
                    if (Schema::hasColumn('equipment_table', 'equipment_replaced_by_id')) {
                        DB::table('equipment_table')
                            ->where('equipment_id', $replacesId)
                            ->update(['equipment_replaced_by_id' => $equipmentId]);
                    }
                }

                $result['created_ids'][] = $equipmentId;
                $result['stocked_lines']++;
                $result['stocked_qty'] += $qty;
            }
        });

        return $result;
    }

    private static function defaultCategoryId(): ?int
    {
        if (! Schema::hasTable('equipment_categories_table')) {
            return null;
        }

        $id = DB::table('equipment_categories_table')
            ->orderBy('equipment_category_id')
            ->value('equipment_category_id');

        return $id ? (int) $id : null;
    }

    /**
     * @return Collection<string, int> lowercase name => id
     */
    private static function categoryLookup(): Collection
    {
        if (! Schema::hasTable('equipment_categories_table')) {
            return collect();
        }

        return DB::table('equipment_categories_table')
            ->get(['equipment_category_id', 'equipment_category_name'])
            ->mapWithKeys(fn ($row) => [
                mb_strtolower(trim((string) $row->equipment_category_name)) => (int) $row->equipment_category_id,
            ]);
    }

    private static function resolveCategoryId(string $article, int $fallback, Collection $categories): int
    {
        $needle = mb_strtolower($article);
        foreach ($categories as $name => $id) {
            if ($name !== '' && (str_contains($needle, $name) || str_contains($name, $needle))) {
                return (int) $id;
            }
        }

        // Light keyword hints for common campus stock.
        $hints = [
            'computer' => ['mouse', 'keyboard', 'monitor', 'laptop', 'cpu', 'pc', 'printer'],
            'audio' => ['speaker', 'projector', 'microphone', 'tv', 'lcd'],
            'furniture' => ['chair', 'table', 'desk', 'cabinet'],
        ];
        foreach ($hints as $categoryFragment => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($needle, $keyword)) {
                    foreach ($categories as $name => $id) {
                        if (str_contains($name, $categoryFragment)) {
                            return (int) $id;
                        }
                    }
                }
            }
        }

        return $fallback;
    }

    private static function uniqueBulkName(string $article, int $roomId, string $rrNumber): string
    {
        $base = Str::limit(trim($article) !== '' ? trim($article) : 'Received item', 200, '');
        $exists = DB::table('equipment_table')
            ->where('equipment_room_id', $roomId)
            ->whereRaw('LOWER(equipment_name) = ?', [mb_strtolower($base)])
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->exists();

        if (! $exists) {
            return $base;
        }

        $suffix = $rrNumber !== '' ? ' ('.$rrNumber.')' : ' ('.now()->format('Ymd-His').')';

        return Str::limit($base.$suffix, 255, '');
    }

    private static function mapCondition(?string $condition): string
    {
        $value = mb_strtolower(trim((string) $condition));
        if ($value === '' || in_array($value, ['good', 'ok', 'accepted', 'pass'], true)) {
            return 'Good';
        }
        if (in_array($value, ['fair', 'used'], true)) {
            return 'Fair';
        }
        if (in_array($value, ['damaged', 'defect', 'defective'], true)) {
            return 'Damaged';
        }

        return 'Good';
    }

    private static function resolveReplacedEquipmentIdFromRr(int $receivingReportId): int
    {
        if (
            $receivingReportId < 1
            || ! Schema::hasTable('receiving_reports_table')
            || ! Schema::hasTable('procurement_request_items_table')
        ) {
            return 0;
        }

        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $receivingReportId)->first();
        if (! $rr) {
            return 0;
        }

        $procurementRequestId = (int) ($rr->receiving_report_procurement_request_id ?? 0);
        if ($procurementRequestId < 1 && Schema::hasColumn('receiving_reports_table', 'receiving_report_ris_id')) {
            $risId = (int) ($rr->receiving_report_ris_id ?? 0);
            if ($risId > 0 && Schema::hasTable('requisition_issue_slip_table')) {
                $procurementRequestId = (int) DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $risId)
                    ->value('ris_procurement_request_id');
            }
        }

        if ($procurementRequestId < 1) {
            return 0;
        }

        return (int) DB::table('procurement_request_items_table')
            ->where('procurement_request_id', $procurementRequestId)
            ->whereNotNull('equipment_id')
            ->where('equipment_id', '>', 0)
            ->orderBy('procurement_request_item_id')
            ->value('equipment_id');
    }
}
