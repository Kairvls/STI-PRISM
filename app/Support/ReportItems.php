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
            $report->report_items = collect($grouped->get((int) $report->report_id, []));
        }

        self::annotateRepeats($reports);

        foreach ($reports as $report) {
            $items = collect($report->report_items);
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

        self::syncRepeatEquipment($reportId);

        return $parentStatus;
    }

    /**
     * Once equipment is fixed or sent for replacement on one ticket, close the
     * same equipment on every other open ticket that re-reported it.
     */
    public static function syncRepeatEquipment(int $reportId): void
    {
        static $running = false;

        if ($running || ! self::tableExists()) {
            return;
        }

        $handled = DB::table('report_items_table')
            ->where('report_id', $reportId)
            ->whereNotNull('report_item_equipment_id')
            ->whereIn('report_item_status', ['Resolved', 'For Replacement'])
            ->get();

        if ($handled->isEmpty()) {
            return;
        }

        $running = true;

        try {
            $ticket = ReportGrouping::ticketCode(
                DB::table('reports_table')->where('report_id', $reportId)->first() ?? $reportId
            );
            $touchedReports = [];

            foreach ($handled as $item) {
                $siblings = DB::table('report_items_table')
                    ->join('reports_table', 'reports_table.report_id', '=', 'report_items_table.report_id')
                    ->where('report_items_table.report_item_equipment_id', $item->report_item_equipment_id)
                    ->where('report_items_table.report_id', '!=', $reportId)
                    ->whereIn('report_items_table.report_item_status', self::openStatuses())
                    ->where('reports_table.report_is_archived', false)
                    ->where('reports_table.report_submitted_at', '<=', $item->report_item_updated_at ?? now())
                    ->get(['report_items_table.report_item_id', 'report_items_table.report_id']);

                if ($siblings->isEmpty()) {
                    continue;
                }

                $isReplacement = $item->report_item_status === 'For Replacement';
                $remarks = trim((string) ($isReplacement
                    ? $item->report_item_replacement_notes
                    : $item->report_item_resolution_notes));
                $note = ($isReplacement ? 'Sent for replacement under ' : 'Fixed under ').$ticket
                    .($remarks !== '' ? ': '.$remarks : '.');

                DB::table('report_items_table')
                    ->whereIn('report_item_id', $siblings->pluck('report_item_id')->all())
                    ->update(array_merge(
                        [
                            'report_item_status' => $item->report_item_status,
                            'report_item_updated_at' => now(),
                        ],
                        $isReplacement
                            ? ['report_item_replacement_notes' => $note]
                            : ['report_item_resolution_notes' => $note]
                    ));

                foreach ($siblings->pluck('report_id') as $siblingReportId) {
                    $touchedReports[(int) $siblingReportId] = true;
                }
            }

            foreach (array_keys($touchedReports) as $siblingReportId) {
                self::refreshParentStatus($siblingReportId);
            }
        } finally {
            $running = false;
        }
    }

    /**
     * Flag items whose equipment is also on another open ticket, and list
     * "reported before, still not actioned" items first.
     *
     * Sets on each item: repeat_state (not_actioned | in_progress | reported_again | null),
     * repeat_earlier (oldest other open ticket filed before this one), repeat_earlier_count,
     * repeat_later_count, repeat_latest (newest later ticket), repeat_times_reported.
     *
     * @param  iterable<object>  $reports
     */
    public static function annotateRepeats(iterable $reports): void
    {
        $reports = collect($reports);

        $equipmentIds = $reports
            ->flatMap(fn ($report) => collect($report->report_items ?? [])->pluck('report_item_equipment_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($equipmentIds->isEmpty() || ! self::tableExists()) {
            return;
        }

        $openTickets = DB::table('report_items_table')
            ->join('reports_table', 'reports_table.report_id', '=', 'report_items_table.report_id')
            ->whereIn('report_items_table.report_item_equipment_id', $equipmentIds->all())
            ->whereIn('report_items_table.report_item_status', self::openStatuses())
            ->where('reports_table.report_is_archived', false)
            ->orderBy('reports_table.report_submitted_at')
            ->orderBy('reports_table.report_id')
            ->get([
                'report_items_table.report_item_equipment_id',
                'report_items_table.report_item_status',
                'reports_table.report_id',
                'reports_table.report_current_status',
                'reports_table.report_submitted_at',
            ])
            ->groupBy('report_item_equipment_id');

        $timesReported = DB::table('report_items_table')
            ->whereIn('report_item_equipment_id', $equipmentIds->all())
            ->select('report_item_equipment_id as equipment_id', 'report_id')
            ->union(
                DB::table('reports_table')
                    ->whereIn('report_equipment_id', $equipmentIds->all())
                    ->select('report_equipment_id as equipment_id', 'report_id')
            )
            ->get()
            ->groupBy('equipment_id')
            ->map(fn ($rows) => $rows->pluck('report_id')->unique()->count());

        $priority = ['not_actioned' => 0, 'in_progress' => 1, 'reported_again' => 2];

        foreach ($reports as $report) {
            $reportId = (int) ($report->report_id ?? 0);
            $filedAt = (string) ($report->report_submitted_at ?? '');

            $items = collect($report->report_items ?? [])->each(function ($item) use ($openTickets, $timesReported, $reportId, $filedAt) {
                $item->repeat_state = null;
                $item->repeat_earlier = null;
                $item->repeat_earlier_count = 0;
                $item->repeat_later_count = 0;
                $item->repeat_latest = null;

                $equipmentId = (int) ($item->report_item_equipment_id ?? 0);
                $item->repeat_times_reported = (int) ($timesReported->get($equipmentId) ?? 0);

                if ($equipmentId < 1 || ! in_array($item->report_item_status, self::openStatuses(), true)) {
                    return;
                }

                $others = collect($openTickets->get($equipmentId, []))
                    ->filter(fn ($row) => (int) $row->report_id !== $reportId)
                    ->map(function ($row) {
                        $status = $row->report_item_status === 'Pending' && $row->report_current_status === 'Processing'
                            ? 'Processing'
                            : $row->report_item_status;

                        return (object) [
                            'report_id' => (int) $row->report_id,
                            'ticket_code' => ReportGrouping::ticketCode($row),
                            'submitted_at' => $row->report_submitted_at,
                            'status' => $status,
                        ];
                    })
                    ->unique('report_id');

                $isEarlier = fn ($other) => $other->submitted_at < $filedAt
                    || ($other->submitted_at === $filedAt && $other->report_id < $reportId);

                $earlier = $others->filter($isEarlier)->values();
                $later = $others->reject($isEarlier)->values();

                $item->repeat_earlier_count = $earlier->count();
                $item->repeat_later_count = $later->count();
                $item->repeat_latest = $later->last();

                if ($earlier->isNotEmpty()) {
                    $waiting = $earlier->firstWhere('status', 'Pending');
                    $item->repeat_earlier = $waiting ?? $earlier->first();
                    $item->repeat_state = $waiting ? 'not_actioned' : 'in_progress';
                } elseif ($later->isNotEmpty()) {
                    $item->repeat_state = 'reported_again';
                }
            });

            $report->report_items = $items
                ->sortBy(fn ($item) => [
                    $priority[$item->repeat_state ?? ''] ?? 3,
                    (int) ($item->report_item_id ?? 0),
                ])
                ->values();

            $report->repeat_flagged_count = $report->report_items
                ->filter(fn ($item) => in_array($item->repeat_state, ['not_actioned', 'in_progress'], true))
                ->count();
        }
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

        foreach ($reports as $report) {
            $report->report_timeline = self::buildTimeline($report);
        }
    }

    /**
     * This ticket's events plus every other report on the same equipment
     * (when it was reported, fixed, sent for replacement or not accepted).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function buildTimeline(object $report): Collection
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

        foreach ($equipmentIds->unique() as $equipmentId) {
            $history = self::equipmentHistory($equipmentId);
            $timesReported = $history->count();

            foreach ($history->values() as $index => $past) {
                $pastId = (int) $past->report_id;
                if ($pastId === $currentId || isset($seenPast[$pastId.'-'.$equipmentId])) {
                    continue;
                }
                $seenPast[$pastId.'-'.$equipmentId] = true;

                $pastName = self::equipmentNameFor($items, $equipmentId) ?? 'Equipment';
                $ticket = ReportGrouping::ticketCode($past);
                $status = (string) $past->status;

                $statusLabel = match ($status) {
                    'Pending' => 'Not actioned yet',
                    'Processing' => 'In progress',
                    'Resolved' => 'Fixed',
                    'Rejected' => 'Not accepted',
                    'For Replacement' => 'Needs replacement',
                    default => $status,
                };

                $events->push((object) [
                    'type' => 'past_report',
                    'at' => $past->report_submitted_at,
                    'title' => $pastName,
                    'subtitle' => 'Malfunction reported ('.($index + 1).' of '.$timesReported.')'
                        .' · '.($past->issue ?: 'No issue named')
                        .(! empty($past->room_name) ? ' in '.$past->room_name : '')
                        .' · '.$ticket,
                    'urgency' => $past->report_urgency_level ?? null,
                    'status_label' => $statusLabel,
                    'status_key' => $status,
                    'meta' => $past->reporter_full_name ?? null,
                    'notes' => null,
                    'is_current' => false,
                    'ticket' => $ticket,
                    'ticket_archived' => (bool) ($past->report_is_archived ?? false),
                ]);

                $outcome = match ($status) {
                    'Resolved' => ['Fixed · back to working', $past->resolution_notes],
                    'For Replacement' => ['Marked for replacement', $past->replacement_notes],
                    'Rejected' => ['Report not accepted', $past->rejection_notes],
                    default => null,
                };

                if ($outcome && ! empty($past->status_at)) {
                    $events->push((object) [
                        'type' => 'past_outcome',
                        'at' => $past->status_at,
                        'title' => $pastName,
                        'subtitle' => $outcome[0].' · '.$ticket,
                        'urgency' => null,
                        'status_label' => $statusLabel,
                        'status_key' => $status,
                        'meta' => null,
                        'notes' => $outcome[1] ?: null,
                        'is_current' => false,
                        'ticket' => $ticket,
                        'ticket_archived' => (bool) ($past->report_is_archived ?? false),
                    ]);
                }
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

    private static function equipmentHistory(int $equipmentId): Collection
    {
        static $cache = [];

        return $cache[$equipmentId] ??= EquipmentTimeline::reportHistory($equipmentId);
    }

    private static function equipmentNameFor(Collection $items, int $equipmentId): ?string
    {
        foreach ($items as $item) {
            if ((int) ($item->report_item_equipment_id ?? 0) === $equipmentId) {
                return self::displayName($item);
            }
        }

        return null;
    }
}
