<?php

namespace App\Http\Controllers\Api;

use App\Support\ReportGrouping;
use App\Support\ReportItems;
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

            ->select(

                'rooms_table.room_id',

                DB::raw("
                    CONCAT(
                        floors_table.floor_level,
                        ' - ',
                        rooms_table.room_name
                    ) AS location
                ")

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
        $columns = [
            'equipment_id',
            'equipment_name',
            'equipment_brand_name',
            'equipment_model',
        ];

        foreach ([
            'equipment_asset_tag',
            'equipment_serial_number',
            'equipment_placement_zone',
        ] as $optional) {
            if (Schema::hasColumn('equipment_table', $optional)) {
                $columns[] = $optional;
            }
        }

        $equipment = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
                ->where('equipment_room_id', $roomId)
        )
            ->select($columns)
            ->orderBy('equipment_name')
            ->get();

        return response()->json($equipment);
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

            'issue_template_id' => 'nullable|integer',

            'description' => 'nullable|string',

            'priority' => 'required|in:Urgent,Non-Urgent',

            'preferred_action_date' => ReportGrouping::preferredActionDateRules(),

            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',

        ]);

        $rawEquipmentIds = collect($request->input('equipment_ids', []));
        $rawEquipmentIssues = collect($request->input('equipment_issues', []));

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
        foreach ($rawEquipmentIds as $index => $rawId) {
            $equipmentId = (int) $rawId;
            if ($equipmentId <= 0) {
                continue;
            }
            $equipmentIssuesById[$equipmentId] = trim((string) ($rawEquipmentIssues[$index] ?? ''));
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

        $dedupedManuals = [];
        $manualIssues = [];
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
        }

        $manualNames = collect($dedupedManuals);
        $manualIssues = collect($manualIssues);

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
            function ($equipmentId) use ($equipmentIssuesById, $sharedFallback) {
                $itemIssue = trim((string) ($equipmentIssuesById[$equipmentId] ?? ''));

                return $itemIssue === '' && $sharedFallback === '';
            }
        );

        $missingManualIssue = $manualNames->keys()->contains(
            function ($index) use ($manualIssues, $sharedFallback) {
                $itemIssue = trim((string) ($manualIssues[$index] ?? ''));

                return $itemIssue === '' && $sharedFallback === '';
            }
        );

        if ($missingListedIssue || $missingManualIssue) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a suggested issue or provide a description for each equipment.',
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

        $photoPath = null;

        if ($request->hasFile('photo')) {

            $photoPath = $request
                ->file('photo')
                ->store('reports', 'public');

        }

        if ($equipmentIds->isNotEmpty()) {
            $validEquipment = DB::table('equipment_table')
                ->whereIn('equipment_id', $equipmentIds->all())
                ->where('equipment_room_id', $request->room_id)
                ->get()
                ->keyBy('equipment_id');

            if ($validEquipment->count() !== $equipmentIds->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected equipment do not belong to the selected room.',
                ], 422);
            }

            foreach ($equipmentIds as $equipmentId) {
                if (ReportGrouping::equipmentIsForReplacement((int) $equipmentId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'An equipment is already marked for replacement and cannot be reported again.',
                    ], 422);
                }
            }
        }

        $preferredDate = ReportGrouping::hasPreferredActionDateColumn()
            ? ReportGrouping::resolvePreferredActionDate(
                $request->priority,
                $request->preferred_action_date
            )
            : null;

        $resolveItemIssue = function (?string $itemIssue) use ($sharedFallback): string {
            $itemIssue = trim((string) $itemIssue);

            return $itemIssue !== '' ? $itemIssue : $sharedFallback;
        };

        $totalSelected = $equipmentIds->count() + $manualNames->count();
        $isMultiItemSubmit = $totalSelected > 1;
        $newEquipmentIds = collect();

        if (! $isMultiItemSubmit && $equipmentIds->count() === 1 && $manualNames->isEmpty()) {
            $equipmentId = (int) $equipmentIds->first();
            $openReport = ReportGrouping::findOpenReport(
                $equipmentId,
                (int) $request->room_id
            );

            $itemIssue = $resolveItemIssue($equipmentIssuesById[$equipmentId] ?? '');

            if ($openReport) {
                ReportGrouping::mergeIntoOpenReport($openReport, [
                    'reporter_id' => $request->employee_id,
                    'urgency' => $request->priority,
                    'preferred_action_date' => $preferredDate,
                    'issue' => $itemIssue,
                ]);

                return response()->json([
                    'success' => true,
                    'merged' => true,
                    'message' => 'This equipment already has an open report (#'.$openReport->report_id.'). Your update was added to it instead of creating a duplicate.',
                ]);
            }

            $newEquipmentIds->push($equipmentId);
        } else {
            foreach ($equipmentIds as $equipmentId) {
                $openReport = ReportGrouping::findOpenReport(
                    (int) $equipmentId,
                    (int) $request->room_id
                );

                if ($openReport) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected equipment already have an open report. Remove those items and submit again.',
                    ], 422);
                }

                $newEquipmentIds->push((int) $equipmentId);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT REPORT
        |--------------------------------------------------------------------------
        */

        $primaryEquipmentId = $newEquipmentIds->first();
        $primaryManual = $primaryEquipmentId ? null : $manualNames->first();
        $primaryIssue = $primaryEquipmentId
            ? $resolveItemIssue($equipmentIssuesById[$primaryEquipmentId] ?? '')
            : $resolveItemIssue($manualIssues->first() ?? '');

        $reportPayload = [

            'report_reporter_employee_id' => $request->employee_id,

            'report_room_id' => $request->room_id,

            'report_equipment_id' => $primaryEquipmentId,

            'report_unlisted_equipment_name' => $primaryManual,

            'report_suggested_issue' => $primaryIssue !== '' ? $primaryIssue : null,

            'report_problem_description' => $sharedDescription !== '' ? $sharedDescription : null,

            'report_urgency_level' => $request->priority,

            'report_current_status' => 'Pending',

            'report_uploaded_image' => $photoPath,

            'report_is_archived' => false,

            'report_submitted_at' => now(),

            'report_updated_at' => now(),

        ];

        if (Schema::hasColumn('reports_table', 'report_related_count')) {
            $reportPayload['report_related_count'] = 1;
        }

        if (ReportGrouping::hasPreferredActionDateColumn()) {
            $reportPayload['report_preferred_action_date'] = $preferredDate;
        }

        $reportId = DB::table('reports_table')->insertGetId($reportPayload);

        $itemPayloads = [];

        foreach ($newEquipmentIds as $equipmentId) {
            $itemIssue = $resolveItemIssue($equipmentIssuesById[$equipmentId] ?? '');

            $itemPayloads[] = [
                'equipment_id' => (int) $equipmentId,
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $sharedDescription !== '' ? $sharedDescription : null,
                'uploaded_image' => $photoPath,
            ];
        }

        foreach ($manualNames as $index => $manualName) {
            $itemIssue = $resolveItemIssue($manualIssues[$index] ?? '');

            $itemPayloads[] = [
                'unlisted_name' => $manualName,
                'suggested_issue' => $itemIssue !== '' ? $itemIssue : null,
                'problem_description' => $sharedDescription !== '' ? $sharedDescription : null,
                'uploaded_image' => $photoPath,
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

        $message = count($itemPayloads) > 1
            ? 'Report #'.$reportId.' submitted successfully with '.count($itemPayloads).' equipment items.'
            : 'Report #'.$reportId.' submitted successfully.';

        return response()->json([

            'success' => true,

            'message' => $message,
            'report_id' => $reportId,
            'item_count' => count($itemPayloads),

        ]);

    }
}
