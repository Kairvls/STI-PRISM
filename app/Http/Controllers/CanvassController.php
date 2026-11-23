<?php

namespace App\Http\Controllers;

use App\Support\CanvassWorkflow;
use App\Support\ProcurementPortal;
use App\Support\ProcurementRequestNumber;
use App\Support\ReplacementRequestBasket;
use App\Support\RisWorkflow;
use App\Support\RoleAccess;
use App\Support\SupplierDeliveryInsights;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CanvassController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(CanvassWorkflow::tablesExist(), 503, 'Canvass tables are not migrated yet. Please run php artisan migrate.');

        $archiveView = $request->query('view') === 'archive';
        $statusFilter = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $baseQuery = DB::table('canvass_table as c')
            ->leftJoin('users_table as owner', 'owner.user_id', '=', 'c.canvass_owner_user_id')
            ->leftJoin('requisition_issue_slip_table as ris', 'ris.ris_id', '=', 'c.canvass_ris_id')
            ->where('c.canvass_is_archived', $archiveView ? 1 : 0);

        if (! RoleAccess::isAdmin()) {
            $userId = (int) Auth::id();
            $baseQuery->where(function ($q) use ($userId) {
                $q->where('c.canvass_owner_user_id', $userId)
                    ->orWhereNull('c.canvass_owner_user_id');
            });
        }

        if ($statusFilter !== '' && in_array($statusFilter, CanvassWorkflow::statuses(), true)) {
            $baseQuery->where('c.canvass_status', $statusFilter);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $baseQuery->where(function ($q) use ($like) {
                $q->where('c.canvass_number', 'like', $like)
                    ->orWhere('c.canvass_request_no', 'like', $like)
                    ->orWhere('c.canvass_purpose', 'like', $like)
                    ->orWhere('ris.ris_form_number', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))
                            ->from('canvass_items_table as ci')
                            ->whereColumn('ci.canvass_id', 'c.canvass_id')
                            ->where('ci.canvass_item_name', 'like', $like);
                    });
            });
        }

        $canvasses = $baseQuery
            ->select([
                'c.*',
                'owner.user_full_name as owner_name',
                'ris.ris_form_number as linked_ris_number',
                'ris.ris_status as linked_ris_status',
            ])
            ->orderByDesc('c.canvass_submitted_at')
            ->orderByDesc('c.canvass_updated_at')
            ->orderByDesc('c.canvass_id')
            ->paginate(15)
            ->withQueryString();

        $canvassIds = $canvasses->getCollection()->pluck('canvass_id')->all();
        $itemCounts = [];
        $supplierCounts = [];
        $lowestSums = [];
        $firstItemNames = [];

        if ($canvassIds !== []) {
            $items = DB::table('canvass_items_table')
                ->whereIn('canvass_id', $canvassIds)
                ->orderBy('canvass_item_sort_order')
                ->orderBy('canvass_item_id')
                ->get(['canvass_item_id', 'canvass_id', 'canvass_item_name', 'canvass_item_quantity']);

            $itemIds = $items->pluck('canvass_item_id')->all();
            $suppliers = $itemIds === []
                ? collect()
                : DB::table('canvass_suppliers_table')
                    ->whereIn('canvass_item_id', $itemIds)
                    ->get(['canvass_supplier_id', 'canvass_item_id', 'supplier_id']);

            $supRowIds = $suppliers->pluck('canvass_supplier_id')->all();
            $offers = $supRowIds === []
                ? collect()
                : DB::table('canvass_offers_table')
                    ->whereIn('canvass_supplier_id', $supRowIds)
                    ->get(['canvass_supplier_id', 'offer_units_per_pack', 'offer_unit_price']);

            $offersBySup = $offers->groupBy('canvass_supplier_id');
            $supsByItem = $suppliers->groupBy('canvass_item_id');

            foreach ($items->groupBy('canvass_id') as $cid => $cItems) {
                $itemCounts[(int) $cid] = $cItems->count();
                $firstItemNames[(int) $cid] = (string) ($cItems->first()->canvass_item_name ?? '');
                $distinctSuppliers = [];
                $sum = 0.0;

                foreach ($cItems as $ci) {
                    $qty = max(1, (int) $ci->canvass_item_quantity);
                    $minOffer = null;
                    foreach ($supsByItem->get($ci->canvass_item_id, collect()) as $s) {
                        $distinctSuppliers[(int) $s->supplier_id] = true;
                        foreach ($offersBySup->get($s->canvass_supplier_id, collect()) as $o) {
                            $tot = CanvassWorkflow::computeOfferTotal(
                                $qty,
                                (float) ($o->offer_units_per_pack ?? 1),
                                (float) ($o->offer_unit_price ?? 0)
                            );
                            if ($minOffer === null || $tot < $minOffer) {
                                $minOffer = $tot;
                            }
                        }
                    }
                    if ($minOffer !== null) {
                        $sum += $minOffer;
                    }
                }

                $supplierCounts[(int) $cid] = count($distinctSuppliers);
                $lowestSums[(int) $cid] = round($sum, 2);
            }
        }

        foreach ($canvasses as $row) {
            $cid = (int) $row->canvass_id;
            $row->item_count = $itemCounts[$cid] ?? 0;
            $row->supplier_count = $supplierCounts[$cid] ?? 0;
            $row->lowest_total_sum = $lowestSums[$cid] ?? 0.0;
            $row->first_item_name = $firstItemNames[$cid] ?? '';
        }

        $summaryBase = DB::table('canvass_table')->where('canvass_is_archived', 0);
        if (! RoleAccess::isAdmin()) {
            $userId = (int) Auth::id();
            $summaryBase->where(function ($q) use ($userId) {
                $q->where('canvass_owner_user_id', $userId)->orWhereNull('canvass_owner_user_id');
            });
        }
        $countsByStatus = $summaryBase
            ->select('canvass_status', DB::raw('COUNT(*) as total'))
            ->groupBy('canvass_status')
            ->pluck('total', 'canvass_status')
            ->all();

        $activeCount = array_sum($countsByStatus);
        $archiveCount = (int) DB::table('canvass_table')
            ->where('canvass_is_archived', 1)
            ->when(! RoleAccess::isAdmin(), function ($q) {
                $uid = (int) Auth::id();
                $q->where(fn ($sub) => $sub->where('canvass_owner_user_id', $uid)->orWhereNull('canvass_owner_user_id'));
            })
            ->count();

        return view('purchaser.canvass.index', compact(
            'canvasses',
            'archiveView',
            'statusFilter',
            'search',
            'countsByStatus',
            'activeCount',
            'archiveCount'
        ));
    }

    public function create(Request $request)
    {
        abort_unless(CanvassWorkflow::tablesExist(), 503, 'Canvass tables are not migrated yet. Please run php artisan migrate.');

        $catalogs = $this->loadFormCatalogs();
        $replacementRequestId = (int) $request->query('replacement_request', 0);

        $initialItems = [];
        $initialPurpose = '';
        $initialUrgency = RisWorkflow::URGENCY_NON_URGENT;
        $selectedPrId = null;

        if ($replacementRequestId > 0) {
            $pr = $catalogs['replacementRequests']->firstWhere('procurement_request_id', $replacementRequestId);
            if ($pr) {
                $selectedPrId = (int) $pr->procurement_request_id;
                $initialPurpose = RisWorkflow::replacementPurpose($pr);
                $initialUrgency = RisWorkflow::normalizeUrgency($pr->report_urgency_level ?? null);
                $lines = ReplacementRequestBasket::risPrefillLines($pr);
                foreach ($lines as $line) {
                    $name = trim((string) ($line['name'] ?? ''));
                    $matchedItem = $catalogs['items']->first(
                        fn ($it) => mb_strtolower(trim((string) $it->item_name)) === mb_strtolower($name)
                    );
                    $initialItems[] = [
                        'item_id' => $matchedItem ? (string) $matchedItem->item_id : '',
                        'item_name' => $name,
                        'uom_id' => '',
                        'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
                        'collapsed' => false,
                        'suppliers' => [
                            ['supplier_id' => '', 'offers' => [['brand_id' => '', 'offer_uom_id' => '', 'units_per_pack' => '1', 'unit_price' => '']]],
                            ['supplier_id' => '', 'offers' => [['brand_id' => '', 'offer_uom_id' => '', 'units_per_pack' => '1', 'unit_price' => '']]],
                        ],
                    ];
                }
            }
        }

        if ($initialItems === []) {
            $initialItems[] = $this->blankItemSeed();
        }

        $savedSignatures = UserSignatureLibrary::forUser((int) Auth::id());
        $defaultSig = (string) (optional($savedSignatures->first())->preview_url ?? '');
        $oldItems = $this->hydrateOldItems(old('items'));

        return view('purchaser.canvass.form', array_merge($catalogs, [
            'mode' => 'create',
            'canvass' => null,
            'attachments' => collect(),
            'savedSignatures' => $savedSignatures,
            'initialState' => [
                'procurement_request_id' => (string) old('canvass_procurement_request_id', $selectedPrId ? (string) $selectedPrId : ''),
                'purpose' => (string) old('canvass_purpose', $initialPurpose),
                'urgency' => RisWorkflow::normalizeUrgency(old('canvass_urgency', $initialUrgency)),
                'purchaser_signature' => (string) old('canvass_purchaser_signature', $defaultSig),
                'items' => $oldItems ?? $initialItems,
            ],
        ]));
    }

    public function store(Request $request)
    {
        abort_unless(CanvassWorkflow::tablesExist(), 503, 'Canvass tables are not migrated yet.');

        $action = $request->input('save_action') === 'draft' ? 'draft' : 'submit';
        $payload = $this->validateAndNormalize($request, $action === 'submit', 0, 0);

        $canvassId = DB::transaction(function () use ($payload, $action, $request) {
            $now = now();
            $isSubmit = $action === 'submit';

            $requestNo = null;
            if (! empty($payload['procurement_request_id']) && Schema::hasColumn('procurement_requests_table', 'procurement_request_number')) {
                $requestNo = DB::table('procurement_requests_table')
                    ->where('procurement_request_id', $payload['procurement_request_id'])
                    ->value('procurement_request_number');
            }
            if (! $requestNo) {
                $requestNo = ProcurementRequestNumber::allocate();
            }

            $canvassNumber = $isSubmit ? CanvassWorkflow::allocateNumber() : null;

            $id = DB::table('canvass_table')->insertGetId([
                'canvass_number' => $canvassNumber,
                'canvass_request_no' => $requestNo,
                'canvass_procurement_request_id' => $payload['procurement_request_id'],
                'canvass_purpose' => $payload['purpose'],
                'canvass_urgency' => $payload['urgency'],
                'canvass_status' => $isSubmit ? CanvassWorkflow::STATUS_AWAITING : CanvassWorkflow::STATUS_DRAFT,
                'canvass_owner_user_id' => (int) Auth::id(),
                'canvass_purchaser_signature' => $payload['signature'],
                'canvass_submitted_by' => $isSubmit ? (int) Auth::id() : null,
                'canvass_submitted_at' => $isSubmit ? $now : null,
                'canvass_is_archived' => false,
                'canvass_created_at' => $now,
                'canvass_updated_at' => $now,
            ]);

            $this->persistItems((int) $id, $payload['items'], $now);
            $this->storeAttachments((int) $id, $request->file('canvass_attachments', []));

            if ($isSubmit) {
                WorkflowNotifier::toRole(
                    WorkflowNotifier::ROLE_SCHOOL_ADMIN,
                    'New Purchase Canvass awaiting review',
                    ($canvassNumber ?: 'Canvass #'.$id).' has '.count($payload['items']).' item(s) awaiting your supplier & brand approval.',
                    'canvass_submitted',
                    'Canvass',
                    (int) $id,
                    '/school-admin/canvass-review/'.$id
                );
            }

            return (int) $id;
        });

        $msg = $action === 'submit'
            ? 'Canvass submitted to School Administrator (Awaiting for review).'
            : 'Canvass saved as draft.';

        return redirect()
            ->route(ProcurementPortal::routeName('canvass.show'), $canvassId)
            ->with('success', $msg);
    }

    public function show(int $canvassId)
    {
        $loaded = CanvassWorkflow::loadFull($canvassId);
        abort_if(! $loaded, 404);

        $this->authorizeOwner($loaded['canvass']);

        return view('purchaser.canvass.show', [
            'canvass' => $loaded['canvass'],
            'items' => $loaded['items'],
            'attachments' => $loaded['attachments'],
            'lowestTotalSum' => $loaded['lowest_total_sum'],
            'approvedTotalSum' => $loaded['approved_total_sum'],
        ]);
    }

    public function edit(int $canvassId)
    {
        $loaded = CanvassWorkflow::loadFull($canvassId);
        abort_if(! $loaded, 404);

        $canvass = $loaded['canvass'];
        $this->authorizeOwner($canvass);

        if (! CanvassWorkflow::canEdit($canvass)) {
            return redirect()
                ->route(ProcurementPortal::routeName('canvass.show'), $canvassId)
                ->with('error', 'This canvass is locked because review has already started or completed.');
        }

        $catalogs = $this->loadFormCatalogs();

        $initialItems = $loaded['items']->map(function ($item) {
            $suppliers = $item->suppliers->map(function ($sup) {
                $offers = $sup->offers->map(fn ($o) => [
                    'brand_id' => (string) $o->brand_id,
                    'offer_uom_id' => (string) $o->offer_uom_id,
                    'units_per_pack' => rtrim(rtrim(number_format((float) $o->offer_units_per_pack, 2, '.', ''), '0'), '.') ?: '1',
                    'unit_price' => number_format((float) $o->offer_unit_price, 2, '.', ''),
                ])->values()->all();

                return [
                    'supplier_id' => (string) $sup->supplier_id,
                    'offers' => $offers !== [] ? $offers : [['brand_id' => '', 'offer_uom_id' => '', 'units_per_pack' => '1', 'unit_price' => '']],
                ];
            })->values()->all();

            while (count($suppliers) < 2) {
                $suppliers[] = [
                    'supplier_id' => '',
                    'offers' => [['brand_id' => '', 'offer_uom_id' => '', 'units_per_pack' => '1', 'unit_price' => '']],
                ];
            }

            return [
                'item_id' => $item->item_id ? (string) $item->item_id : '',
                'item_name' => (string) ($item->canvass_item_name ?: $item->catalog_item_name),
                'uom_id' => $item->uom_id ? (string) $item->uom_id : '',
                'quantity' => max(1, (int) $item->canvass_item_quantity),
                'review_note' => (string) ($item->canvass_item_review_note ?? ''),
                'item_status' => (string) ($item->canvass_item_status ?? CanvassWorkflow::ITEM_PENDING),
                'collapsed' => false,
                'suppliers' => $suppliers,
            ];
        })->values()->all();

        if ($initialItems === []) {
            $initialItems[] = $this->blankItemSeed();
        }

        $savedSignatures = UserSignatureLibrary::forUser((int) Auth::id());
        $oldItems = $this->hydrateOldItems(old('items'));

        return view('purchaser.canvass.form', array_merge($catalogs, [
            'mode' => 'edit',
            'canvass' => $canvass,
            'attachments' => $loaded['attachments'],
            'savedSignatures' => $savedSignatures,
            'initialState' => [
                'procurement_request_id' => (string) old(
                    'canvass_procurement_request_id',
                    $canvass->canvass_procurement_request_id ? (string) $canvass->canvass_procurement_request_id : ''
                ),
                'purpose' => (string) old('canvass_purpose', (string) ($canvass->canvass_purpose ?? '')),
                'urgency' => RisWorkflow::normalizeUrgency(old('canvass_urgency', $canvass->canvass_urgency ?? null)),
                'purchaser_signature' => (string) old('canvass_purchaser_signature', $canvass->canvass_purchaser_signature ?? ''),
                'items' => $oldItems ?? $initialItems,
            ],
        ]));
    }

    public function update(Request $request, int $canvassId)
    {
        abort_unless(CanvassWorkflow::tablesExist(), 503);

        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);
        $this->authorizeOwner($canvass);

        if (! CanvassWorkflow::canEdit($canvass)) {
            return redirect()
                ->route(ProcurementPortal::routeName('canvass.show'), $canvassId)
                ->with('error', 'This canvass is locked and can no longer be edited.');
        }

        $existingAttCount = Schema::hasTable('canvass_attachments_table')
            ? (int) DB::table('canvass_attachments_table')->where('canvass_id', $canvassId)->count()
            : 0;
        $existingAttBytes = Schema::hasTable('canvass_attachments_table')
            ? (int) DB::table('canvass_attachments_table')->where('canvass_id', $canvassId)->sum('canvass_attachment_size')
            : 0;

        $action = $request->input('save_action') === 'draft' ? 'draft' : 'submit';
        $payload = $this->validateAndNormalize($request, $action === 'submit', $existingAttCount, $existingAttBytes);

        DB::transaction(function () use ($canvass, $canvassId, $payload, $action, $request) {
            $now = now();
            $isSubmit = $action === 'submit';
            $wasReturned = (string) $canvass->canvass_status === CanvassWorkflow::STATUS_RETURNED;

            $canvassNumber = $canvass->canvass_number;
            if ($isSubmit && empty($canvassNumber)) {
                $canvassNumber = CanvassWorkflow::allocateNumber();
            }

            $nextStatus = $isSubmit
                ? CanvassWorkflow::STATUS_AWAITING
                : ($wasReturned ? CanvassWorkflow::STATUS_RETURNED : CanvassWorkflow::STATUS_DRAFT);

            DB::table('canvass_table')
                ->where('canvass_id', $canvassId)
                ->update([
                    'canvass_number' => $canvassNumber,
                    'canvass_procurement_request_id' => $payload['procurement_request_id'],
                    'canvass_purpose' => $payload['purpose'],
                    'canvass_urgency' => $payload['urgency'],
                    'canvass_status' => $nextStatus,
                    'canvass_purchaser_signature' => $payload['signature'] ?: $canvass->canvass_purchaser_signature,
                    'canvass_submitted_by' => $isSubmit ? (int) Auth::id() : $canvass->canvass_submitted_by,
                    'canvass_submitted_at' => $isSubmit ? $now : $canvass->canvass_submitted_at,
                    'canvass_updated_at' => $now,
                ]);

            DB::table('canvass_items_table')->where('canvass_id', $canvassId)->delete();
            $this->persistItems($canvassId, $payload['items'], $now);
            $this->storeAttachments($canvassId, $request->file('canvass_attachments', []));

            if ($isSubmit) {
                $title = $wasReturned ? 'Canvass resubmitted for review' : 'Purchase Canvass updated';
                WorkflowNotifier::toRole(
                    WorkflowNotifier::ROLE_SCHOOL_ADMIN,
                    $title,
                    ($canvassNumber ?: 'Canvass #'.$canvassId).' is awaiting School Administrator review.',
                    'canvass_submitted',
                    'Canvass',
                    $canvassId,
                    '/school-admin/canvass-review/'.$canvassId
                );
            }
        });

        $msg = $action === 'submit'
            ? 'Canvass submitted to School Administrator (Awaiting for review).'
            : 'Canvass changes saved.';

        return redirect()
            ->route(ProcurementPortal::routeName('canvass.show'), $canvassId)
            ->with('success', $msg);
    }

    public function archive(int $canvassId)
    {
        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);
        $this->authorizeOwner($canvass);

        DB::table('canvass_table')
            ->where('canvass_id', $canvassId)
            ->update([
                'canvass_is_archived' => true,
                'canvass_archived_at' => now(),
                'canvass_updated_at' => now(),
            ]);

        return redirect()
            ->route(ProcurementPortal::routeName('canvass.index'))
            ->with('success', 'Canvass archived.');
    }

    public function restore(int $canvassId)
    {
        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);
        $this->authorizeOwner($canvass);

        DB::table('canvass_table')
            ->where('canvass_id', $canvassId)
            ->update([
                'canvass_is_archived' => false,
                'canvass_archived_at' => null,
                'canvass_updated_at' => now(),
            ]);

        return redirect()
            ->route(ProcurementPortal::routeName('canvass.index'))
            ->with('success', 'Canvass restored.');
    }

    public function destroy(int $canvassId)
    {
        $canvass = DB::table('canvass_table')->where('canvass_id', $canvassId)->first();
        abort_if(! $canvass, 404);
        $this->authorizeOwner($canvass);

        if ((string) $canvass->canvass_status !== CanvassWorkflow::STATUS_DRAFT) {
            return redirect()
                ->route(ProcurementPortal::routeName('canvass.index'))
                ->with('error', 'Only Draft canvasses can be permanently deleted.');
        }

        DB::table('canvass_table')->where('canvass_id', $canvassId)->delete();

        return redirect()
            ->route(ProcurementPortal::routeName('canvass.index'))
            ->with('success', 'Draft canvass deleted.');
    }

    public function previewAttachment(int $attachmentId)
    {
        $att = DB::table('canvass_attachments_table')
            ->where('canvass_attachment_id', $attachmentId)
            ->first();
        abort_if(! $att, 404);

        $path = (string) $att->canvass_attachment_stored_path;
        $name = (string) $att->canvass_attachment_original_name;

        $disk = Storage::disk('local')->exists($path)
            ? 'local'
            : (Storage::disk('public')->exists($path) ? 'public' : null);

        abort_if(! $disk, 404, 'File not found.');

        return app(RisController::class)->previewStoredFile($path, $name, null, $disk);
    }

    public function downloadAttachment(int $attachmentId)
    {
        $att = DB::table('canvass_attachments_table')
            ->where('canvass_attachment_id', $attachmentId)
            ->first();
        abort_if(! $att, 404);

        if (! Storage::disk('local')->exists($att->canvass_attachment_stored_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download(
            $att->canvass_attachment_stored_path,
            $att->canvass_attachment_original_name
        );
    }

    public function destroyAttachment(int $attachmentId)
    {
        $att = DB::table('canvass_attachments_table')
            ->where('canvass_attachment_id', $attachmentId)
            ->first();
        abort_if(! $att, 404);

        $canvass = DB::table('canvass_table')->where('canvass_id', $att->canvass_id)->first();
        abort_if(! $canvass, 404);
        $this->authorizeOwner($canvass);

        if (! CanvassWorkflow::canEdit($canvass)) {
            abort(403, 'Attachments can only be removed while the canvass is editable.');
        }

        Storage::disk('local')->delete($att->canvass_attachment_stored_path);
        DB::table('canvass_attachments_table')
            ->where('canvass_attachment_id', $attachmentId)
            ->delete();

        return back()->with('success', 'Attachment removed.');
    }

    private function authorizeOwner(object $canvass): void
    {
        if (RoleAccess::isAdmin()) {
            return;
        }
        $ownerId = (int) ($canvass->canvass_owner_user_id ?? 0);
        if ($ownerId > 0 && $ownerId !== (int) Auth::id()) {
            abort(403, 'You do not have access to this canvass.');
        }
    }

    private function blankItemSeed(): array
    {
        return [
            'item_id' => '',
            'item_name' => '',
            'uom_id' => '',
            'quantity' => '',
            'collapsed' => false,
            'suppliers' => [
                ['supplier_id' => '', 'offers' => [['brand_id' => '', 'offer_uom_id' => '', 'units_per_pack' => '1', 'unit_price' => '']]],
                ['supplier_id' => '', 'offers' => [['brand_id' => '', 'offer_uom_id' => '', 'units_per_pack' => '1', 'unit_price' => '']]],
            ],
        ];
    }

    private function loadFormCatalogs(): array
    {
        $items = Schema::hasTable('items_table')
            ? DB::table('items_table')
                ->where('item_status', 'Active')
                ->orderByRaw('LOWER(item_name) ASC')
                ->get(['item_id', 'item_name'])
            : collect();

        $uoms = Schema::hasTable('uom_table')
            ? DB::table('uom_table')
                ->orderByRaw('LOWER(uom_name) ASC')
                ->get(['uom_id', 'uom_name'])
            : collect();

        $brands = Schema::hasTable('brands_table')
            ? DB::table('brands_table')
                ->where('brand_status', 'Active')
                ->orderByRaw('LOWER(brand_name) ASC')
                ->get(['brand_id', 'brand_name'])
            : collect();

        $suppliers = DB::table('suppliers_table as s')
            ->leftJoin('physical_suppliers_table as ps', 'ps.supplier_id', '=', 's.supplier_id')
            ->leftJoin('online_suppliers_table as os', 'os.supplier_id', '=', 's.supplier_id')
            ->where('s.supplier_is_active', 1)
            ->select([
                's.supplier_id',
                's.supplier_store_type',
                's.supplier_is_blacklisted',
                's.supplier_blacklist_reason',
                DB::raw('COALESCE(ps.company_name, os.shop_name, CONCAT("Supplier #", s.supplier_id)) as display_name'),
                DB::raw('COALESCE(ps.contact_person, os.contact_person) as contact_person'),
            ])
            ->orderByRaw('LOWER(COALESCE(ps.company_name, os.shop_name, "")) ASC')
            ->orderBy('s.supplier_id')
            ->get();

        // Disambiguate duplicate supplier names with their contact person (e.g. "AP Electronics · ALVARO C. VEGA")
        $nameCounts = $suppliers
            ->map(fn ($s) => mb_strtolower(trim((string) $s->display_name)))
            ->countBy();
        foreach ($suppliers as $s) {
            $base = trim((string) $s->display_name);
            $contact = trim((string) ($s->contact_person ?? ''));
            $s->option_label = (($nameCounts[mb_strtolower($base)] ?? 0) > 1 && $contact !== '')
                ? $base.' · '.$contact
                : $base;
        }

        $supplierInsights = [];
        try {
            $supplierInsights = SupplierDeliveryInsights::bySupplierId(24);
        } catch (\Throwable) {
            $supplierInsights = [];
        }

        $supplierWarnings = $suppliers->mapWithKeys(function ($s) use ($supplierInsights) {
            $id = (int) $s->supplier_id;
            $ins = $supplierInsights[$id] ?? null;
            $blacklisted = (bool) ($s->supplier_is_blacklisted ?? false);
            $risk = $ins->risk ?? null;
            if (! $blacklisted && ! $risk) {
                return [];
            }

            return [
                (string) $id => [
                    'blacklisted' => $blacklisted,
                    'reason' => (string) ($s->supplier_blacklist_reason ?? ''),
                    'risk' => $risk,
                    'issues' => (int) ($ins->issue_count ?? 0),
                ],
            ];
        })->all();

        $replacementRequests = collect();
        if (Schema::hasTable('procurement_requests_table')) {
            $replacementRequests = DB::table('procurement_requests_table as pr')
                ->leftJoin('reports_table as r', 'r.report_id', '=', 'pr.procurement_request_report_id')
                ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'r.report_equipment_id')
                ->leftJoin('rooms_table as rm', 'rm.room_id', '=', 'r.report_room_id')
                ->where('pr.procurement_request_status', 'Approved')
                ->when(
                    Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived'),
                    fn ($q) => $q->where(fn ($sub) => $sub->whereNull('pr.procurement_request_is_archived')->orWhere('pr.procurement_request_is_archived', 0))
                )
                ->orderByDesc('pr.procurement_request_id')
                ->limit(100)
                ->select([
                    'pr.*',
                    'r.report_urgency_level',
                    'e.equipment_name',
                    'rm.room_name',
                ])
                ->get();

            ReplacementRequestBasket::attachToRequests($replacementRequests->all());
        }

        return [
            'items' => $items,
            'uoms' => $uoms,
            'brands' => $brands,
            'suppliers' => $suppliers,
            'supplierWarnings' => $supplierWarnings,
            'replacementRequests' => $replacementRequests,
        ];
    }

    /**
     * @return array{procurement_request_id: ?int, purpose: ?string, urgency: string, signature: ?string, items: list<array<string, mixed>>}
     */
    private function validateAndNormalize(Request $request, bool $strict, int $existingAttCount, int $existingAttBytes): array
    {
        $request->validate([
            'canvass_procurement_request_id' => ['nullable', 'integer'],
            'canvass_purpose' => ['nullable', 'string', 'max:2000'],
            'canvass_urgency' => ['nullable', 'in:'.implode(',', RisWorkflow::urgencyOptions())],
            'canvass_purchaser_signature' => ['nullable', 'string', 'max:2500000'],
            'canvass_signature_file' => ['nullable', 'image', 'max:2048'],
            'canvass_attachments' => ['nullable', 'array', 'max:'.CanvassWorkflow::MAX_ATTACHMENTS],
            'canvass_attachments.*' => [
                'nullable',
                'file',
                'max:'.CanvassWorkflow::MAX_ATTACHMENT_KB,
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp,bmp,tiff,avif,heic',
            ],
            'items' => ['required', 'array', 'min:1', 'max:'.CanvassWorkflow::MAX_ITEMS],
        ]);

        $errors = [];

        if ($strict && trim((string) $request->input('canvass_purpose', '')) === '') {
            $errors['canvass_purpose'] = 'Purpose is required before submitting.';
        }

        // Validate total attachment count & combined size (1–5 files on submit, 7 MB total)
        $uploadedFiles = array_values(array_filter($request->file('canvass_attachments', []), fn ($f) => $f && $f->isValid()));
        if ($strict && ($existingAttCount + count($uploadedFiles)) < 1) {
            $errors['canvass_attachments'] = 'Please attach at least 1 supporting quotation file before submitting.';
        }
        if (($existingAttCount + count($uploadedFiles)) > CanvassWorkflow::MAX_ATTACHMENTS) {
            $errors['canvass_attachments'] = 'You can attach up to '.CanvassWorkflow::MAX_ATTACHMENTS.' supporting files per canvass.';
        }
        $totalBytes = $existingAttBytes;
        foreach ($uploadedFiles as $f) {
            $totalBytes += (int) $f->getSize();
        }
        if ($totalBytes > CanvassWorkflow::MAX_ATTACHMENTS_TOTAL_BYTES) {
            $errors['canvass_attachments'] = 'Combined supporting files size cannot exceed 7 MB total.';
        }

        // Resolve purchaser signature (drawn data URL or uploaded image file)
        $signatureData = trim((string) $request->input('canvass_purchaser_signature', ''));
        if ($request->hasFile('canvass_signature_file') && $request->file('canvass_signature_file')->isValid()) {
            $sigFile = $request->file('canvass_signature_file');
            $mime = $sigFile->getMimeType() ?: 'image/png';
            $bin = file_get_contents($sigFile->getRealPath());
            if ($bin !== false) {
                $signatureData = 'data:'.$mime.';base64,'.base64_encode($bin);
            }
        }
        if ($signatureData !== '' && ! str_starts_with($signatureData, 'data:image/')) {
            $signatureData = '';
        }
        if ($strict && $signatureData === '') {
            $errors['canvass_purchaser_signature'] = 'Purchaser signature is required before submitting.';
        }

        $rawItems = $request->input('items', []);
        $itemCatalogNames = Schema::hasTable('items_table')
            ? DB::table('items_table')->pluck('item_name', 'item_id')->all()
            : [];

        $normalizedItems = [];

        foreach (array_values($rawItems) as $iIdx => $rawItem) {
            $label = 'Item '.($iIdx + 1);
            $itemId = ! empty($rawItem['item_id']) ? (int) $rawItem['item_id'] : null;
            $itemName = trim((string) ($rawItem['item_name'] ?? ''));
            if ($itemId && isset($itemCatalogNames[$itemId])) {
                $itemName = trim((string) $itemCatalogNames[$itemId]);
            }
            $uomId = ! empty($rawItem['uom_id']) ? (int) $rawItem['uom_id'] : null;
            $qty = isset($rawItem['quantity']) && $rawItem['quantity'] !== '' ? (int) $rawItem['quantity'] : 0;

            if ($itemName === '') {
                if ($strict) {
                    $errors["items.{$iIdx}.item_id"] = "{$label}: Please select an item.";
                } else {
                    continue;
                }
            }

            if ($strict && ! $uomId) {
                $errors["items.{$iIdx}.uom_id"] = "{$label}: Please select a unit (UOM).";
            }
            if ($strict && $qty < 1) {
                $errors["items.{$iIdx}.quantity"] = "{$label}: Requested quantity must be at least 1.";
            }

            $rawSuppliers = is_array($rawItem['suppliers'] ?? null) ? array_values($rawItem['suppliers']) : [];
            $normalizedSuppliers = [];
            $seenSuppliers = [];

            foreach ($rawSuppliers as $sIdx => $rawSup) {
                $supplierId = ! empty($rawSup['supplier_id']) ? (int) $rawSup['supplier_id'] : 0;
                $rawOffers = is_array($rawSup['offers'] ?? null) ? array_values($rawSup['offers']) : [];

                if ($supplierId <= 0) {
                    if ($strict) {
                        $errors["items.{$iIdx}.suppliers.{$sIdx}.supplier_id"] = "{$label}, Supplier ".($sIdx + 1).': Please select a supplier.';
                    }
                    continue;
                }

                if (isset($seenSuppliers[$supplierId])) {
                    $errors["items.{$iIdx}.suppliers.{$sIdx}.supplier_id"] = "{$label}: Each supplier can only be added once per item.";
                    continue;
                }
                $seenSuppliers[$supplierId] = true;

                $normalizedOffers = [];
                $seenBrandUom = [];

                foreach ($rawOffers as $oIdx => $rawOffer) {
                    $brandId = ! empty($rawOffer['brand_id']) ? (int) $rawOffer['brand_id'] : 0;
                    $offerUomId = ! empty($rawOffer['offer_uom_id']) ? (int) $rawOffer['offer_uom_id'] : ($uomId ?: 0);
                    $rawPack = str_replace(',', '', (string) ($rawOffer['units_per_pack'] ?? ''));
                    $rawPrice = str_replace(',', '', (string) ($rawOffer['unit_price'] ?? ''));
                    $unitsPerPack = $rawPack !== ''
                        ? (float) $rawPack
                        : 1.0;
                    $unitPrice = $rawPrice !== ''
                        ? (float) $rawPrice
                        : -1.0;

                    if ($brandId <= 0 && $unitPrice < 0 && ! $strict) {
                        continue;
                    }

                    if ($strict && $brandId <= 0) {
                        $errors["items.{$iIdx}.suppliers.{$sIdx}.offers.{$oIdx}.brand_id"] = "{$label}, Supplier ".($sIdx + 1).', Brand offer '.($oIdx + 1).': Select a brand.';
                        continue;
                    }
                    if ($strict && $offerUomId <= 0) {
                        $errors["items.{$iIdx}.suppliers.{$sIdx}.offers.{$oIdx}.offer_uom_id"] = "{$label}, Supplier ".($sIdx + 1).', Brand offer '.($oIdx + 1).': Select a "Sold as" unit.';
                        continue;
                    }
                    if ($strict && $unitsPerPack <= 0) {
                        $errors["items.{$iIdx}.suppliers.{$sIdx}.offers.{$oIdx}.units_per_pack"] = "{$label}, Supplier ".($sIdx + 1).', Brand offer '.($oIdx + 1).': Units per offer unit must be greater than 0.';
                        continue;
                    }
                    if ($strict && $unitPrice <= 0) {
                        $errors["items.{$iIdx}.suppliers.{$sIdx}.offers.{$oIdx}.unit_price"] = "{$label}, Supplier ".($sIdx + 1).', Brand offer '.($oIdx + 1).': Price must be greater than ₱0.00.';
                        continue;
                    }

                    if ($brandId > 0 && $offerUomId > 0) {
                        $pairKey = $brandId.':'.$offerUomId;
                        if (isset($seenBrandUom[$pairKey])) {
                            $errors["items.{$iIdx}.suppliers.{$sIdx}.offers.{$oIdx}.brand_id"] = "{$label}, Supplier ".($sIdx + 1).': A brand and unit combination can only be listed once per supplier.';
                            continue;
                        }
                        $seenBrandUom[$pairKey] = true;

                        $normalizedOffers[] = [
                            'brand_id' => $brandId,
                            'offer_uom_id' => $offerUomId,
                            'units_per_pack' => max(0.01, round($unitsPerPack, 2)),
                            'unit_price' => max(0.0, round($unitPrice, 2)),
                        ];
                    }
                }

                if ($strict && count($normalizedOffers) < 1) {
                    $errors["items.{$iIdx}.suppliers.{$sIdx}.offers"] = "{$label}, Supplier ".($sIdx + 1).': Add at least 1 brand offer.';
                }
                if (count($normalizedOffers) > CanvassWorkflow::MAX_OFFERS_PER_SUPPLIER) {
                    $errors["items.{$iIdx}.suppliers.{$sIdx}.offers"] = "{$label}, Supplier ".($sIdx + 1).': Maximum '.CanvassWorkflow::MAX_OFFERS_PER_SUPPLIER.' brand offers per supplier.';
                }

                $normalizedSuppliers[] = [
                    'supplier_id' => $supplierId,
                    'offers' => $normalizedOffers,
                ];
            }

            if ($strict && count($normalizedSuppliers) < CanvassWorkflow::MIN_SUPPLIERS_PER_ITEM) {
                $errors["items.{$iIdx}.suppliers"] = "{$label}: Add at least ".CanvassWorkflow::MIN_SUPPLIERS_PER_ITEM.' distinct suppliers before submitting.';
            }
            if (count($normalizedSuppliers) > CanvassWorkflow::MAX_SUPPLIERS_PER_ITEM) {
                $errors["items.{$iIdx}.suppliers"] = "{$label}: Maximum ".CanvassWorkflow::MAX_SUPPLIERS_PER_ITEM.' suppliers per item.';
            }

            $normalizedItems[] = [
                'item_id' => $itemId,
                'item_name' => $itemName,
                'uom_id' => $uomId,
                'quantity' => max(1, $qty),
                'suppliers' => $normalizedSuppliers,
            ];
        }

        if ($normalizedItems === []) {
            $errors['items'] = 'Add at least one item to the canvass.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $prId = $request->filled('canvass_procurement_request_id')
            ? (int) $request->input('canvass_procurement_request_id')
            : null;

        return [
            'procurement_request_id' => $prId ?: null,
            'purpose' => trim((string) $request->input('canvass_purpose', '')) ?: null,
            'urgency' => RisWorkflow::normalizeUrgency($request->input('canvass_urgency')),
            'signature' => $signatureData !== '' ? $signatureData : null,
            'items' => $normalizedItems,
        ];
    }

    private function persistItems(int $canvassId, array $items, $now): void
    {
        foreach ($items as $iIdx => $item) {
            $itemRowId = DB::table('canvass_items_table')->insertGetId([
                'canvass_id' => $canvassId,
                'item_id' => $item['item_id'],
                'canvass_item_name' => $item['item_name'],
                'uom_id' => $item['uom_id'],
                'canvass_item_quantity' => $item['quantity'],
                'canvass_item_status' => CanvassWorkflow::ITEM_PENDING,
                'canvass_item_review_note' => null,
                'canvass_item_selected_offer_id' => null,
                'canvass_item_sort_order' => $iIdx,
                'canvass_item_created_at' => $now,
                'canvass_item_updated_at' => $now,
            ]);

            foreach ($item['suppliers'] as $sIdx => $sup) {
                $supRowId = DB::table('canvass_suppliers_table')->insertGetId([
                    'canvass_item_id' => $itemRowId,
                    'supplier_id' => $sup['supplier_id'],
                    'canvass_supplier_sort_order' => $sIdx,
                    'canvass_supplier_created_at' => $now,
                    'canvass_supplier_updated_at' => $now,
                ]);

                foreach ($sup['offers'] as $oIdx => $offer) {
                    DB::table('canvass_offers_table')->insert([
                        'canvass_supplier_id' => $supRowId,
                        'brand_id' => $offer['brand_id'],
                        'offer_uom_id' => $offer['offer_uom_id'],
                        'offer_units_per_pack' => $offer['units_per_pack'],
                        'offer_unit_price' => $offer['unit_price'],
                        'offer_sort_order' => $oIdx,
                        'offer_created_at' => $now,
                        'offer_updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function storeAttachments(int $canvassId, array $files): void
    {
        if (! Schema::hasTable('canvass_attachments_table')) {
            return;
        }

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $storedPath = $file->store('canvass_attachments/'.$canvassId, 'local');
            DB::table('canvass_attachments_table')->insert([
                'canvass_id' => $canvassId,
                'canvass_attachment_original_name' => $file->getClientOriginalName(),
                'canvass_attachment_stored_path' => $storedPath,
                'canvass_attachment_mime_type' => $file->getClientMimeType(),
                'canvass_attachment_size' => (int) $file->getSize(),
                'canvass_attachment_uploaded_by' => (int) Auth::id(),
                'canvass_attachment_created_at' => now(),
            ]);
        }
    }

    /**
     * Rebuild initial items structure from Laravel old('items') after validation redirect.
     *
     * @return list<array<string, mixed>>|null
     */
    private function hydrateOldItems(mixed $oldItems): ?array
    {
        if (! is_array($oldItems) || $oldItems === []) {
            return null;
        }

        $hydrated = [];
        foreach (array_values($oldItems) as $rawItem) {
            if (! is_array($rawItem)) {
                continue;
            }
            $uomId = (string) ($rawItem['uom_id'] ?? '');
            $rawSuppliers = is_array($rawItem['suppliers'] ?? null) ? array_values($rawItem['suppliers']) : [];
            $suppliers = [];

            foreach ($rawSuppliers as $rawSup) {
                if (! is_array($rawSup)) {
                    continue;
                }
                $rawOffers = is_array($rawSup['offers'] ?? null) ? array_values($rawSup['offers']) : [];
                $offers = [];
                foreach ($rawOffers as $rawOffer) {
                    if (! is_array($rawOffer)) {
                        continue;
                    }
                    $offers[] = [
                        'brand_id' => (string) ($rawOffer['brand_id'] ?? ''),
                        'offer_uom_id' => (string) ($rawOffer['offer_uom_id'] ?? $uomId),
                        'units_per_pack' => (string) ($rawOffer['units_per_pack'] ?? '1'),
                        'unit_price' => (string) ($rawOffer['unit_price'] ?? ''),
                    ];
                }
                if ($offers === []) {
                    $offers[] = ['brand_id' => '', 'offer_uom_id' => $uomId, 'units_per_pack' => '1', 'unit_price' => ''];
                }
                $suppliers[] = [
                    'supplier_id' => (string) ($rawSup['supplier_id'] ?? ''),
                    'offers' => $offers,
                ];
            }

            while (count($suppliers) < 2) {
                $suppliers[] = [
                    'supplier_id' => '',
                    'offers' => [['brand_id' => '', 'offer_uom_id' => $uomId, 'units_per_pack' => '1', 'unit_price' => '']],
                ];
            }

            $hydrated[] = [
                'item_id' => (string) ($rawItem['item_id'] ?? ''),
                'item_name' => (string) ($rawItem['item_name'] ?? ''),
                'uom_id' => $uomId,
                'quantity' => (string) ($rawItem['quantity'] ?? ''),
                'collapsed' => false,
                'suppliers' => $suppliers,
            ];
        }

        return $hydrated !== [] ? $hydrated : null;
    }
}
