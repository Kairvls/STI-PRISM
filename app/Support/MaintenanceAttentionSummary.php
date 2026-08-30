<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MaintenanceAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * Counts used by the maintenance daily reminder / attention UI.
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
        if (self::$cached !== null) {
            return self::$cached;
        }

        $urgentReportsNeedingAction = (int) DB::table('reports_table')
            ->where('report_urgency_level', 'Urgent')
            ->where('report_is_archived', false)
            ->where(function ($query) {
                $query
                    ->where('report_current_status', 'Pending')
                    ->orWhere(function ($overdue) {
                        $overdue
                            ->whereIn(
                                'report_current_status',
                                ['Pending', 'Processing']
                            )
                            ->where(function ($due) {
                                $due
                                    ->where('report_is_overdue', true)
                                    ->orWhereDate(
                                        'report_submitted_at',
                                        '<',
                                        today()
                                    );
                            });
                    });
            })
            ->count();

        $nonUrgentReportsNeedingAction = (int) ReportGrouping::applyNonUrgentReminderWindow(
            DB::table('reports_table')
                ->where('report_urgency_level', 'Non-Urgent')
                ->where('report_is_archived', false)
                ->where('report_current_status', 'Pending')
        )->count();

        $overdueMaintenance = (int) DB::table('maintenance_schedules_table')
            ->where(function ($query) {
                $query
                    ->where('maintenance_schedule_status', 'Overdue')
                    ->orWhere(function ($activePastDue) {
                        $activePastDue
                            ->where('maintenance_schedule_status', 'Active')
                            ->whereDate(
                                'maintenance_schedule_next_date',
                                '<',
                                today()
                            );
                    });
            })
            ->count();

        $overdueBorrowings = (int) DB::table('borrowing_records_table')
            ->where('borrowing_status', 'Overdue')
            ->count();

        self::$cached = [
            'urgentReportsNeedingAction' => $urgentReportsNeedingAction,
            'nonUrgentReportsNeedingAction' => $nonUrgentReportsNeedingAction,
            'overdueMaintenance' => $overdueMaintenance,
            'overdueBorrowings' => $overdueBorrowings,
            'attentionTotal' => $urgentReportsNeedingAction
                + $nonUrgentReportsNeedingAction
                + $overdueMaintenance
                + $overdueBorrowings,
        ];

        return self::$cached;
    }
}
