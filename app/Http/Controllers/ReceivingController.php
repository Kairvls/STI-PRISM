<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\WorkflowNotifier;
use App\Support\RisWorkflow;

class ReceivingController extends Controller
{
    private ?string $queryError = null;

    public function dashboard(): View
    {
        $pendingRows = $this->pendingRows();
        $queueRows = $pendingRows->filter(fn ($row) => ($row->receiving_report_status ?? '') !== 'Returned')->values();
        $acceptedRows = $this->acceptedRows();
        $returnedRows = $this->returnedRows();
        $logs = $this->logRows(20);
        $suppliers = $this->supplierRows();
        $logCount = 0;
        if (Schema::hasTable('receiving_logs_table')) {
            try {
                $logCount = (int) DB::table('receiving_logs_table')->count();
            } catch (\Throwable $e) {
                $logCount = $logs->count();
            }
        }

        $calendarEvents = $this->receivingCalendarEvents($pendingRows, $acceptedRows, $returnedRows);
        $calendarEventsByDate = [];
        foreach ($calendarEvents as $evt) {
            $dateKey = $evt->event_date ?? null;
            if ($dateKey) {
                $calendarEventsByDate[$dateKey][] = $evt;
            }
        }

        return view('receiving-officer.dashboard', $this->withQueryError([
            'pendingCount' => $queueRows->count(),
            'pendingAmount' => (float) $queueRows->sum(fn ($row) => (float) ($row->total_amount ?? 0)),
            'acceptedCount' => $acceptedRows->count(),
            'acceptedMonth' => $acceptedRows->filter(function ($row) {
                return !empty($row->received_at) && \Carbon\Carbon::parse($row->received_at)->isCurrentMonth();
            })->count(),
            'returnedCount' => $returnedRows->count(),
            'historyCount' => $acceptedRows->count() + $returnedRows->count(),
            'supplierCount' => $suppliers->count(),
            'logCount' => $logCount,
            'pendingRows' => $pendingRows->take(8),
            'acceptedRows' => $acceptedRows->take(6),
            'returnedRows' => $returnedRows->take(5),
            'recentLogs' => $logs->take(8),
            'topSuppliers' => $suppliers->sortByDesc('delivery_count')->take(5)->values(),
            'calendarEvents' => $calendarEvents,
            'calendarEventsByDate' => $calendarEventsByDate,
        ]));
    }

    public function notifications(): View
    {
        $items = collect();
        try {
            $items = DB::table('notifications_table')
                ->where(function ($q) {
                    $q->where('notification_user_id', Auth::id())
                        ->orWhere('notification_target_role', 'Receiving Officer');
                })
                ->orderByDesc('notification_created_at')
                ->limit(80)
                ->get();
        } catch (\Throwable $e) {
        }

        return view('receiving-officer.notifications.index', compact('items'));
    }

    public function quickAccessContent(string $section)
    {
        $views = [
            'pending' => 'receiving-officer.quick-access._pending',
            'delivered' => 'receiving-officer.quick-access._delivered',
            'suppliers' => 'receiving-officer.quick-access._suppliers',
            'history' => 'receiving-officer.quick-access._history',
            'logs' => 'receiving-officer.quick-access._logs',
        ];

        abort_unless(isset($views[$section]), 404);

        $data = match ($section) {
            'pending' => ['rows' => $this->pendingRows()->take(40)],
            'delivered' => ['rows' => $this->acceptedRows()->take(40)],
            'suppliers' => ['rows' => $this->supplierRows()->sortByDesc('delivery_count')->take(40)->values()],
            'history' => ['rows' => $this->acceptedRows()->merge($this->returnedRows())->sortByDesc(fn ($row) => $row->received_at ?? $row->authority_purchase_id)->take(40)->values()],
            'logs' => ['rows' => $this->logRows(40)],
            default => ['rows' => collect()],
        };

        return view($views[$section], $this->withQueryError($data));
    }

    public function reports(Request $request)
    {
        $filter = $request->query('status', 'queue');
        $empty = $this->emptyRrPager($request);

        if (!Schema::hasTable('receiving_reports_table')) {
            return view('receiving-officer.receiving-reports.index', [
                'reports' => $empty,
                'items' => collect(),
                'filter' => $filter,
                'counts' => ['queue' => 0, 'completed' => 0, 'returned' => 0],
            ]);
        }

        $query = $this->receivingReviewQuery();
        $this->applyRrActiveScope($query);
        $this->applyRrStatusFilter($query, $filter);
        $this->applyRrSearch($query, $request);

        $sortColumn = 'receiving_reports_table.receiving_report_id';
        foreach (['receiving_report_submitted_at', 'receiving_report_created_at', 'receiving_report_date'] as $column) {
            if (Schema::hasColumn('receiving_reports_table', $column)) {
                $sortColumn = 'receiving_reports_table.'.$column;
                break;
            }
        }

        try {
            $reports = $query
                ->orderByDesc($sortColumn)
                ->orderByDesc('receiving_reports_table.receiving_report_id')
                ->paginate(10)
                ->withQueryString();
        } catch (\Throwable $e) {
            $reports = $empty;
        }

        $ids = $reports->getCollection()->pluck('receiving_report_id');
        $items = collect();
        if ($ids->isNotEmpty() && Schema::hasTable('receiving_report_items_table')) {
            $itemsQuery = DB::table('receiving_report_items_table')->whereIn('receiving_report_id', $ids);
            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_id')) {
                $itemsQuery->orderBy('receiving_report_item_id');
            }
            $items = $itemsQuery->get()->groupBy('receiving_report_id');
        }

        $counts = [
            'queue' => $this->countReceivingReports('queue'),
            'completed' => $this->countReceivingReports('completed'),
            'returned' => $this->countReceivingReports('returned'),
        ];

        return view('receiving-officer.receiving-reports.index', compact('reports', 'items', 'filter', 'counts'));
    }

    private function emptyRrPager(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 10, max(1, (int) $request->query('page', 1)), [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    private function receivingReviewQuery()
    {
        $query = DB::table('receiving_reports_table');

        if (
            Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
            && Schema::hasTable('request_check_table')
        ) {
            $query->leftJoin(
                'request_check_table',
                'receiving_reports_table.receiving_report_request_check_id',
                '=',
                'request_check_table.request_check_id'
            );

            $select = ['receiving_reports_table.*'];
            foreach (['request_check_form_number', 'request_check_payee'] as $column) {
                if (Schema::hasColumn('request_check_table', $column)) {
                    $select[] = 'request_check_table.'.$column;
                }
            }
            $query->select($select);
        }

        return $query;
    }

    private function applyRrActiveScope($query): void
    {
        if (!Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('receiving_reports_table.receiving_report_is_archived')
                ->orWhere('receiving_reports_table.receiving_report_is_archived', 0);
        });
    }

    private function applyRrStatusFilter($query, string $filter): void
    {
        $statusCol = 'receiving_reports_table.receiving_report_status';

        if ($filter === 'completed') {
            $query->where($statusCol, 'Completed');
            return;
        }

        if ($filter === 'returned') {
            $query->where($statusCol, 'Returned');
            return;
        }

        $query->whereIn($statusCol, ['Pending', 'Submitted', 'Resubmitted', 'Under Review']);
    }

    private function applyRrSearch($query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';
        $query->where(function ($q) use ($like) {
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
                $q->orWhere('receiving_reports_table.receiving_report_form_number', 'like', $like);
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_from')) {
                $q->orWhere('receiving_reports_table.receiving_report_received_from', 'like', $like);
            }
            if (
                Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
                && Schema::hasTable('request_check_table')
                && Schema::hasColumn('request_check_table', 'request_check_form_number')
            ) {
                $q->orWhere('request_check_table.request_check_form_number', 'like', $like);
            }
        });
    }

    private function receivingReference(object $row): string
    {
        foreach (['receiving_report_form_number', 'ris_form_number', 'authority_purchase_form_number'] as $field) {
            $value = trim((string) ($row->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $id = $row->receiving_report_id ?? $row->authority_purchase_id ?? null;

        return $id ? 'RR-'.$id : '—';
    }

    private function countReceivingReports(string $filter): int
    {
        $query = $this->receivingReviewQuery();
        $this->applyRrActiveScope($query);
        $this->applyRrStatusFilter($query, $filter);

        try {
            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function startRrReview($id)
    {
        return DB::transaction(function () use ($id) {
            $rr = $this->lockQueueRr($id);
            if (!is_object($rr)) {
                return $rr;
            }

            if (in_array($rr->receiving_report_status, ['Submitted', 'Resubmitted'], true)) {
                DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update($this->onlyExisting('receiving_reports_table', [
                    'receiving_report_status' => 'Under Review',
                    'receiving_report_updated_at' => now(),
                ]));
            }

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'Under Review']);
            }

            return back();
        });
    }

    public function secondCount(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $rr = $this->lockQueueRr($id);
            if (!is_object($rr)) {
                return $rr;
            }

            $name = Auth::user()->user_full_name ?? 'Receiving Officer';
            $signature = RisWorkflow::drawnOrName($request->input('signature_data'), $name);

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update($this->onlyExisting('receiving_reports_table', [
                'receiving_report_status' => 'Completed',
                'receiving_report_second_count_by' => $name,
                'receiving_report_second_count_by_user_id' => Auth::id(),
                'receiving_report_second_count_at' => now(),
                'receiving_report_second_count_signature' => $signature,
                'receiving_report_return_reason' => null,
                'receiving_report_updated_at' => now(),
            ]));

            $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $id)->first() ?? $rr;
            $this->fillRisReceivedBy($rr, $name);
            $this->stockFromReceivingReport($rr, now(), Auth::id());
            $this->writeLog(
                (int) $rr->receiving_report_id,
                !empty($rr->receiving_report_atp_id) ? (int) $rr->receiving_report_atp_id : null,
                'Second count completed',
                'Items accepted. Inventory updated.',
                Auth::id()
            );

            $this->notifyPurchaserRr($rr, 'Receiving completed', 'Items were accepted. You may create a Liquidation Report.', 'rr_completed');

            return back()->with('success', 'Second Count confirmed. Inventory updated. Purchaser may create a Liquidation Report.');
        });
    }

    public function returnRr(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);

        return DB::transaction(function () use ($request, $id) {
            $rr = $this->lockQueueRr($id);
            if (!is_object($rr)) {
                return $rr;
            }

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update($this->onlyExisting('receiving_reports_table', [
                'receiving_report_status' => 'Returned',
                'receiving_report_return_reason' => $request->input('remarks'),
                'receiving_report_updated_at' => now(),
            ]));

            $this->notifyPurchaserRr($rr, 'Receiving returned', $request->input('remarks'), 'rr_returned');
            $this->writeLog(
                (int) $rr->receiving_report_id,
                !empty($rr->receiving_report_atp_id) ? (int) $rr->receiving_report_atp_id : null,
                'Returned for correction',
                $request->input('remarks'),
                Auth::id()
            );

            return back()->with('success', 'Receiving Report returned. Items were not accepted.');
        });
    }

    public function reviseRr(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);

        return DB::transaction(function () use ($request, $id) {
            $rr = $this->lockQueueRr($id);
            if (!is_object($rr)) {
                return $rr;
            }

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                'receiving_report_status' => 'Minor Revision',
                'receiving_report_revision_notes' => $request->input('remarks'),
                'receiving_report_updated_at' => now(),
            ]);

            $this->notifyPurchaserRr($rr, 'Receiving revision required', $request->input('remarks'), 'rr_revision');
            $this->writeLog(
                (int) $rr->receiving_report_id,
                !empty($rr->receiving_report_atp_id) ? (int) $rr->receiving_report_atp_id : null,
                'Returned for revision',
                $request->input('remarks'),
                Auth::id()
            );

            return back()->with('success', 'Receiving Report returned to Purchaser for revision.');
        });
    }

    public function deliveredItems(Request $request): View
    {
        return view('receiving-officer.receiving-reports.delivered-items', $this->withQueryError([
            'rows' => $this->acceptedRows(),
        ]));
    }

    public function inventoryUpdate(Request $request): View
    {
        return view('receiving-officer.receiving-reports.inventory-update', $this->withQueryError([
            'items' => $this->inventoryItems(),
        ]));
    }

    public function officialReceipts(Request $request): View
    {
        return view('receiving-officer.receiving-reports.official-receipts', $this->withQueryError([
            'rows' => $this->acceptedRows()->filter(fn ($row) => !empty($row->official_receipt))->values(),
        ]));
    }

    public function supplierRecords(Request $request): View
    {
        return view('receiving-officer.receiving-reports.supplier-records', $this->withQueryError([
            'suppliers' => $this->supplierRows(),
        ]));
    }

    public function history(Request $request): View
    {
        $accepted = $this->acceptedRows();
        $returned = $this->returnedRows();
        $rows = $accepted->merge($returned)->sortByDesc(fn ($row) => $row->received_at ?? $row->authority_purchase_id)->values();

        return view('receiving-officer.receiving-reports.receiving-history', $this->withQueryError([
            'rows' => $rows,
            'filter' => 'all',
            'acceptedCount' => $accepted->count(),
            'returnedCount' => $returned->count(),
            'allCount' => $accepted->count() + $returned->count(),
        ]));
    }

    public function receivingLogs(Request $request): View
    {
        $logs = $this->logRows(200);

        return view('receiving-officer.receiving-reports.receiving-logs', $this->withQueryError([
            'logs' => $logs,
            'acceptedCount' => $logs->filter(fn ($log) => str_contains(strtolower((string) $log->receiving_log_action), 'return') === false && str_contains(strtolower((string) $log->receiving_log_action), 'accept'))->count(),
            'returnedCount' => $logs->filter(fn ($log) => str_contains(strtolower((string) $log->receiving_log_action), 'return'))->count(),
            'allCount' => $logs->count(),
        ]));
    }

    public function printReport(int $reportId)
    {
        abort_unless(Schema::hasTable('receiving_reports_table'), 404);

        $row = $this->acceptedRows()->firstWhere('receiving_report_id', $reportId)
            ?? $this->returnedRows()->firstWhere('receiving_report_id', $reportId)
            ?? $this->pendingRows()->firstWhere('receiving_report_id', $reportId);

        abort_if(!$row, 404, 'Receiving report not found.');

        $items = collect();
        if (Schema::hasTable('receiving_report_items_table')) {
            $items = DB::table('receiving_report_items_table')
                ->where('receiving_report_id', $reportId)
                ->orderBy('receiving_report_item_id')
                ->get();
        }
        if ($items->isEmpty() && !empty($row->authority_purchase_id)) {
            $items = $this->atpItems((int) $row->authority_purchase_id);
        }

        $checklist = [];
        if (!empty($row->receiving_report_checklist)) {
            $decoded = json_decode((string) $row->receiving_report_checklist, true);
            $checklist = is_array($decoded) ? $decoded : [];
        }

        return view('receiving-officer.receiving-reports.print', [
            'row' => $row,
            'items' => $items,
            'checklist' => $checklist,
            'officerName' => $row->officer_name ?: ($row->officer_signature ?? 'Receiving Officer'),
        ]);
    }

    public function printRis($risId)
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(!$ris, 404, 'RIS not found.');

        $risItems = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get()
            ->pad(8, null);

        return view('admin.ris.print', [
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => null,
        ]);
    }

    public function exportTablePdf(Request $request)
    {
        $section = strtolower((string) $request->query('section', 'reports'));
        $allowed = ['reports', 'history', 'delivered', 'logs', 'suppliers', 'receipts', 'inventory'];
        if (!in_array($section, $allowed, true)) {
            $section = 'reports';
        }

        $pack = match ($section) {
            'reports' => $this->receivingExportPack(
                'Pending Receiving Reports',
                ['Receiving Report', 'Items', 'Supplier', 'Status', 'Value'],
                $this->pendingRows()->map(fn ($row) => [
                    $this->receivingReference($row),
                    $row->item_names ?: '—',
                    $row->supplier_name ?: '—',
                    (($row->receiving_report_status ?? null) === 'Returned') ? 'Returned' : 'Pending',
                    'PHP '.number_format((float) ($row->total_amount ?? 0), 2),
                ]),
                'pending-receiving-reports.pdf'
            ),
            'history' => $this->receivingExportPack(
                'Delivery History',
                ['Date', 'RR / RIS', 'Supplier', 'Result', 'Officer'],
                $this->acceptedRows()->merge($this->returnedRows())->sortByDesc(fn ($row) => $row->received_at ?? $row->authority_purchase_id)->values()->map(fn ($row) => [
                    !empty($row->received_at) ? \Carbon\Carbon::parse($row->received_at)->format('Y-m-d') : '—',
                    $this->receivingReference($row),
                    $row->supplier_name ?: '—',
                    in_array($row->receiving_report_status, ['Accepted', 'Completed'], true) ? 'Accepted' : 'Returned',
                    $row->officer_name ?: '—',
                ]),
                'delivery-history.pdf'
            ),
            'delivered' => $this->receivingExportPack(
                'Delivered Items',
                ['RR / RIS', 'Items', 'Supplier', 'Received', 'Officer'],
                $this->acceptedRows()->map(fn ($row) => [
                    $this->receivingReference($row),
                    $row->item_names ?: '—',
                    $row->supplier_name ?: '—',
                    !empty($row->received_at) ? \Carbon\Carbon::parse($row->received_at)->format('Y-m-d') : '—',
                    $row->officer_name ?: 'Receiving Officer',
                ]),
                'delivered-items.pdf'
            ),
            'logs' => $this->receivingExportPack(
                'Receiving Logs',
                ['Timestamp', 'Action', 'Reference', 'Officer', 'Remarks'],
                $this->logRows(500)->map(fn ($log) => [
                    !empty($log->receiving_log_created_at) ? \Carbon\Carbon::parse($log->receiving_log_created_at)->format('Y-m-d H:i') : '—',
                    $log->receiving_log_action ?: '—',
                    $this->receivingReference($log),
                    $log->officer_name ?: 'Receiving Officer',
                    $log->receiving_log_remarks ?: '—',
                ]),
                'receiving-logs.pdf'
            ),
            'suppliers' => $this->receivingExportPack(
                'Supplier Lookup',
                ['Supplier', 'Type', 'Contact', 'Phone', 'Accepted'],
                $this->supplierRows()->map(fn ($supplier) => [
                    $supplier->supplier_name ?: '—',
                    $supplier->supplier_store_type ?: '—',
                    $supplier->contact_person ?: '—',
                    $supplier->contact_number ?: '—',
                    (string) ($supplier->delivery_count ?? 0),
                ]),
                'supplier-lookup.pdf'
            ),
            'receipts' => $this->receivingExportPack(
                'Official Receipts',
                ['OR', 'RR / RIS', 'Supplier', 'Date'],
                $this->acceptedRows()->filter(fn ($row) => !empty($row->official_receipt))->values()->map(fn ($row) => [
                    $row->official_receipt,
                    $this->receivingReference($row),
                    $row->supplier_name ?: '—',
                    !empty($row->received_at) ? \Carbon\Carbon::parse($row->received_at)->format('Y-m-d') : '—',
                ]),
                'official-receipts.pdf'
            ),
            'inventory' => $this->receivingExportPack(
                'Inventory Update',
                ['Item', 'Qty added', 'Date'],
                $this->inventoryItems()->map(fn ($item) => [
                    $item->receiving_report_item_article ?? $item->receiving_item_description ?? '—',
                    (string) ($item->receiving_report_item_quantity ?? $item->receiving_item_quantity ?? '—'),
                    \Carbon\Carbon::parse($item->receiving_report_date ?? $item->receiving_report_created_at)->format('Y-m-d'),
                ]),
                'inventory-update.pdf'
            ),
        };

        $pdf = Pdf::loadView('receiving-officer.receiving-reports.table-export-pdf', [
            'title' => $pack['title'],
            'headers' => $pack['headers'],
            'rows' => $pack['rows'],
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($pack['filename']);
    }

    private function receivingExportPack(string $title, array $headers, $rows, string $filename): array
    {
        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows->values()->all(),
            'filename' => $filename,
        ];
    }

    private function receivingCalendarEvents($pendingRows, $acceptedRows, $returnedRows)
    {
        $events = collect();
        foreach ($pendingRows as $row) {
            $ref = $this->receivingReference($row);
            $label = (($row->receiving_report_status ?? null) === 'Returned') ? 'Returned for correction' : 'Pending inspection';
            $this->pushReceivingCalendarEvent($events, $row->receiving_report_created_at ?? $row->authority_purchase_date ?? null, $ref.' · '.$label);
        }
        foreach ($acceptedRows as $row) {
            $ref = $this->receivingReference($row);
            $this->pushReceivingCalendarEvent($events, $row->received_at ?? $row->receiving_report_date ?? null, $ref.' · Accepted');
        }
        foreach ($returnedRows as $row) {
            $ref = $this->receivingReference($row);
            $this->pushReceivingCalendarEvent($events, $row->received_at ?? $row->receiving_report_date ?? $row->receiving_report_created_at ?? null, $ref.' · Returned');
        }

        return $events->sortBy('event_date')->values();
    }

    private function pushReceivingCalendarEvent($events, $rawDate, string $name): void
    {
        if (empty($rawDate)) {
            return;
        }
        try {
            $date = \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Throwable $e) {
            return;
        }
        $events->push((object) [
            'event_date' => $date,
            'event_name' => $name,
        ]);
    }

    private function reportBaseQuery()
    {
        $itemsSub = Schema::hasTable('receiving_report_items_table')
            ? DB::table('receiving_report_items_table')
                ->select(
                    'receiving_report_id',
                    DB::raw('GROUP_CONCAT(receiving_report_item_article SEPARATOR ", ") as item_names'),
                    DB::raw('SUM(receiving_report_item_quantity) as total_qty'),
                    DB::raw('SUM(receiving_report_item_amount) as total_amount')
                )
                ->groupBy('receiving_report_id')
            : null;

        $query = DB::table('receiving_reports_table');

        if (Schema::hasTable('authority_to_purchase_table') && Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
            $query->leftJoin(
                'authority_to_purchase_table',
                'authority_to_purchase_table.authority_purchase_id',
                '=',
                'receiving_reports_table.receiving_report_atp_id'
            );
        }

        if (Schema::hasTable('requisition_issue_slip_table')) {
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_ris_id')) {
                $query->leftJoin(
                    'requisition_issue_slip_table',
                    'requisition_issue_slip_table.ris_id',
                    '=',
                    'receiving_reports_table.receiving_report_ris_id'
                );
            } elseif (Schema::hasTable('authority_to_purchase_table')) {
                $query->leftJoin(
                    'requisition_issue_slip_table',
                    'requisition_issue_slip_table.ris_id',
                    '=',
                    'authority_to_purchase_table.authority_purchase_ris_id'
                );
            }
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_supplier_id')) {
            if (Schema::hasTable('physical_suppliers_table')) {
                $query->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'receiving_reports_table.receiving_report_supplier_id');
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $query->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'receiving_reports_table.receiving_report_supplier_id');
            }
        } elseif (Schema::hasTable('authority_to_purchase_table')) {
            if (Schema::hasTable('physical_suppliers_table')) {
                $query->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'authority_to_purchase_table.authority_purchase_supplier_id');
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $query->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'authority_to_purchase_table.authority_purchase_supplier_id');
            }
        }

        if ($itemsSub) {
            $query->leftJoinSub($itemsSub, 'rr_items', function ($join) {
                $join->on('rr_items.receiving_report_id', '=', 'receiving_reports_table.receiving_report_id');
            });
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('receiving_reports_table.receiving_report_is_archived')
                    ->orWhere('receiving_reports_table.receiving_report_is_archived', 0);
            });
        }

        $select = [
            'receiving_reports_table.receiving_report_id',
            'receiving_reports_table.receiving_report_status',
        ];

        $select[] = Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')
            ? 'receiving_reports_table.receiving_report_form_number'
            : DB::raw('NULL as receiving_report_form_number');

        if (Schema::hasTable('authority_to_purchase_table')) {
            $select[] = 'authority_to_purchase_table.authority_purchase_id';
            $select[] = 'authority_to_purchase_table.authority_purchase_form_number';
            $select[] = 'authority_to_purchase_table.authority_purchase_ris_id';
        } else {
            $select[] = DB::raw('NULL as authority_purchase_id');
            $select[] = DB::raw('NULL as authority_purchase_form_number');
            $select[] = DB::raw('NULL as authority_purchase_ris_id');
        }

        if (Schema::hasTable('requisition_issue_slip_table')) {
            $select[] = 'requisition_issue_slip_table.ris_id';
            $select[] = 'requisition_issue_slip_table.ris_form_number';
        } else {
            $select[] = DB::raw('NULL as ris_id');
            $select[] = DB::raw('NULL as ris_form_number');
        }

        $supplierParts = ["'Unnamed supplier'"];
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_from')) {
            array_unshift($supplierParts, 'receiving_reports_table.receiving_report_received_from');
        }
        if (Schema::hasTable('online_suppliers_table')) {
            array_unshift($supplierParts, 'online_suppliers_table.shop_name');
        }
        if (Schema::hasTable('physical_suppliers_table')) {
            array_unshift($supplierParts, 'physical_suppliers_table.company_name');
        }
        $select[] = DB::raw('COALESCE('.implode(', ', $supplierParts).') as supplier_name');

        if ($itemsSub) {
            $select[] = 'rr_items.item_names';
            $select[] = 'rr_items.total_qty';
            $select[] = 'rr_items.total_amount';
        } else {
            $select[] = DB::raw('NULL as item_names');
            $select[] = DB::raw('NULL as total_qty');
            $select[] = DB::raw('0 as total_amount');
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_invoice_no')) {
            $select[] = DB::raw('receiving_reports_table.receiving_report_invoice_no as official_receipt');
        } else {
            $select[] = DB::raw('NULL as official_receipt');
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_second_count_at')) {
            $select[] = DB::raw('receiving_reports_table.receiving_report_second_count_at as received_at');
        } elseif (Schema::hasColumn('receiving_reports_table', 'receiving_report_accepted_at')) {
            $select[] = DB::raw('receiving_reports_table.receiving_report_accepted_at as received_at');
        } elseif (Schema::hasColumn('receiving_reports_table', 'receiving_report_date')) {
            $select[] = DB::raw('receiving_reports_table.receiving_report_date as received_at');
        } else {
            $select[] = DB::raw('receiving_reports_table.receiving_report_created_at as received_at');
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_second_count_by')) {
            $select[] = DB::raw('receiving_reports_table.receiving_report_second_count_by as officer_name');
        } elseif (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_signature')) {
            $select[] = DB::raw('receiving_reports_table.receiving_report_received_by_signature as officer_name');
        } else {
            $select[] = DB::raw("'Receiving Officer' as officer_name");
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_created_at')) {
            $select[] = 'receiving_reports_table.receiving_report_created_at';
        }
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_date')) {
            $select[] = 'receiving_reports_table.receiving_report_date';
        }

        return $query->select($select);
    }

    private function pendingRows()
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            return $this->reportBaseQuery()
                ->whereIn('receiving_reports_table.receiving_report_status', [
                    'Pending',
                    'Submitted',
                    'Resubmitted',
                    'Under Review',
                    'Returned',
                ])
                ->orderByDesc('receiving_reports_table.receiving_report_id')
                ->get();
        });
    }

    private function acceptedRows()
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            return $this->reportBaseQuery()
                ->whereIn('receiving_reports_table.receiving_report_status', ['Accepted', 'Completed'])
                ->orderByDesc('receiving_reports_table.receiving_report_id')
                ->get();
        });
    }

    private function returnedRows()
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            return $this->reportBaseQuery()
                ->where('receiving_reports_table.receiving_report_status', 'Returned')
                ->orderByDesc('receiving_reports_table.receiving_report_id')
                ->get();
        });
    }

    private function logRows(int $limit)
    {
        if (!Schema::hasTable('receiving_logs_table')) {
            return collect();
        }

        return $this->safeQuery(function () use ($limit) {
            return DB::table('receiving_logs_table')
                ->leftJoin('users_table', 'users_table.user_id', '=', 'receiving_logs_table.receiving_log_officer_id')
                ->leftJoin('receiving_reports_table', 'receiving_reports_table.receiving_report_id', '=', 'receiving_logs_table.receiving_report_id')
                ->leftJoin('authority_to_purchase_table', 'authority_to_purchase_table.authority_purchase_id', '=', 'receiving_logs_table.receiving_log_atp_id')
                ->leftJoin('requisition_issue_slip_table', 'requisition_issue_slip_table.ris_id', '=', 'authority_to_purchase_table.authority_purchase_ris_id')
                ->select(
                    'receiving_logs_table.*',
                    'users_table.user_full_name as officer_name',
                    'receiving_reports_table.receiving_report_form_number',
                    'authority_to_purchase_table.authority_purchase_form_number',
                    'requisition_issue_slip_table.ris_form_number'
                )
                ->orderByDesc('receiving_logs_table.receiving_log_id')
                ->limit($limit)
                ->get();
        });
    }

    private function supplierRows()
    {
        if (!Schema::hasTable('suppliers_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            $hasReports = Schema::hasTable('receiving_reports_table')
                && Schema::hasColumn('receiving_reports_table', 'receiving_report_supplier_id');

            $query = DB::table('suppliers_table')
                ->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');

            if ($hasReports) {
                $query->leftJoin('receiving_reports_table', function ($join) {
                    $join->on('receiving_reports_table.receiving_report_supplier_id', '=', 'suppliers_table.supplier_id')
                        ->whereIn('receiving_reports_table.receiving_report_status', ['Accepted', 'Completed']);
                });
            }

            return $query
                ->select(
                    'suppliers_table.supplier_id',
                    'suppliers_table.supplier_store_type',
                    'physical_suppliers_table.company_name',
                    'physical_suppliers_table.contact_person',
                    'physical_suppliers_table.contact_number',
                    'physical_suppliers_table.company_address',
                    'online_suppliers_table.shop_name',
                    DB::raw("COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name, 'Unnamed supplier') as supplier_name"),
                    DB::raw($hasReports ? 'COUNT(DISTINCT receiving_reports_table.receiving_report_id) as delivery_count' : '0 as delivery_count'),
                    DB::raw($hasReports ? 'MAX(receiving_reports_table.receiving_report_created_at) as last_delivery' : 'NULL as last_delivery')
                )
                ->groupBy(
                    'suppliers_table.supplier_id',
                    'suppliers_table.supplier_store_type',
                    'physical_suppliers_table.company_name',
                    'physical_suppliers_table.contact_person',
                    'physical_suppliers_table.contact_number',
                    'physical_suppliers_table.company_address',
                    'online_suppliers_table.shop_name'
                )
                ->orderBy('supplier_name')
                ->get();
        });
    }

    private function inventoryItems()
    {
        if (!Schema::hasTable('receiving_report_items_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            return DB::table('receiving_report_items_table')
                ->join('receiving_reports_table', 'receiving_reports_table.receiving_report_id', '=', 'receiving_report_items_table.receiving_report_id')
                ->whereIn('receiving_reports_table.receiving_report_status', ['Accepted', 'Completed'])
                ->select('receiving_report_items_table.*', 'receiving_reports_table.receiving_report_created_at', 'receiving_reports_table.receiving_report_date')
                ->orderByDesc('receiving_report_items_table.receiving_report_id')
                ->get();
        });
    }

    private function atpItems(int $atpId)
    {
        if (!Schema::hasTable('authority_to_purchase_items_table')) {
            return collect();
        }

        return DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $atpId)
            ->orderBy('atp_item_id')
            ->get();
    }

    private function stockFromReceivingReport(object $rr, $now, $officerId): void
    {
        if (!Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        $items = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rr->receiving_report_id)
            ->orderBy('receiving_report_item_id')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $atp = null;
        if (!empty($rr->receiving_report_atp_id) && Schema::hasTable('authority_to_purchase_table')) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $rr->receiving_report_atp_id)
                ->first();
        }

        $source = (object) [
            'receiving_report_supplier_id' => $rr->receiving_report_supplier_id ?? null,
            'receiving_report_date' => $rr->receiving_report_date ?? $rr->receiving_report_delivery_date ?? null,
            'authority_purchase_supplier_id' => $atp->authority_purchase_supplier_id ?? $rr->receiving_report_supplier_id ?? null,
            'authority_purchase_date' => $atp->authority_purchase_date ?? $rr->receiving_report_date ?? null,
            'equipment_room_id' => $this->replacementRoomId($rr, $atp),
        ];

        $created = 0;
        foreach ($items as $item) {
            if (
                Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')
                && !empty($item->receiving_report_item_equipment_id)
            ) {
                continue;
            }

            $equipmentId = $this->insertEquipment($source, $item, $now);
            if (!$equipmentId) {
                continue;
            }

            if (Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                DB::table('receiving_report_items_table')
                    ->where('receiving_report_item_id', $item->receiving_report_item_id)
                    ->update(['receiving_report_item_equipment_id' => $equipmentId]);
            }

            $created++;
        }

        if ($created > 0) {
            $this->writeLog(
                (int) $rr->receiving_report_id,
                !empty($rr->receiving_report_atp_id) ? (int) $rr->receiving_report_atp_id : null,
                'Inventory updated',
                'Stock records created from second count.',
                $officerId
            );
            $this->notifyMaintenanceUntaggedStock($rr, $created);
        }
    }

    private function insertEquipment($source, $item, $now): ?int
    {
        if (!Schema::hasTable('equipment_table')) {
            return null;
        }

        $equipment = [
            'equipment_name' => $item->receiving_report_item_article
                ?? $item->atp_description
                ?? 'Received item',
            'equipment_quantity' => (int) ($item->receiving_report_item_quantity ?? $item->atp_quantity ?? 1),
        ];
        foreach ([
            'equipment_supplier_id' => $source->receiving_report_supplier_id
                ?? $source->authority_purchase_supplier_id
                ?? null,
            'equipment_tracking_mode' => 'Bulk',
            'equipment_condition_status' => 'Good',
            'equipment_inventory_status' => 'Active',
            'equipment_purchase_date' => $source->receiving_report_date
                ?? $source->authority_purchase_date
                ?? $now->toDateString(),
            'equipment_purchase_cost' => $item->receiving_report_item_amount ?? $item->atp_amount ?? null,
            'equipment_acquired_date' => $now->toDateString(),
            'equipment_room_id' => $source->equipment_room_id ?? null,
            'equipment_created_at' => $now,
        ] as $column => $value) {
            if ($column === 'equipment_room_id' && empty($value)) {
                continue;
            }
            if (Schema::hasColumn('equipment_table', $column)) {
                $equipment[$column] = $value;
            }
        }

        return (int) DB::table('equipment_table')->insertGetId($equipment);
    }

    private function onlyExisting(string $table, array $payload): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return collect($payload)->filter(fn ($value, $column) => Schema::hasColumn($table, $column))->all();
    }

    private function writeLog(?int $reportId, ?int $atpId, string $action, ?string $remarks, $officerId): void
    {
        if (!Schema::hasTable('receiving_logs_table')) {
            return;
        }

        DB::table('receiving_logs_table')->insert($this->onlyExisting('receiving_logs_table', [
            'receiving_report_id' => $reportId,
            'receiving_log_atp_id' => $atpId,
            'receiving_log_action' => $action,
            'receiving_log_remarks' => $remarks,
            'receiving_log_officer_id' => $officerId,
            'receiving_log_created_at' => now(),
        ]));
    }

    private function safeQuery(callable $callback)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            Log::error('Receiving query failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            $this->queryError = config('app.debug')
                ? $e->getMessage()
                : 'Receiving records could not be loaded. Check the application log.';

            return collect();
        }
    }

    private function withQueryError(array $data): array
    {
        $data['queryError'] = $this->queryError;

        return $data;
    }

    private function filterInput(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];
    }

    private function filterRows($rows, Request $request, array $textFields, ?string $dateField)
    {
        $filters = $this->filterInput($request);
        $needle = mb_strtolower($filters['q']);

        return $rows->filter(function ($row) use ($needle, $filters, $textFields, $dateField) {
            if ($needle !== '') {
                $haystack = '';
                foreach ($textFields as $field) {
                    $haystack .= ' '.mb_strtolower((string) ($row->{$field} ?? ''));
                }
                if (!str_contains($haystack, $needle)) {
                    return false;
                }
            }

            if ($dateField && ($filters['from'] !== '' || $filters['to'] !== '')) {
                $raw = $row->{$dateField} ?? null;
                if (!$raw) {
                    return false;
                }
                try {
                    $date = \Carbon\Carbon::parse($raw)->toDateString();
                } catch (\Throwable $e) {
                    return false;
                }
                if ($filters['from'] !== '' && $date < $filters['from']) {
                    return false;
                }
                if ($filters['to'] !== '' && $date > $filters['to']) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    private function fillRisReceivedBy(object $rr, string $officerName): void
    {
        if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_received_by_signature')) {
            return;
        }

        $risId = (int) ($rr->receiving_report_ris_id ?? 0);
        if ($risId < 1 && !empty($rr->receiving_report_request_check_id)) {
            $linked = DB::table('request_check_table')
                ->leftJoin(
                    'authority_to_purchase_table',
                    'request_check_table.request_check_authority_purchase_id',
                    '=',
                    'authority_to_purchase_table.authority_purchase_id'
                )
                ->where('request_check_table.request_check_id', $rr->receiving_report_request_check_id)
                ->select('authority_to_purchase_table.authority_purchase_ris_id')
                ->first();
            $risId = (int) ($linked->authority_purchase_ris_id ?? 0);
        }

        if ($risId < 1) {
            return;
        }

        $payload = [
            'ris_received_by_signature' => $officerName,
        ];
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_received_by_date')) {
            $payload['ris_received_by_date'] = now()->toDateString();
        }

        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->where(function ($q) {
                $q->whereNull('ris_received_by_signature')->orWhere('ris_received_by_signature', '');
            })
            ->update($payload);
    }

    private function lockQueueRr($id)
    {
        $rr = DB::table('receiving_reports_table')
            ->where('receiving_report_id', $id)
            ->lockForUpdate()
            ->first();

        if (!$rr) {
            return back()->with('error', 'Receiving Report not found.');
        }

        if ((int) ($rr->receiving_report_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived Receiving Reports cannot be reviewed.');
        }

        if (!in_array($rr->receiving_report_status, ['Pending', 'Submitted', 'Resubmitted', 'Under Review'], true)) {
            return back()->with('error', 'Only submitted Receiving Reports can be reviewed.');
        }

        return $rr;
    }

    private function notifyPurchaserRr($rr, string $title, string $message, string $type): void
    {
        $ref = $rr->receiving_report_form_number ?? ('RR #' . $rr->receiving_report_id);
        WorkflowNotifier::toUser(
            $rr->receiving_report_submitted_by ?? null,
            WorkflowNotifier::ROLE_PURCHASER,
            $title,
            $ref . ': ' . $message,
            $type,
            'RR',
            (int) $rr->receiving_report_id,
            '/purchaser/receiving-reports'
        );
    }

    private function replacementRoomId(object $rr, $atp): ?int
    {
        if (!Schema::hasTable('reports_table') || !Schema::hasColumn('reports_table', 'report_room_id')) {
            return null;
        }

        $risId = $rr->receiving_report_ris_id ?? null;
        if (!$risId && $atp && !empty($atp->authority_purchase_ris_id)) {
            $risId = $atp->authority_purchase_ris_id;
        }
        if (!$risId && !empty($rr->receiving_report_atp_id) && Schema::hasTable('authority_to_purchase_table')) {
            $risId = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $rr->receiving_report_atp_id)
                ->value('authority_purchase_ris_id');
        }
        if (!$risId || !Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        $procurementRequestId = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->value('ris_procurement_request_id');
        if (!$procurementRequestId || !Schema::hasTable('procurement_requests_table')) {
            return null;
        }

        $reportId = DB::table('procurement_requests_table')
            ->where('procurement_request_id', $procurementRequestId)
            ->value('procurement_request_report_id');
        if (!$reportId) {
            return null;
        }

        $roomId = DB::table('reports_table')->where('report_id', $reportId)->value('report_room_id');
        if (!$roomId) {
            return null;
        }

        if (Schema::hasTable('rooms_table') && Schema::hasColumn('rooms_table', 'room_id')) {
            $exists = DB::table('rooms_table')->where('room_id', $roomId)->exists();
            if (!$exists) {
                return null;
            }
        }

        return (int) $roomId;
    }

    private function notifyMaintenanceUntaggedStock(object $rr, int $created): void
    {
        $ref = $rr->receiving_report_form_number ?? ('RR #' . $rr->receiving_report_id);
        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_MAINTENANCE,
            'New stock ready to place and tag',
            $ref . ' added ' . $created . ' inventory ' . ($created === 1 ? 'item' : 'items') . '. Assign a room if needed, then generate QR codes and asset tags.',
            'rr_stock_created',
            'RR',
            (int) $rr->receiving_report_id,
            '/maintenance/equipment/qr-tools'
        );
    }
}
