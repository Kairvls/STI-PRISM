<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceReportService;
use App\Support\AdminAttentionSummary;
use App\Support\DocumentLineage;
use App\Support\ProcurementPaymentPath;
use App\Support\RisWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * True-admin monitoring + override actions (equipment, schedules, reports, procurement).
 */
class AdminOperationsController extends Controller
{
    private const DEFAULT_USEFUL_LIFE_YEARS = 5;

    private const DOC_TYPES = ['ris', 'atp', 'rfc', 'rr', 'liq'];

    public function __construct(
        private readonly MaintenanceReportService $reportService
    ) {}

    public function overview(): View
    {
        $stats = [
            'equipment_total' => 0,
            'needs_maintenance' => 0,
            'for_replacement' => 0,
            'open_reports' => 0,
            'overdue_schedules' => 0,
            'lifecycle_alerts' => 0,
            'open_ris' => 0,
            'overdue_borrows' => 0,
            'transfers_30d' => 0,
            'disposals_total' => 0,
            'urgent_reports' => 0,
            'awaiting_admin_ris' => 0,
            'awaiting_cosign' => 0,
        ];

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
                $stats['open_reports'] = DB::table('reports_table')
                    ->whereIn('report_current_status', ['Pending', 'Processing'])
                    ->where(function ($q) {
                        if (Schema::hasColumn('reports_table', 'report_is_archived')) {
                            $q->where('report_is_archived', 0)->orWhereNull('report_is_archived');
                        }
                    })
                    ->count();
                $stats['urgent_reports'] = DB::table('reports_table')
                    ->where('report_urgency_level', 'Urgent')
                    ->whereIn('report_current_status', ['Pending', 'Processing', 'For Replacement'])
                    ->where(function ($q) {
                        if (Schema::hasColumn('reports_table', 'report_is_archived')) {
                            $q->where('report_is_archived', 0)->orWhereNull('report_is_archived');
                        }
                    })
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('maintenance_schedules_table')) {
                $stats['overdue_schedules'] = DB::table('maintenance_schedules_table')
                    ->where(function ($q) {
                        $q->where('maintenance_schedule_status', 'Overdue')
                            ->orWhere(function ($q2) {
                                $q2->where('maintenance_schedule_status', 'Active')
                                    ->whereDate('maintenance_schedule_next_date', '<', now()->toDateString());
                            });
                    })
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $stats['lifecycle_alerts'] = $this->lifecycleAlertsQuery()->count();
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('requisition_issue_slip_table')) {
                $stats['open_ris'] = DB::table('requisition_issue_slip_table')
                    ->whereNotIn('ris_status', ['Completed', 'Rejected', 'Cancelled', 'Archived'])
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('borrowing_records_table')) {
                DB::table('borrowing_records_table')
                    ->where('borrowing_status', 'Borrowed')
                    ->whereNotNull('borrowing_expected_return_date')
                    ->whereDate('borrowing_expected_return_date', '<', today())
                    ->update(['borrowing_status' => 'Overdue']);

                $stats['overdue_borrows'] = DB::table('borrowing_records_table')
                    ->where('borrowing_status', 'Overdue')
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('equipment_transfer_history_table')) {
                $stats['transfers_30d'] = DB::table('equipment_transfer_history_table')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('disposal_records_table')) {
                $stats['disposals_total'] = DB::table('disposal_records_table')->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $attention = AdminAttentionSummary::counts();
        $stats['awaiting_admin_ris'] = $attention['pendingRis'] ?? 0;
        $stats['awaiting_cosign'] = $attention['awaitingCosign'] ?? 0;
        $stats['open_ris_amount'] = 0.0;
        $stats['pending_admin_ris_amount'] = 0.0;

        $itemsJoin = DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum');

        try {
            if (Schema::hasTable('requisition_issue_slip_table')) {
                $stats['open_ris_amount'] = (float) DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->whereNotIn('ris_status', ['Completed', 'Rejected', 'Cancelled', 'Archived'])
                    ->sum('ris_items_sum.ris_calculated_total');

                $stats['pending_admin_ris_amount'] = (float) DB::table('requisition_issue_slip_table')
                    ->leftJoin($itemsJoin, 'requisition_issue_slip_table.ris_id', '=', 'ris_items_sum.ris_id')
                    ->whereNotNull('ris_requested_by_date')
                    ->whereIn('ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending'])
                    ->sum('ris_items_sum.ris_calculated_total');
            }
        } catch (\Throwable $e) {
            $stats['open_ris_amount'] = 0.0;
            $stats['pending_admin_ris_amount'] = 0.0;
        }

        $exceptions = [
            [
                'label' => 'Pending Administrator Review — RIS accept',
                'count' => $stats['awaiting_admin_ris'],
                'url' => url('/admin/procurement-review?filter=pending'),
                'tone' => 'sky',
            ],
            [
                'label' => 'Pending Administrator Review — cosign / decision',
                'count' => $stats['awaiting_cosign'],
                'url' => url('/admin/digital-signatures/sign-ris'),
                'tone' => 'indigo',
            ],
            [
                'label' => 'Overdue maintenance schedules',
                'count' => $stats['overdue_schedules'],
                'url' => route('admin.operations.schedules', ['filter' => 'overdue']),
                'tone' => 'amber',
            ],
            [
                'label' => 'Overdue equipment borrows',
                'count' => $stats['overdue_borrows'],
                'url' => route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']),
                'tone' => 'rose',
            ],
            [
                'label' => 'Urgent open reports',
                'count' => $stats['urgent_reports'],
                'url' => route('admin.operations.reports', ['filter' => 'urgent']),
                'tone' => 'violet',
            ],
            [
                'label' => 'Lifecycle replacement horizon',
                'count' => $stats['lifecycle_alerts'],
                'url' => route('admin.operations.equipment', ['filter' => 'lifecycle']),
                'tone' => 'amber',
            ],
        ];

        $overdueSchedulesPreview = collect();
        try {
            if (Schema::hasTable('maintenance_schedules_table')) {
                $overdueSchedulesPreview = DB::table('maintenance_schedules_table')
                    ->leftJoin(
                        'equipment_table',
                        'equipment_table.equipment_id',
                        '=',
                        'maintenance_schedules_table.maintenance_schedule_equipment_id'
                    )
                    ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
                    ->where(function ($q) {
                        $q->where('maintenance_schedule_status', 'Overdue')
                            ->orWhere(function ($q2) {
                                $q2->where('maintenance_schedule_status', 'Active')
                                    ->whereDate('maintenance_schedule_next_date', '<', now()->toDateString());
                            });
                    })
                    ->whereNotNull('maintenance_schedule_next_date')
                    ->select(
                        'maintenance_schedules_table.maintenance_schedule_id',
                        'maintenance_schedules_table.maintenance_schedule_title',
                        'maintenance_schedules_table.maintenance_schedule_next_date',
                        'equipment_table.equipment_name',
                        'rooms_table.room_name'
                    )
                    ->orderBy('maintenance_schedule_next_date')
                    ->limit(3)
                    ->get();
            }
        } catch (\Throwable $e) {
            $overdueSchedulesPreview = collect();
        }

        $overdueBorrowsPreview = collect();
        try {
            if (Schema::hasTable('borrowing_records_table')) {
                $overdueBorrowsPreview = DB::table('borrowing_records_table')
                    ->leftJoin(
                        'equipment_table',
                        'equipment_table.equipment_id',
                        '=',
                        'borrowing_records_table.borrowing_equipment_id'
                    )
                    ->where('borrowing_records_table.borrowing_status', 'Overdue')
                    ->select(
                        'borrowing_records_table.borrowing_record_id',
                        'borrowing_records_table.borrowing_borrower_name',
                        'borrowing_records_table.borrowing_expected_return_date',
                        'equipment_table.equipment_name'
                    )
                    ->orderBy('borrowing_records_table.borrowing_expected_return_date')
                    ->limit(3)
                    ->get();
            }
        } catch (\Throwable $e) {
            $overdueBorrowsPreview = collect();
        }

        $urgentReportsPreview = collect();
        try {
            if (Schema::hasTable('reports_table')) {
                $urgentReportsPreview = DB::table('reports_table')
                    ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'reports_table.report_equipment_id')
                    ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'reports_table.report_room_id')
                    ->where('reports_table.report_urgency_level', 'Urgent')
                    ->whereIn('reports_table.report_current_status', ['Pending', 'Processing', 'For Replacement'])
                    ->where(function ($q) {
                        if (Schema::hasColumn('reports_table', 'report_is_archived')) {
                            $q->where('reports_table.report_is_archived', 0)
                                ->orWhereNull('reports_table.report_is_archived');
                        }
                    })
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
                    ->limit(3)
                    ->get();
            }
        } catch (\Throwable $e) {
            $urgentReportsPreview = collect();
        }

        $lifecyclePreview = collect();
        try {
            $lifecyclePreview = $this->lifecycleAlertsQuery()
                ->orderBy('years_remaining')
                ->limit(3)
                ->get();
        } catch (\Throwable $e) {
            $lifecyclePreview = collect();
        }

        $latestProcurement = collect();
        try {
            if (Schema::hasTable('requisition_issue_slip_table')) {
                $query = DB::table('requisition_issue_slip_table as ris')
                    ->leftJoin(
                        DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                        'ris.ris_id',
                        '=',
                        'ris_items_sum.ris_id'
                    )
                    ->select(
                        'ris.ris_id',
                        'ris.ris_form_number',
                        'ris.ris_status',
                        'ris.ris_purpose_description',
                        'ris.ris_created_at',
                        'ris.ris_requested_by_date',
                        'ris_items_sum.ris_calculated_total'
                    );

                if (Schema::hasTable('procurement_requests_table')) {
                    $query->leftJoin(
                        'procurement_requests_table',
                        'ris.ris_procurement_request_id',
                        '=',
                        'procurement_requests_table.procurement_request_id'
                    )->leftJoin(
                        'users_table as creators',
                        'creators.user_id',
                        '=',
                        'procurement_requests_table.procurement_request_created_by'
                    )->addSelect('creators.user_full_name as created_by_name');
                }

                $latestProcurement = $query
                    ->orderByDesc('ris.ris_id')
                    ->limit(3)
                    ->get()
                    ->map(function ($row) {
                        $row->pipeline = $this->buildAdminPipeline((int) $row->ris_id);

                        return $row;
                    });
            }
        } catch (\Throwable $e) {
            $latestProcurement = collect();
        }

        return view('admin.operations.overview', [
            'stats' => $stats,
            'exceptions' => $exceptions,
            'overdueSchedulesPreview' => $overdueSchedulesPreview,
            'overdueBorrowsPreview' => $overdueBorrowsPreview,
            'urgentReportsPreview' => $urgentReportsPreview,
            'lifecyclePreview' => $lifecyclePreview,
            'latestProcurement' => $latestProcurement,
        ]);
    }

    public function equipment(Request $request): View
    {
        $filter = (string) $request->query('filter', 'all');
        $q = trim((string) $request->query('q', ''));
        $rows = $this->emptyPager($request);
        $lifecycleAlerts = collect();

        if (Schema::hasTable('equipment_table')) {
            $query = DB::table('equipment_table')
                ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
                ->leftJoin('equipment_categories_table', 'equipment_categories_table.equipment_category_id', '=', 'equipment_table.equipment_category_id')
                ->select(
                    'equipment_table.*',
                    'rooms_table.room_name',
                    'equipment_categories_table.equipment_category_name'
                );

            if ($filter === 'maintenance') {
                $query->where(function ($builder) {
                    $builder->where('equipment_condition_status', 'Under Maintenance')
                        ->orWhere('equipment_inventory_status', 'Under Maintenance');
                });
            } elseif ($filter === 'replacement') {
                $query->where('equipment_inventory_status', 'For Replacement');
            } elseif ($filter === 'damaged') {
                $query->where('equipment_condition_status', 'Damaged');
            } elseif ($filter === 'lifecycle') {
                $ids = $this->lifecycleAlertsQuery()->pluck('equipment_id');
                $query->whereIn('equipment_table.equipment_id', $ids->isEmpty() ? [0] : $ids->all());
            }

            if ($q !== '') {
                $needle = '%'.$q.'%';
                $query->where(function ($builder) use ($needle) {
                    $builder->where('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('equipment_table.equipment_asset_tag', 'like', $needle)
                        ->orWhere('equipment_table.equipment_serial_number', 'like', $needle)
                        ->orWhere('rooms_table.room_name', 'like', $needle);
                });
            }

            $rows = $query->orderBy('equipment_table.equipment_name')->paginate(15)->withQueryString();

            $lifecycleAlerts = $this->lifecycleAlertsQuery()
                ->orderBy('years_remaining')
                ->limit(12)
                ->get();
        }

        return view('admin.operations.equipment', [
            'rows' => $rows,
            'filter' => $filter,
            'q' => $q,
            'lifecycleAlerts' => $lifecycleAlerts,
            'usefulLifeYears' => self::DEFAULT_USEFUL_LIFE_YEARS,
        ]);
    }

    public function showEquipment(int $id): View|RedirectResponse
    {
        if (! Schema::hasTable('equipment_table')) {
            return redirect()
                ->route('admin.operations.equipment')
                ->with('error', 'Equipment not found.');
        }

        $equipment = DB::table('equipment_table')
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
            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name',
                'rooms_table.room_name'
            )
            ->where('equipment_table.equipment_id', $id)
            ->first();

        if (! $equipment) {
            return redirect()
                ->route('admin.operations.equipment')
                ->with('error', 'Equipment not found.');
        }

        return view('admin.operations.equipment-show', [
            'equipment' => $equipment,
            'usefulLifeYears' => self::DEFAULT_USEFUL_LIFE_YEARS,
        ]);
    }

    public function schedules(Request $request): View
    {
        $filter = (string) $request->query('filter', 'all');
        $q = trim((string) $request->query('q', ''));
        $rows = $this->emptyPager($request);

        if (Schema::hasTable('maintenance_schedules_table')) {
            $query = DB::table('maintenance_schedules_table')
                ->leftJoin(
                    'equipment_table',
                    'equipment_table.equipment_id',
                    '=',
                    'maintenance_schedules_table.maintenance_schedule_equipment_id'
                )
                ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
                ->select(
                    'maintenance_schedules_table.*',
                    'equipment_table.equipment_name',
                    'rooms_table.room_name'
                );

            if ($filter === 'overdue') {
                $query->where(function ($builder) {
                    $builder->where('maintenance_schedule_status', 'Overdue')
                        ->orWhere(function ($q2) {
                            $q2->where('maintenance_schedule_status', 'Active')
                                ->whereDate('maintenance_schedule_next_date', '<', now()->toDateString());
                        });
                });
            } elseif ($filter === 'upcoming') {
                $query->where('maintenance_schedule_status', 'Active')
                    ->whereDate('maintenance_schedule_next_date', '>=', now()->toDateString())
                    ->whereDate('maintenance_schedule_next_date', '<=', now()->addDays(14)->toDateString());
            } elseif ($filter === 'active') {
                $query->where('maintenance_schedule_status', 'Active');
            } elseif ($filter === 'completed') {
                $query->where('maintenance_schedule_status', 'Completed');
            }

            if ($q !== '') {
                $needle = '%'.$q.'%';
                $query->where(function ($builder) use ($needle) {
                    $builder->where('maintenance_schedules_table.maintenance_schedule_title', 'like', $needle)
                        ->orWhere('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('rooms_table.room_name', 'like', $needle);
                });
            }

            $rows = $query
                ->orderByRaw("CASE WHEN maintenance_schedule_status = 'Overdue' THEN 0 WHEN maintenance_schedule_next_date < CURDATE() THEN 1 ELSE 2 END")
                ->orderBy('maintenance_schedule_next_date')
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.operations.schedules', compact('rows', 'filter', 'q'));
    }

    public function reports(Request $request): View
    {
        $filter = (string) $request->query('filter', 'open');
        $q = trim((string) $request->query('q', ''));
        $rows = $this->emptyPager($request);

        if (Schema::hasTable('reports_table')) {
            $query = DB::table('reports_table')
                ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'reports_table.report_equipment_id')
                ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'reports_table.report_room_id')
                ->leftJoin('users_table as assignees', 'assignees.user_id', '=', 'reports_table.report_assigned_personnel_id')
                ->leftJoin('users_table as purchasers', 'purchasers.user_id', '=', 'reports_table.report_assigned_purchaser_id')
                ->leftJoin('reporters_table', 'reporters_table.reporter_employee_id', '=', 'reports_table.report_reporter_employee_id')
                ->select(
                    'reports_table.*',
                    'equipment_table.equipment_name',
                    'rooms_table.room_name',
                    'assignees.user_full_name as assignee_name',
                    'purchasers.user_full_name as purchaser_name',
                    'reporters_table.reporter_full_name as reporter_name'
                );

            if (Schema::hasColumn('reports_table', 'report_is_archived')) {
                $query->where(function ($builder) {
                    $builder->where('reports_table.report_is_archived', 0)
                        ->orWhereNull('reports_table.report_is_archived');
                });
            }

            if ($filter === 'open') {
                $query->whereIn('report_current_status', ['Pending', 'Processing']);
            } elseif ($filter === 'pending') {
                $query->where('report_current_status', 'Pending');
            } elseif ($filter === 'processing') {
                $query->where('report_current_status', 'Processing');
            } elseif ($filter === 'replacement') {
                $query->where('report_current_status', 'For Replacement');
            } elseif ($filter === 'resolved') {
                $query->where('report_current_status', 'Resolved');
            } elseif ($filter === 'rejected') {
                $query->where('report_current_status', 'Rejected');
            } elseif ($filter === 'urgent') {
                $query->where('report_urgency_level', 'Urgent');
            }

            if ($q !== '') {
                $needle = '%'.$q.'%';
                $query->where(function ($builder) use ($needle) {
                    $builder->where('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('reports_table.report_unlisted_equipment_name', 'like', $needle)
                        ->orWhere('reports_table.report_suggested_issue', 'like', $needle)
                        ->orWhere('rooms_table.room_name', 'like', $needle)
                        ->orWhere('assignees.user_full_name', 'like', $needle);
                });
            }

            $rows = $query->orderByDesc('reports_table.report_submitted_at')->paginate(15)->withQueryString();
        }

        return view('admin.operations.reports', compact('rows', 'filter', 'q'));
    }

    public function updateReport(Request $request, int $reportId): RedirectResponse
    {
        $status = (string) $request->input('status', '');
        $remarks = trim((string) $request->input('remarks', ''));
        $adminId = (int) Auth::id();

        $result = $this->reportService->updateStatus(
            $reportId,
            $adminId,
            [
                'status' => $status,
                'remarks' => $remarks,
            ],
            null,
            true
        );

        if (! ($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Unable to update report.');
        }

        $this->logAdminOverride(
            'equipment_report',
            $reportId,
            'Administrator override: set status to '.$status,
            $remarks
        );

        return back()->with('success', ($result['message'] ?? 'Report updated.').' (Administrator override)');
    }

    public function procurement(Request $request): View
    {
        $filter = (string) $request->query('filter', 'all');
        $q = trim((string) $request->query('q', ''));
        $rows = $this->emptyPager($request);
        $stageCounts = [
            'ris' => 0,
            'atp' => 0,
            'rfc' => 0,
            'receiving' => 0,
            'liquidation' => 0,
        ];

        if (Schema::hasTable('requisition_issue_slip_table')) {
            try {
                $query = DB::table('requisition_issue_slip_table as ris')
                    ->leftJoin(
                        'procurement_requests_table',
                        'ris.ris_procurement_request_id',
                        '=',
                        'procurement_requests_table.procurement_request_id'
                    )
                    ->leftJoin(
                        'reports_table',
                        'procurement_requests_table.procurement_request_report_id',
                        '=',
                        'reports_table.report_id'
                    )
                    ->leftJoin(
                        'users_table as creators',
                        'creators.user_id',
                        '=',
                        'procurement_requests_table.procurement_request_created_by'
                    )
                    ->leftJoin(
                        DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
                        'ris.ris_id',
                        '=',
                        'ris_items_sum.ris_id'
                    )
                    ->select(
                        'ris.ris_id',
                        'ris.ris_form_number',
                        'ris.ris_status',
                        'ris.ris_purpose_description',
                        'ris.ris_created_at',
                        'ris.ris_requested_by_date',
                        'ris_items_sum.ris_calculated_total',
                        'creators.user_full_name as created_by_name',
                        'procurement_requests_table.procurement_request_id',
                        'reports_table.report_id',
                        'reports_table.report_suggested_issue'
                    );

                if ($filter === 'awaiting_admin') {
                    $query->whereIn('ris.ris_status', ['Submitted', 'Under Review', 'Resubmitted', 'Pending']);
                } elseif ($filter === 'open') {
                    $query->whereNotIn('ris.ris_status', ['Completed', 'Rejected', 'Cancelled', 'Archived']);
                } elseif ($filter !== 'all' && in_array($filter, ['ris', 'atp', 'rfc', 'rr', 'liq'], true)) {
                    // Stage filter applied after pipeline enrichment
                } elseif ($filter !== 'all' && Schema::hasColumn('requisition_issue_slip_table', 'ris_status')) {
                    $query->where('ris.ris_status', $filter);
                }

                if ($q !== '') {
                    $needle = '%'.$q.'%';
                    $query->where(function ($builder) use ($needle) {
                        $builder->where('ris.ris_form_number', 'like', $needle)
                            ->orWhere('ris.ris_purpose_description', 'like', $needle)
                            ->orWhere('ris.ris_status', 'like', $needle)
                            ->orWhere('creators.user_full_name', 'like', $needle);
                    });
                }

                if (in_array($filter, ['atp', 'rfc', 'rr', 'liq'], true)) {
                    // Load a larger set then filter by current stage (pipeline is per-row)
                    $candidates = $query->orderByDesc('ris.ris_id')->limit(200)->get();
                    $matched = $candidates->filter(function ($row) use ($filter) {
                        $pipeline = $this->buildAdminPipeline((int) $row->ris_id);

                        return ($pipeline['current_stage'] ?? '') === $filter;
                    })->values();

                    $page = max(1, (int) $request->query('page', 1));
                    $perPage = 15;
                    $slice = $matched->slice(($page - 1) * $perPage, $perPage)->values();
                    foreach ($slice as $row) {
                        $row->pipeline = $this->buildAdminPipeline((int) $row->ris_id);
                    }
                    $rows = new LengthAwarePaginator($slice, $matched->count(), $perPage, $page, [
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]);
                } else {
                    $rows = $query->orderByDesc('ris.ris_id')->paginate(15)->withQueryString();
                    $rows->getCollection()->transform(function ($row) {
                        $row->pipeline = $this->buildAdminPipeline((int) $row->ris_id);

                        return $row;
                    });
                }

                $stageCounts['ris'] = DB::table('requisition_issue_slip_table')->count();
            } catch (\Throwable $e) {
                $rows = $this->emptyPager($request);
            }
        }

        foreach ([
            'atp' => 'authority_to_purchase_table',
            'rfc' => 'request_check_table',
            'receiving' => 'receiving_reports_table',
            'liquidation' => 'liquidation_reports_table',
        ] as $key => $table) {
            try {
                if (Schema::hasTable($table)) {
                    $stageCounts[$key] = DB::table($table)->count();
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return view('admin.operations.procurement', [
            'rows' => $rows,
            'filter' => $filter,
            'q' => $q,
            'stageCounts' => $stageCounts,
        ]);
    }

    public function procurementPipeline(int $risId): JsonResponse
    {
        abort_unless(Schema::hasTable('requisition_issue_slip_table'), 404);
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(! $ris, 404);

        return response()->json($this->buildAdminPipeline($risId, true));
    }

    public function viewDocument(string $type, int $id): View
    {
        $type = strtolower($type);
        abort_unless(in_array($type, self::DOC_TYPES, true), 404);

        $payload = $this->loadDocumentViewPayload($type, $id);
        $payload['type'] = $type;

        return view('procurement-records.document-view', $payload);
    }

    public function movements(Request $request): View
    {
        $tab = (string) $request->query('tab', 'transfers');
        if (! in_array($tab, ['transfers', 'borrowing', 'disposal'], true)) {
            $tab = 'transfers';
        }

        $filter = (string) $request->query('filter', 'all');
        $q = trim((string) $request->query('q', ''));
        $rows = $this->emptyPager($request);
        $counts = [
            'transfers' => 0,
            'borrowing_active' => 0,
            'borrowing_overdue' => 0,
            'disposal' => 0,
        ];

        try {
            if (Schema::hasTable('equipment_transfer_history_table')) {
                $counts['transfers'] = DB::table('equipment_transfer_history_table')->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('borrowing_records_table')) {
                DB::table('borrowing_records_table')
                    ->where('borrowing_status', 'Borrowed')
                    ->whereNotNull('borrowing_expected_return_date')
                    ->whereDate('borrowing_expected_return_date', '<', today())
                    ->update(['borrowing_status' => 'Overdue']);

                $counts['borrowing_active'] = DB::table('borrowing_records_table')
                    ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
                    ->count();
                $counts['borrowing_overdue'] = DB::table('borrowing_records_table')
                    ->where('borrowing_status', 'Overdue')
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (Schema::hasTable('disposal_records_table')) {
                $counts['disposal'] = DB::table('disposal_records_table')->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if ($tab === 'transfers' && Schema::hasTable('equipment_transfer_history_table')) {
            $query = DB::table('equipment_transfer_history_table')
                ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'equipment_transfer_history_table.equipment_id')
                ->leftJoin('rooms_table as from_room', 'from_room.room_id', '=', 'equipment_transfer_history_table.from_room_id')
                ->leftJoin('rooms_table as to_room', 'to_room.room_id', '=', 'equipment_transfer_history_table.to_room_id')
                ->select(
                    'equipment_transfer_history_table.*',
                    'equipment_table.equipment_name',
                    'equipment_table.equipment_asset_tag',
                    'from_room.room_name as from_room_name',
                    'to_room.room_name as to_room_name'
                );

            if ($filter === 'recent') {
                $query->where('equipment_transfer_history_table.created_at', '>=', now()->subDays(30));
            }

            if ($q !== '') {
                $needle = '%'.$q.'%';
                $query->where(function ($builder) use ($needle) {
                    $builder->where('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('equipment_table.equipment_asset_tag', 'like', $needle)
                        ->orWhere('from_room.room_name', 'like', $needle)
                        ->orWhere('to_room.room_name', 'like', $needle)
                        ->orWhere('equipment_transfer_history_table.remarks', 'like', $needle);
                });
            }

            $rows = $query->orderByDesc('equipment_transfer_history_table.created_at')->paginate(15)->withQueryString();
        } elseif ($tab === 'borrowing' && Schema::hasTable('borrowing_records_table')) {
            $query = DB::table('borrowing_records_table')
                ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'borrowing_records_table.borrowing_equipment_id')
                ->select('borrowing_records_table.*', 'equipment_table.equipment_name', 'equipment_table.equipment_asset_tag');

            if ($filter === 'Overdue' || $filter === 'Borrowed' || $filter === 'Returned') {
                $query->where('borrowing_records_table.borrowing_status', $filter);
            } elseif ($filter === 'active') {
                $query->whereIn('borrowing_records_table.borrowing_status', ['Borrowed', 'Overdue']);
            }

            if ($q !== '') {
                $needle = '%'.$q.'%';
                $query->where(function ($builder) use ($needle) {
                    $builder->where('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('borrowing_records_table.borrowing_borrower_name', 'like', $needle)
                        ->orWhere('borrowing_records_table.borrowing_borrower_department', 'like', $needle)
                        ->orWhere('borrowing_records_table.borrowing_authorized_by', 'like', $needle);
                });
            }

            $rows = $query->orderByDesc('borrowing_records_table.borrowing_created_at')->paginate(15)->withQueryString();
        } elseif ($tab === 'disposal' && Schema::hasTable('disposal_records_table')) {
            $query = DB::table('disposal_records_table')
                ->leftJoin('equipment_table', 'equipment_table.equipment_id', '=', 'disposal_records_table.disposal_equipment_id')
                ->leftJoin('equipment_categories_table', 'equipment_categories_table.equipment_category_id', '=', 'equipment_table.equipment_category_id')
                ->select(
                    'disposal_records_table.*',
                    'equipment_table.equipment_name',
                    'equipment_table.equipment_asset_tag',
                    'equipment_table.equipment_inventory_status',
                    'equipment_categories_table.equipment_category_name'
                );

            if ($q !== '') {
                $needle = '%'.$q.'%';
                $query->where(function ($builder) use ($needle) {
                    $builder->where('equipment_table.equipment_name', 'like', $needle)
                        ->orWhere('disposal_records_table.disposal_reason', 'like', $needle)
                        ->orWhere('disposal_records_table.disposal_area_location', 'like', $needle)
                        ->orWhere('equipment_categories_table.equipment_category_name', 'like', $needle);
                });
            }

            $rows = $query->orderByDesc('disposal_records_table.disposal_record_id')->paginate(15)->withQueryString();
        }

        return view('admin.operations.movements', [
            'tab' => $tab,
            'filter' => $filter,
            'q' => $q,
            'rows' => $rows,
            'counts' => $counts,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminPipeline(int $risId, bool $withLogs = false): array
    {
        try {
            return $this->buildAdminPipelineInner($risId, $withLogs);
        } catch (\Throwable $e) {
            return [
                'ris_id' => $risId,
                'stages' => [
                    'ris' => [
                        'exists' => true,
                        'key' => 'ris',
                        'type' => 'RIS',
                        'id' => $risId,
                        'label' => RisWorkflow::formNumber(null, $risId),
                        'hint' => 'Pipeline unavailable',
                        'view_url' => route('admin.operations.document', ['type' => 'ris', 'id' => $risId]),
                    ],
                    'atp' => ['exists' => false, 'key' => 'atp', 'type' => 'ATP', 'id' => null, 'label' => 'ATP', 'hint' => null, 'view_url' => null],
                    'rfc' => ['exists' => false, 'key' => 'rfc', 'type' => 'RFC', 'id' => null, 'label' => 'RFC', 'hint' => null, 'view_url' => null],
                    'rr' => ['exists' => false, 'key' => 'rr', 'type' => 'RR', 'id' => null, 'label' => 'RR', 'hint' => null, 'view_url' => null],
                    'liq' => ['exists' => false, 'key' => 'liq', 'type' => 'LIQ', 'id' => null, 'label' => 'LIQ', 'hint' => null, 'view_url' => null],
                ],
                'funds' => null,
                'payment_path' => null,
                'payment_path_label' => 'Not chosen',
                'current_stage' => 'ris',
                'current_hint' => 'Pipeline unavailable',
                'logs' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminPipelineInner(int $risId, bool $withLogs = false): array
    {
        $chain = DocumentLineage::forRis($risId);
        $stageOrder = ['ris', 'atp', 'rfc', 'rr', 'liq'];
        $stages = [];
        $paymentPath = null;
        $funds = null;

        foreach ($stageOrder as $key) {
            $node = $chain[$key] ?? null;
            if (! $node) {
                $stages[$key] = [
                    'exists' => false,
                    'key' => $key,
                    'type' => strtoupper($key),
                    'id' => null,
                    'label' => strtoupper($key),
                    'hint' => null,
                    'view_url' => null,
                ];

                continue;
            }

            $stages[$key] = [
                'exists' => true,
                'key' => $key,
                'type' => $node['type'] ?? strtoupper($key),
                'id' => $node['id'] ?? null,
                'label' => $node['label'] ?? strtoupper($key),
                'hint' => $node['hint'] ?? null,
                'view_url' => route('admin.operations.document', [
                    'type' => $key,
                    'id' => $node['id'],
                ]),
            ];
        }

        if (! empty($stages['rfc']['id']) && Schema::hasTable('request_check_table')) {
            $rfc = DB::table('request_check_table')->where('request_check_id', $stages['rfc']['id'])->first();
            if ($rfc) {
                if (! empty($rfc->request_check_funding_type)) {
                    $paymentPath = $rfc->request_check_funding_type;
                }
                $released = ! empty($rfc->request_check_funds_released_at);
                $funds = [
                    'exists' => true,
                    'label' => $released ? 'Funds released' : 'Funds pending',
                    'status' => $released ? 'Released' : 'Pending',
                    'released_at' => $rfc->request_check_funds_released_at ?? null,
                ];
            }
        }

        if (! $paymentPath && ! empty($stages['atp']['id']) && Schema::hasTable('authority_to_purchase_table')) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $stages['atp']['id'])
                ->first();
            $paymentPath = $atp->authority_purchase_payment_path ?? null;
        }

        $currentStage = 'ris';
        foreach ($stageOrder as $key) {
            if (! empty($stages[$key]['exists'])) {
                $currentStage = $key;
            }
        }

        $logs = [];
        if ($withLogs && Schema::hasTable('approval_logs_table')) {
            try {
                $refIds = collect($stages)
                    ->filter(fn ($s) => ! empty($s['exists']) && ! empty($s['id']))
                    ->mapWithKeys(fn ($s) => [strtoupper($s['key']) => (int) $s['id']]);

                $query = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'users_table.user_id', '=', 'approval_logs_table.approval_log_approved_by')
                    ->select(
                        'approval_logs_table.*',
                        'users_table.user_full_name as actor_name'
                    )
                    ->orderByDesc('approval_logs_table.approval_log_approved_at')
                    ->limit(40);

                $query->where(function ($builder) use ($refIds, $risId) {
                    $builder->where(function ($q) use ($risId) {
                        $q->where('approval_log_reference_type', 'RIS')
                            ->where('approval_log_reference_id', $risId);
                    });
                    foreach ($refIds as $type => $id) {
                        if ($type === 'RIS') {
                            continue;
                        }
                        $aliases = match ($type) {
                            'ATP' => ['ATP', 'authority_to_purchase'],
                            'RFC' => ['RFC', 'request_check', 'Request for Check'],
                            'RR' => ['RR', 'receiving_report', 'Receiving Report'],
                            'LIQ' => ['LIQ', 'liquidation', 'Liquidation Report'],
                            default => [$type],
                        };
                        $builder->orWhere(function ($q) use ($aliases, $id) {
                            $q->whereIn('approval_log_reference_type', $aliases)
                                ->where('approval_log_reference_id', $id);
                        });
                    }
                });

                $logs = $query->get()->map(function ($log) {
                    return [
                        'type' => $log->approval_log_reference_type,
                        'status' => $log->approval_log_approval_status,
                        'level' => $log->approval_log_level ?? null,
                        'remarks' => $log->approval_log_approval_remarks,
                        'actor' => $log->actor_name,
                        'at' => $log->approval_log_approved_at,
                    ];
                })->values()->all();
            } catch (\Throwable $e) {
                $logs = [];
            }
        }

        return [
            'ris_id' => $risId,
            'stages' => $stages,
            'funds' => $funds,
            'payment_path' => $paymentPath,
            'payment_path_label' => ProcurementPaymentPath::label($paymentPath),
            'current_stage' => $currentStage,
            'current_hint' => $stages[$currentStage]['hint'] ?? null,
            'logs' => $logs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDocumentViewPayload(string $type, int $docId): array
    {
        return match ($type) {
            'ris' => $this->loadRisViewPayload($docId),
            'atp' => $this->loadAtpViewPayload($docId),
            'rfc' => $this->loadRfcViewPayload($docId),
            'rr' => $this->loadRrViewPayload($docId),
            'liq' => $this->loadLiqViewPayload($docId),
            default => abort(404),
        };
    }

    private function loadRisViewPayload(int $risId): array
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(! $ris, 404);

        $risItems = $this->risItemsWithLookups($risId);

        $presidentName = 'President';
        if (
            Schema::hasTable('approval_logs_table')
            && ! empty($ris->ris_approved_by_signature)
            && str_starts_with((string) $ris->ris_approved_by_signature, 'data:image')
        ) {
            try {
                $presidentApproval = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                    ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                    ->where('approval_logs_table.approval_log_reference_id', $risId)
                    ->where('approval_logs_table.approval_log_level', 'President')
                    ->where('approval_logs_table.approval_log_approval_status', 'Approved')
                    ->select('users_table.user_full_name')
                    ->first();
                $presidentName = $presidentApproval->user_full_name ?? 'President';
            } catch (\Throwable $e) {
                // keep default
            }
        }

        return [
            'title' => RisWorkflow::formNumber($ris),
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => $presidentName,
        ];
    }

    private function loadAtpViewPayload(int $atpId): array
    {
        $atp = DB::table('authority_to_purchase_table')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('authority_to_purchase_table.authority_purchase_id', $atpId)
            ->select(
                'authority_to_purchase_table.*',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->first();
        abort_if(! $atp, 404);

        $items = collect();
        if (Schema::hasTable('authority_to_purchase_items_table')) {
            $items = DB::table('authority_to_purchase_items_table')
                ->where('authority_purchase_id', $atpId)
                ->orderBy('atp_item_id')
                ->get();
        }

        return [
            'title' => 'ATP '.($atp->authority_purchase_form_number ?? $atpId),
            'atp' => $atp,
            'items' => $items,
        ];
    }

    private function loadRfcViewPayload(int $rfcId): array
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        abort_if(! $rfc, 404);

        return [
            'title' => ($rfc->request_check_form_number ?? 'RFC #'.$rfcId),
            'rfc' => $rfc,
        ];
    }

    private function loadRrViewPayload(int $rrId): array
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->first();
        abort_if(! $rr, 404);

        $rows = collect();
        if (Schema::hasTable('receiving_report_items_table')) {
            $rows = DB::table('receiving_report_items_table')
                ->where('receiving_report_id', $rrId)
                ->orderBy('receiving_report_item_id')
                ->get();
        }

        $paymentPath = null;
        if (! empty($rr->receiving_report_request_check_id) && Schema::hasTable('request_check_table')) {
            $rfc = DB::table('request_check_table')
                ->where('request_check_id', $rr->receiving_report_request_check_id)
                ->first();
            $paymentPath = $rfc->request_check_funding_type ?? null;
            if (! $paymentPath && ! empty($rfc->request_check_authority_purchase_id)) {
                $atp = DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $rfc->request_check_authority_purchase_id)
                    ->first();
                $paymentPath = $atp->authority_purchase_payment_path ?? null;
            }
        }

        return [
            'title' => 'RR '.($rr->receiving_report_form_number ?? $rrId),
            'rr' => $rr,
            'rows' => $rows,
            'allowMultiSupplier' => ProcurementPaymentPath::allowsMultiSupplier($paymentPath),
        ];
    }

    private function loadLiqViewPayload(int $liqId): array
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $liqId)->first();
        abort_if(! $liq, 404);

        $items = collect();
        if (Schema::hasTable('liquidation_report_items_table')) {
            $items = DB::table('liquidation_report_items_table')
                ->where('liquidation_report_id', $liqId)
                ->orderBy('liquidation_item_id')
                ->get();
        }

        return [
            'title' => 'Liquidation '.($liq->liquidation_report_form_number ?? $liqId),
            'liq' => $liq,
            'items' => $items,
        ];
    }

    private function risItemsWithLookups(int $risId)
    {
        $query = DB::table('requisition_issue_slip_items_table')
            ->where('requisition_issue_slip_items_table.ris_id', $risId)
            ->orderBy('ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];

        if (Schema::hasTable('uom_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')) {
            $query->leftJoin('uom_table', 'uom_table.uom_id', '=', 'requisition_issue_slip_items_table.ris_item_uom_id');
            $select[] = 'uom_table.uom_name';
        }

        if (Schema::hasTable('brands_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')) {
            $query->leftJoin('brands_table', 'brands_table.brand_id', '=', 'requisition_issue_slip_items_table.ris_item_brand_id');
            $select[] = 'brands_table.brand_name';
        }

        if (Schema::hasTable('suppliers_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')) {
            $query
                ->leftJoin('suppliers_table', 'suppliers_table.supplier_id', '=', 'requisition_issue_slip_items_table.ris_item_supplier_id')
                ->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');

            $select[] = DB::raw(
                "CASE
                    WHEN suppliers_table.supplier_store_type = 'Online Store'
                        THEN COALESCE(online_suppliers_table.shop_name, CONCAT('Online supplier #', suppliers_table.supplier_id))
                    ELSE COALESCE(physical_suppliers_table.company_name, CONCAT('Physical supplier #', suppliers_table.supplier_id))
                END as supplier_display_name"
            );
        }

        return $query->select($select)->get();
    }

    private function lifecycleAlertsQuery()
    {
        $defaultYears = self::DEFAULT_USEFUL_LIFE_YEARS;
        $lifeExpr = Schema::hasColumn('equipment_table', 'equipment_useful_life_years')
            ? "COALESCE(equipment_useful_life_years, {$defaultYears})"
            : (string) $defaultYears;

        return DB::table('equipment_table')
            ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
            ->whereNotNull(DB::raw('COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at)'))
            ->where(function ($q) {
                $q->whereNull('equipment_inventory_status')
                    ->orWhereNotIn('equipment_inventory_status', ['Disposed']);
            })
            ->select(
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_inventory_status',
                'equipment_table.equipment_warranty_expiration',
                'rooms_table.room_name',
                DB::raw('COALESCE(equipment_purchase_date, equipment_acquired_date, DATE(equipment_created_at)) as start_date'),
                DB::raw("{$lifeExpr} as useful_life_years"),
                DB::raw("TIMESTAMPDIFF(YEAR, COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at), CURDATE()) as age_years"),
                DB::raw("({$lifeExpr} - TIMESTAMPDIFF(YEAR, COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at), CURDATE())) as years_remaining")
            )
            ->havingRaw('years_remaining <= 1');
    }

    private function emptyPager(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 15, max(1, (int) $request->query('page', 1)), [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    private function logAdminOverride(string $refType, int $refId, string $status, string $remarks = ''): void
    {
        if (! Schema::hasTable('approval_logs_table')) {
            return;
        }

        try {
            $payload = [
                'approval_log_reference_type' => $refType,
                'approval_log_reference_id' => $refId,
                'approval_log_approval_status' => $status,
                'approval_log_approval_remarks' => $remarks !== '' ? $remarks : 'Administrator override',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approved_at' => now(),
            ];
            if (Schema::hasColumn('approval_logs_table', 'approval_log_level')) {
                $payload['approval_log_level'] = 'Admin Override';
            }
            DB::table('approval_logs_table')->insert($payload);
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
