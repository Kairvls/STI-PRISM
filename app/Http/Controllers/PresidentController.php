<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Support\PresidentApprovalGuide;
use App\Support\PresidentAttentionSummary;
use App\Support\PresidentDashboardInsights;
use App\Support\RisForwardAttachments;
use App\Support\RisRevisionImages;
use App\Support\RisWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;

class PresidentController extends Controller
{
    // =====================================================
    // DASHBOARD
    // =====================================================

    public function dashboard(Request $request): View
    {
        $attention = PresidentAttentionSummary::counts();
        $pendingApprovalsCount = $attention['pendingApprovalsCount'];
        $awaitingNotifyCount = $attention['awaitingNotifyCount'];

        $approvedDecisionsCount =
            DB::table('requisition_issue_slip_table')
                ->where(function ($q) {
                    $this->scopePresidentApproved($q);
                })
                ->count();

        // ================================
        // Top 3 most recent RIS awaiting decision
        // ================================
        $recentRis = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_requested_by_signature',
                'ris.ris_approved_by_signature',
                'ris.ris_created_by',
                'ris.ris_submitted_by',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->where(function ($q) {
                $this->scopeAwaitingPresident($q, 'ris.');
            })
            ->groupBy(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_requested_by_signature',
                'ris.ris_approved_by_signature',
                'ris.ris_created_by',
                'ris.ris_submitted_by'
            )
            ->orderByDesc('ris.ris_created_at')
            ->orderByDesc('ris.ris_id')
            ->limit(3)
            ->get();
        $recentRis = PresidentApprovalGuide::attach($recentRis);

        // ================================
        // Recently approved RIS (for dashboard panel)
        // ================================
        $recentlyApprovedRis = DB::table('requisition_issue_slip_table')
            ->select(
                'ris_id',
                'ris_form_number',
                'ris_status',
                'ris_approved_by_date',
                'ris_purpose_description',
                DB::raw('(SELECT COALESCE(SUM(items.ris_total_amount), 0) FROM requisition_issue_slip_items_table items WHERE items.ris_id = requisition_issue_slip_table.ris_id) as total_amount'),
                DB::raw($this->risSignaturePresenceSql('ris_approved_by_signature').' as ris_approved_by_signature'),
                DB::raw($this->risSignaturePresenceSql('ris_issued_by_signature').' as ris_issued_by_signature')
            )
            ->where(function ($q) {
                $this->scopePresidentApproved($q);
            })
            ->orderByDesc('ris_approved_by_date')
            ->orderByDesc('ris_id')
            ->limit(5)
            ->get();

        $dashboardNotifiedIds = $this->presidentNotifiedAdminIds(
            $recentlyApprovedRis->pluck('ris_id')->map(fn ($id) => (int) $id)->all()
        );
        $recentlyApprovedRis = $recentlyApprovedRis->map(function ($ris) use ($dashboardNotifiedIds) {
            $ris->admin_notified = isset($dashboardNotifiedIds[(int) $ris->ris_id]);
            $ris->awaiting_notify = ! $ris->admin_notified
                && trim((string) ($ris->ris_issued_by_signature ?? '')) === '';

            return $ris;
        });

        $toNotify = PresidentAttentionSummary::scopeAwaitingNotify(
            DB::table('requisition_issue_slip_table')->select('ris_id', 'ris_form_number', 'ris_approved_by_date')
        )
            ->orderByDesc('ris_approved_by_date')
            ->orderByDesc('ris_id')
            ->limit(5)
            ->get();

        $pendingDvCount = 0;
        $pendingDvs = collect();
        if (Schema::hasTable('disbursement_vouchers_table')) {
            $dvBase = DB::table('disbursement_vouchers_table')
                ->where('dv_status', 'With President')
                ->where(function ($q) {
                    $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                });
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_president_id')) {
                \App\Support\ReviewerAssignment::applyQueueFilter($dvBase, 'dv_assigned_president_id');
            }
            $pendingDvCount = (clone $dvBase)->count();
            $pendingDvs = (clone $dvBase)
                ->leftJoin('request_check_table', 'disbursement_vouchers_table.dv_request_check_id', '=', 'request_check_table.request_check_id')
                ->orderBy('disbursement_vouchers_table.dv_president_submitted_at')
                ->orderBy('disbursement_vouchers_table.dv_id')
                ->limit(3)
                ->get([
                    'disbursement_vouchers_table.dv_id',
                    'disbursement_vouchers_table.dv_form_number',
                    'disbursement_vouchers_table.dv_payee',
                    'disbursement_vouchers_table.dv_amount',
                    'request_check_table.request_check_payee',
                ]);
        }

        $compiledRecordCount = 0;
        $compiledRecords = collect();
        if (Schema::hasTable('procurement_record_packages_table')) {
            $compiledBase = DB::table('procurement_record_packages_table')
                ->where('package_status', 'forwarded_to_president')
                ->where('package_is_archived', 0);
            $compiledRecordCount = (clone $compiledBase)->count();
            $compiledRecords = (clone $compiledBase)
                ->leftJoin(
                    'authority_to_purchase_table',
                    'procurement_record_packages_table.package_authority_purchase_id',
                    '=',
                    'authority_to_purchase_table.authority_purchase_id'
                )
                ->leftJoin(
                    'requisition_issue_slip_table',
                    'authority_to_purchase_table.authority_purchase_ris_id',
                    '=',
                    'requisition_issue_slip_table.ris_id'
                )
                ->orderByDesc('procurement_record_packages_table.package_forwarded_to_president_at')
                ->limit(4)
                ->get([
                    'procurement_record_packages_table.package_id',
                    'procurement_record_packages_table.package_authority_purchase_id',
                    'procurement_record_packages_table.package_payment_path',
                    'procurement_record_packages_table.package_forwarded_to_president_at',
                    'authority_to_purchase_table.authority_purchase_form_number',
                    Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no')
                        ? 'authority_to_purchase_table.authority_purchase_request_no'
                        : DB::raw('NULL as authority_purchase_request_no'),
                    Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')
                        ? 'requisition_issue_slip_table.ris_request_no'
                        : DB::raw('NULL as ris_request_no'),
                    'requisition_issue_slip_table.ris_form_number',
                    DB::raw(
                        Schema::hasTable('authority_to_purchase_items_table')
                            ? '(SELECT COALESCE(SUM(COALESCE(i.atp_amount, i.atp_quantity * i.atp_unit_price, 0)), 0) FROM authority_to_purchase_items_table i WHERE i.authority_purchase_id = authority_to_purchase_table.authority_purchase_id) as package_amount'
                            : '0 as package_amount'
                    ),
                ]);
        }

        return view('president.dashboard', [
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'approvedDecisionsCount' => $approvedDecisionsCount,
            'awaitingNotifyCount' => $awaitingNotifyCount,
            'compiledRecords' => $compiledRecords,
            'compiledRecordCount' => $compiledRecordCount,
            'recentRis' => $recentRis,
            'recentlyApprovedRis' => $recentlyApprovedRis,
            'toNotify' => $toNotify,
            'pendingDvs' => $pendingDvs,
            'pendingDvCount' => $pendingDvCount,
            'approvedBudget' => $this->approvedBudgetSummary($request),
            'directApprovals' => PresidentDashboardInsights::directApprovals(),
            'directApprovalCount' => (int) DB::table('requisition_issue_slip_table')
                ->where('ris_status', RisWorkflow::DIRECTLY_APPROVED)
                ->count(),
        ]);
    }

    // =====================================================
    // APPROVALS
    // =====================================================

    public function dashboardPreview(string $type, int $id)
    {
        if ($type === 'atp') {
            abort_unless(Schema::hasTable('procurement_record_packages_table'), 404);
            $allowed = DB::table('procurement_record_packages_table')
                ->where('package_authority_purchase_id', $id)
                ->where('package_status', 'forwarded_to_president')
                ->where('package_is_archived', 0)
                ->exists();
            abort_unless($allowed, 404);

            $atp = DB::table('authority_to_purchase_table')
                ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
                ->where('authority_to_purchase_table.authority_purchase_id', $id)
                ->select(
                    'authority_to_purchase_table.*',
                    'suppliers_table.supplier_store_type',
                    'physical_suppliers_table.company_name',
                    'online_suppliers_table.shop_name'
                )
                ->first();
            abort_if(! $atp, 404);
            $items = Schema::hasTable('authority_to_purchase_items_table')
                ? DB::table('authority_to_purchase_items_table')->where('authority_purchase_id', $id)->orderBy('atp_item_id')->get()
                : collect();

            return view('president.dashboard-preview', [
                'kind' => 'atp',
                'title' => $atp->authority_purchase_form_number ?: 'Authority to Purchase',
                'atp' => $atp,
                'items' => $items,
            ]);
        }

        abort(404);
    }

    public function approvals(Request $request)
    {
        // ================================
        // Dashboard summary counts (ALL forwarded RIS)
        // ================================
        $forwardedBase = DB::table('requisition_issue_slip_table')
            ->whereNotNull('ris_requested_by_date')
            ->where('ris_status', '!=', 'Directly Approved');

        $totalRisCount = (clone $forwardedBase)->count();

        $totalPendingRis = (clone $forwardedBase)
            ->where(function ($q) {
                $this->scopeAwaitingPresident($q);
            })
            ->count();

        $totalApprovedRis = (clone $forwardedBase)
            ->where(function ($q) {
                $this->scopePresidentApproved($q);
            })
            ->count();

        $totalRejectedRis = (clone $forwardedBase)
            ->whereIn('ris_status', ['Rejected', 'Rejected by President', 'Rejected by the President'])
            ->count();

        // ================================
        // Base query: records forwarded to President
        // ================================
        $pendingSelect = [
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_purpose_description',
            'ris.ris_status',
            'ris.ris_approved_by_date',
            DB::raw('MAX('.$this->risSignaturePresenceSql('ris.ris_approved_by_signature').') as ris_approved_by_signature'),
            'ris.ris_created_by',
            'ris.ris_submitted_by',
            'ris.ris_created_at',
            DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount'),
        ];
        $pendingGroupBy = [
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_purpose_description',
            'ris.ris_status',
            'ris.ris_approved_by_date',
            'ris.ris_created_by',
            'ris.ris_submitted_by',
            'ris.ris_created_at',
        ];
        $this->appendRisRequestNoSelect($pendingSelect, $pendingGroupBy);
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_details')) {
            $pendingSelect[] = 'ris.ris_forward_details';
            $pendingGroupBy[] = 'ris.ris_forward_details';
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path')) {
            $pendingSelect[] = 'ris.ris_forward_attachment_path';
            $pendingGroupBy[] = 'ris.ris_forward_attachment_path';
        }

        // Daily reminder focus: show only the rows counted by that reminder item.
        $focus = (string) $request->query('focus', '');
        $focusMeta = PresidentAttentionSummary::focusMeta($focus);
        $recordRisId = max(0, (int) $request->query('ris', 0));
        if ($focusMeta === null || $recordRisId > 0) {
            $focus = '';
            $focusMeta = null;
        }

        $query = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select($pendingSelect)
            ->groupBy($pendingGroupBy);

        PresidentAttentionSummary::scopePendingApproval($query, 'ris.');

        if ($focus === PresidentAttentionSummary::FOCUS_AWAITING_NOTIFY) {
            $query->whereRaw('1 = 0');
        }

        if ($recordRisId > 0) {
            $query->where('ris.ris_id', $recordRisId);
        }

        // ================================
        // Search filter
        // ================================
        $search = $request->filled('search') && $recordRisId === 0 ? (string) $request->search : '';
        $applySearch = function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%")
                  ->orWhereIn('ris.ris_created_by', RisWorkflow::userIdsMatchingName($search));

                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
                    $q->orWhere('ris.ris_request_no', 'LIKE', "%{$search}%");
                }

                $this->addDateSearch($q, 'ris.ris_created_at', $search);
            });
        };

        if ($search !== '') {
            $applySearch($query);
        }

        $queueDate = $this->normalizeFilterDate($request->query('queue_date'));
        $recentDate = $this->normalizeFilterDate($request->query('recent_date'));

        if ($queueDate !== null) {
            $query->whereDate('ris.ris_created_at', $queueDate);
        }

        // ================================
        // Order & paginate (newest first)
        // ================================
        $pendingRis = $query
            ->orderByDesc('ris.ris_created_at')
            ->orderByDesc('ris.ris_id')
            ->paginate(10)
            ->withQueryString();

        // ================================
        // Approved but not yet notified (eligible to pin / stay in queue)
        // ================================
        $awaitingSelect = [
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_purpose_description',
            'ris.ris_status',
            'ris.ris_approved_by_date',
            DB::raw('MAX('.$this->risSignaturePresenceSql('ris.ris_approved_by_signature').') as ris_approved_by_signature'),
            DB::raw('MAX('.$this->risSignaturePresenceSql('ris.ris_issued_by_signature').') as ris_issued_by_signature'),
            'ris.ris_created_by',
            'ris.ris_submitted_by',
            'ris.ris_created_at',
            DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount'),
        ];
        $awaitingGroupBy = [
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_purpose_description',
            'ris.ris_status',
            'ris.ris_approved_by_date',
            'ris.ris_created_by',
            'ris.ris_submitted_by',
            'ris.ris_created_at',
        ];
        $this->appendRisRequestNoSelect($awaitingSelect, $awaitingGroupBy);
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_details')) {
            $awaitingSelect[] = 'ris.ris_forward_details';
            $awaitingGroupBy[] = 'ris.ris_forward_details';
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path')) {
            $awaitingSelect[] = 'ris.ris_forward_attachment_path';
            $awaitingGroupBy[] = 'ris.ris_forward_attachment_path';
        }

        $awaitingQuery = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select($awaitingSelect)
            ->groupBy($awaitingGroupBy)
            ->orderBy('ris.ris_approved_by_date')
            ->orderBy('ris.ris_id');

        PresidentAttentionSummary::scopeAwaitingNotify($awaitingQuery, 'ris.');

        if ($recordRisId > 0) {
            $awaitingQuery->where('ris.ris_id', $recordRisId);
        } elseif ($focus === PresidentAttentionSummary::FOCUS_AWAITING_APPROVAL) {
            $awaitingQuery->whereRaw('1 = 0');
        } elseif ($search !== '') {
            $applySearch($awaitingQuery);
        }

        if ($queueDate !== null) {
            $awaitingQuery->where(function ($q) use ($queueDate) {
                $q->whereDate('ris.ris_approved_by_date', $queueDate)
                    ->orWhere(function ($inner) use ($queueDate) {
                        $inner->whereNull('ris.ris_approved_by_date')
                            ->whereDate('ris.ris_created_at', $queueDate);
                    });
            });
        }

        $awaitingNotifyRis = $awaitingQuery
            ->limit(40)
            ->get()
            ->map(function ($ris) {
                $ris->is_president_approved = true;
                $ris->awaiting_notify = true;
                $ris->queue_kind = 'awaiting_notify';

                return $ris;
            });

        // ================================
        // Recent decisions (oldest first, paginated)
        // ================================
        $recentRis = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_approved_by_date',
                Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')
                    ? 'ris.ris_request_no'
                    : DB::raw('NULL as ris_request_no'),
                DB::raw('MAX('.$this->risSignaturePresenceSql('ris.ris_approved_by_signature').') as ris_approved_by_signature'),
                DB::raw('MAX('.$this->risSignaturePresenceSql('ris.ris_issued_by_signature').') as ris_issued_by_signature'),
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->whereIn('ris.ris_status', [
                'Approved',
                'Approved by the President',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
            ])
            ->whereNotNull('ris.ris_requested_by_date')
            ->where('ris.ris_status', '!=', 'Directly Approved')
            ->when($recordRisId > 0, fn ($q) => $q->where('ris.ris_id', $recordRisId))
            ->when($recentDate !== null, function ($q) use ($recentDate) {
                $q->where(function ($inner) use ($recentDate) {
                    $inner->whereDate('ris.ris_approved_by_date', $recentDate)
                        ->orWhere(function ($fallback) use ($recentDate) {
                            $fallback->whereNull('ris.ris_approved_by_date')
                                ->whereDate('ris.ris_created_at', $recentDate);
                        });
                });
            })
            ->groupBy(array_values(array_filter([
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_created_at',
                'ris.ris_purpose_description',
                'ris.ris_approved_by_date',
                Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no') ? 'ris.ris_request_no' : null,
            ])))
            ->orderByRaw("CASE WHEN ris.ris_status IN ('Approved', 'Approved by the President') AND TRIM(COALESCE(MAX(".$this->risSignaturePresenceSql('ris.ris_issued_by_signature')."), '')) = '' THEN 0 ELSE 1 END")
            ->orderBy('ris.ris_approved_by_date')
            ->orderBy('ris.ris_created_at')
            ->orderBy('ris.ris_id')
            ->paginate(5, ['*'], 'recent_page')
            ->withQueryString();

        $recentNotifiedIds = $this->presidentNotifiedAdminIds(
            $recentRis->getCollection()->pluck('ris_id')->map(fn ($id) => (int) $id)->all()
        );

        $recentRis->setCollection(
            $recentRis->getCollection()
                ->map(function ($ris) use ($recentNotifiedIds) {
                $isApproved = RisWorkflow::isPresidentApproved($ris);
                $ris->is_president_approved = $isApproved;
                $ris->admin_notified = $isApproved && isset($recentNotifiedIds[(int) $ris->ris_id]);
                $ris->awaiting_notify = $isApproved && !$ris->admin_notified
                    && trim((string) ($ris->ris_issued_by_signature ?? '')) === '';

                return $ris;
            })
                ->sortBy([
                    fn ($ris) => ! empty($ris->awaiting_notify) ? 0 : 1,
                    fn ($ris) => $ris->ris_approved_by_date ?: $ris->ris_created_at ?: '',
                    fn ($ris) => (int) $ris->ris_id,
                ])
                ->values()
        );

        $recentAttachmentFiles = collect();
        if (Schema::hasTable('ris_attachments_table') && $recentRis->count() > 0) {
            $recentAttachmentFiles = DB::table('ris_attachments_table')
                ->whereIn('ris_id', $recentRis->getCollection()->pluck('ris_id'))
                ->orderBy('ris_attachment_original_name')
                ->get()
                ->groupBy('ris_id');
        }

        $recentRis->setCollection(
            $recentRis->getCollection()->map(function ($ris) use ($recentAttachmentFiles) {
                $ris->supportingDocuments = ($recentAttachmentFiles->get($ris->ris_id, collect()))
                    ->map(fn ($file) => [
                        'id' => $file->ris_attachment_id,
                        'name' => $file->ris_attachment_original_name,
                        'url' => route('president.ris.attachments.preview', $file->ris_attachment_id),
                    ])
                    ->values()
                    ->all();

                return $ris;
            })
        );

        $attentionFocus = $recordRisId > 0
            ? \App\Support\RecordFocus::ris($recordRisId, route('president.approvals'))
            : ($focusMeta === null ? null : $focusMeta + [
                'key' => $focus,
                'clear_url' => route('president.approvals', array_filter(['search' => $search])),
            ]);
        if ($recordRisId > 0) {
            $attentionFocus['total'] = $pendingRis->total() + $awaitingNotifyRis->count() + $recentRis->total();
            if ($pendingRis->total() + $awaitingNotifyRis->count() === 0 && $recentRis->total() > 0) {
                $attentionFocus['description'] = 'This RIS is already decided. It is listed under Recent decisions.';
            }
        }

        // ================================
        // AJAX response: return JSON with rendered partial
        // ================================
        if ($request->ajax()) {
            if ($request->get('section') === 'recent') {
                $listHtml = view('president.approvals._recent-list', [
                    'recentRis' => $recentRis,
                ])->render();

                return response()->json([
                    'list_html' => $listHtml,
                    'total' => $recentRis->total(),
                    'from' => $recentRis->firstItem(),
                    'to' => $recentRis->lastItem(),
                    'current_page' => $recentRis->currentPage(),
                    'last_page' => $recentRis->lastPage(),
                ]);
            }

            $tableHtml = view('president.approvals._table', [
                'pendingRis' => $pendingRis,
                'awaitingNotifyRis' => $awaitingNotifyRis,
                'attentionFocus' => $attentionFocus,
            ])->render();

            return response()->json([
                'table_html' => $tableHtml,
                'total' => $pendingRis->total(),
                'awaiting_review' => $totalPendingRis,
                'from' => $pendingRis->firstItem(),
                'to' => $pendingRis->lastItem(),
                'current_page' => $pendingRis->currentPage(),
                'last_page' => $pendingRis->lastPage(),
            ]);
        }

        $pendingValue = (float) DB::table('requisition_issue_slip_items_table as items')
            ->join('requisition_issue_slip_table as ris', 'items.ris_id', '=', 'ris.ris_id')
            ->where(function ($q) {
                $this->scopeAwaitingPresident($q, 'ris.');
            })
            ->sum('items.ris_total_amount');

        $latestSubmitted = $pendingRis->first();

        return view('president.approvals.index', [
            'pendingRis' => $pendingRis,
            'awaitingNotifyRis' => $awaitingNotifyRis,
            'totalRisCount' => $totalRisCount,
            'totalPendingRis' => $totalPendingRis,
            'totalApprovedRis' => $totalApprovedRis,
            'totalRejectedRis' => $totalRejectedRis,
            'recentRis' => $recentRis,
            'pendingValue' => $pendingValue,
            'latestSubmitted' => $latestSubmitted,
            'attentionFocus' => $attentionFocus,
            'queueDate' => $queueDate,
            'recentDate' => $recentDate,
        ]);
    }


    public function approvalHistory(Request $request)
    {
        $period = (string) $request->query('period', 'all');
        if (! in_array($period, ['all', 'today', 'week', 'month', 'year'], true)) {
            $period = 'all';
        }

        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'approved', 'rejected'], true)) {
            $status = 'all';
        }

        $dateFilter = $this->normalizeFilterDate($request->query('date'));

        $latestPresidentLog = DB::table('approval_logs_table')
            ->select('approval_log_reference_id', DB::raw('MAX(approval_log_id) as latest_log_id'))
            ->where('approval_log_reference_type', 'RIS')
            ->where('approval_log_level', 'President')
            ->whereIn('approval_log_approval_status', ['Approved', 'Rejected'])
            ->groupBy('approval_log_reference_id');

        $listQuery = DB::table('requisition_issue_slip_table as ris')
            ->joinSub($latestPresidentLog, 'latest_log', function ($join) {
                $join->on('latest_log.approval_log_reference_id', '=', 'ris.ris_id');
            })
            ->join('approval_logs_table as log', 'log.approval_log_id', '=', 'latest_log.latest_log_id')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select($this->withRisRequestNoSelect([
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_purpose_description',
                'log.approval_log_approval_status as decision',
                'log.approval_log_approval_remarks as remarks',
                'log.approval_log_approved_at as decided_at',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount'),
            ]))
            ->whereIn('ris.ris_status', [
                'Approved',
                'Approved by the President',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
            ])
            ->groupBy($this->withRisRequestNoGroup([
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_status',
                'ris.ris_approved_by_date',
                'ris.ris_purpose_description',
                'log.approval_log_approval_status',
                'log.approval_log_approval_remarks',
                'log.approval_log_approved_at',
            ]));

        if ($dateFilter !== null) {
            $this->applyDecisionDateFilter($listQuery, $dateFilter);
        } else {
            $this->applyDecisionPeriodFilter($listQuery, $period);
        }
        $this->applyDecisionStatusFilter($listQuery, $status);

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $listQuery->where(function ($q) use ($search) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                  ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%");
                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
                    $q->orWhere('ris.ris_request_no', 'LIKE', "%{$search}%");
                }
            });
        }

        $approvalHistoryRecords = $listQuery
            ->orderByDesc(DB::raw('COALESCE(log.approval_log_approved_at, ris.ris_approved_by_date)'))
            ->orderByDesc('ris.ris_id')
            ->paginate(10)
            ->withQueryString();

        $summary = $this->decisionReportsSummary($period, $dateFilter);

        if ($request->ajax()) {
            $tableHtml = view('president.approvals._history-table', compact('approvalHistoryRecords'))->render();

            return response()->json([
                'table_html' => $tableHtml,
                'total' => $approvalHistoryRecords->total(),
                'from' => $approvalHistoryRecords->firstItem(),
                'to' => $approvalHistoryRecords->lastItem(),
                'current_page' => $approvalHistoryRecords->currentPage(),
                'last_page' => $approvalHistoryRecords->lastPage(),
                'summary' => $summary,
                'period' => $period,
                'status' => $status,
                'date' => $dateFilter,
            ]);
        }

        return view('president.approvals.approval-history', [
            'approvalHistoryRecords' => $approvalHistoryRecords,
            'period' => $period,
            'status' => $status,
            'dateFilter' => $dateFilter,
            'summary' => $summary,
        ]);
    }

    public function digitalSignature(): View
    {
        return view('president.approvals.digitally-sign');
    }

    // =====================================================
    // ADMIN DIRECT APPROVALS (read-only record for President)
    // =====================================================

    public function directApprovals(Request $request)
    {
        $hasReason = Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_reason');
        $hasProofPath = Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_proof_path');
        $hasProofName = Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_proof_name');
        $hasApprovedAt = Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_at');
        $hasApprovedBy = Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_by');

        $select = [
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_purpose_description',
            'ris.ris_status',
            'ris.ris_approved_by_date',
            DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount'),
        ];

        $groupBy = [
            'ris.ris_id',
            'ris.ris_form_number',
            'ris.ris_purpose_description',
            'ris.ris_status',
            'ris.ris_approved_by_date',
        ];
        $this->appendRisRequestNoSelect($select, $groupBy);

        if ($hasReason) {
            $select[] = 'ris.ris_direct_approval_reason';
            $groupBy[] = 'ris.ris_direct_approval_reason';
        } else {
            $select[] = DB::raw('NULL as ris_direct_approval_reason');
        }

        if ($hasProofPath) {
            $select[] = 'ris.ris_direct_approval_proof_path';
            $groupBy[] = 'ris.ris_direct_approval_proof_path';
        } else {
            $select[] = DB::raw('NULL as ris_direct_approval_proof_path');
        }

        if ($hasProofName) {
            $select[] = 'ris.ris_direct_approval_proof_name';
            $groupBy[] = 'ris.ris_direct_approval_proof_name';
        } else {
            $select[] = DB::raw('NULL as ris_direct_approval_proof_name');
        }

        if ($hasApprovedAt) {
            $select[] = 'ris.ris_direct_approval_at';
            $groupBy[] = 'ris.ris_direct_approval_at';
        } else {
            $select[] = DB::raw('NULL as ris_direct_approval_at');
        }

        if ($hasApprovedBy) {
            $select[] = 'ris.ris_direct_approval_by';
            $select[] = 'admin.user_full_name as approved_by_name';
            $groupBy[] = 'ris.ris_direct_approval_by';
            $groupBy[] = 'admin.user_full_name';
        } else {
            $select[] = DB::raw('NULL as ris_direct_approval_by');
            $select[] = DB::raw('NULL as approved_by_name');
        }

        $query = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id');

        if ($hasApprovedBy) {
            $query->leftJoin('users_table as admin', 'ris.ris_direct_approval_by', '=', 'admin.user_id');
        }

        $query->select($select)
            ->where('ris.ris_status', RisWorkflow::DIRECTLY_APPROVED)
            ->groupBy($groupBy);

        $recordRisId = max(0, (int) $request->query('ris', 0));
        $dateFilter = $this->normalizeFilterDate($request->query('date'));

        if ($recordRisId > 0) {
            $query->where('ris.ris_id', $recordRisId);
        } elseif ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $hasReason, $hasApprovedBy) {
                $q->where('ris.ris_id', 'LIKE', "%{$search}%")
                    ->orWhere('ris.ris_form_number', 'LIKE', "%{$search}%")
                    ->orWhere('ris.ris_purpose_description', 'LIKE', "%{$search}%");

                if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
                    $q->orWhere('ris.ris_request_no', 'LIKE', "%{$search}%");
                }
                if ($hasReason) {
                    $q->orWhere('ris.ris_direct_approval_reason', 'LIKE', "%{$search}%");
                }
                if ($hasApprovedBy) {
                    $q->orWhere('admin.user_full_name', 'LIKE', "%{$search}%");
                }
            });
        }

        $orderExpr = $hasApprovedAt
            ? 'COALESCE(ris.ris_direct_approval_at, ris.ris_approved_by_date, ris.ris_created_at)'
            : 'COALESCE(ris.ris_approved_by_date, ris.ris_created_at)';

        if ($dateFilter !== null) {
            $query->whereRaw('DATE('.$orderExpr.') = ?', [$dateFilter]);
        }

        $records = $query
            ->orderByDesc(DB::raw($orderExpr))
            ->orderByDesc('ris.ris_id')
            ->paginate(10)
            ->withQueryString();

        $this->attachDirectApprovalSupportingDocuments($records);

        $latestStops = \App\Support\RequestExplorer::latestStopsForRis(
            $records->getCollection()->pluck('ris_id')->all()
        );
        $records->getCollection()->each(function ($row) use ($latestStops) {
            $row->latest_stop = $latestStops[(int) $row->ris_id] ?? [
                'key' => 'ris',
                'code' => 'RIS',
                'title' => 'Requisition Slip',
            ];
        });

        if ($request->ajax()) {
            $tableHtml = view('president.direct-approvals._table', [
                'records' => $records,
            ])->render();

            return response()->json([
                'table_html' => $tableHtml,
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ]);
        }

        return view('president.direct-approvals.index', [
            'records' => $records,
            'dateFilter' => $dateFilter,
            'attentionFocus' => $recordRisId > 0
                ? \App\Support\RecordFocus::ris($recordRisId, route('president.direct-approvals'))
                : null,
        ]);
    }

    public function downloadDirectApprovalProof(Request $request, $ris)
    {
        abort_unless(
            Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_proof_path'),
            404
        );

        $record = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->where('ris_status', RisWorkflow::DIRECTLY_APPROVED)
            ->first();

        abort_if(!$record, 404);

        $path = trim((string) ($record->ris_direct_approval_proof_path ?? ''));
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        $downloadName = trim((string) ($record->ris_direct_approval_proof_name ?? ''))
            ?: basename($path);

        if ($request->boolean('download')) {
            return Storage::disk('public')->download($path, $downloadName);
        }

        return app(RisController::class)->previewStoredFile(
            $path,
            $downloadName,
            route('president.direct-approvals.proof', ['ris' => $ris, 'download' => 1])
        );
    }

    private function attachDirectApprovalSupportingDocuments($records): void
    {
        if (!Schema::hasTable('ris_attachments_table')) {
            foreach ($records as $row) {
                $row->supportingDocuments = [];
            }
            return;
        }

        $ids = collect($records->items())->pluck('ris_id')->filter()->unique()->values();
        $files = $ids->isEmpty()
            ? collect()
            : DB::table('ris_attachments_table')
                ->whereIn('ris_id', $ids)
                ->orderBy('ris_attachment_original_name')
                ->get()
                ->groupBy('ris_id');

        foreach ($records as $row) {
            $row->supportingDocuments = ($files->get($row->ris_id, collect()))
                ->map(fn ($file) => [
                    'id' => $file->ris_attachment_id,
                    'name' => $file->ris_attachment_original_name,
                    'url' => route('president.ris.attachments.preview', $file->ris_attachment_id),
                ])
                ->values()
                ->all();
        }
    }

    // =====================================================
    // APPROVAL DECISIONS (POST)
    // =====================================================

    public function decideRis(Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        $fail = function (string $message, int $code = 422) use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], $code);
            }
            return back()->with('error', $message);
        };

        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return $fail('Invalid RIS decision payload.');
        }

        $target = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->first();

        if (!$target) {
            return $fail('RIS not found.', 404);
        }

        if (!$this->risIsAwaitingPresident($target)) {
            return $fail('Only RIS records forwarded by Administrator can be decided by President.');
        }

        if ($decision === 'Rejected' && trim((string) $remarks) === '') {
            return $fail('Please provide a rejection reason.');
        }

        $rejectReturnTo = [];
        if ($decision === 'Rejected') {
            $rejectReturnTo = array_values(array_intersect(
                (array) $request->input('return_to', []),
                ['school_admin', 'purchaser']
            ));
            if ($rejectReturnTo === []) {
                $rejectReturnTo = ['school_admin'];
            }

            $purchaserId = RisWorkflow::ownerPurchaserId($target);
            if (in_array('purchaser', $rejectReturnTo, true) && $purchaserId <= 0) {
                return $fail('This RIS has no purchaser to return it to.');
            }
        }

        $fastToPurchaserOnly = $decision === 'Rejected'
            && in_array('purchaser', $rejectReturnTo, true)
            && ! in_array('school_admin', $rejectReturnTo, true);

        $updateValues = [
            'ris_status' => $decision === 'Approved'
                ? 'Approved by the President'
                : ($fastToPurchaserOnly ? 'Minor Revision' : 'Rejected by the President'),
        ];

        if ($decision === 'Rejected' && Schema::hasColumn('requisition_issue_slip_table', 'ris_rejection_reason')) {
            $updateValues['ris_rejection_reason'] = trim((string) $remarks);
        }
        if ($fastToPurchaserOnly) {
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_revision_type')) {
                $updateValues['ris_revision_type'] = 'Minor Revision';
            }
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_approved_by_signature')) {
                $updateValues['ris_approved_by_signature'] = null;
            }
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_approved_by_date')) {
                $updateValues['ris_approved_by_date'] = null;
            }
        }

        if ($decision === 'Approved') {
            $presidentName = trim((string) $request->input('ris_approved_by', ''));
            if ($presidentName === '') {
                $presidentName = Auth::user()->user_full_name ?? 'President';
            }

            $signature = trim((string) $request->input('signature_data', ''));
            if ($signature === '' || !str_starts_with($signature, 'data:image/')) {
                return $fail('Please add a signature before approving (draw, upload, or pick a saved one).');
            }
            if (strlen($signature) > 2000000) {
                return $fail('Signature image is too large. Please use a smaller drawing or upload.');
            }

            $updateValues['ris_approved_by_signature'] = $signature;
            $approvedDate = $this->parseFlexibleDate($request->input('ris_approved_by_date'));
            $updateValues['ris_approved_by_date'] = $approvedDate ?: now()->toDateString();

            // Always passes to School Administrator so they can sign Issued by.
        }

        try {
            DB::transaction(function () use ($targetId, $updateValues, $request, $decision) {
                if ($decision === 'Approved') {
                    $issuedError = RisWorkflow::assertPurchaserIssuedComplete((int) $targetId);
                    if ($issuedError) {
                        throw new \RuntimeException($issuedError);
                    }
                }

                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $targetId)
                    ->update($updateValues);
            });
        } catch (\RuntimeException $e) {
            return $fail($e->getMessage());
        } catch (\Throwable $e) {
            return $fail(
                'Could not save the President decision. Run database migrations so presidential RIS statuses are valid.'
            );
        }

        // approval logs (optional but useful)
        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $targetId,
                'approval_log_level' => 'President',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => $decision,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures to not break testing
        }

        $passedTo = [];
        $adminNotified = false;

        // Approve always goes to School Administrator for Issued-by co-sign.
        if ($decision === 'Approved') {
            $form = RisWorkflow::formNumber($target);

                try {
                    DB::table('approval_logs_table')->insert([
                        'approval_log_reference_type' => 'RIS',
                        'approval_log_reference_id' => (int) $targetId,
                        'approval_log_level' => 'President',
                        'approval_log_approved_by' => Auth::id(),
                        'approval_log_approval_status' => 'Approved',
                        'approval_log_approval_remarks' => 'Notified Administrator for co-sign',
                        'approval_log_approved_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                }

                WorkflowNotifier::toRole(
                    WorkflowNotifier::ROLE_SCHOOL_ADMIN,
                    'President approved an RIS',
                    $form . ' was approved by the President and is ready for School Administrator co-sign.',
                    'ris_president_approved',
                    'RIS',
                    (int) $targetId,
                    '/school-admin/digital-signatures/sign-ris'
                );
                $passedTo[] = 'School Administrator';
                $adminNotified = true;
        }

        if ($decision === 'Rejected') {
            $form = RisWorkflow::formNumber($target);
            $rejectNote = trim((string) $remarks);
            $purchaserId = RisWorkflow::ownerPurchaserId($target);
            $rejectReturnTo = array_values(array_intersect(
                (array) $request->input('return_to', []),
                ['school_admin', 'purchaser']
            ));
            if ($rejectReturnTo === []) {
                $rejectReturnTo = ['school_admin'];
            }

            if (in_array('school_admin', $rejectReturnTo, true)) {
            WorkflowNotifier::toRole(
                WorkflowNotifier::ROLE_SCHOOL_ADMIN,
                'President rejected an RIS',
                    $form.($rejectNote !== '' ? (': '.$rejectNote) : ''),
                'ris_president_rejected',
                'RIS',
                (int) $targetId,
                '/school-admin/digital-signatures/sign-ris'
            );
                $passedTo[] = 'School Administrator';
                $adminNotified = true;
            }

            if (in_array('purchaser', $rejectReturnTo, true) && $purchaserId > 0) {
                $fastOnly = ! in_array('school_admin', $rejectReturnTo, true);
                WorkflowNotifier::toUser(
                    $purchaserId,
                    WorkflowNotifier::ROLE_PURCHASER,
                    $fastOnly
                        ? 'President rejected your RIS — revise and resubmit'
                        : 'President rejected your RIS',
                    $form.($rejectNote !== '' ? (': '.$rejectNote) : '').($fastOnly
                        ? ' Returned for revision (fast route from the President).'
                        : ' School Administrator was also notified.'),
                    'ris_president_rejected',
                    'RIS',
                    (int) $targetId,
                    '/purchaser/ris'
                );
                $passedTo[] = WorkflowNotifier::recipientName($purchaserId, 'the Purchaser');
            }

            if ($fastToPurchaserOnly && Schema::hasTable('ris_revision_notes_table')) {
                try {
                    $note = [
                        'ris_id' => (int) $targetId,
                        'ris_revision_requested_by' => Auth::id(),
                        'ris_revision_type' => 'Minor Revision',
                        'ris_revision_note' => $rejectNote !== '' ? $rejectNote : 'Rejected by the President.',
                        'ris_revision_created_at' => now(),
                    ];
                    if (Schema::hasColumn('ris_revision_notes_table', 'ris_revision_updated_at')) {
                        $note['ris_revision_updated_at'] = now();
                    }
                    DB::table('ris_revision_notes_table')->insert($note);
                } catch (\Throwable $e) {
                    // ignore revision-note failures
                }
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'decision' => $decision,
                'ris_id' => (int) $targetId,
                'approved_by_date' => $decision === 'Approved'
                    ? ($updateValues['ris_approved_by_date'] ?? null)
                    : null,
                'admin_notified' => $adminNotified,
                'message' => $decision === 'Approved'
                    ? ('RIS approved and passed to ' . implode(' and ', $passedTo) . '.')
                    : ('RIS rejected'.($passedTo !== [] ? (' and sent to '.implode(' and ', $passedTo)) : '').'.'),
            ]);
        }

        return redirect('/president/approvals')->with('success', 'RIS decision saved successfully.');
    }

    /**
     * Soft return for revision (not hard reject).
     * Always Returned by the President: School Administrator passes it to the Purchaser (no Issued by on this path).
     */
    public function returnRisForRevision(Request $request)
    {
        $targetId = (int) $request->input('target_id');
        $remarks = trim((string) $request->input('remarks', ''));

        $fail = function (string $message, int $code = 422) use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], $code);
            }

            return back()->with('error', $message);
        };

        if ($targetId < 1) {
            return $fail('Invalid RIS.');
        }
        if ($remarks === '') {
            return $fail('Please provide revision remarks.');
        }
        if ($imageError = RisRevisionImages::validationError($request)) {
            return $fail($imageError);
        }

        $target = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $targetId)
            ->first();

        if (! $target) {
            return $fail('RIS not found.', 404);
        }

        if (! $this->risIsAwaitingPresident($target)) {
            return $fail('Only RIS records forwarded by Administrator can be returned for revision.');
        }

        $newStatus = RisWorkflow::RETURNED_BY_PRESIDENT;

        $images = [];
        $committed = false;

        try {
            if (RisRevisionImages::supported()) {
                $images = RisRevisionImages::store(
                    RisRevisionImages::uploadedFiles($request),
                    $targetId
                );
            }

            DB::transaction(function () use ($targetId, $remarks, $newStatus, $images) {
                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $targetId)
                    ->update([
                        'ris_status' => $newStatus,
                        'ris_rejection_reason' => $remarks,
                        'ris_approved_by_signature' => null,
                        'ris_approved_by_date' => null,
                        'ris_issued_by_signature' => null,
                        'ris_issued_by_date' => null,
                    ]);

                // Always store a revision note (incl. proof images) — even for SA-only returns —
                // so School Administrator can relay images to the Purchaser later.
                if (Schema::hasTable('ris_revision_notes_table')) {
                    $note = [
                        'ris_id' => (int) $targetId,
                        'ris_revision_requested_by' => Auth::id(),
                        'ris_revision_type' => RisWorkflow::PRESIDENT_REVISION,
                        'ris_revision_note' => $remarks,
                        'ris_revision_created_at' => now(),
                    ];
                    if ($images !== [] && Schema::hasColumn('ris_revision_notes_table', 'ris_revision_images')) {
                        $note['ris_revision_images'] = RisRevisionImages::encode($images);
                    }
                    if (Schema::hasColumn('ris_revision_notes_table', 'ris_revision_updated_at')) {
                        $note['ris_revision_updated_at'] = now();
                    }
                    DB::table('ris_revision_notes_table')->insert($note);
                }

                try {
                    DB::table('approval_logs_table')->insert([
                        'approval_log_reference_type' => 'RIS',
                        'approval_log_reference_id' => (int) $targetId,
                        'approval_log_level' => 'President',
                        'approval_log_approved_by' => Auth::id(),
                        'approval_log_approval_status' => $newStatus,
                        'approval_log_approval_remarks' => $remarks,
                        'approval_log_approved_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    // ignore logging failures
                }
            });
            $committed = true;
        } catch (\Throwable $e) {
            if ($images !== []) {
                RisRevisionImages::delete($images);
            }

            return $fail('Could not return this RIS for revision. Check that the new statuses are migrated.');
        } finally {
            if (! $committed && $images !== []) {
                RisRevisionImages::delete($images);
            }
        }

        $form = RisWorkflow::formNumber($target);

        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_SCHOOL_ADMIN,
            'President returned an RIS for revision',
            $form.': '.$remarks.' Pass this RIS back to the Purchaser for revision.',
            'ris_president_revision',
            'RIS',
            $targetId,
            '/school-admin/procurement-review/ris?filter=revision'
        );

        $message = 'RIS returned for revision to the School Administrator.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'decision' => 'Revision',
                'ris_id' => $targetId,
                'message' => $message,
            ]);
        }

        return redirect('/president/approvals')->with('success', $message);
    }

    public function decideProcurement(Request $request)
    {
        $targetId = $request->input('target_id');
        $decision = $request->input('decision');
        $remarks = $request->input('remarks');

        if (empty($targetId) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            return back()->with('error', 'Invalid procurement decision payload.');
        }

        $target = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $targetId)
            ->first();

        if (!$target) {
            return back()->with('error', 'Procurement request not found.');
        }

        DB::table('procurement_requests_table')
            ->where('procurement_request_id', $targetId)
            ->update([
                'procurement_request_status' => $decision,
            ]);

        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'ProcurementRequest',
                'approval_log_reference_id' => (int) $targetId,
                'approval_log_level' => 'President',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => $decision,
                'approval_log_approval_remarks' => $remarks,
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        return redirect('/president/approvals')->with('success', 'Procurement decision saved successfully.');
    }

    // =====================================================
    // REPORTS
    // =====================================================


    public function approvedReports(Request $request)
    {
        return redirect()->route('president.approvals.history', array_filter([
            'period' => $request->query('period', 'month'),
            'status' => $request->query('filter') === 'rejected' ? 'rejected'
                : ($request->query('filter') === 'approved' ? 'approved' : null),
            'search' => $request->query('search'),
        ]));
    }

    /**
     * Apply the Decision History status bucket used by both the table and KPI cards.
     */
    private function applyDecisionHistoryFilter($query, string $filter, string $prefix = 'ris.')
    {
        if ($filter === 'approved') {
            return $this->scopePresidentApproved($query, $prefix);
        }

        if ($filter === 'rejected') {
            return $this->scopeDecisionHistoryRejected($query, $prefix);
        }

        if ($filter === 'pending') {
            return $this->scopeAwaitingPresident($query, $prefix);
        }

        // "all" = union of the three buckets (matches Total Decisions card)
        return $query->where(function ($q) use ($prefix) {
            $q->where(function ($approved) use ($prefix) {
                $this->scopePresidentApproved($approved, $prefix);
            })->orWhere(function ($rejected) use ($prefix) {
                $this->scopeDecisionHistoryRejected($rejected, $prefix);
            })->orWhere(function ($pending) use ($prefix) {
                $this->scopeAwaitingPresident($pending, $prefix);
            });
        });
    }

    private function scopeDecisionHistoryRejected($query, string $prefix = '')
    {
        $status = $prefix . 'ris_status';

        return $query->where(function ($q) use ($status) {
            $q->whereIn($status, RisWorkflow::presidentRejectedStatuses())
                ->orWhere($status, 'Rejected');
        });
    }

    private function countDecisionHistoryBucket(string $filter): int
    {
        $query = DB::table('requisition_issue_slip_table as ris');
        $this->applyDecisionHistoryFilter($query, $filter, 'ris.');

        return (int) $query->count();
    }

    public function monthlySummary(Request $request)
    {
        return redirect()->route('president.approvals.history', [
            'period' => $request->filled('month') ? 'month' : 'year',
        ]);
    }

    private function applyDecisionPeriodFilter($query, string $period): void
    {
        $expr = 'COALESCE(log.approval_log_approved_at, ris.ris_approved_by_date)';

        if ($period === 'today') {
            $query->whereRaw('DATE('.$expr.') = ?', [now()->toDateString()]);
            return;
        }

        if ($period === 'week') {
            $query->whereRaw($expr.' BETWEEN ? AND ?', [
                now()->startOfWeek()->toDateTimeString(),
                now()->endOfWeek()->toDateTimeString(),
            ]);
            return;
        }

        if ($period === 'month') {
            $query->whereRaw('YEAR('.$expr.') = ? AND MONTH('.$expr.') = ?', [
                (int) now()->year,
                (int) now()->month,
            ]);
            return;
        }

        if ($period === 'year') {
            $query->whereRaw('YEAR('.$expr.') = ?', [(int) now()->year]);
        }
    }

    private function applyDecisionDateFilter($query, string $date): void
    {
        $expr = 'COALESCE(log.approval_log_approved_at, ris.ris_approved_by_date)';
        $query->whereRaw('DATE('.$expr.') = ?', [$date]);
    }

    private function applyDecisionStatusFilter($query, string $status): void
    {
        if ($status === 'approved') {
            $query->where('log.approval_log_approval_status', 'Approved');
            return;
        }

        if ($status === 'rejected') {
            $query->where('log.approval_log_approval_status', 'Rejected');
        }
    }

    /**
     * @return array{approved_count:int, rejected_count:int, approved_amount:float, rejected_amount:float, period_label:string}
     */
    private function decisionReportsSummary(string $period, ?string $dateFilter = null): array
    {
        $latestPresidentLog = DB::table('approval_logs_table')
            ->select('approval_log_reference_id', DB::raw('MAX(approval_log_id) as latest_log_id'))
            ->where('approval_log_reference_type', 'RIS')
            ->where('approval_log_level', 'President')
            ->whereIn('approval_log_approval_status', ['Approved', 'Rejected'])
            ->groupBy('approval_log_reference_id');

        $query = DB::table('requisition_issue_slip_table as ris')
            ->joinSub($latestPresidentLog, 'latest_log', function ($join) {
                $join->on('latest_log.approval_log_reference_id', '=', 'ris.ris_id');
            })
            ->join('approval_logs_table as log', 'log.approval_log_id', '=', 'latest_log.latest_log_id')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->whereIn('ris.ris_status', [
                'Approved',
                'Approved by the President',
                'Rejected',
                'Rejected by President',
                'Rejected by the President',
            ]);

        if ($dateFilter !== null) {
            $this->applyDecisionDateFilter($query, $dateFilter);
        } else {
            $this->applyDecisionPeriodFilter($query, $period);
        }

        $rows = $query
            ->select(
                'log.approval_log_approval_status as decision',
                'ris.ris_id',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->groupBy('ris.ris_id', 'log.approval_log_approval_status')
            ->get();

        $approved = $rows->filter(fn ($row) => strcasecmp((string) $row->decision, 'Approved') === 0);
        $rejected = $rows->filter(fn ($row) => strcasecmp((string) $row->decision, 'Rejected') === 0);

        if ($dateFilter !== null) {
            try {
                $periodLabel = Carbon::createFromFormat('Y-m-d', $dateFilter)->format('F j, Y');
            } catch (\Throwable) {
                $periodLabel = $dateFilter;
            }
        } else {
            $periodLabel = match ($period) {
                'today' => 'Today',
                'week' => 'This week',
                'month' => 'This month',
                'year' => 'This year',
                default => 'All time',
            };
        }

        return [
            'approved_count' => $approved->count(),
            'rejected_count' => $rejected->count(),
            'approved_amount' => (float) $approved->sum('total_amount'),
            'rejected_amount' => (float) $rejected->sum('total_amount'),
            'period_label' => $periodLabel,
        ];
    }

    // =====================================================
    // NOTIFICATIONS
    // =====================================================

    public function notifications(Request $request): View
    {
        $userId = Auth::id();
        $category = $request->get('category', 'all');
        $allowedCategories = ['all', 'Approvals', 'Rejections', 'workflow'];

        if (!in_array($category, $allowedCategories, true)) {
            $category = 'all';
        }

        $notifications = collect();
        $unreadCount = 0;

        if ($userId && Schema::hasTable('notifications_table')) {
            $query = DB::table('notifications_table');

            if (Schema::hasTable('notification_reads_table')) {
                $query->leftJoin('notification_reads_table', function ($join) use ($userId) {
                    $join->on(
                        'notifications_table.notification_id',
                        '=',
                        'notification_reads_table.notification_id'
                    )->where('notification_reads_table.user_id', '=', $userId);
                })
                    ->select(
                        'notifications_table.notification_id',
                        'notifications_table.notification_title',
                        'notifications_table.notification_message',
                        'notifications_table.notification_type',
                        'notifications_table.notification_category',
                        'notifications_table.notification_created_at'
                    )
                    ->selectRaw(
                        'CASE
                            WHEN notification_reads_table.notification_read_id IS NULL
                            THEN 0
                            ELSE 1
                        END AS is_read'
                    );
            } else {
                $query->select(
                    'notifications_table.notification_id',
                    'notifications_table.notification_title',
                    'notifications_table.notification_message',
                    'notifications_table.notification_type',
                    'notifications_table.notification_category',
                    'notifications_table.notification_created_at'
                )->selectRaw('0 AS is_read');
            }

            $query = $this->applyPresidentNotificationAccess($query, $userId);

            if ($category !== 'all') {
                $query->where('notifications_table.notification_category', $category);
            }

            $notifications = $query
                ->orderByDesc('notifications_table.notification_created_at')
                ->limit(40)
                ->get();

            $unreadQuery = $this->applyPresidentNotificationAccess(
                DB::table('notifications_table'),
                $userId
            );

            if (Schema::hasTable('notification_reads_table')) {
                $unreadCount = $unreadQuery
                    ->whereNotExists(function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('notification_reads_table')
                            ->whereColumn(
                                'notification_reads_table.notification_id',
                                'notifications_table.notification_id'
                            )
                            ->where('notification_reads_table.user_id', $userId);
                    })
                    ->count();
            } else {
                $unreadCount = $unreadQuery->count();
            }
        }

        return view('president.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'activeCategory' => $category,
        ]);
    }

    public function openNotification($id)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(401);
        }

        $notification = $this->applyPresidentNotificationAccess(
            DB::table('notifications_table')->where('notifications_table.notification_id', $id),
            $userId
        )->first();

        if (!$notification) {
            abort(404);
        }

        if (Schema::hasTable('notification_reads_table')) {
            DB::table('notification_reads_table')->insertOrIgnore([
                'notification_id' => $notification->notification_id,
                'user_id' => $userId,
                'notification_read_at' => now(),
            ]);
        }

        $destination = \App\Support\NotificationLinks::resolve($notification);
        if (!$destination || !str_starts_with($destination, '/president/')) {
            return redirect('/president/notifications');
        }

        return redirect($destination);
    }

    public function markAllNotificationsAsRead()
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(401);
        }

        if (!Schema::hasTable('notifications_table') || !Schema::hasTable('notification_reads_table')) {
            return back()->with('success', 'All notifications are already read.');
        }

        $notificationIds = $this->applyPresidentNotificationAccess(
            DB::table('notifications_table'),
            $userId
        )
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('notification_reads_table')
                    ->whereColumn(
                        'notification_reads_table.notification_id',
                        'notifications_table.notification_id'
                    )
                    ->where('notification_reads_table.user_id', $userId);
            })
            ->pluck('notifications_table.notification_id');

        if ($notificationIds->isEmpty()) {
            return back()->with('success', 'All notifications are already read.');
        }

        $now = now();
        $readRows = $notificationIds->map(fn ($notificationId) => [
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'notification_read_at' => $now,
        ])->all();

        DB::table('notification_reads_table')->insertOrIgnore($readRows);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function rejectionHistory(): View
    {
        return view('president.notifications.rejection-history');
    }

    private function applyPresidentNotificationAccess($query, $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('notifications_table.notification_user_id', $userId)
                ->orWhere(function ($query) {
                    $query->whereNull('notifications_table.notification_user_id')
                        ->where('notifications_table.notification_target_role', 'President');
                });
        });
    }

    // =====================================================
    // RIS VIEWER (for preview in modal)
    // =====================================================
    public function viewRis($ris, Request $request)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        if (!$ris) {
            abort(404, 'RIS not found');
        }

        $risItems = $this->risItemsWithUom($ris->ris_id)->pad(8, null);

        $presidentName = Auth::user()->user_full_name ?? 'President';
        $stamp = (string) ($ris->ris_updated_at ?? $ris->ris_status ?? $ris->ris_id);
        $cacheKey = ($request->boolean('preview') ? 'president-ris-view:' : 'president-ris-print:')
            .$ris->ris_id.':'.(int) Auth::id().':'.$stamp.':v7';
        $html = \App\Support\CachedFormPreview::html($cacheKey, function () use ($request, $ris, $risItems, $presidentName) {
            if ($request->boolean('preview')) {
                return view('president.ris.viewer', [
                    'ris' => $ris,
                    'risItems' => $risItems,
                    'presidentName' => $presidentName,
                    'isScreenPreview' => true,
                ])->render();
            }

            return view('admin.ris.print', [
                'ris' => $ris,
                'risItems' => $risItems,
                'presidentName' => $presidentName,
            ])->render();
        });

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, max-age=300');
    }

    public function risDetails($ris)
    {
        $record = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        abort_if(!$record, 404);

        $items = $this->risItemsWithUom($record->ris_id);

        $attachments = collect();
        if (Schema::hasTable('ris_attachments_table')) {
            $attachments = DB::table('ris_attachments_table')
                ->where('ris_id', $record->ris_id)
                ->orderBy('ris_attachment_original_name')
                ->get();
        }

        $isPresidentApproved = RisWorkflow::isPresidentApproved($record);
        $adminNotified = $isPresidentApproved && $this->presidentHasNotifiedAdmin((int) $record->ris_id);

        $forwardDetails = Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_details')
            ? trim((string) ($record->ris_forward_details ?? ''))
            : '';
        $forwardAttachmentRows = RisForwardAttachments::listed($record);
        $forwardAttachments = array_map(function (array $file) use ($record) {
            $url = $file['key'] === 'legacy'
                ? route('president.ris.forward-attachment', $record->ris_id)
                : route('president.ris.forward-attachment.file', ['ris' => $record->ris_id, 'attachment' => $file['key']]);

            return [
                'name' => $file['name'],
                'url' => $url,
            ];
        }, $forwardAttachmentRows);

        return response()->json([
            'ris_id' => $record->ris_id,
            'form_number' => RisWorkflow::formNumber($record),
            'purpose' => $record->ris_purpose_description,
            'requester_name' => RisWorkflow::requesterName($record, ''),
            'status' => $record->ris_status,
            'created_at' => $record->ris_created_at,
            'approved_by_date' => $record->ris_approved_by_date,
            'requested_by_date' => $record->ris_requested_by_date,
            'total_amount' => $items->sum(fn ($item) => (float) $item->ris_total_amount),
            'attachments' => $attachments->map(fn ($file) => [
                'id' => $file->ris_attachment_id,
                'name' => $file->ris_attachment_original_name,
                'size' => $file->ris_attachment_size ?? null,
                'url' => route('president.ris.attachments.preview', $file->ris_attachment_id),
            ]),
            'forward_details' => $forwardDetails !== '' ? $forwardDetails : null,
            'forward_attachments' => $forwardAttachments,
            'forward_attachment' => $forwardAttachments[0] ?? null,
            'has_president_signature' => trim((string) ($record->ris_approved_by_signature ?? '')) !== '',
            'is_president_approved' => $isPresidentApproved,
            'admin_notified' => $adminNotified,
            'awaiting_notify' => $isPresidentApproved && !$adminNotified
                && trim((string) ($record->ris_issued_by_signature ?? '')) === '',
        ]);
    }

    public function downloadForwardAttachment($ris)
    {
        abort_unless(
            Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path'),
            404
        );

        $record = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        abort_if(!$record, 404);

        $path = trim((string) ($record->ris_forward_attachment_path ?? ''));
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        $downloadName = trim((string) ($record->ris_forward_attachment_name ?? ''))
            ?: basename($path);

        return Storage::disk('public')->response($path, $downloadName);
    }

    public function downloadForwardAttachmentFile($ris, $attachment)
    {
        $file = RisForwardAttachments::find((int) $ris, (string) $attachment);
        abort_if(! $file || ! Storage::disk('public')->exists($file['path']), 404);

        return Storage::disk('public')->response($file['path'], $file['name']);
    }

    public function sendToAdmin($ris)
    {
        $record = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $ris)
            ->first();

        abort_if(!$record, 404);

        if (!RisWorkflow::isPresidentApproved($record)) {
            return response()->json([
                'ok' => false,
                'message' => 'Only an approved RIS can notify Administrator.',
            ], 422);
        }

        if ($this->presidentHasNotifiedAdmin((int) $record->ris_id)) {
            return response()->json([
                'ok' => true,
                'ris_id' => (int) $record->ris_id,
                'already_notified' => true,
                'message' => 'Administrator was already notified for this RIS.',
            ]);
        }

        try {
            DB::table('approval_logs_table')->insert([
                'approval_log_reference_type' => 'RIS',
                'approval_log_reference_id' => (int) $record->ris_id,
                'approval_log_level' => 'President',
                'approval_log_approved_by' => Auth::id(),
                'approval_log_approval_status' => 'Approved',
                'approval_log_approval_remarks' => 'Notified Administrator for co-sign',
                'approval_log_approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }

        $form = RisWorkflow::formNumber($record);
        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_SCHOOL_ADMIN,
            'President approved an RIS',
            $form . ' was approved by the President and is ready for School Administrator co-sign.',
            'ris_president_approved',
            'RIS',
            (int) $record->ris_id,
            '/school-admin/digital-signatures/sign-ris'
        );

        return response()->json([
            'ok' => true,
            'ris_id' => (int) $record->ris_id,
            'already_notified' => false,
            'message' => 'Administrator has been notified.',
        ]);
    }

    public function approveForm($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->first();

        abort_if(!$ris, 404);

        if (!$this->risIsAwaitingPresident($ris)) {
            abort(403, 'Only RIS records forwarded by Administrator can be signed by the President.');
        }

        $risItems = $this->risItemsWithUom($risId);
        $savedSignatures = UserSignatureLibrary::forUser((int) Auth::id());

        return view('president.approvals._approve-form', [
            'ris' => $ris,
            'risItems' => $risItems,
            'savedSignatures' => $savedSignatures,
        ]);
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

    // =====================================================
    // SEARCH HELPER: DATE SEARCH
    // =====================================================

    private function appendRisRequestNoSelect(array &$select, ?array &$groupBy = null): void
    {
        if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            return;
        }

        $select[] = 'ris.ris_request_no';
        if ($groupBy !== null) {
            $groupBy[] = 'ris.ris_request_no';
        }
    }

    /** @param  list<string|\Illuminate\Database\Query\Expression>  $select */
    private function withRisRequestNoSelect(array $select): array
    {
        $this->appendRisRequestNoSelect($select);

        return $select;
    }

    /** @param  list<string>  $groupBy */
    private function withRisRequestNoGroup(array $groupBy): array
    {
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            $groupBy[] = 'ris.ris_request_no';
        }

        return $groupBy;
    }

    private function normalizeFilterDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function addDateSearch($query, string $dateColumn, string $search): void
    {
        $timestamp = strtotime($search);
        $searchLower = strtolower($search);

        $query->orWhere(function ($dq) use ($dateColumn, $search, $timestamp, $searchLower) {
            // If strtotime succeeded, add exact date component matches
            if ($timestamp !== false) {
                $year = (int) date('Y', $timestamp);
                $monthNum = (int) date('m', $timestamp);
                $day = (int) date('d', $timestamp);

                $dq->whereDate($dateColumn, date('Y-m-d', $timestamp))
                   ->whereYear($dateColumn, $year)
                   ->whereMonth($dateColumn, $monthNum)
                   ->whereDay($dateColumn, $day);

                if (preg_match('/^\d{4}$/', $search)) {
                    $dq->orWhereYear($dateColumn, (int) $search);
                }

                if (preg_match('/^(0?[1-9]|[12]\d|3[01])$/', $search)) {
                    $dq->orWhereDay($dateColumn, (int) $search);
                }
            }

            // Always add month name matching (works even if strtotime failed)
            $monthNames = [
                'january', 'february', 'march', 'april', 'may', 'june',
                'july', 'august', 'september', 'october', 'november', 'december'
            ];
            $monthAbbreviations = [
                'jan', 'feb', 'mar', 'apr', 'may', 'jun',
                'jul', 'aug', 'sep', 'oct', 'nov', 'dec'
            ];

            foreach ($monthNames as $month) {
                if (str_contains($searchLower, $month)) {
                    $dq->orWhere($dateColumn, 'LIKE', "%{$month}%");
                    break;
                }
            }

            foreach ($monthAbbreviations as $abbr) {
                if (str_contains($searchLower, $abbr)) {
                    $dq->orWhere($dateColumn, 'LIKE', "%{$abbr}%");
                    break;
                }
            }
        });
    }

    private function presidentHasNotifiedAdmin(int $risId): bool
    {
        return isset($this->presidentNotifiedAdminIds([$risId])[$risId]);
    }

    /**
     * @param  list<int>  $risIds
     * @return array<int, true>
     */
    private function presidentNotifiedAdminIds(array $risIds): array
    {
        $risIds = array_values(array_unique(array_filter(array_map('intval', $risIds))));
        if ($risIds === [] || ! Schema::hasTable('approval_logs_table')) {
            return [];
        }

        try {
            return DB::table('approval_logs_table')
                ->where('approval_log_reference_type', 'RIS')
                ->whereIn('approval_log_reference_id', $risIds)
                ->where('approval_log_level', 'President')
                ->where(function ($q) {
                    $q->where('approval_log_approval_remarks', 'Notified Administrator for co-sign')
                        ->orWhere('approval_log_approval_remarks', 'Notified Admin for co-sign')
                        ->orWhere('approval_log_approval_remarks', 'Forwarded to Admin for co-sign');
                })
                ->pluck('approval_log_reference_id')
                ->mapWithKeys(fn ($id) => [(int) $id => true])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Tiny placeholder instead of base64 signature blobs for list/dashboard flags.
     */
    private function risSignaturePresenceSql(string $column): string
    {
        return "CASE WHEN TRIM(COALESCE({$column}, '')) <> '' THEN 'data:image/x' ELSE '' END";
    }

    private function risIsAwaitingPresident(object $ris): bool
    {
        return RisWorkflow::isAwaitingPresident($ris);
    }

    private function scopeAwaitingPresident($query, string $prefix = '')
    {
        $status = $prefix . 'ris_status';
        $sig = $prefix . 'ris_approved_by_signature';
        $approvedDate = $prefix . 'ris_approved_by_date';

        return $query->where(function ($q) use ($status, $sig, $approvedDate) {
            $q->where($status, 'Forwarded to President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    $legacy->where($status, 'Approved')
                        ->where(function ($empty) use ($sig) {
                            $empty->whereNull($sig)->orWhere($sig, '');
                        });
                })
                ->orWhere(function ($queuedPending) use ($status, $sig, $approvedDate) {
                    $queuedPending->where($status, 'Pending')
                        ->whereNotNull($approvedDate)
                        ->where(function ($empty) use ($sig) {
                            $empty->whereNull($sig)->orWhere($sig, '');
                        });
                });
        });
    }

    private function approvedBudgetSummary(Request $request): array
    {
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        $currentYear = (int) now()->year;
        $years = [$currentYear];

        try {
            $fromDb = DB::table('requisition_issue_slip_table')
                ->whereNotNull('ris_approved_by_date')
                ->selectRaw('DISTINCT YEAR(ris_approved_by_date) as y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y)
                ->filter(fn ($y) => $y > 2000)
                ->all();
            $years = array_values(array_unique(array_merge($fromDb, $years)));
        } catch (\Throwable $e) {
            // keep the current year
        }
        rsort($years, SORT_NUMERIC);

        $year = (int) $request->query('budget_year', $currentYear);
        if (! in_array($year, $years, true)) {
            $year = $currentYear;
        }
        $month = (int) $request->query('budget_month', 0);
        if ($month < 0 || $month > 12) {
            $month = 0;
        }

        $base = DB::table('requisition_issue_slip_table as ris')
            ->where(function ($q) {
                $this->scopePresidentApproved($q, 'ris.');
            })
            ->whereYear('ris.ris_approved_by_date', $year);
        if ($month >= 1) {
            $base->whereMonth('ris.ris_approved_by_date', $month);
        }

        $rows = (clone $base)
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->select(
                'ris.ris_id',
                'ris.ris_form_number',
                'ris.ris_approved_by_date',
                DB::raw('COALESCE(SUM(items.ris_total_amount), 0) as total_amount')
            )
            ->groupBy('ris.ris_id', 'ris.ris_form_number', 'ris.ris_approved_by_date')
            ->orderByDesc('ris.ris_approved_by_date')
            ->orderByDesc('ris.ris_id')
            ->get();

        $totals = DB::table('requisition_issue_slip_table as ris')
            ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
            ->where(function ($q) {
                $this->scopePresidentApproved($q, 'ris.');
            })
            ->whereYear('ris.ris_approved_by_date', $year)
            ->when($month >= 1, fn ($q) => $q->whereMonth('ris.ris_approved_by_date', $month))
            ->selectRaw('COUNT(DISTINCT ris.ris_id) as decisions, COALESCE(SUM(items.ris_total_amount), 0) as amount')
            ->first();

        return [
            'year' => $year,
            'month' => $month,
            'years' => $years,
            'months' => $monthNames,
            'amount' => (float) ($totals->amount ?? 0),
            'count' => (int) ($totals->decisions ?? 0),
            'rows' => $rows,
        ];
    }

    private function scopePresidentApproved($query, string $prefix = '')
    {
        $status = $prefix . 'ris_status';
        $sig = $prefix . 'ris_approved_by_signature';

        return $query->where(function ($q) use ($status, $sig) {
            $q->where($status, 'Approved by the President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    // Legacy Approved counts only with a digital signature image.
                    $legacy->where($status, 'Approved')
                        ->whereNotNull($sig)
                        ->where($sig, '!=', '')
                        ->where($sig, 'like', 'data:image%');
                });
        });
    }

    private function parseFlexibleDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['m/d/Y', 'n/j/Y', 'Y-m-d', 'm-d-Y', 'Y/m/d', 'd/m/Y', 'j/n/Y', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function risItemsWithUom($risId)
    {
        $query = DB::table('requisition_issue_slip_items_table')
            ->where('requisition_issue_slip_items_table.ris_id', $risId)
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
