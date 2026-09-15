<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReplacementRequestBasket
{
    public const MAX_ITEMS = 8;

    public static function tableExists(): bool
    {
        return Schema::hasTable('procurement_request_items_table');
    }

    /**
     * Attach every unlinked "For Replacement" line on a report into pending
     * replacement-request baskets (max 8 items each, across reports/days).
     *
     * @return array<int, int> procurement_request_ids that received items
     */
    public static function attachUnlinkedForReplacementItems(int $reportId, int $createdBy): array
    {
        if ($reportId < 1) {
            return [];
        }

        if (! self::tableExists()) {
            self::ensureLegacyRequestForReport($reportId, $createdBy);

            return [];
        }

        $items = ReportItems::tableExists()
            ? ReportItems::forReport($reportId)->filter(
                fn ($item) => (string) ($item->report_item_status ?? '') === 'For Replacement'
            )
            : collect();

        if ($items->isEmpty()) {
            $report = DB::table('reports_table')->where('report_id', $reportId)->first();
            if ($report && (string) ($report->report_current_status ?? '') === 'For Replacement') {
                $items = collect([(object) [
                    'report_item_id' => null,
                    'report_id' => $reportId,
                    'report_item_equipment_id' => $report->report_equipment_id ?? null,
                    'report_item_unlisted_equipment_name' => $report->report_unlisted_equipment_name ?? null,
                    'equipment_name' => null,
                ]]);
            }
        }

        return self::attachItems($reportId, $items, $createdBy);
    }

    /**
     * @param  iterable<object>  $items
     * @return array<int, int>
     */
    public static function attachItems(int $reportId, iterable $items, int $createdBy): array
    {
        if (! self::tableExists()) {
            self::ensureLegacyRequestForReport($reportId, $createdBy);

            return [];
        }

        $payloads = [];
        foreach ($items as $item) {
            $payload = self::normalizeItemPayload($reportId, $item);
            if ($payload !== null) {
                $payloads[] = $payload;
            }
        }

        if ($payloads === []) {
            self::ensureLegacyRequestForReport($reportId, $createdBy);

            return [];
        }

        $linkedItemIds = self::linkedReportItemIds();
        $linkedKeys = self::linkedLegacyKeys();
        $fresh = [];

        foreach ($payloads as $payload) {
            $itemId = $payload['report_item_id'];
            if ($itemId !== null && isset($linkedItemIds[$itemId])) {
                continue;
            }

            $legacyKey = self::legacyKey($payload['report_id'], $payload['equipment_id'], $payload['unlisted_equipment_name']);
            if ($itemId === null && isset($linkedKeys[$legacyKey])) {
                continue;
            }

            $fresh[] = $payload;
        }

        if ($fresh === []) {
            return [];
        }

        $touched = [];
        $now = now();

        foreach ($fresh as $payload) {
            $requestId = self::resolveBasketId($payload['report_id'], $createdBy);
            if ($requestId < 1) {
                continue;
            }

            $row = [
                'procurement_request_id' => $requestId,
                'report_id' => $payload['report_id'],
                'report_item_id' => $payload['report_item_id'],
                'equipment_id' => $payload['equipment_id'],
                'unlisted_equipment_name' => $payload['unlisted_equipment_name'],
                'created_at' => $now,
            ];

            DB::table('procurement_request_items_table')->insert($row);
            $touched[$requestId] = $requestId;
        }

        return array_values($touched);
    }

    /**
     * @param  iterable<object>|\Illuminate\Contracts\Pagination\Paginator  $requests
     */
    public static function attachToRequests(iterable $requests): void
    {
        if ($requests instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $requests = collect($requests->items());
        } else {
            $requests = collect($requests)->filter(fn ($row) => is_object($row))->values();
        }

        if ($requests->isEmpty()) {
            return;
        }

        $ids = $requests
            ->map(fn ($row) => (int) ($row->procurement_request_id ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $grouped = $ids->isEmpty()
            ? collect()
            : self::itemsQuery()
                ->whereIn('procurement_request_items_table.procurement_request_id', $ids->all())
                ->orderBy('procurement_request_items_table.procurement_request_item_id')
                ->get()
                ->groupBy('procurement_request_id');

        foreach ($requests as $request) {
            if (! is_object($request)) {
                continue;
            }

            $lines = collect($grouped->get((int) ($request->procurement_request_id ?? 0), []));

            if ($lines->isEmpty()) {
                $fallbackName = trim((string) (
                    $request->equipment_name
                    ?? $request->report_unlisted_equipment_name
                    ?? ''
                ));
                $fallbackRoom = trim((string) ($request->room_name ?? ''));

                if ($fallbackName !== '') {
                    $lines = collect([(object) [
                        'procurement_request_item_id' => null,
                        'procurement_request_id' => $request->procurement_request_id ?? null,
                        'report_id' => $request->report_id ?? $request->procurement_request_report_id ?? null,
                        'report_item_id' => null,
                        'equipment_id' => $request->report_equipment_id ?? null,
                        'equipment_name' => $request->equipment_name ?? null,
                        'equipment_asset_tag' => $request->equipment_asset_tag ?? null,
                        'unlisted_equipment_name' => $request->report_unlisted_equipment_name ?? null,
                        'display_name' => $fallbackName,
                        'room_name' => $fallbackRoom !== '' ? $fallbackRoom : null,
                        'report_submitted_at' => $request->report_submitted_at ?? null,
                        'report_urgency_level' => $request->report_urgency_level ?? null,
                        'replacement_notes' => $request->report_replacement_notes ?? null,
                        'problem_description' => $request->report_problem_description ?? null,
                    ]]);
                }
            }

            foreach ($lines as $line) {
                $line->display_name = self::displayName($line);
            }

            $request->line_items = $lines;
            $request->item_count = $lines->count();
            $request->equipment_display = self::summaryLabel($lines, $request);
            $request->room_display = self::roomSummary($lines, $request);
            $request->report_ids = $lines
                ->pluck('report_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($lines->contains(fn ($line) => strcasecmp((string) ($line->report_urgency_level ?? ''), 'Urgent') === 0)) {
                $request->report_urgency_level = 'Urgent';
            }
        }
    }

    public static function itemsForRequest(int $requestId): Collection
    {
        if ($requestId < 1 || ! self::tableExists()) {
            return collect();
        }

        $lines = self::itemsQuery()
            ->where('procurement_request_items_table.procurement_request_id', $requestId)
            ->orderBy('procurement_request_items_table.procurement_request_item_id')
            ->get();

        foreach ($lines as $line) {
            $line->display_name = self::displayName($line);
        }

        return $lines;
    }

    public static function displayName(object $item): string
    {
        $name = trim((string) ($item->equipment_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $unlisted = trim((string) ($item->unlisted_equipment_name ?? $item->report_item_unlisted_equipment_name ?? ''));

        return $unlisted !== '' ? $unlisted : 'Unspecified equipment';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function risPrefillLines(object $source): array
    {
        $lines = collect($source->line_items ?? []);
        if ($lines->isEmpty() && isset($source->procurement_request_id)) {
            $lines = self::itemsForRequest((int) $source->procurement_request_id);
        }

        if ($lines->isEmpty()) {
            $name = trim((string) ($source->equipment_name ?? ''));
            if ($name === '') {
                $name = trim((string) ($source->report_unlisted_equipment_name ?? ''));
            }
            if ($name === '') {
                $name = 'Unspecified equipment';
            }

            return [[
                'name' => $name,
                'room' => trim((string) ($source->room_name ?? '')),
                'report_id' => $source->report_id ?? null,
                'asset_tag' => $source->equipment_asset_tag ?? null,
                'brand' => trim((string) ($source->equipment_brand_name ?? '')),
                'model' => trim((string) ($source->equipment_model ?? '')),
                'quantity' => 1,
                'problem' => $source->report_problem_description ?? '',
                'reason' => $source->report_replacement_notes ?? '',
            ]];
        }

        return $lines->take(self::MAX_ITEMS)->map(function ($line) {
            return [
                'name' => self::displayName($line),
                'room' => trim((string) ($line->room_name ?? '')),
                'report_id' => $line->report_id ?? null,
                'asset_tag' => $line->equipment_asset_tag ?? null,
                'brand' => trim((string) ($line->equipment_brand_name ?? '')),
                'model' => trim((string) ($line->equipment_model ?? '')),
                'quantity' => 1,
                'problem' => $line->problem_description ?? '',
                'reason' => $line->replacement_notes ?? '',
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRisBootArray(object $request): array
    {
        $lines = self::risPrefillLines($request);
        $names = collect($lines)->pluck('name')->filter()->values();
        $rooms = collect($lines)->pluck('room')->filter()->unique()->values();

        return [
            'id' => $request->procurement_request_id,
            'report_id' => $request->report_id ?? ($lines[0]['report_id'] ?? null),
            'equipment' => $request->equipment_display
                ?? ($names->count() > 1
                    ? $names->first().' +'.($names->count() - 1).' more'
                    : (string) ($names->first() ?: 'Unspecified equipment')),
            'asset_tag' => $lines[0]['asset_tag'] ?? ($request->equipment_asset_tag ?? null),
            'room' => $request->room_display
                ?? ($rooms->count() > 1 ? $rooms->implode(', ') : (string) ($rooms->first() ?: ($request->room_name ?? 'Unspecified room'))),
            'problem' => $request->report_problem_description ?? '',
            'reason' => $request->report_replacement_notes ?? '',
            'urgency' => strcasecmp(trim((string) ($request->report_urgency_level ?? '')), 'Urgent') === 0
                ? 'Urgent'
                : 'Non-Urgent',
            'item_count' => count($lines),
            'items' => $lines,
        ];
    }

    public static function summaryLabel(Collection $lines, ?object $fallback = null): string
    {
        $names = $lines->map(fn ($line) => self::displayName($line))->filter()->unique()->values();

        if ($names->count() === 1) {
            return (string) $names->first();
        }

        if ($names->count() > 1) {
            return $names->first().' +'.($names->count() - 1).' more';
        }

        return (string) (
            $fallback->equipment_name
            ?? $fallback->report_unlisted_equipment_name
            ?? 'Unknown Equipment'
        );
    }

    public static function roomSummary(Collection $lines, ?object $fallback = null): string
    {
        $rooms = $lines
            ->map(fn ($line) => trim((string) ($line->room_name ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($rooms->count() === 1) {
            return (string) $rooms->first();
        }

        if ($rooms->count() > 1) {
            return $rooms->first().' +'.($rooms->count() - 1);
        }

        return (string) ($fallback->room_name ?? 'Unknown Room');
    }

    public static function itemsQuery()
    {
        $query = DB::table('procurement_request_items_table')
            ->leftJoin(
                'report_items_table',
                'procurement_request_items_table.report_item_id',
                '=',
                'report_items_table.report_item_id'
            )
            ->leftJoin(
                'equipment_table as pri_equipment',
                'procurement_request_items_table.equipment_id',
                '=',
                'pri_equipment.equipment_id'
            )
            ->leftJoin(
                'equipment_table as item_equipment',
                'report_items_table.report_item_equipment_id',
                '=',
                'item_equipment.equipment_id'
            )
            ->leftJoin(
                'reports_table',
                'procurement_request_items_table.report_id',
                '=',
                'reports_table.report_id'
            )
            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            );

        return $query->select([
            'procurement_request_items_table.procurement_request_item_id',
            'procurement_request_items_table.procurement_request_id',
            'procurement_request_items_table.report_id',
            'procurement_request_items_table.report_item_id',
            'procurement_request_items_table.equipment_id',
            'procurement_request_items_table.unlisted_equipment_name',
            DB::raw('COALESCE(pri_equipment.equipment_name, item_equipment.equipment_name) as equipment_name'),
            DB::raw('COALESCE(pri_equipment.equipment_asset_tag, item_equipment.equipment_asset_tag) as equipment_asset_tag'),
            DB::raw('COALESCE(pri_equipment.equipment_brand_name, item_equipment.equipment_brand_name) as equipment_brand_name'),
            DB::raw('COALESCE(pri_equipment.equipment_model, item_equipment.equipment_model) as equipment_model'),
            'rooms_table.room_name',
            'reports_table.report_submitted_at',
            'reports_table.report_urgency_level',
            'reports_table.report_replacement_notes as report_replacement_notes',
            'reports_table.report_problem_description as report_problem_description',
            'report_items_table.report_item_replacement_notes as replacement_notes',
            'report_items_table.report_item_problem_description as problem_description',
            'report_items_table.report_item_unlisted_equipment_name',
        ]);
    }

    /**
     * Seed the items table from current procurement requests, then pack leftover
     * For Replacement lines and merge pending baskets into groups of 8.
     */
    public static function backfillExisting(): void
    {
        if (! self::tableExists() || ! Schema::hasTable('procurement_requests_table')) {
            return;
        }

        $requests = DB::table('procurement_requests_table')
            ->orderBy('procurement_request_id')
            ->get([
                'procurement_request_id',
                'procurement_request_report_id',
                'procurement_request_created_by',
            ]);

        foreach ($requests as $request) {
            $reportId = (int) ($request->procurement_request_report_id ?? 0);
            if ($reportId < 1) {
                continue;
            }

            $createdBy = (int) ($request->procurement_request_created_by ?? 0);
            self::attachItemsOntoRequest((int) $request->procurement_request_id, $reportId, $createdBy);
        }

        $linkedItemIds = self::linkedReportItemIds();

        if (ReportItems::tableExists()) {
            $leftover = DB::table('report_items_table')
                ->where('report_item_status', 'For Replacement')
                ->when($linkedItemIds !== [], function ($query) use ($linkedItemIds) {
                    $query->whereNotIn('report_item_id', array_keys($linkedItemIds));
                })
                ->orderBy('report_item_id')
                ->get();

            $byReport = $leftover->groupBy('report_id');
            foreach ($byReport as $reportId => $items) {
                self::attachItems((int) $reportId, $items, 0);
            }
        }

        self::consolidatePendingBaskets();
    }

    private static function attachItemsOntoRequest(int $requestId, int $reportId, int $createdBy): void
    {
        if ($requestId < 1 || $reportId < 1) {
            return;
        }

        $room = self::openSlots($requestId);
        if ($room < 1) {
            return;
        }

        if (self::requestHasRis($requestId)) {
            $room = min($room, self::MAX_ITEMS);
        }

        $items = ReportItems::tableExists()
            ? ReportItems::forReport($reportId)->filter(
                fn ($item) => (string) ($item->report_item_status ?? '') === 'For Replacement'
            )
            : collect();

        if ($items->isEmpty()) {
            $report = DB::table('reports_table')->where('report_id', $reportId)->first();
            if (! $report) {
                return;
            }

            $items = collect([(object) [
                'report_item_id' => null,
                'report_id' => $reportId,
                'report_item_equipment_id' => $report->report_equipment_id ?? null,
                'report_item_unlisted_equipment_name' => $report->report_unlisted_equipment_name ?? null,
            ]]);
        }

        $linkedItemIds = self::linkedReportItemIds();
        $linkedKeys = self::linkedLegacyKeys();
        $now = now();
        $added = 0;

        foreach ($items as $item) {
            if ($added >= $room) {
                break;
            }

            $payload = self::normalizeItemPayload($reportId, $item);
            if ($payload === null) {
                continue;
            }

            $itemId = $payload['report_item_id'];
            if ($itemId !== null && isset($linkedItemIds[$itemId])) {
                continue;
            }

            $legacyKey = self::legacyKey($payload['report_id'], $payload['equipment_id'], $payload['unlisted_equipment_name']);
            if ($itemId === null && isset($linkedKeys[$legacyKey])) {
                continue;
            }

            DB::table('procurement_request_items_table')->insert([
                'procurement_request_id' => $requestId,
                'report_id' => $payload['report_id'],
                'report_item_id' => $payload['report_item_id'],
                'equipment_id' => $payload['equipment_id'],
                'unlisted_equipment_name' => $payload['unlisted_equipment_name'],
                'created_at' => $now,
            ]);

            if ($itemId !== null) {
                $linkedItemIds[$itemId] = true;
            } else {
                $linkedKeys[$legacyKey] = true;
            }

            $added++;
        }
    }

    private static function consolidatePendingBaskets(): void
    {
        if (! Schema::hasTable('procurement_requests_table')) {
            return;
        }

        $pending = self::pendingOpenRequests();
        if ($pending->count() < 2) {
            return;
        }

        $pendingIds = $pending->pluck('procurement_request_id')->map(fn ($id) => (int) $id)->all();
        $items = DB::table('procurement_request_items_table')
            ->whereIn('procurement_request_id', $pendingIds)
            ->orderBy('procurement_request_item_id')
            ->get();

        if ($items->count() <= 1) {
            return;
        }

        $containers = $pending->values();
        $containerIndex = 0;
        $placedOnCurrent = 0;
        $usedContainerIds = [];

        foreach ($items as $item) {
            while (
                $containerIndex < $containers->count()
                && $placedOnCurrent >= self::MAX_ITEMS
            ) {
                $containerIndex++;
                $placedOnCurrent = 0;
            }

            if ($containerIndex >= $containers->count()) {
                break;
            }

            $targetId = (int) $containers[$containerIndex]->procurement_request_id;
            $usedContainerIds[$targetId] = true;

            if ((int) $item->procurement_request_id !== $targetId) {
                DB::table('procurement_request_items_table')
                    ->where('procurement_request_item_id', $item->procurement_request_item_id)
                    ->update(['procurement_request_id' => $targetId]);
            }

            $placedOnCurrent++;
        }

        $emptyIds = array_values(array_diff($pendingIds, array_keys($usedContainerIds)));
        if ($emptyIds === []) {
            return;
        }

        $emptyIds = array_values(array_filter($emptyIds, fn ($id) => ! self::requestHasRis((int) $id)));
        if ($emptyIds === []) {
            return;
        }

        DB::table('procurement_request_items_table')
            ->whereIn('procurement_request_id', $emptyIds)
            ->delete();

        DB::table('procurement_requests_table')
            ->whereIn('procurement_request_id', $emptyIds)
            ->where('procurement_request_status', 'Pending')
            ->delete();
    }

    private static function resolveBasketId(int $reportId, int $createdBy): int
    {
        $sameReportId = self::openRequestIdForReport($reportId);
        if ($sameReportId) {
            return $sameReportId;
        }

        $openId = self::latestOpenPendingId();
        if ($openId) {
            return $openId;
        }

        return self::createRequest($reportId, $createdBy);
    }

    private static function openRequestIdForReport(int $reportId): ?int
    {
        $row = DB::table('procurement_request_items_table')
            ->join(
                'procurement_requests_table',
                'procurement_request_items_table.procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )
            ->where('procurement_request_items_table.report_id', $reportId)
            ->whereIn('procurement_requests_table.procurement_request_status', ['Pending', 'Approved'])
            ->when(
                Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived'),
                fn ($query) => $query->where('procurement_requests_table.procurement_request_is_archived', false)
            )
            ->orderByDesc('procurement_requests_table.procurement_request_id')
            ->select('procurement_requests_table.procurement_request_id')
            ->first();

        if ($row) {
            $requestId = (int) $row->procurement_request_id;
            if (! self::requestHasRis($requestId) && self::openSlots($requestId) > 0) {
                return $requestId;
            }
        }

        $legacy = DB::table('procurement_requests_table')
            ->where('procurement_request_report_id', $reportId)
            ->whereIn('procurement_request_status', ['Pending', 'Approved'])
            ->when(
                Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived'),
                fn ($query) => $query->where('procurement_request_is_archived', false)
            )
            ->orderByDesc('procurement_request_id')
            ->first();

        if (! $legacy) {
            return null;
        }

        $requestId = (int) $legacy->procurement_request_id;
        if (self::requestHasRis($requestId) || self::openSlots($requestId) < 1) {
            return null;
        }

        return $requestId;
    }

    private static function latestOpenPendingId(): ?int
    {
        foreach (self::pendingOpenRequests() as $row) {
            $requestId = (int) $row->procurement_request_id;
            if (self::openSlots($requestId) > 0) {
                return $requestId;
            }
        }

        return null;
    }

    private static function pendingOpenRequests(): Collection
    {
        $query = DB::table('procurement_requests_table')
            ->where('procurement_request_status', 'Pending')
            ->when(
                Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived'),
                fn ($q) => $q->where('procurement_request_is_archived', false)
            )
            ->orderBy('procurement_request_created_at')
            ->orderBy('procurement_request_id');

        if (Schema::hasTable('requisition_issue_slip_table')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('requisition_issue_slip_table')
                    ->whereColumn(
                        'requisition_issue_slip_table.ris_procurement_request_id',
                        'procurement_requests_table.procurement_request_id'
                    );
            });
        }

        return $query->get();
    }

    private static function createRequest(int $reportId, int $createdBy): int
    {
        $payload = [
            'procurement_request_report_id' => $reportId,
            'procurement_request_status' => 'Pending',
            'procurement_request_created_by' => $createdBy > 0 ? $createdBy : null,
        ];

        if (Schema::hasColumn('procurement_requests_table', 'procurement_request_created_at')) {
            $payload['procurement_request_created_at'] = now();
        }

        if (Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived')) {
            $payload['procurement_request_is_archived'] = false;
        }

        return (int) DB::table('procurement_requests_table')->insertGetId(
            $payload,
            'procurement_request_id'
        );
    }

    private static function ensureLegacyRequestForReport(int $reportId, int $createdBy): void
    {
        $exists = DB::table('procurement_requests_table')
            ->where('procurement_request_report_id', $reportId)
            ->exists();

        if ($exists) {
            return;
        }

        self::createRequest($reportId, $createdBy);
    }

    private static function openSlots(int $requestId): int
    {
        $count = (int) DB::table('procurement_request_items_table')
            ->where('procurement_request_id', $requestId)
            ->count();

        return max(0, self::MAX_ITEMS - $count);
    }

    private static function requestHasRis(int $requestId): bool
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return false;
        }

        return DB::table('requisition_issue_slip_table')
            ->where('ris_procurement_request_id', $requestId)
            ->exists();
    }

    /**
     * @return array<int, true>
     */
    private static function linkedReportItemIds(): array
    {
        return DB::table('procurement_request_items_table')
            ->whereNotNull('report_item_id')
            ->pluck('report_item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->mapWithKeys(fn ($id) => [$id => true])
            ->all();
    }

    /**
     * @return array<string, true>
     */
    private static function linkedLegacyKeys(): array
    {
        $rows = DB::table('procurement_request_items_table')
            ->whereNull('report_item_id')
            ->get(['report_id', 'equipment_id', 'unlisted_equipment_name']);

        $keys = [];
        foreach ($rows as $row) {
            $keys[self::legacyKey(
                (int) $row->report_id,
                $row->equipment_id !== null ? (int) $row->equipment_id : null,
                $row->unlisted_equipment_name
            )] = true;
        }

        return $keys;
    }

    private static function legacyKey(int $reportId, ?int $equipmentId, ?string $unlisted): string
    {
        return $reportId.'|'.($equipmentId ?? 0).'|'.mb_strtolower(trim((string) $unlisted));
    }

    /**
     * @return array{report_id: int, report_item_id: int|null, equipment_id: int|null, unlisted_equipment_name: string|null}|null
     */
    private static function normalizeItemPayload(int $reportId, object $item): ?array
    {
        $itemReportId = (int) ($item->report_id ?? $reportId);
        if ($itemReportId < 1) {
            $itemReportId = $reportId;
        }

        $itemId = isset($item->report_item_id) && $item->report_item_id !== null && $item->report_item_id !== ''
            ? (int) $item->report_item_id
            : null;
        $equipmentId = $item->report_item_equipment_id
            ?? $item->equipment_id
            ?? null;
        $equipmentId = $equipmentId !== null && $equipmentId !== '' ? (int) $equipmentId : null;
        $unlisted = trim((string) (
            $item->report_item_unlisted_equipment_name
            ?? $item->unlisted_equipment_name
            ?? $item->equipment_name
            ?? ''
        ));

        if ($itemId === null && $equipmentId === null && $unlisted === '') {
            return null;
        }

        return [
            'report_id' => $itemReportId,
            'report_item_id' => $itemId && $itemId > 0 ? $itemId : null,
            'equipment_id' => $equipmentId && $equipmentId > 0 ? $equipmentId : null,
            'unlisted_equipment_name' => $unlisted !== '' ? $unlisted : null,
        ];
    }
}
