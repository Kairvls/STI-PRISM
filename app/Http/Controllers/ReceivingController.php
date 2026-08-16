<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

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

        $calendarEvents = collect();
        $calendarEventsByDate = [];
        try {
            if (Schema::hasTable('maintenance_schedules_table')) {
                $calendarEvents = DB::table('maintenance_schedules_table')
                    ->leftJoin('equipment_table', 'maintenance_schedules_table.maintenance_schedule_equipment_id', '=', 'equipment_table.equipment_id')
                    ->select(
                        'maintenance_schedules_table.*',
                        'equipment_table.equipment_name'
                    )
                    ->where(function ($q) {
                        $q->where('maintenance_schedules_table.maintenance_schedule_status', 'Active')
                            ->orWhere('maintenance_schedules_table.maintenance_schedule_status', 'Overdue');
                    })
                    ->orderBy('maintenance_schedules_table.maintenance_schedule_next_date')
                    ->limit(20)
                    ->get();

                foreach ($calendarEvents as $evt) {
                    $dateKey = $evt->maintenance_schedule_next_date
                        ? \Carbon\Carbon::parse($evt->maintenance_schedule_next_date)->format('Y-m-d')
                        : null;
                    if ($dateKey) {
                        $calendarEventsByDate[$dateKey][] = $evt;
                    }
                }
            }
        } catch (\Throwable $e) {
            $calendarEvents = collect();
            $calendarEventsByDate = [];
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

    public function reports(Request $request): View
    {
        $pending = $this->pendingRows();
        $selectedId = (int) $request->query('atp', 0);
        $selected = $pending->firstWhere('authority_purchase_id', $selectedId) ?? $pending->first();
        $items = $selected ? $this->atpItems((int) $selected->authority_purchase_id) : collect();

        $pending = $this->filterRows($pending, $request, ['ris_form_number', 'authority_purchase_form_number', 'item_names', 'supplier_name', 'authority_purchase_reference_po_no'], 'authority_purchase_date');

        return view('receiving-officer.receiving-reports.index', $this->withQueryError([
            'pending' => $pending,
            'selected' => $selected,
            'items' => $items,
            'checklist' => self::CHECKLIST,
            'readyCount' => $pending->filter(fn ($row) => ($row->receiving_report_status ?? null) !== 'Returned')->count(),
            'returnedCount' => $pending->where('receiving_report_status', 'Returned')->count(),
            'filters' => $this->filterInput($request),
        ]));
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
        $receivedQty = $request->input('received_qty', []);
        if (!is_array($receivedQty)) {
            $receivedQty = [];
        }

        foreach ($atpItems as $item) {
            $itemId = (int) ($item->atp_item_id ?? 0);
            $expected = (int) ($item->atp_quantity ?? 0);
            $received = (int) ($receivedQty[$itemId] ?? $receivedQty[(string) $itemId] ?? -1);
            if ($received !== $expected) {
                return back()->withInput()->with(
                    'error',
                    'Delivered quantity does not match the purchaser ATP. Return the delivery for correction if the goods do not match.'
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
        $rows = $this->filterRows(
            $this->acceptedRows(),
            $request,
            ['ris_form_number', 'authority_purchase_form_number', 'item_names', 'supplier_name', 'official_receipt', 'officer_name'],
            'received_at'
        );

        return view('receiving-officer.receiving-reports.delivered-items', $this->withQueryError([
            'rows' => $rows,
            'filters' => $this->filterInput($request),
        ]));
    }

    public function inventoryUpdate(Request $request): View
    {
        $items = $this->filterRows(
            $this->inventoryItems(),
            $request,
            ['receiving_report_item_article', 'receiving_item_description'],
            'receiving_report_date'
        );

        return view('receiving-officer.receiving-reports.inventory-update', $this->withQueryError([
            'items' => $items,
            'filters' => $this->filterInput($request),
        ]));
    }

    public function officialReceipts(Request $request): View
    {
        $rows = $this->filterRows(
            $this->acceptedRows()->filter(fn ($row) => !empty($row->official_receipt))->values(),
            $request,
            ['official_receipt', 'ris_form_number', 'authority_purchase_form_number', 'supplier_name'],
            'received_at'
        );

        return view('receiving-officer.receiving-reports.official-receipts', $this->withQueryError([
            'rows' => $rows,
            'filters' => $this->filterInput($request),
        ]));
    }

    public function supplierRecords(Request $request): View
    {
        $suppliers = $this->filterRows(
            $this->supplierRows(),
            $request,
            ['supplier_name', 'contact_person', 'company_address', 'supplier_store_type'],
            'last_delivery'
        );

        return view('receiving-officer.receiving-reports.supplier-records', $this->withQueryError([
            'suppliers' => $suppliers,
            'filters' => $this->filterInput($request),
        ]));
    }

    public function history(Request $request): View
    {
        $rows = $this->filterRows(
            $this->acceptedRows()
                ->merge($this->returnedRows())
                ->sortByDesc(fn ($row) => $row->received_at ?? $row->authority_purchase_id)
                ->values(),
            $request,
            ['ris_form_number', 'authority_purchase_form_number', 'item_names', 'supplier_name', 'official_receipt', 'officer_name'],
            'received_at'
        );

        return view('receiving-officer.receiving-reports.receiving-history', $this->withQueryError([
            'rows' => $rows,
            'filters' => $this->filterInput($request),
        ]));
    }

    public function receivingLogs(Request $request): View
    {
        $logs = $this->filterRows(
            $this->logRows(200),
            $request,
            ['receiving_log_action', 'receiving_log_remarks', 'officer_name', 'ris_form_number', 'authority_purchase_form_number'],
            'receiving_log_created_at'
        );

        return view('receiving-officer.receiving-reports.receiving-logs', $this->withQueryError([
            'logs' => $logs,
            'filters' => $this->filterInput($request),
        ]));
    }

    public function printReport(int $reportId)
    {
        abort_unless(Schema::hasTable('receiving_reports_table'), 404);

        $row = $this->acceptedRows()->firstWhere('receiving_report_id', $reportId)
            ?? $this->returnedRows()->firstWhere('receiving_report_id', $reportId);

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
}
