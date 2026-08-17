<?php

namespace App\Http\Controllers;

use App\Support\ReportGrouping;
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
    /*
    |--------------------------------------------------------------------------
    | LANDING PAGE
    |--------------------------------------------------------------------------
    */

    public function index()
    {
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
            ->select(
                'rooms_table.*',
                'floors_table.floor_level',
                'buildings_table.building_name'
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
            'upcomingSchedules'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE REPORT
    |--------------------------------------------------------------------------
    */

    public function storeReport(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'report_reporter_employee_id' =>
                'required|string',

            'report_room_id' =>
                'required|integer',

            'report_equipment_id' =>
                'nullable|integer',

            'report_equipment_manual' =>
                'nullable|string|max:255',

            'report_problem_description' =>
                'nullable|string',

            'report_suggested_issue' =>
            'nullable|string|max:255',

            'report_urgency_level' =>
                'required|in:Urgent,Non-Urgent',

            'report_uploaded_image' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:10240'

        ]);

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT REQUIRED
        |--------------------------------------------------------------------------
        */

        if (

            empty($request->report_equipment_id)

            &&

            empty($request->report_equipment_manual)

        ) {

            return back()->with(

                'error',

                'Please select equipment or enter an equipment name.'

            );

        }

        /*
        |--------------------------------------------------------------------------
        | CHECK REPORTER EXISTENCE
        |--------------------------------------------------------------------------
        */

        $reporter = DB::table('reporters_table')

            ->where(

                'reporter_employee_id',

                $request->report_reporter_employee_id

            )

            ->first();

        /*
        |--------------------------------------------------------------------------
        | INVALID EMPLOYEE ID
        |--------------------------------------------------------------------------
        */

        if (!$reporter) {

            return back()->with(

                'error',

                'Employee ID not recognized.'

            );

        }

        /*
        |--------------------------------------------------------------------------
        | CHECK ROOM EXISTENCE
        |--------------------------------------------------------------------------
        */

        $room = DB::table('rooms_table')

            ->where(
                'room_id',
                $request->report_room_id
            )

            ->first();

        /*
        |--------------------------------------------------------------------------
        | INVALID ROOM
        |--------------------------------------------------------------------------
        */

        if (!$room) {

            return back()->with(

                'error',

                'Selected room not found.'

            );

        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EQUIPMENT EXISTENCE
        |--------------------------------------------------------------------------
        */

        $equipment = null;

        if($request->report_equipment_id){

            $equipment = DB::table('equipment_table')

                ->where(

                    'equipment_id',

                    $request->report_equipment_id

                )

                ->where(

                    'equipment_room_id',

                    $request->report_room_id

                )

                ->first();

            /*
            |--------------------------------------------------------------------------
            | INVALID EQUIPMENT
            |--------------------------------------------------------------------------
            */

            if (!$equipment) {

                return back()->with(

                    'error',

                    'Selected equipment does not belong to the selected room.'

                );

            }

            if (ReportGrouping::equipmentIsForReplacement((int) $equipment->equipment_id)) {
                return back()->with(
                    'error',
                    'This equipment is already marked for replacement and cannot be reported again.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        $imagePath = null;

        if ($request->hasFile('report_uploaded_image')) {

            $imagePath = $request

                ->file('report_uploaded_image')

                ->store(

                    'report-images',

                    'public'

                );
        }

        /*
        |--------------------------------------------------------------------------
        | MERGE INTO OPEN REPORT FOR THE SAME EQUIPMENT
        |--------------------------------------------------------------------------
        */

        if ($equipment) {
            $openReport = ReportGrouping::findOpenReport(
                (int) $equipment->equipment_id,
                (int) $request->report_room_id
            );

            if ($openReport) {
                ReportGrouping::mergeIntoOpenReport($openReport, [
                    'reporter_id' => $request->report_reporter_employee_id,
                    'urgency' => $request->report_urgency_level,
                    'issue' => $request->report_suggested_issue
                        ?: $request->report_problem_description,
                ]);

                return back()->with(
                    'success',
                    'This equipment already has an open report. Your report was added to it instead of creating a duplicate.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT REPORT
        |--------------------------------------------------------------------------
        */

        $insertData = [

                /*
                |--------------------------------------------------------------------------
                | REPORTER
                |--------------------------------------------------------------------------
                */

                'report_reporter_employee_id' =>

                    $request->report_reporter_employee_id,

                /*
                |--------------------------------------------------------------------------
                | ROOM
                |--------------------------------------------------------------------------
                */

                'report_room_id' =>

                    $request->report_room_id,

                /*
                |--------------------------------------------------------------------------
                | EQUIPMENT
                |--------------------------------------------------------------------------
                */

                'report_equipment_id' =>

                    $request->report_equipment_id,

                'report_unlisted_equipment_name' =>

                    $request->report_equipment_manual,

                /*
                |--------------------------------------------------------------------------
                | DESCRIPTION
                |--------------------------------------------------------------------------
                */

                'report_problem_description' =>

                    $request->report_problem_description,

                'report_suggested_issue' =>

                    $request->report_suggested_issue,

                /*
                |--------------------------------------------------------------------------
                | URGENCY
                |--------------------------------------------------------------------------
                */

                'report_urgency_level' =>

                    $request->report_urgency_level,

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                'report_current_status' =>

                    'Pending',

                /*
                |--------------------------------------------------------------------------
                | IMAGE
                |--------------------------------------------------------------------------
                */

                'report_uploaded_image' =>

                    $imagePath,

                /*
                |--------------------------------------------------------------------------
                | OVERDUE
                |--------------------------------------------------------------------------
                */

                'report_is_overdue' =>

                    false,

                /*
                |--------------------------------------------------------------------------
                | TIMESTAMPS
                |--------------------------------------------------------------------------
                */

                'report_submitted_at' =>

                    now(),

                'report_updated_at' =>

                    now()

            ];

        if (Schema::hasColumn('reports_table', 'report_related_count')) {
            $insertData['report_related_count'] = 1;
        }

        DB::table('reports_table')->insert($insertData);

        /*
        |--------------------------------------------------------------------------
        | SUCCESS MESSAGE
        |--------------------------------------------------------------------------
        */

        return back()->with(

            'success',

            'Maintenance report submitted successfully.'

        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET EQUIPMENT BY ROOM
    |--------------------------------------------------------------------------
    */

    public function getEquipmentByRoom($roomId)
    {
        $equipment = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
                ->where('equipment_room_id', $roomId)
        )
            ->orderBy('equipment_name')
            ->get();

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

        $first = trim($request->first_name);
        $middle = trim((string) $request->middle_name);
        $last = trim($request->last_name);

        $payload = [
            'reporter_employee_id' => $employeeId,
            'reporter_full_name' => ReporterImport::composeFullName($first, $middle, $last),
            'reporter_email_address' => $invite->email,
            'reporter_contact_number' => $request->contact,
            'reporter_status' => 'Active',
            'reporter_created_at' => now(),
        ];

        if (ReporterImport::hasNameColumns()) {
            $payload['reporter_first_name'] = $first;
            $payload['reporter_middle_name'] = $middle !== '' ? $middle : null;
            $payload['reporter_last_name'] = $last;
        }

        if (ReporterImport::hasTypeColumn()) {
            $payload['reporter_employment_type'] = $request->type;
        }

        DB::table('reporters_table')->insert($payload);

        DB::table('reporter_registration_invites')
            ->where('id', $invite->id)
            ->update(['completed_at' => now()]);

        return redirect('/')->with('success', 'Your details are saved. You can now submit a maintenance report with your employee ID. This is not a system account.')
            ->with('success_title', 'You can report now')
            ->with('open_report', true);
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
}

