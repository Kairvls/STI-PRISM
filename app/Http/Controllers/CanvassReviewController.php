<?php

namespace App\Http\Controllers;

use App\Support\AdminPortal;
use App\Support\CanvassWorkflow;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CanvassReviewController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(CanvassWorkflow::tablesExist(), 503, 'Canvass tables are not migrated yet. Please run php artisan migrate.');

        $filter = trim((string) $request->query('filter', 'all'));
        if (! in_array($filter, ['pending', 'approved', 'returned', 'rejected', 'all'], true)) {
            $filter = 'all';
        }
        $search = trim((string) $request->query('search', ''));
        $urgency = trim((string) $request->query('urgency', ''));
        if (! in_array($urgency, ['Urgent', 'Non-Urgent'], true)) {
            $urgency = '';
        }
        $openCanvassId = (int) $request->query('open', 0);

        $query = DB::table('canvass_table as c')
            ->leftJoin('users_table as owner', 'owner.user_id', '=', 'c.canvass_owner_user_id')
            ->leftJoin('users_table as reviewer', 'reviewer.user_id', '=', 'c.canvass_reviewed_by')
            ->leftJoin('requisition_issue_slip_table as ris', 'ris.ris_id', '=', 'c.canvass_ris_id')
            ->where('c.canvass_is_archived', 0)
            ->where('c.canvass_status', '!=', CanvassWorkflow::STATUS_DRAFT);

        if ($filter === 'pending') {
            $query->whereIn('c.canvass_status', CanvassWorkflow::pendingReviewStatuses());
        } elseif ($filter === 'approved') {
            $query->whereIn('c.canvass_status', CanvassWorkflow::completedReviewStatuses());
        } elseif ($filter === 'returned') {
            $query->where('c.canvass_status', CanvassWorkflow::STATUS_RETURNED);
        } elseif ($filter === 'rejected') {
            $query->where('c.canvass_status', CanvassWorkflow::STATUS_REJECTED);
        }

        if ($urgency !== '') {
            $query->where('c.canvass_urgency', $urgency);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('c.canvass_number', 'like', $like)
                    ->orWhere('c.canvass_request_no', 'like', $like)
                    ->orWhere('c.canvass_purpose', 'like', $like)
                    ->orWhere('owner.user_full_name', 'like', $like);
            });
        }

        $canvasses = $query
            ->select([
                'c.*',
                'owner.user_full_name as owner_name',
                'reviewer.user_full_name as reviewer_name',
                'ris.ris_form_number as linked_ris_number',
                'ris.ris_status as linked_ris_status',
            ])
            ->orderByRaw("CASE WHEN c.canvass_status IN ('Awaiting for review','Reviewing - Awaiting for approval') THEN 0 ELSE 1 END")
            ->orderByDesc('c.canvass_submitted_at')
            ->orderByDesc('c.canvass_id')
            ->paginate(10)
            ->withQueryString();

        $workspaces = [];
        foreach ($canvasses as $row) {
            $cid = (int) $row->canvass_id;
            $full = CanvassWorkflow::loadFull($cid);
            if ($full) {
                $workspaces[$cid] = $full;
                $row->item_count = $full['items']->count();
                $row->approved_item_count = $full['items']->where('canvass_item_status', CanvassWorkflow::ITEM_APPROVED)->count();
            } else {
                $row->item_count = 0;
                $row->approved_item_count = 0;
            }
        }

        $counts = [
            'pending' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->whereIn('canvass_status', CanvassWorkflow::pendingReviewStatuses())
                ->count(),
            'approved' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->whereIn('canvass_status', CanvassWorkflow::completedReviewStatuses())
                ->count(),
            'returned' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->where('canvass_status', CanvassWorkflow::STATUS_RETURNED)
                ->count(),
            'rejected' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->where('canvass_status', CanvassWorkflow::STATUS_REJECTED)
                ->count(),
            'all' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->where('canvass_status', '!=', CanvassWorkflow::STATUS_DRAFT)
                ->count(),
        ];

        $canAct = AdminPortal::canActOnRis();
        $savedSignatures = $canAct ? UserSignatureLibrary::forUser((int) Auth::id()) : collect();

        $pendingAcceptCount = (int) $counts['pending'];
        $readyToSignCount = Schema::hasTable('requisition_issue_slip_table')
            ? (int) DB::table('requisition_issue_slip_table')->where('ris_status', 'Accepted')->count()
            : 0;
        $awaitingIssuedCount = Schema::hasTable('requisition_issue_slip_table')
            ? (int) DB::table('requisition_issue_slip_table')
                ->where(function ($approved) {
                    $approved->where(function ($p) {
                        $p->where('ris_status', 'Approved by the President')
                            ->whereNotNull('ris_approved_by_signature')
                            ->whereRaw('TRIM(ris_approved_by_signature) != ""');
                    })->orWhere(function ($legacy) {
                        $legacy->where('ris_status', 'Approved')
                            ->whereNotNull('ris_approved_by_signature')
                            ->where('ris_approved_by_signature', 'like', 'data:image%');
                    });
                })
                ->where(function ($unsigned) {
                    $unsigned->whereNull('ris_issued_by_signature')
                        ->orWhereRaw('TRIM(ris_issued_by_signature) = ""');
                })
                ->count()
            : 0;

        return view('admin.canvass-review.index', compact(
            'canvasses',
            'workspaces',
            'filter',
            'search',
            'urgency',
            'counts',
            'canAct',
            'savedSignatures',
            'openCanvassId',
            'pendingAcceptCount',
            'readyToSignCount',
            'awaitingIssuedCount'
        ));
    }

    public function show(int $canvassId)
    {
        return redirect()->route(AdminPortal::prefix().'.canvass-review.index', ['open' => $canvassId]);
    }

    public function startReview(int $canvassId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403);

        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);

        if ((string) $canvass->canvass_status === CanvassWorkflow::STATUS_AWAITING) {
            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->where('canvass_status', CanvassWorkflow::STATUS_AWAITING)
                ->update([
                    'canvass_status' => CanvassWorkflow::STATUS_REVIEWING,
                    'canvass_review_started_by' => (int) Auth::id(),
                    'canvass_review_started_at' => now(),
                    'canvass_updated_at' => now(),
                ]);
        }

        return response()->json([
            'ok' => true,
            'status' => CanvassWorkflow::STATUS_REVIEWING,
        ]);
    }

    public function approveItem(Request $request, int $canvassId, int $itemId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403, 'Only the School Administrator can approve canvass items.');

        $loaded = CanvassWorkflow::loadFull($canvassId);
        abort_if(! $loaded, 404);

        $canvass = $loaded['canvass'];
        if (! CanvassWorkflow::canReview($canvass)) {
            return response()->json(['ok' => false, 'message' => 'This canvass is no longer open for review.'], 422);
        }

        $item = $loaded['items']->firstWhere('canvass_item_id', $itemId);
        abort_if(! $item, 404);

        $sig = trim((string) $request->input('admin_signature', ''));
        if ($sig === '' && ! empty($canvass->canvass_admin_signature)) {
            $sig = (string) $canvass->canvass_admin_signature;
        }
        if ($sig === '' || ! str_starts_with($sig, 'data:image/')) {
            return response()->json([
                'ok' => false,
                'message' => 'Admin authorization signature is required to approve an item.',
            ], 422);
        }

        $rawAllocs = $request->input('allocations', []);
        if (! is_array($rawAllocs) || $rawAllocs === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Select at least one supplier offer and enter an approved quantity.',
            ], 422);
        }

        $offersById = $item->comparison_offers->keyBy('canvass_offer_id');
        $requestedQty = max(1, (int) $item->canvass_item_quantity);

        $cleanAllocs = [];
        $allocatedRequestedUnits = 0.0;

        foreach ($rawAllocs as $alloc) {
            $offerId = (int) ($alloc['offer_id'] ?? 0);
            $offerUnits = (int) ($alloc['qty'] ?? 0);
            if ($offerId <= 0 || $offerUnits < 1 || ! $offersById->has($offerId)) {
                continue;
            }
            $offer = $offersById->get($offerId);
            $pack = max(0.01, (float) ($offer->offer_units_per_pack ?? 1));
            $unitPrice = max(0.0, (float) ($offer->offer_unit_price ?? 0));
            $covered = round($offerUnits * $pack, 2);
            $amount = round($offerUnits * $unitPrice, 2);

            $allocatedRequestedUnits += $covered;
            $cleanAllocs[] = [
                'canvass_item_id' => $itemId,
                'canvass_offer_id' => $offerId,
                'approved_offer_units' => $offerUnits,
                'approved_requested_units' => $covered,
                'approved_unit_cost' => $unitPrice,
                'approved_amount' => $amount,
            ];
        }

        if ($cleanAllocs === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Enter a valid whole offer-unit quantity (>= 1) for at least one checked offer.',
            ], 422);
        }

        if ($allocatedRequestedUnits > ($requestedQty + 0.0001)) {
            return response()->json([
                'ok' => false,
                'message' => "Allocated quantity ({$allocatedRequestedUnits}) cannot exceed the requested quantity ({$requestedQty}).",
            ], 422);
        }

        $result = DB::transaction(function () use ($canvassId, $itemId, $canvass, $cleanAllocs, $sig) {
            $now = now();
            $adminId = (int) Auth::id();

            if (Schema::hasTable('canvass_item_allocations_table')) {
                DB::table('canvass_item_allocations_table')
                    ->where('canvass_item_id', $itemId)
                    ->delete();

                $insertRows = array_map(fn ($r) => array_merge($r, [
                    'allocation_created_at' => $now,
                    'allocation_updated_at' => $now,
                ]), $cleanAllocs);

                DB::table('canvass_item_allocations_table')->insert($insertRows);
            }

            DB::table('canvass_items_table')
                ->where('canvass_item_id', $itemId)
                ->update([
                    'canvass_item_status' => CanvassWorkflow::ITEM_APPROVED,
                    'canvass_item_selected_offer_id' => $cleanAllocs[0]['canvass_offer_id'],
                    'canvass_item_review_note' => null,
                    'canvass_item_updated_at' => $now,
                ]);

            $canvassUpdate = [
                'canvass_status' => CanvassWorkflow::STATUS_REVIEWING,
                'canvass_reviewed_by' => $adminId,
                'canvass_updated_at' => $now,
            ];
            if (Schema::hasColumn('canvass_table', 'canvass_admin_signature')) {
                $canvassUpdate['canvass_admin_signature'] = $sig;
            }
            if (empty($canvass->canvass_review_started_at)) {
                $canvassUpdate['canvass_review_started_by'] = $adminId;
                $canvassUpdate['canvass_review_started_at'] = $now;
            }

            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update($canvassUpdate);

            return $this->finalizeCanvassIfComplete($canvassId, $canvass, $adminId, $now);
        });

        $msg = $result['all_approved']
            ? 'All items finalized! Official RIS '.$result['generated']['ris_number'].' has been moved to Reviewed RIS for Direct Approval or forwarding to the President.'
            : 'Item approved.';
        $redirectUrl = $result['all_approved']
            ? route(AdminPortal::prefix().'.digital-signatures.sign-ris', ['filter' => 'for_decision'])
            : null;
        if ($redirectUrl) {
            session()->flash('success', $msg);
        }

        return response()->json([
            'ok' => true,
            'finalized' => $result['finalized'],
            'all_approved' => $result['all_approved'],
            'canvass_status' => $result['canvass_status'],
            'ris_number' => $result['generated']['ris_number'] ?? null,
            'atp_count' => isset($result['generated']['atp_ids']) ? count($result['generated']['atp_ids']) : 0,
            'redirect_url' => $redirectUrl,
            'message' => $msg,
        ]);
    }

    public function returnItem(Request $request, int $canvassId, int $itemId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403);

        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            return response()->json(['ok' => false, 'message' => 'Please enter a reason for returning this item.'], 422);
        }

        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);

        DB::transaction(function () use ($canvassId, $itemId, $canvass, $note) {
            $now = now();
            DB::table('canvass_items_table')
                ->where('canvass_id', $canvassId)
                ->where('canvass_item_id', $itemId)
                ->update([
                    'canvass_item_status' => CanvassWorkflow::ITEM_RETURNED,
                    'canvass_item_selected_offer_id' => null,
                    'canvass_item_review_note' => $note,
                    'canvass_item_updated_at' => $now,
                ]);

            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update([
                    'canvass_status' => CanvassWorkflow::STATUS_RETURNED,
                    'canvass_reviewed_by' => (int) Auth::id(),
                    'canvass_reviewed_at' => $now,
                    'canvass_review_note' => $note,
                    'canvass_updated_at' => $now,
                ]);

            $this->notifyPurchaserReturned($canvass, $canvassId, $note);
        });

        return response()->json([
            'ok' => true,
            'canvass_status' => CanvassWorkflow::STATUS_RETURNED,
            'message' => 'Item returned to Purchaser for revision.',
        ]);
    }

    public function rejectItem(Request $request, int $canvassId, int $itemId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403, 'Only the School Administrator can reject canvass items.');

        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            return response()->json(['ok' => false, 'message' => 'Please enter a reason for rejecting this item.'], 422);
        }

        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);

        if (! CanvassWorkflow::canReview($canvass)) {
            return response()->json(['ok' => false, 'message' => 'This canvass is no longer open for review.'], 422);
        }

        $result = DB::transaction(function () use ($canvassId, $itemId, $canvass, $note) {
            $now = now();
            $adminId = (int) Auth::id();

            if (Schema::hasTable('canvass_item_allocations_table')) {
                DB::table('canvass_item_allocations_table')->where('canvass_item_id', $itemId)->delete();
            }

            DB::table('canvass_items_table')
                ->where('canvass_id', $canvassId)
                ->where('canvass_item_id', $itemId)
                ->update([
                    'canvass_item_status' => CanvassWorkflow::ITEM_REJECTED,
                    'canvass_item_selected_offer_id' => null,
                    'canvass_item_review_note' => $note,
                    'canvass_item_updated_at' => $now,
                ]);

            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update([
                    'canvass_status' => CanvassWorkflow::STATUS_REVIEWING,
                    'canvass_reviewed_by' => $adminId,
                    'canvass_review_note' => $note,
                    'canvass_updated_at' => $now,
                ]);

            return $this->finalizeCanvassIfComplete($canvassId, $canvass, $adminId, $now, $note);
        });

        $msg = 'Item rejected.';
        if ($result['all_rejected']) {
            $msg = 'All items rejected. Canvass marked as Rejected (no RIS or ATP generated).';
        } elseif ($result['all_approved']) {
            $msg = 'Item rejected and remaining approved items finalized! Official RIS '.$result['generated']['ris_number'].' and '.count($result['generated']['atp_ids']).' per-supplier ATP draft(s) generated.';
        }

        return response()->json([
            'ok' => true,
            'finalized' => $result['finalized'],
            'all_approved' => $result['all_approved'],
            'all_rejected' => $result['all_rejected'],
            'canvass_status' => $result['canvass_status'],
            'ris_number' => $result['generated']['ris_number'] ?? null,
            'atp_count' => isset($result['generated']['atp_ids']) ? count($result['generated']['atp_ids']) : 0,
            'message' => $msg,
        ]);
    }

    public function bulkApprove(Request $request, int $canvassId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403, 'Only the School Administrator can approve canvass items.');

        $loaded = CanvassWorkflow::loadFull($canvassId);
        abort_if(! $loaded, 404);

        $canvass = $loaded['canvass'];
        if (! CanvassWorkflow::canReview($canvass)) {
            return response()->json(['ok' => false, 'message' => 'This canvass is no longer open for review.'], 422);
        }

        $sig = trim((string) $request->input('admin_signature', ''));
        if ($sig === '' && ! empty($canvass->canvass_admin_signature)) {
            $sig = (string) $canvass->canvass_admin_signature;
        }
        if ($sig === '' || ! str_starts_with($sig, 'data:image/')) {
            return response()->json([
                'ok' => false,
                'message' => 'Admin authorization signature is required to approve items.',
            ], 422);
        }

        $rawItems = $request->input('items', []);
        if (! is_array($rawItems) || $rawItems === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Select at least one pending item to approve.',
            ], 422);
        }

        $itemsById = $loaded['items']->keyBy('canvass_item_id');
        $validatedBatch = [];

        foreach ($rawItems as $entry) {
            $itemId = (int) ($entry['item_id'] ?? 0);
            if ($itemId <= 0 || ! $itemsById->has($itemId)) {
                continue;
            }
            $item = $itemsById->get($itemId);
            $itemName = (string) ($item->canvass_item_name ?: $item->catalog_item_name ?: ('Item #'.$itemId));
            $offersById = $item->comparison_offers->keyBy('canvass_offer_id');
            $requestedQty = max(1, (int) $item->canvass_item_quantity);

            $rawAllocs = is_array($entry['allocations'] ?? null) ? $entry['allocations'] : [];
            $cleanAllocs = [];
            $allocatedUnits = 0.0;

            foreach ($rawAllocs as $alloc) {
                $offerId = (int) ($alloc['offer_id'] ?? 0);
                $offerUnits = (int) ($alloc['qty'] ?? 0);
                if ($offerId <= 0 || $offerUnits < 1 || ! $offersById->has($offerId)) {
                    continue;
                }
                $offer = $offersById->get($offerId);
                $pack = max(0.01, (float) ($offer->offer_units_per_pack ?? 1));
                $unitPrice = max(0.0, (float) ($offer->offer_unit_price ?? 0));
                $covered = round($offerUnits * $pack, 2);
                $amount = round($offerUnits * $unitPrice, 2);

                $allocatedUnits += $covered;
                $cleanAllocs[] = [
                    'canvass_item_id' => $itemId,
                    'canvass_offer_id' => $offerId,
                    'approved_offer_units' => $offerUnits,
                    'approved_requested_units' => $covered,
                    'approved_unit_cost' => $unitPrice,
                    'approved_amount' => $amount,
                ];
            }

            if ($cleanAllocs === []) {
                return response()->json([
                    'ok' => false,
                    'message' => "{$itemName}: Please check at least one supplier offer and enter an approved quantity.",
                ], 422);
            }
            if ($allocatedUnits > ($requestedQty + 0.0001)) {
                return response()->json([
                    'ok' => false,
                    'message' => "{$itemName}: Allocated quantity ({$allocatedUnits}) cannot exceed the requested quantity ({$requestedQty}).",
                ], 422);
            }

            $validatedBatch[] = [
                'item_id' => $itemId,
                'allocations' => $cleanAllocs,
            ];
        }

        if ($validatedBatch === []) {
            return response()->json(['ok' => false, 'message' => 'No valid items were provided for approval.'], 422);
        }

        $rejectUnselected = (bool) $request->input('reject_unselected', false);
        $unselectedNote = trim((string) $request->input('unselected_note', ''));
        $approvedIds = array_column($validatedBatch, 'item_id');

        $result = DB::transaction(function () use ($canvassId, $canvass, $validatedBatch, $approvedIds, $sig, $rejectUnselected, $unselectedNote) {
            $now = now();
            $adminId = (int) Auth::id();
            $hasAllocTable = Schema::hasTable('canvass_item_allocations_table');

            foreach ($validatedBatch as $batch) {
                $itemId = $batch['item_id'];
                $cleanAllocs = $batch['allocations'];

                if ($hasAllocTable) {
                    DB::table('canvass_item_allocations_table')->where('canvass_item_id', $itemId)->delete();
                    $insertRows = array_map(fn ($r) => array_merge($r, [
                        'allocation_created_at' => $now,
                        'allocation_updated_at' => $now,
                    ]), $cleanAllocs);
                    DB::table('canvass_item_allocations_table')->insert($insertRows);
                }

                DB::table('canvass_items_table')
                    ->where('canvass_item_id', $itemId)
                    ->update([
                        'canvass_item_status' => CanvassWorkflow::ITEM_APPROVED,
                        'canvass_item_selected_offer_id' => $cleanAllocs[0]['canvass_offer_id'],
                        'canvass_item_review_note' => null,
                        'canvass_item_updated_at' => $now,
                    ]);
            }

            $rejectedIds = [];
            if ($rejectUnselected) {
                $rejectedIds = DB::table('canvass_items_table')
                    ->where('canvass_id', $canvassId)
                    ->whereNotIn('canvass_item_id', $approvedIds)
                    ->whereNotIn('canvass_item_status', [CanvassWorkflow::ITEM_APPROVED, CanvassWorkflow::ITEM_REJECTED])
                    ->pluck('canvass_item_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($rejectedIds !== []) {
                    if ($hasAllocTable) {
                        DB::table('canvass_item_allocations_table')->whereIn('canvass_item_id', $rejectedIds)->delete();
                    }
                    DB::table('canvass_items_table')
                        ->where('canvass_id', $canvassId)
                        ->whereIn('canvass_item_id', $rejectedIds)
                        ->update([
                            'canvass_item_status' => CanvassWorkflow::ITEM_REJECTED,
                            'canvass_item_selected_offer_id' => null,
                            'canvass_item_review_note' => $unselectedNote !== '' ? $unselectedNote : 'Not selected during School Administrator approval.',
                            'canvass_item_updated_at' => $now,
                        ]);
                }
            }

            $canvassUpdate = [
                'canvass_status' => CanvassWorkflow::STATUS_REVIEWING,
                'canvass_reviewed_by' => $adminId,
                'canvass_updated_at' => $now,
            ];
            if (Schema::hasColumn('canvass_table', 'canvass_admin_signature')) {
                $canvassUpdate['canvass_admin_signature'] = $sig;
            }
            if (empty($canvass->canvass_review_started_at)) {
                $canvassUpdate['canvass_review_started_by'] = $adminId;
                $canvassUpdate['canvass_review_started_at'] = $now;
            }
            DB::table('canvass_table')->where('canvass_id', $canvassId)->update($canvassUpdate);

            $fin = $this->finalizeCanvassIfComplete($canvassId, $canvass, $adminId, $now);
            $fin['rejected_item_ids'] = $rejectedIds;

            return $fin;
        });

        $rejectedCount = count($result['rejected_item_ids'] ?? []);
        $msg = $result['all_approved']
            ? count($approvedIds).' item(s) approved'
                .($rejectedCount > 0 ? " ({$rejectedCount} unselected marked Rejected)" : '')
                .' — Official RIS '.$result['generated']['ris_number'].' moved to Reviewed RIS for Direct Approval or forwarding to the President.'
            : count($approvedIds).' selected item(s) approved.';
        $redirectUrl = $result['all_approved']
            ? route(AdminPortal::prefix().'.digital-signatures.sign-ris', ['filter' => 'for_decision'])
            : null;
        if ($redirectUrl) {
            session()->flash('success', $msg);
        }

        return response()->json([
            'ok' => true,
            'approved_item_ids' => $approvedIds,
            'rejected_item_ids' => $result['rejected_item_ids'] ?? [],
            'finalized' => $result['finalized'],
            'all_approved' => $result['all_approved'],
            'canvass_status' => $result['canvass_status'],
            'ris_number' => $result['generated']['ris_number'] ?? null,
            'atp_count' => isset($result['generated']['atp_ids']) ? count($result['generated']['atp_ids']) : 0,
            'redirect_url' => $redirectUrl,
            'message' => $msg,
        ]);
    }

    public function bulkReturn(Request $request, int $canvassId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403);

        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            return response()->json(['ok' => false, 'message' => 'Please enter a reason for returning for revision.'], 422);
        }

        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);

        $itemIds = array_values(array_filter(array_map('intval', (array) $request->input('item_ids', []))));
        $returnAll = (bool) $request->input('all', false) || $itemIds === [];

        $returnedIds = DB::transaction(function () use ($canvassId, $canvass, $itemIds, $returnAll, $note) {
            $now = now();
            $q = DB::table('canvass_items_table')->where('canvass_id', $canvassId);
            if (! $returnAll) {
                $q->whereIn('canvass_item_id', $itemIds);
            }
            $targetIds = $q->pluck('canvass_item_id')->map(fn ($id) => (int) $id)->all();

            if ($targetIds !== []) {
                DB::table('canvass_items_table')
                    ->where('canvass_id', $canvassId)
                    ->whereIn('canvass_item_id', $targetIds)
                    ->update([
                        'canvass_item_status' => CanvassWorkflow::ITEM_RETURNED,
                        'canvass_item_selected_offer_id' => null,
                        'canvass_item_review_note' => $note,
                        'canvass_item_updated_at' => $now,
                    ]);
            }

            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update([
                    'canvass_status' => CanvassWorkflow::STATUS_RETURNED,
                    'canvass_reviewed_by' => (int) Auth::id(),
                    'canvass_reviewed_at' => $now,
                    'canvass_review_note' => $note,
                    'canvass_updated_at' => $now,
                ]);

            $this->notifyPurchaserReturned($canvass, $canvassId, $note);

            return $targetIds;
        });

        return response()->json([
            'ok' => true,
            'returned_item_ids' => $returnedIds,
            'canvass_status' => CanvassWorkflow::STATUS_RETURNED,
            'message' => $returnAll
                ? 'Entire canvass returned to Purchaser for revision.'
                : count($returnedIds).' selected item(s) returned to Purchaser for revision.',
        ]);
    }

    public function bulkReject(Request $request, int $canvassId)
    {
        abort_unless(AdminPortal::canActOnRis(), 403);

        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            return response()->json(['ok' => false, 'message' => 'Please enter a reason for rejecting.'], 422);
        }

        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);

        if (! CanvassWorkflow::canReview($canvass)) {
            return response()->json(['ok' => false, 'message' => 'This canvass is no longer open for review.'], 422);
        }

        $itemIds = array_values(array_filter(array_map('intval', (array) $request->input('item_ids', []))));
        $rejectAll = (bool) $request->input('all', false) || $itemIds === [];

        $out = DB::transaction(function () use ($canvassId, $canvass, $itemIds, $rejectAll, $note) {
            $now = now();
            $adminId = (int) Auth::id();

            $q = DB::table('canvass_items_table')->where('canvass_id', $canvassId);
            if (! $rejectAll) {
                $q->whereIn('canvass_item_id', $itemIds);
            }
            $targetIds = $q->pluck('canvass_item_id')->map(fn ($id) => (int) $id)->all();

            if ($targetIds !== []) {
                if (Schema::hasTable('canvass_item_allocations_table')) {
                    DB::table('canvass_item_allocations_table')->whereIn('canvass_item_id', $targetIds)->delete();
                }

                DB::table('canvass_items_table')
                    ->where('canvass_id', $canvassId)
                    ->whereIn('canvass_item_id', $targetIds)
                    ->update([
                        'canvass_item_status' => CanvassWorkflow::ITEM_REJECTED,
                        'canvass_item_selected_offer_id' => null,
                        'canvass_item_review_note' => $note,
                        'canvass_item_updated_at' => $now,
                    ]);
            }

            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update([
                    'canvass_status' => CanvassWorkflow::STATUS_REVIEWING,
                    'canvass_reviewed_by' => $adminId,
                    'canvass_review_note' => $note,
                    'canvass_updated_at' => $now,
                ]);

            $fin = $this->finalizeCanvassIfComplete($canvassId, $canvass, $adminId, $now, $note);
            $fin['rejected_item_ids'] = $targetIds;

            return $fin;
        });

        $msg = count($out['rejected_item_ids']).' item(s) rejected.';
        if ($out['all_rejected']) {
            $msg = 'Canvass rejected. No RIS or ATP was generated.';
        } elseif ($out['all_approved']) {
            $msg = 'Selected item(s) rejected and approved items finalized! Official RIS '.$out['generated']['ris_number'].' and '.count($out['generated']['atp_ids']).' per-supplier ATP draft(s) generated.';
        }

        return response()->json([
            'ok' => true,
            'rejected_item_ids' => $out['rejected_item_ids'],
            'finalized' => $out['finalized'],
            'all_approved' => $out['all_approved'],
            'all_rejected' => $out['all_rejected'],
            'canvass_status' => $out['canvass_status'],
            'ris_number' => $out['generated']['ris_number'] ?? null,
            'atp_count' => isset($out['generated']['atp_ids']) ? count($out['generated']['atp_ids']) : 0,
            'message' => $msg,
        ]);
    }

    /**
     * Finalize the canvass once every item is either Approved or Rejected.
     *
     * @return array{finalized: bool, all_approved: bool, all_rejected: bool, canvass_status: string, generated: ?array}
     */
    private function finalizeCanvassIfComplete(int $canvassId, object $canvass, int $adminId, $now, ?string $note = null): array
    {
        $pendingOrReturned = (int) DB::table('canvass_items_table')
            ->where('canvass_id', $canvassId)
            ->whereNotIn('canvass_item_status', [CanvassWorkflow::ITEM_APPROVED, CanvassWorkflow::ITEM_REJECTED])
            ->count();

        if ($pendingOrReturned > 0) {
            return [
                'finalized' => false,
                'all_approved' => false,
                'all_rejected' => false,
                'canvass_status' => CanvassWorkflow::STATUS_REVIEWING,
                'generated' => null,
            ];
        }

        $approvedCount = (int) DB::table('canvass_items_table')
            ->where('canvass_id', $canvassId)
            ->where('canvass_item_status', CanvassWorkflow::ITEM_APPROVED)
            ->count();

        $number = CanvassWorkflow::displayNumber($canvass);

        if ($approvedCount > 0) {
            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update([
                    'canvass_status' => CanvassWorkflow::STATUS_APPROVED,
                    'canvass_reviewed_at' => $now,
                    'canvass_updated_at' => $now,
                ]);

            $generated = CanvassWorkflow::createRisAndAtpsFromApprovedCanvass($canvassId, $adminId);
            $atpCount = count($generated['atp_ids'] ?? []);
            $msg = "{$number} was approved. RIS {$generated['ris_number']} and {$atpCount} per-supplier ATP draft(s) were automatically created.";

            if (! empty($canvass->canvass_owner_user_id)) {
                WorkflowNotifier::toUser(
                    (int) $canvass->canvass_owner_user_id,
                    WorkflowNotifier::ROLE_PURCHASER,
                    'Canvass approved — RIS & ATP drafts created',
                    $msg,
                    'canvass_approved',
                    'Canvass',
                    $canvassId,
                    '/purchaser/ris?view_ris='.(int) $generated['ris_id']
                );
            } else {
                WorkflowNotifier::toRole(
                    WorkflowNotifier::ROLE_PURCHASER,
                    'Canvass approved — RIS & ATP drafts created',
                    $msg,
                    'canvass_approved',
                    'Canvass',
                    $canvassId,
                    '/purchaser/ris?view_ris='.(int) $generated['ris_id']
                );
            }

            return [
                'finalized' => true,
                'all_approved' => true,
                'all_rejected' => false,
                'canvass_status' => CanvassWorkflow::STATUS_APPROVED,
                'generated' => $generated,
            ];
        }

        // All items rejected
        DB::table('canvass_table')
            ->where('canvass_id', $canvassId)
            ->update([
                'canvass_status' => CanvassWorkflow::STATUS_REJECTED,
                'canvass_reviewed_by' => $adminId,
                'canvass_reviewed_at' => $now,
                'canvass_review_note' => $note ?: ($canvass->canvass_review_note ?? null),
                'canvass_updated_at' => $now,
            ]);

        $reasonSuffix = $note ? ": {$note}" : '.';
        if (! empty($canvass->canvass_owner_user_id)) {
            WorkflowNotifier::toUser(
                (int) $canvass->canvass_owner_user_id,
                WorkflowNotifier::ROLE_PURCHASER,
                'Canvass rejected',
                "{$number} was rejected by the School Administrator{$reasonSuffix}",
                'canvass_rejected',
                'Canvass',
                $canvassId,
                '/purchaser/canvass/'.$canvassId
            );
        }

        return [
            'finalized' => true,
            'all_approved' => false,
            'all_rejected' => true,
            'canvass_status' => CanvassWorkflow::STATUS_REJECTED,
            'generated' => null,
        ];
    }

    private function notifyPurchaserReturned(object $canvass, int $canvassId, string $note): void
    {
        $number = CanvassWorkflow::displayNumber($canvass);
        if (! empty($canvass->canvass_owner_user_id)) {
            WorkflowNotifier::toUser(
                (int) $canvass->canvass_owner_user_id,
                WorkflowNotifier::ROLE_PURCHASER,
                'Canvass returned for revision',
                "{$number} was returned by the School Administrator: {$note}",
                'canvass_returned',
                'Canvass',
                $canvassId,
                '/purchaser/canvass/'.$canvassId.'/edit'
            );
        }
    }

    public function print(int $canvassId)
    {
        $loaded = CanvassWorkflow::loadFull($canvassId);
        abort_if(! $loaded, 404);

        return view('admin.canvass-review.print', [
            'canvass' => $loaded['canvass'],
            'items' => $loaded['items'],
            'attachments' => $loaded['attachments'],
        ]);
    }
}
