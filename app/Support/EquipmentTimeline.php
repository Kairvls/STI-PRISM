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

        if (self::typeEnabled($types, 'transfer')) {
            $events = $events->merge(self::transferEvents($equipmentId));
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

        $receivingReport = null;
        if (
            Schema::hasTable('receiving_report_items_table')
            && Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')
        ) {
            $receivingReport = DB::table('receiving_report_items_table')
                ->join(
                    'receiving_reports_table',
                    'receiving_report_items_table.receiving_report_id',
                    '=',
                    'receiving_reports_table.receiving_report_id'
                )
                ->where('receiving_report_items_table.receiving_report_item_equipment_id', $equipmentId)
                ->orderByDesc('receiving_reports_table.receiving_report_created_at')
                ->select(
                    'receiving_reports_table.receiving_report_id',
                    'receiving_reports_table.receiving_report_form_number',
                    'receiving_reports_table.receiving_report_created_at'
                )
                ->first();
        }

        return [
            'id' => (int) $row->equipment_id,
            'name' => $row->equipment_name,
            'category' => $row->equipment_category_name,
            'room_name' => $row->room_name,
            'room_type' => $row->room_type,
            'inventory_status' => $row->equipment_inventory_status,
            'condition_status' => $row->equipment_condition_status,
            'asset_tag' => $row->equipment_asset_tag,
            'serial_number' => $row->equipment_serial_number,
            'brand' => $row->equipment_brand_name,
            'model' => $row->equipment_model,
            'location' => $row->equipment_current_location,
            'placement_zone' => $row->equipment_placement_zone,
            'purchase_date' => self::dateString($row->equipment_purchase_date),
            'purchase_cost' => $row->equipment_purchase_cost !== null && $row->equipment_purchase_cost !== ''
                ? (float) $row->equipment_purchase_cost
                : null,
            'acquired_date' => self::dateString($row->equipment_acquired_date),
            'warranty_expiration' => self::dateString($row->equipment_warranty_expiration),
            'created_at' => self::dateString($row->equipment_created_at),
            'supplier_name' => $supplierName,
            'supplier_store_type' => $row->supplier_store_type,
            'receiving_report_id' => $receivingReport->receiving_report_id ?? null,
            'receiving_report_number' => $receivingReport->receiving_report_form_number ?? null,
            'receiving_report_date' => self::dateString($receivingReport->receiving_report_created_at ?? null),
            'view_url' => EquipmentViewReturn::viewUrl($equipmentId),
        ];
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

        if (! empty($equipment['purchase_date'])) {
            $details = [];
            if ($equipment['purchase_cost'] !== null) {
                $details[] = 'Cost: ₱'.number_format($equipment['purchase_cost'], 2);
            }
            if ($equipment['supplier_name']) {
                $details[] = 'Supplier: '.$equipment['supplier_name'];
            }

            $events->push(self::makeEvent(
                'acquisition',
                $equipment['purchase_date'],
                'Purchased',
                $details ? implode(' · ', $details) : 'Equipment purchase recorded.',
                [
                    'purchase_cost' => $equipment['purchase_cost'],
                    'supplier_name' => $equipment['supplier_name'],
                ]
            ));
        }

        if (
            ! empty($equipment['acquired_date'])
            && $equipment['acquired_date'] !== ($equipment['purchase_date'] ?? null)
        ) {
            $events->push(self::makeEvent(
                'acquisition',
                $equipment['acquired_date'],
                'Acquired / deployed',
                'Equipment marked as acquired or deployed.',
                []
            ));
        }

        if (! empty($equipment['receiving_report_id'])) {
            $events->push(self::makeEvent(
                'acquisition',
                $equipment['receiving_report_date'] ?: $equipment['purchase_date'] ?: $equipment['created_at'],
                'Received via receiving report',
                'Linked to receiving report #'.($equipment['receiving_report_number'] ?: $equipment['receiving_report_id']).'.',
                [
                    'receiving_report_id' => $equipment['receiving_report_id'],
                    'receiving_report_number' => $equipment['receiving_report_number'],
                ]
            ));
        }

        return $events;
    }

    private static function transferEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('equipment_transfer_history_table')) {
            return collect();
        }

        return DB::table('equipment_transfer_history_table')
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
            )
            ->where('equipment_transfer_history_table.equipment_id', $equipmentId)
            ->orderByDesc('equipment_transfer_history_table.created_at')
            ->select(
                'equipment_transfer_history_table.transfer_id',
                'equipment_transfer_history_table.remarks',
                'equipment_transfer_history_table.created_at',
                'from_room.room_name as from_room_name',
                'to_room.room_name as to_room_name'
            )
            ->get()
            ->map(function ($row) {
                $from = $row->from_room_name ?: 'Unassigned';
                $to = $row->to_room_name ?: 'Unassigned';

                return self::makeEvent(
                    'transfer',
                    $row->created_at,
                    'Transferred',
                    $from.' → '.$to.($row->remarks ? ' · '.$row->remarks : ''),
                    [
                        'transfer_id' => (int) $row->transfer_id,
                        'from_room' => $from,
                        'to_room' => $to,
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
                        'personnel_name' => $row->personnel_name,
                    ]
                );
            });
    }

    private static function reportEvents(int $equipmentId): Collection
    {
        if (! Schema::hasTable('reports_table')) {
            return collect();
        }

        return DB::table('reports_table')
            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )
            ->where('reports_table.report_equipment_id', $equipmentId)
            ->orderByDesc('reports_table.report_submitted_at')
            ->select(
                'reports_table.report_id',
                'reports_table.report_current_status',
                'reports_table.report_urgency_level',
                'reports_table.report_suggested_issue',
                'reports_table.report_submitted_at',
                'reporters_table.reporter_full_name'
            )
            ->get()
            ->map(function ($row) {
                return self::makeEvent(
                    'report',
                    $row->report_submitted_at,
                    'Report #'.$row->report_id,
                    ($row->report_suggested_issue ?: 'No issue named')
                        .' · '.$row->report_current_status
                        .' · '.$row->report_urgency_level
                        .($row->reporter_full_name ? ' · '.$row->reporter_full_name : ''),
                    [
                        'report_id' => (int) $row->report_id,
                        'status' => $row->report_current_status,
                        'urgency' => $row->report_urgency_level,
                        'issue' => $row->report_suggested_issue,
                        'reporter' => $row->reporter_full_name,
                    ]
                );
            });
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
