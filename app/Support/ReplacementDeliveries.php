<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tells the Administrator and Maintenance when equipment bought for a replacement request arrives.
 */
class ReplacementDeliveries
{
    /**
     * Replacement request behind an RR: set directly on the RR, or via its ATP → RIS.
     */
    public static function requestIdForRr(object $rr): int
    {
        $requestId = (int) ($rr->receiving_report_procurement_request_id ?? 0);
        if ($requestId > 0) {
            return $requestId;
        }

        $risId = (int) ($rr->receiving_report_ris_id ?? 0);
        $atpId = (int) ($rr->receiving_report_atp_id ?? 0);
        if ($risId < 1 && $atpId > 0 && Schema::hasTable('authority_to_purchase_table')) {
            $risId = (int) DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $atpId)
                ->value('authority_purchase_ris_id');
        }

        if ($risId < 1 || ! Schema::hasTable('requisition_issue_slip_table')) {
            return 0;
        }

        return (int) DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->value('ris_procurement_request_id');
    }

    public static function replacedEquipmentIdForRr(int $receivingReportId): int
    {
        if ($receivingReportId < 1 || ! ReplacementRequestBasket::tableExists()) {
            return 0;
        }

        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $receivingReportId)->first();
        $requestId = $rr ? self::requestIdForRr($rr) : 0;
        if ($requestId < 1) {
            return 0;
        }

        return (int) DB::table('procurement_request_items_table')
            ->where('procurement_request_id', $requestId)
            ->whereNotNull('equipment_id')
            ->where('equipment_id', '>', 0)
            ->orderBy('procurement_request_item_id')
            ->value('equipment_id');
    }

    /**
     * Notify both portals about the accepted lines just counted on a replacement RR.
     * Returns false when the RR is not for a replacement or nothing usable arrived.
     *
     * @param  array<int, int>|null  $countedItemIds  RR lines verified in this count (null = every line)
     */
    public static function notifyDelivered(object $rr, ?array $countedItemIds, bool $partial): bool
    {
        if ($countedItemIds === [] || ! Schema::hasTable('procurement_requests_table')) {
            return false;
        }

        $requestId = self::requestIdForRr($rr);
        if ($requestId < 1) {
            return false;
        }

        $delivered = self::acceptedLines((int) $rr->receiving_report_id, $countedItemIds);
        if ($delivered->isEmpty()) {
            return false;
        }

        $request = DB::table('procurement_requests_table')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->where('procurement_requests_table.procurement_request_id', $requestId)
            ->select(
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_report_id',
                'procurement_requests_table.procurement_request_created_at',
                'reports_table.report_urgency_level',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'rooms_table.room_name'
            )
            ->first();
        if (! $request) {
            return false;
        }

        $code = ReplacementRequestCode::code($request);
        $rrRef = trim((string) ($rr->receiving_report_form_number ?? '')) ?: 'RR #'.$rr->receiving_report_id;
        $arrived = $delivered
            ->map(fn ($line) => $line->accepted.' × '.$line->article)
            ->implode(', ');
        $replaces = self::replacedLabel($requestId, $request);

        $summary = $arrived.' arrived on '.$rrRef.' for replacement request '.$code
            .($replaces !== '' ? ', replacing '.$replaces : '').'.'
            .($partial ? ' Some items are still on back order.' : '');
        $title = $partial ? 'Replacement equipment partly delivered' : 'Replacement equipment delivered';
        $rrId = (int) $rr->receiving_report_id;

        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_ADMIN,
            $title,
            $summary.' Add it to inventory, then transfer it to where it is needed.',
            'replacement_delivered',
            'RR',
            $rrId,
            '/admin/inventory?rr='.$rrId
        );

        $reportId = (int) ($request->procurement_request_report_id ?? 0);
        $maintenanceUrl = $reportId > 0 ? '/maintenance/reports/details/'.$reportId : '/maintenance/reports/replacement';
        $maintenanceMessage = $summary.' Once the Administrator adds it to inventory, you can transfer it and swap out the old unit.';
        $adminIds = WorkflowNotifier::userIdsForRole(WorkflowNotifier::ROLE_ADMIN);
        $maintenanceIds = WorkflowNotifier::userIdsForRole(WorkflowNotifier::ROLE_MAINTENANCE);

        if ($maintenanceIds === []) {
            WorkflowNotifier::toRole(WorkflowNotifier::ROLE_MAINTENANCE, $title, $maintenanceMessage, 'replacement_delivered', 'PROC', $requestId, $maintenanceUrl);
        }
        // Users who are also Administrators already got the Admin copy.
        foreach (array_diff($maintenanceIds, $adminIds) as $userId) {
            WorkflowNotifier::toUser($userId, WorkflowNotifier::ROLE_MAINTENANCE, $title, $maintenanceMessage, 'replacement_delivered', 'PROC', $requestId, $maintenanceUrl);
        }

        return true;
    }

    /**
     * @param  array<int, int>|null  $itemIds
     * @return Collection<int, object{article: string, accepted: int}>
     */
    private static function acceptedLines(int $rrId, ?array $itemIds): Collection
    {
        $table = 'receiving_report_items_table';
        if (! Schema::hasTable($table)) {
            return collect();
        }

        $query = DB::table($table)->where('receiving_report_id', $rrId)->orderBy('receiving_report_item_id');
        if ($itemIds !== null) {
            $query->whereIn('receiving_report_item_id', $itemIds);
        }
        $hasDamaged = Schema::hasColumn($table, 'receiving_report_item_damaged_qty');

        return $query->get()
            ->map(function ($item) use ($hasDamaged) {
                $accepted = (int) ($item->receiving_report_item_quantity ?? 0)
                    - ($hasDamaged ? (int) ($item->receiving_report_item_damaged_qty ?? 0) : 0);

                return (object) [
                    'article' => trim((string) ($item->receiving_report_item_article ?? '')) ?: 'Received item',
                    'accepted' => max(0, $accepted),
                ];
            })
            ->filter(fn ($line) => $line->accepted > 0)
            ->values();
    }

    /** Public label of equipment a replacement request is meant to replace. */
    public static function replacesLabelForRequest(int $requestId): string
    {
        if ($requestId < 1 || ! Schema::hasTable('procurement_requests_table')) {
            return '';
        }

        $request = DB::table('procurement_requests_table')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->where('procurement_requests_table.procurement_request_id', $requestId)
            ->select(
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name',
                'rooms_table.room_name'
            )
            ->first();

        return $request ? self::replacedLabel($requestId, $request) : '';
    }

    private static function replacedLabel(int $requestId, object $request): string
    {
        $lines = ReplacementRequestBasket::itemsForRequest($requestId);
        if ($lines->isEmpty()) {
            $name = trim((string) ($request->equipment_name ?? $request->report_unlisted_equipment_name ?? ''));
            if ($name === '') {
                return '';
            }
            $room = trim((string) ($request->room_name ?? ''));

            return $name.($room !== '' ? ' in '.$room : '');
        }

        $labels = $lines
            ->map(function ($line) {
                $room = trim((string) ($line->room_name ?? ''));

                return $line->display_name.($room !== '' ? ' in '.$room : '');
            })
            ->unique()
            ->values();

        return $labels->count() > 3
            ? $labels->take(3)->implode(', ').' +'.($labels->count() - 3).' more'
            : $labels->implode(', ');
    }
}
