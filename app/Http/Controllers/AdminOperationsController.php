<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceReportService;
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

        return view('admin.operations.overview', compact('stats'));
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
            'Admin override: set status to '.$status,
            $remarks
        );

        return back()->with('success', ($result['message'] ?? 'Report updated.').' (Admin override)');
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
                        'ris_items_sum.ris_calculated_total',
                        'creators.user_full_name as created_by_name',
                        'procurement_requests_table.procurement_request_id'
                    );

                if ($filter !== 'all' && Schema::hasColumn('requisition_issue_slip_table', 'ris_status')) {
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

                $rows = $query->orderByDesc('ris.ris_id')->paginate(15)->withQueryString();
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

    private function lifecycleAlertsQuery()
    {
        $years = self::DEFAULT_USEFUL_LIFE_YEARS;

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
                DB::raw("TIMESTAMPDIFF(YEAR, COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at), CURDATE()) as age_years"),
                DB::raw("({$years} - TIMESTAMPDIFF(YEAR, COALESCE(equipment_purchase_date, equipment_acquired_date, equipment_created_at), CURDATE())) as years_remaining")
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
                'approval_log_approval_remarks' => $remarks !== '' ? $remarks : 'Admin override',
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
