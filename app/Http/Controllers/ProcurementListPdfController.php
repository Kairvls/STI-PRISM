<?php

namespace App\Http\Controllers;

use App\Services\DocumentWorkflowService;
use App\Support\BackOrders;
use App\Support\PurchaserDocumentAccess;
use App\Support\RisWorkflow;
use App\Support\TablePdfExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ProcurementListPdfController extends Controller
{
    public function ris(Request $request): Response
    {
        $archiveView = $request->query('view') === 'archive';
        $query = DB::table('requisition_issue_slip_table')
            ->leftJoin('procurement_requests_table', 'requisition_issue_slip_table.ris_procurement_request_id', '=', 'procurement_requests_table.procurement_request_id')
            ->leftJoin('reports_table', 'procurement_requests_table.procurement_request_report_id', '=', 'reports_table.report_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->select(
                'requisition_issue_slip_table.ris_id',
                'requisition_issue_slip_table.ris_form_number',
                'requisition_issue_slip_table.ris_status',
                'requisition_issue_slip_table.ris_request_type',
                'requisition_issue_slip_table.ris_submitted_at',
                'requisition_issue_slip_table.ris_created_at',
                'equipment_table.equipment_name',
                'reports_table.report_unlisted_equipment_name'
            );

        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            $query->addSelect('requisition_issue_slip_table.ris_request_no');
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'requisition_issue_slip_table.ris_is_archived',
                $archiveView
            );
        } elseif ($archiveView) {
            $query->whereRaw('0 = 1');
        }

        PurchaserDocumentAccess::scopeOwned($query, 'ris', 'requisition_issue_slip_table');
        $this->applySearch($query, $request, [
            'requisition_issue_slip_table.ris_form_number',
            'requisition_issue_slip_table.ris_request_no',
            'equipment_table.equipment_name',
            'reports_table.report_unlisted_equipment_name',
        ]);
        if ($request->filled('status')) {
            $query->where('requisition_issue_slip_table.ris_status', $request->query('status'));
        }
        if (
            $request->filled('urgency')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_urgency')
            && in_array((string) $request->query('urgency'), RisWorkflow::urgencyOptions(), true)
        ) {
            $query->where(
                'requisition_issue_slip_table.ris_urgency',
                RisWorkflow::normalizeUrgency($request->query('urgency'))
            );
        }
        $filterDate = trim((string) $request->query('date', $request->query('date_from', '')));
        if ($filterDate !== '') {
            $query->whereDate('requisition_issue_slip_table.ris_created_at', $filterDate);
        }

        $rows = $query->orderByDesc('requisition_issue_slip_table.ris_id')->limit(1000)->get()->map(function ($row) {
            return [
                (string) ($row->ris_form_number ?: ('RIS-'.$row->ris_id)),
                (string) ($row->ris_request_no ?? '—'),
                (string) ($row->equipment_name ?: ($row->report_unlisted_equipment_name ?: '—')),
                (string) ($row->ris_request_type ?: '—'),
                TablePdfExport::date($row->ris_submitted_at ?: $row->ris_created_at),
                (string) ($row->ris_status ?: '—'),
            ];
        })->all();

        return TablePdfExport::download(
            $archiveView ? 'RIS — Archive' : 'RIS — Active',
            ['RIS No.', 'Request No.', 'Equipment', 'Type', 'Date', 'Status'],
            $rows,
            'ris-'.($archiveView ? 'archive' : 'active').'-'.now()->format('Ymd-His').'.pdf'
        );
    }

    public function atp(Request $request): Response
    {
        $archiveView = $request->query('view') === 'archive';
        $query = DB::table('authority_to_purchase_table')
            ->leftJoin('requisition_issue_slip_table', 'authority_to_purchase_table.authority_purchase_ris_id', '=', 'requisition_issue_slip_table.ris_id')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_request_no',
                'authority_to_purchase_table.authority_purchase_status',
                'authority_to_purchase_table.authority_purchase_created_at',
                'authority_to_purchase_table.authority_purchase_submitted_at',
                'requisition_issue_slip_table.ris_form_number',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            );

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'authority_to_purchase_table.authority_purchase_is_archived',
                $archiveView
            );
        } elseif ($archiveView) {
            $query->whereRaw('0 = 1');
        }

        PurchaserDocumentAccess::scopeOwned($query, 'atp', 'authority_to_purchase_table');
        $this->applySearch($query, $request, [
            'authority_to_purchase_table.authority_purchase_form_number',
            'authority_to_purchase_table.authority_purchase_request_no',
            'requisition_issue_slip_table.ris_form_number',
            'physical_suppliers_table.company_name',
            'online_suppliers_table.shop_name',
        ]);
        if ($request->filled('status')) {
            $query->where('authority_to_purchase_table.authority_purchase_status', $request->query('status'));
        }

        $totals = $this->atpTotals();
        $rows = $query->orderByDesc('authority_to_purchase_table.authority_purchase_id')->limit(1000)->get()->map(function ($row) use ($totals) {
            $id = (int) $row->authority_purchase_id;

            return [
                (string) ($row->authority_purchase_form_number ?: ('ATP-'.$id)),
                (string) ($row->authority_purchase_request_no ?: '—'),
                (string) ($row->ris_form_number ?: '—'),
                (string) ($row->company_name ?: ($row->shop_name ?: '—')),
                TablePdfExport::money($totals[$id] ?? null),
                TablePdfExport::date($row->authority_purchase_submitted_at ?: $row->authority_purchase_created_at),
                (string) ($row->authority_purchase_status ?: '—'),
            ];
        })->all();

        return TablePdfExport::download(
            $archiveView ? 'Authority to Purchase — Archive' : 'Authority to Purchase — Active',
            ['ATP No.', 'Request No.', 'RIS', 'Supplier', 'Amount', 'Date', 'Status'],
            $rows,
            'atp-'.($archiveView ? 'archive' : 'active').'-'.now()->format('Ymd-His').'.pdf',
            4
        );
    }

    public function rfc(Request $request): Response
    {
        $archiveView = $request->query('view') === 'archive';
        $query = DB::table('request_check_table')
            ->leftJoin('authority_to_purchase_table', 'request_check_table.request_check_authority_purchase_id', '=', 'authority_to_purchase_table.authority_purchase_id')
            ->select(
                'request_check_table.request_check_id',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_payee',
                'request_check_table.request_check_amount_figures',
                'request_check_table.request_check_status',
                'request_check_table.request_check_funding_source',
                'request_check_table.request_check_date',
                'request_check_table.request_check_created_at',
                'authority_to_purchase_table.authority_purchase_form_number'
            );

        if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'request_check_table.request_check_is_archived',
                $archiveView
            );
        } elseif ($archiveView) {
            $query->whereRaw('0 = 1');
        }

        PurchaserDocumentAccess::scopeOwned($query, 'rfc', 'request_check_table');
        $this->applySearch($query, $request, [
            'request_check_table.request_check_form_number',
            'request_check_table.request_check_payee',
            'authority_to_purchase_table.authority_purchase_form_number',
        ]);
        if ($request->filled('status')) {
            $query->where('request_check_table.request_check_status', $request->query('status'));
        }
        if ($request->filled('fund')) {
            $query->where('request_check_table.request_check_funding_source', $request->query('fund'));
        }

        $rows = $query->orderByDesc('request_check_table.request_check_id')->limit(1000)->get()->map(function ($row) {
            return [
                (string) ($row->request_check_form_number ?: ('RFC-'.$row->request_check_id)),
                (string) ($row->authority_purchase_form_number ?: '—'),
                (string) ($row->request_check_payee ?: '—'),
                (string) ($row->request_check_funding_source ?: '—'),
                TablePdfExport::money($row->request_check_amount_figures),
                TablePdfExport::date($row->request_check_date ?: $row->request_check_created_at),
                (string) ($row->request_check_status ?: '—'),
            ];
        })->all();

        return TablePdfExport::download(
            $archiveView ? 'Funding Requests — Archive' : 'Funding Requests — Active',
            ['RFC No.', 'ATP', 'Payee', 'Funding', 'Amount', 'Date', 'Status'],
            $rows,
            'rfc-'.($archiveView ? 'archive' : 'active').'-'.now()->format('Ymd-His').'.pdf',
            4
        );
    }

    public function rr(Request $request): Response
    {
        $archiveView = $request->query('view') === 'archive';
        $query = DB::table('receiving_reports_table')
            ->leftJoin('request_check_table', 'receiving_reports_table.receiving_report_request_check_id', '=', 'request_check_table.request_check_id')
            ->select(
                'receiving_reports_table.receiving_report_id',
                'receiving_reports_table.receiving_report_form_number',
                'receiving_reports_table.receiving_report_status',
                'receiving_reports_table.receiving_report_delivery_date',
                'receiving_reports_table.receiving_report_created_at',
                'receiving_reports_table.receiving_report_received_from',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_amount_figures'
            );

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'receiving_reports_table.receiving_report_is_archived',
                $archiveView
            );
        } elseif ($archiveView) {
            $query->whereRaw('0 = 1');
        }

        PurchaserDocumentAccess::scopeOwned($query, 'rr', 'receiving_reports_table');
        $this->applySearch($query, $request, [
            'receiving_reports_table.receiving_report_form_number',
            'receiving_reports_table.receiving_report_received_from',
            'request_check_table.request_check_form_number',
        ]);
        if ($request->filled('status')) {
            $query->where('receiving_reports_table.receiving_report_status', $request->query('status'));
        }

        $rows = $query->orderByDesc('receiving_reports_table.receiving_report_id')->limit(1000)->get()->map(function ($row) {
            return [
                (string) ($row->receiving_report_form_number ?: ('RR-'.$row->receiving_report_id)),
                (string) ($row->request_check_form_number ?: '—'),
                (string) ($row->receiving_report_received_from ?: '—'),
                TablePdfExport::money($row->request_check_amount_figures),
                TablePdfExport::date($row->receiving_report_delivery_date ?: $row->receiving_report_created_at),
                (string) ($row->receiving_report_status ?: '—'),
            ];
        })->all();

        return TablePdfExport::download(
            $archiveView ? 'Receiving Reports — Archive' : 'Receiving Reports — Active',
            ['RR No.', 'RFC', 'Received From', 'Amount', 'Date', 'Status'],
            $rows,
            'rr-'.($archiveView ? 'archive' : 'active').'-'.now()->format('Ymd-His').'.pdf',
            3
        );
    }

    public function backOrders(Request $request): Response
    {
        abort_unless(BackOrders::supported(), 404);

        $filter = $request->query('status', 'unresolved');
        $query = DB::table('back_orders_table as bo')
            ->leftJoin('receiving_reports_table as root', 'bo.back_order_root_receiving_report_id', '=', 'root.receiving_report_id')
            ->select(
                'bo.back_order_id',
                'bo.back_order_number',
                'bo.back_order_article',
                'bo.back_order_type',
                'bo.back_order_status',
                'bo.back_order_supplier_name',
                'bo.back_order_unit_price',
                'bo.back_order_quantity',
                'bo.back_order_created_at',
                'root.receiving_report_form_number'
            );

        if ($filter === 'unresolved') {
            $query->whereIn('bo.back_order_status', BackOrders::UNRESOLVED);
        } elseif ($filter === 'resolved') {
            $query->whereNotIn('bo.back_order_status', BackOrders::UNRESOLVED);
        } elseif (in_array($filter, [BackOrders::STATUS_WAITING_RESTOCK, BackOrders::STATUS_REFUNDED, BackOrders::STATUS_RECEIVING], true)) {
            $query->where('bo.back_order_status', $filter);
        }

        if ($request->filled('search')) {
            $like = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($q) use ($like) {
                $q->where('bo.back_order_article', 'like', $like)
                    ->orWhere('bo.back_order_number', 'like', $like)
                    ->orWhere('bo.back_order_supplier_name', 'like', $like)
                    ->orWhere('root.receiving_report_form_number', 'like', $like);
            });
        }

        $rows = $query->orderByDesc('bo.back_order_id')->limit(1000)->get()->map(function ($row) {
            return [
                (string) ($row->back_order_number ?: ('BO-'.$row->back_order_id)),
                (string) ($row->receiving_report_form_number ?: '—'),
                (string) ($row->back_order_article ?: '—'),
                (string) ($row->back_order_type ?: '—'),
                (string) ($row->back_order_supplier_name ?: '—'),
                (string) ((int) ($row->back_order_quantity ?? 0)),
                TablePdfExport::money($row->back_order_unit_price),
                TablePdfExport::date($row->back_order_created_at),
                (string) ($row->back_order_status ?: '—'),
            ];
        })->all();

        return TablePdfExport::download(
            'Back Orders — '.ucfirst(str_replace('_', ' ', (string) $filter)),
            ['BO No.', 'Root RR', 'Item', 'Type', 'Supplier', 'Qty', 'Unit Price', 'Date', 'Status'],
            $rows,
            'back-orders-'.$filter.'-'.now()->format('Ymd-His').'.pdf',
            6
        );
    }

    public function liquidation(Request $request): Response
    {
        $archiveView = $request->query('view') === 'archive';
        $query = DB::table('liquidation_reports_table')
            ->leftJoin('receiving_reports_table', 'liquidation_reports_table.liquidation_report_receiving_report_id', '=', 'receiving_reports_table.receiving_report_id')
            ->select(
                'liquidation_reports_table.liquidation_report_id',
                'liquidation_reports_table.liquidation_report_form_number',
                'liquidation_reports_table.liquidation_report_status',
                'liquidation_reports_table.liquidation_report_employee_name',
                'liquidation_reports_table.liquidation_report_amount_advance',
                'liquidation_reports_table.liquidation_report_created_at',
                'liquidation_reports_table.liquidation_report_date_submitted',
                'receiving_reports_table.receiving_report_form_number'
            );

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'liquidation_reports_table.liquidation_report_is_archived',
                $archiveView
            );
        } elseif ($archiveView) {
            $query->whereRaw('0 = 1');
        }

        PurchaserDocumentAccess::scopeOwned($query, 'liq', 'liquidation_reports_table');
        $this->applySearch($query, $request, [
            'liquidation_reports_table.liquidation_report_form_number',
            'liquidation_reports_table.liquidation_report_employee_name',
            'receiving_reports_table.receiving_report_form_number',
        ]);
        if ($request->filled('status')) {
            $query->where('liquidation_reports_table.liquidation_report_status', $request->query('status'));
        }

        $rows = $query->orderByDesc('liquidation_reports_table.liquidation_report_id')->limit(1000)->get()->map(function ($row) {
            return [
                (string) ($row->liquidation_report_form_number ?: ('LR-'.$row->liquidation_report_id)),
                (string) ($row->receiving_report_form_number ?: '—'),
                (string) ($row->liquidation_report_employee_name ?: '—'),
                TablePdfExport::money($row->liquidation_report_amount_advance),
                TablePdfExport::date($row->liquidation_report_date_submitted ?: $row->liquidation_report_created_at),
                (string) ($row->liquidation_report_status ?: '—'),
            ];
        })->all();

        return TablePdfExport::download(
            $archiveView ? 'Liquidation Reports — Archive' : 'Liquidation Reports — Active',
            ['LR No.', 'RR', 'Employee', 'Amount Advance', 'Date', 'Status'],
            $rows,
            'liquidation-'.($archiveView ? 'archive' : 'active').'-'.now()->format('Ymd-His').'.pdf',
            3
        );
    }

    public function suppliers(Request $request): Response
    {
        $query = DB::table('suppliers_table')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->select(
                'suppliers_table.supplier_id',
                'suppliers_table.supplier_code',
                'suppliers_table.supplier_store_type',
                'suppliers_table.supplier_is_active',
                'suppliers_table.supplier_is_blacklisted',
                'physical_suppliers_table.company_name',
                'physical_suppliers_table.contact_person as physical_contact',
                'online_suppliers_table.shop_name',
                'online_suppliers_table.contact_person as online_contact',
                'online_suppliers_table.app_used'
            );

        if ($request->filled('search')) {
            $like = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($q) use ($like) {
                $q->where('suppliers_table.supplier_code', 'like', $like)
                    ->orWhere('physical_suppliers_table.company_name', 'like', $like)
                    ->orWhere('online_suppliers_table.shop_name', 'like', $like)
                    ->orWhere('physical_suppliers_table.contact_person', 'like', $like)
                    ->orWhere('online_suppliers_table.contact_person', 'like', $like);
            });
        }
        if ($request->filled('type')) {
            $query->where('suppliers_table.supplier_store_type', $request->query('type'));
        }
        if ($request->filled('status') && in_array($request->query('status'), ['Active', 'Inactive'], true)) {
            $query->where('suppliers_table.supplier_is_active', $request->query('status') === 'Inactive' ? 0 : 1);
        }

        $rows = $query->orderByDesc('suppliers_table.supplier_id')->limit(1000)->get()->map(function ($row) {
            $name = $row->supplier_store_type === 'Online Store'
                ? ($row->shop_name ?: '—')
                : ($row->company_name ?: '—');
            $contact = $row->supplier_store_type === 'Online Store'
                ? ($row->online_contact ?: '—')
                : ($row->physical_contact ?: '—');

            return [
                (string) ($row->supplier_code ?: ('SUP-'.$row->supplier_id)),
                (string) $name,
                (string) ($row->supplier_store_type ?: '—'),
                (string) $contact,
                (string) ($row->app_used ?: '—'),
                ((int) ($row->supplier_is_active ?? 0) === 1) ? 'Active' : 'Inactive',
                ((int) ($row->supplier_is_blacklisted ?? 0) === 1) ? 'Yes' : 'No',
            ];
        })->all();

        return TablePdfExport::download(
            'Suppliers',
            ['Code', 'Name', 'Type', 'Contact', 'Platform', 'Status', 'Blacklisted'],
            $rows,
            'suppliers-'.now()->format('Ymd-His').'.pdf'
        );
    }

    public function procurementRecords(Request $request): Response
    {
        abort_unless(Schema::hasTable('procurement_record_packages_table'), 404);

        $archiveView = $request->query('view') === 'archive';
        $query = DB::table('procurement_record_packages_table')
            ->leftJoin('authority_to_purchase_table', 'procurement_record_packages_table.package_authority_purchase_id', '=', 'authority_to_purchase_table.authority_purchase_id')
            ->select(
                'procurement_record_packages_table.package_id',
                'procurement_record_packages_table.package_status',
                'procurement_record_packages_table.package_payment_path',
                'procurement_record_packages_table.package_submitted_to_accounting_at',
                'procurement_record_packages_table.package_created_at',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_id'
            );

        if (Schema::hasColumn('procurement_record_packages_table', 'package_is_archived')) {
            $query->where('procurement_record_packages_table.package_is_archived', $archiveView ? 1 : 0);
        }

        PurchaserDocumentAccess::scopeOwned($query, 'atp', 'authority_to_purchase_table');

        if ($request->filled('search')) {
            $like = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($q) use ($like) {
                $q->where('authority_to_purchase_table.authority_purchase_form_number', 'like', $like)
                    ->orWhere('procurement_record_packages_table.package_payment_path', 'like', $like);
            });
        }

        $totals = $this->atpTotals();
        $rows = $query->orderByDesc('procurement_record_packages_table.package_id')->limit(1000)->get()->map(function ($row) use ($totals) {
            $atpId = (int) ($row->authority_purchase_id ?? 0);

            return [
                (string) ($row->package_id),
                (string) ($row->authority_purchase_form_number ?: '—'),
                (string) str_replace('_', ' ', ucfirst((string) ($row->package_payment_path ?: '—'))),
                TablePdfExport::money($totals[$atpId] ?? null),
                TablePdfExport::date($row->package_submitted_to_accounting_at ?: $row->package_created_at),
                (string) str_replace('_', ' ', ucfirst((string) ($row->package_status ?: '—'))),
            ];
        })->all();

        return TablePdfExport::download(
            $archiveView ? 'Procurement Records — Archive' : 'Procurement Records — Active',
            ['Package ID', 'ATP', 'Payment Path', 'Amount', 'Date', 'Status'],
            $rows,
            'procurement-records-'.($archiveView ? 'archive' : 'active').'-'.now()->format('Ymd-His').'.pdf',
            3
        );
    }

    private function applySearch($query, Request $request, array $columns): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $like = '%'.trim((string) $request->query('search')).'%';
        $query->where(function ($q) use ($like, $columns) {
            foreach ($columns as $i => $column) {
                if (! is_string($column) || $column === '') {
                    continue;
                }
                // Skip columns that may not exist (e.g. optional request_no).
                $parts = explode('.', $column);
                if (count($parts) === 2 && ! Schema::hasColumn($parts[0], $parts[1])) {
                    continue;
                }
                if ($i === 0) {
                    $q->where($column, 'like', $like);
                } else {
                    $q->orWhere($column, 'like', $like);
                }
            }
        });
    }

    /** @return array<int, float> */
    private function atpTotals(): array
    {
        if (! Schema::hasTable('authority_to_purchase_items_table')) {
            return [];
        }

        return DB::table('authority_to_purchase_items_table')
            ->select('authority_purchase_id', DB::raw('SUM(atp_amount) as total'))
            ->groupBy('authority_purchase_id')
            ->pluck('total', 'authority_purchase_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
