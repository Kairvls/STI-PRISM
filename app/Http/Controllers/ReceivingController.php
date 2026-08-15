<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReceivingController extends Controller
{
    public function dashboard(): View
    {
        $pendingRows = $this->dashboardPendingRows();
        $acceptedRows = $this->dashboardAcceptedRows();
        $returnedRows = $this->dashboardReturnedRows();
        $logs = $this->dashboardLogs();
        $suppliers = $this->dashboardSuppliers();

        $pendingAmount = (float) $pendingRows->sum(fn ($row) => (float) ($row->total_amount ?? 0));
        $acceptedMonth = $acceptedRows->filter(function ($row) {
            $date = $row->received_at ?? null;
            return $date && \Carbon\Carbon::parse($date)->isCurrentMonth();
        })->count();

        return view('receiving-officer.dashboard', [
            'pendingCount' => $pendingRows->count(),
            'pendingAmount' => $pendingAmount,
            'acceptedCount' => $acceptedRows->count(),
            'acceptedMonth' => $acceptedMonth,
            'returnedCount' => $returnedRows->count(),
            'supplierCount' => $suppliers->count(),
            'logCount' => $logs->count(),
            'pendingRows' => $pendingRows->take(8),
            'acceptedRows' => $acceptedRows->take(6),
            'returnedRows' => $returnedRows->take(5),
            'recentLogs' => $logs->take(8),
            'topSuppliers' => $suppliers->sortByDesc('delivery_count')->take(5)->values(),
        ]);
    }

    public function reports(): View
    {
        return view('receiving-officer.receiving-reports.index');
    }

    public function deliveredItems(): View
    {
        return view('receiving-officer.receiving-reports.delivered-items');
    }

    public function inventoryUpdate(): View
    {
        return view('receiving-officer.receiving-reports.inventory-update');
    }

    public function officialReceipts(): View
    {
        return view('receiving-officer.receiving-reports.official-receipts');
    }

    public function supplierRecords(): View
    {
        return view('receiving-officer.receiving-reports.supplier-records');
    }

    public function history(): View
    {
        return view('receiving-officer.receiving-reports.receiving-history');
    }

    public function receivingLogs(): View
    {
        return view('receiving-officer.receiving-reports.receiving-logs');
    }

    private function dashboardBaseQuery()
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
            if (Schema::hasColumn('receiving_reports_table', 'receiving_report_accepted_at')) {
                $select[] = DB::raw('receiving_reports_table.receiving_report_accepted_at as received_at');
            } elseif (Schema::hasColumn('receiving_reports_table', 'receiving_report_date')) {
                $select[] = DB::raw('receiving_reports_table.receiving_report_date as received_at');
            } else {
                $select[] = DB::raw('receiving_reports_table.receiving_report_created_at as received_at');
            }
        } else {
            $select[] = DB::raw('NULL as receiving_report_id');
            $select[] = DB::raw('NULL as receiving_report_status');
            $select[] = DB::raw('NULL as official_receipt');
            $select[] = DB::raw('NULL as received_at');
        }

        return $query->select($select);
    }

    private function dashboardPendingRows()
    {
        if (!Schema::hasTable('authority_to_purchase_table')) {
            return collect();
        }

        try {
            $query = $this->dashboardBaseQuery()
                ->where('authority_to_purchase_table.authority_purchase_status', 'Approved');

            if (Schema::hasTable('receiving_reports_table')) {
                $query->where(function ($q) {
                    $q->whereNull('receiving_reports_table.receiving_report_id')
                        ->orWhereIn('receiving_reports_table.receiving_report_status', ['Pending', 'Returned']);
                });
            }

            return $query->orderByDesc('authority_to_purchase_table.authority_purchase_id')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function dashboardAcceptedRows()
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        try {
            return $this->dashboardBaseQuery()
                ->whereIn('receiving_reports_table.receiving_report_status', ['Accepted', 'Completed'])
                ->orderByDesc('receiving_reports_table.receiving_report_id')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function dashboardReturnedRows()
    {
        if (!Schema::hasTable('receiving_reports_table')) {
            return collect();
        }

        try {
            return $this->dashboardBaseQuery()
                ->where('receiving_reports_table.receiving_report_status', 'Returned')
                ->orderByDesc('receiving_reports_table.receiving_report_id')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function dashboardLogs()
    {
        if (!Schema::hasTable('receiving_logs_table')) {
            return collect();
        }

        try {
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
                ->limit(20)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function dashboardSuppliers()
    {
        if (!Schema::hasTable('suppliers_table')) {
            return collect();
        }

        try {
            $hasReports = Schema::hasTable('receiving_reports_table');

            $query = DB::table('suppliers_table')
                ->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');

            if ($hasReports && Schema::hasColumn('receiving_reports_table', 'receiving_report_supplier_id')) {
                $query->leftJoin('receiving_reports_table', function ($join) {
                    $join->on('receiving_reports_table.receiving_report_supplier_id', '=', 'suppliers_table.supplier_id')
                        ->whereIn('receiving_reports_table.receiving_report_status', ['Accepted', 'Completed']);
                });
            }

            return $query
                ->select(
                    'suppliers_table.supplier_id',
                    DB::raw("COALESCE(physical_suppliers_table.company_name, online_suppliers_table.shop_name, 'Unnamed supplier') as supplier_name"),
                    'physical_suppliers_table.contact_person',
                    DB::raw($hasReports && Schema::hasColumn('receiving_reports_table', 'receiving_report_supplier_id')
                        ? 'COUNT(DISTINCT receiving_reports_table.receiving_report_id) as delivery_count'
                        : '0 as delivery_count')
                )
                ->groupBy(
                    'suppliers_table.supplier_id',
                    'physical_suppliers_table.company_name',
                    'online_suppliers_table.shop_name',
                    'physical_suppliers_table.contact_person'
                )
                ->orderBy('supplier_name')
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
