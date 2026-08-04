<?php

namespace App\Http\Controllers\Api;

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
        $equipment = DB::table('equipment_table')

            ->where(
                'equipment_room_id',
                $roomId
            )

            ->where(
                'equipment_inventory_status',
                'Active'
            )

            ->where(
                'equipment_condition_status',
                'Good'
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

        $issues = DB::table('issue_templates_table')

            ->where(
                'issue_template_category_id',
                $equipment->equipment_category_id
            )

            ->orderBy(
                'issue_template_name'
            )

            ->get([
                'issue_template_id',
                'issue_template_name'
            ]);

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
        | INSERT REPORT
        |--------------------------------------------------------------------------
        */

        $reportId = DB::table('reports_table')->insertGetId([

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

        ]);

        $report = DB::table('reports_table')
            ->where('report_id', $reportId)
            ->first();

        event(new \App\Events\ReportSubmitted($report));
        Log::info('Broadcast fired', [
            'report_id' => $reportId,
        ]);

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
