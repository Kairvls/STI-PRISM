<?php

namespace App\Http\Controllers;

use App\Services\ReportSubmissionService;
use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\ReporterApprovals;
use App\Support\ReporterImport;
use App\Support\SuggestedIssues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

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
        $result = $reports->submit($request);

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
    | GET EQUIPMENT BY ROOM
    |--------------------------------------------------------------------------
    */

    public function getEquipmentByRoom($roomId)
    {
        $equipment = ReportGrouping::enrichEquipmentWithOpenReports(
            ReportGrouping::applyReporterEquipmentFilters(
                DB::table('equipment_table')
                    ->where('equipment_room_id', $roomId)
            )
                ->orderBy('equipment_name')
                ->get(),
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
    // CHECK IF REPORTER EXISTS AND RETURN ACCOUNT STATUS
    // =====================================================

    public function getReporter($employeeId)
    {
        // =====================================================
        // FIND REPORTER HERE
        // =====================================================

        $reporter = DB::table('reporters_table')
            ->where(
                'reporter_employee_id',
                $employeeId
            )
            ->first();


        // =====================================================
        // REPORTER DOES NOT EXIST
        // =====================================================

        if (!$reporter) {
            $pending = ReporterApprovals::pendingByEmployeeId((string) $employeeId);

            if ($pending) {
                return response()->json([
                    'reporter_full_name' => $pending->full_name,
                    'reporter_status' => 'Pending Approval',
                ]);
            }

            return response()->json(null);

        }


        // =====================================================
        // RETURN REPORTER INFORMATION HERE
        // INCLUDING REPORTER STATUS
        // =====================================================

        return response()->json([

            'reporter_full_name'
                => $reporter->reporter_full_name,

            'reporter_status'
                => $reporter->reporter_status,

        ]);
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

    /*
    |--------------------------------------------------------------------------
    | START REPORTER REGISTRATION
    | Faculty / staff enter an email on the landing page. We send a
    | one-time form link so they can prove they own that inbox.
    |--------------------------------------------------------------------------
    */

    public function startRegistration(Request $request)
    {
        if (! Schema::hasTable('reporter_registration_invites')) {
            $message = 'Reporter registration is not available yet. Please try again after setup.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 503)
                : back()->withErrors(['email' => $message])->withInput();
        }

        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        $lock = $this->reporterRegistrationLock($email);

        if ($lock['blocked']) {
            return $this->reporterRegistrationLockedResponse($request, $lock['retry_after']);
        }

        RateLimiter::hit($this->reporterRegistrationAttemptKey($email), 5 * 60);
        $attempts = RateLimiter::attempts($this->reporterRegistrationAttemptKey($email));

        if ($attempts > 5) {
            $retryAfter = $this->lockReporterRegistration($email);

            return $this->reporterRegistrationLockedResponse($request, $retryAfter);
        }

        $justLocked = $attempts >= 5;
        $retryAfter = $justLocked ? $this->lockReporterRegistration($email) : 0;

        if (! $this->isAcceptableReporterEmail($email)) {
            $message = 'Use a real email you can open, such as Gmail or your work address.';
            $payload = $this->withRegistrationLock(['message' => $message], $justLocked, $retryAfter);

            return $request->expectsJson()
                ? response()->json($payload, 422)
                : back()->withErrors(['email' => $message])->withInput();
        }

        $existing = DB::table('reporters_table')
            ->whereRaw('LOWER(reporter_email_address) = ?', [$email])
            ->first();

            if ($existing) {
            $message = strtolower((string) $existing->reporter_status) === 'inactive'
                ? 'This email is already registered but inactive. Please contact maintenance personnel.'
                : 'This email is already registered. Use Make Report with your employee ID.';

            $payload = $this->withRegistrationLock([
                'message' => $message,
                'already_registered' => true,
            ], $justLocked, $retryAfter);

            return $request->expectsJson()
                ? response()->json($payload)
                : back()->with('success', $message)->with('success_title', 'Already registered');
        }

        $pending = ReporterApprovals::pendingByEmail($email);

        if ($pending) {
            $message = 'This email already has an application waiting for maintenance approval. You can submit reports after they confirm you are faculty or staff.';
            $payload = $this->withRegistrationLock([
                'message' => $message,
                'pending_approval' => true,
            ], $justLocked, $retryAfter);

            return $request->expectsJson()
                ? response()->json($payload)
                : back()->with('success', $message)->with('success_title', 'Waiting for approval');
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        DB::table('reporter_registration_invites')
            ->where('email', $email)
            ->whereNull('completed_at')
            ->delete();

        DB::table('reporter_registration_invites')->insert([
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
        ]);

        $appUrl = rtrim((string) config('app.url'), '/');
        URL::forceRootUrl($appUrl);
        $registerUrl = $appUrl.'/register-reporter/'.$plainToken;
        $mailSent = false;

        if ($this->mailerIsConfigured()) {
            try {
                Mail::send('emails.reporter-registration', [
                    'email' => $email,
                    'registerUrl' => $registerUrl,
                ], function ($message) use ($email) {
                    $message->to($email)->subject('Add your PaAyo reporter details');
                });
                $mailSent = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($mailSent) {
            $payload = [
                'message' => 'Check your inbox. We sent a registration form to '.$email.'.',
            ];
        } else {
            $payload = [
                'message' => 'We created your registration form. Open it to finish your profile. Email sending is not set up yet (add a Gmail App Password in .env to deliver the link to your inbox).',
                'register_url' => $registerUrl,
            ];
        }

        if ($justLocked) {
            $payload = $this->withRegistrationLock($payload, true, $retryAfter);
        }

        return $request->expectsJson()
            ? response()->json($payload)
            : back()->with('success', $payload['message'])
                ->with('success_title', $mailSent ? 'Check your email' : 'Continue registration')
                ->with('register_url', $payload['register_url'] ?? null);
    }

    public function showRegistrationForm(string $token)
    {
        $invite = $this->findValidInvite($token);

        if (! $invite) {
            return redirect('/')->with('success', 'That registration link is invalid or has expired. Enter your email again to get a new one.')
                ->with('success_title', 'Link expired');
        }

        return view('landing.register-reporter', [
            'token' => $token,
            'email' => $invite->email,
        ]);
    }

    public function completeRegistration(Request $request, string $token)
    {
        $invite = $this->findValidInvite($token);

        if (! $invite) {
            return redirect('/')->with('success', 'That registration link is invalid or has expired. Enter your email again to get a new one.')
                ->with('success_title', 'Link expired');
        }

        $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:Faculty,Staff'],
            'contact' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);

        $employeeId = trim($request->employee_id);

        $idTaken = DB::table('reporters_table')
            ->where('reporter_employee_id', $employeeId)
            ->exists();

        if ($idTaken) {
            return back()->withErrors(['employee_id' => 'That employee ID is already registered.'])->withInput();
        }

        $emailTaken = DB::table('reporters_table')
            ->whereRaw('LOWER(reporter_email_address) = ?', [strtolower($invite->email)])
            ->exists();

        if ($emailTaken) {
            return redirect('/')->with('success', 'This email is already registered. Use Make Report with your employee ID.')
                ->with('success_title', 'Already registered');
        }

        if (! ReporterApprovals::hasTable()) {
            return back()->withErrors(['employee_id' => 'Reporter approval is not available yet. Please try again after setup.'])->withInput();
        }

        $pendingEmail = ReporterApprovals::pendingByEmail($invite->email);

        if ($pendingEmail) {
            return redirect('/')->with('success', 'Your application is already waiting for maintenance approval. You can submit reports after they confirm you are faculty or staff.')
                ->with('success_title', 'Waiting for approval');
        }

        $pendingEmployeeId = ReporterApprovals::pendingByEmployeeId($employeeId);

        if ($pendingEmployeeId) {
            return back()->withErrors(['employee_id' => 'That employee ID already has an application waiting for approval.'])->withInput();
        }

        $first = trim($request->first_name);
        $middle = trim((string) $request->middle_name);
        $last = trim($request->last_name);

        ReporterApprovals::query()->insert([
            'employee_id' => $employeeId,
            'first_name' => $first,
            'middle_name' => $middle !== '' ? $middle : null,
            'last_name' => $last,
            'full_name' => ReporterImport::composeFullName($first, $middle, $last),
            'email' => $invite->email,
            'contact' => $request->contact,
            'employment_type' => $request->type,
            'status' => ReporterApprovals::STATUS_PENDING,
            'invite_id' => $invite->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reporter_registration_invites')
            ->where('id', $invite->id)
            ->update(['completed_at' => now()]);

        return redirect('/')->with('success', 'Your application was sent to maintenance personnel. You can submit reports with your employee ID after they confirm you are faculty or staff.')
            ->with('success_title', 'Waiting for approval');
    }

    protected function findValidInvite(string $token)
    {
        if (! Schema::hasTable('reporter_registration_invites')) {
            return null;
        }

        return DB::table('reporter_registration_invites')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('completed_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    protected function isAcceptableReporterEmail(string $email): bool
    {
        $blocked = [
            'mailinator.com',
            'tempmail.com',
            'temp-mail.org',
            '10minutemail.com',
            'guerrillamail.com',
            'trashmail.com',
            'yopmail.com',
            'sharklasers.com',
        ];

        $domain = strtolower((string) substr(strrchr($email, '@'), 1));

        return $domain !== '' && ! in_array($domain, $blocked, true);
    }

    protected function mailerIsConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return true;
        }

        return filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && filled(config('mail.from.address'));
    }

    protected function reporterRegistrationAttemptKey(string $email): string
    {
        return 'reporter-register-attempts:'.$email;
    }

    protected function reporterRegistrationBlockKey(string $email): string
    {
        return 'reporter-register-block:'.$email;
    }

    protected function reporterRegistrationLock(string $email): array
    {
        $blockKey = $this->reporterRegistrationBlockKey($email);

        if (RateLimiter::tooManyAttempts($blockKey, 1)) {
            return [
                'blocked' => true,
                'retry_after' => RateLimiter::availableIn($blockKey),
            ];
        }

        return ['blocked' => false, 'retry_after' => 0];
    }

    protected function lockReporterRegistration(string $email): int
    {
        $blockKey = $this->reporterRegistrationBlockKey($email);
        RateLimiter::clear($blockKey);
        RateLimiter::hit($blockKey, 15 * 60);

        return RateLimiter::availableIn($blockKey);
    }

    protected function reporterRegistrationLockMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return 'Too many attempts for this email. Submit is paused for '.$minutes.' minute'.($minutes === 1 ? '' : 's').'. Please try again later.';
    }

    protected function withRegistrationLock(array $payload, bool $justLocked, int $retryAfter): array
    {
        if (! $justLocked) {
            return $payload;
        }

        $payload['locked'] = true;
        $payload['retry_after'] = $retryAfter;
        $payload['lock_message'] = $this->reporterRegistrationLockMessage($retryAfter);

        return $payload;
    }

    protected function reporterRegistrationLockedResponse(Request $request, int $retryAfter)
    {
        $message = $this->reporterRegistrationLockMessage($retryAfter);
        $payload = [
            'message' => $message,
            'locked' => true,
            'retry_after' => $retryAfter,
            'lock_message' => $message,
        ];

        return $request->expectsJson()
            ? response()->json($payload, 429)
            : back()->withErrors(['email' => $message])->withInput();
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

