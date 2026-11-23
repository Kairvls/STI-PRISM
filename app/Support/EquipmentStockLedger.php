<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * On-hand quantity is the sum of signed movements. equipment_quantity is a cache.
 */
class EquipmentStockLedger
{
    public const RECEIPT = 'receipt';

    public const TRANSFER_OUT = 'transfer_out';

    public const TRANSFER_IN = 'transfer_in';

    public const ISSUE = 'issue';

    public const RETURNED = 'return';

    public const ADJUSTMENT = 'adjustment';

    public const SPOILAGE = 'spoilage';

    public const OPENING_BALANCE = 'opening_balance';

    public const TYPES = [
        self::RECEIPT,
        self::TRANSFER_OUT,
        self::TRANSFER_IN,
        self::ISSUE,
        self::RETURNED,
        self::ADJUSTMENT,
        self::SPOILAGE,
        self::OPENING_BALANCE,
    ];

    public const LABELS = [
        self::RECEIPT => 'Receipt',
        self::TRANSFER_OUT => 'Transfer out',
        self::TRANSFER_IN => 'Transfer in',
        self::ISSUE => 'Issue',
        self::RETURNED => 'Return',
        self::ADJUSTMENT => 'Adjustment',
        self::SPOILAGE => 'Spoilage',
        self::OPENING_BALANCE => 'Opening balance',
    ];

    public static function enabled(): bool
    {
        return (bool) config('inventory.ledger_v1', true)
            && Schema::hasTable('equipment_stock_movements');
    }

    public static function balance(int $equipmentId): int
    {
        if ($equipmentId < 1 || ! Schema::hasTable('equipment_stock_movements')) {
            return 0;
        }

        return (int) DB::table('equipment_stock_movements')
            ->where('equipment_id', $equipmentId)
            ->sum('quantity_delta');
    }

    /**
     * @param  array{room_id?: int|null, reference_type?: string|null, reference_id?: int|null, performed_by?: int|null, remarks?: string|null}  $meta
     */
    public static function post(int $equipmentId, string $type, int $delta, array $meta = []): void
    {
        if (! self::enabled() || $equipmentId < 1 || $delta === 0) {
            return;
        }

        if (! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unknown stock movement.');
        }

        $next = self::balance($equipmentId) + $delta;
        if ($next < 0) {
            throw new RuntimeException('That quantity would take stock below zero.');
        }

        DB::table('equipment_stock_movements')->insert([
            'equipment_id' => $equipmentId,
            'movement_type' => $type,
            'quantity_delta' => $delta,
            'room_id' => $meta['room_id'] ?? null,
            'reference_type' => $meta['reference_type'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'performed_by' => $meta['performed_by'] ?? Auth::id(),
            'remarks' => isset($meta['remarks']) ? mb_substr((string) $meta['remarks'], 0, 1000) : null,
            'created_at' => now(),
        ]);

        if (Schema::hasTable('equipment_table')) {
            DB::table('equipment_table')
                ->where('equipment_id', $equipmentId)
                ->update(['equipment_quantity' => $next]);
        }
    }

    public static function recordReceipt(int $equipmentId, int $quantity, ?int $roomId = null, ?int $rrItemId = null, ?string $remarks = null): void
    {
        $quantity = max(1, $quantity);
        self::post($equipmentId, self::RECEIPT, $quantity, [
            'room_id' => $roomId,
            'reference_type' => $rrItemId ? 'receiving_report_item' : null,
            'reference_id' => $rrItemId,
            'remarks' => $remarks ?: 'Stocked into inventory',
        ]);
    }

    public static function history(int $equipmentId, int $limit = 40): Collection
    {
        if ($equipmentId < 1 || ! Schema::hasTable('equipment_stock_movements')) {
            return collect();
        }

        return DB::table('equipment_stock_movements')
            ->where('equipment_id', $equipmentId)
            ->orderByDesc('movement_id')
            ->limit($limit)
            ->get();
    }
};
