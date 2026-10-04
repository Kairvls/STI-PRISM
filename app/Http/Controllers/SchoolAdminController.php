<?php

namespace App\Http\Controllers;

use App\Support\AdminAttentionSummary;
use App\Support\ReviewerAssignment;
use App\Support\WorkflowNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * School Administrator portal: RIS approval/signing plus read-mostly campus monitors.
 */
class SchoolAdminController extends Controller
{
    public function dashboard(): View
    {
        $attention = ['pendingRis' => 0, 'awaitingCosign' => 0, 'amendRis' => 0, 'attentionTotal' => 0];
        try {
            $attention = AdminAttentionSummary::counts();
        } catch (\Throwable $e) {
            // Keep zeros if schema/query fails.
        }

        $stats = [
            'pending_ris' => (int) $attention['pendingRis'],
            'awaiting_cosign' => (int) $attention['awaitingCosign'],
            'amend_ris' => (int) $attention['amendRis'],
            'open_ris' => 0,
            'pending_ris_amount' => 0.0,
            'equipment_total' => 0,
            'needs_maintenance' => 0,
            'for_replacement' => 0,
            'open_reports' => 0,
            'urgent_reports' => 0,
            'overdue_schedules' => 0,
            'signed_30d' => 0,
        ];

        $hasRis = Schema::hasTable('requisition_issue_slip_table');
        $itemsSum = DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum');

        try {
            if ($hasRis) {
                $stats['open_ris'] = DB::table('requisition_issue_slip_table')
                    ->whereNotIn('ris_status', ['Completed', 'Rejected', 'Cancelled', 'Archived'])
                    ->count();

                $pendingAmount = DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id');
                $this->applyReviewerQueue($pendingAmount);
                $stats['pending_ris_amount'] = (float) AdminAttentionSummary::scopePendingRis($pendingAmount)
                    ->sum('ris_items_sum.ris_calculated_total');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('equipment_table')) {
                $stats['equipment_total'] = DB::table('equipment_table')->count();
                $stats['needs_maintenance'] = DB::table('equipment_table')
                    ->where(function ($q) {
                        $q->where('equipment_condition_status', 'Under Maintenance')
                            ->orWhere('equipment_inventory_status', 'Under Maintenance');
                    })
                    ->count();
                $stats['for_replacement'] = DB::table('equipment_table')
                    ->where('equipment_inventory_status', 'For Replacement')
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('reports_table')) {
                $stats['open_reports'] = $this->openReportsQuery(['Pending', 'Processing'])->count();
                $stats['urgent_reports'] = $this->openReportsQuery(['Pending', 'Processing', 'For Replacement'])
                    ->where('reports_table.report_urgency_level', 'Urgent')
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('maintenance_schedules_table')) {
                $stats['overdue_schedules'] = $this->overdueSchedulesQuery()->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('approval_logs_table')) {
                $stats['signed_30d'] = DB::table('approval_logs_table')
                    ->where('approval_log_reference_type', 'RIS')
                    ->where('approval_log_approved_by', Auth::id())
                    ->where('approval_log_approved_at', '>=', now()->subDays(30))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $pendingRisList = collect();
        try {
            if ($hasRis) {
                $query = DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->select(
                        'requisition_issue_slip_table.ris_id',
                        'requisition_issue_slip_table.ris_form_number',
                        'requisition_issue_slip_table.ris_status',
                        'requisition_issue_slip_table.ris_purpose_description',
                        'requisition_issue_slip_table.ris_requested_by_date',
                        'ris_items_sum.ris_calculated_total'
                    );
                $this->applyReviewerQueue($query);
                $pendingRisList = AdminAttentionSummary::scopePendingRis($query, 'requisition_issue_slip_table.')
                    ->orderBy('requisition_issue_slip_table.ris_requested_by_date')
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable $e) {
            $pendingRisList = collect();
        }

        $awaitingSignList = collect();
        try {
            if ($hasRis) {
                $awaitingSignList = AdminAttentionSummary::scopeAwaitingCosign(
                    DB::table('requisition_issue_slip_table')
                        ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                        ->select(
                            'requisition_issue_slip_table.ris_id',
                            'requisition_issue_slip_table.ris_form_number',
                            'requisition_issue_slip_table.ris_status',
                            'requisition_issue_slip_table.ris_purpose_description',
                            'requisition_issue_slip_table.ris_requested_by_date',
                            'ris_items_sum.ris_calculated_total'
                        ),
                    'requisition_issue_slip_table.'
                )
                    ->orderByDesc('requisition_issue_slip_table.ris_id')
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable $e) {
            $awaitingSignList = collect();
        }

        $urgentReports = collect();
        try {
            if (Schema::hasTable('reports_table')) {
                $urgentReports = $this->openReportsQuery(['Pending', 'Processing', 'For Replacement'])
                    ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'reports_table.report_equipment_id')
                    ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'reports_table.report_room_id')
                    ->where('reports_table.report_urgency_level', 'Urgent')
                    ->select(
                        'reports_table.report_id',
                        'reports_table.report_current_status',
                        'reports_table.report_suggested_issue',
                        'reports_table.report_unlisted_equipment_name',
                        'reports_table.report_submitted_at',
                        'equipment_table.equipment_name',
                        'rooms_table.room_name'
                    )
                    ->orderByDesc('reports_table.report_submitted_at')
                    ->limit(4)
                    ->get();
            }
        } catch (\Throwable $e) {
            $urgentReports = collect();
        }

        $overdueSchedules = collect();
        try {
            if (Schema::hasTable('maintenance_schedules_table')) {
                $overdueSchedules = $this->overdueSchedulesQuery()
                    ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'maintenance_schedules_table.maintenance_schedule_equipment_id')
                    ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
                    ->whereNotNull('maintenance_schedules_table.maintenance_schedule_next_date')
                    ->select(
                        'maintenance_schedules_table.maintenance_schedule_id',
                        'maintenance_schedules_table.maintenance_schedule_title',
                        'maintenance_schedules_table.maintenance_schedule_next_date',
                        'equipment_table.equipment_name',
                        'rooms_table.room_name'
                    )
                    ->orderBy('maintenance_schedules_table.maintenance_schedule_next_date')
                    ->limit(4)
                    ->get();
            }
        } catch (\Throwable $e) {
            $overdueSchedules = collect();
        }

        return view('school-admin.dashboard', compact(
            'stats',
            'pendingRisList',
            'awaitingSignList',
            'urgentReports',
            'overdueSchedules'
        ));
    }

    public function notifications(): View
    {
        $items = collect();
        try {
            $items = WorkflowNotifier::scopeVisibleTo(DB::table('notifications_table'), Auth::id(), WorkflowNotifier::ROLE_SCHOOL_ADMIN)
                ->orderByDesc('notification_created_at')
                ->limit(80)
                ->get();
        } catch (\Throwable $e) {
        }

        return view('admin.notifications.index', compact('items'));
    }

    private function applyReviewerQueue($query): void
    {
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'requisition_issue_slip_table.ris_assigned_reviewer_id');
        }
    }

    private function openReportsQuery(array $statuses)
    {
        return DB::table('reports_table')
            ->whereIn('reports_table.report_current_status', $statuses)
            ->where(function ($q) {
                if (Schema::hasColumn('reports_table', 'report_is_archived')) {
                    $q->where('reports_table.report_is_archived', 0)
                        ->orWhereNull('reports_table.report_is_archived');
                }
            });
    }

    private function overdueSchedulesQuery()
    {
        return DB::table('maintenance_schedules_table')
            ->where(function ($q) {
                $q->where('maintenance_schedules_table.maintenance_schedule_status', 'Overdue')
                    ->orWhere(function ($q2) {
                        $q2->where('maintenance_schedules_table.maintenance_schedule_status', 'Active')
                            ->whereDate('maintenance_schedules_table.maintenance_schedule_next_date', '<', now()->toDateString());
                    });
            });
    }
}
