<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\RoomInspections;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Mobile room inspections — list due rooms and record checklist results
 * (parity with web RoomInspectionController for maintenance personnel).
 */
class MobileRoomInspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! RoomInspections::ready()) {
            return response()->json([
                'success' => true,
                'schedules' => [],
                'message' => 'Room inspection tables are not ready.',
            ]);
        }

        RoomInspections::refreshOverdue();

        $hasDepartmentColumn = Schema::hasColumn(
            RoomInspections::SCHEDULES,
            'room_inspection_schedule_department_id'
        );
        $hasDepartmentsTable = Schema::hasTable('departments_table');

        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $limit = min(max((int) $request->query('limit', 100), 1), 200);

        $query = DB::table(RoomInspections::SCHEDULES.' as s')
            ->join('rooms_table', 'rooms_table.room_id', '=', 's.room_inspection_schedule_room_id')
            ->when($hasDepartmentColumn && $hasDepartmentsTable, fn ($q) => $q->leftJoin(
                'departments_table as dept',
                'dept.department_id',
                '=',
                's.room_inspection_schedule_department_id'
            ))
            ->select(
                's.*',
                'rooms_table.room_name',
                'rooms_table.room_type'
            )
            ->when($hasDepartmentColumn && $hasDepartmentsTable, fn ($q) => $q->addSelect('dept.department_name'));

        if ($status === 'due_week') {
            $query->where('s.room_inspection_schedule_status', '!=', 'Completed')
                ->whereBetween('s.room_inspection_schedule_next_date', [
                    today()->toDateString(),
                    today()->addDays(7)->toDateString(),
                ]);
        } elseif (in_array($status, ['Active', 'Overdue', 'Completed'], true)) {
            $query->where('s.room_inspection_schedule_status', $status);
        } else {
            $query->where('s.room_inspection_schedule_status', '!=', 'Completed');
        }

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

        $schedules = $query
            ->orderByRaw("CASE s.room_inspection_schedule_status WHEN 'Overdue' THEN 0 WHEN 'Active' THEN 1 ELSE 2 END")
            ->orderBy('s.room_inspection_schedule_next_date')
            ->orderBy('rooms_table.room_name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->serializeSchedule($row))
            ->values();

        return response()->json([
            'success' => true,
            'schedules' => $schedules,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        if (! RoomInspections::ready()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $schedule = $this->findSchedule($id);
        if (! $schedule) {
            return response()->json(['message' => 'Schedule not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'schedule' => $this->serializeSchedule($schedule, withChecklist: true),
            'results' => RoomInspections::RESULTS,
            'check_statuses' => [
                RoomInspections::CHECK_PASS,
                RoomInspections::CHECK_ISSUE,
                RoomInspections::CHECK_NA,
            ],
        ]);
    }

    public function inspect(Request $request, int $id): JsonResponse
    {
        if (! RoomInspections::ready()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $schedule = $this->findSchedule($id);
        if (! $schedule) {
            return response()->json(['message' => 'Schedule not found.'], 404);
        }

        if ($schedule->room_inspection_schedule_status === 'Completed') {
            return response()->json([
                'success' => false,
                'message' => 'This one-time inspection is already done.',
            ], 422);
        }

        $checklist = RoomInspections::checklistFor($schedule);
        $validated = $request->validate([
            'checks' => ['nullable', 'array'],
            'checks.*' => ['nullable', Rule::in([
                RoomInspections::CHECK_PASS,
                RoomInspections::CHECK_ISSUE,
                RoomInspections::CHECK_NA,
            ])],
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
        $nextDate = RoomInspections::nextDate(
            (string) $schedule->room_inspection_schedule_frequency,
            $scheduledFor
        );

        DB::transaction(function () use (
            $request,
            $schedule,
            $validated,
            $checks,
            $issueCount,
            $proof,
            $scheduledFor,
            $nextDate
        ) {
            DB::table(RoomInspections::LOGS)->insert([
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

            if (Schema::hasTable('audit_logs_table')) {
                DB::table('audit_logs_table')->insert([
                    'audit_log_user_id' => Auth::id(),
                    'audit_log_action' => 'Completed room inspection',
                    'audit_log_module' => 'Schedules',
                    'audit_log_table_name' => RoomInspections::SCHEDULES,
                    'audit_log_reference_id' => $schedule->room_inspection_schedule_id,
                    'audit_log_description' => 'Mobile inspected '.$schedule->room_name
                        .' ("'.$schedule->room_inspection_schedule_title.'"): '
                        .$validated['result']
                        .($issueCount > 0 ? ", {$issueCount} issue(s)" : '').'.',
                    'audit_log_ip_address' => $request->ip(),
                    'audit_log_created_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $nextDate
                ? 'Inspection recorded. Next is '.Carbon::parse($nextDate)->format('M j, Y').'.'
                : 'Inspection recorded.',
            'next_date' => $nextDate,
        ]);
    }

    private function findSchedule(int $id): ?object
    {
        $hasDepartmentColumn = Schema::hasColumn(
            RoomInspections::SCHEDULES,
            'room_inspection_schedule_department_id'
        );
        $hasDepartmentsTable = Schema::hasTable('departments_table');

        return DB::table(RoomInspections::SCHEDULES.' as s')
            ->join('rooms_table', 'rooms_table.room_id', '=', 's.room_inspection_schedule_room_id')
            ->when($hasDepartmentColumn && $hasDepartmentsTable, fn ($q) => $q->leftJoin(
                'departments_table as dept',
                'dept.department_id',
                '=',
                's.room_inspection_schedule_department_id'
            ))
            ->where('s.room_inspection_schedule_id', $id)
            ->select('s.*', 'rooms_table.room_name', 'rooms_table.room_type')
            ->when($hasDepartmentColumn && $hasDepartmentsTable, fn ($q) => $q->addSelect('dept.department_name'))
            ->first();
    }

    private function serializeSchedule(object $row, bool $withChecklist = false): array
    {
        $payload = [
            'id' => (int) $row->room_inspection_schedule_id,
            'title' => (string) $row->room_inspection_schedule_title,
            'room_id' => (int) $row->room_inspection_schedule_room_id,
            'room_name' => (string) ($row->room_name ?? ''),
            'room_type' => (string) ($row->room_type ?? ''),
            'department' => (string) ($row->department_name ?? ''),
            'frequency' => (string) $row->room_inspection_schedule_frequency,
            'status' => (string) $row->room_inspection_schedule_status,
            'next_date' => $row->room_inspection_schedule_next_date,
            'last_date' => $row->room_inspection_schedule_last_date,
            'notes' => $row->room_inspection_schedule_notes,
        ];

        if ($withChecklist) {
            $payload['checklist'] = RoomInspections::checklistFor($row);
        }

        return $payload;
    }
}
