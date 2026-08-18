<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\WorkflowNotifier;
use App\Support\RisWorkflow;

class AccountingController extends Controller
{
    private const LIQ_INCOMING = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];

    public function dashboard()
    {
        $metrics = $this->metrics();

        $incomingAtp = Schema::hasTable('authority_to_purchase_table')
            ? $this->atpQuery()
                ->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at')
                ->orderByDesc('authority_to_purchase_table.authority_purchase_submitted_at')
                ->limit(6)
                ->get()
            : collect();

        $incomingRfc = Schema::hasTable('request_check_table')
            ? $this->rfcQuery()
                ->whereIn('request_check_table.request_check_status', $this->rfcIncomingStatuses())
                ->orderByDesc($this->rfcSortColumn())
                ->limit(6)
                ->get()
            : collect();

        $awaitingFunds = collect();
        if (Schema::hasTable('request_check_table') && $this->rfcHas('request_check_funds_released_at')) {
            $fundsQuery = $this->rfcQuery()
                ->where('request_check_table.request_check_status', 'Approved')
                ->whereNull('request_check_table.request_check_funds_released_at');
            $fundsQuery->orderByDesc($this->rfcHas('request_check_approved_at')
                ? 'request_check_table.request_check_approved_at'
                : $this->rfcSortColumn());
            $awaitingFunds = $fundsQuery->limit(6)->get();
        }

        $incomingLiq = Schema::hasTable('liquidation_reports_table')
            ? $this->liqQuery()
                ->whereIn('liquidation_reports_table.liquidation_report_status', self::LIQ_INCOMING)
                ->orderByDesc($this->liqSortColumn())
                ->limit(6)
                ->get()
            : collect();

        $queue = collect();
        foreach ($incomingAtp as $row) {
            $queue->push((object) [
                'type' => 'ATP',
                'ref' => $row->authority_purchase_form_number ?: ('ATP-' . $row->authority_purchase_id),
                'related' => $row->ris_form_number ?? '—',
                'who' => $row->company_name ?? $row->shop_name ?? '—',
                'amount' => $row->atp_total ?? null,
                'when' => $row->authority_purchase_submitted_at ?? $row->authority_purchase_created_at,
                'status' => 'Pending',
                'action' => 'Review',
                'url' => '/accounting/authority-to-purchase/' . $row->authority_purchase_id,
            ]);
        }
        foreach ($incomingRfc as $row) {
            $queue->push((object) [
                'type' => 'Request Check',
                'ref' => $row->request_check_form_number ?? ('RFC-' . $row->request_check_id),
                'related' => $row->ris_form_number ?? ($row->authority_purchase_form_number ?? '—'),
                'who' => $row->request_check_payee ?? $row->request_check_requested_by ?? '—',
                'amount' => $row->request_check_amount_figures ?? null,
                'when' => $row->request_check_submitted_at ?? $row->request_check_created_at ?? $row->request_check_date,
                'status' => $row->request_check_status,
                'action' => 'Review',
                'url' => '/accounting/request-check/' . $row->request_check_id,
            ]);
        }
        foreach ($awaitingFunds as $row) {
            $queue->push((object) [
                'type' => 'Funds',
                'ref' => $row->request_check_form_number ?? ('RFC-' . $row->request_check_id),
                'related' => $row->ris_form_number ?? ($row->authority_purchase_form_number ?? '—'),
                'who' => $row->request_check_payee ?? '—',
                'amount' => $row->request_check_amount_figures ?? null,
                'when' => $row->request_check_approved_at ?? $row->request_check_created_at,
                'status' => 'Approved',
                'action' => 'Release funds',
                'url' => '/accounting/request-check/' . $row->request_check_id,
            ]);
        }
        foreach ($incomingLiq as $row) {
            $queue->push((object) [
                'type' => 'Liquidation',
                'ref' => $row->liquidation_report_form_number ?? ('LIQ-' . $row->liquidation_report_id),
                'related' => $row->receiving_report_form_number ?? '—',
                'who' => $row->liquidation_report_employee_name ?? '—',
                'amount' => $row->liquidation_report_amount_advance ?? null,
                'when' => $row->liquidation_report_submitted_at ?? $row->liquidation_report_date_submitted ?? $row->liquidation_report_created_at,
                'status' => $row->liquidation_report_status,
                'action' => 'Review',
                'url' => '/accounting/liquidation-reports/' . $row->liquidation_report_id,
            ]);
        }
        $queue = $queue->sortByDesc('when')->values();

        $recentActivity = collect();
        if (Schema::hasTable('approval_logs_table')) {
            try {
                $recentActivity = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                    ->where('approval_log_level', 'Accounting')
                    ->orderByDesc('approval_log_approved_at')
                    ->limit(8)
                    ->select('approval_logs_table.*', 'users_table.user_full_name')
                    ->get();
            } catch (\Throwable $e) {
                $recentActivity = collect();
            }
        }

        return view('accounting.dashboard', compact(
            'metrics',
            'incomingAtp',
            'incomingRfc',
            'awaitingFunds',
            'incomingLiq',
            'queue',
            'recentActivity'
        ));
    }

    public function authorityToPurchase(Request $request)
    {
        $filter = $request->query('status', 'incoming');
        $query = $this->atpQuery()->where(function ($q) {
            $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
        });

        if ($filter === 'incoming') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at');
        } elseif ($filter === 'revision') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNull('authority_to_purchase_table.authority_purchase_submitted_at')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_rejection_reason');
        } elseif ($filter === 'approved') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Approved');
        } elseif ($filter === 'rejected') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Rejected');
        }

        $this->applySearch($query, $request, [
            'authority_to_purchase_table.authority_purchase_form_number',
            'requisition_issue_slip_table.ris_form_number',
            'physical_suppliers_table.company_name',
            'online_suppliers_table.shop_name',
        ]);

        $records = $query->orderByDesc('authority_to_purchase_table.authority_purchase_updated_at')
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'incoming' => $this->countAtpIncoming(),
            'revision' => $this->countAtpRevision(),
            'approved' => $this->countAtpApproved(),
        ];

        return view('accounting.authority-to-purchase.index', compact('records', 'filter', 'counts'));
    }

    public function showAtp($id)
    {
        $atp = $this->atpQuery()
            ->where('authority_to_purchase_table.authority_purchase_id', $id)
            ->first();
        abort_if(!$atp, 404);

        $items = Schema::hasTable('authority_to_purchase_items_table')
            ? DB::table('authority_to_purchase_items_table')->where('authority_purchase_id', $id)->orderBy('atp_item_id')->get()
            : collect();

        $chain = $this->chainFromAtp((int) $id);
        $history = $this->documentHistory('ATP', (int) $id);
        $reviewable = $atp->authority_purchase_status === 'Pending' && $atp->authority_purchase_submitted_at !== null;

        return view('accounting.authority-to-purchase.show', compact('atp', 'items', 'chain', 'history', 'reviewable'));
    }

    public function approveAtp(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
        $atp = $this->lockAtp($id);
        abort_if(!$atp, 404);

        if ($atp->authority_purchase_status !== 'Pending' || $atp->authority_purchase_submitted_at === null) {
            return back()->with('error', 'Only submitted ATP records can be approved.');
        }

        $name = Auth::user()->user_full_name ?? Auth::user()->name ?? 'Accounting';
        DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->update([
            'authority_purchase_status' => 'Approved',
            'authority_purchase_authorized_by_signature' => RisWorkflow::drawnOrName($request->input('signature_data'), $name),
            'authority_purchase_rejection_reason' => null,
            'authority_purchase_updated_at' => now(),
        ]);

        $this->log('ATP', (int) $id, 'Approved', 'ATP approved. Purchaser may proceed to Request Check.');
        $this->notifyPurchaser(
            $atp->authority_purchase_submitted_by,
            'ATP approved',
            ($atp->authority_purchase_form_number ?: ('ATP #' . $id)) . ' was approved by Accounting. You may create a Request Check.',
            'atp_approved',
            'ATP',
            (int) $id,
            '/purchaser/request-check?selected_atp=' . (int) $id
        );

        return redirect('/accounting/authority-to-purchase/' . $id)->with('success', 'ATP approved. Purchaser has been notified.');
        });
    }

    public function reviseAtp(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        $atp = $this->lockAtp($id);
        abort_if(!$atp, 404);

        if ($atp->authority_purchase_status !== 'Pending' || $atp->authority_purchase_submitted_at === null) {
            return back()->with('error', 'Only submitted ATP records can be sent back for revision.');
        }

        DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->update([
            'authority_purchase_rejection_reason' => $validated['remarks'],
            'authority_purchase_submitted_at' => null,
            'authority_purchase_updated_at' => now(),
        ]);

        $this->log('ATP', (int) $id, 'Under Review', $validated['remarks']);
        $this->notifyPurchaser(
            $atp->authority_purchase_submitted_by,
            'ATP revision required',
            ($atp->authority_purchase_form_number ?: ('ATP #' . $id)) . ': ' . $validated['remarks'],
            'atp_revision',
            'ATP',
            (int) $id,
            '/purchaser/authority-to-purchase'
        );

        return redirect('/accounting/authority-to-purchase')->with('success', 'Revision requested. Purchaser has been notified.');
    }

    public function requestCheck(Request $request)
    {
        $filter = $request->query('status', 'incoming');
        $query = $this->rfcQuery();
        if ($this->rfcHas('request_check_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });
        }

        if ($filter === 'incoming') {
            $query->whereIn('request_check_table.request_check_status', $this->rfcIncomingStatuses());
        } elseif ($filter === 'funds') {
            $query->where('request_check_table.request_check_status', 'Approved');
            if ($this->rfcHas('request_check_funds_released_at')) {
                $query->whereNull('request_check_table.request_check_funds_released_at');
            }
        } elseif ($filter === 'released') {
            if ($this->rfcHas('request_check_funds_released_at')) {
                $query->whereNotNull('request_check_table.request_check_funds_released_at');
            } else {
                $query->whereRaw('0 = 1');
            }
        } elseif ($filter === 'revision') {
            $query->whereIn('request_check_table.request_check_status', $this->rfcRevisionStatuses());
        } elseif ($filter === 'approved') {
            $query->where('request_check_table.request_check_status', 'Approved');
        }

        $searchCols = ['request_check_table.request_check_payee'];
        if ($this->rfcHas('request_check_form_number')) {
            array_unshift($searchCols, 'request_check_table.request_check_form_number');
        }
        $searchCols[] = 'authority_to_purchase_table.authority_purchase_form_number';
        $searchCols[] = 'requisition_issue_slip_table.ris_form_number';
        $this->applySearch($query, $request, $searchCols);

        $records = $query->orderByDesc($this->rfcSortColumn())->paginate(12)->withQueryString();
        $counts = [
            'incoming' => $this->countRfcIncoming(),
            'funds' => $this->countFundsAwaiting(),
            'released' => $this->countFundsReleased(),
        ];

        return view('accounting.request-check.index', compact('records', 'filter', 'counts'));
    }

    public function showRequestCheck($id)
    {
        $rfc = $this->rfcQuery()->where('request_check_table.request_check_id', $id)->first();
        abort_if(!$rfc, 404);

        if (in_array($rfc->request_check_status, ['Submitted', 'Resubmitted'], true) && $this->rfcStatusAllowed('Under Review')) {
            $this->rfcUpdate($id, [
                'request_check_status' => 'Under Review',
                'request_check_review_stage' => 'accounting',
                'request_check_updated_at' => now(),
            ]);
            $rfc->request_check_status = 'Under Review';
            $this->log('RFC', (int) $id, 'Under Review', 'Opened for Accounting review.');
        }

        $attachments = Schema::hasTable('request_check_attachments_table')
            ? DB::table('request_check_attachments_table')->where('request_check_id', $id)->orderBy('request_check_attachment_id')->get()
            : collect();

        $chain = $this->chainFromAtp((int) ($rfc->request_check_authority_purchase_id ?? 0));
        $history = $this->documentHistory('RFC', (int) $id);
        $reviewable = in_array($rfc->request_check_status, $this->rfcIncomingStatuses(), true);
        $releasable = $rfc->request_check_status === 'Approved'
            && $this->rfcHas('request_check_funds_released_at')
            && empty($rfc->request_check_funds_released_at);

        return view('accounting.request-check.show', compact('rfc', 'attachments', 'chain', 'history', 'reviewable', 'releasable'));
    }

    public function approveRequestCheck(Request $request, $id)
    {
        $rfc = $this->lockRfc($id);
        abort_if(!$rfc, 404);

        if (!in_array($rfc->request_check_status, $this->rfcIncomingStatuses(), true)) {
            return back()->with('error', 'This Request Check is not awaiting Accounting review.');
        }

        $name = Auth::user()->user_full_name ?? Auth::user()->name ?? 'Accounting';
        $signature = RisWorkflow::drawnOrName($request->input('signature_data'), $name);
        $this->rfcUpdate($id, [
            'request_check_status' => 'Approved',
            'request_check_review_stage' => 'accounting',
            'request_check_accounting_verified_by' => Auth::id(),
            'request_check_accounting_verified_at' => now(),
            'request_check_approved_by_user_id' => Auth::id(),
            'request_check_approved_at' => now(),
            'request_check_approved_by_signature' => $signature,
            'request_check_approved_by_admin' => $signature,
            'request_check_updated_at' => now(),
        ]);

        $this->log('RFC', (int) $id, 'Approved', 'Request Check approved. Funds may now be released for collection.');
        $this->notifyPurchaser(
            $rfc->request_check_submitted_by ?? $rfc->request_check_requested_by_user_id,
            'Request Check approved',
            ($rfc->request_check_form_number ?: ('RFC #' . $id)) . ' was approved. Wait for Accounting to release funds, then create a Receiving Report.',
            'rfc_approved',
            'RFC',
            (int) $id,
            '/purchaser/request-check'
        );

        return redirect('/accounting/request-check/' . $id)->with('success', 'Request Check approved.');
    }

    public function reviseRequestCheck(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        $rfc = $this->lockRfc($id);
        abort_if(!$rfc, 404);

        if (!in_array($rfc->request_check_status, $this->rfcIncomingStatuses(), true)) {
            return back()->with('error', 'This Request Check cannot be sent for revision.');
        }

        $revisionStatus = $this->rfcStatusAllowed('Minor Revision') ? 'Minor Revision' : 'Rejected';
        $this->rfcUpdate($id, [
            'request_check_status' => $revisionStatus,
            'request_check_review_stage' => 'purchaser',
            'request_check_revision_notes' => $validated['remarks'],
            'request_check_updated_at' => now(),
        ]);

        $this->log('RFC', (int) $id, 'Minor Revision', $validated['remarks']);
        $this->notifyPurchaser(
            $rfc->request_check_submitted_by ?? $rfc->request_check_requested_by_user_id,
            'Request Check revision required',
            ($rfc->request_check_form_number ?: ('RFC #' . $id)) . ': ' . $validated['remarks'],
            'rfc_revision',
            'RFC',
            (int) $id,
            '/purchaser/request-check'
        );

        return redirect('/accounting/request-check')->with('success', 'Revision requested. Purchaser has been notified.');
    }

    public function releaseFunds($id)
    {
        $rfc = $this->lockRfc($id);
        abort_if(!$rfc, 404);

        if (!$this->rfcHas('request_check_funds_released_at')) {
            return back()->with('error', 'Funds-release columns are not present on this Request Check table.');
        }
        if ($rfc->request_check_status !== 'Approved') {
            return back()->with('error', 'Approve the Request Check before releasing funds.');
        }
        if (!empty($rfc->request_check_funds_released_at)) {
            return back()->with('error', 'Funds for this Request Check were already marked as released.');
        }

        $this->rfcUpdate($id, [
            'request_check_funds_released_at' => now(),
            'request_check_funds_released_by' => Auth::id(),
            'request_check_updated_at' => now(),
        ]);

        $amount = $rfc->request_check_amount_figures !== null
            ? '₱' . number_format((float) $rfc->request_check_amount_figures, 2)
            : 'the approved amount';

        $this->log('RFC', (int) $id, 'Approved', 'Funds released for personal collection (' . $amount . ').');
        $this->notifyPurchaser(
            $rfc->request_check_submitted_by ?? $rfc->request_check_requested_by_user_id,
            'Funds ready for collection',
            ($rfc->request_check_form_number ?: ('RFC #' . $id)) . ' — ' . $amount . ' is ready for personal collection. You may create a Receiving Report.',
            'rfc_funds_released',
            'RFC',
            (int) $id,
            '/purchaser/receiving-reports'
        );

        return redirect('/accounting/request-check/' . $id)->with('success', 'Funds marked as ready for collection. Purchaser notified.');
    }

    public function downloadRfcAttachment($id, $attachmentId)
    {
        abort_unless(Schema::hasTable('request_check_attachments_table'), 404);
        $file = DB::table('request_check_attachments_table')
            ->where('request_check_id', $id)
            ->where('request_check_attachment_id', $attachmentId)
            ->first();
        abort_if(!$file, 404);
        $path = storage_path('app/public/' . $file->request_check_attachment_path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $file->request_check_attachment_original_name . '"',
        ]);
    }

    public function liquidationReports(Request $request)
    {
        $filter = $request->query('status', 'incoming');
        $query = $this->liqQuery();
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('liquidation_reports_table.liquidation_report_is_archived')
                    ->orWhere('liquidation_reports_table.liquidation_report_is_archived', 0);
            });
        }

        if ($filter === 'incoming') {
            $query->whereIn('liquidation_reports_table.liquidation_report_status', self::LIQ_INCOMING);
        } elseif ($filter === 'revision') {
            $query->whereIn('liquidation_reports_table.liquidation_report_status', Schema::hasColumn('liquidation_reports_table', 'liquidation_report_revision_notes') ? ['Minor Revision'] : ['Rejected']);
        } elseif ($filter === 'approved') {
            $query->where('liquidation_reports_table.liquidation_report_status', 'Approved');
        }

        $liqSearch = ['liquidation_reports_table.liquidation_report_employee_name'];
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_form_number')) {
            array_unshift($liqSearch, 'liquidation_reports_table.liquidation_report_form_number');
        }
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')) {
            $liqSearch[] = 'receiving_reports_table.receiving_report_form_number';
        }
        $this->applySearch($query, $request, $liqSearch);

        $records = $query->orderByDesc($this->liqSortColumn())->paginate(12)->withQueryString();
        $counts = [
            'incoming' => $this->countLiqIncoming(),
            'revision' => $this->countLiqRevision(),
            'approved' => $this->countLiqApproved(),
        ];

        return view('accounting.liquidation-reports.index', compact('records', 'filter', 'counts'));
    }

    public function showLiquidation($id)
    {
        $liq = $this->liqQuery()->where('liquidation_reports_table.liquidation_report_id', $id)->first();
        abort_if(!$liq, 404);

        if (in_array($liq->liquidation_report_status, ['Submitted', 'Resubmitted'], true)
            && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_review_stage')) {
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Under Review',
                'liquidation_report_review_stage' => 'accounting',
                'liquidation_report_updated_at' => now(),
            ]);
            $liq->liquidation_report_status = 'Under Review';
            $this->log('LIQ', (int) $id, 'Under Review', 'Opened for Accounting review.');
        }

        $rows = Schema::hasTable('liquidation_report_items_table')
            ? DB::table('liquidation_report_items_table')->where('liquidation_report_id', $id)->orderBy('liquidation_item_id')->get()->values()
            : collect();
        $attachments = Schema::hasTable('liquidation_report_attachments_table')
            ? DB::table('liquidation_report_attachments_table')->where('liquidation_report_id', $id)->orderBy('liquidation_attachment_id')->get()
            : collect();

        $atpId = 0;
        if (!empty($liq->liquidation_report_receiving_report_id) && Schema::hasTable('receiving_reports_table')) {
            $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $liq->liquidation_report_receiving_report_id)->first();
            if ($rr && Schema::hasTable('request_check_table') && !empty($rr->receiving_report_request_check_id)) {
                $rfc = DB::table('request_check_table')->where('request_check_id', $rr->receiving_report_request_check_id)->first();
                $atpId = (int) ($rfc->request_check_authority_purchase_id ?? 0);
            } elseif ($rr && !empty($rr->receiving_report_atp_id)) {
                $atpId = (int) $rr->receiving_report_atp_id;
            }
        }

        $chain = $this->chainFromAtp($atpId);
        $history = $this->documentHistory('LIQ', (int) $id);
        $reviewable = in_array($liq->liquidation_report_status, self::LIQ_INCOMING, true);

        return view('accounting.liquidation-reports.show', compact('liq', 'rows', 'attachments', 'chain', 'history', 'reviewable'));
    }

    public function approveLiquidation(Request $request, $id)
    {
        $liq = $this->lockLiq($id);
        abort_if(!$liq, 404);

        if (!in_array($liq->liquidation_report_status, self::LIQ_INCOMING, true)) {
            return back()->with('error', 'This liquidation report is not awaiting Accounting review.');
        }

        $name = Auth::user()->user_full_name ?? Auth::user()->name ?? 'Accounting';
        $this->liqUpdate($id, [
            'liquidation_report_status' => 'Approved',
            'liquidation_report_review_stage' => 'completed',
            'liquidation_report_checked_by_accountant' => RisWorkflow::drawnOrName($request->input('signature_data'), $name),
            'liquidation_report_checked_by_date' => now()->toDateString(),
            'liquidation_report_updated_at' => now(),
        ]);

        $this->log('LIQ', (int) $id, 'Approved', 'Liquidation approved. Transaction completed.');
        $this->completeLinkedProcurementRequest($liq);
        $this->notifyPurchaser(
            $liq->liquidation_report_submitted_by,
            'Liquidation report approved',
            ($liq->liquidation_report_form_number ?: ('LIQ #' . $id)) . ' was approved. This transaction is complete.',
            'liq_approved',
            'LIQ',
            (int) $id,
            '/purchaser/liquidation-reports'
        );

        return redirect('/accounting/liquidation-reports/' . $id)->with('success', 'Liquidation approved. Transaction completed.');
    }

    public function reviseLiquidation(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        $liq = $this->lockLiq($id);
        abort_if(!$liq, 404);

        if (!in_array($liq->liquidation_report_status, self::LIQ_INCOMING, true)) {
            return back()->with('error', 'This liquidation report cannot be sent for revision.');
        }

        $revisionStatus = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_revision_notes') ? 'Minor Revision' : 'Rejected';
        $this->liqUpdate($id, [
            'liquidation_report_status' => $revisionStatus,
            'liquidation_report_review_stage' => 'purchaser',
            'liquidation_report_revision_notes' => $validated['remarks'],
            'liquidation_report_updated_at' => now(),
        ]);

        $this->log('LIQ', (int) $id, 'Rejected', $validated['remarks']);
        $this->notifyPurchaser(
            $liq->liquidation_report_submitted_by,
            'Liquidation revision required',
            ($liq->liquidation_report_form_number ?: ('LIQ #' . $id)) . ': ' . $validated['remarks'],
            'liq_revision',
            'LIQ',
            (int) $id,
            '/purchaser/liquidation-reports'
        );

        return redirect('/accounting/liquidation-reports')->with('success', 'Revision requested. Purchaser has been notified.');
    }

    public function downloadLiqAttachment($id, $attachmentId)
    {
        abort_unless(Schema::hasTable('liquidation_report_attachments_table'), 404);
        $file = DB::table('liquidation_report_attachments_table')
            ->where('liquidation_report_id', $id)
            ->where('liquidation_attachment_id', $attachmentId)
            ->first();
        abort_if(!$file, 404);
        $path = storage_path('app/public/' . $file->liquidation_attachment_path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $file->liquidation_attachment_original_name . '"',
        ]);
    }

    public function history(Request $request)
    {
        $type = $request->query('type', 'all');
        $search = trim((string) $request->query('search', ''));
        $rows = collect();

        if ($type === 'all' || $type === 'atp') {
            $q = $this->atpQuery()->whereIn('authority_to_purchase_table.authority_purchase_status', ['Approved', 'Rejected']);
            if ($search !== '') {
                $this->applySearch($q, $request, [
                    'authority_to_purchase_table.authority_purchase_form_number',
                    'requisition_issue_slip_table.ris_form_number',
                ]);
            }
            foreach ($q->orderByDesc('authority_to_purchase_table.authority_purchase_updated_at')->limit(40)->get() as $row) {
                $rows->push((object) [
                    'type' => 'ATP',
                    'ref' => $row->authority_purchase_form_number,
                    'related' => $row->ris_form_number,
                    'status' => $row->authority_purchase_status,
                    'amount' => $row->atp_total ?? null,
                    'when' => $row->authority_purchase_updated_at,
                    'url' => '/accounting/authority-to-purchase/' . $row->authority_purchase_id,
                ]);
            }
        }

        if ($type === 'all' || $type === 'rfc') {
            $q = $this->rfcQuery()->where(function ($inner) {
                $inner->where('request_check_table.request_check_status', 'Approved')
                    ->orWhere('request_check_table.request_check_status', 'Rejected');
                if ($this->rfcHas('request_check_funds_released_at')) {
                    $inner->orWhereNotNull('request_check_table.request_check_funds_released_at');
                }
            });
            if ($search !== '') {
                $cols = ['authority_to_purchase_table.authority_purchase_form_number'];
                if ($this->rfcHas('request_check_form_number')) {
                    array_unshift($cols, 'request_check_table.request_check_form_number');
                }
                $this->applySearch($q, $request, $cols);
            }
            foreach ($q->orderByDesc($this->rfcSortColumn())->limit(40)->get() as $row) {
                $status = !empty($row->request_check_funds_released_at ?? null) ? 'Funds released' : $row->request_check_status;
                $rows->push((object) [
                    'type' => 'Request Check',
                    'ref' => $row->request_check_form_number ?? ('RFC-' . $row->request_check_id),
                    'related' => $row->authority_purchase_form_number,
                    'status' => $status,
                    'amount' => $row->request_check_amount_figures,
                    'when' => $row->request_check_updated_at ?? $row->request_check_created_at ?? $row->request_check_date,
                    'url' => '/accounting/request-check/' . $row->request_check_id,
                ]);
            }
        }

        if ($type === 'all' || $type === 'liq') {
            $q = $this->liqQuery()->whereIn('liquidation_reports_table.liquidation_report_status', ['Approved', 'Rejected']);
            if ($search !== '') {
                $liqCols = ['liquidation_reports_table.liquidation_report_employee_name'];
                if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_form_number')) {
                    $liqCols[] = 'liquidation_reports_table.liquidation_report_form_number';
                }
                $this->applySearch($q, $request, $liqCols);
            }
            foreach ($q->orderByDesc($this->liqSortColumn())->limit(40)->get() as $row) {
                $rows->push((object) [
                    'type' => 'Liquidation',
                    'ref' => $row->liquidation_report_form_number ?? ('LIQ-' . $row->liquidation_report_id),
                    'related' => $row->receiving_report_form_number ?? null,
                    'status' => $row->liquidation_report_status,
                    'amount' => $row->liquidation_report_summary_actual_expense ?? $row->liquidation_report_amount_advance,
                    'when' => $row->liquidation_report_updated_at ?? $row->liquidation_report_created_at ?? $row->liquidation_report_date_submitted,
                    'url' => '/accounting/liquidation-reports/' . $row->liquidation_report_id,
                ]);
            }
        }

        $records = $rows->sortByDesc('when')->values();

        return view('accounting.history', compact('records', 'type', 'search'));
    }

    public function reports()
    {
        return view('accounting.reports.index', ['metrics' => $this->metrics()]);
    }

    public function financialRecords(Request $request)
    {
        return $this->history($request);
    }

    public function notifications()
    {
        $items = collect();
        try {
            $items = DB::table('notifications_table')
                ->where(function ($q) {
                    $q->where('notification_user_id', Auth::id())
                        ->orWhere('notification_target_role', 'Accounting');
                })
                ->orderByDesc('notification_created_at')
                ->limit(80)
                ->get();
        } catch (\Throwable $e) {
        }

        return view('accounting.notifications.index', compact('items'));
    }

    private function metrics(): array
    {
        return [
            'atp_pending' => $this->countAtpIncoming(),
            'rfc_pending' => $this->countRfcIncoming(),
            'funds_awaiting' => $this->countFundsAwaiting(),
            'liq_pending' => $this->countLiqIncoming(),
            'atp_approved' => $this->countAtpApproved(),
            'rfc_approved' => $this->countRfcApproved(),
            'liq_approved' => $this->countLiqApproved(),
            'needs_revision' => $this->countAtpRevision() + $this->countRfcRevision() + $this->countLiqRevision(),
        ];
    }

    private function countAtpIncoming(): int
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return 0;
        }
        return (int) DB::table('authority_to_purchase_table')
            ->where('authority_purchase_status', 'Pending')
            ->whereNotNull('authority_purchase_submitted_at')
            ->where(function ($q) {
                $q->whereNull('authority_purchase_is_archived')->orWhere('authority_purchase_is_archived', 0);
            })
            ->count();
    }

    private function countAtpRevision(): int
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return 0;
        }
        return (int) DB::table('authority_to_purchase_table')
            ->where('authority_purchase_status', 'Pending')
            ->whereNull('authority_purchase_submitted_at')
            ->whereNotNull('authority_purchase_rejection_reason')
            ->count();
    }

    private function countAtpApproved(): int
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return 0;
        }
        return (int) DB::table('authority_to_purchase_table')->where('authority_purchase_status', 'Approved')->count();
    }

    private function countRfcIncoming(): int
    {
        if (!Schema::hasTable('request_check_table')) {
            return 0;
        }
        return (int) DB::table('request_check_table')->whereIn('request_check_status', $this->rfcIncomingStatuses())->count();
    }

    private function countRfcRevision(): int
    {
        if (!Schema::hasTable('request_check_table')) {
            return 0;
        }
        return (int) DB::table('request_check_table')->whereIn('request_check_status', $this->rfcRevisionStatuses())->count();
    }

    private function countRfcApproved(): int
    {
        if (!Schema::hasTable('request_check_table')) {
            return 0;
        }
        return (int) DB::table('request_check_table')->where('request_check_status', 'Approved')->count();
    }

    private function countFundsAwaiting(): int
    {
        if (!Schema::hasTable('request_check_table') || !Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            return 0;
        }
        return (int) DB::table('request_check_table')
            ->where('request_check_status', 'Approved')
            ->whereNull('request_check_funds_released_at')
            ->count();
    }

    private function countFundsReleased(): int
    {
        if (!Schema::hasTable('request_check_table') || !Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            return 0;
        }
        return (int) DB::table('request_check_table')->whereNotNull('request_check_funds_released_at')->count();
    }

    private function countLiqIncoming(): int
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return 0;
        }
        return (int) DB::table('liquidation_reports_table')->whereIn('liquidation_report_status', self::LIQ_INCOMING)->count();
    }

    private function countLiqRevision(): int
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return 0;
        }
        return (int) DB::table('liquidation_reports_table')->where('liquidation_report_status', 'Minor Revision')->count();
    }

    private function countLiqApproved(): int
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return 0;
        }
        return (int) DB::table('liquidation_reports_table')->where('liquidation_report_status', 'Approved')->count();
    }

    private function atpQuery()
    {
        $query = DB::table('authority_to_purchase_table')
            ->leftJoin('requisition_issue_slip_table', 'authority_to_purchase_table.authority_purchase_ris_id', '=', 'requisition_issue_slip_table.ris_id')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id');

        $select = [
            'authority_to_purchase_table.*',
            'requisition_issue_slip_table.ris_form_number',
            'requisition_issue_slip_table.ris_purpose_description',
            'physical_suppliers_table.company_name',
            'online_suppliers_table.shop_name',
            'suppliers_table.supplier_store_type',
        ];

        if (Schema::hasTable('authority_to_purchase_items_table')) {
            $totals = DB::table('authority_to_purchase_items_table')
                ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as atp_total'))
                ->groupBy('authority_purchase_id');
            $query->leftJoinSub($totals, 'atp_totals', function ($join) {
                $join->on('atp_totals.authority_purchase_id', '=', 'authority_to_purchase_table.authority_purchase_id');
            });
            $select[] = 'atp_totals.atp_total';
        }

        return $query->select($select);
    }

    private function rfcQuery()
    {
        $query = DB::table('request_check_table')
            ->leftJoin(
                'authority_to_purchase_table',
                'request_check_table.request_check_authority_purchase_id',
                '=',
                'authority_to_purchase_table.authority_purchase_id'
            )
            ->leftJoin(
                'requisition_issue_slip_table',
                'authority_to_purchase_table.authority_purchase_ris_id',
                '=',
                'requisition_issue_slip_table.ris_id'
            );

        return $query->select(
            'request_check_table.*',
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_ris_id',
            'requisition_issue_slip_table.ris_form_number'
        );
    }

    private function liqQuery()
    {
        $query = DB::table('liquidation_reports_table');
        $select = ['liquidation_reports_table.*'];

        if (Schema::hasTable('receiving_reports_table') && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')) {
            $query->leftJoin(
                'receiving_reports_table',
                'liquidation_reports_table.liquidation_report_receiving_report_id',
                '=',
                'receiving_reports_table.receiving_report_id'
            );
            $select[] = 'receiving_reports_table.receiving_report_form_number';
        }

        return $query->select($select);
    }

    private function chainFromAtp(int $atpId): array
    {
        $chain = [
            'ris' => null,
            'atp' => null,
            'rfc' => null,
            'funds' => null,
            'rr' => null,
            'liq' => null,
        ];

        if ($atpId < 1) {
            return $chain;
        }

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        if ($atp) {
            $chain['atp'] = [
                'label' => $atp->authority_purchase_form_number ?: ('ATP #' . $atpId),
                'url' => '/accounting/authority-to-purchase/' . $atpId,
                'status' => $atp->authority_purchase_status,
            ];
            if (!empty($atp->authority_purchase_ris_id)) {
                $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $atp->authority_purchase_ris_id)->first();
                $chain['ris'] = [
                    'label' => $ris->ris_form_number ?? ('RIS #' . $atp->authority_purchase_ris_id),
                    'url' => null,
                    'status' => $ris->ris_status ?? null,
                ];
            }
        }

        if (Schema::hasTable('request_check_table')) {
            $rfc = DB::table('request_check_table')
                ->where('request_check_authority_purchase_id', $atpId)
                ->orderByDesc('request_check_id')
                ->first();
            if ($rfc) {
                $chain['rfc'] = [
                    'label' => $rfc->request_check_form_number ?: ('RFC #' . $rfc->request_check_id),
                    'url' => '/accounting/request-check/' . $rfc->request_check_id,
                    'status' => $rfc->request_check_status,
                ];
                $chain['funds'] = [
                    'label' => !empty($rfc->request_check_funds_released_at) ? 'Released' : 'Not released',
                    'url' => '/accounting/request-check/' . $rfc->request_check_id,
                    'status' => !empty($rfc->request_check_funds_released_at) ? 'Released' : 'Pending',
                ];

                if (Schema::hasTable('receiving_reports_table')) {
                    $rr = DB::table('receiving_reports_table')
                        ->where(function ($q) use ($rfc, $atpId) {
                            $q->where('receiving_report_request_check_id', $rfc->request_check_id);
                            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
                                $q->orWhere('receiving_report_atp_id', $atpId);
                            }
                        })
                        ->orderByDesc('receiving_report_id')
                        ->first();
                    if ($rr) {
                        $chain['rr'] = [
                            'label' => $rr->receiving_report_form_number ?: ('RR #' . $rr->receiving_report_id),
                            'url' => null,
                            'status' => $rr->receiving_report_status ?? null,
                        ];
                        if (Schema::hasTable('liquidation_reports_table')) {
                            $liq = DB::table('liquidation_reports_table')
                                ->where('liquidation_report_receiving_report_id', $rr->receiving_report_id)
                                ->orderByDesc('liquidation_report_id')
                                ->first();
                            if ($liq) {
                                $chain['liq'] = [
                                    'label' => $liq->liquidation_report_form_number ?: ('LIQ #' . $liq->liquidation_report_id),
                                    'url' => '/accounting/liquidation-reports/' . $liq->liquidation_report_id,
                                    'status' => $liq->liquidation_report_status,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $chain;
    }

    private function documentHistory(string $type, int $id)
    {
        if (!Schema::hasTable('approval_logs_table')) {
            return collect();
        }

        try {
            return DB::table('approval_logs_table')
                ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                ->where('approval_log_reference_type', $type)
                ->where('approval_log_reference_id', $id)
                ->orderByDesc('approval_log_approved_at')
                ->select('approval_logs_table.*', 'users_table.user_full_name')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function log(string $type, int $id, string $status, ?string $remarks = null): void
    {
        if (!Schema::hasTable('approval_logs_table')) {
            return;
        }
        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => $type,
                'approval_log_reference_id' => $id,
                'approval_log_level' => 'Accounting',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => $status,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function markReviewed(string $type, int $id, bool $should): void
    {
        if (!$should) {
            return;
        }
        $this->log($type, $id, 'Under Review', 'Opened for Accounting review.');
    }

    private function notifyPurchaser($userId, string $title, string $message, string $kind, string $refType, int $refId, string $url): void
    {
        WorkflowNotifier::toUser(
            $userId,
            WorkflowNotifier::ROLE_PURCHASER,
            $title,
            $message,
            $kind,
            $refType,
            $refId,
            $url,
            'accounting'
        );
    }

    private function rfcHas(string $column): bool
    {
        return Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', $column);
    }

    private function rfcSortColumn(): string
    {
        if ($this->rfcHas('request_check_submitted_at')) {
            return 'request_check_table.request_check_submitted_at';
        }
        if ($this->rfcHas('request_check_created_at')) {
            return 'request_check_table.request_check_created_at';
        }

        return 'request_check_table.request_check_date';
    }

    private function rfcIncomingStatuses(): array
    {
        return ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];
    }

    private function rfcRevisionStatuses(): array
    {
        return $this->rfcStatusAllowed('Minor Revision') ? ['Minor Revision'] : ['Rejected'];
    }

    private function rfcStatusAllowed(string $status): bool
    {
        static $values = null;
        if ($values === null) {
            $values = [];
            try {
                $col = DB::select("SHOW COLUMNS FROM request_check_table LIKE 'request_check_status'");
                $type = $col[0]->Type ?? '';
                if (preg_match_all("/'([^']+)'/", $type, $matches)) {
                    $values = $matches[1];
                }
            } catch (\Throwable $e) {
                $values = [];
            }
        }

        return $values === [] || in_array($status, $values, true);
    }

    private function rfcUpdate($id, array $payload): void
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if ($this->rfcHas($column)) {
                $filtered[$column] = $value;
            }
        }
        if ($filtered === []) {
            return;
        }
        DB::table('request_check_table')->where('request_check_id', $id)->update($filtered);
    }

    private function liqUpdate($id, array $payload): void
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if (Schema::hasColumn('liquidation_reports_table', $column)) {
                $filtered[$column] = $value;
            }
        }
        if ($filtered === []) {
            return;
        }
        DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update($filtered);
    }

    private function liqSortColumn(): string
    {
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_submitted_at')) {
            return 'liquidation_reports_table.liquidation_report_submitted_at';
        }
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_date_submitted')) {
            return 'liquidation_reports_table.liquidation_report_date_submitted';
        }

        return 'liquidation_reports_table.liquidation_report_created_at';
    }

    private function applySearch($query, Request $request, array $columns): void
    {
        if (!$request->filled('search')) {
            return;
        }
        $search = '%' . $request->search . '%';
        $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $i => $col) {
                $i === 0 ? $q->where($col, 'LIKE', $search) : $q->orWhere($col, 'LIKE', $search);
            }
        });
    }

    private function lockAtp($id)
    {
        return DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->lockForUpdate()->first();
    }

    private function lockRfc($id)
    {
        return DB::table('request_check_table')->where('request_check_id', $id)->lockForUpdate()->first();
    }

    private function lockLiq($id)
    {
        return DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->lockForUpdate()->first();
    }

    private function completeLinkedProcurementRequest(object $liq): void
    {
        if (!Schema::hasTable('procurement_requests_table')) {
            return;
        }

        $procurementId = (int) ($liq->liquidation_report_procurement_request_id ?? 0);

        if ($procurementId < 1 && !empty($liq->liquidation_report_receiving_report_id) && Schema::hasTable('receiving_reports_table')) {
            $rr = DB::table('receiving_reports_table')
                ->where('receiving_report_id', $liq->liquidation_report_receiving_report_id)
                ->first();

            $procurementId = (int) ($rr->receiving_report_procurement_request_id ?? 0);

            if ($procurementId < 1 && !empty($rr->receiving_report_ris_id) && Schema::hasTable('requisition_issue_slip_table')) {
                $procurementId = (int) DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $rr->receiving_report_ris_id)
                    ->value('ris_procurement_request_id');
            }

            if (
                $procurementId < 1
                && !empty($rr->receiving_report_request_check_id)
                && Schema::hasTable('request_check_table')
                && Schema::hasTable('authority_to_purchase_table')
                && Schema::hasTable('requisition_issue_slip_table')
            ) {
                $linked = DB::table('request_check_table')
                    ->leftJoin(
                        'authority_to_purchase_table',
                        'request_check_table.request_check_authority_purchase_id',
                        '=',
                        'authority_to_purchase_table.authority_purchase_id'
                    )
                    ->leftJoin(
                        'requisition_issue_slip_table',
                        'authority_to_purchase_table.authority_purchase_ris_id',
                        '=',
                        'requisition_issue_slip_table.ris_id'
                    )
                    ->where('request_check_table.request_check_id', $rr->receiving_report_request_check_id)
                    ->select('requisition_issue_slip_table.ris_procurement_request_id')
                    ->first();

                $procurementId = (int) ($linked->ris_procurement_request_id ?? 0);
            }
        }

        if ($procurementId < 1) {
            return;
        }

        DB::table('procurement_requests_table')
            ->where('procurement_request_id', $procurementId)
            ->whereIn('procurement_request_status', ['Approved', 'Pending'])
            ->update([
                'procurement_request_status' => 'Completed',
            ]);
    }
}
