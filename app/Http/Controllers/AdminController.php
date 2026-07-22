<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CampusSetupSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function dashboard(): View
    {
        return view('admin.dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | Procurement Review
    |--------------------------------------------------------------------------
    */

    public function procurementReview(Request $request): View
{
    // =====================================================
    // PROCUREMENT REVIEW
    // =====================================================

    // Get selected status filter.
    // Default is "all".
    $filter = strtolower($request->query('filter', 'all'));

    // Get live search value.
    $search = trim($request->query('search', ''));

    // Only allow these filter values.
    if (!in_array($filter, ['all', 'pending', 'approved', 'rejected', 'direct_approved'], true)) {
        $filter = 'all';
    }


    // =====================================================
    // BASE RIS QUERY
    // =====================================================

    $baseQuery = DB::table('requisition_issue_slip_table')

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

        // =====================================================
        // LEFT JOIN RIS ITEMS SUBQUERY
        // Computes the total amount from RIS items
        // (SUM of ris_total_amount per ris_id)
        // =====================================================

        ->leftJoin(
            DB::raw('(SELECT ris_id, SUM(COALESCE(ris_total_amount, 0)) as ris_calculated_total FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_sum'),
            'requisition_issue_slip_table.ris_id',
            '=',
            'ris_items_sum.ris_id'
        )

        // =====================================================
        // LEFT JOIN RIS ITEMS SUBQUERY
        // Gets the concatenated item names/descriptions per RIS
        // =====================================================

        ->leftJoin(
            DB::raw('(SELECT ris_id, GROUP_CONCAT(COALESCE(ris_item_name_description, "N/A") SEPARATOR ", ") as ris_item_names FROM requisition_issue_slip_items_table GROUP BY ris_id) as ris_items_names'),
            'requisition_issue_slip_table.ris_id',
            '=',
            'ris_items_names.ris_id'
        )

        ->select(
            'requisition_issue_slip_table.*',
            'procurement_requests_table.procurement_request_id',
            'reports_table.report_id',
            'reports_table.report_unlisted_equipment_name',
            'equipment_table.equipment_name',
            'ris_items_sum.ris_calculated_total',
            'ris_items_names.ris_item_names'
        )

        // Only RIS forms already submitted by Purchaser.
        ->whereNotNull(
            'requisition_issue_slip_table.ris_requested_by_date'
        );


    // =====================================================
    // DASHBOARD CARD COUNTS
    // These counts are NOT affected by the selected filter.
    // =====================================================

    $totalRis = (clone $baseQuery)->count();

    $pendingRis = (clone $baseQuery)
        ->where(
            'requisition_issue_slip_table.ris_status',
            'Pending'
        )
        ->count();

    // "Amend" is only the UI name.
    // Database still uses "Rejected".
    $amendRis = (clone $baseQuery)
        ->where(
            'requisition_issue_slip_table.ris_status',
            'Rejected'
        )
        ->count();

    // "Approved for President" = Approved AND has approved_by_date
    $approvedRis = (clone $baseQuery)
        ->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNotNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        )
        ->count();

    // "Direct Approval" = Approved BUT NULL approved_by_date
    $directApprovedRis = (clone $baseQuery)
        ->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        )
        ->count();


    // =====================================================
    // TABLE QUERY
    // =====================================================

    $query = clone $baseQuery;


    // =====================================================
    // STATUS FILTER
    // =====================================================

    if ($filter === 'pending') {

        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Pending'
        );

    } elseif ($filter === 'approved') {

        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNotNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        );

    } elseif ($filter === 'direct_approved') {

        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Approved'
        )
        ->whereNull(
            'requisition_issue_slip_table.ris_approved_by_date'
        );

    } elseif ($filter === 'rejected') {

        // Database still stores Rejected.
        // The Blade page displays this as Amend.
        $query->where(
            'requisition_issue_slip_table.ris_status',
            'Rejected'
        );

    } else {

        // All statuses shown by this Procurement Review page.
        $query->whereIn(
            'requisition_issue_slip_table.ris_status',
            [
                'Pending',
                'Approved',
                'Rejected',
            ]
        );
    }


    // =====================================================
    // SEARCH
    // =====================================================

    if ($search !== '') {

        $query->where(function ($searchQuery) use ($search) {

            $searchQuery

                // Search RIS number.
                ->where(
                    'requisition_issue_slip_table.ris_form_number',
                    'like',
                    '%' . $search . '%'
                )

                // Search person who sent/requested the RIS.
                ->orWhere(
                    'requisition_issue_slip_table.ris_requested_by_signature',
                    'like',
                    '%' . $search . '%'
                )

                // Search equipment from equipment table.
                ->orWhere(
                    'equipment_table.equipment_name',
                    'like',
                    '%' . $search . '%'
                )

                // Search manually entered/unlisted equipment.
                ->orWhere(
                    'reports_table.report_unlisted_equipment_name',
                    'like',
                    '%' . $search . '%'
                )

                // Search status.
                ->orWhere(
                    'requisition_issue_slip_table.ris_status',
                    'like',
                    '%' . $search . '%'
                );

        });
    }


    // =====================================================
    // SORTING
    //
    // Pending RIS always appear first in All.
    // Newest Pending RIS appear first.
    // =====================================================

    $risRecords = $query

        ->orderByRaw("
            CASE
                WHEN requisition_issue_slip_table.ris_status = 'Pending' THEN 0
                WHEN requisition_issue_slip_table.ris_status = 'Approved' THEN 1
                WHEN requisition_issue_slip_table.ris_status = 'Rejected' THEN 2
                ELSE 3
            END
        ")

        ->orderByDesc(
            'requisition_issue_slip_table.ris_requested_by_date'
        )

        ->orderByDesc(
            'requisition_issue_slip_table.ris_id'
        )

        // Exactly 10 RIS requests per page.
        ->paginate(10)

        // Preserve filter and search when changing pages.
        ->appends([
            'filter' => $filter,
            'search' => $search,
        ]);


    // =====================================================
    // RETURN VIEW
    // =====================================================

    // AJAX requests return only the content partial
    // so the page does not fully reload.
    if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {

        return view(
            'admin.procurement-review._content',
            compact(
                'risRecords',
                'filter',
                'search',
                'totalRis',
                'pendingRis',
                'amendRis',
                'approvedRis',
                'directApprovedRis'
            )
        );

    }

    return view(
        'admin.procurement-review.index',
        compact(
            'risRecords',
            'filter',
            'search',
            'totalRis',
            'pendingRis',
            'amendRis',
            'approvedRis',
            'directApprovedRis'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | Digital Signatures
    |--------------------------------------------------------------------------
    */

    public function signRis(): View
    {
        // Get RIS records approved by Admin but not yet signed by Admin (for signature)
        $signableRisRecords = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name'
            )
            ->where('requisition_issue_slip_table.ris_status', 'Approved')
            ->whereNotNull('requisition_issue_slip_table.ris_approved_by_date')
            ->whereNull('requisition_issue_slip_table.ris_issued_by_date')
            ->orderByDesc('requisition_issue_slip_table.ris_approved_by_date')
            ->get();

        return view('admin.digital-signatures.sign-ris', compact('signableRisRecords'));
    }

    public function signatureHistory(): View
    {
        // Get RIS records that have been signed by Admin
        $signatureHistory = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->select(
                'requisition_issue_slip_table.*',
                'procurement_requests_table.procurement_request_id',
                'reports_table.report_id',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name'
            )
            ->whereNotNull('requisition_issue_slip_table.ris_issued_by_date')
            ->orderByDesc('requisition_issue_slip_table.ris_issued_by_date')
            ->paginate(10);

        return view('admin.digital-signatures.signature-history', compact('signatureHistory'));
    }

    // =====================================================
    // ADMIN RIS DECISION WITH DIGITAL SIGNATURE
    // =====================================================

    public function decideRis(Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        // Basic validation
        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid RIS decision payload.');
        }

        $target = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->first();

        if (!$target) {
            return back()->with('error', 'RIS not found.');
        }

        // Check if RIS is eligible for signing
        if ($target->ris_status !== 'Approved' || empty($target->ris_approved_by_date)) {
            return back()->with('error', 'Only RIS records approved by President can be signed.');
        }

        $updateValues = [
            'ris_status' => $decision === 'Approved' ? 'Approved' : 'Rejected',
        ];

        if ($decision === 'Approved') {
            $signatureData = $request->input('signature_data');
            if (empty($signatureData)) {
                return back()->with('error', 'Admin signature is required to approve the RIS.');
            }
            $updateValues['ris_issued_by_signature'] = $signatureData;
            $updateValues['ris_issued_by_date'] = now()->toDateString();
        } else {
            // For rejection, just update status
            $updateValues['ris_status'] = 'Rejected';
        }

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->update($updateValues);

        // Log the activity to approval logs
        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $targetId,
                'approval_log_level' => 'Admin',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => $decision,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        return redirect('/admin/digital-signatures/sign-ris')->with('success', 'RIS decision saved successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    public function notifications(): View
    {
        return view('admin.notifications.index');
    }

    public function createNotification(): View
    {
        return view('admin.notifications.create');
    }

    public function viewNotification(): View
    {
        return view('admin.notifications.view');
    }

    public function sentNotificationHistory(): View
    {
        return view('admin.notifications.sent-history');
    }

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    public function users(): View
    {
        return view('admin.users.index');
    }

    public function createUser(): View
    {
        return view('admin.users.create');
    }

    public function editUser(): View
    {
        return view('admin.users.edit');
    }

    public function viewUser(): View
    {
        return view('admin.users.view');
    }

    public function resetPassword(): View
    {
        return view('admin.users.reset-password');
    }

    public function userActivityLogs(): View
    {
        return view('admin.users.activity-logs');
    }

    /*
    |--------------------------------------------------------------------------
    | Store User
    |--------------------------------------------------------------------------
    */

    public function storeUser(Request $request)
    {
        User::create([

            // users_table.user_role_id
            'user_role_id' => $request->role,

            // users_table.user_employee_id
            'user_employee_id' => $request->employee_id,

            // users_table.user_username
            'user_username' => $request->username,

            // users_table.user_full_name
            'user_full_name' => $request->full_name,

            // users_table.user_email_address
            'user_email_address' => $request->email,

            // users_table.user_contact_number
            'user_contact_number' => $request->contact_number,

            // users_table.user_password
            'user_password' => Hash::make($request->password)

        ]);

        return redirect('/admin/users');
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function approvalLogs(): View
    {
        return view('admin.reports.approval-logs');
    }

    public function auditLogs(): View
    {
        return view('admin.reports.audit-logs');
    }

    public function maintenanceHistory(): View
    {
        return view('admin.reports.maintenance-history');
    }

    public function procurementHistory(): View
    {
        return view('admin.reports.procurement-history');
    }

    public function userLoginLogs(): View
    {
        return view('admin.reports.user-login-logs');
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    public function campusSetupPin(): View
    {
        $setting = CampusSetupSetting::query()->first();

        return view('admin.settings.campus-setup-pin', [
            'setting' => $setting,
        ]);
    }

    public function maintenanceSettings(): View
    {
        return view('admin.settings.maintenance-settings');
    }

    public function notificationSettings(): View
    {
        return view('admin.settings.notification-settings');
    }

    public function systemSettings(): View
    {
        return view('admin.settings.system-settings');
    }

    public function updateCampusSetupPin(Request $request)
    {
        $setting = CampusSetupSetting::query()->first() ?? new CampusSetupSetting();

        $rules = [
            'campus_setup_pin' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ];

        if (!empty($setting->campus_setup_pin_hash)) {
            $rules['current_campus_setup_pin'] = ['required', 'string', 'min:4', 'max:20'];
        }

        $validated = $request->validate($rules, [
            'current_campus_setup_pin.required' => 'Please enter the current PIN before saving a new one.',
            'campus_setup_pin.confirmed' => 'The new PIN confirmation does not match.',
        ]);

        if (!empty($setting->campus_setup_pin_hash)) {
            if (!Hash::check((string) $validated['current_campus_setup_pin'], (string) $setting->campus_setup_pin_hash)) {
                return back()
                    ->withErrors([
                        'current_campus_setup_pin' => 'The current PIN is incorrect.',
                    ])
                    ->withInput();
            }
        }

        $setting->campus_setup_pin_hash = Hash::make($validated['campus_setup_pin']);
        $setting->campus_setup_pin_updated_by = Auth::id();
        $setting->campus_setup_pin_updated_at = now();
        $setting->save();

        return back()->with('success', 'Campus setup PIN updated successfully.');
    }
    // =====================================================
    // ADDED RIS ADMIN APPROVAL: SHOW SUBMITTED RIS RECORDS
    // =====================================================

    public function risApprovals(Request $request): View
{
    return $this->procurementReview($request);
}

    // =====================================================
    // ADDED RIS ADMIN APPROVAL: APPROVE RIS
    // =====================================================

    public function approveRis($risId)
    {
        return DB::transaction(function () use ($risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            if ($ris->ris_status !== 'Pending' || !$ris->ris_requested_by_date) {
                return back()->with('error', 'Only submitted pending RIS records can be approved.');
            }

            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Approved',
                    'ris_approved_by_date' => now()->toDateString(),
                ]);

            return back()->with('success', 'RIS approved and forwarded to the President for final approval.');
        });
    }

    public function directApproveRis($risId)
{
    return DB::transaction(function () use ($risId) {

        // =====================================================
        // GET AND LOCK RIS
        // =====================================================

        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->lockForUpdate()
            ->first();

        abort_if(!$ris, 404);

        // =====================================================
        // VALIDATE RIS
        // Only submitted Pending RIS can be directly approved
        // =====================================================

        if (
            $ris->ris_status !== 'Pending' ||
            empty($ris->ris_requested_by_date)
        ) {
            return back()->with(
                'error',
                'Only submitted pending RIS records can be directly approved.'
            );
        }

        // =====================================================
        // DIRECTLY APPROVE RIS
        //
        // Do NOT fill ris_approved_by_date here.
        // The normal Sign RIS queue requires that date.
        // =====================================================

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->update([
                'ris_status' => 'Approved',
            ]);

        // =====================================================
        // APPROVAL LOG
        // Keep a record that this was a Direct Approval
        // =====================================================

        try {

            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $risId,
                'approval_log_level' => 'Admin Direct Approval',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => 'Approved',
                'approval_log_approval_remarks' => 'RIS directly approved and returned to Purchaser.',
                'approval_log_approved_at' => now(),
            ]);

        } catch (\Throwable $e) {

            // Approval must still succeed even if logging fails.

        }

        // =====================================================
        // RETURN TO PROCUREMENT REQUEST TABLE
        // =====================================================

        return redirect()
            ->route('admin.procurement-review.ris', [
                'filter' => 'all'
            ])
            ->with(
                'success',
                'RIS directly approved successfully and returned to the Purchaser.'
            );
    });
}

    // =====================================================
    // ADDED RIS ADMIN APPROVAL: REJECT RIS
    // =====================================================

    public function rejectRis($risId)
    {
        return DB::transaction(function () use ($risId) {
            $ris = DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->lockForUpdate()
                ->first();

            abort_if(!$ris, 404);

            if ($ris->ris_status !== 'Pending' || !$ris->ris_requested_by_date) {
                return back()->with('error', 'Only submitted pending RIS records can be rejected.');
            }

            // Reset submission fields so the Purchaser can edit and resubmit
            DB::table('requisition_issue_slip_table')
                ->where('ris_id', $risId)
                ->update([
                    'ris_status' => 'Pending',
                    'ris_requested_by_signature' => null,
                    'ris_requested_by_date' => null,
                    'ris_submitted_by' => null,
                    'ris_submitted_at' => null,
                ]);

            return back()->with('success', 'RIS returned to Purchaser for amendment.');
        });
    }
}

