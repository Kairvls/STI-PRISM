<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ghost stock, warranty alerts, and SLA metrics for Lifecycle Pack v2.
 */
class EquipmentMonitoring
{
    /**
     * Active equipment with no RR/PO procurement link (excluding intentional non-procurement is hard;
     * treat missing receiving_report_item_id AND no legacy RR item link as ghost).
     *
     * @return Collection<int, object>
     */
    public static function ghostStock(int $limit = 25): Collection
    {
        if (! Schema::hasTable('equipment_table')) {
            return collect();
        }

        $query = DB::table('equipment_table as e')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->whereNotIn('e.equipment_inventory_status', ['Disposed'])
            ->orderByDesc('e.equipment_created_at')
            ->limit($limit);

        $select = [
            'e.equipment_id',
            'e.equipment_name',
            'e.equipment_asset_tag',
            'e.equipment_inventory_status',
            'e.equipment_created_at',
            'r.room_name',
        ];
        if (Schema::hasColumn('equipment_table', 'equipment_serial_number')) {
            $select[] = 'e.equipment_serial_number';
        }

        $query->select($select);

        if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            $query->where(function ($q) {
                $q->whereNull('e.equipment_receiving_report_item_id')
                    ->orWhere('e.equipment_receiving_report_item_id', 0);
            });
        }

        // Also exclude rows still linked via legacy RR item equipment_id.
        if (
            Schema::hasTable('receiving_report_items_table')
            && Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')
        ) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('receiving_report_items_table as ri')
                    ->whereColumn('ri.receiving_report_item_equipment_id', 'e.equipment_id');
            });
        }

        return $query->get()->map(function ($row) {
            $row->view_url = url('/maintenance/equipment/view/'.$row->equipment_id);
            $row->reason = 'No receiving report / purchase order link';

            return $row;
        });
    }

    public static function ghostStockCount(): int
    {
        if (! Schema::hasTable('equipment_table')) {
            return 0;
        }

        $query = DB::table('equipment_table as e')
            ->whereNotIn('e.equipment_inventory_status', ['Disposed']);

        if (Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')) {
            $query->where(function ($q) {
                $q->whereNull('e.equipment_receiving_report_item_id')
                    ->orWhere('e.equipment_receiving_report_item_id', 0);
            });
        }

        if (
            Schema::hasTable('receiving_report_items_table')
            && Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')
        ) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('receiving_report_items_table as ri')
                    ->whereColumn('ri.receiving_report_item_equipment_id', 'e.equipment_id');
            });
        }

        return (int) $query->count();
    }

    /**
     * @return Collection<int, object>
     */
    public static function warrantyAlerts(int $limit = 12, int $withinDays = 90): Collection
    {
        if (
            ! Schema::hasTable('equipment_table')
            || ! Schema::hasColumn('equipment_table', 'equipment_warranty_expiration')
        ) {
            return collect();
        }

        $select = [
            'e.equipment_id',
            'e.equipment_name',
            'e.equipment_asset_tag',
            'e.equipment_warranty_expiration',
            'e.equipment_inventory_status',
            'r.room_name',
            DB::raw('DATEDIFF(e.equipment_warranty_expiration, CURDATE()) as days_remaining'),
        ];
        if (Schema::hasColumn('equipment_table', 'equipment_serial_number')) {
            $select[] = 'e.equipment_serial_number';
        }

        return DB::table('equipment_table as e')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->whereNotNull('e.equipment_warranty_expiration')
            ->whereNotIn('e.equipment_inventory_status', ['Disposed'])
            ->whereRaw('DATEDIFF(e.equipment_warranty_expiration, CURDATE()) <= ?', [$withinDays])
            ->orderBy('e.equipment_warranty_expiration')
            ->limit($limit)
            ->get($select)
            ->map(function ($row) {
                $days = (int) ($row->days_remaining ?? 0);
                $row->view_url = url('/maintenance/equipment/view/'.$row->equipment_id);
                $row->suggest_action = $days < 0
                    ? 'Expired'
                    : ($days <= 30 ? $days.'d left' : 'Monitor');
                $row->is_expired = $days < 0;

                return $row;
            });
    }

    public static function warrantyAlertCount(int $withinDays = 90): int
    {
        if (
            ! Schema::hasTable('equipment_table')
            || ! Schema::hasColumn('equipment_table', 'equipment_warranty_expiration')
        ) {
            return 0;
        }

        return (int) DB::table('equipment_table')
            ->whereNotNull('equipment_warranty_expiration')
            ->whereNotIn('equipment_inventory_status', ['Disposed'])
            ->whereRaw('DATEDIFF(equipment_warranty_expiration, CURDATE()) <= ?', [$withinDays])
            ->count();
    }

    /**
     * @return array{
     *   rr_to_stock_avg_days: ?float,
     *   rr_to_stock_samples: int,
     *   report_to_repair_avg_days: ?float,
     *   report_to_repair_samples: int,
     *   pending_rr_stock: int,
     *   ghost_stock_count: int,
     *   warranty_alert_count: int
     * }
     */
    public static function slaSummary(): array
    {
        $rrSamples = [];
        if (
            Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_receiving_report_item_id')
            && Schema::hasTable('receiving_report_items_table')
            && Schema::hasTable('receiving_reports_table')
        ) {
            $rows = DB::table('equipment_table as e')
                ->join(
                    'receiving_report_items_table as ri',
                    'ri.receiving_report_item_id',
                    '=',
                    'e.equipment_receiving_report_item_id'
                )
                ->join(
                    'receiving_reports_table as rr',
                    'rr.receiving_report_id',
                    '=',
                    'ri.receiving_report_id'
                )
                ->whereNotNull('e.equipment_acquired_date')
                ->limit(500)
                ->get([
                    'e.equipment_acquired_date',
                    'rr.receiving_report_second_count_at',
                    'rr.receiving_report_date',
                    'rr.receiving_report_created_at',
                ]);

            foreach ($rows as $row) {
                $received = $row->receiving_report_second_count_at
                    ?? $row->receiving_report_date
                    ?? $row->receiving_report_created_at;
                $stocked = $row->equipment_acquired_date;
                if (! $received || ! $stocked) {
                    continue;
                }
                try {
                    $rrSamples[] = max(0, Carbon::parse($received)->diffInDays(Carbon::parse($stocked), false));
                } catch (\Throwable $e) {
                    // skip bad dates
                }
            }
        }

        $repairSamples = [];
        if (
            Schema::hasTable('equipment_maintenance_history_table')
            && Schema::hasTable('reports_table')
            && Schema::hasColumn('equipment_maintenance_history_table', 'equipment_maintenance_report_id')
        ) {
            $rows = DB::table('equipment_maintenance_history_table as m')
                ->join('reports_table as r', 'r.report_id', '=', 'm.equipment_maintenance_report_id')
                ->whereNotNull('m.equipment_maintenance_completed_at')
                ->limit(500)
                ->get([
                    'r.report_submitted_at',
                    'm.equipment_maintenance_completed_at',
                ]);

            foreach ($rows as $row) {
                if (! $row->report_submitted_at || ! $row->equipment_maintenance_completed_at) {
                    continue;
                }
                try {
                    $repairSamples[] = max(
                        0,
                        Carbon::parse($row->report_submitted_at)
                            ->diffInDays(Carbon::parse($row->equipment_maintenance_completed_at), false)
                    );
                } catch (\Throwable $e) {
                    // skip
                }
            }
        }

        return [
            'rr_to_stock_avg_days' => $rrSamples === [] ? null : round(array_sum($rrSamples) / count($rrSamples), 1),
            'rr_to_stock_samples' => count($rrSamples),
            'report_to_repair_avg_days' => $repairSamples === [] ? null : round(array_sum($repairSamples) / count($repairSamples), 1),
            'report_to_repair_samples' => count($repairSamples),
            'pending_rr_stock' => ReceivableStockLines::pending()->count(),
            'ghost_stock_count' => self::ghostStockCount(),
            'warranty_alert_count' => self::warrantyAlertCount(),
        ];
    }
}
