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
        if (! in_array($filter, ['pending', 'approved', 'returned', 'all'], true)) {
            $filter = 'all';
        }
        $search = trim((string) $request->query('search', ''));
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
            $query->whereIn('c.canvass_status', [CanvassWorkflow::STATUS_APPROVED, CanvassWorkflow::STATUS_CONVERTED]);
        } elseif ($filter === 'returned') {
            $query->where('c.canvass_status', CanvassWorkflow::STATUS_RETURNED);
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
                ->whereIn('canvass_status', [CanvassWorkflow::STATUS_APPROVED, CanvassWorkflow::STATUS_CONVERTED])
                ->count(),
            'returned' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->where('canvass_status', CanvassWorkflow::STATUS_RETURNED)
                ->count(),
            'all' => (int) DB::table('canvass_table')
                ->where('canvass_is_archived', 0)
                ->where('canvass_status', '!=', CanvassWorkflow::STATUS_DRAFT)
                ->count(),
        ];

        $canAct = AdminPortal::canActOnRis();
        $savedSignatures = $canAct ? UserSignatureLibrary::forUser((int) Auth::id()) : collect();

        return view('admin.canvass-review.index', compact(
            'canvasses',
            'workspaces',
            'filter',
            'search',
            'counts',
            'canAct',
            'savedSignatures',
            'openCanvassId'
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

        if (($allocatedRequestedUnits + 0.0001) < $requestedQty) {
            return response()->json([
                'ok' => false,
                'message' => "Allocated quantity ({$allocatedRequestedUnits}) must cover the requested quantity ({$requestedQty}).",
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

            // Check whether every item in this canvass is now Approved
            $remaining = (int) DB::table('canvass_items_table')
                ->where('canvass_id', $canvassId)
                ->where('canvass_item_status', '!=', CanvassWorkflow::ITEM_APPROVED)
                ->count();

            $generated = null;
            if ($remaining === 0) {
                DB::table('canvass_table')
                    ->where('canvass_id', $canvassId)
                    ->update([
                        'canvass_status' => CanvassWorkflow::STATUS_APPROVED,
                        'canvass_reviewed_at' => $now,
                        'canvass_updated_at' => $now,
                    ]);

                $generated = CanvassWorkflow::createRisAndAtpsFromApprovedCanvass($canvassId, $adminId);

                $number = CanvassWorkflow::displayNumber($canvass);
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
            }

            return [
                'all_approved' => $remaining === 0,
                'generated' => $generated,
            ];
        });

        return response()->json([
            'ok' => true,
            'all_approved' => $result['all_approved'],
            'ris_number' => $result['generated']['ris_number'] ?? null,
            'atp_count' => isset($result['generated']['atp_ids']) ? count($result['generated']['atp_ids']) : 0,
            'message' => $result['all_approved']
                ? 'All items approved! Official RIS '.$result['generated']['ris_number'].' and '.count($result['generated']['atp_ids']).' per-supplier ATP draft(s) have been generated.'
                : 'Item approved.',
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
        });

        return response()->json(['ok' => true, 'message' => 'Item returned to Purchaser for revision.']);
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
