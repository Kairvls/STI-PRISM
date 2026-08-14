<?php

namespace App\Http\Controllers;

use App\Support\ReportGrouping;
use App\Support\SuggestedIssues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LANDING PAGE
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | ROOMS
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | FUNCTIONAL EQUIPMENT ONLY
        |--------------------------------------------------------------------------
        */

        $equipment = ReportGrouping::applyReporterEquipmentFilters(
            DB::table('equipment_table')
        )
            ->orderBy('equipment_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MAINTENANCE ANNOUNCEMENTS
        |--------------------------------------------------------------------------
        */

        $announcements = collect([

            (object)[

                'announcement_title' =>

                    'Internet Maintenance',

                'announcement_description' =>

                    'Computer Laboratory 2 internet maintenance ongoing.'

            ],

            (object)[

                'announcement_title' =>

                    'Aircon Repair',

                'announcement_description' =>

                    'Room 204 air conditioning repair scheduled.'

            ],

            (object)[

                'announcement_title' =>

                    'Laboratory Upgrade',

                'announcement_description' =>

                    'Computer Lab 1 equipment replacement this week.'

            ]

        ]);

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(

            'landing.index',

            compact(

                'rooms',

                'equipment',

                'announcements'

            )

        );
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
}

