<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data for the Purchaser dashboard. Workload counts come from PurchaserAttentionSummary (same numbers as
 * the daily reminder); document lists come from PurchaserHistory (strictly this account's own).
 */
class PurchaserDashboard
{
    public const STAGES = [
        'draft' => 'Draft',
        'review' => 'In review',
        'attention' => 'Needs action',
        'done' => 'Completed',
    ];

    public static function build(int $userId): array
    {
        $documents = PurchaserHistory::allDocuments($userId)
            ->each(fn ($doc) => $doc->stage = self::stage($doc));
        $active = $documents->reject(fn ($doc) => $doc->is_archived);
        $attention = PurchaserAttentionSummary::counts();

        return [
            'attention' => $attention,
            'handovers' => DraftHandover::incomingCounts(),
            'openBackOrders' => self::openBackOrders($documents),
            'backOrderReports' => self::backOrderReports($documents),
            'needsAction' => $active->where('stage', 'attention')->take(5)->values(),
            'recent' => $documents->take(5)->values(),
            'recentTotal' => $documents->count(),
            'replacementCounts' => self::replacementCounts($attention),
            'latestReplacementRequests' => self::latestReplacementRequests(),
            'delayedUrgentFlows' => self::delayedUrgentFlows(),
        ];
    }

    /**
     * Where a document sits in its own workflow.
     */
    public static function stage(object $doc): string
    {
        $status = mb_strtolower(trim($doc->status));

        return match (true) {
            $status === '' || str_starts_with($status, 'draft') => 'draft',
            str_contains($status, 'revision') || in_array($status, ['returned', 'incomplete'], true) => 'attention',
            $status === 'accepted' => 'review',
            $doc->tone === 'green' => 'done',
            $doc->tone === 'red' => 'closed',
            default => 'review',
        };
    }

    private static function openBackOrders(Collection $documents): int
    {
        $rrIds = $documents->where('type', 'rr')->pluck('id')->all();
        if ($rrIds === [] || ! BackOrders::supported()) {
            return 0;
        }

        return (int) DB::table(BackOrders::TABLE)
            ->whereIn('back_order_root_receiving_report_id', $rrIds)
            ->whereIn('back_order_status', BackOrders::UNRESOLVED)
            ->count();
    }

    /**
     * This account's RRs that still have unresolved back orders, newest first.
     *
     * @return Collection<int, object{doc: object, open: int, missing: int, damaged: int, amount: float, articles: array<int, string>}>
     */
    private static function backOrderReports(Collection $documents): Collection
    {
        $rrDocs = $documents->where('type', 'rr')->reject(fn ($doc) => $doc->is_archived)->keyBy('id');
        if ($rrDocs->isEmpty() || ! BackOrders::supported()) {
            return collect();
        }

        $grouped = DB::table(BackOrders::TABLE)
            ->whereIn('back_order_root_receiving_report_id', $rrDocs->keys()->all())
            ->whereIn('back_order_status', BackOrders::UNRESOLVED)
            ->get(['back_order_root_receiving_report_id', 'back_order_type', 'back_order_quantity', 'back_order_unit_price', 'back_order_article'])
            ->groupBy('back_order_root_receiving_report_id');

        return $rrDocs
            ->filter(fn ($doc, $id) => $grouped->has($id))
            ->map(function ($doc, $id) use ($grouped) {
                $rows = $grouped->get($id);

                return (object) [
                    'doc' => $doc,
                    'open' => $rows->count(),
                    'missing' => (int) $rows->where('back_order_type', 'short')->sum('back_order_quantity'),
                    'damaged' => (int) $rows->where('back_order_type', 'damaged')->sum('back_order_quantity'),
                    'amount' => (float) $rows->sum(fn ($row) => (int) $row->back_order_quantity * (float) $row->back_order_unit_price),
                    'articles' => $rows->pluck('back_order_article')->map(fn ($a) => trim((string) $a))->filter()->unique()->values()->all(),
                ];
            })
            ->values();
    }

    /**
     * Newest replacement requests from Maintenance, shared across purchasers.
     *
     * @return Collection<int, object>
     */
    private static function latestReplacementRequests(): Collection
    {
        if (! Schema::hasTable('procurement_requests_table') || ! Schema::hasTable('reports_table')) {
            return collect();
        }

        $query = DB::table('procurement_requests_table')
            ->join('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->where('procurement_requests_table.procurement_request_is_archived', false);

        if (Schema::hasTable('equipment_table')) {
            $query->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id');
        }
        if (Schema::hasTable('rooms_table')) {
            $query->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id');
        }

        $equipment = Schema::hasTable('equipment_table')
            ? "COALESCE(NULLIF(equipment_table.equipment_name, ''), NULLIF(reports_table.report_unlisted_equipment_name, ''), 'Equipment')"
            : "COALESCE(NULLIF(reports_table.report_unlisted_equipment_name, ''), 'Equipment')";
        $room = Schema::hasTable('rooms_table')
            ? "COALESCE(NULLIF(rooms_table.room_name, ''), '')"
            : "''";

        return $query
            ->orderByDesc('procurement_requests_table.procurement_request_created_at')
            ->limit(3)
            ->get([
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_status',
                'procurement_requests_table.procurement_request_created_at',
                'reports_table.report_urgency_level',
                'reports_table.report_id',
                'reports_table.report_submitted_at',
                DB::raw($equipment.' as equipment_name'),
                DB::raw($room.' as room_name'),
            ])
            ->map(function ($row) {
                $created = $row->procurement_request_created_at;

                return (object) [
                    'id' => (int) $row->procurement_request_id,
                    'code' => ReplacementRequestCode::code($row),
                    'equipment' => (string) $row->equipment_name,
                    'room' => (string) $row->room_name,
                    'status' => trim((string) ($row->procurement_request_status ?: 'Pending')),
                    'urgent' => strcasecmp((string) $row->report_urgency_level, 'Urgent') === 0,
                    'date' => $created ? Carbon::parse($created)->format('M d, Y') : '—',
                ];
            })
            ->values();
    }

    /**
     * Urgent procurement flows still open, longest wait first.
     * A flow is open until a non-cash-advance Receiving Report is completed,
     * or a Cash Advance liquidation is approved.
     *
     * @return Collection<int, object>
     */
    private static function delayedUrgentFlows(): Collection
    {
        if (! Schema::hasTable('requisition_issue_slip_table')
            || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_urgency')) {
            return collect();
        }

        $dateSql = Schema::hasColumn('requisition_issue_slip_table', 'ris_submitted_at')
            ? 'COALESCE(requisition_issue_slip_table.ris_submitted_at, requisition_issue_slip_table.ris_created_at)'
            : 'requisition_issue_slip_table.ris_created_at';

        $query = DB::table('requisition_issue_slip_table')
            ->whereRaw('TRIM(requisition_issue_slip_table.ris_urgency) = ?', [RisWorkflow::URGENCY_URGENT]);

        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('requisition_issue_slip_table.ris_is_archived')
                    ->orWhere('requisition_issue_slip_table.ris_is_archived', 0);
            });
        }

        $query->where(function ($q) {
            $q->whereNull('requisition_issue_slip_table.ris_status')
                ->orWhere('requisition_issue_slip_table.ris_status', 'not like', '%Reject%');
        });

        PurchaserDocumentAccess::scopeOwned($query, 'ris', 'requisition_issue_slip_table');
        self::whereFlowStillOpen($query);

        $select = [
            'requisition_issue_slip_table.ris_id',
            'requisition_issue_slip_table.ris_form_number',
            'requisition_issue_slip_table.ris_status',
            DB::raw($dateSql.' as waited_since'),
        ];
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            $select[] = 'requisition_issue_slip_table.ris_request_no';
        }

        return $query
            ->orderByRaw($dateSql.' asc')
            ->limit(3)
            ->get($select)
            ->map(function ($row) {
                $requestNo = property_exists($row, 'ris_request_no')
                    ? trim((string) $row->ris_request_no)
                    : '';
                if ($requestNo === ''
                    && Schema::hasTable('authority_to_purchase_table')
                    && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no')) {
                    $requestNo = trim((string) DB::table('authority_to_purchase_table')
                        ->where('authority_purchase_ris_id', (int) $row->ris_id)
                        ->whereNotNull('authority_purchase_request_no')
                        ->orderByDesc('authority_purchase_id')
                        ->value('authority_purchase_request_no'));
                }
                $requestNo = $requestNo !== '' ? RequestExplorer::normalize($requestNo) : '';
                $explorer = $requestNo !== '' ? RequestExplorer::find($requestNo) : null;
                $stages = $explorer['stages'] ?? [];
                $stageKey = $explorer['current_stage'] ?? 'ris';
                $stageName = RequestExplorer::STAGE_LABELS[$stageKey] ?? 'Canvas + RIS';
                $detail = trim((string) ($explorer['current_hint'] ?? ''));
                if ($detail === '') {
                    $detail = trim((string) ($row->ris_status ?? '')) ?: 'In progress';
                }
                $risNumber = trim((string) ($stages['ris']['label'] ?? ''));
                if ($risNumber === '') {
                    $risNumber = trim((string) ($row->ris_form_number ?? '')) ?: 'RIS not started';
                }
                $atpNumber = trim((string) ($stages['atp']['label'] ?? ''));
                if ($atpNumber === '' || empty($stages['atp']['exists'])) {
                    $atpNumber = 'ATP not started';
                }
                $since = $row->waited_since ? Carbon::parse($row->waited_since) : null;
                $days = $since ? (int) $since->startOfDay()->diffInDays(now()->startOfDay()) : 0;
                $hasPr = (bool) preg_match('/^PR-\d{4,6}-\d{1,6}$/', $requestNo);
                $continue = $hasPr
                    ? route('purchaser.requests.show', ['requestNo' => $requestNo])
                    : route('purchaser.ris.index', ['view_ris' => (int) $row->ris_id]);

                return (object) [
                    'request_no' => $hasPr ? $requestNo : ($risNumber !== '' ? $risNumber : 'Draft RIS'),
                    'ris' => $risNumber,
                    'atp' => $atpNumber,
                    'stage' => $stageName,
                    'detail' => $detail,
                    'days' => $days,
                    'preview_url' => $hasPr ? route('purchaser.requests.show', ['requestNo' => $requestNo]).'?preview=1' : '',
                    'continue_url' => $continue,
                ];
            })
            ->values();
    }

    /**
     * Keep RIS whose procurement path has not reached a finished document.
     */
    private static function whereFlowStillOpen($query): void
    {
        if (Schema::hasTable('authority_to_purchase_table')
            && Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('authority_to_purchase_table as open_atp')
                    ->join('receiving_reports_table as open_rr', 'open_rr.receiving_report_atp_id', '=', 'open_atp.authority_purchase_id')
                    ->whereColumn('open_atp.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id')
                    ->where('open_rr.receiving_report_status', 'Completed')
                    ->where(function ($path) {
                        $path->whereNull('open_atp.authority_purchase_payment_path')
                            ->orWhere('open_atp.authority_purchase_payment_path', '!=', ProcurementPaymentPath::CASH_ADVANCE);
                    });
            });
        }

        if (Schema::hasTable('authority_to_purchase_table')
            && Schema::hasTable('request_check_table')
            && Schema::hasTable('receiving_reports_table')
            && Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('authority_to_purchase_table as open_atp2')
                    ->join('request_check_table as open_rfc', 'open_rfc.request_check_authority_purchase_id', '=', 'open_atp2.authority_purchase_id')
                    ->join('receiving_reports_table as open_rr2', 'open_rr2.receiving_report_request_check_id', '=', 'open_rfc.request_check_id')
                    ->whereColumn('open_atp2.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id')
                    ->where('open_rr2.receiving_report_status', 'Completed')
                    ->where(function ($path) {
                        $path->where('open_rfc.request_check_funding_type', '!=', ProcurementPaymentPath::CASH_ADVANCE)
                            ->orWhereNull('open_rfc.request_check_funding_type');
                    });
            });
        }

        $linkAtp = Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id');
        $linkRfc = Schema::hasTable('request_check_table')
            && Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
            && Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id');
        if (Schema::hasTable('liquidation_reports_table')
            && Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')
            && Schema::hasTable('receiving_reports_table')
            && ($linkAtp || $linkRfc)) {
            $query->whereNotExists(function ($sub) use ($linkAtp, $linkRfc) {
                $sub->select(DB::raw(1))
                    ->from('liquidation_reports_table as open_liq')
                    ->join('receiving_reports_table as open_rr3', 'open_rr3.receiving_report_id', '=', 'open_liq.liquidation_report_receiving_report_id')
                    ->where('open_liq.liquidation_report_status', 'Approved')
                    ->where(function ($link) use ($linkAtp, $linkRfc) {
                        if ($linkAtp) {
                            $link->whereExists(function ($atp) {
                                $atp->select(DB::raw(1))
                                    ->from('authority_to_purchase_table as liq_atp')
                                    ->whereColumn('liq_atp.authority_purchase_id', 'open_rr3.receiving_report_atp_id')
                                    ->whereColumn('liq_atp.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id');
                            });
                        }
                        if ($linkRfc) {
                            $method = $linkAtp ? 'orWhereExists' : 'whereExists';
                            $link->{$method}(function ($rfc) {
                                $rfc->select(DB::raw(1))
                                    ->from('request_check_table as liq_rfc')
                                    ->join('authority_to_purchase_table as liq_atp2', 'liq_atp2.authority_purchase_id', '=', 'liq_rfc.request_check_authority_purchase_id')
                                    ->whereColumn('liq_rfc.request_check_id', 'open_rr3.receiving_report_request_check_id')
                                    ->whereColumn('liq_atp2.authority_purchase_ris_id', 'requisition_issue_slip_table.ris_id');
                            });
                        }
                    });
            });
        }
    }

    /**
     * @param  array<string, int>  $attention
     * @return array{pending: int, approved: int, completed: int}
     */
    private static function replacementCounts(array $attention): array
    {
        $counts = DB::table('procurement_requests_table')
            ->where('procurement_request_is_archived', false)
            ->select('procurement_request_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('procurement_request_status')
            ->pluck('aggregate', 'procurement_request_status');

        return [
            'pending' => (int) ($attention['pendingReplacementRequests'] ?? 0),
            'approved' => (int) ($counts['Approved'] ?? 0),
            'completed' => (int) ($counts['Completed'] ?? 0),
        ];
    }
}
