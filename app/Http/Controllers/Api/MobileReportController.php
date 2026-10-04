<?php

namespace App\Http\Controllers\Api;

use App\Support\PropertyAssignments;
use App\Support\ReportGrouping;
use App\Support\ReportItems;
use App\Support\ReportSeverity;
use App\Support\SuggestedIssues;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class MobileReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET ROOMS
    |--------------------------------------------------------------------------
    */

    public function rooms()
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

            ->leftJoinSub(
                $equipmentCountSub,
                'room_equipment_counts',
                'rooms_table.room_id',
                '=',
                'room_equipment_counts.equipment_room_id'
            )

            ->select(

                'rooms_table.room_id',

                DB::raw("
                    CONCAT(
                        floors_table.floor_level,
                        ' - ',
                        rooms_table.room_name
                    ) AS location
                "),

                DB::raw('COALESCE(room_equipment_counts.equipment_count, 0) as equipment_count')

            )

            ->orderBy('floors_table.floor_level')

            ->orderBy('rooms_table.room_name')

            ->get();

        return response()->json($rooms);
    }

    /*
    |--------------------------------------------------------------------------
    | GET EQUIPMENT BY ROOM
    |--------------------------------------------------------------------------
    */

    public function equipment($roomId)
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

        $columns = [
            'equipment_table.equipment_id',
            'equipment_table.equipment_name',
            'equipment_table.equipment_brand_name',
            'equipment_table.equipment_model',
        ];

        foreach ([
            'equipment_asset_tag',
            'equipment_serial_number',
            'equipment_placement_zone',
            'equipment_category_id',
        ] as $optional) {
            if (Schema::hasColumn('equipment_table', $optional)) {
                $columns[] = "equipment_table.$optional";
            }
        }

        if (Schema::hasTable('equipment_categories_table')) {
            $columns[] = 'equipment_categories_table.equipment_category_name';
        }

        $equipment = ReportGrouping::enrichEquipmentWithOpenReports(
            $query
                ->select($columns)
                ->orderBy('equipment_table.equipment_name')
                ->get(),
            (int) $roomId
        );

        return response()->json($equipment);
    }

    /*
    |--------------------------------------------------------------------------
    | GET REPORTABLE EQUIPMENT BY QR (reporter scan-to-add)
    |--------------------------------------------------------------------------
    */

    public function equipmentByQr(string $qr)
    {
        $qr = trim($qr);

        $base = DB::table('equipment_table')
            ->whereRaw('LOWER(equipment_table.equipment_qr_code) = ?', [mb_strtolower($qr)]);

        $raw = (clone $base)->select('equipment_id', 'equipment_room_id')->first();

        if (! $raw) {
            return response()->json([
                'success' => false,
                'code' => 'not_found',
                'message' => 'No equipment matches this QR code.',
            ], 404);
        }

        if (empty($raw->equipment_room_id)) {
            return response()->json([
                'success' => false,
                'code' => 'no_location',
                'message' => 'This equipment is not assigned to a location yet.',
            ], 422);
        }

        $equipment = $this->reportableEquipmentRows(clone $base)->first();

        if (! $equipment) {
            return response()->json([
                'success' => false,
                'code' => 'not_reportable',
                'message' => 'This equipment can\'t be reported right now (it may be disposed, for replacement, or its room is archived).',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'equipment' => $equipment,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET EQUIPMENT ASSIGNED TO A REPORTER (property assignment)
    |--------------------------------------------------------------------------
    */

    public function assignedEquipment(string $employeeId)
    {
        $equipmentIds = PropertyAssignments::reportableForEmployee($employeeId)
            ->pluck('equipment_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($equipmentIds === []) {
            return response()->json([]);
        }

        $rows = $this->reportableEquipmentRows(
            DB::table('equipment_table')->whereIn('equipment_table.equipment_id', $equipmentIds)
        );

        return response()->json($rows->values());
    }

    /**
     * Reportable equipment with its room, location label and any open report.
     */
    private function reportableEquipmentRows($base)
    {
        $query = ReportGrouping::applyReporterEquipmentFilters($base)
            ->join('rooms_table', 'equipment_table.equipment_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('floors_table', 'rooms_table.room_floor_id', '=', 'floors_table.floor_id')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('rooms_table.room_is_archived', false)
            );

        if (Schema::hasTable('equipment_categories_table')) {
            $query->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            );
        }

        $columns = [
            'equipment_table.equipment_id',
            'equipment_table.equipment_name',
            'equipment_table.equipment_brand_name',
            'equipment_table.equipment_model',
            'equipment_table.equipment_qr_code',
            'rooms_table.room_id',
            'rooms_table.room_name',
            DB::raw("CONCAT(floors_table.floor_level, ' - ', rooms_table.room_name) AS location"),
        ];

        foreach ([
            'equipment_asset_tag',
            'equipment_serial_number',
            'equipment_placement_zone',
            'equipment_category_id',
        ] as $optional) {
            if (Schema::hasColumn('equipment_table', $optional)) {
                $columns[] = "equipment_table.$optional";
            }
        }

        if (Schema::hasTable('equipment_categories_table')) {
            $columns[] = 'equipment_categories_table.equipment_category_name';
        }

        return $query->select($columns)
            ->orderBy('rooms_table.room_name')
            ->orderBy('equipment_table.equipment_name')
            ->get()
            ->groupBy('room_id')
            ->flatMap(fn ($rows, $roomId) => ReportGrouping::enrichEquipmentWithOpenReports($rows, (int) $roomId))
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | GET SUGGESTED ISSUES
    |--------------------------------------------------------------------------
    */

    public function suggestedIssues($equipmentId)
    {
        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_id',
                $equipmentId
            )

            ->first();

        if (!$equipment) {

            return response()->json([]);

        }

        $query = DB::table('issue_templates_table')
            ->where('issue_template_category_id', $equipment->equipment_category_id)
            ->orderBy('issue_template_name');

        if (Schema::hasColumn('issue_templates_table', 'issue_template_component')) {
            $component = SuggestedIssues::detectComponent($equipment->equipment_name ?? '');

            if ($component) {
                $query->where('issue_template_component', $component);
            } else {
                $query->where(function ($inner) {
                    $inner
                        ->whereNull('issue_template_component')
                        ->orWhere('issue_template_component', '');
                });
            }
        }

        $issues = $query
            ->select('issue_template_id', 'issue_template_name')
            ->get();

        return response()->json($issues);
    }

    public function globalSuggestedIssues()
    {
        $issues = DB::table('issue_templates_table')

            ->select(
                'issue_template_id',
                'issue_template_name'
            )

            ->orderBy('issue_template_name')

            ->get();

        return response()->json($issues);
    }

    /*
    |--------------------------------------------------------------------------
    | GET REPORTER INFORMATION
    |--------------------------------------------------------------------------
    */

    public function reporter($employeeId)
    {
        $reporter = DB::table('reporters_table')

            ->where(
                'reporter_employee_id',
                $employeeId
            )

            ->first();

        return response()->json($reporter);
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMIT REPORT
    |--------------------------------------------------------------------------
    */

    public function submitReport(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'employee_id' => 'required',

            'room_id' => 'required|integer',

            'equipment_id' => 'nullable|integer',

            'equipment_ids' => 'nullable|array',

            'equipment_ids.*' => 'integer',

            'equipment_issues' => 'nullable|array',

            'equipment_issues.*' => 'nullable|string|max:255',

            'manual_equipment_name' => 'nullable|string|max:255',

            'manual_equipment_names' => 'nullable|array',

            'manual_equipment_names.*' => 'nullable|string|max:255',

            'manual_equipment_issues' => 'nullable|array',

            'manual_equipment_issues.*' => 'nullable|string|max:255',

            // Per-item details, parallel to equipment_ids / manual_equipment_names.
            'equipment_details' => 'nullable|array',

            'equipment_details.*' => 'nullable|string|max:2000',

            'manual_equipment_details' => 'nullable|array',

            'manual_equipment_details.*' => 'nullable|string|max:2000',

            // Parallel to manual_equipment_names; items may come from different rooms.
            'manual_equipment_rooms' => 'nullable|array',

            'manual_equipment_rooms.*' => 'nullable|integer',

            'issue_template_id' => 'nullable|integer',

            'description' => 'nullable|string',

            // Older app builds still send priority; the system decides it now.
            'priority' => 'nullable|string',

            'preferred_action_date' => ReportGrouping::preferredActionDateRules(),

            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',

            // One optional photo per item: keyed by equipment id / manual_equipment_names index.
            'equipment_photos' => 'nullable|array',

            'equipment_photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',

            'manual_equipment_photos' => 'nullable|array',

            'manual_equipment_photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',

        ]);

        $rawEquipmentIds = collect($request->input('equipment_ids', []));
        $rawEquipmentIssues = collect($request->input('equipment_issues', []));
        $rawEquipmentDetails = collect($request->input('equipment_details', []));

        $equipmentIds = $rawEquipmentIds
            ->when(
                $request->filled('equipment_id'),
                fn ($ids) => $ids->push($request->equipment_id)
            )
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $equipmentIssuesById = [];
        $equipmentDetailsById = [];
        foreach ($rawEquipmentIds as $index => $rawId) {
            $equipmentId = (int) $rawId;
            if ($equipmentId <= 0) {
                continue;
            }
            $equipmentIssuesById[$equipmentId] = trim((string) ($rawEquipmentIssues[$index] ?? ''));
            $equipmentDetailsById[$equipmentId] = trim((string) ($rawEquipmentDetails[$index] ?? ''));
        }

        $manualNames = collect($request->input('manual_equipment_names', []))
            ->when(
                filled(trim((string) $request->manual_equipment_name)),
                fn ($names) => $names->push($request->manual_equipment_name)
            )
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        // Preserve first occurrence for case-insensitive uniqueness while
        // keeping parallel issue indexes aligned via a rebuild below.
        $manualIssuesRaw = collect($request->input('manual_equipment_issues', []))
            ->map(fn ($issue) => trim((string) $issue))
            ->values();

        while ($manualIssuesRaw->count() < collect($request->input('manual_equipment_names', []))->count()) {
            $manualIssuesRaw->push('');
        }

        $rawManualDetails = collect($request->input('manual_equipment_details', []));

        $dedupedManuals = [];
        $manualIssues = [];
        $manualDetails = [];
        $manualPhotoKeys = [];
        $seenManual = [];
        $sourceManuals = collect($request->input('manual_equipment_names', []))
            ->when(
                filled(trim((string) $request->manual_equipment_name)),
                fn ($names) => $names->push($request->manual_equipment_name)
            )
            ->values();

        foreach ($sourceManuals as $index => $rawName) {
            $name = trim((string) $rawName);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seenManual[$key])) {
                continue;
            }
            $seenManual[$key] = true;
            $dedupedManuals[] = $name;
            $manualIssues[] = trim((string) ($manualIssuesRaw[$index] ?? ''));
            $manualDetails[] = trim((string) ($rawManualDetails[$index] ?? ''));
            $manualPhotoKeys[] = $index;
        }

        $manualNames = collect($dedupedManuals);
        $manualIssues = collect($manualIssues);
        $manualDetails = collect($manualDetails);

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($equipmentIds->isEmpty() && $manualNames->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one equipment or enter a manual equipment name.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD SHARED FALLBACK ISSUE (legacy single-item clients)
        |--------------------------------------------------------------------------
        */

        $sharedIssueName = null;

        if ($request->filled('issue_template_id')) {
            $issue = DB::table('issue_templates_table')
                ->where('issue_template_id', $request->issue_template_id)
                ->first();

            if ($issue) {
                $sharedIssueName = $issue->issue_template_name;
            }
        }

        $sharedDescription = trim((string) $request->description);
        $sharedFallback = $sharedIssueName ?: $sharedDescription;

        if (
            $request->filled('equipment_id')
            && $sharedFallback !== ''
            && ! isset($equipmentIssuesById[(int) $request->equipment_id])
        ) {
            $equipmentIssuesById[(int) $request->equipment_id] = $sharedFallback;
        }

        /*
        |--------------------------------------------------------------------------
        | ISSUE VALIDATION (per item, matching web)
        |--------------------------------------------------------------------------
        */

        $missingListedIssue = $equipmentIds->contains(
            function ($equipmentId) use ($equipmentIssuesById, $equipmentDetailsById, $sharedFallback) {
                $itemIssue = trim((string) ($equipmentIssuesById[$equipmentId] ?? ''));

                return $itemIssue === ''
                    && ($equipmentDetailsById[$equipmentId] ?? '') === ''
                    && $sharedFallback === '';
            }
        );

        $missingManualIssue = $manualNames->keys()->contains(
            function ($index) use ($manualIssues, $manualDetails, $sharedFallback) {
                $itemIssue = trim((string) ($manualIssues[$index] ?? ''));

                return $itemIssue === ''
                    && ($manualDetails[$index] ?? '') === ''
                    && $sharedFallback === '';
            }
        );

        if ($missingListedIssue || $missingManualIssue) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a suggested issue or describe the problem for each equipment.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY REPORTER
        |--------------------------------------------------------------------------
        */

        $reporter = DB::table('reporters_table')

            ->where(
                'reporter_employee_id',
                $request->employee_id
            )

            ->first();

        if (!$reporter) {

            return response()->json([

                'success' => false,

                'message' => 'Employee ID not found.'

            ], 404);

        }

        /*
        |--------------------------------------------------------------------------
        | SAVE PHOTO
        |--------------------------------------------------------------------------
        */

        $equipmentRoomIds = collect();
        $validEquipment = collect();

        if ($equipmentIds->isNotEmpty()) {
            $validEquipment = DB::table('equipment_table')
                ->whereIn('equipment_id', $equipmentIds->all())
                ->whereNotNull('equipment_room_id')
                ->get()
                ->keyBy('equipment_id');

            if ($validEquipment->count() !== $equipmentIds->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected equipment could not be found or have no location.',
                ], 422);
            }

            $equipmentRoomIds = $validEquipment->map(fn ($row) => (int) $row->equipment_room_id);

            foreach ($equipmentIds as $equipmentId) {
                if (ReportGrouping::equipmentIsForReplacement((int) $equipmentId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An equipment is already marked for replacement and cannot be reported again.',
                    ], 422);
                }
            }
        }

        $rawManualRooms = $request->input('manual_equipment_rooms', []);
        $manualRoomIds = collect();
        foreach ($manualNames->keys() as $position) {
            $rawKey = $manualPhotoKeys[$position] ?? null;
            $roomId = (int) ($rawKey !== null ? ($rawManualRooms[$rawKey] ?? 0) : 0);
            $manualRoomIds[$position] = $roomId > 0 ? $roomId : (int) $request->room_id;
        }

        $roomIdsToCheck = $manualRoomIds->unique()->values();
        if ($roomIdsToCheck->isNotEmpty()) {
            $existingRooms = DB::table('rooms_table')
                ->whereIn('room_id', $roomIdsToCheck->all())
                ->count();

            if ($existingRooms !== $roomIdsToCheck->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected locations could not be found.',
                ], 422);
            }
        }

        $storePhoto = fn ($file) => $file instanceof \Illuminate\Http\UploadedFile && $file->isValid()
            ? $file->store('reports', 'public')
            : null;

        // Older app builds send one photo for the whole report; it fills items without their own.
        $sharedPhotoPath = $storePhoto($request->file('photo'));

        $equipmentPhotoPaths = [];
        foreach ($equipmentIds as $equipmentId) {
            $equipmentPhotoPaths[$equipmentId] = $storePhoto($request->file('equipment_photos.'.$equipmentId))
                ?? $sharedPhotoPath;
        }

        $manualPhotoPaths = [];
        foreach ($manualNames->keys() as $position) {
            $rawKey = $manualPhotoKeys[$position] ?? null;
            $manualPhotoPaths[$position] = ($rawKey !== null
                ? $storePhoto($request->file('manual_equipment_photos.'.$rawKey))
                : null) ?? $sharedPhotoPath;
        }

        // An item with its own details but no picked issue is an "Other" problem,
        // so the shared fallback only fills items that sent neither.
        $resolveItemIssue = function (?string $itemIssue, string $details = '') use ($sharedFallback): string {
            $itemIssue = trim((string) $itemIssue);

            if ($itemIssue !== '') {
                return $itemIssue;
            }

            return $details !== '' ? '' : $sharedFallback;
        };

        $severityItems = [];
        $detailEntries = [];
        foreach ($equipmentIds as $equipmentId) {
            $details = $equipmentDetailsById[$equipmentId] ?? '';
            $severityItems[] = [
                'equipment_id' => (int) $equipmentId,
                'issue' => $resolveItemIssue($equipmentIssuesById[$equipmentId] ?? '', $details),
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
                'issue' => $resolveItemIssue($manualIssues[$index] ?? '', $details),
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
                $request->preferred_action_date
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

        /*
        |--------------------------------------------------------------------------
        | INSERT REPORT
        |--------------------------------------------------------------------------
        */

        $primaryEquipmentId = $newEquipmentIds->first();
        $primaryManual = $primaryEquipmentId ? null : $manualNames->first();
        $primaryIssue = $primaryEquipmentId
            ? $resolveItemIssue($equipmentIssuesById[$primaryEquipmentId] ?? '', $equipmentDetailsById[$primaryEquipmentId] ?? '')
            : $resolveItemIssue($manualIssues->first() ?? '', $manualDetails->first() ?? '');
        $primaryIssue = $primaryIssue !== ''
            ? $primaryIssue
            : (string) collect($severityItems)->pluck('issue')->filter()->first();

        $primaryRoomId = $primaryEquipmentId
            ? $equipmentRoomIds[$primaryEquipmentId]
            : ($manualRoomIds->first() ?: (int) $request->room_id);

        $photoPath = $newEquipmentIds->map(fn ($id) => $equipmentPhotoPaths[$id] ?? null)
            ->merge($manualPhotoPaths)
            ->filter()
            ->first();

        $reportPayload = [

            'report_reporter_employee_id' => $request->employee_id,

            'report_room_id' => $primaryRoomId,

            'report_equipment_id' => $primaryEquipmentId,

            'report_unlisted_equipment_name' => $primaryManual,

            'report_suggested_issue' => $primaryIssue !== '' ? $primaryIssue : null,

            'report_problem_description' => $ticketDescription !== '' ? $ticketDescription : null,

            'report_urgency_level' => $severity['urgency'],

            'report_current_status' => 'Pending',

            'report_uploaded_image' => $photoPath,

            'report_is_archived' => false,

            'report_submitted_at' => now(),

            'report_updated_at' => now(),

        ] + ReportSeverity::columnsFor($severity);

        if (Schema::hasColumn('reports_table', 'report_related_count')) {
            $reportPayload['report_related_count'] = 1;
        }

        if (ReportGrouping::hasLastReportedColumn()) {
            $reportPayload['report_last_reported_at'] = $reportPayload['report_submitted_at'];
        }

        if (ReportGrouping::hasPreferredActionDateColumn()) {
            $reportPayload['report_preferred_action_date'] = $preferredDate;
        }

        $reportId = DB::table('reports_table')->insertGetId($reportPayload);

        $itemPayloads = [];

        foreach ($newEquipmentIds as $equipmentId) {
            $details = $equipmentDetailsById[$equipmentId] ?? '';
            $itemIssue = $resolveItemIssue($equipmentIssuesById[$equipmentId] ?? '', $details);
            $itemDetails = $details !== '' ? $details : $sharedDescription;

            $itemPayloads[] = [
                'equipment_id' => (int) $equipmentId,
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $itemDetails !== '' ? $itemDetails : null,
                'uploaded_image' => $equipmentPhotoPaths[$equipmentId] ?? null,
                'room_id' => $equipmentRoomIds[$equipmentId],
            ];
        }

        foreach ($manualNames as $index => $manualName) {
            $details = $manualDetails[$index] ?? '';
            $itemIssue = $resolveItemIssue($manualIssues[$index] ?? '', $details);
            $itemDetails = $details !== '' ? $details : $sharedDescription;

            $itemPayloads[] = [
                'unlisted_name' => $manualName,
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $itemDetails !== '' ? $itemDetails : null,
                'uploaded_image' => $manualPhotoPaths[$index] ?? null,
                'room_id' => $manualRoomIds[$index],
            ];
        }

        ReportItems::createForReport((int) $reportId, $itemPayloads);

        $report = DB::table('reports_table')
            ->where('report_id', $reportId)
            ->first();

        // Report is already saved. Don't fail the API if Reverb/Pusher is offline.
        try {
            event(new \App\Events\ReportSubmitted($report));
            Log::info('Broadcast fired', [
                'report_id' => $reportId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Report broadcast failed (report still saved)', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        $ticketCode = ReportGrouping::ticketCode($report ?? (int) $reportId, $reportPayload['report_submitted_at']);

        $message = count($itemPayloads) > 1
            ? 'Report '.$ticketCode.' submitted successfully with '.count($itemPayloads).' equipment items.'
            : 'Report '.$ticketCode.' submitted successfully.';

        $repeatCount = $alreadyReportedIds->count();
        if ($repeatCount > 0) {
            $message .= ' '.($repeatCount === 1 ? '1 equipment was' : $repeatCount.' equipment were')
                .' already reported and still waiting, so maintenance will see '
                .($repeatCount === 1 ? 'it' : 'them').' flagged as priority.';
        }

        return response()->json([

            'success' => true,

            'message' => $message,
            'report_id' => $reportId,
            'ticket_code' => $ticketCode,
            'item_count' => count($itemPayloads),
            'repeat_count' => $repeatCount,
            'severity' => $severity['level'],
            'severity_reason' => $severity['reason'],
            'urgency' => $severity['urgency'],

        ]);

    }
}
