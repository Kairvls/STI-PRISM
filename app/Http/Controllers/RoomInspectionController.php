<?php

namespace App\Http\Controllers;

use App\Support\Departments;
use App\Support\RoomDepartments;
use App\Support\RoomInspections;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomInspectionController extends Controller
{
    public function index(Request $request): View
    {
        if (! RoomInspections::ready()) {
            return view('maintenance-personnel.maintenance-schedules.rooms', ['tablesMissing' => true]);
        }

        RoomInspections::refreshOverdue();

        $hasDepartmentColumn = Schema::hasColumn(RoomInspections::SCHEDULES, 'room_inspection_schedule_department_id');
        $hasDepartmentsTable = Schema::hasTable('departments_table');

        $base = DB::table(RoomInspections::SCHEDULES.' as s')
            ->join('rooms_table', 'rooms_table.room_id', '=', 's.room_inspection_schedule_room_id')
            ->when($hasDepartmentColumn && $hasDepartmentsTable, fn ($q) => $q->leftJoin(
                'departments_table as dept',
                'dept.department_id',
                '=',
                's.room_inspection_schedule_department_id'
            ));

        $query = (clone $base)
            ->leftJoin(RoomInspections::LOGS.' as last_log', function ($join) {
                $join->on('last_log.room_inspection_log_id', '=', DB::raw(
                    '(SELECT MAX(l2.room_inspection_log_id) FROM '.RoomInspections::LOGS.' l2 WHERE l2.room_inspection_log_schedule_id = s.room_inspection_schedule_id)'
                ));
            })
            ->select(
                's.*',
                'rooms_table.room_name',
                'rooms_table.room_type',
                'last_log.room_inspection_log_result as last_result',
                'last_log.room_inspection_log_issue_count as last_issue_count'
            )
            ->when($hasDepartmentColumn && $hasDepartmentsTable, fn ($q) => $q->addSelect('dept.department_name'));

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search, $hasDepartmentColumn, $hasDepartmentsTable) {
                $q->where('rooms_table.room_name', 'like', '%'.$search.'%')
                    ->orWhere('rooms_table.room_type', 'like', '%'.$search.'%')
                    ->orWhere('s.room_inspection_schedule_title', 'like', '%'.$search.'%');
                if ($hasDepartmentColumn && $hasDepartmentsTable) {
                    $q->orWhere('dept.department_name', 'like', '%'.$search.'%');
                }
            });
        }

        $status = (string) $request->query('status', '');
        if ($status === 'due_week') {
            $query->where('s.room_inspection_schedule_status', '!=', 'Completed')
                ->whereBetween('s.room_inspection_schedule_next_date', [today()->toDateString(), today()->addDays(7)->toDateString()]);
        } elseif (in_array($status, ['Active', 'Overdue', 'Completed'], true)) {
            $query->where('s.room_inspection_schedule_status', $status);
        }

        $frequency = (string) $request->query('frequency', '');
        if (in_array($frequency, RoomInspections::FREQUENCIES, true)) {
            $query->where('s.room_inspection_schedule_frequency', $frequency);
        }

        $schedules = $query
            ->orderByRaw("CASE s.room_inspection_schedule_status WHEN 'Overdue' THEN 0 WHEN 'Active' THEN 1 ELSE 2 END")
            ->orderBy('s.room_inspection_schedule_next_date')
            ->orderBy('rooms_table.room_name')
            ->paginate(12)
            ->withQueryString();

        $schedules->getCollection()->transform(function ($row) {
            $row->checklist = RoomInspections::checklistFor($row);

            return $row;
        });

        $stats = [
            'active' => (clone $base)->where('s.room_inspection_schedule_status', '!=', 'Completed')->count(),
            'dueWeek' => (clone $base)
                ->where('s.room_inspection_schedule_status', '!=', 'Completed')
                ->whereBetween('s.room_inspection_schedule_next_date', [today()->toDateString(), today()->addDays(7)->toDateString()])
                ->count(),
            'overdue' => (clone $base)->where('s.room_inspection_schedule_status', 'Overdue')->count(),
            'inspectedMonth' => DB::table(RoomInspections::LOGS)
                ->where('room_inspection_log_inspected_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $recentLogs = $this->logsQuery()
            ->orderByDesc('l.room_inspection_log_inspected_at')
            ->limit(8)
            ->get();

        $rooms = $this->roomsWithDepartments();

        return view('maintenance-personnel.maintenance-schedules.rooms', [
            'tablesMissing' => false,
            'schedules' => $schedules,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
            'rooms' => $rooms,
            'frequencies' => RoomInspections::FREQUENCIES,
            'defaultNextDate' => today()->toDateString(),
            'search' => $search,
            'status' => $status,
            'frequency' => $frequency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(RoomInspections::ready(), 503, 'Run php artisan migrate to enable room inspections.');

        $hasDepartmentColumn = Schema::hasColumn(RoomInspections::SCHEDULES, 'room_inspection_schedule_department_id');

        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.room_id' => ['required', 'integer', 'exists:rooms_table,room_id'],
            'lines.*.department_ids' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.department_ids.*' => ['integer', 'min:0'],
            'lines.*.frequency' => ['required', Rule::in(RoomInspections::FREQUENCIES)],
            'lines.*.next_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'checklist' => ['nullable', 'string', 'max:5000'],
        ], [
            'lines.required' => 'Pick at least one room and department to inspect.',
            'lines.*.department_ids.required' => 'Pick at least one department for each selected room.',
        ]);

        $departmentIds = collect($validated['lines'])
            ->flatMap(fn ($line) => $line['department_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($departmentIds->isNotEmpty()) {
            $existing = DB::table('departments_table')
                ->whereIn('department_id', $departmentIds->all())
                ->pluck('department_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $missing = $departmentIds->diff($existing);
            if ($missing->isNotEmpty()) {
                return back()->withInput()->withErrors([
                    'lines' => 'One or more selected departments are no longer available.',
                ]);
            }
        }

        $checklist = RoomInspections::parseChecklist($validated['checklist'] ?? null);
        $storedChecklist = $checklist === [] || $checklist === RoomInspections::DEFAULT_CHECKLIST
            ? null
            : json_encode($checklist);

        $departmentNames = $departmentIds->isNotEmpty()
            ? DB::table('departments_table')->whereIn('department_id', $departmentIds->all())->pluck('department_name', 'department_id')
            : collect();

        $created = 0;

        DB::transaction(function () use ($request, $validated, $storedChecklist, $hasDepartmentColumn, $departmentNames, &$created) {
            $lastId = 0;

            foreach ($validated['lines'] as $line) {
                $roomId = (int) $line['room_id'];
                $nextDate = Carbon::parse($line['next_date'])->toDateString();
                $frequency = $line['frequency'];
                $deptIds = collect($line['department_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                foreach ($deptIds as $departmentId) {
                    $deptName = $departmentId > 0
                        ? (string) ($departmentNames[$departmentId] ?? 'Department')
                        : null;
                    $title = $validated['title'];
                    if ($deptName) {
                        $suffix = ' — '.$deptName;
                        if (! str_ends_with($title, $suffix) && mb_strlen($title) + mb_strlen($suffix) <= 255) {
                            $title .= $suffix;
                        }
                    }

                    $payload = [
                        'room_inspection_schedule_room_id' => $roomId,
                        'room_inspection_schedule_title' => $title,
                        'room_inspection_schedule_notes' => $validated['notes'] ?? null,
                        'room_inspection_schedule_checklist' => $storedChecklist,
                        'room_inspection_schedule_frequency' => $frequency,
                        'room_inspection_schedule_next_date' => $nextDate,
                        'room_inspection_schedule_status' => Carbon::parse($nextDate)->lt(today()) ? 'Overdue' : 'Active',
                        'room_inspection_schedule_created_by' => Auth::id(),
                        'room_inspection_schedule_created_at' => now(),
                        'room_inspection_schedule_updated_at' => now(),
                    ];

                    if ($hasDepartmentColumn) {
                        $payload['room_inspection_schedule_department_id'] = $departmentId > 0 ? $departmentId : null;
                    }

                    $lastId = DB::table(RoomInspections::SCHEDULES)->insertGetId($payload);
                    $created++;
                }
            }

            $roomIds = collect($validated['lines'])->pluck('room_id')->map(fn ($id) => (int) $id)->unique()->all();
            $names = DB::table('rooms_table')->whereIn('room_id', $roomIds)->orderBy('room_name')->pluck('room_name');
            $this->audit(
                $request,
                'Created room inspection schedule',
                $lastId,
                'Scheduled "'.$validated['title'].'" for '.$names->implode(', ').' ('.$created.' line'.($created === 1 ? '' : 's').').'
            );
        });

        return back()->with('success', $created === 1
            ? 'Room inspection scheduled.'
            : "Scheduled {$created} room inspections.");
    }

    public function inspect(Request $request, int $id): RedirectResponse
    {
        $schedule = $this->findSchedule($id);
        if ($schedule->room_inspection_schedule_status === 'Completed') {
            return back()->with('error', 'This one-time inspection is already done.');
        }

        $checklist = RoomInspections::checklistFor($schedule);
        $validated = $request->validate([
            'checks' => ['nullable', 'array'],
            'checks.*' => ['nullable', Rule::in([RoomInspections::CHECK_PASS, RoomInspections::CHECK_ISSUE, RoomInspections::CHECK_NA])],
            'result' => ['required', Rule::in(RoomInspections::RESULTS)],
            'findings' => [
                Rule::requiredIf(fn () => $request->input('result') !== RoomInspections::RESULT_GOOD
                    || in_array(RoomInspections::CHECK_ISSUE, (array) $request->input('checks', []), true)),
                'nullable',
                'string',
                'max:5000',
            ],
            'action_taken' => ['nullable', 'string', 'max:5000'],
            'proof_image' => ['nullable', 'image', 'max:4096'],
        ], [
            'findings.required' => 'Describe what needs attention in the findings.',
        ]);

        $checks = [];
        foreach ($checklist as $index => $label) {
            $checks[] = [
                'item' => $label,
                'status' => $validated['checks'][$index] ?? RoomInspections::CHECK_PASS,
            ];
        }
        $issueCount = collect($checks)->where('status', RoomInspections::CHECK_ISSUE)->count();

        $proof = $request->hasFile('proof_image')
            ? $request->file('proof_image')->store('room-inspections', 'public')
            : null;

        $scheduledFor = $schedule->room_inspection_schedule_next_date;
        $nextDate = RoomInspections::nextDate($schedule->room_inspection_schedule_frequency, $scheduledFor);

        DB::transaction(function () use ($request, $schedule, $validated, $checks, $issueCount, $proof, $scheduledFor, $nextDate) {
            $logId = DB::table(RoomInspections::LOGS)->insertGetId([
                'room_inspection_log_schedule_id' => $schedule->room_inspection_schedule_id,
                'room_inspection_log_room_id' => $schedule->room_inspection_schedule_room_id,
                'room_inspection_log_title' => $schedule->room_inspection_schedule_title,
                'room_inspection_log_result' => $validated['result'],
                'room_inspection_log_checklist' => json_encode($checks),
                'room_inspection_log_issue_count' => $issueCount,
                'room_inspection_log_findings' => $validated['findings'] ?? null,
                'room_inspection_log_action_taken' => $validated['action_taken'] ?? null,
                'room_inspection_log_proof_image' => $proof,
                'room_inspection_log_scheduled_for' => $scheduledFor,
                'room_inspection_log_inspected_by' => Auth::id(),
                'room_inspection_log_inspected_at' => now(),
            ]);

            DB::table(RoomInspections::SCHEDULES)
                ->where('room_inspection_schedule_id', $schedule->room_inspection_schedule_id)
                ->update([
                    'room_inspection_schedule_last_date' => now(),
                    'room_inspection_schedule_next_date' => $nextDate ?? $scheduledFor,
                    'room_inspection_schedule_status' => $nextDate ? 'Active' : 'Completed',
                    'room_inspection_schedule_updated_at' => now(),
                ]);

            $this->audit(
                $request,
                'Completed room inspection',
                $logId,
                'Inspected '.$schedule->room_name.' ("'.$schedule->room_inspection_schedule_title.'"): '
                    .$validated['result'].($issueCount > 0 ? ", {$issueCount} issue(s)" : '').'.'
            );
        });

        return back()->with('success', $nextDate
            ? 'Inspection recorded. Next inspection is on '.Carbon::parse($nextDate)->format('M j, Y').'.'
            : 'Inspection recorded.');
    }

    public function reschedule(Request $request, int $id): RedirectResponse
    {
        $schedule = $this->findSchedule($id);
        $validated = $request->validate([
            'new_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $newDate = Carbon::parse($validated['new_date'])->toDateString();
        $notes = trim((string) $schedule->room_inspection_schedule_notes);
        if (filled($validated['reason'] ?? null)) {
            $notes = trim($notes."\n\nRescheduled to ".Carbon::parse($newDate)->format('M j, Y').': '.$validated['reason']);
        }

        DB::table(RoomInspections::SCHEDULES)
            ->where('room_inspection_schedule_id', $id)
            ->update([
                'room_inspection_schedule_next_date' => $newDate,
                'room_inspection_schedule_status' => Carbon::parse($newDate)->lt(today()) ? 'Overdue' : 'Active',
                'room_inspection_schedule_notes' => $notes !== '' ? $notes : null,
                'room_inspection_schedule_updated_at' => now(),
            ]);

        $this->audit($request, 'Rescheduled room inspection', $id, 'Moved "'.$schedule->room_inspection_schedule_title.'" for '.$schedule->room_name.' to '.$newDate.'.');

        return back()->with('success', 'Room inspection rescheduled.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $schedule = $this->findSchedule($id);

        DB::table(RoomInspections::SCHEDULES)->where('room_inspection_schedule_id', $id)->delete();

        $this->audit($request, 'Deleted room inspection schedule', $id, 'Deleted "'.$schedule->room_inspection_schedule_title.'" for '.$schedule->room_name.'. Past inspection records were kept.');

        return back()->with('success', 'Room inspection schedule deleted. Past inspection records were kept.');
    }

    public function history(int $id): JsonResponse
    {
        $schedule = $this->findSchedule($id);

        $logs = $this->logsQuery()
            ->where('l.room_inspection_log_schedule_id', $id)
            ->orderByDesc('l.room_inspection_log_inspected_at')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'result' => $log->room_inspection_log_result,
                'tone' => RoomInspections::resultTone($log->room_inspection_log_result),
                'issues' => (int) $log->room_inspection_log_issue_count,
                'checklist' => json_decode((string) $log->room_inspection_log_checklist, true) ?: [],
                'findings' => $log->room_inspection_log_findings,
                'action_taken' => $log->room_inspection_log_action_taken,
                'proof_url' => $log->room_inspection_log_proof_image ? asset('storage/'.$log->room_inspection_log_proof_image) : null,
                'inspected_by' => $log->inspector_name ?: 'Unknown',
                'inspected_at' => $log->room_inspection_log_inspected_at
                    ? Carbon::parse($log->room_inspection_log_inspected_at)->format('M j, Y g:i A')
                    : '',
            ]);

        return response()->json([
            'room' => $schedule->room_name,
            'title' => $schedule->room_inspection_schedule_title,
            'logs' => $logs,
        ]);
    }

    /**
     * Rooms for the create picker. Under each room, only departments that
     * actually belong there (equipment charged to the department in that room,
     * or people of that department stationed in that room). Never lists every
     * campus department. Department id 0 means “entire room”.
     *
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,type:string,departments:list<array{id:int,name:string,equipment_count:int}>}>
     */
    private function roomsWithDepartments()
    {
        $departmentNames = Departments::options()
            ->mapWithKeys(fn ($dept) => [(int) $dept->department_id => (string) $dept->department_name]);

        /** @var array<int, array<int, int>> $countsByRoom department_id => equipment_count */
        $countsByRoom = [];
        /** @var array<int, array<int, true>> $peopleByRoom department_id => true */
        $peopleByRoom = [];

        if (
            Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_department_id')
            && Schema::hasColumn('equipment_table', 'equipment_room_id')
        ) {
            $rows = DB::table('equipment_table')
                ->whereNotNull('equipment_room_id')
                ->whereNotNull('equipment_department_id')
                ->where('equipment_inventory_status', '!=', 'Disposed')
                ->groupBy('equipment_room_id', 'equipment_department_id')
                ->select(
                    'equipment_room_id',
                    'equipment_department_id',
                    DB::raw('COUNT(*) as equipment_count')
                )
                ->get();

            foreach ($rows as $row) {
                $countsByRoom[(int) $row->equipment_room_id][(int) $row->equipment_department_id] = (int) $row->equipment_count;
            }
        }

        if (
            Schema::hasTable('custodians_table')
            && Schema::hasColumn('custodians_table', 'custodian_room_id')
            && Schema::hasColumn('custodians_table', 'custodian_department_id')
        ) {
            $rows = DB::table('custodians_table')
                ->whereNotNull('custodian_room_id')
                ->whereNotNull('custodian_department_id')
                ->groupBy('custodian_room_id', 'custodian_department_id')
                ->select('custodian_room_id', 'custodian_department_id')
                ->get();

            foreach ($rows as $row) {
                $peopleByRoom[(int) $row->custodian_room_id][(int) $row->custodian_department_id] = true;
            }
        }

        // Persons directory can own department while the custodian row holds the room.
        if (
            Schema::hasTable('persons_table')
            && Schema::hasColumn('persons_table', 'person_department_id')
            && Schema::hasTable('custodians_table')
            && Schema::hasColumn('custodians_table', 'custodian_room_id')
            && Schema::hasColumn('custodians_table', 'person_id')
        ) {
            $rows = DB::table('custodians_table as c')
                ->join('persons_table as p', 'p.person_id', '=', 'c.person_id')
                ->whereNotNull('c.custodian_room_id')
                ->whereNotNull('p.person_department_id')
                ->groupBy('c.custodian_room_id', 'p.person_department_id')
                ->select('c.custodian_room_id', 'p.person_department_id')
                ->get();

            foreach ($rows as $row) {
                $peopleByRoom[(int) $row->custodian_room_id][(int) $row->person_department_id] = true;
            }
        }

        $assignedByRoom = RoomDepartments::idsByRoom();

        return DB::table('rooms_table')
            ->when(Schema::hasColumn('rooms_table', 'room_is_archived'), fn ($q) => $q->where('room_is_archived', false))
            ->orderBy('room_name')
            ->get(['room_id', 'room_name', 'room_type'])
            ->map(function ($room) use ($departmentNames, $countsByRoom, $peopleByRoom, $assignedByRoom) {
                $roomId = (int) $room->room_id;
                $roomName = (string) $room->room_name;
                $roomNameLower = mb_strtolower($roomName);
                $equipmentCounts = $countsByRoom[$roomId] ?? [];
                $peopleIds = array_keys($peopleByRoom[$roomId] ?? []);
                $assignedIds = $assignedByRoom[$roomId] ?? [];

                // Prefer explicit room assignments; still include living equipment/people links.
                $linkedIds = array_values(array_unique(array_filter(array_merge(
                    $assignedIds,
                    array_keys($equipmentCounts),
                    $peopleIds
                ))));

                // Office-style rooms: “Admission Office” ↔ department “Admission”.
                foreach ($departmentNames as $deptId => $deptName) {
                    $deptLower = mb_strtolower($deptName);
                    if ($deptLower === '' || in_array($deptId, $linkedIds, true)) {
                        continue;
                    }
                    if (
                        str_contains($roomNameLower, $deptLower)
                        || str_contains($deptLower, $roomNameLower)
                    ) {
                        $linkedIds[] = $deptId;
                    }
                }

                sort($linkedIds);

                $departments = [
                    [
                        'id' => 0,
                        'name' => 'Entire room',
                        'equipment_count' => 0,
                    ],
                ];

                foreach ($linkedIds as $deptId) {
                    $name = $departmentNames[$deptId] ?? null;
                    if (! $name) {
                        continue;
                    }
                    $departments[] = [
                        'id' => $deptId,
                        'name' => $name,
                        'equipment_count' => (int) ($equipmentCounts[$deptId] ?? 0),
                    ];
                }

                return [
                    'id' => $roomId,
                    'name' => $roomName,
                    'type' => (string) ($room->room_type ?? ''),
                    'departments' => $departments,
                ];
            })
            ->values();
    }

    private function findSchedule(int $id): object
    {
        abort_unless(RoomInspections::ready(), 404);

        $schedule = DB::table(RoomInspections::SCHEDULES.' as s')
            ->join('rooms_table', 'rooms_table.room_id', '=', 's.room_inspection_schedule_room_id')
            ->where('s.room_inspection_schedule_id', $id)
            ->select('s.*', 'rooms_table.room_name')
            ->first();

        abort_unless($schedule, 404);

        return $schedule;
    }

    private function logsQuery()
    {
        return DB::table(RoomInspections::LOGS.' as l')
            ->join('rooms_table', 'rooms_table.room_id', '=', 'l.room_inspection_log_room_id')
            ->leftJoin(RoomInspections::SCHEDULES.' as s', 's.room_inspection_schedule_id', '=', 'l.room_inspection_log_schedule_id')
            ->leftJoin('users_table', 'users_table.user_id', '=', 'l.room_inspection_log_inspected_by')
            ->select(
                'l.*',
                'rooms_table.room_name',
                DB::raw('COALESCE(l.room_inspection_log_title, s.room_inspection_schedule_title) as inspection_title'),
                'users_table.user_full_name as inspector_name'
            );
    }

    private function audit(Request $request, string $action, int $referenceId, string $description): void
    {
        DB::table('audit_logs_table')->insert([
            'audit_log_user_id' => Auth::id(),
            'audit_log_action' => $action,
            'audit_log_module' => 'Schedules',
            'audit_log_table_name' => RoomInspections::SCHEDULES,
            'audit_log_reference_id' => $referenceId,
            'audit_log_description' => $description,
            'audit_log_ip_address' => $request->ip(),
            'audit_log_created_at' => now(),
        ]);
    }
}
