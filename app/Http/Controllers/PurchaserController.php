<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaserController extends Controller
{
    // =====================================================
    // PURCHASER DASHBOARD
    // =====================================================

    public function dashboard()
    {
        // =====================================================
        // COUNT PENDING REPLACEMENT REQUESTS HERE
        // =====================================================

        $pendingReplacementRequests = DB::table(
            'procurement_requests_table'
        )
            ->where(
                'procurement_request_status',
                'Pending'
            )
            ->count();


        // =====================================================
        // COUNT APPROVED REPLACEMENT REQUESTS HERE
        // =====================================================

        $approvedReplacementRequests = DB::table(
            'procurement_requests_table'
        )
            ->where(
                'procurement_request_status',
                'Approved'
            )
            ->count();


        // =====================================================
        // COUNT COMPLETED REPLACEMENT REQUESTS HERE
        // =====================================================

        $completedReplacementRequests = DB::table(
            'procurement_requests_table'
        )
            ->where(
                'procurement_request_status',
                'Completed'
            )
            ->count();


        // =====================================================
        // COUNT AVAILABLE URGENT REPORTS HERE
        //
        // AVAILABLE MEANS:
        // URGENT
        // PENDING
        // NOT ARCHIVED
        // NOT CLAIMED BY MAINTENANCE
        // NOT CLAIMED BY PURCHASER
        // =====================================================

        $availableUrgentReports = DB::table('reports_table')

            ->where(
                'report_urgency_level',
                'Urgent'
            )

            ->where(
                'report_current_status',
                'Pending'
            )

            ->where(
                'report_is_archived',
                0
            )

            ->whereNull(
                'report_assigned_personnel_id'
            )

            ->whereNull(
                'report_assigned_purchaser_id'
            )

            ->count();


        // =====================================================
        // RETURN PURCHASER DASHBOARD HERE
        // =====================================================

        return view(
            'purchaser.dashboard',
            compact(
                'pendingReplacementRequests',
                'approvedReplacementRequests',
                'completedReplacementRequests',
                'availableUrgentReports'
            )
        );
    }



    // =====================================================
    // SHOW REPLACEMENT REQUESTS FROM MAINTENANCE
    // =====================================================

    public function replacementRequests(Request $request)
    {
        // =====================================================
        // BUILD REPLACEMENT REQUEST QUERY HERE
        // =====================================================

        $query = DB::table('procurement_requests_table')


            // =====================================================
            // JOIN MAINTENANCE REPORT HERE
            // =====================================================

            ->join(
                'reports_table',
                'procurement_requests_table.procurement_request_report_id',
                '=',
                'reports_table.report_id'
            )


            // =====================================================
            // JOIN EQUIPMENT HERE
            // =====================================================

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )


            // =====================================================
            // JOIN ROOM HERE
            // =====================================================

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            // =====================================================
            // JOIN REPORTER HERE
            // USED BY REPLACEMENT REQUEST CARDS AND VIEW MODAL
            // =====================================================

            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )


            // =====================================================
            // JOIN MAINTENANCE PERSONNEL WHO CREATED REQUEST HERE
            // procurement_request_created_by stores user_id
            // =====================================================

            ->leftJoin(
                'users_table as request_creator',
                'procurement_requests_table.procurement_request_created_by',
                '=',
                'request_creator.user_id'
            )


            // =====================================================
            // SELECT DATA HERE
            // =====================================================

            ->select(

                'procurement_requests_table.*',

                'reports_table.report_id',

                'reports_table.report_unlisted_equipment_name',

                'reports_table.report_problem_description',

                'reports_table.report_suggested_issue',

                'reports_table.report_urgency_level',

                'reports_table.report_replacement_notes',

                'reports_table.report_replacement_image',

                'reports_table.report_submitted_at',

                'equipment_table.equipment_name',

                'equipment_table.equipment_asset_tag',

                'rooms_table.room_name',

                // =====================================================
                // REPORTER INFORMATION HERE
                // =====================================================

                'reporters_table.reporter_full_name',

                'reporters_table.reporter_employee_id',

                'reporters_table.reporter_contact_number',


                // =====================================================
                // MAINTENANCE PERSONNEL WHO SUBMITTED REPLACEMENT HERE
                // =====================================================

                'request_creator.user_full_name
                    as request_creator_name',

                // =====================================================
                // ORIGINAL REPORT IMAGE HERE
                // =====================================================

                'reports_table.report_uploaded_image',

            );


        // =====================================================
        // SEARCH FILTER HERE
        // =====================================================

        if ($request->filled('search')) {

            $query->where(function ($subQuery) use ($request) {

                $subQuery

                    ->where(
                        'procurement_requests_table.procurement_request_id',
                        'LIKE',
                        '%' . $request->search . '%'
                    )

                    ->orWhere(
                        'reports_table.report_id',
                        'LIKE',
                        '%' . $request->search . '%'
                    )

                    ->orWhere(
                        'equipment_table.equipment_name',
                        'LIKE',
                        '%' . $request->search . '%'
                    )

                    ->orWhere(
                        'rooms_table.room_name',
                        'LIKE',
                        '%' . $request->search . '%'
                    );

            });

        }


        // =====================================================
        // STATUS FILTER HERE
        // =====================================================

        if ($request->filled('status')) {

            $query->where(
                'procurement_requests_table.procurement_request_status',
                $request->status
            );

        }


        // =====================================================
        // NEWEST REQUEST FIRST
        // =====================================================

        $replacementRequests = $query

            ->orderByDesc(
                'procurement_requests_table.procurement_request_created_at'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // RETURN REPLACEMENT REQUEST PAGE HERE
        // =====================================================

        return view(
            'purchaser.procurement.replacement-requests',
            compact('replacementRequests')
        );
    }



    // =====================================================
    // SHOW URGENT REPORTS
    // =====================================================

    // =====================================================
    // SHOW URGENT REPORTS
    // =====================================================

    public function urgentReports(Request $request)
    {
        // =====================================================
        // CURRENT PURCHASER USER ID HERE
        // =====================================================

        $purchaserId = Auth::id();


        // =====================================================
        // CHECK ACTIVE OR ARCHIVE VIEW HERE
        //
        // DEFAULT:
        // ACTIVE
        //
        // ?view=archive:
        // ARCHIVED REPORTS
        // =====================================================

        $archiveView =
            $request->get('view') === 'archive';


        // =====================================================
        // BUILD URGENT REPORT QUERY HERE
        // =====================================================

        $query = DB::table('reports_table')


            // =================================================
            // JOIN REPORTER HERE
            // =================================================

            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )


            // =================================================
            // JOIN EQUIPMENT HERE
            // =================================================

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )


            // =================================================
            // JOIN ROOM HERE
            // =================================================

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )


            // =================================================
            // PURCHASER CAN ONLY SEE URGENT REPORTS
            // =================================================

            ->where(
                'reports_table.report_urgency_level',
                'Urgent'
            );


        // =====================================================
        // ARCHIVE VIEW HERE
        //
        // ONLY SHOW:
        //
        // ARCHIVED REPORTS
        // OWNED BY CURRENT PURCHASER
        //
        // IMPORTANT:
        // DO NOT SHOW AVAILABLE REPORTS OR REPORTS OWNED
        // BY ANOTHER USER.
        // =====================================================

        if ($archiveView) {

            $query

                ->where(
                    'reports_table.report_is_archived',
                    1
                )

                ->where(
                    'reports_table.report_assigned_purchaser_id',
                    $purchaserId
                );

        } else {


            // =================================================
            // ACTIVE VIEW HERE
            //
            // ONLY SHOW NON ARCHIVED REPORTS.
            // =================================================

            $query->where(
                'reports_table.report_is_archived',
                0
            );


            // =================================================
            // ACTIVE REPORT VISIBILITY RULE
            //
            // SHOW:
            //
            // 1. AVAILABLE PENDING URGENT REPORTS
            //
            // OR
            //
            // 2. REPORTS OWNED BY CURRENT PURCHASER
            // =================================================

            $query->where(function ($visibilityQuery) use (
                $purchaserId
            ) {

                $visibilityQuery


                    // =============================================
                    // AVAILABLE URGENT REPORTS HERE
                    // =============================================

                    ->where(function ($availableQuery) {

                        $availableQuery

                            ->where(
                                'reports_table.report_current_status',
                                'Pending'
                            )

                            ->whereNull(
                                'reports_table.report_assigned_personnel_id'
                            )

                            ->whereNull(
                                'reports_table.report_assigned_purchaser_id'
                            );

                    })


                    // =============================================
                    // REPORTS OWNED BY CURRENT PURCHASER
                    // =============================================

                    ->orWhere(
                        'reports_table.report_assigned_purchaser_id',
                        $purchaserId
                    );

            });

        }


        // =====================================================
        // SELECT DATA HERE
        // =====================================================

        $query->select(

            'reports_table.*',

            'reporters_table.reporter_full_name',

            'reporters_table.reporter_employee_id',

            'equipment_table.equipment_name',

            'equipment_table.equipment_asset_tag',

            'rooms_table.room_name'

        );


        // =====================================================
        // SEARCH FILTER HERE
        // =====================================================

        if ($request->filled('search')) {

            $search = $request->search;


            $query->where(function ($subQuery) use ($search) {

                $subQuery

                    ->where(
                        'reports_table.report_id',
                        'LIKE',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'equipment_table.equipment_name',
                        'LIKE',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'reports_table.report_unlisted_equipment_name',
                        'LIKE',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'rooms_table.room_name',
                        'LIKE',
                        '%' . $search . '%'
                    )

                    ->orWhere(
                        'reporters_table.reporter_full_name',
                        'LIKE',
                        '%' . $search . '%'
                    );

            });

        }


        // =====================================================
        // STATUS FILTER HERE
        // =====================================================

        if ($request->filled('status')) {

            $query->where(
                'reports_table.report_current_status',
                $request->status
            );

        }


        // =====================================================
        // GET REPORTS HERE
        // =====================================================

        $urgentReports = $query

            ->orderByDesc(
                'reports_table.report_updated_at'
            )

            ->paginate(10)

            ->withQueryString();


        // =====================================================
        // RETURN URGENT REPORT PAGE HERE
        // =====================================================

        return view(
            'purchaser.reports.urgent-reports',
            compact(
                'urgentReports',
                'archiveView'
            )
        );
    }



    // =====================================================
    // PURCHASER ACCEPT URGENT REPORT
    // =====================================================

    public function acceptUrgentReport($reportId)
    {
        // =====================================================
        // CURRENT PURCHASER USER ID HERE
        // =====================================================

        
        $purchaserId = Auth::id();


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (
            $reportId,
            $purchaserId
        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // STOP IF REPORT DOES NOT EXIST
            // =================================================

            abort_if(!$report, 404);


            // =================================================
            // PURCHASER CAN ONLY ACCEPT URGENT REPORTS
            // =================================================

            abort_unless(
                $report->report_urgency_level === 'Urgent',
                403
            );


            // =================================================
            // REPORT MUST NOT BE ARCHIVED
            // =================================================

            if ($report->report_is_archived) {

                return back()->with(
                    'error',
                    'Archived reports cannot be accepted.'
                );

            }


            // =================================================
            // REPORT MUST STILL BE PENDING
            // =================================================

            if (
                $report->report_current_status
                !==
                'Pending'
            ) {

                return back()->with(
                    'error',
                    'This urgent report is no longer available.'
                );

            }


            // =================================================
            // MAINTENANCE MUST NOT OWN REPORT
            // =================================================

            if (
                $report->report_assigned_personnel_id
                !==
                null
            ) {

                return back()->with(
                    'error',
                    'Maintenance personnel is already handling this report.'
                );

            }


            // =================================================
            // PURCHASER MUST NOT ALREADY OWN REPORT
            // =================================================

            if (
                $report->report_assigned_purchaser_id
                !==
                null
            ) {

                return back()->with(
                    'error',
                    'Another purchaser is already handling this report.'
                );

            }


            // =================================================
            // ASSIGN REPORT TO PURCHASER
            // =================================================

            DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->update([

                    'report_current_status' =>
                        'Processing',

                    'report_assigned_purchaser_id' =>
                        $purchaserId,

                    'report_purchaser_assigned_at' =>
                        now(),

                ]);


            // =================================================
            // RETURN SUCCESS MESSAGE
            // =================================================

            return back()->with(
                'success',
                'Urgent report accepted successfully.'
            );

        });
    }



    // =====================================================
    // PURCHASER RESOLVE URGENT REPORT
    // =====================================================

    public function resolveUrgentReport(
        Request $request,
        $reportId
    ) {
        // =====================================================
        // VALIDATE RESOLUTION DATA HERE
        // =====================================================

        $request->validate([

            'resolution_notes' =>
                'nullable|string|max:5000',

            'resolution_image' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

        ]);


        // =====================================================
        // CURRENT PURCHASER USER ID HERE
        // =====================================================

        $purchaserId = Auth::id();


        // =====================================================
        // SAVE RESOLUTION IMAGE BEFORE DATABASE TRANSACTION
        //
        // DO NOT HOLD THE DATABASE ROW LOCK WHILE
        // STORING THE UPLOADED FILE.
        // =====================================================

        $resolutionImagePath = null;


        if ($request->hasFile('resolution_image')) {

            $resolutionImagePath = $request
                ->file('resolution_image')
                ->store(
                    'report-resolutions',
                    'public'
                );

        }


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (

            $request,

            $reportId,

            $purchaserId,

            $resolutionImagePath

        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            //
            // THIS PREVENTS ANOTHER REQUEST FROM CHANGING
            // THE REPORT WHILE WE CHECK AND UPDATE IT.
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // STOP IF REPORT DOES NOT EXIST
            // =================================================

            abort_if(!$report, 404);


            // =================================================
            // REPORT MUST NOT BE ARCHIVED
            // =================================================

            if ($report->report_is_archived) {

                return back()->with(
                    'error',
                    'Archived reports cannot be resolved.'
                );

            }


            // =================================================
            // SECURITY AND OWNERSHIP CHECK HERE
            //
            // REPORT MUST:
            //
            // BE URGENT
            // BE PROCESSING
            // BELONG TO CURRENT PURCHASER
            // NOT BELONG TO MAINTENANCE
            // =================================================

            if (

                $report->report_urgency_level
                    !==
                'Urgent'

                ||

                $report->report_current_status
                    !==
                'Processing'

                ||

                (int) $report->report_assigned_purchaser_id
                    !==
                (int) $purchaserId

                ||

                $report->report_assigned_personnel_id
                    !==
                null

            ) {

                return back()->with(
                    'error',
                    'You cannot resolve this urgent report.'
                );

            }


            // =================================================
            // RESOLVE REPORT HERE
            // =================================================

            DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->update([

                    'report_current_status' =>
                        'Resolved',

                    'report_resolution_notes' =>
                        $request->resolution_notes,

                    'report_resolution_image' =>
                        $resolutionImagePath,

                    'report_updated_at' =>
                        now(),

                ]);


            // =================================================
            // RETURN SUCCESS MESSAGE HERE
            // =================================================

            return back()->with(
                'success',
                'Urgent report resolved successfully.'
            );

        });
    }



    // =====================================================
    // PURCHASER SEND URGENT REPORT FOR REPLACEMENT
    // =====================================================

    public function replaceUrgentReport(
        Request $request,
        $reportId
    ) {
        // =====================================================
        // VALIDATE REPLACEMENT DATA HERE
        // =====================================================

        $request->validate([

            'replacement_notes' =>
                'required|string|max:5000',

            'replacement_image' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

        ]);


        // =====================================================
        // CURRENT PURCHASER USER ID HERE
        // =====================================================

        $purchaserId = Auth::id();


        // =====================================================
        // SAVE REPLACEMENT IMAGE BEFORE DATABASE TRANSACTION
        //
        // DO NOT HOLD THE DATABASE ROW LOCK WHILE
        // STORING THE UPLOADED FILE.
        // =====================================================

        $replacementImagePath = null;


        if ($request->hasFile('replacement_image')) {

            $replacementImagePath = $request
                ->file('replacement_image')
                ->store(
                    'report-replacements',
                    'public'
                );

        }


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (

            $request,

            $reportId,

            $purchaserId,

            $replacementImagePath

        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // STOP IF REPORT DOES NOT EXIST
            // =================================================

            abort_if(!$report, 404);


            // =================================================
            // REPORT MUST NOT BE ARCHIVED
            // =================================================

            if ($report->report_is_archived) {

                return back()->with(
                    'error',
                    'Archived reports cannot be sent for replacement.'
                );

            }


            // =================================================
            // SECURITY AND OWNERSHIP CHECK HERE
            //
            // REPORT MUST:
            //
            // BE URGENT
            // BE PROCESSING
            // BELONG TO CURRENT PURCHASER
            // NOT BELONG TO MAINTENANCE
            // =================================================

            if (

                $report->report_urgency_level
                    !==
                'Urgent'

                ||

                $report->report_current_status
                    !==
                'Processing'

                ||

                (int) $report->report_assigned_purchaser_id
                    !==
                (int) $purchaserId

                ||

                $report->report_assigned_personnel_id
                    !==
                null

            ) {

                return back()->with(
                    'error',
                    'You cannot send this urgent report for replacement.'
                );

            }


            // =================================================
            // UPDATE REPORT HERE
            // =================================================

            DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->update([

                    'report_current_status' =>
                        'For Replacement',

                    'report_replacement_notes' =>
                        $request->replacement_notes,

                    'report_replacement_image' =>
                        $replacementImagePath,

                    'report_replacement_submitted_to_purchaser' =>
                        1,

                    'report_updated_at' =>
                        now(),

                ]);


            // =================================================
            // CHECK IF PROCUREMENT REQUEST ALREADY EXISTS
            // =================================================

            $procurementRequestExists =
                DB::table('procurement_requests_table')

                    ->where(
                        'procurement_request_report_id',
                        $reportId
                    )

                    ->exists();


            // =================================================
            // CREATE PROCUREMENT REQUEST IF NEEDED
            // =================================================

            if (!$procurementRequestExists) {

                DB::table('procurement_requests_table')

                    ->insert([

                        'procurement_request_report_id' =>
                            $reportId,

                        'procurement_request_status' =>
                            'Pending',

                        // =================================================
                        // PURCHASER WHO CREATED PROCUREMENT REQUEST HERE
                        // =================================================

                        'procurement_request_created_by' =>
                            $purchaserId,

                        'procurement_request_created_at' =>
                            now(),

                    ]);

            }


            // =================================================
            // RETURN SUCCESS MESSAGE HERE
            // =================================================

            return back()->with(
                'success',
                'Urgent report sent for replacement successfully.'
            );

        });
    }

    // =====================================================
    // PURCHASER ARCHIVE URGENT REPORT
    // =====================================================

    public function archiveUrgentReport($reportId)
    {
        // =====================================================
        // CURRENT PURCHASER USER ID HERE
        // =====================================================

        $purchaserId = Auth::id();


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (
            $reportId,
            $purchaserId
        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // STOP IF REPORT DOES NOT EXIST
            // =================================================

            abort_if(!$report, 404);


            // =================================================
            // MUST BE AN URGENT REPORT
            // =================================================

            if (
                $report->report_urgency_level
                !==
                'Urgent'
            ) {

                return back()->with(
                    'error',
                    'Only urgent reports can be archived here.'
                );

            }


            // =================================================
            // REPORT MUST BELONG TO CURRENT PURCHASER
            // =================================================

            if (
                (int) $report->report_assigned_purchaser_id
                !==
                (int) $purchaserId
            ) {

                return back()->with(
                    'error',
                    'You can only archive urgent reports assigned to you.'
                );

            }


            // =================================================
            // MAINTENANCE MUST NOT OWN THE REPORT
            // =================================================

            if (
                $report->report_assigned_personnel_id
                !==
                null
            ) {

                return back()->with(
                    'error',
                    'This report belongs to maintenance personnel.'
                );

            }


            // =================================================
            // REPORT MUST BE FINISHED
            //
            // ALLOW:
            //
            // RESOLVED
            // FOR REPLACEMENT
            // =================================================

            if (
                !in_array(
                    $report->report_current_status,
                    [
                        'Resolved',
                        'For Replacement',
                    ],
                    true
                )
            ) {

                return back()->with(
                    'error',
                    'Only completed urgent reports can be archived.'
                );

            }


            // =================================================
            // REPORT MUST NOT ALREADY BE ARCHIVED
            // =================================================

            if ($report->report_is_archived) {

                return back()->with(
                    'error',
                    'This urgent report is already archived.'
                );

            }


            // =================================================
            // ARCHIVE REPORT HERE
            // =================================================

            DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->update([

                    'report_is_archived' =>
                        1,

                    'report_updated_at' =>
                        now(),

                ]);


            // =================================================
            // RETURN SUCCESS MESSAGE HERE
            // =================================================

            return back()->with(
                'success',
                'Urgent report archived successfully.'
            );

        });
    }

    // =====================================================
    // PURCHASER RESTORE ARCHIVED URGENT REPORT
    // =====================================================

    public function restoreUrgentReport($reportId)
    {
        // =====================================================
        // CURRENT PURCHASER USER ID HERE
        // =====================================================

        $purchaserId = Auth::id();


        // =====================================================
        // DATABASE TRANSACTION HERE
        // =====================================================

        return DB::transaction(function () use (
            $reportId,
            $purchaserId
        ) {


            // =================================================
            // LOCK REPORT ROW HERE
            // =================================================

            $report = DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->lockForUpdate()

                ->first();


            // =================================================
            // STOP IF REPORT DOES NOT EXIST
            // =================================================

            abort_if(!$report, 404);


            // =================================================
            // SECURITY CHECK HERE
            //
            // MUST:
            //
            // BE URGENT
            // BELONG TO CURRENT PURCHASER
            // NOT BELONG TO MAINTENANCE
            // BE ARCHIVED
            // =================================================

            if (

                $report->report_urgency_level
                    !==
                'Urgent'

                ||

                (int) $report->report_assigned_purchaser_id
                    !==
                (int) $purchaserId

                ||

                $report->report_assigned_personnel_id
                    !==
                null

                ||

                !$report->report_is_archived

            ) {

                return back()->with(
                    'error',
                    'You cannot restore this urgent report.'
                );

            }


            // =================================================
            // RESTORE REPORT HERE
            // =================================================

            DB::table('reports_table')

                ->where(
                    'report_id',
                    $reportId
                )

                ->update([

                    'report_is_archived' =>
                        0,

                    'report_updated_at' =>
                        now(),

                ]);


            // =================================================
            // RETURN TO ARCHIVE VIEW HERE
            // =================================================

            return redirect()

                ->route(
                    'purchaser.reports.urgent',
                    [
                        'view' =>
                            'archive',
                    ]
                )

                ->with(
                    'success',
                    'Urgent report restored successfully.'
                );

        });
    }
    // =====================================================
    // ADDED RIS MODULE: SHOW RIS DASHBOARD AND LIST
    // =====================================================

    public function risIndex(Request $request)
    {
        // =====================================================
        // ADDED RIS MODULE: GET RIS RECORDS WITH RELATED REQUEST DATA
        // =====================================================

        $risQuery = DB::table('requisition_issue_slip_table')

            ->leftJoin(
                'procurement_requests_table',
                'requisition_issue_slip_table.ris_procurement_request_id',
                '=',
                'procurement_requests_table.procurement_request_id'
            )

            ->leftJoin(
                'reports_table',
                'procurement_requests_table.procurement_request_report_id',
                '=',
                'reports_table.report_id'
            )

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_status',
                'reports_table.report_id',
                'reports_table.report_problem_description',
                'reports_table.report_replacement_notes',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name'
            );

        // =====================================================
        // ADDED RIS MODULE: SEARCH FILTER
        // =====================================================

        if ($request->filled('search')) {

            $risQuery->where(function ($query) use ($request) {

                $query
                    ->where(
                        'requisition_issue_slip_table.ris_form_number',
                        'LIKE',
                        '%' . $request->search . '%'
                    )
                    ->orWhere(
                        'procurement_requests_table.procurement_request_id',
                        'LIKE',
                        '%' . $request->search . '%'
                    )
                    ->orWhere(
                        'equipment_table.equipment_name',
                        'LIKE',
                        '%' . $request->search . '%'
                    )
                    ->orWhere(
                        'reports_table.report_unlisted_equipment_name',
                        'LIKE',
                        '%' . $request->search . '%'
                    );

            });

        }

        // =====================================================
        // ADDED RIS MODULE: STATUS FILTER
        // =====================================================

        if ($request->filled('status')) {

            $risQuery->where(
                'requisition_issue_slip_table.ris_status',
                $request->status
            );

        }

        // =====================================================
        // ADDED RIS MODULE: DATE FILTERS
        // =====================================================

        if ($request->filled('date_from')) {

            $risQuery->whereDate(
                'requisition_issue_slip_table.ris_created_at',
                '>=',
                $request->date_from
            );

        }

        if ($request->filled('date_to')) {

            $risQuery->whereDate(
                'requisition_issue_slip_table.ris_created_at',
                '<=',
                $request->date_to
            );

        }

        // =====================================================
        // ADDED RIS MODULE: PAGINATED RIS LIST
        // =====================================================

        $risRecords = $risQuery
            ->orderByDesc('requisition_issue_slip_table.ris_created_at')
            ->paginate(10)
            ->withQueryString();

        // =====================================================
        // ADDED RIS MODULE: REPLACEMENT REQUESTS WITHOUT RIS YET
        // =====================================================

        $eligibleReplacementRequests = DB::table('procurement_requests_table')

            ->join(
                'reports_table',
                'procurement_requests_table.procurement_request_report_id',
                '=',
                'reports_table.report_id'
            )

            ->leftJoin(
                'equipment_table',
                'reports_table.report_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->leftJoin(
                'rooms_table',
                'reports_table.report_room_id',
                '=',
                'rooms_table.room_id'
            )

            ->leftJoin(
                'requisition_issue_slip_table',
                'procurement_requests_table.procurement_request_id',
                '=',
                'requisition_issue_slip_table.ris_procurement_request_id'
            )

            ->whereNull('requisition_issue_slip_table.ris_id')

            ->where(
                'reports_table.report_current_status',
                'For Replacement'
            )

            ->select(
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_status',
                'procurement_requests_table.procurement_request_created_at',
                'reports_table.report_id',
                'reports_table.report_problem_description',
                'reports_table.report_replacement_notes',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'rooms_table.room_name'
            )

            ->orderByDesc(
                'procurement_requests_table.procurement_request_created_at'
            )

            ->limit(10)

            ->get();

        // =====================================================
        // ADDED RIS MODULE: DASHBOARD COUNTS
        // =====================================================

        $risSummary = [
            'total' => DB::table('requisition_issue_slip_table')->count(),
            'draft' => DB::table('requisition_issue_slip_table')->where('ris_status', 'Pending')->whereNull('ris_requested_by_date')->count(),
            'submitted' => DB::table('requisition_issue_slip_table')->where('ris_status', 'Pending')->whereNotNull('ris_requested_by_date')->count(),
            'approved' => DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Approved')
                ->count(),
            'rejected' => DB::table('requisition_issue_slip_table')
                ->where('ris_status', 'Rejected')
                ->count(),
        ];

        // =====================================================
        // ADDED RIS MODULE: RETURN RIS INDEX PAGE
        // =====================================================

        return view(
            'purchaser.ris.index',
            compact(
                'risRecords',
                'eligibleReplacementRequests',
                'risSummary'
            )
        );
    }


    // =====================================================
    // ADDED RIS MODULE: CREATE RIS FROM REPLACEMENT REQUEST
    // =====================================================

    public function storeRis(Request $request)
    {
        // =====================================================
        // ADDED RIS MODULE: VALIDATE RIS FORM INPUT WITH MULTIPLE ITEMS
        // =====================================================

        $validated = $request->validate([
            'procurement_request_id' => ['required', 'integer', 'exists:procurement_requests_table,procurement_request_id'],
            'ris_purpose_description' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity_requested' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        // =====================================================
        // ADDED RIS MODULE: REMOVE BLANK ITEM ROWS
        // =====================================================

        $items = collect($validated['items'])
            ->filter(function ($item) {
                return filled($item['description'] ?? null);
            })
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Please add at least one RIS item.');
        }

        // =====================================================
        // ADDED RIS MODULE: CREATE RIS SAFELY AND PREVENT DUPLICATES
        // =====================================================

        return DB::transaction(function () use ($validated, $items) {
            $replacementRequest = DB::table('procurement_requests_table')
                ->join('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
                ->where('procurement_requests_table.procurement_request_id', $validated['procurement_request_id'])
                ->where('reports_table.report_current_status', 'For Replacement')
                ->lockForUpdate()
                ->first();

            if (!$replacementRequest) {
                return back()->with('error', 'Only reports marked For Replacement can have an RIS.');
            }

            $existingRis = DB::table('requisition_issue_slip_table')
                ->where('ris_procurement_request_id', $validated['procurement_request_id'])
                ->exists();

            if ($existingRis) {
                return back()->with('error', 'This replacement request already has an RIS.');
            }

            $risId = DB::table('requisition_issue_slip_table')
                ->insertGetId([
                    'ris_procurement_request_id' => $validated['procurement_request_id'],
                    'ris_purpose_description' => $validated['ris_purpose_description'],
                    'ris_status' => 'Pending',
                    'ris_created_at' => now(),
                ]);

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_form_number' => 'RIS-' . now()->format('Y') . '-' . str_pad($risId, 5, '0', STR_PAD_LEFT),
                ]);

            foreach ($items as $item) {
                $quantityRequested = $item['quantity_requested'] ?? 1;
                $unitCost = $item['unit_cost'] ?? 0;

                DB::table('requisition_issue_slip_items_table')
                    ->insert([
                        'ris_id' => $risId,
                        'ris_item_name_description' => $item['description'],
                        'ris_quantity_requested' => $quantityRequested,
                        'ris_quantity_issued' => 0,
                        'ris_unit_cost' => $unitCost,
                        'ris_total_amount' => $unitCost * $quantityRequested,
                    ]);
            }

            return redirect()
                ->route('purchaser.ris.index')
                ->with('success', 'RIS created successfully.');
        });
    }

    // =====================================================
    // ADDED RIS MODULE: SUBMIT RIS TO ADMIN USING EXISTING COLUMNS
    // =====================================================

    public function submitRis($risId)
    {
        // =====================================================
        // ADDED RIS MODULE: EXISTING SQL HAS NO SUBMITTED STATUS,
        // SO ADMIN QUEUE USES PENDING STATUS WITH REQUESTED DATE FILLED
        // =====================================================

        return DB::transaction(function () use ($risId) {

            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            if ($ris->ris_status !== 'Pending') {

                return back()->with(
                    'error',
                    'Only pending RIS records can be submitted.'
                );

            }

            if ($ris->ris_requested_by_date !== null) {

                return back()->with(
                    'error',
                    'This RIS has already been submitted to Admin.'
                );

            }

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_requested_by_signature' =>
                        Auth::user()->user_full_name ?? 'Purchaser',
                    'ris_requested_by_date' =>
                        now()->toDateString(),
                ]);

            return back()->with(
                'success',
                'RIS submitted to Admin for approval.'
            );

        });
    }

    // =====================================================
    // ADDED RIS MODULE: SHOW DEDICATED PRINTABLE RIS FORM
    // =====================================================

    public function printRis($risId)
    {
        // =====================================================
        // ADDED RIS MODULE: LOAD RIS HEADER FOR PRINT FORM
        // =====================================================

        $ris = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            )
            ->where('requisition_issue_slip_table.ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        // =====================================================
        // ADDED RIS MODULE: LOAD RIS ITEMS FOR PRINT FORM
        // =====================================================

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get();

        return view('purchaser.ris.print', compact('ris', 'risItems'));
    }

    // =====================================================
    // END ADDED RIS MODULE
    // =====================================================
}


