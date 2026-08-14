<?php

namespace App\Http\Controllers\Api;

use App\Support\ReportGrouping;
use App\Support\SuggestedIssues;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $equipment = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
                ->where('equipment_room_id', $roomId)
        )
            ->select(
                'equipment_id',
                'equipment_name',
                'equipment_brand_name',
                'equipment_model'
            )
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

        $issues = SuggestedIssues::namesForEquipment($equipment)
            ->map(function ($name, $index) {
                return [
                    'issue_template_id' => $index + 1,
                    'issue_template_name' => $name,
                ];
            })
            ->values();

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

            'manual_equipment_name' => 'nullable|string|max:255',

            'issue_template_id' => 'nullable|integer',

            'description' => 'nullable|string',

            'priority' => 'required|in:Urgent,Non-Urgent',

            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',

        ]);

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT VALIDATION
        |--------------------------------------------------------------------------
        */

        if (

            empty($request->equipment_id) &&
            empty(trim($request->manual_equipment_name))

        ) {

            return response()->json([

                'success' => false,

                'message' => 'Please select equipment or enter a manual equipment name.'

            ], 422);

        }

        /*
        |--------------------------------------------------------------------------
        | ISSUE VALIDATION
        |--------------------------------------------------------------------------
        */

        if (

            empty($request->issue_template_id) &&
            empty(trim($request->description))

        ) {

            return response()->json([

                'success' => false,

                'message' => 'Please select a suggested issue or provide a description.'

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
        | BUILD FINAL DESCRIPTION
        |--------------------------------------------------------------------------
        */

        $issueName = null;

        if ($request->filled('issue_template_id')) {

            $issue = DB::table('issue_templates_table')

                ->where(
                    'issue_template_id',
                    $request->issue_template_id
                )

                ->first();

            if ($issue) {

                $issueName = $issue->issue_template_name;

            }

        }

        $description = '';

        if ($issueName) {

            $description .= $issueName;

        }

        if (!empty(trim($request->description))) {

            if (!empty($description)) {

                $description .= "\n\n";

            }

            $description .= trim($request->description);

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

        /*
        |--------------------------------------------------------------------------
        | BLOCK EQUIPMENT ALREADY FOR REPLACEMENT
        |--------------------------------------------------------------------------
        */

        if (!empty($request->equipment_id)) {
            $listedEquipment = DB::table('equipment_table')
                ->where('equipment_id', $request->equipment_id)
                ->where('equipment_room_id', $request->room_id)
                ->first();

            if (!$listedEquipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected equipment does not belong to the selected room.',
                ], 422);
            }

            if (ReportGrouping::equipmentIsForReplacement((int) $request->equipment_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This equipment is already marked for replacement and cannot be reported again.',
                ], 422);
            }

            $openReport = ReportGrouping::findOpenReport(
                (int) $request->equipment_id,
                (int) $request->room_id
            );

            if ($openReport) {
                ReportGrouping::mergeIntoOpenReport($openReport, [
                    'reporter_id' => $request->employee_id,
                    'urgency' => $request->priority,
                    'issue' => $issueName ?: trim((string) $request->description),
                ]);

                return response()->json([
                    'success' => true,
                    'merged' => true,
                    'message' => 'This equipment already has an open report. Your report was added to it instead of creating a duplicate.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT REPORT
        |--------------------------------------------------------------------------
        */

        $reportPayload = [

            'report_reporter_employee_id' => $request->employee_id,

            'report_room_id' => $request->room_id,

            'report_equipment_id' => $request->equipment_id,

            'report_suggested_issue' => $issueName,

            'report_problem_description' => trim($request->description) ?: null,

            'report_urgency_level' => $request->priority,

            'report_current_status' => 'Pending',

            'report_uploaded_image' => $photoPath,

            'report_submitted_at' => now(),

            'report_updated_at' => now(),

        ];

        if (Schema::hasColumn('reports_table', 'report_related_count')) {
            $reportPayload['report_related_count'] = 1;
        }

        $reportId = DB::table('reports_table')->insertGetId($reportPayload);

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

        return response()->json([

            'success' => true,

            'message' => 'Report submitted successfully.'

        ]);

    }
}
