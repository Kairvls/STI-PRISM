<?php

namespace App\Http\Controllers;

use App\Support\AccountingAttentionSummary;
use App\Support\AccountingDashboard;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\WorkflowNotifier;
use App\Support\ReviewerAssignment;
use App\Support\RisWorkflow;
use App\Support\UserSignatureLibrary;
use App\Support\PurchaseOrderBasket;
use App\Support\RfcAtpLinks;
use App\Support\DocumentRevisionNotes;
use App\Support\DocumentUrgency;
use App\Support\RisRevisionImages;
use App\Exceptions\RevisionImageUploadException;

class AccountingController extends Controller
{
    private const LIQ_INCOMING = ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];

    public function dashboard(Request $request)
    {
        $chartYears = $this->fundsReleasedChartYears();
        $chartYear = (int) $request->query('year', now()->year);
        if (!in_array($chartYear, $chartYears, true)) {
            $chartYear = (int) ($chartYears[0] ?? now()->year);
        }
        $fundsReleasedChart = $this->fundsReleasedChart($chartYear);

        if ($request->ajax() && $request->query('partial') === 'funds_chart') {
            return response()->json([
                'year' => $fundsReleasedChart['year'],
                'months' => $fundsReleasedChart['months'],
                'total' => $fundsReleasedChart['total'],
                'releases' => $fundsReleasedChart['releases'],
            ]);
        }

        return view('accounting.dashboard', [
            'user' => Auth::user(),
            'metrics' => $this->metrics(),
            'financialSummary' => $this->financialSummary(),
            'deadlines' => $this->deadlines(),
            'fundsReleasedChart' => $fundsReleasedChart,
            'chartYear' => $chartYear,
            'chartYears' => $chartYears,
            'dashboard' => AccountingDashboard::build(),
        ]);
    }

    public function authorityToPurchase(Request $request)
    {
        $filter = $this->resolveModuleFilter($request, 'accounting.atp_status', 'incoming', [
            'all', 'incoming', 'revision', 'approved', 'rejected',
        ]);
        $attentionFocus = AccountingAttentionSummary::focusFor($request, 'authority_to_purchase_table');
        if ($attentionFocus) {
            $filter = $attentionFocus['status'];
            session(['accounting.atp_status' => $filter]);
        }
        $query = $this->atpQuery()->where(function ($q) {
            $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
        });

        if ($attentionFocus) {
            AccountingAttentionSummary::applyFocus($query, $attentionFocus['key']);
        } elseif ($filter === 'incoming') {
            AccountingAttentionSummary::scopeAtpIncoming($query);
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
            'requisition_issue_slip_table.ris_purpose_description',
            'authority_to_purchase_table.authority_purchase_status',
            'physical_suppliers_table.company_name',
            'online_suppliers_table.shop_name',
        ]);

        if ($filter === 'incoming') {
            DocumentUrgency::orderUrgentFirst($query, 'ATP');
        }
        $records = $query->orderByDesc('authority_to_purchase_table.authority_purchase_updated_at')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => $this->countAtpAll(),
            'incoming' => $this->countAtpIncoming(),
            'revision' => $this->countAtpRevision(),
            'approved' => $this->countAtpApproved(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('accounting.authority-to-purchase._rows', compact('records', 'filter'))->render(),
                'counts' => $counts,
                'focus' => $attentionFocus['key'] ?? null,
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'pagination_html' => $records->hasPages()
                    ? view('pagination.president', ['paginator' => $records])->render()
                    : '',
            ]);
        }

        return view('accounting.authority-to-purchase.index', compact('records', 'filter', 'counts', 'attentionFocus'));
    }

    public function showAtp(Request $request, $id)
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
        $purchaseOrder = $this->purchaseOrderForAtp((int) $id);
        $reviewable = $atp->authority_purchase_status === 'Pending'
            && $atp->authority_purchase_submitted_at !== null
            && ! $this->atpReviewedViaPurchaseOrder($purchaseOrder);
        $returnStatus = $this->resolveReturnStatus($request, 'accounting.atp_status', 'incoming');

        $savedSignatures = UserSignatureLibrary::forUser((int) Auth::id());

        return view('accounting.authority-to-purchase.show', compact(
            'atp',
            'items',
            'chain',
            'history',
            'reviewable',
            'returnStatus',
            'savedSignatures',
            'purchaseOrder'
        ));
    }

    private function purchaseOrderForAtp(int $atpId): ?object
    {
        if (! PurchaseOrderBasket::tablesExist()) {
            return null;
        }

        $poId = PurchaseOrderBasket::poIdForAtp($atpId);

        return $poId ? DB::table('purchase_orders_table')->where('purchase_order_id', $poId)->first() : null;
    }

    private function atpReviewedViaPurchaseOrder(?object $order): bool
    {
        return $order && in_array((string) $order->purchase_order_status, [
            PurchaseOrderBasket::STATUS_SUBMITTED,
            PurchaseOrderBasket::STATUS_APPROVED,
        ], true);
    }

    private function purchaseOrderDecisionBlock(Request $request, int $atpId)
    {
        $order = $this->purchaseOrderForAtp($atpId);
        if (! $this->atpReviewedViaPurchaseOrder($order)) {
            return null;
        }

        $message = 'This ATP is part of '.PurchaseOrderBasket::displayNumber($order).'. Approve or send back the whole Purchase Order instead.';

        return $request->expectsJson()
            ? response()->json(['ok' => false, 'message' => $message], 422)
            : back()->with('error', $message);
    }

    /**
     * Read-only RIS (and supporting docs) for Accounting related-document chain.
     */
    public function showRis(Request $request, $id)
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $id)->first();
        abort_if(!$ris, 404);

        $risItems = $this->risItemsWithLookups([(int) $id])->values();

        $attachments = Schema::hasTable('ris_attachments_table')
            ? DB::table('ris_attachments_table')
                ->where('ris_id', $id)
                ->orderBy('ris_attachment_original_name')
                ->get()
            : collect();

        $atps = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_ris_id', $id)
            ->orderByDesc('authority_purchase_id')
            ->get();
        $atp = $atps->first();

        $chain = $atp
            ? $this->chainFromAtp((int) $atp->authority_purchase_id)
            : [
                'ris' => [
                    'label' => RisWorkflow::formNumber($ris),
                    'url' => route('accounting.ris.show', $id),
                    'status' => $ris->ris_status ?? null,
                ],
                'atp' => null,
                'rfc' => null,
                'funds' => null,
                'rr' => null,
                'liq' => null,
            ];
        $chain['atp_siblings'] = $atps->slice(1)
            ->reject(fn ($row) => (string) ($row->authority_purchase_status ?? '') === 'Rejected')
            ->map(fn ($row) => [
                'label' => $row->authority_purchase_form_number ?: ('ATP #' . $row->authority_purchase_id),
                'url' => '/accounting/authority-to-purchase/' . $row->authority_purchase_id,
                'status' => $row->authority_purchase_status,
            ])
            ->values()
            ->all();

        $history = $this->documentHistory('RIS', (int) $id);
        $backUrl = $atp
            ? '/accounting/authority-to-purchase/' . $atp->authority_purchase_id
            : '/accounting/authority-to-purchase';

        return view('accounting.ris.show', [
            'ris' => $ris,
            'risItems' => $risItems,
            'attachments' => $attachments,
            'chain' => $chain,
            'history' => $history,
            'backUrl' => $backUrl,
            'presidentName' => 'President',
        ]);
    }

    public function downloadRisAttachment($id, $attachmentId)
    {
        abort_unless(Schema::hasTable('ris_attachments_table'), 404);

        $file = DB::table('ris_attachments_table')
            ->where('ris_id', $id)
            ->where('ris_attachment_id', $attachmentId)
            ->first();
        abort_if(!$file, 404);

        $path = storage_path('app/public/' . $file->ris_attachment_path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $file->ris_attachment_original_name . '"',
        ]);
    }

    public function approveAtp(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
        $atp = $this->lockAtp($id);
        abort_if(!$atp, 404);
        ReviewerAssignment::assertCanAct(
            isset($atp->authority_purchase_assigned_reviewer_id)
                ? (int) $atp->authority_purchase_assigned_reviewer_id
                : null,
            'ATP'
        );

        if ($atp->authority_purchase_status !== 'Pending' || $atp->authority_purchase_submitted_at === null) {
            return back()->with('error', 'Only submitted ATP records can be approved.');
        }

        if ($blocked = $this->purchaseOrderDecisionBlock($request, (int) $id)) {
            return $blocked;
        }

        $name = \App\Support\AccountingSigner::nameFromRequest($request);
        $update = [
            'authority_purchase_status' => 'Approved',
            'authority_purchase_authorized_by_signature' => RisWorkflow::drawnOrName($request->input('signature_data'), $name),
            'authority_purchase_rejection_reason' => null,
            'authority_purchase_updated_at' => now(),
        ];
        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by')) {
            $update['authority_purchase_authorized_by'] = Auth::id();
        }
        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by_name')) {
            $update['authority_purchase_authorized_by_name'] = $name;
        }
        DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->update($update);

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

        $message = 'ATP approved. ' . WorkflowNotifier::recipientName($atp->authority_purchase_submitted_by, 'The Purchaser') . ' has been notified.';
        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return redirect('/accounting/authority-to-purchase/' . $id)->with('success', $message);
        });
    }

    public function reviseAtp(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        if ($imageError = RisRevisionImages::validationError($request)) {
            return $this->revisionImageError($request, $imageError);
        }
        $atp = $this->lockAtp($id);
        abort_if(!$atp, 404);
        ReviewerAssignment::assertCanAct(
            isset($atp->authority_purchase_assigned_reviewer_id)
                ? (int) $atp->authority_purchase_assigned_reviewer_id
                : null,
            'ATP'
        );

        if ($atp->authority_purchase_status !== 'Pending' || $atp->authority_purchase_submitted_at === null) {
            return back()->with('error', 'Only submitted ATP records can be sent back for revision.');
        }

        if ($blocked = $this->purchaseOrderDecisionBlock($request, (int) $id)) {
            return $blocked;
        }

        try {
            return DocumentRevisionNotes::record($request, 'ATP', (int) $id, $validated['remarks'], DocumentRevisionNotes::KIND_REVISION, function (array $images) use ($request, $id, $atp, $validated) {
                DB::table('authority_to_purchase_table')->where('authority_purchase_id', $id)->update([
                    'authority_purchase_rejection_reason' => $validated['remarks'],
                    'authority_purchase_submitted_at' => null,
                    'authority_purchase_updated_at' => now(),
                ]);

                $this->log('ATP', (int) $id, 'Under Review', $validated['remarks']);
                $this->notifyPurchaser(
                    $atp->authority_purchase_submitted_by,
                    'ATP revision required',
                    ($atp->authority_purchase_form_number ?: ('ATP #' . $id)) . ': ' . $validated['remarks'] . DocumentRevisionNotes::imagesSuffix($images),
                    'atp_revision',
                    'ATP',
                    (int) $id,
                    '/purchaser/authority-to-purchase'
                );

                $message = 'Revision requested. ' . WorkflowNotifier::recipientName($atp->authority_purchase_submitted_by, 'The Purchaser') . ' has been notified' . DocumentRevisionNotes::imagesSuffix($images) . '.';
                if ($request->ajax()) {
                    return response()->json(['ok' => true, 'message' => $message]);
                }
                return redirect('/accounting/authority-to-purchase?status=' . urlencode(session('accounting.atp_status', 'incoming')))
                    ->with('success', $message);
            });
        } catch (RevisionImageUploadException $e) {
            return $this->revisionImageError($request, $e->getMessage());
        }
    }

    private function revisionImageError(Request $request, string $message)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->withInput($request->except(RisRevisionImages::FIELD))->with('error', $message);
    }

    public function purchaseOrders(Request $request)
    {
        abort_unless(PurchaseOrderBasket::tablesExist(), 404);

        $filter = $this->resolveModuleFilter($request, 'accounting.po_status', 'incoming', [
            'all', 'incoming', 'revision', 'approved', 'rejected', 'cancelled',
        ]);

        $query = DB::table('purchase_orders_table')
            ->where(function ($q) {
                $q->whereNull('purchase_order_is_archived')
                    ->orWhere('purchase_order_is_archived', 0);
            });
        $this->applyAssignedReviewerFilter(
            $query,
            'purchase_orders_table',
            'purchase_order_assigned_reviewer_id'
        );

        if ($filter === 'incoming') {
            $query->where('purchase_order_status', PurchaseOrderBasket::STATUS_SUBMITTED);
        } elseif ($filter === 'revision') {
            $query->where('purchase_order_status', PurchaseOrderBasket::STATUS_DRAFT)
                ->whereNotNull('purchase_order_revision_reason');
        } elseif ($filter === 'approved') {
            $query->where('purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED);
        } elseif ($filter === 'rejected') {
            $query->where('purchase_order_status', PurchaseOrderBasket::STATUS_REJECTED);
        } elseif ($filter === 'cancelled') {
            $query->where('purchase_order_status', PurchaseOrderBasket::STATUS_CANCELLED);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('purchase_order_number', 'LIKE', $search)
                    ->orWhere('purchase_order_status', 'LIKE', $search)
                    ->orWhere('purchase_order_revision_reason', 'LIKE', $search);
            });
        }

        DocumentUrgency::select($query, 'PO');
        if ($filter === 'incoming') {
            DocumentUrgency::orderUrgentFirst($query, 'PO');
        }
        $records = $query
            ->orderByDesc('purchase_order_updated_at')
            ->orderByDesc('purchase_order_id')
            ->paginate(15)
            ->withQueryString();

        PurchaseOrderBasket::attachToOrders($records->getCollection());

        $base = DB::table('purchase_orders_table')->where(function ($q) {
            $q->whereNull('purchase_order_is_archived')->orWhere('purchase_order_is_archived', 0);
        });

        $counts = [
            'all' => (clone $base)->count(),
            'incoming' => (clone $base)->where('purchase_order_status', PurchaseOrderBasket::STATUS_SUBMITTED)->count(),
            'revision' => (clone $base)->where('purchase_order_status', PurchaseOrderBasket::STATUS_DRAFT)->whereNotNull('purchase_order_revision_reason')->count(),
            'approved' => (clone $base)->where('purchase_order_status', PurchaseOrderBasket::STATUS_APPROVED)->count(),
            'cancelled' => (clone $base)->where('purchase_order_status', PurchaseOrderBasket::STATUS_CANCELLED)->count(),
        ];

        return view('accounting.purchase-orders.index', compact('records', 'filter', 'counts'));
    }

    public function showPurchaseOrder($id)
    {
        abort_unless(PurchaseOrderBasket::tablesExist(), 404);

        $order = DB::table('purchase_orders_table')
            ->where('purchase_order_id', $id)
            ->first();
        abort_if(! $order, 404);

        $collection = collect([$order]);
        PurchaseOrderBasket::attachToOrders($collection);
        $order = $collection->first();

        $reviewable = ($order->purchase_order_status ?? '') === PurchaseOrderBasket::STATUS_SUBMITTED;

        $atpIds = collect($order->linked_atps ?? [])->pluck('authority_purchase_id')->all();
        $atpItems = $atpIds !== [] && Schema::hasTable('authority_to_purchase_items_table')
            ? DB::table('authority_to_purchase_items_table')
                ->whereIn('authority_purchase_id', $atpIds)
                ->orderBy('atp_item_id')
                ->get()
                ->groupBy('authority_purchase_id')
            : collect();

        return view('accounting.purchase-orders.show', compact('order', 'reviewable', 'atpItems'));
    }

    public function approvePurchaseOrder(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            abort_unless(PurchaseOrderBasket::tablesExist(), 404);

            $order = DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->lockForUpdate()
                ->first();
            abort_if(! $order, 404);
            ReviewerAssignment::assertCanAct(
                isset($order->purchase_order_assigned_reviewer_id)
                    ? (int) $order->purchase_order_assigned_reviewer_id
                    : null,
                'Purchase Order'
            );

            if (($order->purchase_order_status ?? '') !== PurchaseOrderBasket::STATUS_SUBMITTED) {
                return back()->with('error', 'Only submitted Purchase Orders can be approved.');
            }

            $atpIds = PurchaseOrderBasket::atpIdsForPo((int) $id);
            $name = \App\Support\AccountingSigner::nameFromRequest($request);
            $now = now();

            DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->update([
                    'purchase_order_status' => PurchaseOrderBasket::STATUS_APPROVED,
                    'purchase_order_approved_by' => Auth::id(),
                    'purchase_order_approved_at' => $now,
                    'purchase_order_revision_reason' => null,
                    'purchase_order_updated_at' => $now,
                ]);

            foreach ($atpIds as $atpId) {
                $update = [
                    'authority_purchase_status' => 'Approved',
                    'authority_purchase_authorized_by_signature' => RisWorkflow::drawnOrName($request->input('signature_data'), $name),
                    'authority_purchase_rejection_reason' => null,
                    'authority_purchase_updated_at' => $now,
                ];
                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by')) {
                    $update['authority_purchase_authorized_by'] = Auth::id();
                }
                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by_name')) {
                    $update['authority_purchase_authorized_by_name'] = $name;
                }
                DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $atpId)
                    ->update($update);
            }

            $label = PurchaseOrderBasket::displayNumber($order);
            $this->log('PO', (int) $id, 'Approved', $label.' approved with '.count($atpIds).' ATP(s).');
            $this->notifyPurchaser(
                $order->purchase_order_submitted_by ?: $order->purchase_order_created_by,
                'Purchase Order approved',
                $label.' was approved by Accounting. Linked ATPs are ready for Request Check.',
                'po_approved',
                'PO',
                (int) $id,
                '/purchaser/purchase-orders?view_po='.(int) $id
            );

            return redirect('/accounting/purchase-orders/'.$id)->with(
                'success',
                $label.' approved. '.WorkflowNotifier::recipientName($order->purchase_order_submitted_by ?: $order->purchase_order_created_by, 'The Purchaser').' has been notified.'
            );
        });
    }

    public function revisePurchaseOrder(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        if ($imageError = RisRevisionImages::validationError($request)) {
            return $this->revisionImageError($request, $imageError);
        }

        try {
            return DB::transaction(fn () => $this->revisePurchaseOrderLocked($request, $validated['remarks'], (int) $id));
        } catch (RevisionImageUploadException $e) {
            return $this->revisionImageError($request, $e->getMessage());
        }
    }

    private function revisePurchaseOrderLocked(Request $request, string $remarks, int $id)
    {
        abort_unless(PurchaseOrderBasket::tablesExist(), 404);

        $order = DB::table('purchase_orders_table')
            ->where('purchase_order_id', $id)
            ->lockForUpdate()
            ->first();
        abort_if(! $order, 404);
        ReviewerAssignment::assertCanAct(
            isset($order->purchase_order_assigned_reviewer_id)
                ? (int) $order->purchase_order_assigned_reviewer_id
                : null,
            'Purchase Order'
        );

        if (($order->purchase_order_status ?? '') !== PurchaseOrderBasket::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted Purchase Orders can be sent back for revision.');
        }

        return DocumentRevisionNotes::record($request, 'PO', $id, $remarks, DocumentRevisionNotes::KIND_REVISION, function (array $images) use ($order, $remarks, $id) {
            $atpIds = PurchaseOrderBasket::atpIdsForPo($id);
            $now = now();

            DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->update([
                    'purchase_order_status' => PurchaseOrderBasket::STATUS_DRAFT,
                    'purchase_order_submitted_at' => null,
                    'purchase_order_revision_reason' => $remarks,
                    'purchase_order_updated_at' => $now,
                ]);

            foreach ($atpIds as $atpId) {
                DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $atpId)
                    ->update([
                        'authority_purchase_rejection_reason' => $remarks,
                        'authority_purchase_submitted_at' => null,
                        'authority_purchase_updated_at' => $now,
                    ]);
            }

            $suffix = DocumentRevisionNotes::imagesSuffix($images);
            $label = PurchaseOrderBasket::displayNumber($order);
            $this->log('PO', $id, 'Under Review', $remarks);
            $this->notifyPurchaser(
                $order->purchase_order_submitted_by ?: $order->purchase_order_created_by,
                'Purchase Order revision required',
                $label.': '.$remarks.$suffix,
                'po_revision',
                'PO',
                $id,
                '/purchaser/purchase-orders?edit_po='.$id
            );

            return redirect('/accounting/purchase-orders')->with(
                'success',
                'Purchase Order sent back to '.WorkflowNotifier::recipientName($order->purchase_order_submitted_by ?: $order->purchase_order_created_by, 'the Purchaser').' for revision'.$suffix.'.'
            );
        });
    }

    public function requestCheck(Request $request)
    {
        $filter = $this->resolveModuleFilter($request, 'accounting.rfc_status', 'incoming', [
            'all', 'incoming', 'funds', 'released', 'revision', 'approved',
        ]);
        $attentionFocus = AccountingAttentionSummary::focusFor($request, 'request_check_table');
        if ($attentionFocus) {
            $filter = $attentionFocus['status'];
            session(['accounting.rfc_status' => $filter]);
        }
        $query = $this->rfcQuery();
        AccountingAttentionSummary::scopeRfcActive($query);

        if ($attentionFocus) {
            AccountingAttentionSummary::applyFocus($query, $attentionFocus['key']);
        } elseif ($filter === 'incoming') {
            AccountingAttentionSummary::scopeRfcIncoming($query);
        } elseif ($filter === 'funds') {
            AccountingAttentionSummary::scopeRfcFunds($query);
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

        $searchCols = [
            'request_check_table.request_check_payee',
            'request_check_table.request_check_status',
        ];
        if ($this->rfcHas('request_check_form_number')) {
            array_unshift($searchCols, 'request_check_table.request_check_form_number');
        }
        $searchCols[] = 'authority_to_purchase_table.authority_purchase_form_number';
        $searchCols[] = 'requisition_issue_slip_table.ris_form_number';
        if ($this->rfcHas('request_check_amount_figures')) {
            $searchCols[] = 'request_check_table.request_check_amount_figures';
        }
        $this->applySearch($query, $request, $searchCols);

        if (in_array($filter, ['incoming', 'funds'], true)) {
            DocumentUrgency::orderUrgentFirst($query, 'RFC');
        }
        $records = $query->orderByDesc($this->rfcSortColumn())->paginate(15)->withQueryString();
        $counts = [
            'incoming' => $this->countRfcIncoming(),
            'funds' => $this->countFundsAwaiting(),
            'released' => $this->countFundsReleased(),
            'revision' => $this->countRfcRevision(),
            'approved' => $this->countRfcApproved(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('accounting.request-check._rows', compact('records', 'filter'))->render(),
                'counts' => $counts,
                'focus' => $attentionFocus['key'] ?? null,
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'pagination_html' => $records->hasPages()
                    ? view('pagination.president', ['paginator' => $records])->render()
                    : '',
            ]);
        }

        return view('accounting.request-check.index', compact('records', 'filter', 'counts', 'attentionFocus'));
    }

    public function showRequestCheck(Request $request, $id)
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

        $atpItems = collect();
        $atpId = (int) ($rfc->request_check_authority_purchase_id ?? 0);
        $linkedAtpIds = RfcAtpLinks::atpIdsFor((int) $id, $atpId ?: null);
        $linkedAtps = $linkedAtpIds === []
            ? collect()
            : DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_id', $linkedAtpIds)
                ->get(['authority_purchase_id', 'authority_purchase_form_number', 'authority_purchase_status'])
                ->sortBy(fn ($atp) => array_search((int) $atp->authority_purchase_id, $linkedAtpIds, true))
                ->values();
        if ($linkedAtpIds !== [] && Schema::hasTable('authority_to_purchase_items_table')) {
            $labels = $linkedAtps->mapWithKeys(fn ($atp) => [(int) $atp->authority_purchase_id => PurchaseOrderBasket::atpListLabel($atp)]);
            $atpItems = DB::table('authority_to_purchase_items_table')
                ->whereIn('authority_purchase_id', $linkedAtpIds)
                ->orderBy('authority_purchase_id')
                ->orderBy('atp_item_id')
                ->get()
                ->each(function ($item) use ($labels) {
                    $item->atp_label = $labels[(int) $item->authority_purchase_id] ?? null;
                });
        }
        $purchaseOrderLabel = null;
        if (!empty($rfc->request_check_purchase_order_id) && PurchaseOrderBasket::tablesExist()) {
            $order = DB::table('purchase_orders_table')->where('purchase_order_id', $rfc->request_check_purchase_order_id)->first();
            $purchaseOrderLabel = $order ? PurchaseOrderBasket::displayNumber($order) : null;
        }

        $chain = $this->chainFromAtp($atpId);
        $history = $this->documentHistory('RFC', (int) $id);
        $reviewable = in_array($rfc->request_check_status, $this->rfcIncomingStatuses(), true);
        $releasable = $rfc->request_check_status === 'Approved'
            && $this->rfcHas('request_check_funds_released_at')
            && empty($rfc->request_check_funds_released_at);
        $returnStatus = $this->resolveReturnStatus($request, 'accounting.rfc_status', 'incoming');

        $savedSignatures = UserSignatureLibrary::forUser((int) Auth::id());

        return view('accounting.request-check.show', compact(
            'rfc',
            'attachments',
            'atpItems',
            'linkedAtps',
            'purchaseOrderLabel',
            'chain',
            'history',
            'reviewable',
            'releasable',
            'returnStatus',
            'savedSignatures'
        ));
    }

    public function approveRequestCheck(Request $request, $id)
    {
        $rfc = $this->lockRfc($id);
        abort_if(!$rfc, 404);
        ReviewerAssignment::assertCanAct(
            isset($rfc->request_check_assigned_reviewer_id)
                ? (int) $rfc->request_check_assigned_reviewer_id
                : null,
            'Request for Check'
        );

        if (!in_array($rfc->request_check_status, $this->rfcIncomingStatuses(), true)) {
            return back()->with('error', 'This Request Check is not awaiting Accounting review.');
        }

        $name = \App\Support\AccountingSigner::nameFromRequest($request);
        $signature = RisWorkflow::drawnOrName($request->input('signature_data'), $name);
        $this->rfcUpdate($id, [
            'request_check_status' => 'Approved',
            'request_check_review_stage' => 'accounting',
            'request_check_accounting_verified_by' => Auth::id(),
            'request_check_accounting_verified_at' => now(),
            'request_check_approved_by_user_id' => Auth::id(),
            'request_check_approved_at' => now(),
            'request_check_approved_by_signature' => $signature,
            'request_check_approved_by_admin' => $name,
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

        $message = 'Request Check approved. ' . WorkflowNotifier::recipientName($rfc->request_check_submitted_by ?? $rfc->request_check_requested_by_user_id, 'The Purchaser') . ' has been notified.';
        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return redirect('/accounting/request-check/' . $id)->with('success', $message);
    }

    public function reviseRequestCheck(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        if ($imageError = RisRevisionImages::validationError($request)) {
            return $this->revisionImageError($request, $imageError);
        }
        $rfc = $this->lockRfc($id);
        abort_if(!$rfc, 404);
        ReviewerAssignment::assertCanAct(
            isset($rfc->request_check_assigned_reviewer_id)
                ? (int) $rfc->request_check_assigned_reviewer_id
                : null,
            'Request for Check'
        );

        if (!in_array($rfc->request_check_status, $this->rfcIncomingStatuses(), true)) {
            return back()->with('error', 'This Request Check cannot be sent for revision.');
        }

        try {
            return DocumentRevisionNotes::record($request, 'RFC', (int) $id, $validated['remarks'], DocumentRevisionNotes::KIND_REVISION, function (array $images) use ($request, $id, $rfc, $validated) {
                $revisionStatus = $this->rfcStatusAllowed('Minor Revision') ? 'Minor Revision' : 'Rejected';
                $this->rfcUpdate($id, [
                    'request_check_status' => $revisionStatus,
                    'request_check_review_stage' => 'purchaser',
                    'request_check_revision_notes' => $validated['remarks'],
                    'request_check_updated_at' => now(),
                ]);

                $suffix = DocumentRevisionNotes::imagesSuffix($images);
                $purchaserId = $rfc->request_check_submitted_by ?? $rfc->request_check_requested_by_user_id;
                $this->log('RFC', (int) $id, 'Minor Revision', $validated['remarks']);
                $this->notifyPurchaser(
                    $purchaserId,
                    'Request Check revision required',
                    ($rfc->request_check_form_number ?: ('RFC #' . $id)) . ': ' . $validated['remarks'] . $suffix,
                    'rfc_revision',
                    'RFC',
                    (int) $id,
                    '/purchaser/request-check'
                );

                $message = 'Revision requested. ' . WorkflowNotifier::recipientName($purchaserId, 'The Purchaser') . ' has been notified' . $suffix . '.';
                if ($request->ajax()) {
                    return response()->json(['ok' => true, 'message' => $message]);
                }
                return redirect('/accounting/request-check?status=' . urlencode(session('accounting.rfc_status', 'incoming')))
                    ->with('success', $message);
            });
        } catch (RevisionImageUploadException $e) {
            return $this->revisionImageError($request, $e->getMessage());
        }
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

        return redirect('/accounting/request-check/' . $id)->with(
            'success',
            'Funds marked as ready for collection. ' . WorkflowNotifier::recipientName($rfc->request_check_submitted_by ?? $rfc->request_check_requested_by_user_id, 'The Purchaser') . ' has been notified.'
        );
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
        $filter = $this->resolveModuleFilter($request, 'accounting.liq_status', 'incoming', [
            'all', 'incoming', 'revision', 'approved',
        ]);
        $deadlineFilter = $request->query('deadline');
        if (!in_array($deadlineFilter, ['overdue', 'due_today', 'this_week'], true)) {
            $deadlineFilter = null;
        }

        $attentionFocus = AccountingAttentionSummary::focusFor($request, 'liquidation_reports_table');
        if ($attentionFocus) {
            $filter = $attentionFocus['status'];
            $deadlineFilter = null;
            session(['accounting.liq_status' => $filter]);
        }

        // Deadline cards always mean incoming liquidations.
        if ($deadlineFilter) {
            $filter = 'incoming';
            session(['accounting.liq_status' => 'incoming']);
        }

        $query = $this->liqQuery();
        AccountingAttentionSummary::scopeLiqActive($query);

        if ($attentionFocus) {
            AccountingAttentionSummary::applyFocus($query, $attentionFocus['key']);
        } elseif ($deadlineFilter === 'overdue') {
            AccountingAttentionSummary::scopeLiqOverdue($query);
        } elseif ($filter === 'incoming') {
            AccountingAttentionSummary::scopeLiqIncoming($query);
        } elseif ($filter === 'revision') {
            $query->whereIn('liquidation_reports_table.liquidation_report_status', Schema::hasColumn('liquidation_reports_table', 'liquidation_report_revision_notes') ? ['Minor Revision'] : ['Rejected']);
        } elseif ($filter === 'approved') {
            $query->where('liquidation_reports_table.liquidation_report_status', 'Approved');
        }

        $hasDeadlineCol = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_submission_deadline');
        if ($deadlineFilter && $deadlineFilter !== 'overdue' && $hasDeadlineCol) {
            $today = now()->toDateString();
            $weekEnd = now()->copy()->addDays(7)->toDateString();

            if ($deadlineFilter === 'due_today') {
                $query->whereDate('liquidation_reports_table.liquidation_report_submission_deadline', '=', $today);
            } else {
                $query->whereDate('liquidation_reports_table.liquidation_report_submission_deadline', '>=', $today)
                    ->whereDate('liquidation_reports_table.liquidation_report_submission_deadline', '<=', $weekEnd);
            }
        }

        $liqSearch = [
            'liquidation_reports_table.liquidation_report_employee_name',
            'liquidation_reports_table.liquidation_report_status',
        ];
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_form_number')) {
            array_unshift($liqSearch, 'liquidation_reports_table.liquidation_report_form_number');
        }
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')) {
            $liqSearch[] = 'receiving_reports_table.receiving_report_form_number';
        }
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_amount_advance')) {
            $liqSearch[] = 'liquidation_reports_table.liquidation_report_amount_advance';
        }
        $this->applySearch($query, $request, $liqSearch);

        if ($hasDeadlineCol) {
            $query->orderByRaw('CASE
                WHEN liquidation_report_submission_deadline IS NULL THEN 3
                WHEN DATE(liquidation_report_submission_deadline) < ? THEN 0
                WHEN DATE(liquidation_report_submission_deadline) = ? THEN 1
                ELSE 2
            END', [now()->toDateString(), now()->toDateString()]);
        }
        $records = $query->orderByDesc($this->liqSortColumn())->paginate(15)->withQueryString();
        $counts = [
            'all' => $this->countLiqAll(),
            'incoming' => $this->countLiqIncoming(),
            'revision' => $this->countLiqRevision(),
            'approved' => $this->countLiqApproved(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('accounting.liquidation-reports._rows', [
                    'records' => $records,
                    'filter' => $filter,
                    'deadlineFilter' => $deadlineFilter,
                ])->render(),
                'counts' => $counts,
                'focus' => $attentionFocus['key'] ?? null,
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'pagination_html' => $records->hasPages()
                    ? view('pagination.president', ['paginator' => $records])->render()
                    : '',
                'deadline_filter' => $deadlineFilter,
            ]);
        }

        return view('accounting.liquidation-reports.index', compact('records', 'filter', 'counts', 'deadlineFilter', 'attentionFocus'));
    }

    public function showLiquidation(Request $request, $id)
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
            if ($rr && !empty($rr->receiving_report_atp_id)) {
                $atpId = (int) $rr->receiving_report_atp_id;
            } elseif ($rr && Schema::hasTable('request_check_table') && !empty($rr->receiving_report_request_check_id)) {
                $rfc = DB::table('request_check_table')->where('request_check_id', $rr->receiving_report_request_check_id)->first();
                $atpId = (int) ($rfc->request_check_authority_purchase_id ?? 0);
            } elseif ($rr) {
                $atpId = (int) ($rr->receiving_report_authority_purchase_id ?? $rr->authority_purchase_id ?? 0);
            }
        }
        $chain = $this->chainFromAtp($atpId);
        $history = $this->documentHistory('LIQ', (int) $id);
        $reviewable = in_array($liq->liquidation_report_status, self::LIQ_INCOMING, true);
        $returnStatus = $this->resolveReturnStatus($request, 'accounting.liq_status', 'incoming');

        $savedSignatures = UserSignatureLibrary::forUser((int) Auth::id());

        return view('accounting.liquidation-reports.show', compact(
            'liq',
            'rows',
            'attachments',
            'chain',
            'history',
            'reviewable',
            'returnStatus',
            'savedSignatures'
        ));
    }

    public function approveLiquidation(Request $request, $id)
    {
        $liq = $this->lockLiq($id);
        abort_if(!$liq, 404);
        ReviewerAssignment::assertCanAct(
            isset($liq->liquidation_report_assigned_reviewer_id)
                ? (int) $liq->liquidation_report_assigned_reviewer_id
                : null,
            'Liquidation Report'
        );

        if (!in_array($liq->liquidation_report_status, self::LIQ_INCOMING, true)) {
            return back()->with('error', 'This liquidation report is not awaiting Accounting review.');
        }

        $balance = (float) ($liq->liquidation_report_summary_balance ?? 0);
        if ($balance > 0.009 && blank($liq->liquidation_report_cash_returned_or_no ?? null)) {
            return back()->with(
                'error',
                'Unused cash is ₱'.number_format($balance, 2).'. Purchaser must record Cash Returned Under OR# before Accounting can approve.'
            );
        }

        $name = \App\Support\AccountingSigner::nameFromRequest($request);
        $liqPayload = [
            'liquidation_report_status' => 'Approved',
            'liquidation_report_review_stage' => 'completed',
            'liquidation_report_checked_by_accountant' => RisWorkflow::drawnOrName($request->input('signature_data'), $name),
            'liquidation_report_checked_by_date' => now()->toDateString(),
            'liquidation_report_updated_at' => now(),
        ];
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_checked_by_user_id')) {
            $liqPayload['liquidation_report_checked_by_user_id'] = Auth::id();
        }
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_checked_by_name')) {
            $liqPayload['liquidation_report_checked_by_name'] = $name;
        }
        $this->liqUpdate($id, $liqPayload);

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

        $message = 'Liquidation approved. Transaction completed. ' . WorkflowNotifier::recipientName($liq->liquidation_report_submitted_by, 'The Purchaser') . ' has been notified.';
        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return redirect('/accounting/liquidation-reports/' . $id)->with('success', $message);
    }

    public function reviseLiquidation(Request $request, $id)
    {
        $validated = $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        if ($imageError = RisRevisionImages::validationError($request)) {
            return $this->revisionImageError($request, $imageError);
        }
        $liq = $this->lockLiq($id);
        abort_if(!$liq, 404);
        ReviewerAssignment::assertCanAct(
            isset($liq->liquidation_report_assigned_reviewer_id)
                ? (int) $liq->liquidation_report_assigned_reviewer_id
                : null,
            'Liquidation Report'
        );

        if (!in_array($liq->liquidation_report_status, self::LIQ_INCOMING, true)) {
            return back()->with('error', 'This liquidation report cannot be sent for revision.');
        }

        try {
            return DocumentRevisionNotes::record($request, 'LIQ', (int) $id, $validated['remarks'], DocumentRevisionNotes::KIND_REVISION, function (array $images) use ($request, $id, $liq, $validated) {
                $revisionStatus = Schema::hasColumn('liquidation_reports_table', 'liquidation_report_revision_notes') ? 'Minor Revision' : 'Rejected';
                $this->liqUpdate($id, [
                    'liquidation_report_status' => $revisionStatus,
                    'liquidation_report_review_stage' => 'purchaser',
                    'liquidation_report_revision_notes' => $validated['remarks'],
                    'liquidation_report_updated_at' => now(),
                ]);

                $suffix = DocumentRevisionNotes::imagesSuffix($images);
                $this->log('LIQ', (int) $id, 'Rejected', $validated['remarks']);
                $this->notifyPurchaser(
                    $liq->liquidation_report_submitted_by,
                    'Liquidation revision required',
                    ($liq->liquidation_report_form_number ?: ('LIQ #' . $id)) . ': ' . $validated['remarks'] . $suffix,
                    'liq_revision',
                    'LIQ',
                    (int) $id,
                    '/purchaser/liquidation-reports'
                );

                $message = 'Revision requested. ' . WorkflowNotifier::recipientName($liq->liquidation_report_submitted_by, 'The Purchaser') . ' has been notified' . $suffix . '.';
                if ($request->ajax()) {
                    return response()->json(['ok' => true, 'message' => $message]);
                }
                return redirect('/accounting/liquidation-reports?status=' . urlencode(session('accounting.liq_status', 'incoming')))
                    ->with('success', $message);
            });
        } catch (RevisionImageUploadException $e) {
            return $this->revisionImageError($request, $e->getMessage());
        }
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
            foreach ($q->orderByDesc('authority_to_purchase_table.authority_purchase_updated_at')->get() as $row) {
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
            foreach ($q->orderByDesc($this->rfcSortColumn())->get() as $row) {
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
            foreach ($q->orderByDesc($this->liqSortColumn())->get() as $row) {
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

        $merged = $rows->sortByDesc('when')->values();

        // Paginate the merged records using the same pagination logic as the President module.
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $records = new LengthAwarePaginator(
            $merged->forPage($page, $perPage),
            $merged->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('accounting._history-rows', compact('records'))->render(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'pagination_html' => $records->hasPages()
                    ? view('pagination.president', ['paginator' => $records])->render()
                    : '',
            ]);
        }

        return view('accounting.history', compact('records', 'type', 'search'));
    }

    public function financialRecords(Request $request)
    {
        return $this->history($request);
    }

    public function notifications(Request $request)
    {
        $period = $request->get('period', 'today');
        $allowedPeriods = ['today', 'week', 'month', 'year'];

        if (! in_array($period, $allowedPeriods, true)) {
            $period = 'today';
        }

        $items = collect();
        try {
            $query = WorkflowNotifier::scopeVisibleTo(DB::table('notifications_table'), Auth::id(), 'Accounting');

            switch ($period) {
                case 'week':
                    $query->whereBetween(
                        'notification_created_at',
                        [now()->startOfWeek(), now()->endOfWeek()]
                    );
                    break;
                case 'month':
                    $query
                        ->whereYear('notification_created_at', now()->year)
                        ->whereMonth('notification_created_at', now()->month);
                    break;
                case 'year':
                    $query->whereYear('notification_created_at', now()->year);
                    break;
                default:
                    $query->whereDate('notification_created_at', today());
                    break;
            }

            $items = $query
                ->orderByDesc('notification_created_at')
                ->paginate(15)
                ->withQueryString();
        } catch (\Throwable $e) {
            $items = new LengthAwarePaginator(collect(), 0, 15);
        }

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('accounting._notif-items', compact('items', 'period'))->render(),
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'pagination_html' => $items->hasPages()
                    ? view('pagination.president', ['paginator' => $items])->render()
                    : '',
            ]);
        }

        return view('accounting.notifications.index', compact('items', 'period'));
    }

    public function storeSavedSignature(Request $request)
    {
        $validated = $request->validate([
            'signature_label' => ['nullable', 'string', 'max:120'],
            'signature_image' => ['nullable', 'string', 'max:2000000'],
            'signature_file' => ['nullable', 'image', 'max:2048'],
        ]);

        $userId = (int) Auth::id();
        $label = isset($validated['signature_label']) ? trim((string) $validated['signature_label']) : null;

        if (!UserSignatureLibrary::canSaveMore($userId)) {
            return response()->json([
                'ok' => false,
                'message' => 'You can save up to 4 signatures only. Remove one from your list first.',
                'max' => UserSignatureLibrary::MAX_PER_USER,
                'signatures' => UserSignatureLibrary::forUser($userId)->map(fn ($item) => [
                    'id' => (int) $item->user_signature_id,
                    'label' => (string) ($item->user_signature_label ?? 'Signature'),
                    'preview_url' => (string) ($item->preview_url ?? ''),
                ])->values(),
            ], 422);
        }

        $row = null;

        if ($request->hasFile('signature_file')) {
            $row = UserSignatureLibrary::storeFromUpload($userId, $request->file('signature_file'), $label);
        } else {
            $row = UserSignatureLibrary::storeFromDataUrl(
                $userId,
                (string) ($validated['signature_image'] ?? ''),
                $label
            );
        }

        if (!$row) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not save that signature. Use a PNG/JPG under 2 MB.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'signature' => [
                'id' => (int) $row->user_signature_id,
                'label' => (string) ($row->user_signature_label ?? 'Signature'),
                'preview_url' => (string) ($row->preview_url ?? ''),
            ],
            'max' => UserSignatureLibrary::MAX_PER_USER,
            'signatures' => UserSignatureLibrary::forUser($userId)->map(fn ($item) => [
                'id' => (int) $item->user_signature_id,
                'label' => (string) ($item->user_signature_label ?? 'Signature'),
                'preview_url' => (string) ($item->preview_url ?? ''),
            ])->values(),
        ]);
    }

    public function destroySavedSignature(Request $request, $signatureId)
    {
        $deleted = UserSignatureLibrary::deleteForUser((int) Auth::id(), (int) $signatureId);
        if (!$deleted) {
            return response()->json([
                'ok' => false,
                'message' => 'Signature not found.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'signatures' => UserSignatureLibrary::forUser((int) Auth::id())->map(fn ($item) => [
                'id' => (int) $item->user_signature_id,
                'label' => (string) ($item->user_signature_label ?? 'Signature'),
                'preview_url' => (string) ($item->preview_url ?? ''),
            ])->values(),
        ]);
    }

    private function metrics(): array
    {
        $attention = AccountingAttentionSummary::counts();

        return [
            'atp_pending' => $attention['atpPending'],
            'rfc_pending' => $attention['rfcPending'],
            'funds_awaiting' => $attention['fundsAwaiting'],
            'funds_released' => $this->countFundsReleased(),
            'liq_pending' => $attention['liqPending'],
            'atp_revision' => $this->countAtpRevision(),
            'rfc_revision' => $this->countRfcRevision(),
            'liq_revision' => $this->countLiqRevision(),
            'atp_approved' => $this->countAtpApproved(),
            'rfc_approved' => $this->countRfcApproved(),
            'liq_approved' => $this->countLiqApproved(),
            'needs_revision' => $this->countAtpRevision() + $this->countRfcRevision() + $this->countLiqRevision(),
        ];
    }

    private function financialSummary(): array
    {
        $received = 0.0;
        $released = 0.0;
        $liquidated = 0.0;

        if (Schema::hasTable('request_check_table') && $this->rfcHas('request_check_amount_figures')) {
            $rfcBase = DB::table('request_check_table')->where('request_check_status', 'Approved');
            $received = (float) (clone $rfcBase)->sum('request_check_amount_figures');

            if ($this->rfcHas('request_check_funds_released_at')) {
                $released = (float) (clone $rfcBase)
                    ->whereNotNull('request_check_funds_released_at')
                    ->sum('request_check_amount_figures');
            }
        }

        if (Schema::hasTable('liquidation_reports_table')) {
            $liqQuery = DB::table('liquidation_reports_table')
                ->where('liquidation_report_status', 'Approved');

            if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_summary_actual_expense')
                && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_amount_advance')) {
                $liquidated = (float) $liqQuery->sum(DB::raw(
                    'COALESCE(liquidation_report_summary_actual_expense, liquidation_report_amount_advance, 0)'
                ));
            } elseif (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_summary_actual_expense')) {
                $liquidated = (float) $liqQuery->sum('liquidation_report_summary_actual_expense');
            } elseif (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_amount_advance')) {
                $liquidated = (float) $liqQuery->sum('liquidation_report_amount_advance');
            }
        }

        $remaining = $received - $released;

        return [
            'received' => $received,
            'released' => $released,
            'liquidated' => $liquidated,
            'remaining' => $remaining,
        ];
    }

    private function fundsReleasedChartYears(): array
    {
        $current = (int) now()->year;
        $years = [$current];

        if (Schema::hasTable('request_check_table') && $this->rfcHas('request_check_funds_released_at')) {
            try {
                $fromDb = DB::table('request_check_table')
                    ->whereNotNull('request_check_funds_released_at')
                    ->selectRaw('DISTINCT YEAR(request_check_funds_released_at) as y')
                    ->orderByDesc('y')
                    ->pluck('y')
                    ->map(fn ($y) => (int) $y)
                    ->filter(fn ($y) => $y > 2000)
                    ->all();
                $years = array_values(array_unique(array_merge($fromDb, $years)));
            } catch (\Throwable $e) {
                // keep current year
            }
        }

        rsort($years, SORT_NUMERIC);

        // Always offer a short window around the current year for empty databases.
        for ($y = $current; $y >= $current - 4; $y--) {
            if (!in_array($y, $years, true)) {
                $years[] = $y;
            }
        }
        rsort($years, SORT_NUMERIC);

        return $years;
    }

    private function fundsReleasedChart(int $year): array
    {
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $months = [];
        $yearTotal = 0.0;
        $yearReleases = 0;

        $hasAmount = Schema::hasTable('request_check_table')
            && $this->rfcHas('request_check_funds_released_at')
            && $this->rfcHas('request_check_amount_figures');

        for ($m = 1; $m <= 12; $m++) {
            $released = 0.0;
            $count = 0;

            if ($hasAmount) {
                $base = DB::table('request_check_table')
                    ->whereNotNull('request_check_funds_released_at')
                    ->whereYear('request_check_funds_released_at', $year)
                    ->whereMonth('request_check_funds_released_at', $m);

                $released = (float) (clone $base)->sum('request_check_amount_figures');
                $count = (int) (clone $base)->count();
            }

            $yearTotal += $released;
            $yearReleases += $count;

            $months[] = [
                'month' => $m,
                'month_label' => $monthNames[$m],
                'released' => round($released, 2),
                'count' => $count,
                'average' => $count > 0 ? round($released / $count, 2) : 0.0,
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
            'total' => round($yearTotal, 2),
            'releases' => $yearReleases,
        ];
    }

    private function deadlines(): array
    {
        if (!Schema::hasTable('liquidation_reports_table') || !Schema::hasColumn('liquidation_reports_table', 'liquidation_report_submission_deadline')) {
            return [
                'overdue' => 0,
                'due_today' => 0,
                'this_week' => 0,
            ];
        }

        $today = now()->toDateString();
        $weekEnd = now()->copy()->addDays(7)->toDateString();

        $base = function () {
            $query = AccountingAttentionSummary::queue('liquidation_reports_table');
            AccountingAttentionSummary::scopeLiqIncoming($query);

            return $query;
        };

        $overdue = AccountingAttentionSummary::countFocus('overdue');

        $dueToday = (int) $base()
            ->whereDate('liquidation_report_submission_deadline', '=', $today)
            ->count();

        $thisWeek = (int) $base()
            ->whereDate('liquidation_report_submission_deadline', '>=', $today)
            ->whereDate('liquidation_report_submission_deadline', '<=', $weekEnd)
            ->count();

        return [
            'overdue' => $overdue,
            'due_today' => $dueToday,
            'this_week' => $thisWeek,
        ];
    }

    private function resolveModuleFilter(Request $request, string $sessionKey, string $default, array $allowed): string
    {
        $filter = $request->query('status');
        if ($filter === null || $filter === '') {
            // Fresh entry into the module (sidebar / no query) always opens Needs review.
            $filter = $default;
        }

        if (!in_array($filter, $allowed, true)) {
            $filter = $default;
        }

        session([$sessionKey => $filter]);

        return $filter;
    }

    private function resolveReturnStatus(Request $request, string $sessionKey, string $default): string
    {
        $status = $request->query('return_status', session($sessionKey, $default));
        if (!is_string($status) || $status === '') {
            $status = $default;
        }
        session([$sessionKey => $status]);

        return $status;
    }

    private function countAtpIncoming(): int
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return 0;
        }
        return AccountingAttentionSummary::countFocus('atp-review');
    }

    private function countAtpAll(): int
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return 0;
        }
        return (int) AccountingAttentionSummary::queue('authority_to_purchase_table')
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
        return (int) AccountingAttentionSummary::queue('authority_to_purchase_table')
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
        return (int) AccountingAttentionSummary::queue('authority_to_purchase_table')->where('authority_purchase_status', 'Approved')->count();
    }

    private function countRfcIncoming(): int
    {
        if (!Schema::hasTable('request_check_table')) {
            return 0;
        }
        return AccountingAttentionSummary::countFocus('rfc-review');
    }

    private function countRfcRevision(): int
    {
        if (!Schema::hasTable('request_check_table')) {
            return 0;
        }
        return (int) AccountingAttentionSummary::queue('request_check_table')->whereIn('request_check_status', $this->rfcRevisionStatuses())->count();
    }

    private function countRfcApproved(): int
    {
        if (!Schema::hasTable('request_check_table')) {
            return 0;
        }
        return (int) AccountingAttentionSummary::queue('request_check_table')->where('request_check_status', 'Approved')->count();
    }

    private function countFundsAwaiting(): int
    {
        return AccountingAttentionSummary::countFocus('funds');
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
        return AccountingAttentionSummary::countFocus('liq-review');
    }

    private function countLiqAll(): int
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return 0;
        }
        $query = AccountingAttentionSummary::queue('liquidation_reports_table');
        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            });
        }
        return (int) $query->count();
    }

    private function countLiqRevision(): int
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return 0;
        }
        return (int) AccountingAttentionSummary::queue('liquidation_reports_table')->where('liquidation_report_status', 'Minor Revision')->count();
    }

    private function countLiqApproved(): int
    {
        if (!Schema::hasTable('liquidation_reports_table')) {
            return 0;
        }
        return (int) AccountingAttentionSummary::queue('liquidation_reports_table')->where('liquidation_report_status', 'Approved')->count();
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

        $this->applyAssignedReviewerFilter(
            $query,
            'authority_to_purchase_table',
            'authority_purchase_assigned_reviewer_id'
        );

        return DocumentUrgency::select($query->select($select), 'ATP');
    }

    private function applyAssignedReviewerFilter($query, string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        ReviewerAssignment::applyQueueFilter($query, $table.'.'.$column);
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

        $this->applyAssignedReviewerFilter(
            $query,
            'request_check_table',
            'request_check_assigned_reviewer_id'
        );

        return DocumentUrgency::select($query->select(
            'request_check_table.*',
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_ris_id',
            'requisition_issue_slip_table.ris_form_number'
        ), 'RFC');
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

        $this->applyAssignedReviewerFilter(
            $query,
            'liquidation_reports_table',
            'liquidation_report_assigned_reviewer_id'
        );

        return DocumentUrgency::select($query->select($select), 'LIQ');
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
                    'label' => RisWorkflow::formNumber($ris, (int) $atp->authority_purchase_ris_id),
                    'url' => route('accounting.ris.show', $atp->authority_purchase_ris_id),
                    'status' => $ris->ris_status ?? null,
                ];
            }
        }

        if (Schema::hasTable('request_check_table')) {
            $rfc = RfcAtpLinks::latestRfcForAtp($atpId);
            if ($rfc) {
                $chain['rfc'] = [
                    'label' => $rfc->request_check_form_number ?: ('RFC #' . $rfc->request_check_id),
                    'url' => '/accounting/request-check/' . $rfc->request_check_id,
                    'status' => $rfc->request_check_status,
                ];
                $chain['funds'] = [
                    'label' => !empty($rfc->request_check_funds_released_at) ? 'Released' : 'Not released',
                    'url' => '/accounting/request-check/' . $rfc->request_check_id . '?view=funds',
                    'status' => !empty($rfc->request_check_funds_released_at) ? 'Released' : 'Pending',
                ];

                if (Schema::hasTable('receiving_reports_table')) {
                    $rr = DB::table('receiving_reports_table')
                        ->where(function ($q) use ($rfc, $atpId) {
                            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
                                $q->where(function ($own) use ($rfc) {
                                    $own->where('receiving_report_request_check_id', $rfc->request_check_id)
                                        ->whereNull('receiving_report_atp_id');
                                })->orWhere('receiving_report_atp_id', $atpId);
                            } else {
                                $q->where('receiving_report_request_check_id', $rfc->request_check_id);
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

    private function risItemsWithLookups(array $risIds)
    {
        $query = DB::table('requisition_issue_slip_items_table')
            ->whereIn('requisition_issue_slip_items_table.ris_id', $risIds)
            ->orderBy('ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];

        if (Schema::hasTable('uom_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')) {
            $query->leftJoin('uom_table', 'uom_table.uom_id', '=', 'requisition_issue_slip_items_table.ris_item_uom_id');
            $select[] = 'uom_table.uom_name';
        }

        if (Schema::hasTable('brands_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')) {
            $query->leftJoin('brands_table', 'brands_table.brand_id', '=', 'requisition_issue_slip_items_table.ris_item_brand_id');
            $select[] = 'brands_table.brand_name';
        }

        if (Schema::hasTable('suppliers_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')) {
            $query
                ->leftJoin('suppliers_table', 'suppliers_table.supplier_id', '=', 'requisition_issue_slip_items_table.ris_item_supplier_id')
                ->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');

            $select[] = DB::raw(
                "CASE
                    WHEN suppliers_table.supplier_store_type = 'Online Store'
                        THEN COALESCE(online_suppliers_table.shop_name, CONCAT('Online supplier #', suppliers_table.supplier_id))
                    ELSE COALESCE(physical_suppliers_table.company_name, CONCAT('Physical supplier #', suppliers_table.supplier_id))
                END as supplier_display_name"
            );
        }

        return $query->select($select)->get();
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
