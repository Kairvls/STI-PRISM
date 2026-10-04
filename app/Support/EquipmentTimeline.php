<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EquipmentTimeline
{
    public static function eventTypes(): array
    {
        return [
            'acquisition' => 'Acquisition / Purchase',
            'transfer' => 'Transfer',
            'maintenance' => 'Maintenance',
            'report' => 'Report',
            'disposal' => 'Disposal',
            'created' => 'Record Created',
            'qr' => 'QR / Tag',
            'borrow' => 'Borrowing',
            'condition' => 'Condition',
            'assignment' => 'Property Assignment',
        ];
    }

    public static function forEquipment(int $equipmentId, array $filters = []): array
    {
        $equipment = self::loadEquipmentProfile($equipmentId);

        if (! $equipment) {
            return [
                'equipment' => null,
                'events' => [],
                'counts' => [],
            ];
        }

        $types = self::normalizeTypes($filters['types'] ?? null);
        $from = self::parseFilterDate($filters['from'] ?? null, true);
        $to = self::parseFilterDate($filters['to'] ?? null, false);

        $events = collect();

        if (self::typeEnabled($types, 'created')) {
            $events = $events->merge(self::createdEvents($equipment));
        }

        if (self::typeEnabled($types, 'acquisition')) {
            $events = $events->merge(self::acquisitionEvents($equipment));
        }

        if (self::typeEnabled($types, 'qr')) {
            $events = $events->merge(self::qrEvents($equipment));
        }

        if (self::typeEnabled($types, 'transfer')) {
            $events = $events->merge(self::transferEvents($equipmentId));
        }

        if (self::typeEnabled($types, 'borrow')) {
            $events = $events->merge(self::borrowEvents($equipmentId));
        }

        if (self::typeEnabled($types, 'condition')) {
            $events = $events->merge(self::conditionEvents($equipmentId));
        }

        if (self::typeEnabled($types, 'assignment')) {
            $events = $events->merge(self::assignmentEvents($equipmentId));
        }

        if (self::typeEnabled($types, 'maintenance')) {
            $events = $events->merge(self::maintenanceEvents($equipmentId));
        }

        if (self::typeEnabled($types, 'report')) {
            $events = $events->merge(self::reportEvents($equipmentId));
        }

        if (self::typeEnabled($types, 'disposal')) {
            $events = $events->merge(self::disposalEvents($equipmentId));
        }

        $events = self::filterEvents($events, $from, $to)
            ->sortByDesc(fn ($event) => $event['occurred_at'])
            ->values();

        $counts = $events
            ->groupBy('type')
            ->map(fn (Collection $group) => $group->count())
            ->all();

        return [
            'equipment' => $equipment,
            'events' => $events->all(),
            'counts' => $counts,
            'report_summary' => self::reportSummary($equipmentId),
        ];
    }

    public static function matchingEquipmentIds(
        ?string $from = null,
        ?string $to = null,
        ?array $types = null
    ): ?Collection {
        $hasDateFilter = ($from !== null && $from !== '')
            || ($to !== null && $to !== '');

        $normalizedTypes = self::normalizeTypes($types);
        $hasTypeFilter = is_array($types)
            && count($types) > 0
            && count($normalizedTypes) < count(self::eventTypes());

        if (! $hasDateFilter && ! $hasTypeFilter) {
            return null;
        }

        $fromDate = $hasDateFilter ? self::parseFilterDate($from, true) : null;
        $toDate = $hasDateFilter ? self::parseFilterDate($to, false) : null;
        $types = $normalizedTypes;
        $ids = collect();

        if (self::typeEnabled($types, 'report')) {
            $query = DB::table('reports_table')
                ->whereNotNull('report_equipment_id')
                ->select('report_equipment_id as equipment_id', 'report_submitted_at as occurred_at');

            self::applyDateBounds($query, 'report_submitted_at', $fromDate, $toDate);
            $ids = $ids->merge($query->pluck('equipment_id'));

            if (ReportItems::tableExists()) {
                $itemQuery = DB::table('report_items_table')
                    ->join('reports_table', 'reports_table.report_id', '=', 'report_items_table.report_id')
                    ->whereNotNull('report_items_table.report_item_equipment_id')
                    ->select('report_items_table.report_item_equipment_id as equipment_id');

                self::applyDateBounds($itemQuery, 'reports_table.report_submitted_at', $fromDate, $toDate);
                $ids = $ids->merge($itemQuery->pluck('equipment_id'));
            }
        }

        if (self::typeEnabled($types, 'transfer') && Schema::hasTable('equipment_transfer_history_table')) {
            $query = DB::table('equipment_transfer_history_table')
                ->select('equipment_id', 'created_at as occurred_at');

            self::applyDateBounds($query, 'created_at', $fromDate, $toDate);
            $ids = $ids->merge($query->pluck('equipment_id'));
        }

        if (self::typeEnabled($types, 'maintenance') && Schema::hasTable('equipment_maintenance_history_table')) {
            $query = DB::table('equipment_maintenance_history_table')
                ->select(
                    'equipment_maintenance_equipment_id as equipment_id',
                    DB::raw('COALESCE(equipment_maintenance_completed_at, equipment_maintenance_created_at) as occurred_at')
                );

            self::applyDateBounds(
                $query,
                DB::raw('COALESCE(equipment_maintenance_completed_at, equipment_maintenance_created_at)'),
                $fromDate,
                $toDate
            );
            $ids = $ids->merge($query->pluck('equipment_id'));
        }

        if (self::typeEnabled($types, 'disposal') && Schema::hasTable('disposal_records_table')) {
            $query = DB::table('disposal_records_table')
                ->select('disposal_equipment_id as equipment_id', 'disposal_disposed_at as occurred_at');

            self::applyDateBounds($query, 'disposal_disposed_at', $fromDate, $toDate);
            $ids = $ids->merge($query->pluck('equipment_id'));
        }

        if (self::typeEnabled($types, 'assignment') && PropertyAssignments::tableReady()) {
            $query = DB::table('property_assignments_table')
                ->select('assignment_equipment_id as equipment_id');

            self::applyDateBounds($query, 'assignment_issued_at', $fromDate, $toDate);
            $ids = $ids->merge($query->pluck('equipment_id'));

            $returned = DB::table('property_assignments_table')
                ->whereNotNull('assignment_returned_at')
                ->select('assignment_equipment_id as equipment_id');

            self::applyDateBounds($returned, 'assignment_returned_at', $fromDate, $toDate);
            $ids = $ids->merge($returned->pluck('equipment_id'));
        }

        if (self::typeEnabled($types, 'acquisition') || self::typeEnabled($types, 'created')) {
            $equipmentQuery = DB::table('equipment_table')->select('equipment_id');

            if (self::typeEnabled($types, 'created')) {
                $createdQuery = clone $equipmentQuery;
                self::applyDateBounds($createdQuery, 'equipment_created_at', $fromDate, $toDate);
                $ids = $ids->merge($createdQuery->pluck('equipment_id'));
            }

            if (self::typeEnabled($types, 'acquisition')) {
                $purchaseQuery = clone $equipmentQuery;
                $purchaseQuery->whereNotNull('equipment_purchase_date');
                self::applyDateBounds($purchaseQuery, 'equipment_purchase_date', $fromDate, $toDate);
                $ids = $ids->merge($purchaseQuery->pluck('equipment_id'));

                $acquiredQuery = clone $equipmentQuery;
                $acquiredQuery->whereNotNull('equipment_acquired_date');
                self::applyDateBounds($acquiredQuery, 'equipment_acquired_date', $fromDate, $toDate);
                $ids = $ids->merge($acquiredQuery->pluck('equipment_id'));
            }
        }

        return $ids->filter()->unique()->values();
    }

    private static function loadEquipmentProfile(int $equipmentId): ?array
    {
        $row = DB::table('equipment_table')
            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            )
            ->leftJoin(
                'suppliers_table',
                'equipment_table.equipment_supplier_id',
                '=',
                'suppliers_table.supplier_id'
            )
            ->where('equipment_table.equipment_id', $equipmentId)
            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name',
                'rooms_table.room_type',
                'suppliers_table.supplier_store_type'
            )
            ->first();

        if (! $row) {
            return null;
        }

        $supplierName = null;
        if ($row->equipment_supplier_id) {
            $physical = DB::table('physical_suppliers_table')
                ->where('supplier_id', $row->equipment_supplier_id)
                ->value('company_name');
            $online = DB::table('online_suppliers_table')
                ->where('supplier_id', $row->equipment_supplier_id)
                ->value('shop_name');

            $supplierName = $physical ?: $online;
        }
        if (! filled($supplierName) && filled($row->equipment_supplier_name ?? null)) {
            $supplierName = $row->equipment_supplier_name;
        }

        $receivingReport = null;
        $purchaseOrderNumber = null;
        $atpNumber = null;
        $risNumber = null;
        $poDate = null;
        $receivedBy = null;
        $rrCondition = null;
        $rrItemId = (int) ($row->equipment_receiving_report_item_id ?? 0);

        if (Schema::hasTable('receiving_report_items_table') && Schema::hasTable('receiving_reports_table')) {
            $rrSelect = [
                'receiving_report_items_table.receiving_report_item_id',
                'receiving_reports_table.receiving_report_id',
                'receiving_reports_table.receiving_report_form_number',
                'receiving_reports_table.receiving_report_created_at',
                'receiving_reports_table.receiving_report_date',
                'receiving_reports_table.receiving_report_delivery_date',
            ];
            foreach ([
                'receiving_report_atp_id',
                'receiving_report_request_check_id',
                'receiving_report_second_count_by',
                'receiving_report_ris_id',
            ] as $col) {
                if (Schema::hasColumn('receiving_reports_table', $col)) {
                    $rrSelect[] = 'receiving_reports_table.'.$col;
                }
            }
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_condition')) {
                $rrSelect[] = 'receiving_report_items_table.receiving_report_item_condition';
            }

            $rrQuery = DB::table('receiving_report_items_table')
                ->join(
                    'receiving_reports_table',
                    'receiving_report_items_table.receiving_report_id',
                    '=',
                    'receiving_reports_table.receiving_report_id'
                );

            if ($rrItemId > 0) {
                $rrQuery->where('receiving_report_items_table.receiving_report_item_id', $rrItemId);
            } elseif (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                $rrQuery->where('receiving_report_items_table.receiving_report_item_equipment_id', $equipmentId);
            } else {
                $rrQuery = null;
            }

            $receivingReport = $rrQuery
                ? $rrQuery->orderByDesc('receiving_reports_table.receiving_report_created_at')
                    ->select($rrSelect)
                    ->first()
                : null;

            if ($receivingReport) {
                $rrCondition = $receivingReport->receiving_report_item_condition ?? null;
                $receivedBy = $receivingReport->receiving_report_second_count_by ?? null;
                $atpId = (int) ($receivingReport->receiving_report_atp_id ?? 0);
                if (
                    $atpId < 1
                    && ! empty($receivingReport->receiving_report_request_check_id)
                    && Schema::hasTable('request_check_table')
                    && Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id')
                ) {
                    $atpId = (int) DB::table('request_check_table')
                        ->where('request_check_id', $receivingReport->receiving_report_request_check_id)
                        ->value('request_check_authority_purchase_id');
                }
                if ($atpId > 0 && Schema::hasTable('authority_to_purchase_table')) {
                    $atp = DB::table('authority_to_purchase_table')
                        ->where('authority_purchase_id', $atpId)
                        ->first();
                    if ($atp) {
                        $atpNumber = $atp->authority_purchase_form_number ?? null;
                        $poDate = self::dateString($atp->authority_purchase_date ?? null);
                        if (
                            Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_ris_id')
                            && ! empty($atp->authority_purchase_ris_id)
                            && Schema::hasTable('requisition_issue_slip_table')
                        ) {
                            $risNumber = DB::table('requisition_issue_slip_table')
                                ->where('ris_id', $atp->authority_purchase_ris_id)
                                ->value('ris_form_number');
                        }
                    }
                }
                if (
                    $atpId > 0
                    && Schema::hasTable('purchase_order_atps_table')
                    && Schema::hasTable('purchase_orders_table')
                ) {
                    $poSelect = ['po.purchase_order_number'];
                    if (Schema::hasColumn('purchase_orders_table', 'purchase_order_date')) {
                        $poSelect[] = 'po.purchase_order_date';
                    }
                    if (Schema::hasColumn('purchase_orders_table', 'purchase_order_created_at')) {
                        $poSelect[] = 'po.purchase_order_created_at';
                    } elseif (Schema::hasColumn('purchase_orders_table', 'created_at')) {
                        $poSelect[] = 'po.created_at';
                    }
                    $po = DB::table('purchase_order_atps_table as poa')
                        ->join('purchase_orders_table as po', 'po.purchase_order_id', '=', 'poa.purchase_order_id')
                        ->where('poa.authority_purchase_id', $atpId)
                        ->where('po.purchase_order_status', '!=', PurchaseOrderBasket::STATUS_CANCELLED)
                        ->first($poSelect);
                    if ($po) {
                        $purchaseOrderNumber = $po->purchase_order_number ?? null;
                        $poDate = self::dateString(
                            $po->purchase_order_date
                                ?? $po->purchase_order_created_at
                                ?? $po->created_at
                                ?? $poDate
                        );
                    }
                }
            }
        }

        $stockedByName = null;
        if (! empty($row->equipment_stocked_by) && Schema::hasTable('users_table')) {
            $stockedByName = DB::table('users_table')
                ->where('user_id', $row->equipment_stocked_by)
                ->value('user_full_name');
        }

        [$deployedAt, $deployedRoom] = self::firstDeployment($row);
        $acquisitionSource = $row->equipment_acquisition_source ?? null;
        if (! filled($acquisitionSource) && $receivingReport) {
            $acquisitionSource = EquipmentAcquisition::PROCUREMENT;
        }

        return [
            'id' => (int) $row->equipment_id,
            'name' => $row->equipment_name,
            'category' => $row->equipment_category_name,
            'room_name' => $row->room_name,
            'room_type' => $row->room_type,
            'inventory_status' => $row->equipment_inventory_status,
            'condition_status' => $row->equipment_condition_status,
            'tracking_mode' => $row->equipment_tracking_mode ?? null,
            'quantity' => (int) ($row->equipment_quantity ?? 1),
            'asset_tag' => $row->equipment_asset_tag,
            'serial_number' => $row->equipment_serial_number,
            'brand' => $row->equipment_brand_name,
            'model' => $row->equipment_model,
            'qr_code' => $row->equipment_qr_code ?? null,
            'qr_issued_at' => self::dateString($row->equipment_qr_issued_at ?? null),
            'location' => $row->equipment_current_location,
            'placement_zone' => $row->equipment_placement_zone,
            'purchase_date' => self::dateString($row->equipment_purchase_date),
            'purchase_cost' => $row->equipment_purchase_cost !== null && $row->equipment_purchase_cost !== ''
                ? (float) $row->equipment_purchase_cost
                : null,
            'acquired_date' => self::dateString($row->equipment_acquired_date),
            'stocked_by' => $row->equipment_stocked_by ?? null,
            'stocked_by_name' => $stockedByName,
            'stock_lot_code' => $row->equipment_stock_lot_code ?? null,
            'warranty_expiration' => self::dateString($row->equipment_warranty_expiration),
            'useful_life_years' => $row->equipment_useful_life_years ?? null,
            'created_at' => self::dateString($row->equipment_created_at),
            'supplier_name' => $supplierName,
            'supplier_store_type' => $row->supplier_store_type,
            'receiving_report_id' => $receivingReport->receiving_report_id ?? null,
            'receiving_report_item_id' => $receivingReport->receiving_report_item_id ?? ($rrItemId ?: null),
            'receiving_report_number' => $receivingReport->receiving_report_form_number ?? null,
            'receiving_report_date' => self::dateString(
                $receivingReport->receiving_report_date
                    ?? $receivingReport->receiving_report_delivery_date
                    ?? $receivingReport->receiving_report_created_at
                    ?? null
            ),
            'receiving_condition' => $rrCondition,
            'received_by' => $receivedBy,
            'purchase_order_number' => $purchaseOrderNumber ? (string) $purchaseOrderNumber : null,
            'purchase_order_date' => $poDate,
            'atp_number' => $atpNumber ? (string) $atpNumber : null,
            'ris_number' => $risNumber ? (string) $risNumber : null,
            'replaces_id' => ! empty($row->equipment_replaces_id) ? (int) $row->equipment_replaces_id : null,
            'replaced_by_id' => ! empty($row->equipment_replaced_by_id) ? (int) $row->equipment_replaced_by_id : null,
            'acquisition_source' => $acquisitionSource,
            'acquisition_source_label' => EquipmentAcquisition::sourceLabel($acquisitionSource),
            'reference_number' => $row->equipment_reference_number ?? null,
            'acquisition_notes' => $row->equipment_acquisition_notes ?? null,
            'deployed_at' => $deployedAt,
            'deployed_room' => $deployedRoom,
            'view_url' => EquipmentViewReturn::viewUrl($equipmentId),
        ];
    }

    /**
     * First move out of storage into a working room.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function firstDeployment(object $row): array
    {
        if (Schema::hasTable('equipment_transfer_history_table')) {
            $move = DB::table('equipment_transfer_history_table as th')
                ->leftJoin('rooms_table as to_room', 'to_room.room_id', '=', 'th.to_room_id')
                ->where('th.equipment_id', $row->equipment_id)
                ->where(function ($q) {
                    $q->whereNull('to_room.room_type')
                        ->orWhere('to_room.room_type', '!=', RoomCategories::STORAGE_TYPE);
                })
                ->orderBy('th.created_at')
                ->first(['th.created_at', 'to_room.room_name']);

            if ($move) {
                return [self::dateString($move->created_at), $move->room_name];
            }
        }

        if (filled($row->room_type ?? null) && ! RoomCategories::isStorageType($row->room_type)) {
            return [
                self::dateString($row->equipment_acquired_date ?? $row->equipment_created_at ?? null),
                $row->room_name ?? null,
            ];
        }

        return [null, null];
    }

    private static function createdEvents(array $equipment): Collection
    {
        if (empty($equipment['created_at'])) {
            return collect();
        }

        return collect([
            self::makeEvent(
                'created',
                $equipment['created_at'],
                'Equipment record created',
                'This equipment was added to the inventory system.',
                [
                    'asset_tag' => $equipment['asset_tag'],
                ]
            ),
        ]);
    }

    private static function acquisitionEvents(array $equipment): Collection
    {
        $events = collect();

        if (! empty($equipment['purchase_order_number']) || ! empty($equipment['atp_number']) || ! empty($equipment['ris_number'])) {
            $parts = array_filter([
                $equipment['purchase_order_number'] ? 'PO '.$equipment['purchase_order_number'] : null,
                $equipment['atp_number'] ? 'ATP '.$equipment['atp_number'] : null,
                $equipment['ris_number'] ? 'RIS '.$equipment['ris_number'] : null,
            ]);
            $events->push(self::makeEvent(
                'acquisition',
                $equipment['purchase_order_date'] ?: $equipment['purchase_date'] ?: $equipment['receiving_report_date'] ?: $equipment['created_at'],
                'Ordered',
                implode(' · ', $parts) ?: 'Purchase commitment recorded.',
                [
                    'purchase_order_number' => $equipment['purchase_order_number'],
                    'atp_number' => $equipment['atp_number'],
                    'ris_number' => $equipment['ris_number'],
                ]
            ));
        }

        $hasManualSource = ! empty($equipment['acquisition_source'])
            && $equipment['acquisition_source'] !== EquipmentAcquisition::PROCUREMENT;
        if (! empty($equipment['purchase_date']) || $equipment['purchase_cost'] !== null || ! empty($equipment['supplier_name']) || $hasManualSource) {
            $details = [];
            if ($equipment['purchase_cost'] !== null) {
                $details[] = 'Cost: ₱'.number_format($equipment['purchase_cost'], 2);
            }
            if ($equipment['supplier_name']) {
                $details[] = 'Supplier: '.$equipment['supplier_name'];
            }
            if ($equipment['purchase_order_number']) {
                $details[] = 'PO '.$equipment['purchase_order_number'];
            } elseif (! empty($equipment['reference_number'])) {
                $details[] = 'Ref '.$equipment['reference_number'];
            }
            $isManualSource = $hasManualSource;
            if ($isManualSource) {
                array_unshift($details, $equipment['acquisition_source_label']);
            }

            $events->push(self::makeEvent(
                'acquisition',
                $equipment['purchase_date'] ?: $equipment['purchase_order_date'] ?: $equipment['created_at'],
                $isManualSource && $equipment['acquisition_source'] !== 'direct_purchase' ? 'Acquired' : 'Bought / committed',
                $details ? implode(' · ', $details) : 'Purchase recorded.',
                [
                    'purchase_cost' => $equipment['purchase_cost'],
                    'supplier_name' => $equipment['supplier_name'],
                    'purchase_order_number' => $equipment['purchase_order_number'],
                ]
            ));
        }

        if (! empty($equipment['receiving_report_id'])) {
            $details = [
                'RR '.($equipment['receiving_report_number'] ?: '#'.$equipment['receiving_report_id']),
            ];
            if (! empty($equipment['receiving_condition'])) {
                $details[] = 'Condition: '.$equipment['receiving_condition'];
            }
            if (! empty($equipment['received_by'])) {
                $details[] = 'Received by: '.$equipment['received_by'];
            }

            $events->push(self::makeEvent(
                'acquisition',
                $equipment['receiving_report_date'] ?: $equipment['purchase_date'] ?: $equipment['created_at'],
                'Delivered / received',
                implode(' · ', $details),
                [
                    'receiving_report_id' => $equipment['receiving_report_id'],
                    'receiving_report_number' => $equipment['receiving_report_number'],
                    'received_by' => $equipment['received_by'],
                    'condition' => $equipment['receiving_condition'],
                ]
            ));
        }

        if (! empty($equipment['acquired_date']) || ! empty($equipment['created_at'])) {
            $stockParts = array_filter([
                $equipment['room_name'] ? 'Room: '.$equipment['room_name'] : null,
                $equipment['tracking_mode'] ? 'Mode: '.$equipment['tracking_mode'] : null,
                $equipment['stocked_by_name'] ? 'By: '.$equipment['stocked_by_name'] : null,
                $equipment['stock_lot_code'] ? 'Lot: '.$equipment['stock_lot_code'] : null,
            ]);
            $events->push(self::makeEvent(
                'acquisition',
                $equipment['acquired_date'] ?: $equipment['created_at'],
                'Added to inventory',
                $stockParts ? implode(' · ', $stockParts) : 'Stocked into campus inventory.',
                [
                    'stocked_by' => $equipment['stocked_by_name'],
                    'tracking_mode' => $equipment['tracking_mode'],
                    'room' => $equipment['room_name'],
                ]
            ));
        }

        return $events;
    }

    private static function qrEvents(array $equipment): Collection
    {
        if (empty($equipment['qr_code']) && empty($equipment['qr_issued_at'])) {
            return collect();
        }

        return collect([
            self::makeEvent(
                'qr',
                $equipment['qr_issued_at'] ?: $equipment['created_at'],
                'QR / tag issued',
                $equipment['qr_code']
                    ? 'Code: '.$equipment['qr_code']
                    : 'QR code assigned to this asset.',
                [
                    'qr_code' => $equipment['qr_code'],
                    'asset_tag' => $equipment['asset_tag'],
                ]
            ),
        ]);
    }

    private static function borrowEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return collect();
        }

        return DB::table('borrowing_records_table')
            ->where('borrowing_equipment_id', $equipmentId)
            ->orderByDesc('borrowing_created_at')
            ->get()
            ->flatMap(function ($row) {
                $events = collect();
                $borrower = trim((string) ($row->borrowing_borrower_name ?? 'Borrower'));
                $dept = trim((string) ($row->borrowing_borrower_department ?? ''));
                $events->push(self::makeEvent(
                    'borrow',
                    $row->borrowing_date ?: $row->borrowing_created_at,
                    'Borrowed',
                    implode(' · ', array_filter([
                        $borrower,
                        $dept !== '' ? $dept : null,
                        $row->borrowing_expected_return_date ? 'Due '.$row->borrowing_expected_return_date : null,
                        $row->borrowing_destination_location ? 'To '.$row->borrowing_destination_location : null,
                    ])),
                    [
                        'borrower' => $borrower,
                        'expected_return' => $row->borrowing_expected_return_date,
                        'status' => $row->borrowing_status,
                    ]
                ));

                if (! empty($row->borrowing_actual_return_date)) {
                    $events->push(self::makeEvent(
                        'borrow',
                        $row->borrowing_actual_return_date,
                        'Returned from borrow',
                        implode(' · ', array_filter([
                            $borrower,
                            $row->borrowing_equipment_condition ? 'Condition: '.$row->borrowing_equipment_condition : null,
                        ])),
                        [
                            'borrower' => $borrower,
                            'condition' => $row->borrowing_equipment_condition,
                        ]
                    ));
                }

                return $events;
            });
    }

    private static function conditionEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('equipment_condition_history_table')) {
            return collect();
        }

        return DB::table('equipment_condition_history_table as h')
            ->leftJoin('users_table as u', 'u.user_id', '=', 'h.changed_by')
            ->where('h.equipment_id', $equipmentId)
            ->orderByDesc('h.created_at')
            ->get([
                'h.*',
                'u.user_full_name as changed_by_name',
            ])
            ->map(function ($row) {
                $from = $row->condition_from ?: '—';
                $to = $row->condition_to ?: '—';

                return self::makeEvent(
                    'condition',
                    $row->created_at,
                    'Condition changed',
                    $from.' → '.$to
                        .($row->changed_by_name ? ' · By: '.$row->changed_by_name : '')
                        .($row->change_source ? ' · '.$row->change_source : ''),
                    [
                        'from' => $row->condition_from,
                        'to' => $row->condition_to,
                        'by' => $row->changed_by_name,
                        'source' => $row->change_source,
                    ]
                );
            });
    }

    private static function assignmentEvents(int $equipmentId): Collection
    {
        return PropertyAssignments::history($equipmentId, 200)
            ->flatMap(function ($row) {
                $person = trim((string) ($row->custodian_full_name ?? '')) ?: 'Unknown person';
                $events = collect([
                    self::makeEvent(
                        'assignment',
                        $row->assignment_issued_at,
                        'Assigned to '.$person,
                        implode(' · ', array_filter([
                            $row->custodian_position ?? null,
                            $row->custodian_employee_id ?? null,
                            $row->assignment_document_no ? 'Doc '.$row->assignment_document_no : null,
                            $row->room_name ? 'Room: '.$row->room_name : null,
                            $row->workstation_slot_label ? 'Desk: '.$row->workstation_slot_label : null,
                            $row->assignment_notes,
                        ])),
                        [
                            'assignment_id' => (int) $row->assignment_id,
                            'actor' => $row->issued_by_name,
                            'custodian' => $person,
                            'dot' => 'bg-teal-500',
                        ]
                    ),
                ]);

                if (! empty($row->assignment_returned_at)) {
                    $transferred = $row->assignment_status === 'Transferred';
                    $events->push(self::makeEvent(
                        'assignment',
                        $row->assignment_returned_at,
                        ($transferred ? 'Reassigned from ' : 'Returned by ').$person,
                        implode(' · ', array_filter([
                            $row->assignment_return_condition ? 'Condition: '.$row->assignment_return_condition : null,
                            $row->assignment_return_notes,
                        ])) ?: ($transferred ? 'Custody moved to another person.' : 'Custody closed.'),
                        [
                            'assignment_id' => (int) $row->assignment_id,
                            'actor' => $row->returned_by_name,
                            'custodian' => $person,
                            'dot' => 'bg-slate-400',
                        ]
                    ));
                }

                return $events;
            });
    }

    private static function transferEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('equipment_transfer_history_table')) {
            return collect();
        }

        $query = DB::table('equipment_transfer_history_table')
            ->leftJoin(
                'rooms_table as from_room',
                'equipment_transfer_history_table.from_room_id',
                '=',
                'from_room.room_id'
            )
            ->leftJoin(
                'rooms_table as to_room',
                'equipment_transfer_history_table.to_room_id',
                '=',
                'to_room.room_id'
            );

        $select = [
            'equipment_transfer_history_table.transfer_id',
            'equipment_transfer_history_table.remarks',
            'equipment_transfer_history_table.created_at',
            'from_room.room_name as from_room_name',
            'to_room.room_name as to_room_name',
        ];

        if (
            Schema::hasColumn('equipment_transfer_history_table', 'transferred_by')
            && Schema::hasTable('users_table')
        ) {
            $query->leftJoin(
                'users_table as transfer_user',
                'equipment_transfer_history_table.transferred_by',
                '=',
                'transfer_user.user_id'
            );
            $select[] = 'transfer_user.user_full_name as transferred_by_name';
        }

        return $query
            ->where('equipment_transfer_history_table.equipment_id', $equipmentId)
            ->orderByDesc('equipment_transfer_history_table.created_at')
            ->select($select)
            ->get()
            ->map(function ($row) {
                $from = $row->from_room_name ?: 'Unassigned';
                $to = $row->to_room_name ?: 'Unassigned';
                $by = trim((string) ($row->transferred_by_name ?? ''));
                $detail = $from.' → '.$to;
                if ($by !== '') {
                    $detail .= ' · By: '.$by;
                }
                if (! empty($row->remarks)) {
                    $detail .= ' · '.$row->remarks;
                }

                return self::makeEvent(
                    'transfer',
                    $row->created_at,
                    'Deployed / moved',
                    $detail,
                    [
                        'transfer_id' => (int) $row->transfer_id,
                        'from_room' => $from,
                        'to_room' => $to,
                        'transferred_by' => $by !== '' ? $by : null,
                        'remarks' => $row->remarks,
                    ]
                );
            });
    }

    private static function maintenanceEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('equipment_maintenance_history_table')) {
            return collect();
        }

        return DB::table('equipment_maintenance_history_table')
            ->leftJoin(
                'users_table',
                'equipment_maintenance_history_table.equipment_maintenance_personnel_id',
                '=',
                'users_table.user_id'
            )
            ->where('equipment_maintenance_equipment_id', $equipmentId)
            ->orderByDesc('equipment_maintenance_created_at')
            ->select(
                'equipment_maintenance_history_table.*',
                'users_table.user_full_name as personnel_name'
            )
            ->get()
            ->map(function ($row) {
                $at = $row->equipment_maintenance_completed_at ?: $row->equipment_maintenance_created_at;
                $parts = array_filter([
                    $row->equipment_maintenance_findings ? 'Findings: '.$row->equipment_maintenance_findings : null,
                    $row->equipment_maintenance_repair_action ? 'Action: '.$row->equipment_maintenance_repair_action : null,
                    ! empty($row->equipment_maintenance_parts_used) ? 'Parts: '.$row->equipment_maintenance_parts_used : null,
                    isset($row->equipment_maintenance_repair_cost) && $row->equipment_maintenance_repair_cost !== null
                        ? 'Cost: ₱'.number_format((float) $row->equipment_maintenance_repair_cost, 2)
                        : null,
                    isset($row->equipment_maintenance_downtime_hours) && $row->equipment_maintenance_downtime_hours !== null
                        ? 'Downtime: '.$row->equipment_maintenance_downtime_hours.'h'
                        : null,
                    $row->personnel_name ? 'By: '.$row->personnel_name : null,
                ]);

                return self::makeEvent(
                    'maintenance',
                    $at,
                    'Maintenance'.($row->equipment_maintenance_status ? ' · '.$row->equipment_maintenance_status : ''),
                    $parts ? implode(' · ', $parts) : 'Maintenance activity recorded.',
                    [
                        'status' => $row->equipment_maintenance_status,
                        'findings' => $row->equipment_maintenance_findings,
                        'repair_action' => $row->equipment_maintenance_repair_action,
                        'parts_used' => $row->equipment_maintenance_parts_used ?? null,
                        'repair_cost' => $row->equipment_maintenance_repair_cost ?? null,
                        'downtime_hours' => $row->equipment_maintenance_downtime_hours ?? null,
                        'personnel_name' => $row->personnel_name,
                    ]
                );
            });
    }

    /**
     * One row per ticket that included this equipment, oldest first, with the
     * equipment's own line status (falls back to the ticket for legacy reports).
     */
    public static function reportHistory(int $equipmentId): Collection
    {
        if (! Schema::hasTable('reports_table')) {
            return collect();
        }

        $rows = collect();
        $severityColumn = ReportSeverity::hasColumns() ? ['reports_table.report_severity'] : [];

        if (ReportItems::tableExists()) {
            $rows = DB::table('report_items_table')
                ->join('reports_table', 'reports_table.report_id', '=', 'report_items_table.report_id')
                ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
                ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
                ->where('report_items_table.report_item_equipment_id', $equipmentId)
                ->get([
                    'reports_table.report_id',
                    'reports_table.report_submitted_at',
                    'reports_table.report_urgency_level',
                    'reports_table.report_current_status',
                    'reports_table.report_is_archived',
                    'reporters_table.reporter_full_name',
                    'rooms_table.room_name',
                    'report_items_table.report_item_status as status',
                    'report_items_table.report_item_suggested_issue as issue',
                    'report_items_table.report_item_updated_at as status_at',
                    'report_items_table.report_item_resolution_notes as resolution_notes',
                    'report_items_table.report_item_replacement_notes as replacement_notes',
                    'report_items_table.report_item_rejection_notes as rejection_notes',
                    ...$severityColumn,
                ]);
        }

        $legacy = DB::table('reports_table')
            ->leftJoin('reporters_table', 'reports_table.report_reporter_employee_id', '=', 'reporters_table.reporter_employee_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->where('reports_table.report_equipment_id', $equipmentId)
            ->whereNotIn('reports_table.report_id', $rows->pluck('report_id')->all() ?: [0])
            ->get([
                'reports_table.report_id',
                'reports_table.report_submitted_at',
                'reports_table.report_urgency_level',
                'reports_table.report_current_status',
                'reports_table.report_is_archived',
                'reporters_table.reporter_full_name',
                'rooms_table.room_name',
                'reports_table.report_current_status as status',
                'reports_table.report_suggested_issue as issue',
                'reports_table.report_updated_at as status_at',
                'reports_table.report_resolution_notes as resolution_notes',
                'reports_table.report_replacement_notes as replacement_notes',
                'reports_table.report_rejection_notes as rejection_notes',
                ...$severityColumn,
            ]);

        return $rows->merge($legacy)
            ->unique('report_id')
            ->sortBy(fn ($row) => [(string) $row->report_submitted_at, (int) $row->report_id])
            ->values();
    }

    /**
     * @return array{times_reported: int, times_fixed: int, open_count: int, replacement: bool,
     *     last_reported_at: ?string, last_fixed_at: ?string, state: string}
     */
    public static function reportSummary(int $equipmentId, ?Collection $history = null): array
    {
        $history = $history ?? self::reportHistory($equipmentId);
        $fixed = $history->where('status', 'Resolved')->reject(fn ($row) => self::closedWithOtherTicket($row));
        $open = $history->filter(fn ($row) => in_array($row->status, ['Pending', 'Processing'], true));
        $replacement = $history->contains('status', 'For Replacement');

        $state = match (true) {
            $replacement => 'Needs replacement',
            $open->contains('status', 'Processing') => 'Under repair',
            $open->isNotEmpty() => 'Malfunction reported',
            default => 'Working',
        };

        return [
            'times_reported' => $history->count(),
            'times_fixed' => $fixed->count(),
            'open_count' => $open->count(),
            'replacement' => $replacement,
            'last_reported_at' => self::dateString($history->last()->report_submitted_at ?? null),
            'last_fixed_at' => self::dateString($fixed->max('status_at')),
            'state' => $state,
        ];
    }

    private static function reportEvents(int $equipmentId): Collection
    {
        $events = collect();
        $openSince = [];

        foreach (self::reportHistory($equipmentId)->values() as $index => $row) {
            $ticket = ReportGrouping::ticketCode($row);
            $nth = $index + 1;

            $stillOpenBefore = collect($openSince)
                ->filter(fn ($closedAt) => $closedAt === null || $closedAt > $row->report_submitted_at)
                ->keys()
                ->first();

            $events->push(self::makeEvent(
                'report',
                $row->report_submitted_at,
                'Malfunction reported'.($nth > 1 ? ' · '.self::ordinal($nth).' time' : ''),
                ($row->issue ?: 'No issue named')
                    .' · '.$ticket
                    .' · '.ReportSeverity::forReport($row).' priority'
                    .($row->room_name ? ' · '.$row->room_name : '')
                    .($stillOpenBefore ? ' · Re-reported while '.$stillOpenBefore.' was still not fixed' : ''),
                [
                    'report_id' => (int) $row->report_id,
                    'status' => $row->status,
                    'urgency' => $row->report_urgency_level,
                    'severity' => ReportSeverity::forReport($row),
                    'issue' => $row->issue,
                    'actor' => $row->reporter_full_name,
                    'reporter' => $row->reporter_full_name,
                    'times_reported' => $nth,
                    'dot' => 'bg-rose-500',
                ]
            ));

            $closedAt = in_array($row->status, ['Resolved', 'For Replacement', 'Rejected'], true)
                ? (string) $row->status_at
                : null;
            $openSince[$ticket] = $closedAt;

            $outcome = match ($row->status) {
                'Processing' => ['Repair in progress', 'bg-sky-500', null],
                'Resolved' => ['Fixed · back to working', 'bg-emerald-500', $row->resolution_notes],
                'For Replacement' => ['Needs replacement', 'bg-orange-500', $row->replacement_notes],
                'Rejected' => ['Report not accepted', 'bg-slate-400', $row->rejection_notes],
                default => null,
            };

            if ($outcome && ! empty($row->status_at) && ! self::closedWithOtherTicket($row)) {
                $events->push(self::makeEvent(
                    'report',
                    $row->status_at,
                    $outcome[0],
                    $ticket.(! empty($outcome[2]) ? ' · '.$outcome[2] : ''),
                    [
                        'report_id' => (int) $row->report_id,
                        'status' => $row->status,
                        'dot' => $outcome[1],
                    ]
                ));
            }
        }

        return $events;
    }

    /**
     * Line closed automatically because the same repair was done on another ticket
     * (see ReportItems::syncRepeatEquipment) — not a separate fix.
     */
    private static function closedWithOtherTicket(object $row): bool
    {
        $notes = (string) ($row->status === 'For Replacement' ? $row->replacement_notes : $row->resolution_notes);

        return (bool) preg_match('/^(Fixed|Sent for replacement) under RPT-/', $notes);
    }

    private static function ordinal(int $number): string
    {
        $suffix = in_array($number % 100, [11, 12, 13], true)
            ? 'th'
            : (['th', 'st', 'nd', 'rd'][$number % 10] ?? 'th');

        return $number.$suffix;
    }

    private static function disposalEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('disposal_records_table')) {
            return collect();
        }

        return DB::table('disposal_records_table')
            ->leftJoin(
                'users_table',
                'disposal_records_table.disposal_approved_by',
                '=',
                'users_table.user_id'
            )
            ->where('disposal_equipment_id', $equipmentId)
            ->orderByDesc('disposal_disposed_at')
            ->select(
                'disposal_records_table.*',
                'users_table.user_full_name as approved_by_name'
            )
            ->get()
            ->map(function ($row) {
                $parts = array_filter([
                    $row->disposal_reason ? 'Reason: '.$row->disposal_reason : null,
                    ! empty($row->disposal_method) ? 'Method: '.$row->disposal_method : null,
                    $row->disposal_area_location ? 'Area: '.$row->disposal_area_location : null,
                    $row->approved_by_name ? 'Approved by: '.$row->approved_by_name : null,
                ]);

                return self::makeEvent(
                    'disposal',
                    $row->disposal_disposed_at,
                    'Disposed',
                    $parts ? implode(' · ', $parts) : 'Equipment disposal recorded.',
                    [
                        'reason' => $row->disposal_reason,
                        'method' => $row->disposal_method ?? null,
                        'area' => $row->disposal_area_location,
                        'approved_by' => $row->approved_by_name,
                    ]
                );
            });
    }

    private static function makeEvent(
        string $type,
        $occurredAt,
        string $title,
        string $description,
        array $meta = []
    ): array {
        return [
            'type' => $type,
            'type_label' => self::eventTypes()[$type] ?? ucfirst($type),
            'occurred_at' => self::dateString($occurredAt),
            'title' => $title,
            'description' => $description,
            'meta' => $meta,
        ];
    }

    private static function normalizeTypes($types): array
    {
        $allowed = array_keys(self::eventTypes());

        if ($types === null || $types === '' || $types === []) {
            return $allowed;
        }

        if (is_string($types)) {
            $types = array_filter(array_map('trim', explode(',', $types)));
        }

        $normalized = collect($types)
            ->map(fn ($type) => strtolower((string) $type))
            ->filter(fn ($type) => in_array($type, $allowed, true))
            ->values()
            ->all();

        return $normalized ?: $allowed;
    }

    private static function typeEnabled(array $types, string $type): bool
    {
        return in_array($type, $types, true);
    }

    private static function parseFilterDate(?string $value, bool $startOfDay): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = Carbon::parse($value);

        return $startOfDay ? $date->copy()->startOfDay() : $date->copy()->endOfDay();
    }

    private static function filterEvents(Collection $events, ?Carbon $from, ?Carbon $to): Collection
    {
        return $events->filter(function ($event) use ($from, $to) {
            if (empty($event['occurred_at'])) {
                return $from === null && $to === null;
            }

            $occurred = Carbon::parse($event['occurred_at']);

            if ($from && $occurred->lt($from)) {
                return false;
            }

            if ($to && $occurred->gt($to)) {
                return false;
            }

            return true;
        });
    }

    private static function applyDateBounds($query, $column, ?Carbon $from, ?Carbon $to): void
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }

        if ($to) {
            $query->where($column, '<=', $to);
        }
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
