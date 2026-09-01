<?php

namespace App\Services;

use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\RoomCategories;
use Illuminate\Support\Facades\DB;

class MaintenanceReportService
{
    /**
     * @return array{success: bool, reports: array<int, array<string, mixed>>, total: int}
     */
    public function listReports(array $filters = []): array
    {
        $limit = min((int) ($filters['limit'] ?? 50), 100);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $urgency = trim((string) ($filters['urgency'] ?? ''));
        $showArchive = (bool) ($filters['archive'] ?? false);

        $query = DB::table('reports_table')
            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )
            ->leftJoin(
                'users_table as assigned_personnel',
                'reports_table.report_assigned_personnel_id',
                '=',
                'assigned_personnel.user_id'
            )
            ->leftJoin(
                'users_table as assigned_purchaser',
                'reports_table.report_assigned_purchaser_id',
                '=',
                'assigned_purchaser.user_id'
            );

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

        $query
            ->orderByRaw(
                "CASE WHEN reports_table.report_urgency_level = 'Urgent' THEN 0 ELSE 1 END"
            )
            ->orderByDesc('reports_table.report_updated_at')
            ->orderByDesc('reports_table.report_submitted_at')
            ->orderByDesc('reports_table.report_id')
            ->select(
                'reports_table.*',
                'rooms_table.room_name',
                'equipment_table.equipment_name',
                'reporters_table.reporter_full_name',
                'reporters_table.reporter_employee_id',
                'assigned_personnel.user_full_name as assigned_personnel_name',
                'assigned_purchaser.user_full_name as assigned_purchaser_name'
            );

        if ($status !== '') {
            $query->where('reports_table.report_current_status', $status);
        }

        if ($urgency !== '') {
            $query->where('reports_table.report_urgency_level', $urgency);
        }

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
            ->first();

        if (! $report) {
            return [
                'success' => false,
                'message' => 'Report not found.',
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
     * @param  array{
     *     status: string,
     *     remarks?: string|null,
     *     report_item_ids?: array<int, int|string>
     * }  $input
     * @return array{
     *     success: bool,
     *     message: string,
     *     report?: array<string, mixed>,
     *     partial?: bool
     * }
     */
    public function updateStatus(
        int $id,
        int $personnelId,
        array $input,
        ?string $imagePath = null
    ): array {
        $newStatus = (string) ($input['status'] ?? '');
        $remarks = trim((string) ($input['remarks'] ?? ''));
        $selectedItemIds = collect($input['report_item_ids'] ?? [])
            ->map(fn ($itemId) => (int) $itemId)
            ->filter(fn ($itemId) => $itemId > 0)
            ->unique()
            ->values();

        $equipmentForReplacement = [];
        $result = [
            'success' => false,
            'message' => 'Unable to update this report.',
        ];

        DB::transaction(function () use (
            $id,
            $personnelId,
            $newStatus,
            $remarks,
            $selectedItemIds,
            $imagePath,
            &$equipmentForReplacement,
            &$result
        ) {
            $report = DB::table('reports_table')
                ->where('report_id', $id)
                ->lockForUpdate()
                ->first();

            if (! $report) {
                $result = [
                    'success' => false,
                    'message' => 'Report not found.',
                ];

                return;
            }

            if ($report->report_is_archived) {
                $result = [
                    'success' => false,
                    'message' => 'Archived reports cannot be updated.',
                ];

                return;
            }

            $allowedTransitions = [
                'Pending' => ['Processing', 'Rejected'],
                'Processing' => ['Resolved', 'For Replacement'],
            ];

            if (
                ! isset($allowedTransitions[$report->report_current_status])
                || ! in_array($newStatus, $allowedTransitions[$report->report_current_status], true)
            ) {
                $result = [
                    'success' => false,
                    'message' => 'This status cannot be changed to the selected value.',
                ];

                return;
            }

            if ($report->report_current_status === 'Pending') {
                if ($report->report_assigned_purchaser_id !== null) {
                    $result = [
                        'success' => false,
                        'message' => 'The Purchaser is already handling this urgent report.',
                    ];

                    return;
                }

                if ($report->report_assigned_personnel_id !== null) {
                    $result = [
                        'success' => false,
                        'message' => 'Another maintenance personnel is already assigned to this report.',
                    ];

                    return;
                }

                if ($newStatus === 'Processing') {
                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'Processing',
                        'report_assigned_personnel_id' => $personnelId,
                        'report_updated_at' => now(),
                    ]);

                    $result = [
                        'success' => true,
                        'message' => 'Report is now being processed.',
                        'report' => $this->getReportPayload($id),
                    ];

                    return;
                }

                if ($newStatus === 'Rejected') {
                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'Rejected',
                        'report_rejection_notes' => $remarks !== '' ? $remarks : null,
                        'report_updated_at' => now(),
                    ]);

                    $result = [
                        'success' => true,
                        'message' => 'Report rejected successfully.',
                        'report' => $this->getReportPayload($id),
                    ];

                    return;
                }
            }

            if ($report->report_current_status === 'Processing') {
                if ($report->report_assigned_purchaser_id !== null) {
                    $result = [
                        'success' => false,
                        'message' => 'This urgent report is being handled by the Purchaser.',
                    ];

                    return;
                }

                if ((int) $report->report_assigned_personnel_id !== (int) $personnelId) {
                    $result = [
                        'success' => false,
                        'message' => 'You are not assigned to this report.',
                    ];

                    return;
                }

                ReportItems::ensureLegacyItem($report);
                $allItems = ReportItems::forReport($id);

                if (
                    ($newStatus === 'Resolved' || $newStatus === 'For Replacement')
                    && $allItems->count() > 1
                    && $selectedItemIds->isEmpty()
                ) {
                    $result = [
                        'success' => false,
                        'message' => 'Select at least one equipment item before updating status.',
                    ];

                    return;
                }

                if ($newStatus === 'Resolved') {
                    if ($allItems->count() > 1 && $selectedItemIds->isNotEmpty()) {
                        $targets = $allItems->whereIn('report_item_id', $selectedItemIds->all());

                        if ($targets->isEmpty()) {
                            $result = [
                                'success' => false,
                                'message' => 'Select at least one equipment item to resolve.',
                            ];

                            return;
                        }

                        foreach ($targets as $item) {
                            ReportItems::updateItem((int) $item->report_item_id, 'Resolved', [
                                'report_item_resolution_notes' => $remarks !== '' ? $remarks : null,
                                'report_item_resolution_image' => $imagePath,
                            ]);
                        }

                        $result = [
                            'success' => true,
                            'message' => 'Selected equipment marked as resolved. Remaining items can still be fixed or sent for replacement.',
                            'partial' => true,
                            'report' => $this->getReportPayload($id),
                        ];

                        return;
                    }

                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'Resolved',
                        'report_resolution_notes' => $remarks !== '' ? $remarks : null,
                        'report_resolution_image' => $imagePath,
                        'report_updated_at' => now(),
                    ]);

                    $result = [
                        'success' => true,
                        'message' => 'Report resolved successfully.',
                        'report' => $this->getReportPayload($id),
                    ];

                    return;
                }

                if ($newStatus === 'For Replacement') {
                    $partialReplacement = $allItems->count() > 1 && $selectedItemIds->isNotEmpty();

                    if ($partialReplacement) {
                        $targets = $allItems->whereIn('report_item_id', $selectedItemIds->all());

                        if ($targets->isEmpty()) {
                            $result = [
                                'success' => false,
                                'message' => 'Select at least one equipment item for replacement.',
                            ];

                            return;
                        }

                        foreach ($targets as $item) {
                            ReportItems::updateItem((int) $item->report_item_id, 'For Replacement', [
                                'report_item_replacement_notes' => $remarks !== '' ? $remarks : null,
                                'report_item_replacement_image' => $imagePath,
                            ]);

                            if (! empty($item->report_item_equipment_id)) {
                                $equipmentForReplacement[] = (int) $item->report_item_equipment_id;
                            }
                        }

                        DB::table('reports_table')
                            ->where('report_id', $id)
                            ->update([
                                'report_replacement_notes' => $remarks !== '' ? $remarks : null,
                                'report_replacement_image' => $imagePath,
                                'report_replacement_submitted_to_purchaser' => 1,
                                'report_updated_at' => now(),
                            ]);

                        ReportItems::refreshParentStatus($id);
                        $this->ensureReplacementSideEffects($id, $personnelId);

                        $result = [
                            'success' => true,
                            'message' => 'Selected equipment submitted for replacement. Other items on this report can still be resolved separately.',
                            'partial' => true,
                            'report' => $this->getReportPayload($id),
                        ];

                        return;
                    }

                    $this->applyReportStatusUpdate($id, $report, [
                        'report_current_status' => 'For Replacement',
                        'report_replacement_notes' => $remarks !== '' ? $remarks : null,
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

                    $this->ensureReplacementSideEffects($id, $personnelId);

                    $result = [
                        'success' => true,
                        'message' => 'Report submitted for replacement successfully.',
                        'report' => $this->getReportPayload($id),
                    ];

                    return;
                }
            }
        });

        foreach (array_unique($equipmentForReplacement) as $equipmentId) {
            ReportGrouping::markEquipmentForReplacement((int) $equipmentId);
        }

        return $result;
    }

    private function ensureReplacementSideEffects(int $reportId, int $personnelId): void
    {
        $existingNotification = DB::table('notifications_table')
            ->where('notification_type', 'replacement')
            ->where('notification_message', 'Report #'.$reportId.' requires replacement.')
            ->exists();

        if (! $existingNotification) {
            DB::table('notifications_table')->insert([
                'notification_user_id' => 3,
                'notification_title' => 'Replacement Request',
                'notification_message' => 'Report #'.$reportId.' requires replacement.',
                'notification_type' => 'replacement',
                'notification_created_at' => now(),
            ]);
        }

        $existingProcurement = DB::table('procurement_requests_table')
            ->where('procurement_request_report_id', $reportId)
            ->exists();

        if (! $existingProcurement) {
            DB::table('procurement_requests_table')->insert([
                'procurement_request_report_id' => $reportId,
                'procurement_request_status' => 'Pending',
                'procurement_request_created_by' => $personnelId,
            ]);
        }
    }

    private function applyReportStatusUpdate(int $id, object $report, array $updates): void
    {
        DB::table('reports_table')
            ->where('report_id', $id)
            ->update($updates);

        if (ReportItems::tableExists()) {
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
        }

        ReportGrouping::syncOpenSiblings($report, $updates);
    }

    private function baseReportQuery()
    {
        return DB::table('reports_table')
            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )
            ->leftJoin(
                'users_table as assigned_personnel',
                'reports_table.report_assigned_personnel_id',
                '=',
                'assigned_personnel.user_id'
            )
            ->leftJoin(
                'users_table as assigned_purchaser',
                'reports_table.report_assigned_purchaser_id',
                '=',
                'assigned_purchaser.user_id'
            )
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
            'urgency' => (string) ($report->report_urgency_level ?? 'Non-Urgent'),
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
            'borrowable' => isset($item->equipment_is_borrowable)
                ? ((int) $item->equipment_is_borrowable === 1)
                : null,
            'placement' => $isStorageRoom ? 'Stock' : 'Deployed',
            'image' => $item->equipment_image ?? null,
            'is_listed' => ! empty($item->report_item_equipment_id),
        ];
    }
}
