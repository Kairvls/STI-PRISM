<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaintenanceAttentionSummary
{
    public const FOCUS_URGENT_ACTION = 'urgent-action';

    public const FOCUS_NON_URGENT_PENDING = 'non-urgent-pending';

    public const FOCUS_OVERDUE = 'overdue';

    /** @var array<int, array<string, int>> */
    private static array $cached = [];

    /**
     * Counts used by the maintenance daily reminder / attention UI.
     * Report counts are list rows (one per open equipment+room group), not raw reports.
     *
     * @return array{
     *     urgentReportsNeedingAction: int,
     *     nonUrgentReportsNeedingAction: int,
     *     overdueMaintenance: int,
     *     overdueBorrowings: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        $userId = (int) Auth::id();

        if (isset(self::$cached[$userId])) {
            return self::$cached[$userId];
        }

        $urgentReportsNeedingAction = (int) self::scopeUrgentAction(
            self::groupedReportRows(),
            $userId
        )->count();

        $nonUrgentReportsNeedingAction = (int) self::scopeNonUrgentPending(
            self::groupedReportRows()
        )->count();

        $overdueMaintenance = (int) self::scopeOverdueSchedules(
            DB::table('maintenance_schedules_table')
        )->count();

        $overdueBorrowings = (int) self::scopeOverdueBorrowings(
            DB::table('borrowing_records_table')
        )->count();

        return self::$cached[$userId] = [
            'urgentReportsNeedingAction' => $urgentReportsNeedingAction,
            'nonUrgentReportsNeedingAction' => $nonUrgentReportsNeedingAction,
            'overdueMaintenance' => $overdueMaintenance,
            'overdueBorrowings' => $overdueBorrowings,
            'attentionTotal' => $urgentReportsNeedingAction
                + $nonUrgentReportsNeedingAction
                + $overdueMaintenance
                + $overdueBorrowings,
        ];
    }

    /**
     * Active (non-archived) report list rows exactly as the maintenance report pages list them.
     */
    public static function groupedReportRows()
    {
        return self::joinReportGroupStats(
            self::applyActiveGroupRows(
                DB::table('reports_table')->where('reports_table.report_is_archived', false)
            )
        );
    }

    /**
     * One card/row per equipment+room group, including Resolved and For Replacement.
     * Tickets with several equipment keep their own row. An exact ticket-code search
     * still finds a ticket stacked under a newer one.
     */
    public static function applyActiveGroupRows($query, ?int $exactTicketId = null)
    {
        return $query->where(function ($groupQuery) use ($exactTicketId) {
            $groupQuery
                ->when($exactTicketId, fn ($q) => $q->orWhere('reports_table.report_id', $exactTicketId))
                ->orWhereNull('reports_table.report_equipment_id')
                ->when(
                    ReportItems::tableExists(),
                    fn ($q) => $q->orWhereRaw(ReportGrouping::multiItemReportSql())
                )
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

    /**
     * Adds the `open_report_group` stats (stack size, has_urgent, last reported) per row.
     */
    public static function joinReportGroupStats($query)
    {
        return $query->leftJoin(
            DB::raw('(
                SELECT
                    report_equipment_id,
                    report_room_id,
                    CASE
                        WHEN report_current_status IN (\'Pending\', \'Processing\') THEN \'open\'
                        WHEN report_current_status = \'Resolved\' THEN \'resolved\'
                        WHEN report_current_status = \'For Replacement\' THEN \'replacement\'
                        ELSE report_current_status
                    END AS report_group_bucket,
                    COUNT(*) AS open_count,
                    MAX(CASE WHEN report_urgency_level = \'Urgent\' THEN 1 ELSE 0 END) AS has_urgent,
                    MAX('.ReportGrouping::lastReportedSql('reports_table').') AS group_last_reported_at
                FROM reports_table
                WHERE report_equipment_id IS NOT NULL
                  AND report_is_archived = 0
                  AND report_current_status IN (\'Pending\', \'Processing\', \'Resolved\', \'For Replacement\')
                GROUP BY
                    report_equipment_id,
                    report_room_id,
                    CASE
                        WHEN report_current_status IN (\'Pending\', \'Processing\') THEN \'open\'
                        WHEN report_current_status = \'Resolved\' THEN \'resolved\'
                        WHEN report_current_status = \'For Replacement\' THEN \'replacement\'
                        ELSE report_current_status
                    END
            ) AS open_report_group'),
            function ($join) {
                $join
                    ->on(
                        'open_report_group.report_equipment_id',
                        '=',
                        'reports_table.report_equipment_id'
                    )
                    ->on(
                        'open_report_group.report_room_id',
                        '=',
                        'reports_table.report_room_id'
                    )
                    ->whereRaw(
                        'open_report_group.report_group_bucket = '.ReportGrouping::groupBucketSql('reports_table')
                    );
            }
        );
    }

    /**
     * Priority shown on the list: a stack with any urgent report is Urgent. Needs `open_report_group`.
     */
    public static function listUrgencySql(): string
    {
        return "CASE WHEN COALESCE(open_report_group.has_urgent, 0) = 1 THEN 'Urgent' ELSE reports_table.report_urgency_level END";
    }

    /**
     * Urgent rows maintenance can act on now: Pending and not claimed by the Purchaser
     * (anyone in maintenance may start them), or Processing by this personnel and
     * submitted before today / flagged overdue (only the assignee can update those).
     */
    public static function scopeUrgentAction($query, ?int $personnelId)
    {
        return $query
            ->whereRaw(self::listUrgencySql()." = 'Urgent'")
            ->where('reports_table.report_is_archived', false)
            ->whereNull('reports_table.report_assigned_purchaser_id')
            ->where(function ($action) use ($personnelId) {
                $action
                    ->where('reports_table.report_current_status', 'Pending')
                    ->orWhere(function ($overdue) use ($personnelId) {
                        $overdue
                            ->where('reports_table.report_current_status', 'Processing')
                            ->where('reports_table.report_assigned_personnel_id', $personnelId ?: 0)
                            ->where(function ($due) {
                                $due
                                    ->where('reports_table.report_is_overdue', true)
                                    ->orWhereDate('reports_table.report_submitted_at', '<', today());
                            });
                    });
            });
    }

    /**
     * Non-urgent Pending rows whose preferred date has arrived, or pending past the grace period.
     */
    public static function scopeNonUrgentPending($query)
    {
        return ReportGrouping::applyNonUrgentReminderWindow(
            $query
                ->whereRaw(self::listUrgencySql()." = 'Non-Urgent'")
                ->where('reports_table.report_is_archived', false)
                ->where('reports_table.report_current_status', 'Pending')
        );
    }

    public static function scopeOverdueSchedules($query)
    {
        return $query->where(function ($overdue) {
            $overdue
                ->where('maintenance_schedules_table.maintenance_schedule_status', 'Overdue')
                ->orWhere(function ($activePastDue) {
                    $activePastDue
                        ->where('maintenance_schedules_table.maintenance_schedule_status', 'Active')
                        ->whereDate('maintenance_schedules_table.maintenance_schedule_next_date', '<', today());
                });
        });
    }

    /**
     * Includes Borrowed records past their return date; the borrowing page flips those to Overdue.
     */
    public static function scopeOverdueBorrowings($query)
    {
        return $query->where(function ($overdue) {
            $overdue
                ->where('borrowing_records_table.borrowing_status', 'Overdue')
                ->orWhere(function ($pastDue) {
                    $pastDue
                        ->where('borrowing_records_table.borrowing_status', 'Borrowed')
                        ->whereDate('borrowing_records_table.borrowing_expected_return_date', '<', today());
                });
        });
    }

    /**
     * Banner data for a list opened from a reminder item.
     *
     * @return array{label: string, description: string, scope: ?string}|null
     */
    public static function focusDetails(string $page, ?string $focus): ?array
    {
        return match ([$page, $focus]) {
            ['reports.urgent', self::FOCUS_URGENT_ACTION] => [
                'label' => 'Urgent reports needing action',
                'description' => 'Pending urgent reports anyone in maintenance can start (not claimed by the Purchaser), plus urgent reports you are processing that were submitted before today.',
                'scope' => null,
            ],
            ['reports.incoming', self::FOCUS_NON_URGENT_PENDING] => [
                'label' => 'Non-urgent reports needing action',
                'description' => 'Pending non-urgent reports whose preferred date has arrived, or pending '.ReportGrouping::nonUrgentReminderWindowLabel().' or more without one.',
                'scope' => 'shared',
            ],
            ['schedules', self::FOCUS_OVERDUE] => [
                'label' => 'Overdue maintenance schedules',
                'description' => 'Overdue schedules, or active schedules whose next date has passed.',
                'scope' => 'shared',
            ],
            ['borrowing', self::FOCUS_OVERDUE] => [
                'label' => 'Overdue borrows',
                'description' => 'Equipment past its expected return date and not yet returned.',
                'scope' => 'shared',
            ],
            default => null,
        };
    }
}
