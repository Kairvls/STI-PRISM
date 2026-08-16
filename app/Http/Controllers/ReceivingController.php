<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceivingController extends Controller
{
    private ?string $queryError = null;

    public const CHECKLIST = [
        'Quantity of items',
        'Product Model',
        'Brand',
        'Specifications',
        'Item Condition',
        'Physical Damage Inspection',
        'Serial Number',
        'Warranty Information',
        'Total Price',
        'Purchase Order Match',
        'Supplier Information',
        'Delivered Item Completeness',
    ];

    public function dashboard(): View
    {
        $this->ensurePendingReceivingReports();

        $pendingRows = $this->pendingRows();
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
            'pendingCount' => $pendingRows->count(),
            'pendingAmount' => (float) $pendingRows->sum(fn ($row) => (float) ($row->total_amount ?? 0)),
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

        if (in_array($section, ['pending', 'history'], true)) {
            $this->ensurePendingReceivingReports();
        }

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

        $query = ReceivingReportController::reviewBaseQuery()
            ->where(function ($q) {
                $q->whereNull('receiving_reports_table.receiving_report_is_archived')
                    ->orWhere('receiving_reports_table.receiving_report_is_archived', 0);
            });

        if ($filter === 'queue') {
            $query->whereIn('receiving_reports_table.receiving_report_status', ['Submitted', 'Resubmitted', 'Under Review']);
        } elseif ($filter === 'completed') {
            $query->where('receiving_reports_table.receiving_report_status', 'Completed');
        } elseif ($filter === 'returned') {
            $query->where('receiving_reports_table.receiving_report_status', 'Returned');
        }

        $reports = $query
            ->orderByDesc('receiving_reports_table.receiving_report_submitted_at')
            ->orderByDesc('receiving_reports_table.receiving_report_id')
            ->paginate(10)
            ->withQueryString();

        $ids = $reports->getCollection()->pluck('receiving_report_id');
        $items = $ids->isEmpty()
            ? collect()
            : DB::table('receiving_report_items_table')
                ->whereIn('receiving_report_id', $ids)
                ->orderBy('receiving_report_item_id')
                ->get()
                ->groupBy('receiving_report_id');

        $base = function () {
            return ReceivingReportController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('receiving_report_is_archived')->orWhere('receiving_report_is_archived', 0);
                });
        };

        $counts = [
            'queue' => $base()->whereIn('receiving_report_status', ['Submitted', 'Resubmitted', 'Under Review'])->count(),
            'completed' => $base()->where('receiving_report_status', 'Completed')->count(),
            'returned' => $base()->where('receiving_report_status', 'Returned')->count(),
        ];

        return view('receiving-officer.receiving-reports.index', compact('reports', 'items', 'filter', 'counts'));
    }

    public function startRrReview($id)
    {
        return DB::transaction(function () use ($id) {
            $rr = $this->lockQueueRr($id);
            if (!is_object($rr)) {
                return $rr;
            }

            if (in_array($rr->receiving_report_status, ['Submitted', 'Resubmitted'], true)) {
                DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                    'receiving_report_status' => 'Under Review',
                    'receiving_report_updated_at' => now(),
                ]);
            }

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'Under Review']);
            }

            return back();
        });
    }

    public function secondCount($id)
    {
        return DB::transaction(function () use ($id) {
            $rr = $this->lockQueueRr($id);
            if (!is_object($rr)) {
                return $rr;
            }

            $name = Auth::user()->user_full_name ?? 'Receiving Officer';

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                'receiving_report_status' => 'Completed',
                'receiving_report_second_count_by' => $name,
                'receiving_report_second_count_by_user_id' => Auth::id(),
                'receiving_report_second_count_at' => now(),
                'receiving_report_second_count_signature' => $name,
                'receiving_report_return_reason' => null,
                'receiving_report_updated_at' => now(),
            ]);

            return back()->with('success', 'Second Count confirmed. Items marked as received correctly.');
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

            DB::table('receiving_reports_table')->where('receiving_report_id', $id)->update([
                'receiving_report_status' => 'Returned',
                'receiving_report_return_reason' => $request->input('remarks'),
                'receiving_report_updated_at' => now(),
            ]);

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

            return back()->with('success', 'Receiving Report returned to Purchaser for revision.');
        });
    }

    public function accept(Request $request, int $atpId): RedirectResponse
    {
        $checked = $request->input('checklist', []);
        if (!is_array($checked) || count($checked) < count(self::CHECKLIST)) {
            return back()->with('error', 'Complete the physical validation checklist before accepting.');
        }

        $atp = $this->approvedAtp($atpId);
        if (!$atp) {
            return back()->with('error', 'This delivery is not ready for receiving.');
        }

        $existing = $this->reportForAtp($atpId);
        if ($existing && in_array($existing->receiving_report_status, ['Accepted', 'Completed'], true)) {
            return back()->with('error', 'This delivery has already been accepted.');
        }

        $request->validate([
            'official_receipt' => ['required', 'string', 'min:3', 'max:80'],
            'goods_match' => ['required', 'accepted'],
        ]);

        $atpItems = $this->atpItems((int) $atp->authority_purchase_id);
        $lineItems = $existing
            ? $this->reportLineItems((object) [
                'receiving_report_id' => $existing->receiving_report_id,
                'authority_purchase_id' => $atp->authority_purchase_id,
            ])
            : $atpItems;
        $receivedQty = $request->input('received_qty', []);
        if (!is_array($receivedQty)) {
            $receivedQty = [];
        }

        foreach ($lineItems as $item) {
            $itemId = (int) ($item->atp_item_id ?? 0);
            $expected = (int) ($item->atp_quantity ?? 0);
            $received = (int) ($receivedQty[$itemId] ?? $receivedQty[(string) $itemId] ?? -1);
            if ($received !== $expected) {
                return back()->withInput()->with(
                    'error',
                    'Delivered quantity does not match the receiving report. Return the delivery for correction if the goods do not match.'
                );
            }
        }

        $officerId = Auth::id();
        $officerName = Auth::user()->user_full_name ?? 'Receiving Officer';
        $receipt = trim((string) $request->input('official_receipt', ''));
        $procurementRequestId = $this->procurementRequestIdForAtp($atp);

        $reportId = DB::transaction(function () use ($atp, $existing, $checked, $officerId, $officerName, $receipt, $procurementRequestId, $atpItems) {
            $now = now();
            $payload = $this->onlyExisting('receiving_reports_table', [
                'receiving_report_atp_id' => $atp->authority_purchase_id,
                'receiving_report_ris_id' => $atp->authority_purchase_ris_id,
                'receiving_report_supplier_id' => $atp->authority_purchase_supplier_id,
                'receiving_report_procurement_request_id' => $procurementRequestId,
                'receiving_report_status' => 'Completed',
                'receiving_report_invoice_no' => $receipt !== '' ? $receipt : null,
                'receiving_report_remarks' => null,
                'receiving_report_checklist' => json_encode(array_values($checked)),
                'receiving_report_officer_id' => $officerId,
                'receiving_report_received_by_signature' => $officerName,
                'receiving_report_date' => $now->toDateString(),
                'receiving_report_delivery_date' => $now->toDateString(),
                'receiving_report_accepted_at' => $now,
                'receiving_report_returned_at' => null,
                'receiving_report_updated_at' => $now,
            ]);

            if ($existing) {
                DB::table('receiving_reports_table')->where('receiving_report_id', $existing->receiving_report_id)->update($payload);
                $reportId = (int) $existing->receiving_report_id;
                if (Schema::hasTable('receiving_report_items_table')) {
                    DB::table('receiving_report_items_table')->where('receiving_report_id', $reportId)->delete();
                }
            } else {
                $payload = $this->onlyExisting('receiving_reports_table', array_merge($payload, [
                    'receiving_report_created_at' => $now,
                ]));
                $reportId = (int) DB::table('receiving_reports_table')->insertGetId($payload);
            }

            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
                $current = DB::table('receiving_reports_table')->where('receiving_report_id', $reportId)->first();
                if ($current && empty($current->receiving_report_form_number)) {
                    DB::table('receiving_reports_table')->where('receiving_report_id', $reportId)->update([
                        'receiving_report_form_number' => 'RR-' . $now->format('Y') . '-' . str_pad((string) $reportId, 5, '0', STR_PAD_LEFT),
                    ]);
                }
            }

            foreach ($atpItems as $item) {
                $equipmentId = $this->insertEquipment($atp, $item, $now);
                if (Schema::hasTable('receiving_report_items_table')) {
                    DB::table('receiving_report_items_table')->insert($this->onlyExisting('receiving_report_items_table', [
                        'receiving_report_id' => $reportId,
                        'receiving_report_item_article' => $item->atp_description,
                        'receiving_report_item_quantity' => $item->atp_quantity,
                        'receiving_report_item_unit' => $item->atp_unit,
                        'receiving_report_item_unit_price' => $item->atp_unit_price ?? null,
                        'receiving_report_item_amount' => $item->atp_amount,
                        'receiving_report_item_equipment_id' => $equipmentId,
                    ]));
                }
            }

            if ($atp->authority_purchase_ris_id && Schema::hasColumn('requisition_issue_slip_table', 'ris_received_by_signature')) {
                DB::table('requisition_issue_slip_table')
                    ->where('ris_id', $atp->authority_purchase_ris_id)
                    ->where(function ($q) {
                        $q->whereNull('ris_received_by_signature')->orWhere('ris_received_by_signature', '');
                    })
                    ->update([
                        'ris_received_by_signature' => $officerName,
                        'ris_received_by_date' => $now->toDateString(),
                    ]);
            }

            $this->writeLog($reportId, (int) $atp->authority_purchase_id, 'Receiving report saved', 'Items complete. Inventory updated.', $officerId);
            $this->writeLog($reportId, (int) $atp->authority_purchase_id, 'Physical validation passed', 'Checklist completed.', $officerId);
            $this->writeLog($reportId, (int) $atp->authority_purchase_id, 'Inventory updated', 'Stock records created from accepted delivery.', $officerId);

            return $reportId;
        });

        return redirect('/receiving/reports/'.$reportId.'/print')->with('success', 'Goods verified and accepted. Inventory updated. Purchaser can proceed to liquidation.');
    }

    public function returnReport(Request $request, int $atpId): RedirectResponse
    {
        $request->validate(['remarks' => ['required', 'string', 'min:8']]);

        $atp = $this->approvedAtp($atpId);
        if (!$atp) {
            return back()->with('error', 'This delivery is not ready for receiving.');
        }

        $existing = $this->reportForAtp($atpId);
        if ($existing && in_array($existing->receiving_report_status, ['Accepted', 'Completed'], true)) {
            return back()->with('error', 'Accepted deliveries cannot be returned from this screen.');
        }

        $officerId = Auth::id();
        $now = now();
        $remarks = trim($request->input('remarks'));
        $procurementRequestId = $this->procurementRequestIdForAtp($atp);

        if ($existing) {
            DB::table('receiving_reports_table')->where('receiving_report_id', $existing->receiving_report_id)->update($this->onlyExisting('receiving_reports_table', [
                'receiving_report_status' => 'Returned',
                'receiving_report_remarks' => $remarks,
                'receiving_report_officer_id' => $officerId,
                'receiving_report_returned_at' => $now,
                'receiving_report_updated_at' => $now,
            ]));
            $reportId = (int) $existing->receiving_report_id;
        } else {
            $reportId = (int) DB::table('receiving_reports_table')->insertGetId($this->onlyExisting('receiving_reports_table', [
                'receiving_report_atp_id' => $atp->authority_purchase_id,
                'receiving_report_ris_id' => $atp->authority_purchase_ris_id,
                'receiving_report_supplier_id' => $atp->authority_purchase_supplier_id,
                'receiving_report_procurement_request_id' => $procurementRequestId,
                'receiving_report_status' => 'Returned',
                'receiving_report_remarks' => $remarks,
                'receiving_report_officer_id' => $officerId,
                'receiving_report_returned_at' => $now,
                'receiving_report_created_at' => $now,
                'receiving_report_updated_at' => $now,
            ]));
        }

        $this->writeLog($reportId, (int) $atp->authority_purchase_id, 'Returned for correction', $remarks, $officerId);

        return redirect('/receiving/reports')->with('success', 'Delivery returned for correction.');
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

        if (in_array($section, ['reports', 'history'], true)) {
            $this->ensurePendingReceivingReports();
        }

        $pack = match ($section) {
            'reports' => $this->receivingExportPack(
                'Pending Receiving Reports',
                ['Receiving Report', 'Items', 'Supplier', 'Status', 'Value'],
                $this->pendingRows()->map(fn ($row) => [
                    $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id)),
                    $row->item_names ?: '—',
                    $row->supplier_name ?: '—',
                    (($row->receiving_report_status ?? null) === 'Returned') ? 'Returned' : 'Pending',
                    'PHP '.number_format((float) ($row->total_amount ?? 0), 2),
                ]),
                'pending-receiving-reports.pdf'
            ),
            'history' => $this->receivingExportPack(
                'Delivery History',
                ['Date', 'RIS / ATP', 'Supplier', 'Result', 'Officer'],
                $this->acceptedRows()->merge($this->returnedRows())->sortByDesc(fn ($row) => $row->received_at ?? $row->authority_purchase_id)->values()->map(fn ($row) => [
                    !empty($row->received_at) ? \Carbon\Carbon::parse($row->received_at)->format('Y-m-d') : '—',
                    $row->ris_form_number ?: ($row->authority_purchase_form_number ?: '—'),
                    $row->supplier_name ?: '—',
                    in_array($row->receiving_report_status, ['Accepted', 'Completed'], true) ? 'Accepted' : 'Returned',
                    $row->officer_name ?: '—',
                ]),
                'delivery-history.pdf'
            ),
            'delivered' => $this->receivingExportPack(
                'Delivered Items',
                ['RIS / ATP', 'Items', 'Supplier', 'Received', 'Officer'],
                $this->acceptedRows()->map(fn ($row) => [
                    $row->ris_form_number ?: ($row->authority_purchase_form_number ?: '—'),
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
                    $log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—'),
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
                ['OR', 'RIS / ATP', 'Supplier', 'Date'],
                $this->acceptedRows()->filter(fn ($row) => !empty($row->official_receipt))->values()->map(fn ($row) => [
                    $row->official_receipt,
                    $row->ris_form_number ?: ($row->authority_purchase_form_number ?: '—'),
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
            $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id));
            $label = (($row->receiving_report_status ?? null) === 'Returned') ? 'Returned for correction' : 'Pending inspection';
            $this->pushReceivingCalendarEvent($events, $row->receiving_report_created_at ?? $row->authority_purchase_date ?? null, $ref.' · '.$label);
        }
        foreach ($acceptedRows as $row) {
            $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id));
            $this->pushReceivingCalendarEvent($events, $row->received_at ?? $row->receiving_report_date ?? null, $ref.' · Accepted');
        }
        foreach ($returnedRows as $row) {
            $ref = $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id));
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

    private function deliveryBaseQuery()
    {
        $itemsSub = Schema::hasTable('authority_to_purchase_items_table')
            ? DB::table('authority_to_purchase_items_table')
                ->select(
                    'authority_purchase_id',
                    DB::raw('GROUP_CONCAT(atp_description SEPARATOR ", ") as item_names'),
                    DB::raw('SUM(atp_quantity) as total_qty'),
                    DB::raw('SUM(atp_amount) as total_amount')
                )
                ->groupBy('authority_purchase_id')
            : null;

        $query = DB::table('authority_to_purchase_table')
            ->leftJoin('requisition_issue_slip_table', 'requisition_issue_slip_table.ris_id', '=', 'authority_to_purchase_table.authority_purchase_ris_id')
            ->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'authority_to_purchase_table.authority_purchase_supplier_id')
            ->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'authority_to_purchase_table.authority_purchase_supplier_id');

        if ($itemsSub) {
            $query->leftJoinSub($itemsSub, 'atp_items', function ($join) {
                $join->on('atp_items.authority_purchase_id', '=', 'authority_to_purchase_table.authority_purchase_id');
            });
        }

        if (Schema::hasTable('receiving_reports_table')) {
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
                $query->leftJoin('receiving_reports_table', 'receiving_reports_table.receiving_report_atp_id', '=', 'authority_to_purchase_table.authority_purchase_id');
            } elseif (Schema::hasColumn('receiving_reports_table', 'receiving_report_procurement_request_id')) {
                $query->leftJoin('receiving_reports_table', 'receiving_reports_table.receiving_report_procurement_request_id', '=', 'requisition_issue_slip_table.ris_procurement_request_id');
            }

            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_officer_id') && Schema::hasTable('users_table')) {
                $query->leftJoin('users_table', 'users_table.user_id', '=', 'receiving_reports_table.receiving_report_officer_id');
            }
        }

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                    ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
            });
        }

        $select = [
            'authority_to_purchase_table.authority_purchase_id',
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_reference_po_no',
            'authority_to_purchase_table.authority_purchase_status',
            'authority_to_purchase_table.authority_purchase_date',
            'authority_to_purchase_table.authority_purchase_ris_id',
            'authority_to_purchase_table.authority_purchase_supplier_id',
            'requisition_issue_slip_table.ris_id',
            'requisition_issue_slip_table.ris_form_number',
            DB::raw("COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name, 'Unnamed supplier') as supplier_name"),
        ];

        if ($itemsSub) {
            $select[] = 'atp_items.item_names';
            $select[] = 'atp_items.total_qty';
            $select[] = 'atp_items.total_amount';
        } else {
            $select[] = DB::raw('NULL as item_names');
            $select[] = DB::raw('NULL as total_qty');
            $select[] = DB::raw('0 as total_amount');
        }

        if (Schema::hasTable('receiving_reports_table')) {
            $select[] = 'receiving_reports_table.receiving_report_id';
            $select[] = 'receiving_reports_table.receiving_report_status';
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
                $select[] = 'receiving_reports_table.receiving_report_form_number';
            } else {
                $select[] = DB::raw('NULL as receiving_report_form_number');
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_invoice_no')) {
                $select[] = DB::raw('receiving_reports_table.receiving_report_invoice_no as official_receipt');
            } else {
                $select[] = DB::raw('NULL as official_receipt');
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_remarks')) {
                $select[] = 'receiving_reports_table.receiving_report_remarks';
            } else {
                $select[] = DB::raw('NULL as receiving_report_remarks');
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_accepted_at')) {
                $select[] = DB::raw('receiving_reports_table.receiving_report_accepted_at as received_at');
            } elseif (Schema::hasColumn('receiving_reports_table', 'receiving_report_date')) {
                $select[] = DB::raw('receiving_reports_table.receiving_report_date as received_at');
            } else {
                $select[] = DB::raw('receiving_reports_table.receiving_report_created_at as received_at');
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_checklist')) {
                $select[] = 'receiving_reports_table.receiving_report_checklist';
            } else {
                $select[] = DB::raw('NULL as receiving_report_checklist');
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_signature')) {
                $select[] = DB::raw('receiving_reports_table.receiving_report_received_by_signature as officer_signature');
            } else {
                $select[] = DB::raw('NULL as officer_signature');
            }
            $officerNameParts = [];
            if (Schema::hasTable('users_table') && Schema::hasColumn('receiving_reports_table', 'receiving_report_officer_id')) {
                $officerNameParts[] = 'users_table.user_full_name';
            }
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_received_by_signature')) {
                $officerNameParts[] = 'receiving_reports_table.receiving_report_received_by_signature';
            }
            $select[] = DB::raw(($officerNameParts ? 'COALESCE('.implode(', ', $officerNameParts).', ' : '')."'Receiving Officer'".($officerNameParts ? ')' : '').' as officer_name');
        } else {
            $select[] = DB::raw('NULL as receiving_report_id');
            $select[] = DB::raw('NULL as receiving_report_form_number');
            $select[] = DB::raw('NULL as receiving_report_status');
            $select[] = DB::raw('NULL as official_receipt');
            $select[] = DB::raw('NULL as receiving_report_remarks');
            $select[] = DB::raw('NULL as received_at');
            $select[] = DB::raw('NULL as receiving_report_checklist');
            $select[] = DB::raw('NULL as officer_signature');
            $select[] = DB::raw("NULL as officer_name");
        }

        return $query->select($select);
    }

    private function pendingRows()
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            $query = $this->deliveryBaseQuery()
                ->where('authority_to_purchase_table.authority_purchase_status', 'Approved');

            if (Schema::hasTable('receiving_reports_table')) {
                $query->where(function ($q) {
                    $q->whereNull('receiving_reports_table.receiving_report_id')
                        ->orWhereIn('receiving_reports_table.receiving_report_status', ['Pending', 'Returned']);
                });
            }

            return $query->orderByDesc('authority_to_purchase_table.authority_purchase_id')->get();
        });
    }

    private function acceptedRows()
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        return $this->safeQuery(function () {
            return $this->deliveryBaseQuery()
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
            return $this->deliveryBaseQuery()
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
                ->leftJoin('authority_to_purchase_table', 'authority_to_purchase_table.authority_purchase_id', '=', 'receiving_logs_table.receiving_log_atp_id')
                ->leftJoin('requisition_issue_slip_table', 'requisition_issue_slip_table.ris_id', '=', 'authority_to_purchase_table.authority_purchase_ris_id')
                ->select(
                    'receiving_logs_table.*',
                    'users_table.user_full_name as officer_name',
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

    private function reportLineItems($row)
    {
        if (!empty($row->receiving_report_id) && Schema::hasTable('receiving_report_items_table')) {
            $items = DB::table('receiving_report_items_table')
                ->where('receiving_report_id', $row->receiving_report_id)
                ->orderBy('receiving_report_item_id')
                ->get();

            if ($items->isNotEmpty()) {
                return $items->map(function ($item) {
                    $item->atp_item_id = $item->receiving_report_item_id;
                    $item->atp_description = $item->receiving_report_item_article;
                    $item->atp_quantity = $item->receiving_report_item_quantity;
                    $item->atp_unit = $item->receiving_report_item_unit ?? '';
                    $item->atp_amount = $item->receiving_report_item_amount ?? 0;
                    $item->atp_unit_price = $item->receiving_report_item_unit_price ?? null;

                    return $item;
                });
            }
        }

        return !empty($row->authority_purchase_id)
            ? $this->atpItems((int) $row->authority_purchase_id)
            : collect();
    }

    private function ensurePendingReceivingReports(): void
    {
        if (!Schema::hasTable('receiving_reports_table') || !Schema::hasTable('authority_to_purchase_table')) {
            return;
        }

        try {
            $approved = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_status', 'Approved')
                ->get();
        } catch (\Throwable $e) {
            return;
        }

        foreach ($approved as $atp) {
            if ($this->reportForAtp((int) $atp->authority_purchase_id)) {
                continue;
            }

            $procurementRequestId = $this->procurementRequestIdForAtp($atp);
            $now = now();
            $payload = $this->onlyExisting('receiving_reports_table', [
                'receiving_report_atp_id' => $atp->authority_purchase_id,
                'receiving_report_ris_id' => $atp->authority_purchase_ris_id ?? null,
                'receiving_report_supplier_id' => $atp->authority_purchase_supplier_id ?? null,
                'receiving_report_procurement_request_id' => $procurementRequestId,
                'receiving_report_status' => 'Pending',
                'receiving_report_date' => $now->toDateString(),
                'receiving_report_created_at' => $now,
                'receiving_report_updated_at' => $now,
            ]);

            if ($payload === []) {
                continue;
            }

            try {
                $reportId = (int) DB::table('receiving_reports_table')->insertGetId($payload);
            } catch (\Throwable $e) {
                continue;
            }

            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
                DB::table('receiving_reports_table')
                    ->where('receiving_report_id', $reportId)
                    ->update([
                        'receiving_report_form_number' => 'RR-' . $now->format('Y') . '-' . str_pad((string) $reportId, 5, '0', STR_PAD_LEFT),
                    ]);
            }

            if (Schema::hasTable('receiving_report_items_table')) {
                foreach ($this->atpItems((int) $atp->authority_purchase_id) as $item) {
                    try {
                        DB::table('receiving_report_items_table')->insert($this->onlyExisting('receiving_report_items_table', [
                            'receiving_report_id' => $reportId,
                            'receiving_report_item_article' => $item->atp_description,
                            'receiving_report_item_quantity' => $item->atp_quantity,
                            'receiving_report_item_unit' => $item->atp_unit,
                            'receiving_report_item_unit_price' => $item->atp_unit_price ?? null,
                            'receiving_report_item_amount' => $item->atp_amount,
                        ]));
                    } catch (\Throwable $e) {
                        // Keep the report even if a line item cannot be copied yet.
                    }
                }
            }

            $this->writeLog($reportId, (int) $atp->authority_purchase_id, 'Receiving report opened', 'Pending receiving report created from approved ATP.', Auth::id());
        }
    }

    private function approvedAtp(int $atpId)
    {
        return DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->where('authority_purchase_status', 'Approved')
            ->first();
    }

    private function reportForAtp(int $atpId)
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return null;
        }

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
            return DB::table('receiving_reports_table')->where('receiving_report_atp_id', $atpId)->first();
        }

        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        $procurementRequestId = $this->procurementRequestIdForAtp($atp);
        if (!$procurementRequestId || !Schema::hasColumn('receiving_reports_table', 'receiving_report_procurement_request_id')) {
            return null;
        }

        return DB::table('receiving_reports_table')
            ->where('receiving_report_procurement_request_id', $procurementRequestId)
            ->orderByDesc('receiving_report_id')
            ->first();
    }

    private function procurementRequestIdForAtp($atp): ?int
    {
        if (!$atp || empty($atp->authority_purchase_ris_id)) {
            return null;
        }

        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $atp->authority_purchase_ris_id)->first();

        return $ris && !empty($ris->ris_procurement_request_id) ? (int) $ris->ris_procurement_request_id : null;
    }

    private function insertEquipment($atp, $item, $now): ?int
    {
        if (!Schema::hasTable('equipment_table')) {
            return null;
        }

        $equipment = [
            'equipment_name' => $item->atp_description ?: 'Received item',
            'equipment_quantity' => (int) ($item->atp_quantity ?: 1),
        ];
        foreach ([
            'equipment_supplier_id' => $atp->authority_purchase_supplier_id,
            'equipment_tracking_mode' => 'Bulk',
            'equipment_condition_status' => 'Good',
            'equipment_inventory_status' => 'Active',
            'equipment_purchase_date' => $atp->authority_purchase_date,
            'equipment_purchase_cost' => $item->atp_amount,
            'equipment_acquired_date' => $now->toDateString(),
            'equipment_created_at' => $now,
        ] as $column => $value) {
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

    private function writeLog(?int $reportId, int $atpId, string $action, ?string $remarks, $officerId): void
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

        if (!in_array($rr->receiving_report_status, ['Submitted', 'Resubmitted', 'Under Review'], true)) {
            return back()->with('error', 'Only submitted Receiving Reports can be reviewed.');
        }

        return $rr;
    }
}
