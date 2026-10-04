<?php

namespace App\Services;

use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\ReporterApprovals;
use App\Support\ReportSeverity;
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
            'report_equipment_manual_rooms' => 'nullable|array',
            'report_equipment_manual_rooms.*' => 'nullable|integer',
            'report_equipment_details' => 'nullable|array',
            'report_equipment_details.*' => 'nullable|string|max:2000',
            'report_equipment_manual_details' => 'nullable|array',
            'report_equipment_manual_details.*' => 'nullable|string|max:2000',
            'report_problem_description' => 'nullable|string',
            'report_suggested_issue' => 'nullable|string|max:255',
            'report_preferred_action_date' => ReportGrouping::preferredActionDateRules(),
            'report_uploaded_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'report_equipment_images' => 'nullable|array|max:'.\App\Support\RisWorkflow::MAX_ITEMS,
            'report_equipment_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'report_equipment_manual_images' => 'nullable|array|max:'.\App\Support\RisWorkflow::MAX_ITEMS,
            'report_equipment_manual_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'report_equipment_images.*.image' => 'Each equipment photo must be an image.',
            'report_equipment_images.*.max' => 'Each equipment photo must be 10 MB or smaller.',
            'report_equipment_manual_images.*.image' => 'Each equipment photo must be an image.',
            'report_equipment_manual_images.*.max' => 'Each equipment photo must be 10 MB or smaller.',
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
        $equipmentDetailsById = [];
        $rawEquipmentIds = collect($request->input('report_equipment_ids', []));
        $rawEquipmentIssues = collect($request->input('report_equipment_issues', []));
        $rawEquipmentDetails = collect($request->input('report_equipment_details', []));
        foreach ($rawEquipmentIds as $index => $rawId) {
            $equipmentId = (int) $rawId;
            if ($equipmentId <= 0) {
                continue;
            }
            $equipmentIssuesById[$equipmentId] = trim((string) ($rawEquipmentIssues[$index] ?? ''));
            $equipmentDetailsById[$equipmentId] = trim((string) ($rawEquipmentDetails[$index] ?? ''));
        }

        // Older clients send one description for the whole report instead of per item.
        $sharedDescription = trim((string) $request->report_problem_description);

        if ($request->filled('report_equipment_id') && $request->filled('report_suggested_issue')) {
            $equipmentIssuesById[(int) $request->report_equipment_id] = trim(
                (string) $request->report_suggested_issue
            );
        }

        // Keys of the non-empty manual names, in order; manual photos are keyed by these.
        $manualImageKeys = collect($request->input('report_equipment_manuals', []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->keys()
            ->values();

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

        $rawManualRooms = collect($request->input('report_equipment_manual_rooms', []));
        $manualRoomIds = $manualImageKeys
            ->map(fn ($rawKey) => (int) ($rawManualRooms[$rawKey] ?? 0) ?: (int) $request->report_room_id)
            ->values();
        while ($manualRoomIds->count() < $manualNames->count()) {
            $manualRoomIds->push((int) $request->report_room_id);
        }

        while ($manualIssues->count() < $manualNames->count()) {
            $manualIssues->push(trim((string) $request->report_suggested_issue));
        }

        $rawManualDetails = collect($request->input('report_equipment_manual_details', []));
        $manualDetails = $manualImageKeys
            ->map(fn ($rawKey) => trim((string) ($rawManualDetails[$rawKey] ?? '')))
            ->values();
        while ($manualDetails->count() < $manualNames->count()) {
            $manualDetails->push('');
        }

        if ($equipmentIds->isEmpty() && $manualNames->isEmpty()) {
            return $this->fail(
                'Please select at least one equipment or enter an equipment name.',
                ['report_equipment_id' => 'Please add at least one equipment.']
            );
        }

        $missingListedIssue = $equipmentIds->contains(
            fn ($equipmentId) => ($equipmentIssuesById[$equipmentId] ?? '') === ''
                && ($equipmentDetailsById[$equipmentId] ?? '') === ''
        );

        $missingManualIssue = $manualNames->keys()->contains(
            fn ($index) => trim((string) ($manualIssues[$index] ?? '')) === ''
                && ($manualDetails[$index] ?? '') === ''
        );

        if (
            ($equipmentIds->isNotEmpty() || $manualNames->isNotEmpty())
            && ($missingListedIssue || $missingManualIssue)
            && empty(trim((string) $request->report_suggested_issue))
            && $sharedDescription === ''
        ) {
            return $this->fail(
                'Please select a suggested issue or describe the problem for each equipment before submitting.',
                ['report_suggested_issue' => 'Please select a suggested issue or describe the problem for each equipment before submitting.']
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

        $validEquipment = collect();

        if ($equipmentIds->isNotEmpty()) {
            // One ticket can span rooms; each listed item is filed under the room it sits in.
            $validEquipment = DB::table('equipment_table')
                ->whereIn('equipment_id', $equipmentIds->all())
                ->whereNotNull('equipment_room_id')
                ->get()
                ->keyBy('equipment_id');

            if ($validEquipment->count() !== $equipmentIds->count()) {
                return $this->fail(
                    'One or more selected equipment could not be found in any room.',
                    ['report_equipment_id' => 'One or more selected equipment could not be found in any room.']
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

        $equipmentRoomIds = $validEquipment->map(fn ($equipment) => (int) $equipment->equipment_room_id)->all();

        $neededRoomIds = $manualRoomIds->take($manualNames->count())
            ->push((int) $request->report_room_id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        $knownRoomIds = DB::table('rooms_table')
            ->whereIn('room_id', $neededRoomIds->all())
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id);

        if ($neededRoomIds->diff($knownRoomIds)->isNotEmpty()) {
            return $this->fail(
                'Selected room not found.',
                ['report_room_id' => 'Selected room not found.']
            );
        }

        $storeImage = fn ($file) => $file instanceof \Illuminate\Http\UploadedFile && $file->isValid()
            ? $file->store('report-images', 'public')
            : null;

        // Older clients send one photo for the whole report; it fills items without their own.
        $sharedImagePath = $storeImage($request->file('report_uploaded_image'));

        $equipmentImagePaths = [];
        foreach ($equipmentIds as $equipmentId) {
            $equipmentImagePaths[$equipmentId] = $storeImage($request->file('report_equipment_images.'.$equipmentId))
                ?? $sharedImagePath;
        }

        $manualImagePaths = [];
        foreach ($manualNames->keys() as $position) {
            $rawKey = $manualImageKeys[$position] ?? null;
            $manualImagePaths[$position] = ($rawKey !== null
                ? $storeImage($request->file('report_equipment_manual_images.'.$rawKey))
                : null) ?? $sharedImagePath;
        }

        // An item with its own details but no picked issue is an "Other" problem, not the ticket's issue.
        $itemIssueFor = fn (string $issue, string $details) => $issue !== ''
            ? $issue
            : ($details !== '' ? '' : trim((string) $request->report_suggested_issue));

        $severityItems = [];
        $detailEntries = [];
        foreach ($equipmentIds as $equipmentId) {
            $details = $equipmentDetailsById[$equipmentId] ?? '';
            $severityItems[] = [
                'equipment_id' => (int) $equipmentId,
                'issue' => $itemIssueFor($equipmentIssuesById[$equipmentId] ?? '', $details),
                'room_id' => $equipmentRoomIds[$equipmentId],
            ];
            if ($details !== '') {
                $detailEntries[] = [(string) ($validEquipment->get($equipmentId)->equipment_name ?? 'Equipment'), $details];
            }
        }
        foreach ($manualNames as $index => $manualName) {
            $details = $manualDetails[$index] ?? '';
            $severityItems[] = [
                'name' => $manualName,
                'issue' => $itemIssueFor((string) ($manualIssues[$index] ?? ''), $details),
                'room_id' => $manualRoomIds[$index],
            ];
            if ($details !== '') {
                $detailEntries[] = [$manualName, $details];
            }
        }

        // Ticket-level summary of every item's details; procurement and RIS read this column.
        $itemTotal = $equipmentIds->count() + $manualNames->count();
        $ticketDescription = collect($detailEntries)
            ->map(fn ($entry) => $itemTotal > 1 ? $entry[0].': '.$entry[1] : $entry[1])
            ->prepend($sharedDescription)
            ->filter()
            ->implode("\n");

        $severity = ReportSeverity::assess($severityItems, $ticketDescription);

        $preferredDate = ReportGrouping::hasPreferredActionDateColumn()
            ? ReportGrouping::resolvePreferredActionDate(
                $severity['urgency'],
                $request->report_preferred_action_date
            )
            : null;

        [$alreadyReportedIds, $freshIds] = $equipmentIds->partition(
            fn ($equipmentId) => (bool) ReportGrouping::findOpenReport(
                (int) $equipmentId,
                $equipmentRoomIds[$equipmentId]
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
        $primaryIssue = trim((string) (
            $primaryIssue
            ?: $request->report_suggested_issue
            ?: collect($severityItems)->pluck('issue')->filter()->first()
        ));

        $imagePath = $newEquipmentIds->map(fn ($id) => $equipmentImagePaths[$id] ?? null)
            ->merge($manualImagePaths)
            ->filter()
            ->first();

        // The ticket's main room is the first item's; procurement and room views read it.
        $primaryRoomId = $primaryEquipmentId
            ? $equipmentRoomIds[$primaryEquipmentId]
            : ($manualRoomIds->first() ?: (int) $request->report_room_id);

        $insertData = [
            'report_reporter_employee_id' => $request->report_reporter_employee_id,
            'report_room_id' => $primaryRoomId,
            'report_equipment_id' => $primaryEquipmentId,
            'report_unlisted_equipment_name' => $primaryManual,
            'report_problem_description' => $ticketDescription !== '' ? $ticketDescription : null,
            'report_suggested_issue' => $primaryIssue !== '' ? $primaryIssue : null,
            'report_urgency_level' => $severity['urgency'],
            'report_current_status' => 'Pending',
            'report_uploaded_image' => $imagePath,
            'report_is_overdue' => false,
            'report_is_archived' => false,
            'report_submitted_at' => now(),
            'report_updated_at' => now(),
        ] + ReportSeverity::columnsFor($severity);

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
            $details = $equipmentDetailsById[$equipmentId] ?? '';
            $itemIssue = $itemIssueFor($equipmentIssuesById[$equipmentId] ?? '', $details);
            if ($itemIssue === '' && $details === '') {
                $itemIssue = $primaryIssue;
            }
            $itemDetails = $details !== '' ? $details : $sharedDescription;

            $itemPayloads[] = [
                'equipment_id' => (int) $equipmentId,
                'room_id' => $equipmentRoomIds[$equipmentId],
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $itemDetails !== '' ? $itemDetails : null,
                'uploaded_image' => $equipmentImagePaths[$equipmentId] ?? null,
            ];
        }

        foreach ($manualNames as $index => $manualName) {
            $details = $manualDetails[$index] ?? '';
            $itemIssue = $itemIssueFor((string) ($manualIssues[$index] ?? ''), $details);
            if ($itemIssue === '' && $details === '') {
                $itemIssue = $primaryIssue;
            }
            $itemDetails = $details !== '' ? $details : $sharedDescription;

            $itemPayloads[] = [
                'unlisted_name' => $manualName,
                'room_id' => $manualRoomIds[$index],
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $itemDetails !== '' ? $itemDetails : null,
                'uploaded_image' => $manualImagePaths[$index] ?? null,
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
            'severity' => $severity['level'],
            'severity_reason' => $severity['reason'],
            'urgency' => $severity['urgency'],
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
