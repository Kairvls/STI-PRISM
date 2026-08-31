<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportGrouping
{
    public static function openStatuses(): array
    {
        return ['Pending', 'Processing'];
    }

    public static function todayCount(): int
    {
        if (! Schema::hasTable('reports_table')) {
            return 0;
        }

        return DB::table('reports_table')
            ->where('report_is_archived', false)
            ->whereBetween('report_submitted_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->count();
    }

    public static function groupedStatuses(): array
    {
        return ['Pending', 'Processing', 'Resolved', 'For Replacement'];
    }

    public static function groupBucketSql(string $table = 'reports_table'): string
    {
        return "CASE
            WHEN {$table}.report_current_status IN ('Pending', 'Processing') THEN 'open'
            WHEN {$table}.report_current_status = 'Resolved' THEN 'resolved'
            WHEN {$table}.report_current_status = 'For Replacement' THEN 'replacement'
            ELSE {$table}.report_current_status
        END";
    }

    public static function findOpenReport(int $equipmentId, int $roomId)
    {
        if (ReportItems::tableExists()) {
            $viaItem = DB::table('reports_table')
                ->join(
                    'report_items_table',
                    'report_items_table.report_id',
                    '=',
                    'reports_table.report_id'
                )
                ->where('report_items_table.report_item_equipment_id', $equipmentId)
                ->where('reports_table.report_room_id', $roomId)
                ->whereIn('report_items_table.report_item_status', self::openStatuses())
                ->where('reports_table.report_is_archived', false)
                ->orderBy('reports_table.report_submitted_at', 'asc')
                ->select('reports_table.*')
                ->first();

            if ($viaItem) {
                return $viaItem;
            }
        }

        return DB::table('reports_table')
            ->where('report_equipment_id', $equipmentId)
            ->where('report_room_id', $roomId)
            ->whereIn('report_current_status', self::openStatuses())
            ->where('report_is_archived', false)
            ->orderBy('report_submitted_at', 'asc')
            ->first();
    }

    public static function equipmentIsForReplacement(int $equipmentId): bool
    {
        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->first();

        if (!$equipment) {
            return false;
        }

        $inventoryStatus = strtolower((string) ($equipment->equipment_inventory_status ?? ''));
        $conditionStatus = strtolower((string) ($equipment->equipment_condition_status ?? ''));

        if (
            str_contains($inventoryStatus, 'replacement')
            || str_contains($inventoryStatus, 'disposed')
            || str_contains($conditionStatus, 'replacement')
        ) {
            return true;
        }

        if (ReportItems::tableExists()) {
            $viaItem = DB::table('report_items_table')
                ->join(
                    'reports_table',
                    'reports_table.report_id',
                    '=',
                    'report_items_table.report_id'
                )
                ->where('report_items_table.report_item_equipment_id', $equipmentId)
                ->where('report_items_table.report_item_status', 'For Replacement')
                ->where('reports_table.report_is_archived', false)
                ->exists();

            if ($viaItem) {
                return true;
            }
        }

        return DB::table('reports_table')
            ->where('report_equipment_id', $equipmentId)
            ->where('report_current_status', 'For Replacement')
            ->where('report_is_archived', false)
            ->exists();
    }

    public static function applyReporterEquipmentFilters($query)
    {
        $query = $query
            ->where('equipment_inventory_status', 'Active')
            ->whereNotIn('equipment_inventory_status', ['Disposed', 'For Replacement'])
            ->where('equipment_condition_status', 'Good')
            ->whereNotIn('equipment_id', function ($subQuery) {
                $subQuery
                    ->select('report_equipment_id')
                    ->from('reports_table')
                    ->whereNotNull('report_equipment_id')
                    ->where('report_current_status', 'For Replacement')
                    ->where('report_is_archived', false);
            })
            // Hide equipment that already has an open maintenance report.
            ->whereNotIn('equipment_id', function ($subQuery) {
                $subQuery
                    ->select('report_equipment_id')
                    ->from('reports_table')
                    ->whereNotNull('report_equipment_id')
                    ->whereIn('report_current_status', self::openStatuses())
                    ->where('report_is_archived', false);
            });

        if (ReportItems::tableExists()) {
            $query->whereNotIn('equipment_id', function ($subQuery) {
                $subQuery
                    ->select('report_items_table.report_item_equipment_id')
                    ->from('report_items_table')
                    ->join(
                        'reports_table',
                        'reports_table.report_id',
                        '=',
                        'report_items_table.report_id'
                    )
                    ->whereNotNull('report_items_table.report_item_equipment_id')
                    ->where('report_items_table.report_item_status', 'For Replacement')
                    ->where('reports_table.report_is_archived', false);
            });

            $query->whereNotIn('equipment_id', function ($subQuery) {
                $subQuery
                    ->select('report_items_table.report_item_equipment_id')
                    ->from('report_items_table')
                    ->join(
                        'reports_table',
                        'reports_table.report_id',
                        '=',
                        'report_items_table.report_id'
                    )
                    ->whereNotNull('report_items_table.report_item_equipment_id')
                    ->whereIn('report_items_table.report_item_status', self::openStatuses())
                    ->where('reports_table.report_is_archived', false);
            });
        }

        return $query;
    }

    public static function mergeIntoOpenReport(
        object $openReport,
        array $incoming
    ): int {
        $countColumn = Schema::hasColumn('reports_table', 'report_related_count');
        $notesColumn = Schema::hasColumn('reports_table', 'report_related_notes');

        $updates = [
            'report_updated_at' => now(),
        ];

        if (
            ($incoming['urgency'] ?? 'Non-Urgent') === 'Urgent'
            && $openReport->report_urgency_level !== 'Urgent'
        ) {
            $updates['report_urgency_level'] = 'Urgent';
        }

        if (
            self::hasPreferredActionDateColumn()
            && ($incoming['urgency'] ?? 'Non-Urgent') !== 'Urgent'
            && ($openReport->report_urgency_level ?? 'Non-Urgent') !== 'Urgent'
        ) {
            $incomingDate = $incoming['preferred_action_date'] ?? null;
            $existingDate = $openReport->report_preferred_action_date ?? null;

            if ($incomingDate) {
                if (!$existingDate || $incomingDate < $existingDate) {
                    $updates['report_preferred_action_date'] = $incomingDate;
                }
            }
        }

        if ($countColumn) {
            $updates['report_related_count'] = max(
                1,
                (int) ($openReport->report_related_count ?? 1)
            ) + 1;
        }

        if ($notesColumn) {
            $note = trim(sprintf(
                '%s — %s reported again%s',
                now()->format('M d, Y h:i A'),
                $incoming['reporter_id'] ?? 'Unknown reporter',
                !empty($incoming['issue']) ? ': '.$incoming['issue'] : '.'
            ));

            $existing = trim((string) ($openReport->report_related_notes ?? ''));

            $updates['report_related_notes'] = $existing === ''
                ? $note
                : $existing."\n".$note;
        }

        DB::table('reports_table')
            ->where('report_id', $openReport->report_id)
            ->update($updates);

        return (int) $openReport->report_id;
    }

    public static function nonUrgentReminderGraceDays(): int
    {
        return 3;
    }

    /**
     * Earliest selectable preferred date for Non-Urgent reports (today + 2 days).
     */
    public static function preferredActionDateMinimumDaysAhead(): int
    {
        return 2;
    }

    public static function preferredActionDateMinimum(): string
    {
        return today()
            ->addDays(self::preferredActionDateMinimumDaysAhead())
            ->toDateString();
    }

    public static function preferredActionDateColumn(): string
    {
        return 'report_preferred_action_date';
    }

    public static function hasPreferredActionDateColumn(): bool
    {
        return Schema::hasColumn('reports_table', self::preferredActionDateColumn());
    }

    public static function preferredActionDateRules(): array
    {
        return [
            'nullable',
            'date',
            'after_or_equal:'.self::preferredActionDateMinimum(),
        ];
    }

    public static function resolvePreferredActionDate(?string $urgency, mixed $date): ?string
    {
        if ($urgency !== 'Non-Urgent' || !self::hasPreferredActionDateColumn()) {
            return null;
        }

        $date = is_string($date) ? trim($date) : '';

        return $date === '' ? null : $date;
    }

    public static function applyNonUrgentReminderWindow($query)
    {
        $undatedDueOn = today()->subDays(self::nonUrgentReminderGraceDays())->toDateString();

        if (!self::hasPreferredActionDateColumn()) {
            return $query->whereDate('report_submitted_at', '<=', $undatedDueOn);
        }

        return $query->where(function ($due) use ($undatedDueOn) {
            $due->where(function ($dated) {
                $dated
                    ->whereNotNull('report_preferred_action_date')
                    ->whereDate('report_preferred_action_date', '<=', today());
            })->orWhere(function ($undated) use ($undatedDueOn) {
                $undated
                    ->whereNull('report_preferred_action_date')
                    ->whereDate('report_submitted_at', '<=', $undatedDueOn);
            });
        });
    }

    /**
     * Keep unusable equipment in inventory as For Replacement.
     * Disposal is intentional only (via Disposal module / Dispose action).
     */
    public static function markEquipmentForReplacement(int $equipmentId): void
    {
        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->first();

        if (!$equipment) {
            return;
        }

        $currentStatus = strtolower((string) ($equipment->equipment_inventory_status ?? ''));

        if ($currentStatus === 'disposed') {
            return;
        }

        DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->update([
                'equipment_inventory_status' => 'For Replacement',
                'equipment_condition_status' => 'Damaged',
            ]);
    }

    /** @deprecated Use markEquipmentForReplacement(); disposal is no longer automatic. */
    public static function moveUnusableEquipmentToDisposal(int $equipmentId, ?string $reason = null): void
    {
        self::markEquipmentForReplacement($equipmentId);
    }

    public static function backfillMissingDisposals(): void
    {
        // Intentionally empty: For Replacement stays in inventory until disposed on purpose.
    }

    /**
     * Ensure every Disposed inventory item has a disposal record so it appears in Disposal.
     */
    public static function ensureDisposedAppearInDisposalModule(): void
    {
        $disposedIds = DB::table('equipment_table')
            ->where('equipment_inventory_status', 'Disposed')
            ->pluck('equipment_id');

        if ($disposedIds->isEmpty()) {
            return;
        }

        $existing = DB::table('disposal_records_table')
            ->whereIn('disposal_equipment_id', $disposedIds)
            ->pluck('disposal_equipment_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = $disposedIds
            ->map(fn ($id) => (int) $id)
            ->diff($existing)
            ->values();

        foreach ($missing as $equipmentId) {
            $equipment = DB::table('equipment_table')
                ->where('equipment_id', $equipmentId)
                ->first();

            if (!$equipment) {
                continue;
            }

            $roomName = null;
            if (!empty($equipment->equipment_room_id)) {
                $roomName = DB::table('rooms_table')
                    ->where('room_id', $equipment->equipment_room_id)
                    ->value('room_name');
            }

            DB::table('disposal_records_table')->insert([
                'disposal_equipment_id' => $equipmentId,
                'disposal_reason' => 'Equipment marked as disposed.',
                'disposal_area_location' => $roomName,
                'disposal_approved_by' => Auth::id(),
                'disposal_disposed_at' => now(),
            ]);
        }
    }

    public static function syncOpenSiblings(object $report, array $updates): void
    {
        if (empty($report->report_equipment_id) || empty($report->report_room_id)) {
            return;
        }

        unset($updates['report_related_count'], $updates['report_related_notes']);

        DB::table('reports_table')
            ->where('report_equipment_id', $report->report_equipment_id)
            ->where('report_room_id', $report->report_room_id)
            ->where('report_id', '!=', $report->report_id)
            ->whereIn('report_current_status', self::openStatuses())
            ->where('report_is_archived', false)
            ->update($updates);
    }

    /**
     * Human-friendly ticket code for display (not a DB column).
     * Example: RPT-20260901-074
     */
    public static function ticketCode(object|int|null $report, mixed $submittedAt = null): string
    {
        if (is_object($report)) {
            $id = (int) ($report->report_id ?? 0);
            $submittedAt = $report->report_submitted_at ?? $submittedAt;
        } else {
            $id = (int) ($report ?? 0);
        }

        $ymd = now()->format('Ymd');
        if (! empty($submittedAt)) {
            try {
                $ymd = \Carbon\Carbon::parse($submittedAt)->format('Ymd');
            } catch (\Throwable $e) {
                // keep today fallback
            }
        }

        return sprintf('RPT-%s-%03d', $ymd, max(0, $id));
    }

    /**
     * Extract a report_id from ticket-code or raw id search text.
     */
    public static function parseTicketSearch(?string $search): ?int
    {
        $search = trim((string) $search);
        if ($search === '') {
            return null;
        }

        if (preg_match('/^RPT-(\d{8})-(\d+)$/i', $search, $matches)) {
            return (int) $matches[2];
        }

        if (preg_match('/^#?(\d+)$/', $search, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
