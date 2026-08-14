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

        return DB::table('reports_table')
            ->where('report_equipment_id', $equipmentId)
            ->where('report_current_status', 'For Replacement')
            ->where('report_is_archived', false)
            ->exists();
    }

    public static function applyReporterEquipmentFilters($query)
    {
        return $query
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
            });
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

    public static function moveUnusableEquipmentToDisposal(int $equipmentId, ?string $reason = null): void
    {
        $equipment = DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->first();

        if (!$equipment) {
            return;
        }

        $roomName = null;
        if (!empty($equipment->equipment_room_id)) {
            $roomName = DB::table('rooms_table')
                ->where('room_id', $equipment->equipment_room_id)
                ->value('room_name');
        }

        $currentStatus = strtolower((string) ($equipment->equipment_inventory_status ?? ''));

        if ($currentStatus !== 'disposed') {
            DB::table('equipment_table')
                ->where('equipment_id', $equipmentId)
                ->update([
                    'equipment_inventory_status' => 'For Replacement',
                    'equipment_condition_status' => 'Damaged',
                ]);
        }

        $alreadyDisposed = DB::table('disposal_records_table')
            ->where('disposal_equipment_id', $equipmentId)
            ->exists();

        if ($alreadyDisposed) {
            return;
        }

        $reasonText = trim((string) $reason);
        if ($reasonText === '') {
            $reasonText = 'Marked for replacement — equipment is no longer functional.';
        }

        $approverId = Auth::id();
        if (
            $approverId
            && !DB::table('users_table')->where('user_id', $approverId)->exists()
        ) {
            $approverId = null;
        }

        DB::table('disposal_records_table')->insert([
            'disposal_equipment_id' => $equipmentId,
            'disposal_reason' => $reasonText,
            'disposal_area_location' => $roomName,
            'disposal_approved_by' => $approverId,
            'disposal_disposed_at' => now(),
        ]);
    }

    public static function backfillMissingDisposals(): void
    {
        $fromStatus = DB::table('equipment_table')
            ->where('equipment_inventory_status', 'For Replacement')
            ->pluck('equipment_id');

        $fromReports = DB::table('reports_table')
            ->where('report_current_status', 'For Replacement')
            ->whereNotNull('report_equipment_id')
            ->pluck('report_equipment_id');

        foreach ($fromStatus->merge($fromReports)->unique()->filter() as $equipmentId) {
            self::moveUnusableEquipmentToDisposal((int) $equipmentId);
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
}
