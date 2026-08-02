<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

            'report_reporter_employee_id' =>

                'required',

            'report_room_id' =>

                'required',

            'report_problem_description' =>

                'required',

            'report_urgency_level' =>

                'required'

        ]);

        /*
        |--------------------------------------------------------------------------
        | CHECK REPORTER
        |--------------------------------------------------------------------------
        */

        $reporter = DB::table('reporters_table')

            ->where(
                'reporter_employee_id',
                $request->report_reporter_employee_id
            )

            ->first();

        if(!$reporter){

            return response()->json([

                'success' => false,

                'message' => 'Employee ID not found.'

            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        $imagePath = null;

        if($request->hasFile('report_uploaded_image')){

            $imagePath = $request

                ->file('report_uploaded_image')

                ->store(
                    'report-images',
                    'public'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT REPORT
        |--------------------------------------------------------------------------
        */

        DB::table('reports_table')

            ->insert([

                'report_reporter_employee_id' =>

                    $request->report_reporter_employee_id,

                'report_room_id' =>

                    $request->report_room_id,

                'report_equipment_id' =>

                    $request->report_equipment_id,

                'report_problem_description' =>

                    $request->report_problem_description,

                'report_urgency_level' =>

                    $request->report_urgency_level,

                'report_current_status' =>

                    'Pending',

                'report_uploaded_image' =>

                    $imagePath,

                'report_submitted_at' =>

                    now(),

                'report_updated_at' =>

                    now()

            ]);

        /*
        |--------------------------------------------------------------------------
        | SUCCESS RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' => 'Report submitted successfully.'

        ]);
    }
}
