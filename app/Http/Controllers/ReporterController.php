<?php

namespace App\Http\Controllers;

use App\Services\ReportSubmissionService;
use App\Support\IssueChecks;
use App\Support\PropertyAssignments;
use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\ReporterPins;
use App\Support\ReportSeverity;
use App\Support\SuggestedIssues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class ReporterController extends Controller
{
    private function reportStoreResponse(
        Request $request,
        bool $success,
        string $message,
        int $status = 200,
        array $extra = []
    ) {
        // Report modal always submits via fetch and expects JSON.
        return response()->json(array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra), $success ? $status : ($status >= 400 ? $status : 422));
    }

    /*
    |--------------------------------------------------------------------------
    | LANDING PAGE
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $equipmentCountSub = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
                ->select('equipment_room_id', DB::raw('COUNT(*) as equipment_count'))
                ->whereNotNull('equipment_room_id')
                ->groupBy('equipment_room_id')
        );

        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($query) => $query->where('rooms_table.room_is_archived', false)
            )
            ->leftJoin(
                'floors_table',
                'rooms_table.room_floor_id',
                '=',
                'floors_table.floor_id'
            )
            ->leftJoin(
                'buildings_table',
                'floors_table.floor_building_id',
                '=',
                'buildings_table.building_id'
            )
            ->leftJoinSub(
                $equipmentCountSub,
                'room_equipment_counts',
                'rooms_table.room_id',
                '=',
                'room_equipment_counts.equipment_room_id'
            )
            ->select(
                'rooms_table.*',
                'floors_table.floor_level',
                'buildings_table.building_name',
                DB::raw('COALESCE(room_equipment_counts.equipment_count, 0) as equipment_count')
            )
            ->orderByRaw("
                CASE
                    WHEN floors_table.floor_level LIKE '2nd%' THEN 1
                    WHEN floors_table.floor_level LIKE '3rd%' THEN 2
                    ELSE 99
                END ASC
            ")
            ->orderByRaw(
                "CASE WHEN floors_table.floor_level LIKE '3rd%' AND rooms_table.room_type = ? THEN 0 ELSE 1 END ASC",
                ['Lecture Room']
            )
            ->orderBy('rooms_table.room_name')
            ->get();

        $equipment = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
        )
            ->orderBy('equipment_name')
            ->get();

        $reportsQuery = Schema::hasTable('reports_table')
            ? DB::table('reports_table')
            : null;

        $equipmentQuery = Schema::hasTable('equipment_table')
            ? DB::table('equipment_table')
            : null;

        $totalRooms = $rooms->count();

        $totalEquipment = $equipmentQuery
            ? (clone $equipmentQuery)->where('equipment_inventory_status', '!=', 'Disposed')->count()
            : 0;

        $healthyEquipment = $equipmentQuery
            ? (clone $equipmentQuery)
                ->where('equipment_inventory_status', '!=', 'Disposed')
                ->where('equipment_condition_status', 'Good')
                ->count()
            : 0;

        $assetHealthPercent = $totalEquipment > 0
            ? round(($healthyEquipment / $totalEquipment) * 100, 1)
            : 0;

        $resolvedThisMonth = $reportsQuery
            ? (clone $reportsQuery)
                ->where('report_current_status', 'Resolved')
                ->whereBetween('report_submitted_at', [
                    now()->copy()->startOfMonth(),
                    now()->copy()->endOfMonth(),
                ])
                ->count()
            : 0;

        $resolvedLastMonth = $reportsQuery
            ? (clone $reportsQuery)
                ->where('report_current_status', 'Resolved')
                ->whereBetween('report_submitted_at', [
                    now()->copy()->subMonth()->startOfMonth(),
                    now()->copy()->subMonth()->endOfMonth(),
                ])
                ->count()
            : 0;

        $resolvedChangePercent = null;
        if ($resolvedLastMonth > 0) {
            $resolvedChangePercent = round((($resolvedThisMonth - $resolvedLastMonth) / $resolvedLastMonth) * 100, 1);
        } elseif ($resolvedThisMonth > 0) {
            $resolvedChangePercent = 100;
        }

        $weeklyReports = collect();
        $weeklyMax = 1;
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->copy()->subDays($i);
            $count = $reportsQuery
                ? (clone $reportsQuery)->whereDate('report_submitted_at', $day->toDateString())->count()
                : 0;

            $weeklyReports->push((object) [
                'label' => $day->format('D'),
                'count' => $count,
            ]);
            $weeklyMax = max($weeklyMax, $count);
        }

        $weeklyReports = $weeklyReports->map(function ($day) use ($weeklyMax) {
            $day->height = $day->count > 0
                ? max(12, (int) round(($day->count / $weeklyMax) * 100))
                : 8;
            $day->isPeak = $day->count === $weeklyMax && $day->count > 0;
            return $day;
        });

        $resolvedAtColumn = $reportsQuery && Schema::hasColumn('reports_table', 'report_updated_at')
            ? 'report_updated_at'
            : 'report_submitted_at';

        $weekStart = now()->copy()->startOfWeek();
        $resolvedThisWeek = $reportsQuery
            ? (clone $reportsQuery)
                ->where('report_current_status', 'Resolved')
                ->where($resolvedAtColumn, '>=', $weekStart)
                ->count()
            : 0;

        $totalReports = $reportsQuery ? (clone $reportsQuery)->count() : 0;
        $openReports = $reportsQuery
            ? (clone $reportsQuery)
                ->whereIn('report_current_status', ['Pending', 'Processing'])
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($query) => $query->where('report_is_archived', false)
                )
                ->count()
            : 0;
        $resolvedReports = $reportsQuery
            ? (clone $reportsQuery)->where('report_current_status', 'Resolved')->count()
            : 0;

        $qrTaggedCount = $equipmentQuery
            ? (clone $equipmentQuery)
                ->whereNotNull('equipment_qr_code')
                ->where('equipment_qr_code', '!=', '')
                ->count()
            : 0;

        $maintenanceLead = Schema::hasTable('users_table')
            ? DB::table('users_table')
                ->leftJoin('roles_table', 'users_table.user_role_id', '=', 'roles_table.role_id')
                ->where('users_table.user_role_id', 2)
                ->select(
                    'users_table.user_full_name',
                    'users_table.user_employee_id',
                    'roles_table.role_name'
                )
                ->orderBy('users_table.user_full_name')
                ->first()
            : null;

        $leadInitials = 'PA';
        if ($maintenanceLead && $maintenanceLead->user_full_name) {
            $parts = preg_split('/\s+/', trim($maintenanceLead->user_full_name));
            $leadInitials = strtoupper(
                mb_substr($parts[0] ?? 'P', 0, 1)
                . mb_substr($parts[count($parts) - 1] ?? 'A', 0, 1)
            );
        }

        $recentReports = $reportsQuery
            ? (clone $reportsQuery)
                ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
                ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
                ->select(
                    'reports_table.report_current_status',
                    'reports_table.report_unlisted_equipment_name',
                    'reports_table.report_submitted_at',
                    'rooms_table.room_name',
                    'equipment_table.equipment_name'
                )
                ->orderByDesc('reports_table.report_submitted_at')
                ->limit(3)
                ->get()
            : collect();

        $statusMeta = [
            'Resolved' => ['label' => 'Done', 'color' => '#16a34a', 'dot' => '#16a34a'],
            'Processing' => ['label' => 'Active', 'color' => '#d97706', 'dot' => '#fff200'],
            'Pending' => ['label' => 'New', 'color' => '#0025cc', 'dot' => '#0025cc'],
            'For Replacement' => ['label' => 'Replace', 'color' => '#d97706', 'dot' => '#fff200'],
            'Rejected' => ['label' => 'Closed', 'color' => '#6b7280', 'dot' => '#94a3b8'],
        ];

        $recentActivities = $recentReports->map(function ($report) use ($statusMeta) {
            $status = $report->report_current_status ?: 'Pending';
            $meta = $statusMeta[$status] ?? $statusMeta['Pending'];
            $subject = $report->equipment_name
                ?: $report->report_unlisted_equipment_name
                ?: 'Damage report';
            $place = $report->room_name ? ' · ' . $report->room_name : '';

            return (object) [
                'label' => $subject . $place,
                'status' => $meta['label'],
                'color' => $meta['color'],
                'dot' => $meta['dot'],
            ];
        });

        $buildings = Schema::hasTable('buildings_table')
            ? DB::table('buildings_table')
                ->orderBy('building_name')
                ->pluck('building_name')
                ->filter()
                ->values()
            : collect();

        $upcomingSchedules = Schema::hasTable('maintenance_schedules_table')
            ? DB::table('maintenance_schedules_table')
                ->leftJoin(
                    'equipment_table',
                    'maintenance_schedules_table.maintenance_schedule_equipment_id',
                    '=',
                    'equipment_table.equipment_id'
                )
                ->leftJoin(
                    'rooms_table',
                    'equipment_table.equipment_room_id',
                    '=',
                    'rooms_table.room_id'
                )
                ->whereNotNull('maintenance_schedules_table.maintenance_schedule_next_date')
                ->whereDate('maintenance_schedules_table.maintenance_schedule_next_date', '>=', today())
                ->orderBy('maintenance_schedules_table.maintenance_schedule_next_date')
                ->limit(3)
                ->select(
                    'maintenance_schedules_table.maintenance_schedule_title',
                    'maintenance_schedules_table.maintenance_schedule_description',
                    'maintenance_schedules_table.maintenance_schedule_next_date',
                    'equipment_table.equipment_name',
                    'rooms_table.room_name'
                )
                ->get()
            : collect();

        $announcements = $upcomingSchedules->map(function ($schedule) {
            $place = collect([
                $schedule->room_name,
                $schedule->equipment_name,
            ])->filter()->implode(' · ');

            return (object) [
                'announcement_title' => $schedule->maintenance_schedule_title ?: 'Scheduled maintenance',
                'announcement_description' => $schedule->maintenance_schedule_description
                    ?: ($place !== '' ? $place : 'Upcoming campus maintenance.'),
            ];
        });

        $currentMonth = now()->copy()->startOfMonth();
        $monthStart = $currentMonth->copy()->startOfDay();
        $monthEnd = $currentMonth->copy()->endOfMonth()->endOfDay();
        $daysInMonth = (int) $currentMonth->daysInMonth;

        $monthlyDayCounts = collect();
        if ($reportsQuery) {
            $monthlyDayCounts = (clone $reportsQuery)
                ->selectRaw('DAY(report_submitted_at) as day_num, COUNT(*) as total')
                ->whereBetween('report_submitted_at', [$monthStart, $monthEnd])
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($query) => $query->where('report_is_archived', false)
                )
                ->groupBy(DB::raw('DAY(report_submitted_at)'))
                ->pluck('total', 'day_num');
        }

        $monthlyReportDays = collect();
        $monthlyReportMax = 1;
        for (
            $cursor = $monthStart->copy();
            $cursor->month === $currentMonth->month && $cursor->year === $currentMonth->year;
            $cursor->addDay()
        ) {
            $day = (int) $cursor->day;
            $count = (int) ($monthlyDayCounts[$day] ?? 0);
            $monthlyReportMax = max($monthlyReportMax, $count);
            $monthlyReportDays->push((object) [
                'day' => $day,
                'count' => $count,
                'date' => $cursor->toDateString(),
            ]);
        }

        $monthlyReportDays = $monthlyReportDays->map(function ($item) use ($monthlyReportMax) {
            $item->height = $item->count > 0
                ? max(10, (int) round(($item->count / $monthlyReportMax) * 100))
                : 6;
            $item->isPeak = $item->count === $monthlyReportMax && $item->count > 0;
            return $item;
        });

        $monthlyReportTotal = (int) $monthlyReportDays->sum('count');
        $monthlyReportLabel = now()->format('F Y');

        $yearStart = now()->copy()->startOfYear()->startOfDay();
        $yearEnd = now()->copy()->endOfYear()->endOfDay();
        $yearlyReportTotal = 0;
        if ($reportsQuery) {
            $yearlyReportTotal = (int) (clone $reportsQuery)
                ->whereBetween('report_submitted_at', [$yearStart, $yearEnd])
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($query) => $query->where('report_is_archived', false)
                )
                ->count();
        }
        $yearlyReportYear = (int) now()->year;

        $monthlyStatusCounts = [
            'Pending' => 0,
            'Processing' => 0,
            'Resolved' => 0,
        ];
        if ($reportsQuery) {
            $statusRows = (clone $reportsQuery)
                ->selectRaw('report_current_status as status, COUNT(*) as total')
                ->whereBetween('report_submitted_at', [$monthStart, $monthEnd])
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($query) => $query->where('report_is_archived', false)
                )
                ->groupBy('report_current_status')
                ->pluck('total', 'status');

            foreach ($monthlyStatusCounts as $status => $_) {
                $monthlyStatusCounts[$status] = (int) ($statusRows[$status] ?? 0);
            }
        }

        $equipmentReportedTodayCount = 0;
        $latestReportedEquipmentToday = collect();

        if ($reportsQuery) {
            $entries = collect();

            if (ReportItems::tableExists()) {
                $itemQuery = ReportItems::itemsQuery()
                    ->join('reports_table', 'report_items_table.report_id', '=', 'reports_table.report_id')
                    ->leftJoin('rooms_table as report_rooms', 'reports_table.report_room_id', '=', 'report_rooms.room_id');

                if (Schema::hasTable('floors_table')) {
                    $itemQuery->leftJoin(
                        'floors_table as report_floors',
                        'report_rooms.room_floor_id',
                        '=',
                        'report_floors.floor_id'
                    );
                }

                $itemQuery
                    ->whereDate('reports_table.report_submitted_at', today())
                    ->when(
                        Schema::hasColumn('reports_table', 'report_is_archived'),
                        fn ($query) => $query->where('reports_table.report_is_archived', false)
                    )
                    ->addSelect([
                        'reports_table.report_submitted_at',
                        'report_rooms.room_name as report_room_name',
                    ]);

                if (Schema::hasTable('floors_table')) {
                    $itemQuery->addSelect('report_floors.floor_level as report_floor_level');
                }

                foreach ($itemQuery
                    ->orderByDesc('reports_table.report_submitted_at')
                    ->orderByDesc('report_items_table.report_item_id')
                    ->get() as $item) {
                    $entries->push((object) [
                        'equipment_name' => ReportItems::displayName($item),
                        'identifier' => $this->formatEquipmentIdentifier($item),
                        'location' => $this->formatReportLocation($item),
                        'submitted_at' => $item->report_submitted_at,
                    ]);
                }
            }

            $legacyQuery = (clone $reportsQuery)
                ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
                ->leftJoin('rooms_table as report_rooms', 'reports_table.report_room_id', '=', 'report_rooms.room_id');

            if (Schema::hasTable('floors_table')) {
                $legacyQuery->leftJoin(
                    'floors_table as report_floors',
                    'report_rooms.room_floor_id',
                    '=',
                    'report_floors.floor_id'
                );
            }

            if (ReportItems::tableExists()) {
                $legacyQuery->whereNotIn('reports_table.report_id', function ($sub) {
                    $sub->select('report_id')->from('report_items_table');
                });
            }

            $legacySelect = [
                'reports_table.report_submitted_at',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_serial_number',
                'equipment_table.equipment_current_location',
                'report_rooms.room_name as report_room_name',
            ];

            if (Schema::hasTable('floors_table')) {
                $legacySelect[] = 'report_floors.floor_level as report_floor_level';
            }

            foreach ($legacyQuery
                ->whereDate('reports_table.report_submitted_at', today())
                ->when(
                    Schema::hasColumn('reports_table', 'report_is_archived'),
                    fn ($query) => $query->where('reports_table.report_is_archived', false)
                )
                ->select($legacySelect)
                ->orderByDesc('reports_table.report_submitted_at')
                ->get() as $report) {
                $name = trim((string) ($report->equipment_name ?? ''));
                if ($name === '') {
                    $name = trim((string) ($report->report_unlisted_equipment_name ?? ''));
                }
                if ($name === '') {
                    $name = 'Unlisted equipment';
                }

                $entries->push((object) [
                    'equipment_name' => $name,
                    'identifier' => $this->formatEquipmentIdentifier($report),
                    'location' => $this->formatReportLocation($report),
                    'submitted_at' => $report->report_submitted_at,
                ]);
            }

            $equipmentReportedTodayCount = $entries->count();

            $latestReportedEquipmentToday = $entries
                ->sortByDesc('submitted_at')
                ->take(3)
                ->values();
        }

        $campusBuildings = $rooms
            ->groupBy(fn ($room) => $room->building_name ?: 'Campus Building')
            ->map(function ($buildingRooms, $buildingName) {
                $floors = $buildingRooms
                    ->groupBy(fn ($room) => (int) ($room->floor_level ?: 1))
                    ->sortKeys()
                    ->map(function ($floorRooms, $level) {
                        return (object) [
                            'level' => (int) $level,
                            'rooms' => $floorRooms->count(),
                        ];
                    })
                    ->values();

                return (object) [
                    'name' => $buildingName,
                    'floors' => $floors,
                    'room_count' => $buildingRooms->count(),
                    'floor_count' => $floors->count(),
                ];
            })
            ->values();

        if ($campusBuildings->isEmpty()) {
            $campusBuildings = collect([(object) [
                'name' => 'STI College Ormoc',
                'floors' => collect([
                    (object) ['level' => 1, 'rooms' => 8],
                    (object) ['level' => 2, 'rooms' => 8],
                    (object) ['level' => 3, 'rooms' => 6],
                ]),
                'room_count' => 22,
                'floor_count' => 3,
            ]]);
        }

        return view('landing.index', compact(
            'rooms',
            'equipment',
            'announcements',
            'assetHealthPercent',
            'resolvedChangePercent',
            'weeklyReports',
            'maintenanceLead',
            'leadInitials',
            'resolvedThisWeek',
            'recentActivities',
            'totalReports',
            'openReports',
            'resolvedReports',
            'totalRooms',
            'totalEquipment',
            'qrTaggedCount',
            'buildings',
            'upcomingSchedules',
            'monthlyReportDays',
            'monthlyReportTotal',
            'monthlyReportLabel',
            'daysInMonth',
            'monthlyStatusCounts',
            'campusBuildings',
            'yearlyReportTotal',
            'yearlyReportYear',
            'equipmentReportedTodayCount',
            'latestReportedEquipmentToday'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE REPORT
    |--------------------------------------------------------------------------
    */

    public function storeReport(Request $request, ReportSubmissionService $reports)
    {
        $reporter = ReporterPins::sessionReporter($request);

        if (! $reporter) {
            return $this->reportStoreResponse(
                $request,
                false,
                'Your reporter verification expired. Enter your employee ID and PIN again.',
                401,
                ['pin_required' => true]
            );
        }

        // The verified session decides who is reporting, never the form field.
        $request->merge(['report_reporter_employee_id' => $reporter->reporter_employee_id]);

        $result = $reports->submit($request);

        if ($result['success'] ?? false) {
            ReporterPins::endSession($request);
        }

        return $this->reportStoreResponse(
            $request,
            (bool) ($result['success'] ?? false),
            (string) ($result['message'] ?? 'Unable to submit report.'),
            (int) ($result['status'] ?? (($result['success'] ?? false) ? 200 : 422)),
            array_filter([
                'report_id' => $result['report_id'] ?? null,
                'ticket_code' => $result['ticket_code'] ?? null,
                'item_count' => $result['item_count'] ?? null,
                'merged' => $result['merged'] ?? null,
                'errors' => $result['errors'] ?? null,
            ], fn ($value) => $value !== null)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW AUTOMATIC PRIORITY (same rules as submit)
    |--------------------------------------------------------------------------
    */

    public function previewSeverity(Request $request)
    {
        $validated = $request->validate([
            'items' => 'nullable|array|max:'.\App\Support\RisWorkflow::MAX_ITEMS,
            'items.*.equipment_id' => 'nullable|integer',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.issue' => 'nullable|string|max:255',
            'items.*.room_id' => 'nullable|integer',
            'description' => 'nullable|string|max:5000',
        ]);

        $items = collect($validated['items'] ?? [])
            ->map(fn ($item) => [
                'equipment_id' => isset($item['equipment_id']) ? (int) $item['equipment_id'] : null,
                'name' => $item['name'] ?? null,
                'issue' => $item['issue'] ?? null,
                'room_id' => isset($item['room_id']) ? (int) $item['room_id'] : null,
            ])
            ->values()
            ->all();

        $assessment = ReportSeverity::assess($items, $validated['description'] ?? null);

        return response()->json($assessment + ReportSeverity::meta($assessment['level']));
    }

    /*
    |--------------------------------------------------------------------------
    | GET EQUIPMENT BY ROOM
    |--------------------------------------------------------------------------
    */

    public function getEquipmentByRoom($roomId)
    {
        $query = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
                ->where('equipment_table.equipment_room_id', $roomId)
        );

        if (Schema::hasTable('equipment_categories_table')) {
            $query->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            );
        }

        $select = ['equipment_table.*'];

        if (Schema::hasTable('equipment_categories_table')) {
            $select[] = 'equipment_categories_table.equipment_category_name';
        }

        $equipment = ReportGrouping::enrichEquipmentWithOpenReports(
            $query->select($select)->orderBy('equipment_table.equipment_name')->get(),
            (int) $roomId
        );

        return response()->json($equipment);
    }

    /*
    |--------------------------------------------------------------------------
    | GET REPORTER INFORMATION
    |--------------------------------------------------------------------------
    */

    // =====================================================
    // GET REPORTER INFORMATION
    // PUBLIC VISITORS ONLY GET THE REPORTER THEY VERIFIED IN PHASE 1;
    // SIGNED-IN STAFF (WALK-IN LOGGING) CAN LOOK UP ANY EMPLOYEE ID
    // =====================================================

    public function getReporter(Request $request, $employeeId)
    {
        if (! $request->user()) {
            $reporter = ReporterPins::sessionMatches($request, (string) $employeeId);

            return response()->json($reporter ? [
                'reporter_full_name' => $reporter->reporter_full_name,
                'reporter_status' => $reporter->reporter_status,
            ] : null);
        }

        $reporter = DB::table('reporters_table')
            ->where('reporter_employee_id', $employeeId)
            ->first();

        if (! $reporter) {
            return response()->json(null);
        }

        // Try to resolve a linked custodian / department for this employee id.
        $custodian = DB::table('custodians_table as c')
            ->leftJoin('departments_table as d', 'd.department_id', '=', 'c.custodian_department_id')
            ->where(function ($q) use ($employeeId, $reporter) {
                $q->where('c.custodian_employee_id', $employeeId)
                  ->orWhere('c.custodian_reporter_id', DB::table('reporters_table')->where('reporter_employee_id', $employeeId)->value('reporter_id'));
            })
            ->where('c.custodian_status', '!=', 'Inactive')
            ->select('c.custodian_id', 'c.custodian_department_id', 'd.department_name')
            ->first();

        return response()->json([
            'reporter_full_name' => $reporter->reporter_full_name,
            'reporter_status' => $reporter->reporter_status,
            'department_id' => $custodian->custodian_department_id ?? null,
            'department_name' => $custodian->department_name ?? null,
        ]);
    }

    public function getAssignedEquipment(Request $request, $employeeId)
    {
        if (! $request->user() && ! ReporterPins::sessionMatches($request, (string) $employeeId)) {
            return response()->json([]);
        }

        return response()->json(
            PropertyAssignments::reportableForEmployee((string) $employeeId)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PHASE 1: VERIFY EMPLOYEE ID + PIN
    |--------------------------------------------------------------------------
    */

    public function verifyPin(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'string', 'max:12'],
        ]);

        if (! ReporterPins::ready()) {
            return response()->json([
                'success' => false,
                'message' => 'Reporter PINs are not set up yet. Please contact the system administrator.',
            ], 503);
        }

        $result = ReporterPins::verify($request->employee_id, $request->pin, (string) $request->ip());

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'code' => $result['code'] ?? null,
                'message' => $result['message'],
                'retry_after' => $result['retry_after'] ?? null,
            ], $result['status']);
        }

        // New session ID without rotating the CSRF token the open report form still uses.
        $request->session()->migrate(true);
        ReporterPins::startSession($request, $result['reporter']);

        return response()->json($this->pinSessionPayload($result['reporter']));
    }

    public function pinSession(Request $request)
    {
        $reporter = ReporterPins::sessionReporter($request);

        return response()->json($reporter
            ? $this->pinSessionPayload($reporter)
            : ['success' => false]);
    }

    public function exitPin(Request $request)
    {
        ReporterPins::endSession($request);

        return response()->json(['success' => true]);
    }

    private function pinSessionPayload(object $reporter): array
    {
        return [
            'success' => true,
            'reporter_employee_id' => $reporter->reporter_employee_id,
            'reporter_full_name' => $reporter->reporter_full_name,
            'reporter_status' => $reporter->reporter_status,
            'idle_minutes' => ReporterPins::SESSION_IDLE_MINUTES,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PIN
    | Same answer for every employee ID so the response never reveals who exists.
    |--------------------------------------------------------------------------
    */

    public function forgotPin(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'string', 'max:20'],
        ]);

        $key = 'reporter-pin-forgot:'.sha1(ReporterPins::normalizeEmployeeId($request->employee_id));

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 15 * 60);
            ReporterPins::sendResetLink($request->employee_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'If this employee ID has an email on file, we sent a link to set a new PIN. It expires in '
                .ReporterPins::RESET_MINUTES.' minutes. No email on file? Ask the system administrator to reset your PIN.',
        ]);
    }

    public function showPinReset(string $token)
    {
        $reset = ReporterPins::findReset($token);

        if (! $reset) {
            return redirect('/')
                ->with('success', 'That PIN reset link is invalid, already used, or expired. Choose Forgot PIN again to get a new one.')
                ->with('success_title', 'Link expired');
        }

        return view('landing.reset-reporter-pin', [
            'token' => $token,
            'reporterName' => $reset->reporter_full_name,
            'employeeId' => $reset->reporter_employee_id,
        ]);
    }

    public function completePinReset(Request $request, string $token)
    {
        $reset = ReporterPins::findReset($token);

        if (! $reset) {
            return redirect('/')
                ->with('success', 'That PIN reset link is invalid, already used, or expired. Choose Forgot PIN again to get a new one.')
                ->with('success_title', 'Link expired');
        }

        $request->validate([
            'pin' => ['required', 'digits:'.ReporterPins::LENGTH, 'confirmed'],
        ], [
            'pin.digits' => 'PIN must be exactly '.ReporterPins::LENGTH.' digits.',
            'pin.confirmed' => 'The two PINs do not match.',
        ]);

        if ($reason = ReporterPins::weaknessReason($request->pin)) {
            return back()->withErrors(['pin' => $reason]);
        }

        ReporterPins::completeReset($reset, $request->pin);

        return redirect('/')
            ->with('success', 'Your new PIN is saved. Use it with your employee ID '.$reset->reporter_employee_id.' when you make a report.')
            ->with('success_title', 'PIN updated')
            ->with('open_report', true);
    }

    // REPORTCONTROLLER.PHP

    public function getSuggestions($equipmentId)
    {
        $equipment = DB::table(
            'equipment_table'
        )
        ->where(
            'equipment_id',
            $equipmentId
        )
        ->first();

        if (!$equipment) {

            return response()->json([]);

        }

        return response()->json(
            SuggestedIssues::namesForEquipment($equipment)
        );
    }

    /** Quick checks for the suggested issue the reporter picked on this equipment. */
    public function getIssueChecks(Request $request, $equipmentId)
    {
        $equipment = DB::table('equipment_table')->where('equipment_id', $equipmentId)->first();

        return response()->json([
            'checks' => $equipment ? IssueChecks::payloadsFor($equipment, (string) $request->query('issue', '')) : [],
        ]);
    }

    /** "That fixed it": no report is filed, but maintenance can see which checks solve problems. */
    public function selfFixed(Request $request)
    {
        $reporter = ReporterPins::ready() ? ReporterPins::sessionReporter($request) : null;

        if (! $reporter) {
            return response()->json([
                'success' => false,
                'pin_required' => true,
                'message' => 'Your reporter verification expired. Enter your employee ID and PIN again.',
            ], 401);
        }

        $validated = $request->validate([
            'equipment_id' => ['required', 'integer'],
            'check_id' => ['required', 'integer'],
        ]);

        $equipment = DB::table('equipment_table')->where('equipment_id', $validated['equipment_id'])->first();

        if (! $equipment || ! IssueChecks::recordSelfFix($equipment, (int) $validated['check_id'], $reporter, 'web')) {
            return response()->json(['success' => false, 'message' => 'That check was not found.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Glad it works now. No report was sent.',
        ]);
    }

    private function formatEquipmentIdentifier(object $row): string
    {
        $tag = trim((string) ($row->equipment_asset_tag ?? ''));
        if ($tag !== '') {
            return $tag;
        }

        $serial = trim((string) ($row->equipment_serial_number ?? ''));
        if ($serial !== '') {
            return $serial;
        }

        return '—';
    }

    private function formatReportLocation(object $row): string
    {
        $reportRoom = trim((string) ($row->report_room_name ?? ''));
        $reportFloor = $row->report_floor_level ?? null;
        $equipmentRoom = trim((string) ($row->room_name ?? ''));
        $currentLocation = trim((string) ($row->equipment_current_location ?? ''));

        if ($reportRoom !== '') {
            if ($reportFloor !== null && $reportFloor !== '') {
                return $reportFloor.' - '.$reportRoom;
            }

            return $reportRoom;
        }

        if ($equipmentRoom !== '') {
            return $equipmentRoom;
        }

        if ($currentLocation !== '') {
            return $currentLocation;
        }

        return '—';
    }
}

