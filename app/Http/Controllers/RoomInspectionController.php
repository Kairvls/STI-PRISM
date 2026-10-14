<?php

namespace App\Http\Controllers;

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

        $base = DB::table(RoomInspections::SCHEDULES.' as s')
            ->join('rooms_table', 'rooms_table.room_id', '=', 's.room_inspection_schedule_room_id');

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
            );

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('rooms_table.room_name', 'like', '%'.$search.'%')
                    ->orWhere('rooms_table.room_type', 'like', '%'.$search.'%')
                    ->orWhere('s.room_inspection_schedule_title', 'like', '%'.$search.'%');
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

        $rooms = DB::table('rooms_table')
            ->when(Schema::hasColumn('rooms_table', 'room_is_archived'), fn ($q) => $q->where('room_is_archived', false))
            ->orderBy('room_name')
            ->get(['room_id', 'room_name', 'room_type'])
            ->map(fn ($room) => [
                'id' => (int) $room->room_id,
                'name' => (string) $room->room_name,
                'type' => (string) ($room->room_type ?? ''),
            ])
            ->values();

        return view('maintenance-personnel.maintenance-schedules.rooms', [
            'tablesMissing' => false,
            'schedules' => $schedules,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
            'rooms' => $rooms,
            'search' => $search,
            'status' => $status,
            'frequency' => $frequency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(RoomInspections::ready(), 503, 'Run php artisan migrate to enable room inspections.');

        $validated = $request->validate([
            'room_ids' => ['required', 'array', 'min:1', 'max:200'],
            'room_ids.*' => ['required', 'integer', 'exists:rooms_table,room_id'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'frequency' => ['required', Rule::in(RoomInspections::FREQUENCIES)],
            'next_date' => ['required', 'date'],
            'checklist' => ['nullable', 'string', 'max:5000'],
        ], [
            'room_ids.required' => 'Pick at least one room to inspect.',
        ]);

        $checklist = RoomInspections::parseChecklist($validated['checklist'] ?? null);
        $storedChecklist = $checklist === [] || $checklist === RoomInspections::DEFAULT_CHECKLIST
            ? null
            : json_encode($checklist);
        $roomIds = array_values(array_unique(array_map('intval', $validated['room_ids'])));
        $nextDate = Carbon::parse($validated['next_date'])->toDateString();

        DB::transaction(function () use ($request, $validated, $roomIds, $storedChecklist, $nextDate) {
            $lastId = 0;
            foreach ($roomIds as $roomId) {
                $lastId = DB::table(RoomInspections::SCHEDULES)->insertGetId([
                    'room_inspection_schedule_room_id' => $roomId,
                    'room_inspection_schedule_title' => $validated['title'],
                    'room_inspection_schedule_notes' => $validated['notes'] ?? null,
                    'room_inspection_schedule_checklist' => $storedChecklist,
                    'room_inspection_schedule_frequency' => $validated['frequency'],
                    'room_inspection_schedule_next_date' => $nextDate,
                    'room_inspection_schedule_status' => Carbon::parse($nextDate)->lt(today()) ? 'Overdue' : 'Active',
                    'room_inspection_schedule_created_by' => Auth::id(),
                    'room_inspection_schedule_created_at' => now(),
                    'room_inspection_schedule_updated_at' => now(),
                ]);
            }

            $names = DB::table('rooms_table')->whereIn('room_id', $roomIds)->orderBy('room_name')->pluck('room_name');
            $this->audit(
                $request,
                'Created room inspection schedule',
                $lastId,
                'Scheduled "'.$validated['title'].'" ('.$validated['frequency'].') for '.$names->implode(', ').'.'
            );
        });

        $count = count($roomIds);

        return back()->with('success', $count === 1
            ? 'Room inspection scheduled.'
            : "Scheduled inspections for {$count} rooms.");
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
