<?php

namespace App\Http\Controllers;

use App\Support\Departments;
use App\Support\ReporterImport;
use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Read-only view of faculty and staff reporters and the reports they filed.
 * Reporters are added and edited in Equipment Reporters.
 */
class PersonnelDirectoryController extends Controller
{
    public const STATUSES = ['Active', 'Inactive'];

    public const TYPES = ['Faculty', 'Staff'];

    public const OPEN_REPORT_STATUSES = ['Pending', 'Processing', 'For Replacement'];

    public const REPORT_STATUSES = ['Pending', 'Processing', 'For Replacement', 'Resolved', 'Rejected'];

    public function index(Request $request)
    {
        $hasDepartment = ReporterImport::hasDepartmentColumn() && Departments::tableReady();
        $hasType = ReporterImport::hasTypeColumn();

        if ($request->filled('reporter')) {
            return $this->records($request, (int) $request->query('reporter'), $hasDepartment);
        }

        $search = trim((string) $request->query('search', ''));
        $status = in_array($request->query('status'), self::STATUSES, true) ? (string) $request->query('status') : '';
        $type = $hasType && in_array($request->query('type'), self::TYPES, true) ? (string) $request->query('type') : '';
        $departmentId = $hasDepartment ? (int) $request->query('department', 0) : 0;

        $query = $this->reportersQuery($hasDepartment);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('r.reporter_employee_id', 'LIKE', $like)
                    ->orWhere('r.reporter_full_name', 'LIKE', $like)
                    ->orWhere('r.reporter_email_address', 'LIKE', $like)
                    ->orWhere('r.reporter_contact_number', 'LIKE', $like);
            });
        }

        $query
            ->when($status !== '', fn ($q) => $q->where('r.reporter_status', $status))
            ->when($type !== '', fn ($q) => $q->where('r.reporter_employment_type', $type))
            ->when($departmentId > 0, fn ($q) => $q->where('r.reporter_department_id', $departmentId));

        $people = $query
            ->orderByRaw(ReporterImport::hasNameColumns()
                ? 'COALESCE(r.reporter_last_name, r.reporter_full_name), r.reporter_first_name'
                : 'r.reporter_full_name')
            ->paginate(15)
            ->withQueryString();

        if ($hasDepartment) {
            foreach ($people as $person) {
                if (empty($person->department_name) && ! empty($person->reporter_employee_id)) {
                    $person->department_name = $this->resolveReporterDepartmentName($person);
                }
            }
        }

        return view('maintenance-personnel.personnel-directory.index', [
            'people' => $people,
            'stats' => $this->stats($hasType),
            'departments' => $hasDepartment
                ? DB::table('departments_table')->orderBy('department_name')->get(['department_id', 'department_name'])
                : collect(),
            'hasDepartment' => $hasDepartment,
            'hasType' => $hasType,
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'departmentId' => $departmentId,
        ]);
    }

    private function records(Request $request, int $reporterId, bool $hasDepartment)
    {
        $reporter = $this->reportersQuery($hasDepartment)->where('r.reporter_id', $reporterId)->first();

        if ($reporter && $hasDepartment && empty($reporter->department_name)) {
            $reporter->department_name = $this->resolveReporterDepartmentName($reporter);
        }

        if (! $reporter) {
            return redirect()
                ->route('maintenance.personnel-directory.index')
                ->with('error', 'Reporter not found.');
        }

        $recordStatus = in_array($request->query('record_status'), self::REPORT_STATUSES, true)
            ? (string) $request->query('record_status')
            : '';

        $records = DB::table('reports_table')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->where('reports_table.report_reporter_employee_id', $reporter->reporter_employee_id)
            ->when($recordStatus !== '', fn ($q) => $q->where('reports_table.report_current_status', $recordStatus))
            ->select(
                'reports_table.report_id',
                'reports_table.report_submitted_at',
                'reports_table.report_current_status',
                'reports_table.report_urgency_level',
                'reports_table.report_suggested_issue',
                'reports_table.report_problem_description',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'rooms_table.room_name'
            )
            ->orderByDesc('reports_table.report_submitted_at')
            ->paginate(10, ['*'], 'records_page')
            ->withQueryString();

        return view('maintenance-personnel.personnel-directory.records', [
            'reporter' => $reporter,
            'records' => $records,
            'recordStatus' => $recordStatus,
            'canOpenReports' => RoleAccess::hasRole(RoleAccess::MAINTENANCE, $request->user()),
        ]);
    }

    private function reportersQuery(bool $hasDepartment)
    {
        $openStatuses = "'".implode("','", self::OPEN_REPORT_STATUSES)."'";

        $counts = DB::table('reports_table')
            ->whereNotNull('report_reporter_employee_id')
            ->groupBy('report_reporter_employee_id')
            ->select(
                'report_reporter_employee_id',
                DB::raw('COUNT(*) as reports_total'),
                DB::raw("SUM(CASE WHEN report_current_status IN ({$openStatuses}) THEN 1 ELSE 0 END) as reports_open"),
                DB::raw("SUM(CASE WHEN report_current_status = 'Resolved' THEN 1 ELSE 0 END) as reports_resolved"),
                DB::raw('MAX(report_submitted_at) as last_reported_at')
            );

        return DB::table('reporters_table as r')
            ->leftJoinSub($counts, 'rc', 'rc.report_reporter_employee_id', '=', 'r.reporter_employee_id')
            ->when($hasDepartment, fn ($q) => $q->leftJoin('departments_table as d', 'd.department_id', '=', 'r.reporter_department_id'))
            ->select(
                'r.*',
                DB::raw('COALESCE(rc.reports_total, 0) as reports_total'),
                DB::raw('COALESCE(rc.reports_open, 0) as reports_open'),
                DB::raw('COALESCE(rc.reports_resolved, 0) as reports_resolved'),
                'rc.last_reported_at'
            )
            ->when($hasDepartment, fn ($q) => $q->addSelect('d.department_name'));
    }

    private function resolveReporterDepartmentName(object $reporter): ?string
    {
        if (! ReporterImport::hasDepartmentColumn() || ! Departments::tableReady()) {
            return null;
        }

        $employeeId = trim((string) ($reporter->reporter_employee_id ?? ''));
        $reporterId = (int) ($reporter->reporter_id ?? 0);

        if ($reporterId <= 0 && $employeeId === '') {
            return null;
        }

        $linked = DB::table('custodians_table as person')
            ->leftJoin('departments_table as dept', 'dept.department_id', '=', 'person.custodian_department_id')
            ->where(function ($query) use ($reporterId, $employeeId) {
                if ($reporterId > 0) {
                    $query->orWhere('person.custodian_reporter_id', $reporterId);
                }
                if ($employeeId !== '') {
                    $query->orWhere('person.custodian_employee_id', $employeeId);
                }
            })
            ->orderByRaw('person.custodian_reporter_id = ? DESC', [$reporterId])
            ->value('dept.department_name');

        return $linked !== null && $linked !== '' ? (string) $linked : null;
    }

    /**
     * @return array{total:int, active:int, faculty:int, staff:int, inactive:int, reports:int}
     */
    private function stats(bool $hasType): array
    {
        $active = fn () => DB::table('reporters_table')->where('reporter_status', 'Active');

        return [
            'total' => DB::table('reporters_table')->count(),
            'active' => $active()->count(),
            'faculty' => $hasType ? $active()->where('reporter_employment_type', 'Faculty')->count() : 0,
            'staff' => $hasType ? $active()->where('reporter_employment_type', 'Staff')->count() : 0,
            'inactive' => DB::table('reporters_table')->where('reporter_status', '!=', 'Active')->count(),
            'reports' => DB::table('reports_table')
                ->whereIn('report_reporter_employee_id', DB::table('reporters_table')->select('reporter_employee_id'))
                ->count(),
        ];
    }
}
