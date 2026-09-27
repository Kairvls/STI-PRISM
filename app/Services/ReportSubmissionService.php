<?php

namespace App\Services;

use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\ReporterApprovals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportSubmissionService
{
    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     report_id?: int,
     *     merged?: bool,
     *     item_count?: int,
     *     errors?: array<string, string>,
     *     status?: int
     * }
     */
    public function submit(Request $request, ?int $loggedByUserId = null): array
    {
        $request->validate([
            'report_reporter_employee_id' => 'required|string',
            'report_room_id' => 'required|integer',
            'report_equipment_id' => 'nullable|integer',
            'report_equipment_ids' => 'nullable|array',
            'report_equipment_ids.*' => 'integer',
            'report_equipment_issues' => 'nullable|array',
            'report_equipment_issues.*' => 'nullable|string|max:255',
            'report_equipment_manual' => 'nullable|string|max:255',
            'report_equipment_manuals' => 'nullable|array',
            'report_equipment_manuals.*' => 'nullable|string|max:255',
            'report_equipment_manual_issues' => 'nullable|array',
            'report_equipment_manual_issues.*' => 'nullable|string|max:255',
            'report_problem_description' => 'nullable|string',
            'report_suggested_issue' => 'nullable|string|max:255',
            'report_urgency_level' => 'required|in:Urgent,Non-Urgent',
            'report_preferred_action_date' => ReportGrouping::preferredActionDateRules(),
            'report_uploaded_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $equipmentIds = collect($request->input('report_equipment_ids', []))
            ->when(
                $request->filled('report_equipment_id'),
                fn ($ids) => $ids->push($request->report_equipment_id)
            )
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $equipmentIssuesById = [];
        $rawEquipmentIds = collect($request->input('report_equipment_ids', []));
        $rawEquipmentIssues = collect($request->input('report_equipment_issues', []));
        foreach ($rawEquipmentIds as $index => $rawId) {
            $equipmentId = (int) $rawId;
            if ($equipmentId <= 0) {
                continue;
            }
            $equipmentIssuesById[$equipmentId] = trim((string) ($rawEquipmentIssues[$index] ?? ''));
        }

        if ($request->filled('report_equipment_id') && $request->filled('report_suggested_issue')) {
            $equipmentIssuesById[(int) $request->report_equipment_id] = trim(
                (string) $request->report_suggested_issue
            );
        }

        $manualNames = collect($request->input('report_equipment_manuals', []))
            ->when(
                $request->filled('report_equipment_manual'),
                fn ($names) => $names->push($request->report_equipment_manual)
            )
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        $manualIssues = collect($request->input('report_equipment_manual_issues', []))
            ->map(fn ($issue) => trim((string) $issue))
            ->values();

        while ($manualIssues->count() < $manualNames->count()) {
            $manualIssues->push(trim((string) $request->report_suggested_issue));
        }

        if ($equipmentIds->isEmpty() && $manualNames->isEmpty()) {
            return $this->fail(
                'Please select at least one equipment or enter an equipment name.',
                ['report_equipment_id' => 'Please add at least one equipment.']
            );
        }

        $missingListedIssue = $equipmentIds->contains(
            fn ($equipmentId) => trim((string) ($equipmentIssuesById[$equipmentId] ?? '')) === ''
        );

        $missingManualIssue = $manualNames->keys()->contains(
            fn ($index) => trim((string) ($manualIssues[$index] ?? '')) === ''
        );

        if (
            ($equipmentIds->isNotEmpty() || $manualNames->isNotEmpty())
            && ($missingListedIssue || $missingManualIssue)
            && empty(trim((string) $request->report_suggested_issue))
            && empty(trim((string) $request->report_problem_description))
        ) {
            return $this->fail(
                'Please select a suggested issue for each equipment before submitting.',
                ['report_suggested_issue' => 'Please select a suggested issue for each equipment before submitting.']
            );
        }

        $reporter = DB::table('reporters_table')
            ->where('reporter_employee_id', $request->report_reporter_employee_id)
            ->first();

        if (! $reporter) {
            $pending = ReporterApprovals::pendingByEmployeeId(
                (string) $request->report_reporter_employee_id
            );

            if ($pending) {
                return $this->fail(
                    'This reporter application is still waiting for maintenance approval. You can log reports after they are confirmed as faculty or staff.',
                    ['report_reporter_employee_id' => 'Reporter is still pending approval.']
                );
            }

            return $this->fail(
                'Employee ID not recognized.',
                ['report_reporter_employee_id' => 'Employee ID not recognized.']
            );
        }

        if (strtolower((string) $reporter->reporter_status) !== 'active') {
            return $this->fail(
                'This reporter account is inactive and cannot submit maintenance reports.',
                ['report_reporter_employee_id' => 'Reporter account is inactive.']
            );
        }

        $room = DB::table('rooms_table')
            ->where('room_id', $request->report_room_id)
            ->first();

        if (! $room) {
            return $this->fail(
                'Selected room not found.',
                ['report_room_id' => 'Selected room not found.']
            );
        }

        $validEquipment = collect();

        if ($equipmentIds->isNotEmpty()) {
            $validEquipment = DB::table('equipment_table')
                ->whereIn('equipment_id', $equipmentIds->all())
                ->where('equipment_room_id', $request->report_room_id)
                ->get()
                ->keyBy('equipment_id');

            if ($validEquipment->count() !== $equipmentIds->count()) {
                return $this->fail(
                    'One or more selected equipment do not belong to the selected room.',
                    ['report_equipment_id' => 'One or more selected equipment do not belong to the selected room.']
                );
            }

            foreach ($equipmentIds as $equipmentId) {
                if (ReportGrouping::equipmentIsForReplacement((int) $equipmentId)) {
                    $name = $validEquipment->get($equipmentId)->equipment_name ?? ('#'.$equipmentId);

                    return $this->fail(
                        $name.' is already marked for replacement and cannot be reported again.',
                        ['report_equipment_id' => $name.' is already marked for replacement and cannot be reported again.']
                    );
                }
            }
        }

        $imagePath = null;

        if ($request->hasFile('report_uploaded_image')) {
            $imagePath = $request
                ->file('report_uploaded_image')
                ->store('report-images', 'public');
        }

        $preferredDate = ReportGrouping::hasPreferredActionDateColumn()
            ? ReportGrouping::resolvePreferredActionDate(
                $request->report_urgency_level,
                $request->report_preferred_action_date
            )
            : null;

        [$alreadyReportedIds, $freshIds] = $equipmentIds->partition(
            fn ($equipmentId) => (bool) ReportGrouping::findOpenReport(
                (int) $equipmentId,
                (int) $request->report_room_id
            )
        );

        // Not-yet-reported equipment leads, so a mixed ticket gets its own list row
        // instead of stacking under an older open ticket.
        $newEquipmentIds = $freshIds->merge($alreadyReportedIds)->values();

        $primaryEquipmentId = $newEquipmentIds->first();
        $primaryManual = $primaryEquipmentId ? null : $manualNames->first();
        $primaryIssue = $primaryEquipmentId
            ? ($equipmentIssuesById[$primaryEquipmentId] ?? null)
            : ($manualIssues->first() ?: null);
        $primaryIssue = trim((string) ($primaryIssue ?: $request->report_suggested_issue));

        $insertData = [
            'report_reporter_employee_id' => $request->report_reporter_employee_id,
            'report_room_id' => $request->report_room_id,
            'report_equipment_id' => $primaryEquipmentId,
            'report_unlisted_equipment_name' => $primaryManual,
            'report_problem_description' => $request->report_problem_description,
            'report_suggested_issue' => $primaryIssue !== '' ? $primaryIssue : null,
            'report_urgency_level' => $request->report_urgency_level,
            'report_current_status' => 'Pending',
            'report_uploaded_image' => $imagePath,
            'report_is_overdue' => false,
            'report_is_archived' => false,
            'report_submitted_at' => now(),
            'report_updated_at' => now(),
        ];

        if (Schema::hasColumn('reports_table', 'report_related_count')) {
            $insertData['report_related_count'] = 1;
        }

        if (ReportGrouping::hasLastReportedColumn()) {
            $insertData['report_last_reported_at'] = $insertData['report_submitted_at'];
        }

        if (ReportGrouping::hasPreferredActionDateColumn()) {
            $insertData['report_preferred_action_date'] = $preferredDate;
        }

        if ($loggedByUserId !== null && ReportGrouping::hasLoggedByColumn()) {
            $insertData['report_logged_by'] = $loggedByUserId;
        }

        $reportId = (int) DB::table('reports_table')->insertGetId($insertData);

        $itemPayloads = [];

        foreach ($newEquipmentIds as $equipmentId) {
            $itemIssue = trim((string) ($equipmentIssuesById[$equipmentId] ?? ''));
            if ($itemIssue === '') {
                $itemIssue = $primaryIssue;
            }

            $itemPayloads[] = [
                'equipment_id' => (int) $equipmentId,
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $request->report_problem_description,
                'uploaded_image' => $imagePath,
            ];
        }

        foreach ($manualNames as $index => $manualName) {
            $itemIssue = trim((string) ($manualIssues[$index] ?? ''));
            if ($itemIssue === '') {
                $itemIssue = $primaryIssue;
            }

            $itemPayloads[] = [
                'unlisted_name' => $manualName,
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $request->report_problem_description,
                'uploaded_image' => $imagePath,
            ];
        }

        ReportItems::createForReport($reportId, $itemPayloads);

        $itemCount = count($itemPayloads);
        $submittedAt = now();
        $ticketCode = ReportGrouping::ticketCode($reportId, $submittedAt);
        $message = $itemCount > 1
            ? 'Maintenance report '.$ticketCode.' submitted successfully with '.$itemCount.' equipment items. It is now in Pending reports.'
            : 'Maintenance report '.$ticketCode.' submitted successfully. It is now in Pending reports.';

        $repeatCount = $alreadyReportedIds->count();
        if ($repeatCount > 0) {
            $message .= ' '.($repeatCount === 1 ? '1 equipment was' : $repeatCount.' equipment were')
                .' already reported and still waiting, so maintenance will see '
                .($repeatCount === 1 ? 'it' : 'them').' flagged as priority.';
        }

        return [
            'success' => true,
            'report_id' => $reportId,
            'ticket_code' => $ticketCode,
            'item_count' => $itemCount,
            'repeat_count' => $repeatCount,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, string>  $errors
     * @return array{success: false, message: string, errors: array<string, string>, status: int}
     */
    private function fail(string $message, array $errors = []): array
    {
        if ($errors === []) {
            $errors = ['general' => $message];
        }

        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status' => 422,
        ];
    }
}
