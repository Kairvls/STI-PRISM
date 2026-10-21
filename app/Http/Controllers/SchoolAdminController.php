<?php

namespace App\Http\Controllers;

use App\Support\AccountingAttentionSummary;
use App\Support\AdminAttentionSummary;
use App\Support\AdminPipeline;
use App\Support\ReviewerAssignment;
use App\Support\RisWorkflow;
use App\Support\SemesterInspections;
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
            'awaiting_issued_by' => 0,
            'ready_to_forward' => 0,
            'ready_to_forward_amount' => 0.0,
            'with_president' => 0,
            'with_president_amount' => 0.0,
            'amend_ris' => (int) $attention['amendRis'],
            'amend_ris_amount' => 0.0,
            'open_ris' => 0,
            'pending_ris_amount' => 0.0,
            'equipment_total' => 0,
            'needs_maintenance' => 0,
            'for_replacement' => 0,
            'active_inspections' => 0,
            'overdue_inspections' => 0,
            'overdue_borrows' => 0,
            'overdue_schedules' => 0,
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

                $readyToForward = DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->where('requisition_issue_slip_table.ris_status', RisWorkflow::ACCEPTED);
                $stats['ready_to_forward'] = (clone $readyToForward)->count();
                $stats['ready_to_forward_amount'] = (float) $readyToForward->sum('ris_items_sum.ris_calculated_total');

                $amendAmount = DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id');
                $this->applyReviewerQueue($amendAmount);
                $stats['amend_ris_amount'] = (float) AdminAttentionSummary::scopeAmendRis($amendAmount, 'requisition_issue_slip_table.')
                    ->sum('ris_items_sum.ris_calculated_total');

                $withPresident = DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->where('requisition_issue_slip_table.ris_status', RisWorkflow::FORWARDED);
                $stats['with_president'] = (clone $withPresident)->count();
                $stats['with_president_amount'] = (float) $withPresident->sum('ris_items_sum.ris_calculated_total');
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
            if (SemesterInspections::tablesReady()) {
                $openCampaigns = DB::table('semester_inspection_campaigns_table')
                    ->whereIn('campaign_status', ['Active', 'In Progress']);
                $stats['active_inspections'] = (clone $openCampaigns)->count();
                $stats['overdue_inspections'] = (clone $openCampaigns)
                    ->whereDate('campaign_due_date', '<', today())
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('borrowing_records_table')) {
                $stats['overdue_borrows'] = $this->overdueBorrowsQuery()->count();
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
                $issuedByQuery = AdminAttentionSummary::scopeAwaitingIssuedBy(
                    DB::table('requisition_issue_slip_table'),
                    'requisition_issue_slip_table.'
                );
                $stats['awaiting_issued_by'] = (clone $issuedByQuery)->count();

                $awaitingSignList = $issuedByQuery
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->select(
                        'requisition_issue_slip_table.ris_id',
                        'requisition_issue_slip_table.ris_form_number',
                        'requisition_issue_slip_table.ris_status',
                        'requisition_issue_slip_table.ris_purpose_description',
                        'requisition_issue_slip_table.ris_approved_by_date',
                        'ris_items_sum.ris_calculated_total'
                    )
                    ->orderBy('requisition_issue_slip_table.ris_approved_by_date')
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable $e) {
            $awaitingSignList = collect();
        }

        $latestProcurements = collect();
        try {
            if ($hasRis) {
                $latestQuery = DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsSum, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->whereNotNull('requisition_issue_slip_table.ris_requested_by_date')
                    ->whereNotIn('requisition_issue_slip_table.ris_status', ['Draft', 'Completed', 'Rejected', 'Cancelled', 'Archived'])
                    ->select(
                        'requisition_issue_slip_table.ris_id',
                        'requisition_issue_slip_table.ris_form_number',
                        'requisition_issue_slip_table.ris_status',
                        'requisition_issue_slip_table.ris_purpose_description',
                        'requisition_issue_slip_table.ris_requested_by_date',
                        'ris_items_sum.ris_calculated_total'
                    )
                    ->orderByRaw('COALESCE(requisition_issue_slip_table.ris_updated_at, requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_created_at) DESC')
                    ->orderByDesc('requisition_issue_slip_table.ris_id')
                    ->limit(3);

                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
                    $latestQuery->addSelect('requisition_issue_slip_table.ris_request_no');
                }

                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
                    $latestQuery->where(function ($q) {
                        $q->whereNull('requisition_issue_slip_table.ris_is_archived')
                            ->orWhere('requisition_issue_slip_table.ris_is_archived', 0);
                    });
                }

                $latestProcurements = $latestQuery->get()->each(function ($row) {
                    $row->pipeline = AdminPipeline::build((int) $row->ris_id);
                });
            }
        } catch (\Throwable $e) {
            $latestProcurements = collect();
        }

        $overdueBorrows = collect();
        try {
            if (Schema::hasTable('borrowing_records_table')) {
                $overdueBorrows = $this->overdueBorrowsQuery()
                    ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'borrowing_records_table.borrowing_equipment_id')
                    ->select(
                        'borrowing_records_table.borrowing_record_id',
                        'borrowing_records_table.borrowing_borrower_name',
                        'borrowing_records_table.borrowing_expected_return_date',
                        'equipment_table.equipment_name',
                        'equipment_table.equipment_asset_tag'
                    )
                    ->orderBy('borrowing_records_table.borrowing_expected_return_date')
                    ->limit(4)
                    ->get();
            }
        } catch (\Throwable $e) {
            $overdueBorrows = collect();
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
                        'equipment_table.equipment_asset_tag',
                        'rooms_table.room_name'
                    )
                    ->orderBy('maintenance_schedules_table.maintenance_schedule_next_date')
                    ->limit(4)
                    ->get();
            }
        } catch (\Throwable $e) {
            $overdueSchedules = collect();
        }

        return view('school-admin.dashboard', [
            'stats' => $stats,
            'pendingRisList' => $pendingRisList,
            'awaitingSignList' => $awaitingSignList,
            'latestProcurements' => $latestProcurements,
            'overdueBorrows' => $overdueBorrows,
            'overdueSchedules' => $overdueSchedules,
            'approvalQueues' => $this->approvalQueues(),
        ]);
    }

    /**
     * ATP, RFC, and DV records waiting for the School Administrator.
     *
     * @return list<array<string, mixed>>
     */
    private function approvalQueues(): array
    {
        return [
            $this->atpApprovalQueue(),
            $this->rfcApprovalQueue(),
            $this->dvApprovalQueue(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function atpApprovalQueue(): array
    {
        $empty = [
            'title' => 'ATP — Pending approval',
            'url' => route('school-admin.atp.index', ['status' => 'incoming']),
            'total' => 0,
            'empty' => 'No Authority to Purchase waiting for approval.',
            'rows' => collect(),
        ];

        if (! Schema::hasTable('authority_to_purchase_table')) {
            return $empty;
        }

        try {
            $query = AccountingAttentionSummary::queue('authority_to_purchase_table')
                ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id');
            AccountingAttentionSummary::scopeAtpIncoming($query);

            if (Schema::hasTable('authority_to_purchase_items_table')) {
                $totals = DB::table('authority_to_purchase_items_table')
                    ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as atp_total'))
                    ->groupBy('authority_purchase_id');
                $query->leftJoinSub($totals, 'atp_totals', function ($join) {
                    $join->on('atp_totals.authority_purchase_id', '=', 'authority_to_purchase_table.authority_purchase_id');
                });
            }

            $empty['total'] = (clone $query)->count();
            $select = [
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_submitted_at',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name',
            ];
            if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no')) {
                $select[] = 'authority_to_purchase_table.authority_purchase_request_no';
            }
            $select[] = Schema::hasTable('authority_to_purchase_items_table')
                ? DB::raw('COALESCE(atp_totals.atp_total, 0) as amount')
                : DB::raw('0 as amount');

            $empty['rows'] = (clone $query)
                ->select($select)
                ->orderBy('authority_to_purchase_table.authority_purchase_submitted_at')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    $supplier = trim((string) ($row->company_name ?: $row->shop_name ?: ''));

                    return (object) [
                        'label' => $row->authority_purchase_form_number ?: 'ATP #'.$row->authority_purchase_id,
                        'request_no' => $row->authority_purchase_request_no ?? null,
                        'detail' => $supplier !== '' ? $supplier : 'Authority to Purchase',
                        'amount' => (float) ($row->amount ?? 0),
                        'date' => $row->authority_purchase_submitted_at,
                        'status' => 'Pending approval',
                        'url' => route('school-admin.atp.show', $row->authority_purchase_id),
                    ];
                });
        } catch (\Throwable $e) {
            return $empty;
        }

        return $empty;
    }

    /**
     * @return array<string, mixed>
     */
    private function rfcApprovalQueue(): array
    {
        $empty = [
            'title' => 'RFC — Pending approval',
            'url' => route('school-admin.rfc.index', ['status' => 'incoming']),
            'total' => 0,
            'empty' => 'No Request for Check waiting for approval.',
            'rows' => collect(),
        ];

        if (! Schema::hasTable('request_check_table')) {
            return $empty;
        }

        try {
            $query = AccountingAttentionSummary::queue('request_check_table');
            AccountingAttentionSummary::scopeRfcIncoming($query);
            $empty['total'] = (clone $query)->count();

            $select = [
                'request_check_id',
                'request_check_form_number',
                'request_check_payee',
                'request_check_status',
                'request_check_amount_figures',
            ];
            if (Schema::hasColumn('request_check_table', 'request_check_request_no')) {
                $select[] = 'request_check_request_no';
            }
            if (Schema::hasColumn('request_check_table', 'request_check_submitted_at')) {
                $select[] = 'request_check_submitted_at';
            }

            $empty['rows'] = (clone $query)
                ->select($select)
                ->orderByDesc('request_check_id')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'label' => $row->request_check_form_number ?: 'RFC #'.$row->request_check_id,
                        'request_no' => $row->request_check_request_no ?? null,
                        'detail' => trim((string) ($row->request_check_payee ?? '')) ?: 'Request for Check',
                        'amount' => (float) ($row->request_check_amount_figures ?? 0),
                        'date' => $row->request_check_submitted_at ?? null,
                        'status' => (string) ($row->request_check_status ?: 'Pending approval'),
                        'url' => route('school-admin.rfc.show', $row->request_check_id),
                    ];
                });
        } catch (\Throwable $e) {
            return $empty;
        }

        return $empty;
    }

    /**
     * @return array<string, mixed>
     */
    private function dvApprovalQueue(): array
    {
        $empty = [
            'title' => 'DV — Pending approval',
            'url' => route('school-admin.dv.index', ['status' => 'incoming']),
            'total' => 0,
            'empty' => 'No Disbursement Voucher waiting for approval.',
            'rows' => collect(),
        ];

        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return $empty;
        }

        try {
            $query = DB::table('disbursement_vouchers_table')
                ->where('dv_status', 'Pending')
                ->where(function ($q) {
                    $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                });
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_reviewer_id')) {
                ReviewerAssignment::applyQueueFilter($query, 'dv_assigned_reviewer_id');
            }

            $empty['total'] = (clone $query)->count();
            $empty['rows'] = (clone $query)
                ->orderBy('dv_submitted_at')
                ->orderBy('dv_id')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'label' => $row->dv_form_number ?: 'DV #'.$row->dv_id,
                        'request_no' => $row->dv_request_no ?? null,
                        'detail' => trim((string) ($row->dv_payee ?? '')) ?: 'Disbursement Voucher',
                        'amount' => (float) ($row->dv_amount ?? 0),
                        'date' => $row->dv_submitted_at ?? null,
                        'status' => 'Pending approval',
                        'url' => route('school-admin.dv.show', $row->dv_id),
                    ];
                });
        } catch (\Throwable $e) {
            return $empty;
        }

        return $empty;
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

    private function overdueBorrowsQuery()
    {
        return DB::table('borrowing_records_table')
            ->where(function ($q) {
                $q->where('borrowing_records_table.borrowing_status', 'Overdue')
                    ->orWhere(function ($q2) {
                        $q2->where('borrowing_records_table.borrowing_status', 'Borrowed')
                            ->whereNotNull('borrowing_records_table.borrowing_expected_return_date')
                            ->whereDate('borrowing_records_table.borrowing_expected_return_date', '<', today());
                    });
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
