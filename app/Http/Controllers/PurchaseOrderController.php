<?php

namespace App\Http\Controllers;

use App\Services\DocumentWorkflowService;
use App\Support\AtpFormNumber;
use App\Support\ProcurementPortal;
use App\Support\PurchaseOrderBasket;
use App\Support\PurchaserDocumentAccess;
use App\Support\ReviewerAssignment;
use App\Support\RisWorkflow;
use App\Support\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(PurchaseOrderBasket::tablesExist(), 404);

        $userId = (int) Auth::id();
        // Draft ATPs are packed into a PO on ATP create/save only — not on every list load,
        // so Remove from a PO is not silently undone on refresh.

        $archiveView = $request->query('view') === 'archive';
        $status = trim((string) $request->query('status', ''));

        $query = DB::table('purchase_orders_table')
            ->where('purchase_order_created_by', $userId);

        if ($archiveView) {
            $query->where('purchase_order_is_archived', 1);
        } else {
            $query->where(function ($q) {
                $q->where('purchase_order_is_archived', 0)
                    ->orWhereNull('purchase_order_is_archived');
            });
        }

        if ($status !== '' && in_array($status, [
            PurchaseOrderBasket::STATUS_DRAFT,
            PurchaseOrderBasket::STATUS_SUBMITTED,
            PurchaseOrderBasket::STATUS_APPROVED,
            PurchaseOrderBasket::STATUS_REJECTED,
            PurchaseOrderBasket::STATUS_CANCELLED,
        ], true)) {
            $query->where('purchase_order_status', $status);
        }

        $orders = $query
            ->orderByRaw("CASE purchase_order_status WHEN 'Draft' THEN 0 WHEN 'Submitted' THEN 1 ELSE 2 END")
            ->orderByDesc('purchase_order_updated_at')
            ->orderByDesc('purchase_order_id')
            ->paginate(15)
            ->withQueryString();

        PurchaseOrderBasket::attachToOrders($orders->getCollection());

        $viewPoId = (int) $request->query('view_po', 0);
        $editPoId = (int) $request->query('edit_po', 0);

        $availableAtps = $this->availableDraftAtps($userId);

        return view('purchaser.purchase-orders.index', [
            'orders' => $orders,
            'archiveView' => $archiveView,
            'statusFilter' => $status,
            'viewPoId' => $viewPoId ?: null,
            'editPoId' => $editPoId ?: null,
            'availableAtps' => $availableAtps,
            'maxAtps' => PurchaseOrderBasket::MAX_ATPS,
            'pp' => ProcurementPortal::prefix(),
            'procurementLayout' => ProcurementPortal::layout(),
        ]);
    }

    public function show($id)
    {
        return ProcurementPortal::redirect('purchase-orders.index', [
            'view_po' => $id,
        ]);
    }

    public function edit($id)
    {
        $order = $this->findOwnedOrder($id);
        abort_if(! $order, 404);

        if ((string) $order->purchase_order_status !== PurchaseOrderBasket::STATUS_DRAFT) {
            return ProcurementPortal::redirect('purchase-orders.index', ['view_po' => $id])
                ->with('error', 'Only draft Purchase Orders can be edited.');
        }

        return ProcurementPortal::redirect('purchase-orders.index', [
            'edit_po' => $id,
        ]);
    }

    public function attach(Request $request, $id)
    {
        $order = $this->findOwnedOrder($id);
        abort_if(! $order, 404);

        if ((string) $order->purchase_order_status !== PurchaseOrderBasket::STATUS_DRAFT) {
            return back()->with('error', 'Only draft Purchase Orders can be updated.');
        }

        $validated = $request->validate([
            'authority_purchase_id' => ['required', 'integer'],
        ]);

        $atpId = (int) $validated['authority_purchase_id'];
        $userId = (int) Auth::id();

        if (PurchaseOrderBasket::openSlots((int) $id) < 1) {
            return back()->with('error', 'This Purchase Order already has '.PurchaseOrderBasket::MAX_ATPS.' ATPs.');
        }

        if (! PurchaseOrderBasket::isEligibleDraftAtp($atpId, $userId)) {
            return back()->with('error', 'That ATP cannot be added to this Purchase Order.');
        }

        // Force attach onto this specific PO (not another open one).
        if (PurchaseOrderBasket::poIdForAtp($atpId)) {
            return back()->with('error', 'That ATP is already on a Purchase Order.');
        }

        DB::table('purchase_order_atps_table')->insert([
            'purchase_order_id' => (int) $id,
            'authority_purchase_id' => $atpId,
            'created_at' => now(),
        ]);

        DB::table('purchase_orders_table')
            ->where('purchase_order_id', $id)
            ->update(['purchase_order_updated_at' => now()]);

        return redirect()
            ->route(ProcurementPortal::routeName('purchase-orders.index'), ['edit_po' => $id])
            ->with('success', 'ATP added to Purchase Order.');
    }

    public function detach(Request $request, $id)
    {
        $order = $this->findOwnedOrder($id);
        abort_if(! $order, 404);

        if ((string) $order->purchase_order_status !== PurchaseOrderBasket::STATUS_DRAFT) {
            return back()->with('error', 'Only draft Purchase Orders can be updated.');
        }

        $validated = $request->validate([
            'authority_purchase_id' => ['required', 'integer'],
        ]);

        $ok = PurchaseOrderBasket::detachAtp((int) $validated['authority_purchase_id'], (int) $id);
        if (! $ok) {
            return redirect()
                ->route(ProcurementPortal::routeName('purchase-orders.index'), ['edit_po' => $id])
                ->with('error', 'Could not remove that ATP from the Purchase Order.');
        }

        return redirect()
            ->route(ProcurementPortal::routeName('purchase-orders.index'), ['edit_po' => $id])
            ->with('success', 'ATP removed from Purchase Order.');
    }

    public function submit($id)
    {
        $reviewerId = ReviewerAssignment::resolve(request(), WorkflowNotifier::ROLE_ACCOUNTING);

        return DB::transaction(function () use ($id, $reviewerId) {
            $order = DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(! $order, 404);
            $this->assertOwnsOrder($order);

            if ((string) $order->purchase_order_status !== PurchaseOrderBasket::STATUS_DRAFT) {
                return back()->with('error', 'Only draft Purchase Orders can be submitted.');
            }

            $atpIds = PurchaseOrderBasket::atpIdsForPo((int) $id);
            if ($atpIds === []) {
                return back()->with('error', 'Add at least one ATP before submitting the Purchase Order.');
            }

            if (count($atpIds) > PurchaseOrderBasket::MAX_ATPS) {
                return back()->with('error', 'A Purchase Order can include at most '.PurchaseOrderBasket::MAX_ATPS.' ATPs.');
            }

            foreach ($atpIds as $atpId) {
                $error = $this->validateAtpReadyForSubmit($atpId);
                if ($error) {
                    return back()->with('error', $error);
                }
            }

            $poNumber = $order->purchase_order_number ?: PurchaseOrderBasket::allocateNumberOnSubmit();
            $now = now();

            $poUpdate = [
                'purchase_order_number' => $poNumber,
                'purchase_order_status' => PurchaseOrderBasket::STATUS_SUBMITTED,
                'purchase_order_submitted_by' => Auth::id(),
                'purchase_order_submitted_at' => $now,
                'purchase_order_revision_reason' => null,
                'purchase_order_updated_at' => $now,
            ];
            if (Schema::hasColumn('purchase_orders_table', 'purchase_order_assigned_reviewer_id')) {
                $poUpdate['purchase_order_assigned_reviewer_id'] = $reviewerId;
            }

            DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->update($poUpdate);

            foreach ($atpIds as $atpId) {
                $atp = DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $atpId)
                    ->lockForUpdate()
                    ->first();

                $formNumber = AtpFormNumber::allocateOnSubmit($atp->authority_purchase_form_number ?? null);

                $update = [
                    'authority_purchase_form_number' => $formNumber,
                    'authority_purchase_status' => 'Pending',
                    'authority_purchase_submitted_by' => Auth::id(),
                    'authority_purchase_submitted_at' => $now,
                    'authority_purchase_rejection_reason' => null,
                    'authority_purchase_updated_at' => $now,
                ];

                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_reference_po_no')) {
                    $update['authority_purchase_reference_po_no'] = $poNumber;
                }
                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_assigned_reviewer_id')) {
                    $update['authority_purchase_assigned_reviewer_id'] = $reviewerId;
                }

                DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $atpId)
                    ->update($update);
            }

            DocumentWorkflowService::notifySubmitted(
                WorkflowNotifier::ROLE_ACCOUNTING,
                'New Purchase Order submitted',
                $poNumber.' ('.count($atpIds).' ATP'.(count($atpIds) === 1 ? '' : 's').') was submitted for Accounting review.',
                'po_submitted',
                'PO',
                (int) $id,
                '/accounting/purchase-orders?status=incoming',
                $reviewerId
            );

            return redirect()
                ->route(ProcurementPortal::routeName('purchase-orders.index'))
                ->with('success', $poNumber.' submitted to Accounting.');
        });
    }

    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $order = DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->lockForUpdate()
                ->first();

            abort_if(! $order, 404);
            $this->assertOwnsOrder($order);

            $status = (string) ($order->purchase_order_status ?? '');
            if (! in_array($status, [
                PurchaseOrderBasket::STATUS_DRAFT,
                PurchaseOrderBasket::STATUS_SUBMITTED,
            ], true)) {
                return back()->with('error', 'Only draft or submitted Purchase Orders can be cancelled.');
            }

            $atpIds = PurchaseOrderBasket::atpIdsForPo((int) $id);
            $now = now();
            $reason = trim($validated['cancel_reason']);
            $label = PurchaseOrderBasket::displayNumber($order);

            DB::table('purchase_orders_table')
                ->where('purchase_order_id', $id)
                ->update([
                    'purchase_order_status' => PurchaseOrderBasket::STATUS_CANCELLED,
                    'purchase_order_submitted_at' => null,
                    'purchase_order_revision_reason' => $reason,
                    'purchase_order_updated_at' => $now,
                ]);

            // Keep ATP links for history, but return ATPs to editable drafts.
            foreach ($atpIds as $atpId) {
                DB::table('authority_to_purchase_table')
                    ->where('authority_purchase_id', $atpId)
                    ->update([
                        'authority_purchase_status' => 'Pending',
                        'authority_purchase_submitted_at' => null,
                        'authority_purchase_rejection_reason' => 'PO cancelled: '.$reason,
                        'authority_purchase_updated_at' => $now,
                    ]);
            }

            $this->logPoHistory((int) $id, 'Cancelled', $reason);

            return redirect()
                ->route(ProcurementPortal::routeName('purchase-orders.index'), ['view_po' => $id])
                ->with('success', $label.' cancelled and kept on record.');
        });
    }

    public function archive($id)
    {
        $order = $this->findOwnedOrder($id);
        abort_if(! $order, 404);

        if (! in_array((string) $order->purchase_order_status, [
            PurchaseOrderBasket::STATUS_APPROVED,
            PurchaseOrderBasket::STATUS_REJECTED,
            PurchaseOrderBasket::STATUS_CANCELLED,
        ], true)) {
            return back()->with('error', 'Only approved, rejected, or cancelled Purchase Orders can be archived.');
        }

        DB::table('purchase_orders_table')
            ->where('purchase_order_id', $id)
            ->update([
                'purchase_order_is_archived' => 1,
                'purchase_order_updated_at' => now(),
            ]);

        return back()->with('success', 'Purchase Order archived.');
    }

    public function restore($id)
    {
        $order = $this->findOwnedOrder($id);
        abort_if(! $order, 404);

        DB::table('purchase_orders_table')
            ->where('purchase_order_id', $id)
            ->update([
                'purchase_order_is_archived' => 0,
                'purchase_order_updated_at' => now(),
            ]);

        return back()->with('success', 'Purchase Order restored.');
    }

    private function findOwnedOrder($id): ?object
    {
        $order = DB::table('purchase_orders_table')
            ->where('purchase_order_id', $id)
            ->first();

        if (! $order) {
            return null;
        }

        $this->assertOwnsOrder($order);

        return $order;
    }

    private function assertOwnsOrder(object $order): void
    {
        if (! PurchaserDocumentAccess::isProcurementActor()) {
            return;
        }

        $owner = (int) ($order->purchase_order_created_by ?? 0);
        if ($owner > 0 && $owner !== (int) Auth::id()) {
            abort(403);
        }
    }

    private function availableDraftAtps(int $userId)
    {
        $ids = PurchaseOrderBasket::eligibleDraftAtpIds($userId);
        if ($ids === []) {
            return collect();
        }

        return DB::table('authority_to_purchase_table')
            ->leftJoin(
                'suppliers_table',
                'authority_to_purchase_table.authority_purchase_supplier_id',
                '=',
                'suppliers_table.supplier_id'
            )
            ->leftJoin(
                'physical_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'physical_suppliers_table.supplier_id'
            )
            ->leftJoin(
                'online_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'online_suppliers_table.supplier_id'
            )
            ->leftJoin(
                'requisition_issue_slip_table',
                'authority_to_purchase_table.authority_purchase_ris_id',
                '=',
                'requisition_issue_slip_table.ris_id'
            )
            ->whereIn('authority_to_purchase_table.authority_purchase_id', $ids)
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_date',
                'requisition_issue_slip_table.ris_form_number',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->orderBy('authority_to_purchase_table.authority_purchase_id')
            ->get()
            ->map(function ($row) {
                $row->supplier_display = ($row->supplier_store_type ?? null) === 'Online Store'
                    ? (string) ($row->shop_name ?: 'Online supplier')
                    : (string) ($row->company_name ?: 'Supplier');

                return $row;
            });
    }

    private function validateAtpReadyForSubmit(int $atpId): ?string
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->first();

        if (! $atp) {
            return 'One of the linked ATPs no longer exists.';
        }

        $label = filled($atp->authority_purchase_form_number)
            ? (string) $atp->authority_purchase_form_number
            : 'Draft ATP (Record #'.$atpId.')';

        if ((string) ($atp->authority_purchase_status ?? '') !== 'Pending' || filled($atp->authority_purchase_submitted_at ?? null)) {
            return "{$label} is not a draft and cannot be submitted with this Purchase Order.";
        }

        if (blank($atp->authority_purchase_supplier_id)) {
            return "{$label}: supplier is required before submitting.";
        }

        if (blank($atp->authority_purchase_date)) {
            return "{$label}: date is required before submitting.";
        }

        if (blank($atp->authority_purchase_received_by_name)) {
            return "{$label}: Received By is required before submitting.";
        }

        if (
            Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_received_by_signature')
            && ! RisWorkflow::isDrawnSignature((string) ($atp->authority_purchase_received_by_signature ?? ''))
        ) {
            return "{$label}: draw or upload the signature before submitting.";
        }

        $items = DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $atpId)
            ->get();

        if ($items->isEmpty()) {
            return "{$label}: add at least one item before submitting.";
        }

        foreach ($items as $index => $item) {
            $row = $index + 1;
            if (blank($item->atp_description)) {
                return "{$label} item {$row}: description is required.";
            }
            if ((int) $item->atp_quantity < 1) {
                return "{$label} item {$row}: quantity must be at least 1.";
            }
            if (blank($item->atp_unit)) {
                return "{$label} item {$row}: unit is required.";
            }
            if ($item->atp_unit_price === null || $item->atp_unit_price === '') {
                return "{$label} item {$row}: unit price is required.";
            }
        }

        return null;
    }

    private function logPoHistory(int $id, string $status, ?string $remarks = null): void
    {
        if (! Schema::hasTable('approval_logs_table')) {
            return;
        }

        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'PO',
                'approval_log_reference_id' => $id,
                'approval_log_level' => 'Purchaser',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => $status,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Keep cancel flow resilient if audit table shape differs.
        }
    }
}
