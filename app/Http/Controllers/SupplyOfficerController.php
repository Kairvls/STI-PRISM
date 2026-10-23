<?php

namespace App\Http\Controllers;

use App\Support\AtpRisSuppliers;
use App\Support\PurchaserDocumentAccess;
use App\Support\RisWorkflow;
use App\Support\SupplyHandoff;
use App\Support\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupplyOfficerController extends Controller
{
    public function dashboard(): View
    {
        $incomingRisCount = 0;
        $atpDraftCount = 0;
        $atpPendingCount = 0;
        $atpToReleaseCount = 0;
        $atpReturnedCount = 0;

        $latestIncomingRis = collect();
        $latestAtpDrafts = collect();
        $latestAtpPending = collect();
        $latestAtpToRelease = collect();
        $latestAtpReturned = collect();

        try {
            if (SupplyHandoff::risHandoffColumnsReady()) {
                $incomingQuery = DB::table('requisition_issue_slip_table')
                    ->leftJoin(
                        'users_table as senders',
                        'requisition_issue_slip_table.ris_sent_to_supply_by',
                        '=',
                        'senders.user_id'
                    )
                    ->select(
                        'requisition_issue_slip_table.ris_id',
                        'requisition_issue_slip_table.ris_form_number',
                        'requisition_issue_slip_table.ris_request_no',
                        'requisition_issue_slip_table.ris_purpose_description',
                        'requisition_issue_slip_table.ris_sent_to_supply_at',
                        'senders.user_full_name as sent_by_name'
                    );

                SupplyHandoff::scopeIncomingRisForSupply($incomingQuery);

                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
                    $incomingQuery->where(function ($inner) {
                        $inner->whereNull('requisition_issue_slip_table.ris_is_archived')
                            ->orWhere('requisition_issue_slip_table.ris_is_archived', 0);
                    });
                }

                $incomingRisCount = (int) (clone $incomingQuery)->count();
                $latestIncomingRis = (clone $incomingQuery)
                    ->orderByDesc('requisition_issue_slip_table.ris_sent_to_supply_at')
                    ->limit(5)
                    ->get();

                $progress = AtpRisSuppliers::progress($latestIncomingRis->pluck('ris_id'));
                foreach ($latestIncomingRis as $ris) {
                    $row = $progress[(int) $ris->ris_id] ?? ['total' => 0, 'open' => 0];
                    $ris->supplier_total = (int) $row['total'];
                    $ris->supplier_open = (int) $row['open'];
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            if (Schema::hasTable('authority_to_purchase_table')) {
                $base = DB::table('authority_to_purchase_table')
                    ->leftJoin(
                        'requisition_issue_slip_table',
                        'authority_to_purchase_table.authority_purchase_ris_id',
                        '=',
                        'requisition_issue_slip_table.ris_id'
                    )
                    ->select(
                        'authority_to_purchase_table.authority_purchase_id',
                        'authority_to_purchase_table.authority_purchase_form_number',
                        'authority_to_purchase_table.authority_purchase_status',
                        'authority_to_purchase_table.authority_purchase_submitted_at',
                        'authority_to_purchase_table.authority_purchase_updated_at',
                        'authority_to_purchase_table.authority_purchase_created_at',
                        'requisition_issue_slip_table.ris_form_number',
                        'requisition_issue_slip_table.ris_request_no'
                    )
                    ->where(function ($q) {
                        $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                            ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
                    });

                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_created_by')) {
                    $base->where('authority_to_purchase_table.authority_purchase_created_by', Auth::id());
                }

                if (SupplyHandoff::atpReleaseColumnsReady()) {
                    $base->addSelect('authority_to_purchase_table.authority_purchase_released_to_purchaser_at');
                }

                if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_rejection_reason')) {
                    $base->addSelect('authority_to_purchase_table.authority_purchase_rejection_reason');
                }

                $draftQuery = (clone $base)
                    ->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                    ->whereNull('authority_to_purchase_table.authority_purchase_submitted_at');
                $atpDraftCount = (int) (clone $draftQuery)->count();
                $latestAtpDrafts = (clone $draftQuery)
                    ->orderByDesc('authority_to_purchase_table.authority_purchase_updated_at')
                    ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
                    ->limit(5)
                    ->get();

                $pendingQuery = (clone $base)
                    ->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                    ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at');
                $atpPendingCount = (int) (clone $pendingQuery)->count();
                $latestAtpPending = (clone $pendingQuery)
                    ->orderByDesc('authority_to_purchase_table.authority_purchase_submitted_at')
                    ->limit(5)
                    ->get();

                $releaseQuery = (clone $base)
                    ->where('authority_to_purchase_table.authority_purchase_status', 'Approved');
                if (SupplyHandoff::atpReleaseColumnsReady()) {
                    $releaseQuery->whereNull('authority_to_purchase_table.authority_purchase_released_to_purchaser_at');
                }
                $atpToReleaseCount = (int) (clone $releaseQuery)->count();
                $latestAtpToRelease = (clone $releaseQuery)
                    ->orderByDesc('authority_to_purchase_table.authority_purchase_updated_at')
                    ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
                    ->limit(5)
                    ->get();

                if (SupplyHandoff::atpReleaseColumnsReady()) {
                    $returnedQuery = (clone $base)
                        ->where('authority_to_purchase_table.authority_purchase_status', 'Approved')
                        ->whereNotNull('authority_to_purchase_table.authority_purchase_released_to_purchaser_at');
                    $atpReturnedCount = (int) (clone $returnedQuery)->count();
                    $latestAtpReturned = (clone $returnedQuery)
                        ->orderByDesc('authority_to_purchase_table.authority_purchase_released_to_purchaser_at')
                        ->limit(5)
                        ->get();
                }
            }
        } catch (\Throwable $e) {
        }

        return view('supply-officer.dashboard', [
            'user' => Auth::user(),
            'incomingRisCount' => $incomingRisCount,
            'atpDraftCount' => $atpDraftCount,
            'atpPendingCount' => $atpPendingCount,
            'atpToReleaseCount' => $atpToReleaseCount,
            'atpReturnedCount' => $atpReturnedCount,
            'latestIncomingRis' => $latestIncomingRis,
            'latestAtpDrafts' => $latestAtpDrafts,
            'latestAtpPending' => $latestAtpPending,
            'latestAtpToRelease' => $latestAtpToRelease,
            'latestAtpReturned' => $latestAtpReturned,
        ]);
    }

    public function previewIncomingRis(int $ris): View
    {
        $record = $this->assertSupplyVisibleRis($ris);
        $attachments = Schema::hasTable('ris_attachments_table')
            ? DB::table('ris_attachments_table')
                ->where('ris_id', $ris)
                ->orderByDesc('ris_attachment_id')
                ->limit(1)
                ->get()
            : collect();

        return view('supply-officer.dashboard-preview', [
            'kind' => 'ris',
            'ris' => $record,
            'risItems' => $this->risItemsWithLookups([$ris]),
            'attachments' => $attachments,
        ]);
    }

    public function previewAtp(int $atp): View
    {
        $record = DB::table('authority_to_purchase_table')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('authority_to_purchase_table.authority_purchase_id', $atp)
            ->select(
                'authority_to_purchase_table.*',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->first();

        abort_if(! $record, 404);
        PurchaserDocumentAccess::assertOwns($record, 'atp');

        $items = Schema::hasTable('authority_to_purchase_items_table')
            ? DB::table('authority_to_purchase_items_table')
                ->where('authority_purchase_id', $atp)
                ->orderBy('atp_item_id')
                ->get()
            : collect();

        return view('supply-officer.dashboard-preview', [
            'kind' => 'atp',
            'atp' => $record,
            'items' => $items,
        ]);
    }

    public function notifications(): View
    {
        $items = collect();
        try {
            $items = WorkflowNotifier::scopeVisibleTo(
                DB::table('notifications_table'),
                Auth::id(),
                WorkflowNotifier::ROLE_SUPPLY_OFFICER
            )
                ->orderByDesc('notification_created_at')
                ->limit(80)
                ->get();
        } catch (\Throwable $e) {
        }

        return view('supply-officer.notifications.index', compact('items'));
    }

    public function incomingRis(Request $request): View
    {
        $records = collect();
        $search = trim((string) $request->query('search', ''));

        if (SupplyHandoff::risHandoffColumnsReady()) {
            $query = DB::table('requisition_issue_slip_table')
                ->leftJoin(
                    'users_table as senders',
                    'requisition_issue_slip_table.ris_sent_to_supply_by',
                    '=',
                    'senders.user_id'
                )
                ->select(
                    'requisition_issue_slip_table.*',
                    'senders.user_full_name as sent_by_name'
                );

            SupplyHandoff::scopeIncomingRisForSupply($query);

            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
                $query->where(function ($q) {
                    $q->whereNull('requisition_issue_slip_table.ris_is_archived')
                        ->orWhere('requisition_issue_slip_table.ris_is_archived', 0);
                });
            }

            if ($search !== '') {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('requisition_issue_slip_table.ris_form_number', 'like', $like)
                        ->orWhere('requisition_issue_slip_table.ris_purpose_description', 'like', $like)
                        ->orWhere('senders.user_full_name', 'like', $like);
                    if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
                        $q->orWhere('requisition_issue_slip_table.ris_request_no', 'like', $like);
                    }
                });
            }

            $records = $query
                ->orderByDesc('requisition_issue_slip_table.ris_sent_to_supply_at')
                ->paginate(15)
                ->withQueryString();
        }

        return view('supply-officer.incoming-ris.index', [
            'records' => $records,
            'search' => $search,
        ]);
    }

    /**
     * Read-only Canvas + RIS reference (same idea as Accounting package refs for DV).
     */
    public function showIncomingRis(int $risId): View
    {
        $ris = $this->assertSupplyVisibleRis($risId);

        $risItems = $this->risItemsWithLookups([$risId]);
        $attachments = Schema::hasTable('ris_attachments_table')
            ? DB::table('ris_attachments_table')
                ->where('ris_id', $risId)
                ->orderBy('ris_attachment_original_name')
                ->get()
            : collect();

        return view('supply-officer.incoming-ris.show', [
            'ris' => $ris,
            'risItems' => $risItems,
            'attachments' => $attachments,
        ]);
    }

    public function downloadIncomingRisAttachment(int $risId, int $attachmentId): BinaryFileResponse
    {
        $this->assertSupplyVisibleRis($risId);
        abort_unless(Schema::hasTable('ris_attachments_table'), 404);

        $file = DB::table('ris_attachments_table')
            ->where('ris_id', $risId)
            ->where('ris_attachment_id', $attachmentId)
            ->first();
        abort_if(! $file, 404);
        abort_unless(Storage::disk('public')->exists($file->ris_attachment_path), 404);

        $path = storage_path('app/public/'.$file->ris_attachment_path);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.$file->ris_attachment_original_name.'"',
        ]);
    }

    /**
     * RIS the Supply Officer may open as ATP reference (sent by Purchaser).
     */
    private function assertSupplyVisibleRis(int $risId): object
    {
        abort_unless(SupplyHandoff::risHandoffColumnsReady(), 404);

        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(! $ris, 404);
        abort_unless(SupplyHandoff::risIsSentToSupply($ris), 404);

        $assigned = (int) ($ris->ris_supply_officer_user_id ?? 0);
        if ($assigned > 0 && $assigned !== (int) Auth::id()) {
            abort(403, 'This RIS was sent to another School Supply / Material Officer.');
        }

        return $ris;
    }

    /**
     * @param  iterable<int>  $risIds
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function risItemsWithLookups(iterable $risIds)
    {
        $risIds = collect($risIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($risIds->isEmpty() || ! Schema::hasTable('requisition_issue_slip_items_table')) {
            return collect();
        }

        $query = DB::table('requisition_issue_slip_items_table')
            ->whereIn('requisition_issue_slip_items_table.ris_id', $risIds)
            ->orderBy('ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];

        if (
            Schema::hasTable('uom_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')
        ) {
            $query->leftJoin(
                'uom_table',
                'uom_table.uom_id',
                '=',
                'requisition_issue_slip_items_table.ris_item_uom_id'
            );
            $select[] = 'uom_table.uom_name';
        }

        if (
            Schema::hasTable('brands_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')
        ) {
            $query->leftJoin(
                'brands_table',
                'brands_table.brand_id',
                '=',
                'requisition_issue_slip_items_table.ris_item_brand_id'
            );
            $select[] = 'brands_table.brand_name';
        }

        if (
            Schema::hasTable('suppliers_table')
            && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')
        ) {
            $query
                ->leftJoin(
                    'suppliers_table',
                    'suppliers_table.supplier_id',
                    '=',
                    'requisition_issue_slip_items_table.ris_item_supplier_id'
                )
                ->leftJoin(
                    'physical_suppliers_table',
                    'physical_suppliers_table.supplier_id',
                    '=',
                    'suppliers_table.supplier_id'
                )
                ->leftJoin(
                    'online_suppliers_table',
                    'online_suppliers_table.supplier_id',
                    '=',
                    'suppliers_table.supplier_id'
                );

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
}
