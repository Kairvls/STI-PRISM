<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportItems
{
    public static function tableExists(): bool
    {
        return Schema::hasTable('report_items_table');
    }

    /**
     * @param  array<int, array{
     *     equipment_id?: int|null,
     *     unlisted_name?: string|null,
     *     suggested_issue?: string|null,
     *     problem_description?: string|null,
     *     uploaded_image?: string|null,
     *     status?: string|null
     * }>  $items
     */
    public static function createForReport(int $reportId, array $items, string $defaultStatus = 'Pending'): void
    {
        if (! self::tableExists() || $items === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($items as $item) {
            $equipmentId = isset($item['equipment_id']) && $item['equipment_id'] !== null && $item['equipment_id'] !== ''
                ? (int) $item['equipment_id']
                : null;
            $unlisted = trim((string) ($item['unlisted_name'] ?? ''));

            if ($equipmentId === null && $unlisted === '') {
                continue;
            }

            $rows[] = [
                'report_id' => $reportId,
                'report_item_equipment_id' => $equipmentId,
                'report_item_unlisted_equipment_name' => $unlisted !== '' ? $unlisted : null,
                'report_item_suggested_issue' => $item['suggested_issue'] ?? null,
                'report_item_problem_description' => $item['problem_description'] ?? null,
                'report_item_uploaded_image' => $item['uploaded_image'] ?? null,
                'report_item_status' => $item['status'] ?? $defaultStatus,
                'report_item_created_at' => $now,
                'report_item_updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('report_items_table')->insert($rows);
        }
    }

    public static function forReport(int $reportId): Collection
    {
        if (! self::tableExists()) {
            return collect();
        }

        return self::itemsQuery()
            ->where('report_items_table.report_id', $reportId)
            ->orderBy('report_items_table.report_item_id')
            ->get();
    }

    public static function displayName(object $item): string
    {
        $name = trim((string) ($item->equipment_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $manual = trim((string) ($item->report_item_unlisted_equipment_name ?? ''));

        return $manual !== '' ? $manual : 'Unlisted equipment';
    }

    /**
     * Shared select + joins for report line items with equipment details.
     */
    public static function itemsQuery()
    {
        $query = DB::table('report_items_table')
            ->leftJoin(
                'equipment_table',
                'report_items_table.report_item_equipment_id',
                '=',
                'equipment_table.equipment_id'
            );

        if (Schema::hasTable('equipment_categories_table')) {
            $query->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            );
        }

        if (Schema::hasTable('rooms_table')) {
            $query->leftJoin(
                'rooms_table',
                'equipment_table.equipment_room_id',
                '=',
                'rooms_table.room_id'
            );
        }

        $select = [
            'report_items_table.*',
            'equipment_table.equipment_name',
            'equipment_table.equipment_asset_tag',
            'equipment_table.equipment_brand_name',
            'equipment_table.equipment_model',
            'equipment_table.equipment_serial_number',
            'equipment_table.equipment_quantity',
            'equipment_table.equipment_condition_status',
            'equipment_table.equipment_inventory_status',
            'equipment_table.equipment_purchase_date',
            'equipment_table.equipment_purchase_cost',
            'equipment_table.equipment_acquired_date',
            'equipment_table.equipment_warranty_expiration',
            'equipment_table.equipment_is_borrowable',
            'equipment_table.equipment_image',
            'equipment_table.equipment_current_location',
        ];

        if (Schema::hasColumn('equipment_table', 'equipment_tracking_mode')) {
            $select[] = 'equipment_table.equipment_tracking_mode';
        }

        if (Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
            $select[] = 'equipment_table.equipment_placement_zone';
        }

        if (Schema::hasTable('rooms_table')) {
            $select[] = 'rooms_table.room_name';
            if (Schema::hasColumn('rooms_table', 'room_type')) {
                $select[] = 'rooms_table.room_type';
            }
        }

        if (Schema::hasTable('equipment_categories_table')) {
            $select[] = 'equipment_categories_table.equipment_category_name';
        }

        return $query->select($select);
    }

    public static function labelForReport(?object $report, ?Collection $items = null): string
    {
        if ($report === null) {
            return 'Not specified';
        }

        $items = $items ?? (isset($report->report_id)
            ? self::forReport((int) $report->report_id)
            : collect());

        if ($items->isNotEmpty()) {
            $names = $items->map(fn ($item) => self::displayName($item))->filter()->values();

            if ($names->count() === 1) {
                return (string) $names->first();
            }

            if ($names->count() > 1) {
                return $names->first().' +'.($names->count() - 1).' more';
            }
        }

        return (string) (
            $report->equipment_name
            ?? $report->report_unlisted_equipment_name
            ?? 'Not specified'
        );
    }

    /**
     * Split "Name +2 more" style labels into primary text + extra count.
     *
     * @return array{primary: string, more: int}
     */
    public static function splitMoreLabel(?string $label): array
    {
        $label = trim((string) $label);
        if ($label !== '' && preg_match('/^(.*?)\s\+(\d+)\s+more$/u', $label, $matches)) {
            return [
                'primary' => trim($matches[1]),
                'more' => (int) $matches[2],
            ];
        }

        return [
            'primary' => $label !== '' ? $label : 'Not specified',
            'more' => 0,
        ];
    }

    /**
     * Card/list label for issues across all items on a report.
     * Example: "Broken Monitor +1 more"
     */
    public static function issueLabelForReport(?object $report, ?Collection $items = null): string
    {
        if ($report === null) {
            return 'No suggested issue';
        }

        $items = $items ?? (isset($report->report_id)
            ? self::forReport((int) $report->report_id)
            : collect());

        $issues = $items
            ->map(function ($item) {
                $issue = trim((string) ($item->report_item_suggested_issue ?? ''));

                return $issue !== '' ? $issue : null;
            })
            ->filter()
            ->unique(fn ($issue) => mb_strtolower($issue))
            ->values();

        if ($issues->count() === 1) {
            return (string) $issues->first();
        }

        if ($issues->count() > 1) {
            return $issues->first().' +'.($issues->count() - 1).' more';
        }

        $fallback = trim((string) ($report->report_suggested_issue ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        $details = trim((string) ($report->report_problem_description ?? ''));
        if ($details !== '') {
            return \Illuminate\Support\Str::limit($details, 60);
        }

        return 'No suggested issue';
    }

    /**
     * Attach report_items + equipment_display onto report objects.
     *
     * @param  iterable<object>  $reports
     */
    public static function attachToReports(iterable $reports): void
    {
        $reports = collect($reports);

        if ($reports->isEmpty()) {
            return;
        }

        if (! self::tableExists()) {
            foreach ($reports as $report) {
                $report->report_items = collect();
                $report->equipment_display = self::labelForReport($report, collect());
                $report->issue_display = self::issueLabelForReport($report, collect());
            }

            return;
        }

        $reportIds = $reports
            ->pluck('report_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $grouped = self::itemsQuery()
            ->whereIn('report_items_table.report_id', $reportIds)
            ->orderBy('report_items_table.report_item_id')
            ->get()
            ->groupBy('report_id');

        foreach ($reports as $report) {
            $items = collect($grouped->get((int) $report->report_id, []));
            $report->report_items = $items;
            $report->equipment_display = self::labelForReport($report, $items);
            $report->issue_display = self::issueLabelForReport($report, $items);

            if (
                empty($report->equipment_name)
                && $items->isNotEmpty()
            ) {
                $report->equipment_name = self::displayName($items->first());
            }
        }
    }

    public static function openStatuses(): array
    {
        return ReportGrouping::openStatuses();
    }

    public static function terminalStatuses(): array
    {
        return ['Resolved', 'For Replacement', 'Rejected'];
    }

    public static function refreshParentStatus(int $reportId): ?string
    {
        if (! self::tableExists()) {
            return null;
        }

        $items = DB::table('report_items_table')
            ->where('report_id', $reportId)
            ->get(['report_item_status']);

        if ($items->isEmpty()) {
            return null;
        }

        $statuses = $items->pluck('report_item_status')->map(fn ($s) => (string) $s);

        $parentStatus = self::aggregateStatus($statuses->all());

        DB::table('reports_table')
            ->where('report_id', $reportId)
            ->update([
                'report_current_status' => $parentStatus,
                'report_updated_at' => now(),
            ]);

        return $parentStatus;
    }

    /**
     * @param  array<int, string>  $statuses
     */
    public static function aggregateStatus(array $statuses): string
    {
        if ($statuses === []) {
            return 'Pending';
        }

        $unique = array_values(array_unique($statuses));

        if (in_array('Processing', $unique, true)) {
            return 'Processing';
        }

        if (in_array('Pending', $unique, true)) {
            return 'Pending';
        }

        if (in_array('For Replacement', $unique, true)) {
            return 'For Replacement';
        }

        if (count($unique) === 1 && $unique[0] === 'Rejected') {
            return 'Rejected';
        }

        if (in_array('Resolved', $unique, true) && ! in_array('Rejected', $unique, true)) {
            return 'Resolved';
        }

        if (in_array('Rejected', $unique, true) && in_array('Resolved', $unique, true)) {
            return 'Resolved';
        }

        return $unique[0] ?? 'Pending';
    }

    public static function syncAllItemStatuses(int $reportId, string $status, array $extra = []): void
    {
        if (! self::tableExists()) {
            return;
        }

        $payload = array_merge($extra, [
            'report_item_status' => $status,
            'report_item_updated_at' => now(),
        ]);

        DB::table('report_items_table')
            ->where('report_id', $reportId)
            ->update($payload);
    }

    public static function updateItem(int $itemId, string $status, array $extra = []): ?object
    {
        if (! self::tableExists()) {
            return null;
        }

        $item = DB::table('report_items_table')
            ->where('report_item_id', $itemId)
            ->first();

        if (! $item) {
            return null;
        }

        $payload = array_merge($extra, [
            'report_item_status' => $status,
            'report_item_updated_at' => now(),
        ]);

        DB::table('report_items_table')
            ->where('report_item_id', $itemId)
            ->update($payload);

        self::refreshParentStatus((int) $item->report_id);

        return DB::table('report_items_table')
            ->where('report_item_id', $itemId)
            ->first();
    }

    /**
     * Ensure a legacy single-equipment report has at least one item row.
     */
    public static function ensureLegacyItem(object $report): void
    {
        if (! self::tableExists()) {
            return;
        }

        $exists = DB::table('report_items_table')
            ->where('report_id', $report->report_id)
            ->exists();

        if ($exists) {
            return;
        }

        self::createForReport((int) $report->report_id, [[
            'equipment_id' => $report->report_equipment_id ?? null,
            'unlisted_name' => $report->report_unlisted_equipment_name ?? null,
            'suggested_issue' => $report->report_suggested_issue ?? null,
            'problem_description' => $report->report_problem_description ?? null,
            'uploaded_image' => $report->report_uploaded_image ?? null,
            'status' => $report->report_current_status ?? 'Pending',
        ]], (string) ($report->report_current_status ?? 'Pending'));
    }

    /**
     * Attach a chronological timeline to each report (this ticket + past reports
     * for every equipment on the ticket).
     *
     * @param  iterable<object>  $reports
     */
    public static function attachTimelines(iterable $reports): void
    {
        $reports = collect($reports);

        if ($reports->isEmpty()) {
            return;
        }

        foreach ($reports as $report) {
            if (! isset($report->report_items)) {
                $report->report_items = self::forReport((int) $report->report_id);
            }
        }

        $equipmentIds = $reports
            ->flatMap(fn ($report) => collect($report->report_items ?? [])
                ->pluck('report_item_equipment_id')
                ->filter()
                ->map(fn ($id) => (int) $id))
            ->merge($reports->pluck('report_equipment_id')->filter()->map(fn ($id) => (int) $id))
            ->unique()
            ->values();

        $pastByEquipment = collect();

        if ($equipmentIds->isNotEmpty()) {
            $linkedReportIds = collect();

            if (self::tableExists()) {
                $linkedReportIds = $linkedReportIds->merge(
                    DB::table('report_items_table')
                        ->whereIn('report_item_equipment_id', $equipmentIds->all())
                        ->pluck('report_id')
                );
            }

            $linkedReportIds = $linkedReportIds
                ->merge(
                    DB::table('reports_table')
                        ->whereIn('report_equipment_id', $equipmentIds->all())
                        ->pluck('report_id')
                )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $pastReports = $linkedReportIds->isEmpty()
                ? collect()
                : DB::table('reports_table')
                    ->leftJoin(
                        'reporters_table',
                        'reports_table.report_reporter_employee_id',
                        '=',
                        'reporters_table.reporter_employee_id'
                    )
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
                    ->whereIn('reports_table.report_id', $linkedReportIds->all())
                    ->orderByDesc('reports_table.report_submitted_at')
                    ->select(
                        'reports_table.report_id',
                        'reports_table.report_equipment_id',
                        'reports_table.report_unlisted_equipment_name',
                        'reports_table.report_reporter_employee_id',
                        'reports_table.report_urgency_level',
                        'reports_table.report_current_status',
                        'reports_table.report_suggested_issue',
                        'reports_table.report_problem_description',
                        'reports_table.report_submitted_at',
                        'reporters_table.reporter_full_name',
                        'rooms_table.room_name',
                        'equipment_table.equipment_name'
                    )
                    ->get();

            $itemsByPastReport = self::tableExists() && $pastReports->isNotEmpty()
                ? DB::table('report_items_table')
                    ->leftJoin(
                        'equipment_table',
                        'report_items_table.report_item_equipment_id',
                        '=',
                        'equipment_table.equipment_id'
                    )
                    ->whereIn('report_items_table.report_id', $pastReports->pluck('report_id')->all())
                    ->select(
                        'report_items_table.report_id',
                        'report_items_table.report_item_equipment_id',
                        'report_items_table.report_item_unlisted_equipment_name',
                        'equipment_table.equipment_name'
                    )
                    ->get()
                    ->groupBy('report_id')
                : collect();

            foreach ($pastReports as $past) {
                $pastItems = collect($itemsByPastReport->get($past->report_id, []));
                $eqIds = $pastItems
                    ->pluck('report_item_equipment_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($eqIds->isEmpty() && ! empty($past->report_equipment_id)) {
                    $eqIds = collect([(int) $past->report_equipment_id]);
                }

                foreach ($eqIds as $eqId) {
                    $pastByEquipment->push((object) [
                        'equipment_id' => $eqId,
                        'report' => $past,
                        'items' => $pastItems,
                    ]);
                }
            }

            $pastByEquipment = $pastByEquipment->groupBy('equipment_id');
        }

        foreach ($reports as $report) {
            $report->report_timeline = self::buildTimeline($report, $pastByEquipment);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>  $pastByEquipment
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function buildTimeline(object $report, $pastByEquipment = null): Collection
    {
        $events = collect();
        $items = collect($report->report_items ?? []);
        $roomName = $report->room_name ?? null;
        $reporterName = $report->reporter_full_name ?? 'Unknown reporter';
        $employeeId = $report->reporter_employee_id ?? $report->report_reporter_employee_id ?? null;

        $itemNames = $items->map(fn ($item) => self::displayName($item))->filter()->values();
        if ($itemNames->isEmpty()) {
            $itemNames = collect([
                $report->equipment_display
                    ?? $report->equipment_name
                    ?? $report->report_unlisted_equipment_name
                    ?? 'Equipment',
            ]);
        }

        $filedLabel = $itemNames->count() > 1
            ? $itemNames->first().' +'.($itemNames->count() - 1).' more'
            : (string) $itemNames->first();

        $events->push((object) [
            'type' => 'filed',
            'at' => $report->report_submitted_at,
            'title' => $filedLabel,
            'subtitle' => 'Ticket filed'
                .($report->report_suggested_issue ? ': '.$report->report_suggested_issue : '')
                .($roomName ? ' in '.$roomName : ''),
            'urgency' => $report->report_urgency_level ?? null,
            'status_label' => 'Submitted',
            'status_key' => 'Pending',
            'meta' => trim($reporterName.($employeeId ? ' · '.$employeeId : '')),
            'notes' => $report->report_problem_description ?? null,
            'is_current' => true,
        ]);

        foreach ($items as $item) {
            $status = (string) ($item->report_item_status ?? 'Pending');
            $created = $item->report_item_created_at ?? null;
            $updated = $item->report_item_updated_at ?? $created;

            if ($status === 'Pending' && (string) $created === (string) $updated) {
                continue;
            }

            $statusLabel = match ($status) {
                'Pending' => 'Waiting for staff',
                'Processing' => 'In progress',
                'Resolved' => 'Resolved',
                'Rejected' => 'Rejected',
                'For Replacement' => 'For replacement',
                default => $status,
            };

            $notes = $item->report_item_resolution_notes
                ?? $item->report_item_replacement_notes
                ?? $item->report_item_rejection_notes
                ?? $item->report_item_suggested_issue
                ?? null;

            $events->push((object) [
                'type' => 'item_status',
                'at' => $updated ?: $report->report_submitted_at,
                'title' => self::displayName($item),
                'subtitle' => $statusLabel
                    .(! empty($item->report_item_suggested_issue) ? ' · '.$item->report_item_suggested_issue : ''),
                'urgency' => $report->report_urgency_level ?? null,
                'status_label' => $statusLabel,
                'status_key' => $status,
                'meta' => null,
                'notes' => $notes,
                'is_current' => true,
            ]);
        }

        $equipmentIds = $items
            ->pluck('report_item_equipment_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($equipmentIds->isEmpty() && ! empty($report->report_equipment_id)) {
            $equipmentIds = collect([(int) $report->report_equipment_id]);
        }

        $currentId = (int) $report->report_id;
        $seenPast = [];

        foreach ($equipmentIds as $equipmentId) {
            $bucket = collect($pastByEquipment?->get($equipmentId, []));

            foreach ($bucket as $entry) {
                $past = $entry->report;
                $pastId = (int) $past->report_id;
                if ($pastId === $currentId || isset($seenPast[$pastId.'-'.$equipmentId])) {
                    continue;
                }
                $seenPast[$pastId.'-'.$equipmentId] = true;

                $pastName = null;
                foreach (collect($entry->items ?? []) as $pastItem) {
                    if ((int) ($pastItem->report_item_equipment_id ?? 0) === $equipmentId) {
                        $pastName = self::displayName($pastItem);
                        break;
                    }
                }
                $pastName = $pastName
                    ?: ($past->equipment_name ?? $past->report_unlisted_equipment_name ?? 'Equipment');

                $statusLabel = match ((string) $past->report_current_status) {
                    'Pending' => 'Waiting for staff',
                    'Processing' => 'In progress',
                    'Resolved' => 'Fixed',
                    'Rejected' => 'Not accepted',
                    'For Replacement' => 'Needs replacement',
                    default => (string) $past->report_current_status,
                };

                $events->push((object) [
                    'type' => 'past_report',
                    'at' => $past->report_submitted_at,
                    'title' => $pastName,
                    'subtitle' => ($past->report_suggested_issue ?: 'Earlier report')
                        .(! empty($past->room_name) ? ' in '.$past->room_name : '')
                        .' · '.ReportGrouping::ticketCode($past),
                    'urgency' => $past->report_urgency_level ?? null,
                    'status_label' => $statusLabel,
                    'status_key' => $past->report_current_status,
                    'meta' => trim(
                        ($past->reporter_full_name ?? 'Unknown reporter')
                        .(! empty($past->report_reporter_employee_id) ? ' · '.$past->report_reporter_employee_id : '')
                    ),
                    'notes' => $past->report_problem_description ?? null,
                    'is_current' => false,
                ]);
            }
        }

        return $events
            ->sortByDesc(function ($event) {
                try {
                    return \Carbon\Carbon::parse($event->at)->timestamp;
                } catch (\Throwable $e) {
                    return 0;
                }
            })
            ->values();
    }
}
