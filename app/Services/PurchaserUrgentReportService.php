<?php

namespace App\Services;

use App\Support\PurchaserAttentionSummary;
use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\RoomCategories;
use Illuminate\Support\Facades\DB;

class PurchaserUrgentReportService
{
    /**
     * @return array{success: bool, available: int, pending_replacement_requests: int}
     */
    public function summary(): array
    {
        $counts = PurchaserAttentionSummary::counts();

        return [
            'success' => true,
            'available' => $counts['availableUrgentReports'],
            'pending_replacement_requests' => $counts['pendingReplacementRequests'],
        ];
    }

    /**
     * @param  array{
     *     search?: string,
     *     status?: string,
     *     archive?: bool,
     *     limit?: int
     * }  $filters
     * @return array{success: bool, reports: array<int, array<string, mixed>>, total: int}
     */
    public function listReports(array $filters = []): array
    {
        $limit = min((int) ($filters['limit'] ?? 50), 100);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $showArchive = (bool) ($filters['archive'] ?? false);

        $query = $this->urgentReportsQuery($search, $status, $showArchive);
        $total = (clone $query)->count();
        $reports = $query->limit($limit)->get();

        ReportItems::attachToReports($reports);

        return [
            'success' => true,
            'reports' => $reports
                ->map(fn ($report) => $this->serializeReport($report))
                ->values()
                ->all(),
            'total' => $total,
        ];
    }

    /**
     * @return array{success: bool, report?: array<string, mixed>, message?: string}
     */
    public function getReport(int $id): array
    {
        $report = $this->baseReportQuery()
            ->where('reports_table.report_id', $id)
            ->where('reports_table.report_urgency_level', 'Urgent')
            ->first();

        if (! $report) {
            return [
                'success' => false,
                'message' => 'Urgent report not found.',
            ];
        }

        ReportItems::ensureLegacyItem($report);
        $items = ReportItems::forReport($id);
        $report->report_items = $items;
        $report->equipment_display = ReportItems::labelForReport($report, $items);
        $report->issue_display = ReportItems::issueLabelForReport($report, $items);

        return [
            'success' => true,
            'report' => $this->serializeReport($report),
        ];
    }

    /**
     * @return array{success: bool, message: string, report?: array<string, mixed>}
     */
    public function acceptReport(int $reportId, int $purchaserId): array
    {
        $result = [
            'success' => false,
            'message' => 'Unable to accept this report.',
        ];

        DB::transaction(function () use ($reportId, $purchaserId, &$result) {
            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = ['success' => false, 'message' => 'Report not found.'];

                return;
            }

            if ($report->report_urgency_level !== 'Urgent') {
                $result = ['success' => false, 'message' => 'Only urgent reports can be accepted here.'];

                return;
            }

            if ($report->report_is_archived) {
                $result = ['success' => false, 'message' => 'Archived reports cannot be accepted.'];

                return;
            }

            if ($report->report_current_status !== 'Pending') {
                $result = ['success' => false, 'message' => 'This urgent report is no longer available.'];

                return;
            }

            if ($report->report_assigned_personnel_id !== null) {
                $result = ['success' => false, 'message' => 'Maintenance personnel is already handling this report.'];

                return;
            }

            if ($report->report_assigned_purchaser_id !== null) {
                $result = ['success' => false, 'message' => 'Another purchaser is already handling this report.'];

                return;
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_current_status' => 'Processing',
                    'report_assigned_purchaser_id' => $purchaserId,
                    'report_purchaser_assigned_at' => now(),
                    'report_updated_at' => now(),
                ]);

            ReportItems::ensureLegacyItem($report);
            ReportItems::syncAllItemStatuses((int) $reportId, 'Processing');

            $result = [
                'success' => true,
                'message' => 'Urgent report accepted successfully.',
                'report' => $this->getReportPayload($reportId),
            ];
        });

        return $result;
    }

    /**
     * @param  array<int, int>  $selectedItemIds
     * @return array{success: bool, message: string, report?: array<string, mixed>, partial?: bool}
     */
    public function resolveReport(
        int $reportId,
        int $purchaserId,
        ?string $notes,
        ?string $imagePath,
        array $selectedItemIds = []
    ): array {
        $result = [
            'success' => false,
            'message' => 'Unable to resolve this report.',
        ];

        $selected = collect($selectedItemIds)
            ->map(fn ($itemId) => (int) $itemId)
            ->filter(fn ($itemId) => $itemId > 0)
            ->unique()
            ->values();

        DB::transaction(function () use ($reportId, $purchaserId, $notes, $imagePath, $selected, &$result) {
            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = ['success' => false, 'message' => 'Report not found.'];

                return;
            }

            if ($report->report_is_archived) {
                $result = ['success' => false, 'message' => 'Archived reports cannot be resolved.'];

                return;
            }

            if (
                $report->report_urgency_level !== 'Urgent'
                || $report->report_current_status !== 'Processing'
                || (int) $report->report_assigned_purchaser_id !== $purchaserId
                || $report->report_assigned_personnel_id !== null
            ) {
                $result = ['success' => false, 'message' => 'You cannot resolve this urgent report.'];

                return;
            }

            ReportItems::ensureLegacyItem($report);
            $allItems = ReportItems::forReport($reportId);

            if ($allItems->count() > 1 && $selected->isNotEmpty()) {
                $targets = $allItems->whereIn('report_item_id', $selected->all());

                if ($targets->isEmpty()) {
                    $result = ['success' => false, 'message' => 'Select at least one equipment item to resolve.'];

                    return;
                }

                foreach ($targets as $item) {
                    ReportItems::updateItem((int) $item->report_item_id, 'Resolved', [
                        'report_item_resolution_notes' => $notes,
                        'report_item_resolution_image' => $imagePath,
                    ]);
                }

                $result = [
                    'success' => true,
                    'message' => 'Selected equipment marked as resolved. Remaining items can still be fixed or sent for replacement.',
                    'partial' => true,
                    'report' => $this->getReportPayload($reportId),
                ];

                return;
            }

            $this->applyUrgentReportStatusUpdate($reportId, $report, [
                'report_current_status' => 'Resolved',
                'report_resolution_notes' => $notes,
                'report_resolution_image' => $imagePath,
                'report_updated_at' => now(),
            ]);

            $result = [
                'success' => true,
                'message' => 'Urgent report resolved successfully.',
                'report' => $this->getReportPayload($reportId),
            ];
        });

        return $result;
    }

    /**
     * @param  array<int, int>  $selectedItemIds
     * @return array{success: bool, message: string, report?: array<string, mixed>, partial?: bool}
     */
    public function replaceReport(
        int $reportId,
        int $purchaserId,
        string $notes,
        ?string $imagePath,
        array $selectedItemIds = []
    ): array {
        $result = [
            'success' => false,
            'message' => 'Unable to send this report for replacement.',
        ];

        $selected = collect($selectedItemIds)
            ->map(fn ($itemId) => (int) $itemId)
            ->filter(fn ($itemId) => $itemId > 0)
            ->unique()
            ->values();

        $equipmentForReplacement = [];

        DB::transaction(function () use (
            $reportId,
            $purchaserId,
            $notes,
            $imagePath,
            $selected,
            &$equipmentForReplacement,
            &$result
        ) {
            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = ['success' => false, 'message' => 'Report not found.'];

                return;
            }

            if ($report->report_is_archived) {
                $result = ['success' => false, 'message' => 'Archived reports cannot be sent for replacement.'];

                return;
            }

            if (
                $report->report_urgency_level !== 'Urgent'
                || $report->report_current_status !== 'Processing'
                || (int) $report->report_assigned_purchaser_id !== $purchaserId
                || $report->report_assigned_personnel_id !== null
            ) {
                $result = ['success' => false, 'message' => 'You cannot send this urgent report for replacement.'];

                return;
            }

            ReportItems::ensureLegacyItem($report);
            $allItems = ReportItems::forReport($reportId);
            $partialReplacement = $allItems->count() > 1 && $selected->isNotEmpty();

            if ($partialReplacement) {
                $targets = $allItems->whereIn('report_item_id', $selected->all());

                if ($targets->isEmpty()) {
                    $result = ['success' => false, 'message' => 'Select at least one equipment item for replacement.'];

                    return;
                }

                foreach ($targets as $item) {
                    ReportItems::updateItem((int) $item->report_item_id, 'For Replacement', [
                        'report_item_replacement_notes' => $notes,
                        'report_item_replacement_image' => $imagePath,
                    ]);

                    if (! empty($item->report_item_equipment_id)) {
                        $equipmentForReplacement[] = (int) $item->report_item_equipment_id;
                    }
                }

                DB::table('reports_table')
                    ->where('report_id', $reportId)
                    ->update([
                        'report_replacement_notes' => $notes,
                        'report_replacement_image' => $imagePath,
                        'report_replacement_submitted_to_purchaser' => 1,
                        'report_updated_at' => now(),
                    ]);

                ReportItems::refreshParentStatus((int) $reportId);
            } else {
                $this->applyUrgentReportStatusUpdate($reportId, $report, [
                    'report_current_status' => 'For Replacement',
                    'report_replacement_notes' => $notes,
                    'report_replacement_image' => $imagePath,
                    'report_replacement_submitted_to_purchaser' => 1,
                    'report_updated_at' => now(),
                ]);

                $replacementItems = $allItems->filter(
                    fn ($item) => in_array($item->report_item_status, ReportItems::openStatuses(), true)
                );

                if ($replacementItems->isEmpty() && $allItems->isEmpty() && ! empty($report->report_equipment_id)) {
                    $replacementItems = collect([(object) [
                        'report_item_equipment_id' => $report->report_equipment_id,
                    ]]);
                }

                foreach ($replacementItems as $item) {
                    $equipmentId = (int) ($item->report_item_equipment_id ?? 0);
                    if ($equipmentId > 0) {
                        $equipmentForReplacement[] = $equipmentId;
                    }
                }
            }

            $procurementRequestExists = DB::table('procurement_requests_table')
                ->where('procurement_request_report_id', $reportId)
                ->exists();

            if (! $procurementRequestExists) {
                DB::table('procurement_requests_table')->insert([
                    'procurement_request_report_id' => $reportId,
                    'procurement_request_status' => 'Pending',
                    'procurement_request_created_by' => $purchaserId,
                    'procurement_request_created_at' => now(),
                ]);
            }

            $result = [
                'success' => true,
                'message' => $partialReplacement
                    ? 'Selected equipment submitted for replacement. Other items on this report can still be resolved separately.'
                    : 'Urgent report sent for replacement successfully.',
                'partial' => $partialReplacement,
                'report' => $this->getReportPayload($reportId),
            ];
        });

        foreach (array_unique($equipmentForReplacement) as $equipmentId) {
            ReportGrouping::markEquipmentForReplacement((int) $equipmentId);
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, report?: array<string, mixed>}
     */
    public function rejectReport(int $reportId, string $notes): array
    {
        $result = [
            'success' => false,
            'message' => 'Unable to reject this report.',
        ];

        DB::transaction(function () use ($reportId, $notes, &$result) {
            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = ['success' => false, 'message' => 'Report not found.'];

                return;
            }

            if ($report->report_urgency_level !== 'Urgent') {
                $result = ['success' => false, 'message' => 'Only urgent reports can be rejected here.'];

                return;
            }

            if ($report->report_is_archived) {
                $result = ['success' => false, 'message' => 'Archived reports cannot be rejected.'];

                return;
            }

            if ($report->report_current_status !== 'Pending') {
                $result = ['success' => false, 'message' => 'This urgent report is no longer available.'];

                return;
            }

            if ($report->report_assigned_personnel_id !== null) {
                $result = ['success' => false, 'message' => 'Maintenance personnel is already handling this report.'];

                return;
            }

            if ($report->report_assigned_purchaser_id !== null) {
                $result = ['success' => false, 'message' => 'Another purchaser is already handling this report.'];

                return;
            }

            $this->applyUrgentReportStatusUpdate($reportId, $report, [
                'report_current_status' => 'Rejected',
                'report_rejection_notes' => $notes,
                'report_updated_at' => now(),
            ]);

            $result = [
                'success' => true,
                'message' => 'Urgent report rejected successfully.',
                'report' => $this->getReportPayload($reportId),
            ];
        });

        return $result;
    }

    /**
     * @return array{success: bool, message: string, report?: array<string, mixed>}
     */
    public function archiveReport(int $reportId, int $purchaserId): array
    {
        $result = [
            'success' => false,
            'message' => 'Unable to archive this report.',
        ];

        DB::transaction(function () use ($reportId, $purchaserId, &$result) {
            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = ['success' => false, 'message' => 'Report not found.'];

                return;
            }

            if ($report->report_urgency_level !== 'Urgent') {
                $result = ['success' => false, 'message' => 'Only urgent reports can be archived here.'];

                return;
            }

            if ((int) $report->report_assigned_purchaser_id !== $purchaserId) {
                $isOpenRejected = $report->report_current_status === 'Rejected'
                    && $report->report_assigned_purchaser_id === null;

                if (! $isOpenRejected) {
                    $result = ['success' => false, 'message' => 'You can only archive urgent reports assigned to you.'];

                    return;
                }
            }

            if ($report->report_assigned_personnel_id !== null) {
                $result = ['success' => false, 'message' => 'This report belongs to maintenance personnel.'];

                return;
            }

            if (! in_array($report->report_current_status, ['Resolved', 'Rejected', 'For Replacement'], true)) {
                $result = ['success' => false, 'message' => 'Only completed urgent reports can be archived.'];

                return;
            }

            if ($report->report_is_archived) {
                $result = ['success' => false, 'message' => 'This urgent report is already archived.'];

                return;
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_is_archived' => 1,
                    'report_updated_at' => now(),
                ]);

            $result = [
                'success' => true,
                'message' => 'Urgent report archived successfully.',
                'report' => $this->getReportPayload($reportId),
            ];
        });

        return $result;
    }

    /**
     * @return array{success: bool, message: string, report?: array<string, mixed>}
     */
    public function restoreReport(int $reportId, int $purchaserId): array
    {
        $result = [
            'success' => false,
            'message' => 'Unable to restore this report.',
        ];

        DB::transaction(function () use ($reportId, $purchaserId, &$result) {
            $report = DB::table('reports_table')
                ->where('report_id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = ['success' => false, 'message' => 'Report not found.'];

                return;
            }

            if (
                $report->report_urgency_level !== 'Urgent'
                || (int) $report->report_assigned_purchaser_id !== $purchaserId
                || $report->report_assigned_personnel_id !== null
                || ! $report->report_is_archived
            ) {
                $result = ['success' => false, 'message' => 'You cannot restore this urgent report.'];

                return;
            }

            DB::table('reports_table')
                ->where('report_id', $reportId)
                ->update([
                    'report_is_archived' => 0,
                    'report_updated_at' => now(),
                ]);

            $result = [
                'success' => true,
                'message' => 'Urgent report restored successfully.',
                'report' => $this->getReportPayload($reportId),
            ];
        });

        return $result;
    }

    private function urgentReportsQuery(string $search, string $status, bool $showArchive)
    {
        $query = DB::table('reports_table')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            ->leftJoin('users_table as assigned_personnel', 'reports_table.report_assigned_personnel_id', '=', 'assigned_personnel.user_id')
            ->leftJoin('users_table as assigned_purchaser', 'reports_table.report_assigned_purchaser_id', '=', 'assigned_purchaser.user_id')
            ->where('reports_table.report_urgency_level', 'Urgent');

        if ($search !== '') {
            $ticketId = ReportGrouping::parseTicketSearch($search);

            $query->where(function ($subQuery) use ($search, $ticketId) {
                $subQuery
                    ->where('reports_table.report_id', 'LIKE', '%'.$search.'%')
                    ->orWhere('equipment_table.equipment_name', 'LIKE', $search.'%')
                    ->orWhere('rooms_table.room_name', 'LIKE', $search.'%')
                    ->orWhere('reporters_table.reporter_full_name', 'LIKE', $search.'%');

                if ($ticketId !== null) {
                    $subQuery->orWhere('reports_table.report_id', $ticketId);
                }
            });
        }

        if ($status !== '') {
            $query->where('reports_table.report_current_status', $status);
        }

        if ($showArchive) {
            $query->where('reports_table.report_is_archived', true);
        } else {
            $query->where('reports_table.report_is_archived', false);
            $query->where(function ($groupQuery) {
                $groupQuery
                    ->whereNull('reports_table.report_equipment_id')
                    ->orWhereNotIn(
                        'reports_table.report_current_status',
                        ReportGrouping::groupedStatuses()
                    )
                    ->orWhereRaw(
                        'reports_table.report_id = (
                            SELECT MAX(duplicate_reports.report_id)
                            FROM reports_table AS duplicate_reports
                            WHERE duplicate_reports.report_equipment_id = reports_table.report_equipment_id
                              AND duplicate_reports.report_room_id = reports_table.report_room_id
                              AND duplicate_reports.report_is_archived = 0
                              AND duplicate_reports.report_current_status IN (?, ?, ?, ?)
                              AND '.ReportGrouping::groupBucketSql('duplicate_reports').'
                                = '.ReportGrouping::groupBucketSql('reports_table').'
                        )',
                        ReportGrouping::groupedStatuses()
                    );
            });
        }

        return $query
            ->orderByDesc('reports_table.report_submitted_at')
            ->select([
                'reports_table.*',
                'rooms_table.room_name',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'reporters_table.reporter_full_name',
                'reporters_table.reporter_employee_id',
                'assigned_personnel.user_full_name as assigned_personnel_name',
                'assigned_purchaser.user_full_name as assigned_purchaser_name',
            ]);
    }

    private function baseReportQuery()
    {
        return DB::table('reports_table')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            ->leftJoin('users_table as assigned_personnel', 'reports_table.report_assigned_personnel_id', '=', 'assigned_personnel.user_id')
            ->leftJoin('users_table as assigned_purchaser', 'reports_table.report_assigned_purchaser_id', '=', 'assigned_purchaser.user_id')
            ->select(
                'reports_table.*',
                'rooms_table.room_name',
                'equipment_table.equipment_name',
                'reporters_table.reporter_full_name',
                'reporters_table.reporter_employee_id',
                'assigned_personnel.user_full_name as assigned_personnel_name',
                'assigned_purchaser.user_full_name as assigned_purchaser_name'
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function getReportPayload(int $id): array
    {
        $payload = $this->getReport($id);

        return $payload['report'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReport(object $report): array
    {
        $items = collect($report->report_items ?? []);

        return [
            'id' => (int) $report->report_id,
            'ticket_code' => ReportGrouping::ticketCode($report),
            'status' => (string) ($report->report_current_status ?? 'Pending'),
            'urgency' => (string) ($report->report_urgency_level ?? 'Urgent'),
            'room' => (string) ($report->room_name ?? ''),
            'room_id' => isset($report->report_room_id) ? (int) $report->report_room_id : null,
            'equipment_display' => (string) ($report->equipment_display
                ?? ReportItems::labelForReport($report, $items)),
            'issue_display' => (string) ($report->issue_display
                ?? ReportItems::issueLabelForReport($report, $items)),
            'description' => $report->report_problem_description ?? null,
            'reporter_name' => $report->reporter_full_name ?? null,
            'reporter_employee_id' => $report->report_reporter_employee_id ?? null,
            'assigned_personnel_name' => $report->assigned_personnel_name ?? null,
            'assigned_personnel_id' => isset($report->report_assigned_personnel_id)
                ? (int) $report->report_assigned_personnel_id
                : null,
            'assigned_purchaser_name' => $report->assigned_purchaser_name ?? null,
            'assigned_purchaser_id' => isset($report->report_assigned_purchaser_id)
                ? (int) $report->report_assigned_purchaser_id
                : null,
            'is_archived' => (bool) ($report->report_is_archived ?? false),
            'submitted_at' => $report->report_submitted_at ?? null,
            'updated_at' => $report->report_updated_at ?? null,
            'preferred_action_date' => $report->report_preferred_action_date ?? null,
            'item_count' => $items->count(),
            'items' => $items->map(fn ($item) => $this->serializeItem($item))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(object $item): array
    {
        $trackingMode = $item->equipment_tracking_mode ?? 'Individual';
        $zone = $item->equipment_placement_zone
            ?? $item->equipment_current_location
            ?? null;
        $roomType = $item->room_type ?? null;
        $isStorageRoom = RoomCategories::isStorageType($roomType);
        $qty = $item->equipment_quantity ?? null;

        return [
            'id' => (int) $item->report_item_id,
            'equipment_id' => isset($item->report_item_equipment_id)
                ? (int) $item->report_item_equipment_id
                : null,
            'equipment_name' => ReportItems::displayName($item),
            'unlisted_name' => $item->report_item_unlisted_equipment_name ?? null,
            'issue' => $item->report_item_suggested_issue ?? null,
            'description' => $item->report_item_problem_description ?? null,
            'status' => (string) ($item->report_item_status ?? 'Pending'),
            'asset_tag' => $item->equipment_asset_tag ?? null,
            'brand' => $item->equipment_brand_name ?? null,
            'model' => $item->equipment_model ?? null,
            'serial_number' => $item->equipment_serial_number ?? null,
            'category' => $item->equipment_category_name ?? null,
            'condition' => $item->equipment_condition_status ?? null,
            'inventory_status' => $item->equipment_inventory_status ?? null,
            'quantity' => $qty,
            'tracking_mode' => $trackingMode,
            'qty_mode' => trim((string) ($qty ?? '1')).' · '.$trackingMode,
            'warranty_expiration' => $item->equipment_warranty_expiration ?? null,
            'acquired_date' => $item->equipment_acquired_date ?? null,
            'purchase_date' => $item->equipment_purchase_date ?? null,
            'purchase_cost' => $item->equipment_purchase_cost ?? null,
            'room' => $item->room_name ?? null,
            'room_type' => $roomType,
            'zone' => $zone,
            'borrowable' => $isStorageRoom ? false : (bool) ($item->equipment_is_borrowable ?? false),
            'placement' => $zone,
            'image' => $item->report_item_uploaded_image ?? null,
            'is_listed' => ! empty($item->report_item_equipment_id),
        ];
    }

    private function applyUrgentReportStatusUpdate(int $id, object $report, array $updates): void
    {
        DB::table('reports_table')
            ->where('report_id', $id)
            ->update($updates);

        if (! ReportItems::tableExists()) {
            ReportGrouping::syncOpenSiblings($report, $updates);

            return;
        }

        ReportItems::ensureLegacyItem($report);

        $itemExtra = [];
        $status = $updates['report_current_status'] ?? null;

        if (array_key_exists('report_resolution_notes', $updates)) {
            $itemExtra['report_item_resolution_notes'] = $updates['report_resolution_notes'];
        }
        if (array_key_exists('report_resolution_image', $updates)) {
            $itemExtra['report_item_resolution_image'] = $updates['report_resolution_image'];
        }
        if (array_key_exists('report_replacement_notes', $updates)) {
            $itemExtra['report_item_replacement_notes'] = $updates['report_replacement_notes'];
        }
        if (array_key_exists('report_replacement_image', $updates)) {
            $itemExtra['report_item_replacement_image'] = $updates['report_replacement_image'];
        }
        if (array_key_exists('report_rejection_notes', $updates)) {
            $itemExtra['report_item_rejection_notes'] = $updates['report_rejection_notes'];
        }

        if ($status) {
            $payload = array_merge($itemExtra, [
                'report_item_status' => $status,
                'report_item_updated_at' => now(),
            ]);

            $query = DB::table('report_items_table')
                ->where('report_id', $id);

            if (in_array($status, ReportItems::terminalStatuses(), true)) {
                $query->whereIn('report_item_status', ReportItems::openStatuses());
            }

            $query->update($payload);
            ReportItems::refreshParentStatus($id);
        }

        ReportGrouping::syncOpenSiblings($report, $updates);
    }
}
