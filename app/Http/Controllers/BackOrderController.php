<?php

namespace App\Http\Controllers;

use App\Services\DocumentWorkflowService;
use App\Support\BackOrders;
use App\Support\ProcurementPaymentPath;
use App\Support\ProcurementPortal;
use App\Support\PurchaserDocumentAccess;
use App\Support\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BackOrderController extends Controller
{
    private const FILTERS = [
        'unresolved' => 'Needs action',
        'waiting_restock' => 'Waiting for restock',
        'refunded' => 'Refunded',
        'receiving' => 'For second count',
        'resolved' => 'Delivered',
        'all' => 'All',
    ];

    /** RR statuses where a replacement row can be added to the same RR. */
    private const REPLACEMENT_RR_STATUSES = ['Incomplete', 'Resubmitted', 'Minor Revision'];

    /** RR statuses where a replacement row can still be taken back (Receiving has not started counting it). */
    private const UNDO_RR_STATUSES = ['Resubmitted', 'Minor Revision'];

    private const MAX_RR_ROWS = 9;

    public function index(Request $request)
    {
        $portal = $this->portal();
        $filter = array_key_exists($request->query('status'), self::FILTERS) ? $request->query('status') : 'unresolved';
        $type = array_key_exists($request->query('type'), BackOrders::TYPES) ? $request->query('type') : null;
        $payment = in_array($request->query('payment'), ['rfc', 'ca'], true) ? $request->query('payment') : null;
        $supplier = in_array($request->query('supplier'), ['original', 'new'], true) ? $request->query('supplier') : null;
        $rootRrId = (int) $request->query('rr', 0);

        $backOrders = collect();
        $summary = ['unresolved' => 0, 'waiting_restock' => 0, 'refunded' => 0, 'receiving' => 0, 'resolved' => 0];
        $suppliers = collect();

        if (BackOrders::supported()) {
            $summaryQuery = $this->baseQuery($portal);
            $this->applyPaymentFilter($summaryQuery, $payment);
            foreach ($summaryQuery->select('bo.back_order_status', DB::raw('COUNT(*) as aggregate'))->groupBy('bo.back_order_status')->get() as $row) {
                $count = (int) $row->aggregate;
                $summary[BackOrders::isUnresolved($row->back_order_status) ? 'unresolved' : 'resolved'] += $count;
                if (in_array($row->back_order_status, [BackOrders::STATUS_WAITING_RESTOCK, BackOrders::STATUS_REFUNDED, BackOrders::STATUS_RECEIVING], true)) {
                    $summary[$row->back_order_status] += $count;
                }
            }

            $query = $this->baseQuery($portal);
            match ($filter) {
                'unresolved' => $query->whereIn('bo.back_order_status', BackOrders::UNRESOLVED),
                'resolved' => $query->whereNotIn('bo.back_order_status', BackOrders::UNRESOLVED),
                'waiting_restock', 'refunded', 'receiving' => $query->where('bo.back_order_status', $filter),
                default => null,
            };
            if ($type) {
                $query->where('bo.back_order_type', $type);
            }
            $this->applyPaymentFilter($query, $payment);
            if ($supplier === 'new') {
                $query->whereNotNull('bo.back_order_replacement_supplier_name');
            } elseif ($supplier === 'original') {
                $query->whereNull('bo.back_order_replacement_supplier_name');
            }
            if ($rootRrId > 0) {
                $query->where('bo.back_order_root_receiving_report_id', $rootRrId);
            }
            if ($request->filled('search')) {
                $like = '%'.trim((string) $request->query('search')).'%';
                $query->where(function ($q) use ($like) {
                    $q->where('bo.back_order_article', 'like', $like)
                        ->orWhere('bo.back_order_number', 'like', $like)
                        ->orWhere('bo.back_order_supplier_name', 'like', $like)
                        ->orWhere('bo.back_order_replacement_supplier_name', 'like', $like)
                        ->orWhere('root.receiving_report_form_number', 'like', $like);
                });
            }

            $backOrders = $query
                ->orderByRaw("CASE WHEN bo.back_order_status IN ('".implode("','", BackOrders::UNRESOLVED)."') THEN 0 ELSE 1 END")
                ->orderByDesc('bo.back_order_id')
                ->paginate(15)
                ->withQueryString();

            $rowCounts = $this->rowCounts($backOrders->getCollection()->pluck('back_order_root_receiving_report_id')->unique()->all());
            $verifiedRows = $this->verifiedFlags($backOrders->getCollection()->pluck('back_order_replacement_item_id')->filter()->all());
            foreach ($backOrders as $bo) {
                $this->decorate($bo, (int) ($rowCounts[(int) $bo->back_order_root_receiving_report_id] ?? 0), $verifiedRows);
            }

            $suppliers = $this->activeSuppliers();
        }

        return view('back-orders.index', [
            'portal' => $portal,
            'layout' => $this->layoutFor($portal),
            'canManage' => $portal === 'purchaser',
            'routePrefix' => $this->routePrefix($portal),
            'backOrders' => $backOrders,
            'summary' => $summary,
            'filters' => self::FILTERS,
            'filter' => $filter,
            'typeFilter' => $type,
            'paymentFilter' => $payment,
            'supplierFilter' => $supplier,
            'rootRrId' => $rootRrId ?: null,
            'suppliers' => $suppliers,
            'supported' => BackOrders::supported(),
        ]);
    }

    /**
     * Purchaser records what the supplier said: reason, notes, attachments, and whether we are waiting for restock.
     */
    public function update(Request $request, $id)
    {
        $bo = $this->findOwned($id);
        if (!BackOrders::isUnresolved($bo->back_order_status)) {
            return back()->with('error', 'This back order is already delivered.');
        }

        $request->validate([
            'back_order_reason' => ['required', Rule::in(array_keys(BackOrders::REASONS))],
            'back_order_remarks' => ['nullable', 'string', 'max:2000'],
            'back_order_status' => ['nullable', Rule::in([BackOrders::STATUS_OPEN, BackOrders::STATUS_WAITING_RESTOCK])],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer', 'min:0'],
        ], [
            'back_order_reason.required' => 'Choose why the items were missing or damaged.',
        ]);

        $existing = BackOrders::decodeFiles($bo->back_order_images);
        $remove = array_map('intval', (array) $request->input('remove_files', []));
        $kept = [];
        $removed = [];
        foreach ($existing as $index => $file) {
            in_array($index, $remove, true) ? $removed[] = $file : $kept[] = $file;
        }
        if ($error = BackOrders::fileValidationError($request, 'attachments', false, count($kept))) {
            return back()->with('error', $error);
        }

        try {
            $stored = BackOrders::storeFiles($request, 'attachments', 'back-order-files/'.$bo->back_order_id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $status = $bo->back_order_status;
        if (in_array($status, [BackOrders::STATUS_OPEN, BackOrders::STATUS_WAITING_RESTOCK], true) && $request->filled('back_order_status')) {
            $status = $request->input('back_order_status');
        }

        DB::table(BackOrders::TABLE)->where('back_order_id', $bo->back_order_id)->update([
            'back_order_reason' => $request->input('back_order_reason'),
            'back_order_remarks' => filled($request->input('back_order_remarks')) ? trim((string) $request->input('back_order_remarks')) : null,
            'back_order_status' => $status,
            'back_order_images' => BackOrders::encodeFiles(array_merge($kept, $stored)),
            'back_order_updated_by' => auth()->id(),
            'back_order_updated_at' => now(),
        ]);
        BackOrders::deleteFiles($removed);

        return back()->with('success', 'Back order updated.');
    }

    /**
     * Cash Advance only: the supplier returned the money. The back order stays open until replacement items arrive.
     */
    public function refund(Request $request, $id)
    {
        $bo = $this->findOwned($id);
        if ($bo->back_order_payment_path !== ProcurementPaymentPath::CASH_ADVANCE) {
            return back()->with('error', 'Refunds apply to Cash Advance purchases only. Request for Check stays with the original supplier.');
        }
        if (!in_array($bo->back_order_status, [BackOrders::STATUS_OPEN, BackOrders::STATUS_WAITING_RESTOCK], true)) {
            return back()->with('error', 'A refund can only be recorded before a replacement is bought.');
        }

        $maxRefund = round((float) $bo->back_order_unit_price * (int) $bo->back_order_quantity, 2);
        $request->validate([
            'back_order_refund_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'back_order_refund_reference' => ['nullable', 'string', 'max:255'],
            'back_order_remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'back_order_refund_amount.required' => 'Enter the amount the supplier refunded.',
        ]);
        if ($error = BackOrders::fileValidationError($request, 'refund_files', true)) {
            return back()->with('error', $error);
        }

        $amount = round((float) $request->input('back_order_refund_amount'), 2);
        if ($maxRefund > 0 && $amount > $maxRefund) {
            return back()->with('error', 'Refund cannot exceed ₱'.number_format($maxRefund, 2).' ('.(int) $bo->back_order_quantity.' × ₱'.number_format((float) $bo->back_order_unit_price, 2).').');
        }

        try {
            $stored = BackOrders::storeFiles($request, 'refund_files', 'back-order-files/'.$bo->back_order_id.'/refund');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $update = [
            'back_order_status' => BackOrders::STATUS_REFUNDED,
            'back_order_refund_amount' => $amount,
            'back_order_refund_reference' => filled($request->input('back_order_refund_reference')) ? trim((string) $request->input('back_order_refund_reference')) : null,
            'back_order_refund_images' => BackOrders::encodeFiles($stored),
            'back_order_cash_difference' => $amount,
            'back_order_cash_note' => 'Refund of ₱'.number_format($amount, 2).' received for '.(int) $bo->back_order_quantity.' '.($bo->back_order_unit ?: 'unit').'(s) – use it for the replacement or return it to the cashier.',
            'back_order_updated_by' => auth()->id(),
            'back_order_updated_at' => now(),
        ];
        if (filled($request->input('back_order_remarks'))) {
            $update['back_order_remarks'] = trim((string) $request->input('back_order_remarks'));
        }
        if (blank($bo->back_order_reason)) {
            $update['back_order_reason'] = 'out_of_stock';
        }
        DB::table(BackOrders::TABLE)->where('back_order_id', $bo->back_order_id)->update($update);

        return back()->with('success', 'Refund of ₱'.number_format($amount, 2).' recorded. Buy the replacement and record it here so the RR can be completed.');
    }

    /**
     * Replacement items arrived: add them as a new row on the same RR (the original row stays) and send the RR
     * back to Receiving for second count. Request for Check always stays with the original supplier and price.
     */
    public function replace(Request $request, $id)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999999'],
            'damaged_qty' => ['nullable', 'integer', 'min:0', 'lte:quantity'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers_table,supplier_id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], [
            'damaged_qty.lte' => 'Damaged units cannot be more than the quantity received.',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $bo = DB::table(BackOrders::TABLE)->where('back_order_id', (int) $id)->lockForUpdate()->first();
            abort_if(!$bo, 404);
            $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $bo->back_order_root_receiving_report_id)->lockForUpdate()->first();
            abort_if(!$rr, 404);
            PurchaserDocumentAccess::assertOwns($rr, 'rr');

            if (!in_array($bo->back_order_status, BackOrders::REPLACEABLE, true)) {
                return back()->with('error', 'This back order already has a replacement waiting for second count or is delivered.');
            }
            if (!in_array($rr->receiving_report_status, self::REPLACEMENT_RR_STATUSES, true)) {
                return back()->with('error', 'Replacements can be recorded after Receiving finishes the second count (RR status Incomplete).');
            }
            if (DB::table('receiving_report_items_table')->where('receiving_report_id', $rr->receiving_report_id)->count() >= self::MAX_RR_ROWS) {
                return back()->with('error', 'This Receiving Report already has '.self::MAX_RR_ROWS.' rows, the most it can hold.');
            }

            $quantity = (int) $request->input('quantity');
            if ($quantity > (int) $bo->back_order_quantity) {
                return back()->with('error', 'Only '.(int) $bo->back_order_quantity.' '.($bo->back_order_unit ?: 'unit').'(s) are on back order.');
            }

            $isCashAdvance = $bo->back_order_payment_path === ProcurementPaymentPath::CASH_ADVANCE;
            if ($isCashAdvance) {
                if (!$request->filled('unit_price')) {
                    return back()->with('error', 'Enter the unit price you paid for the replacement.');
                }
                $supplierId = (int) $request->input('supplier_id') ?: null;
                $supplierName = $supplierId ? $this->supplierName($supplierId) : trim((string) $request->input('supplier_name'));
                if ($supplierName === '' || $supplierName === null) {
                    return back()->with('error', 'Choose or type the supplier you bought the replacement from.');
                }
                $unitPrice = round((float) $request->input('unit_price'), 2);
            } else {
                $supplierId = $bo->back_order_supplier_id ? (int) $bo->back_order_supplier_id : null;
                $supplierName = (string) ($bo->back_order_supplier_name ?? '');
                $unitPrice = (float) $bo->back_order_unit_price;
            }
            $isNewSupplier = $isCashAdvance && $this->differentSupplier($bo, $supplierId, $supplierName);

            if ($error = BackOrders::fileValidationError($request, 'replacement_files', $isCashAdvance)) {
                return back()->with('error', $isCashAdvance && str_starts_with($error, 'Attach at least') ? 'Attach the receipt for the replacement purchase.' : $error);
            }
            try {
                $stored = BackOrders::storeFiles($request, 'replacement_files', 'back-order-files/'.$bo->back_order_id.'/replacement');
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            $damaged = min($quantity, (int) $request->input('damaged_qty', 0));
            $remarks = trim((string) $request->input('remarks', ''));
            $itemId = (int) DB::table('receiving_report_items_table')->insertGetId([
                'receiving_report_id' => $rr->receiving_report_id,
                'receiving_report_item_quantity' => $quantity,
                'receiving_report_item_ordered_qty' => (int) $bo->back_order_quantity,
                'receiving_report_item_condition' => $quantity < (int) $bo->back_order_quantity ? 'short' : 'ok',
                'receiving_report_item_condition_remarks' => $remarks !== '' ? $remarks : null,
                'receiving_report_item_unit' => $bo->back_order_unit,
                'receiving_report_item_article' => $bo->back_order_article,
                'receiving_report_item_unit_price' => $unitPrice,
                'receiving_report_item_supplier_id' => $supplierId,
                'receiving_report_item_supplier_name' => $supplierName !== '' ? mb_substr($supplierName, 0, 255) : null,
                'receiving_report_item_back_order_id' => (int) $bo->back_order_id,
                'receiving_report_item_damaged_qty' => $damaged,
                'receiving_report_item_damage_remarks' => $damaged > 0 && $remarks !== '' ? $remarks : null,
                'receiving_report_item_verified' => 0,
            ], 'receiving_report_item_id');

            [$difference, $note] = BackOrders::cashDifference($bo, $quantity, $unitPrice);
            DB::table(BackOrders::TABLE)->where('back_order_id', $bo->back_order_id)->update([
                'back_order_status' => BackOrders::STATUS_RECEIVING,
                'back_order_replacement_item_id' => $itemId,
                'back_order_replacement_supplier_id' => $isNewSupplier ? $supplierId : null,
                'back_order_replacement_supplier_name' => $isNewSupplier ? mb_substr($supplierName, 0, 255) : null,
                'back_order_replacement_quantity' => $quantity,
                'back_order_replacement_unit_price' => $unitPrice,
                'back_order_replacement_reference' => filled($request->input('reference')) ? trim((string) $request->input('reference')) : null,
                'back_order_replacement_files' => BackOrders::encodeFiles($stored),
                'back_order_cash_difference' => $difference,
                'back_order_cash_note' => $note,
                'back_order_updated_by' => auth()->id(),
                'back_order_updated_at' => now(),
            ]);

            BackOrders::syncForRr($rr);

            if ($rr->receiving_report_status === 'Incomplete') {
                $update = [
                    'receiving_report_status' => 'Resubmitted',
                    'receiving_report_submitted_at' => now(),
                    'receiving_report_updated_at' => now(),
                ];
                DB::table('receiving_reports_table')->where('receiving_report_id', $rr->receiving_report_id)->update($update);
                $this->notifyReceiving($rr);
            }

            $message = 'Replacement added as a new row on '.($rr->receiving_report_form_number ?: 'the RR').'.';
            $message .= $rr->receiving_report_status === 'Minor Revision'
                ? ' Submit the RR revision so Receiving can do the second count.'
                : ' Sent to Receiving for second count.';

            return back()->with('success', $note ? $message.' '.$note : $message);
        });
    }

    /**
     * Take back a replacement row Receiving has not counted yet.
     */
    public function undoReplacement($id)
    {
        return DB::transaction(function () use ($id) {
            $bo = DB::table(BackOrders::TABLE)->where('back_order_id', (int) $id)->lockForUpdate()->first();
            abort_if(!$bo, 404);
            $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $bo->back_order_root_receiving_report_id)->lockForUpdate()->first();
            abort_if(!$rr, 404);
            PurchaserDocumentAccess::assertOwns($rr, 'rr');

            $row = $bo->back_order_replacement_item_id
                ? DB::table('receiving_report_items_table')->where('receiving_report_item_id', $bo->back_order_replacement_item_id)->first()
                : null;
            if ($bo->back_order_status !== BackOrders::STATUS_RECEIVING || !$row || !empty($row->receiving_report_item_verified)) {
                return back()->with('error', 'There is no uncounted replacement to undo on this back order.');
            }
            if (!in_array($rr->receiving_report_status, self::UNDO_RR_STATUSES, true)) {
                return back()->with('error', 'Receiving is already counting this RR. Ask them to return it if the replacement is wrong.');
            }

            BackOrders::deleteBackOrders(DB::table(BackOrders::TABLE)->where('back_order_receiving_report_item_id', $row->receiving_report_item_id)->get());
            BackOrders::clearReplacement($bo);
            DB::table('receiving_report_items_table')->where('receiving_report_item_id', $row->receiving_report_item_id)->delete();

            $stillPending = DB::table('receiving_report_items_table')
                ->where('receiving_report_id', $rr->receiving_report_id)
                ->where(fn ($q) => $q->whereNull('receiving_report_item_verified')->orWhere('receiving_report_item_verified', 0))
                ->exists();
            if (!$stillPending && BackOrders::hasVerifiedRows((int) $rr->receiving_report_id)) {
                DB::table('receiving_reports_table')->where('receiving_report_id', $rr->receiving_report_id)->update([
                    'receiving_report_status' => BackOrders::rrStatusAfterCount((int) $rr->receiving_report_id),
                    'receiving_report_updated_at' => now(),
                ]);
            }

            return back()->with('success', 'Replacement row removed from the RR. The back order is open again.');
        });
    }

    public function file($id, string $kind, $index)
    {
        $bo = BackOrders::find($id);
        abort_if(!$bo, 404);
        if ($this->portal() === 'purchaser') {
            $this->assertOwnsRoot($bo);
        }

        $files = BackOrders::decodeFiles(match ($kind) {
            'refund' => $bo->back_order_refund_images,
            'replacement' => $bo->back_order_replacement_files ?? null,
            default => $bo->back_order_images,
        });
        $file = $files[(int) $index] ?? null;
        abort_if(!$file || !Storage::disk('public')->exists($file['path']), 404);

        $name = $file['name'] ?: basename($file['path']);

        return BackOrders::isImage($file)
            ? Storage::disk('public')->response($file['path'], $name)
            : Storage::disk('public')->download($file['path'], $name);
    }

    private function decorate(object $bo, int $rrRowCount, array $verifiedRows): void
    {
        $bo->files = BackOrders::decodeFiles($bo->back_order_images);
        $bo->refund_files = BackOrders::decodeFiles($bo->back_order_refund_images);
        $bo->replacement_files = BackOrders::decodeFiles($bo->back_order_replacement_files ?? null);
        $bo->is_cash_advance = $bo->back_order_payment_path === ProcurementPaymentPath::CASH_ADVANCE;
        $bo->is_unresolved = BackOrders::isUnresolved($bo->back_order_status);
        $bo->is_new_supplier = filled($bo->back_order_replacement_supplier_name);
        $bo->is_replacement_line = !empty($bo->source_item_back_order_id);
        $bo->rr_row_count = $rrRowCount;
        $bo->rr_full = $rrRowCount >= self::MAX_RR_ROWS;

        $rrStatus = (string) ($bo->root_rr_status ?? '');
        $bo->can_replace = in_array($bo->back_order_status, BackOrders::REPLACEABLE, true)
            && in_array($rrStatus, self::REPLACEMENT_RR_STATUSES, true)
            && !$bo->rr_full;
        $bo->replace_blocked_reason = match (true) {
            !in_array($bo->back_order_status, BackOrders::REPLACEABLE, true) => null,
            !in_array($rrStatus, self::REPLACEMENT_RR_STATUSES, true) => 'Available after Receiving finishes the second count.',
            $bo->rr_full => 'The RR already has '.self::MAX_RR_ROWS.' rows.',
            default => null,
        };
        $bo->can_refund = $bo->is_cash_advance
            && in_array($bo->back_order_status, [BackOrders::STATUS_OPEN, BackOrders::STATUS_WAITING_RESTOCK], true);
        $bo->can_undo = $bo->back_order_status === BackOrders::STATUS_RECEIVING
            && !empty($bo->back_order_replacement_item_id)
            && empty($verifiedRows[(int) $bo->back_order_replacement_item_id])
            && in_array($rrStatus, self::UNDO_RR_STATUSES, true);
    }

    private function applyPaymentFilter($query, ?string $payment): void
    {
        if ($payment === 'ca') {
            $query->where('bo.back_order_payment_path', ProcurementPaymentPath::CASH_ADVANCE);
        } elseif ($payment === 'rfc') {
            $query->where(fn ($q) => $q->where('bo.back_order_payment_path', '!=', ProcurementPaymentPath::CASH_ADVANCE)
                ->orWhereNull('bo.back_order_payment_path'));
        }
    }

    private function baseQuery(string $portal)
    {
        $query = DB::table(BackOrders::TABLE.' as bo')
            ->join('receiving_reports_table as root', 'root.receiving_report_id', '=', 'bo.back_order_root_receiving_report_id')
            ->leftJoin('receiving_report_items_table as src_item', 'src_item.receiving_report_item_id', '=', 'bo.back_order_receiving_report_item_id')
            ->leftJoin('authority_to_purchase_table as atp', 'atp.authority_purchase_id', '=', 'bo.back_order_atp_id')
            ->leftJoin('request_check_table as rfc', 'rfc.request_check_id', '=', 'bo.back_order_request_check_id')
            ->select(
                'bo.*',
                'root.receiving_report_form_number as root_rr_number',
                'root.receiving_report_status as root_rr_status',
                'src_item.receiving_report_item_back_order_id as source_item_back_order_id',
                'atp.authority_purchase_form_number',
                'rfc.request_check_form_number'
            );

        if ($portal === 'purchaser') {
            PurchaserDocumentAccess::scopeOwned($query, 'rr', 'root');
        }

        return $query;
    }

    /**
     * @return array<int, int> RR id => row count
     */
    private function rowCounts(array $rrIds): array
    {
        if ($rrIds === []) {
            return [];
        }

        return DB::table('receiving_report_items_table')
            ->whereIn('receiving_report_id', $rrIds)
            ->groupBy('receiving_report_id')
            ->select('receiving_report_id', DB::raw('COUNT(*) as aggregate'))
            ->pluck('aggregate', 'receiving_report_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @return array<int, bool> row id => verified
     */
    private function verifiedFlags(array $itemIds): array
    {
        if ($itemIds === [] || !Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_verified')) {
            return [];
        }

        return DB::table('receiving_report_items_table')
            ->whereIn('receiving_report_item_id', $itemIds)
            ->pluck('receiving_report_item_verified', 'receiving_report_item_id')
            ->map(fn ($flag) => (bool) $flag)
            ->all();
    }

    private function activeSuppliers()
    {
        return DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('suppliers_table.supplier_is_active', 1)
            ->select(
                'suppliers_table.supplier_id',
                DB::raw("COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name, 'Unnamed supplier') as supplier_name")
            )
            ->orderBy('supplier_name')
            ->get();
    }

    private function supplierName(int $supplierId): ?string
    {
        return DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('suppliers_table.supplier_id', $supplierId)
            ->value(DB::raw('COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name)'));
    }

    private function differentSupplier(object $bo, ?int $supplierId, string $supplierName): bool
    {
        if ($supplierId && $bo->back_order_supplier_id) {
            return $supplierId !== (int) $bo->back_order_supplier_id;
        }

        return mb_strtolower(trim($supplierName)) !== mb_strtolower(trim((string) ($bo->back_order_supplier_name ?? '')));
    }

    private function notifyReceiving(object $rr): void
    {
        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_RECEIVING,
            'Replacement items for second count',
            ($rr->receiving_report_form_number ?: 'RR #'.$rr->receiving_report_id).' has replacement items waiting for second count.',
            'rr_submitted',
            'RR',
            (int) $rr->receiving_report_id,
            '/receiving/reports',
            !empty($rr->receiving_report_assigned_reviewer_id) ? (int) $rr->receiving_report_assigned_reviewer_id : null
        );
    }

    private function findOwned($id): object
    {
        $bo = BackOrders::find($id);
        abort_if(!$bo, 404);
        $this->assertOwnsRoot($bo);

        return $bo;
    }

    private function assertOwnsRoot(object $bo): void
    {
        $rootRr = DB::table('receiving_reports_table')->where('receiving_report_id', $bo->back_order_root_receiving_report_id)->first();
        abort_if(!$rootRr, 404);
        PurchaserDocumentAccess::assertOwns($rootRr, 'rr');
    }

    private function portal(): string
    {
        return match (true) {
            request()->routeIs('receiving.*') => 'receiving',
            request()->routeIs('admin.*') => 'admin',
            default => 'purchaser',
        };
    }

    private function routePrefix(string $portal): string
    {
        return match ($portal) {
            'receiving' => 'receiving.back-orders',
            'admin' => 'admin.back-orders',
            default => ProcurementPortal::prefix().'.bo',
        };
    }

    private function layoutFor(string $portal): string
    {
        return match ($portal) {
            'receiving' => 'layouts.receiving-layout',
            'admin' => 'layouts.admin-layout',
            default => ProcurementPortal::layout(),
        };
    }
}
