<?php

namespace App\Http\Controllers;

use App\Support\ProcurementPaymentPath;
use App\Support\ProcurementRecordCompiler;
use App\Support\PurchaserDocumentAccess;
use App\Support\WorkflowNotifier;
use App\Services\AtpFormExporter;
use App\Services\DocumentWorkflowService;
use App\Services\LiquidationReportExporter;
use App\Services\ReceivingReportFormExporter;
use App\Services\RfcFormExporter;
use App\Services\RisFormExporter;
use App\Http\Controllers\AuthorityToPurchaseController;
use App\Http\Controllers\LiquidationReportController;
use App\Http\Controllers\ReceivingReportController;
use App\Http\Controllers\RequestForCheckController;
use App\Http\Controllers\RisController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcurementRecordPackageController extends Controller
{
    public function index(Request $request)
    {
        $archiveView = $request->query('view') === 'archive';

        $atpQuery = DB::table('authority_to_purchase_table')
            ->leftJoin(
                'requisition_issue_slip_table',
                'authority_to_purchase_table.authority_purchase_ris_id',
                '=',
                'requisition_issue_slip_table.ris_id'
            )
            ->where('authority_to_purchase_table.authority_purchase_status', 'Approved')
            ->where(function ($q) {
                $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                    ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
            })
            ->whereNotNull('authority_to_purchase_table.authority_purchase_payment_path')
            ->select(
                'authority_to_purchase_table.authority_purchase_id',
                'authority_to_purchase_table.authority_purchase_form_number',
                'authority_to_purchase_table.authority_purchase_payment_path',
                'requisition_issue_slip_table.ris_form_number'
            )
            ->orderByDesc('authority_to_purchase_table.authority_purchase_id');

        PurchaserDocumentAccess::scopeOwned($atpQuery, 'atp', 'authority_to_purchase_table');

        $eligibleAtps = $atpQuery->limit(100)->get();

        $packages = collect();
        if (Schema::hasTable('procurement_record_packages_table')) {
            $packageQuery = DB::table('procurement_record_packages_table')
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
                ->select(
                    'procurement_record_packages_table.*',
                    'authority_to_purchase_table.authority_purchase_form_number',
                    'requisition_issue_slip_table.ris_form_number'
                );

            if (Schema::hasColumn('procurement_record_packages_table', 'package_is_archived')) {
                $packageQuery->where('procurement_record_packages_table.package_is_archived', $archiveView ? 1 : 0);
            }

            PurchaserDocumentAccess::scopeOwned($packageQuery, 'atp', 'authority_to_purchase_table');

            $packages = $packageQuery
                ->orderByDesc('procurement_record_packages_table.package_id')
                ->paginate(10)
                ->withQueryString();
        }

        $checklists = [];
        foreach ($eligibleAtps as $atp) {
            $checklists[(int) $atp->authority_purchase_id] = ProcurementRecordCompiler::checklistForAtp((int) $atp->authority_purchase_id);
        }

        $existingAtpIds = $packages instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $packages->getCollection()->pluck('package_authority_purchase_id')->map(fn ($id) => (int) $id)->all()
            : [];

        return view('purchaser.procurement-records.index', [
            'packages' => $packages,
            'eligibleAtps' => $eligibleAtps,
            'checklists' => $checklists,
            'existingAtpIds' => $existingAtpIds,
            'archiveView' => $archiveView,
            'viewAtpId' => (int) $request->query('view_atp', 0) ?: null,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('procurement_record_packages_table')) {
            return back()->with('error', 'Compiled records are not available in this environment.');
        }

        $validated = $request->validate([
            'package_authority_purchase_id' => ['required', 'integer', 'exists:authority_to_purchase_table,authority_purchase_id'],
        ]);

        $atpId = (int) $validated['package_authority_purchase_id'];
        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }
        PurchaserDocumentAccess::assertOwns($atp, 'atp');

        if (!ProcurementRecordCompiler::isWorkflowComplete($atpId)) {
            return back()->with('error', 'Complete all required workflow documents before submitting a compiled record.');
        }

        $exists = DB::table('procurement_record_packages_table')
            ->where('package_authority_purchase_id', $atpId)
            ->where('package_is_archived', 0)
            ->whereNotIn('package_status', ['archived'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'A compiled record for this ATP has already been submitted.');
        }

        $checklist = ProcurementRecordCompiler::checklistForAtp($atpId);
        $now = now();

        $packageId = DB::table('procurement_record_packages_table')->insertGetId([
            'package_authority_purchase_id' => $atpId,
            'package_payment_path' => $atp->authority_purchase_payment_path ?? null,
            'package_status' => 'submitted_to_accounting',
            'package_checklist' => json_encode($checklist),
            'package_submitted_by' => auth()->id(),
            'package_submitted_to_accounting_at' => $now,
            'package_is_archived' => 0,
            'package_created_at' => $now,
            'package_updated_at' => $now,
        ]);

        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_ACCOUNTING,
            'Compiled procurement record submitted',
            ($atp->authority_purchase_form_number ?? ('ATP #' . $atpId)) . ' record package is ready for review.',
            'procurement_package_submitted',
            'PACKAGE',
            (int) $packageId,
            '/accounting/procurement-records/' . $packageId
        );

        return redirect()->route('purchaser.procurement-records.index')
            ->with('success', 'Compiled record submitted to Accounting.');
    }

    public function accountingIndex(Request $request)
    {
        if (!Schema::hasTable('procurement_record_packages_table')) {
            return view('accounting.procurement-records.index', ['packages' => collect()]);
        }

        $packages = DB::table('procurement_record_packages_table')
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
            ->where('procurement_record_packages_table.package_is_archived', 0)
            ->select(
                'procurement_record_packages_table.*',
                'authority_to_purchase_table.authority_purchase_form_number',
                'requisition_issue_slip_table.ris_form_number'
            )
            ->orderByDesc('procurement_record_packages_table.package_id')
            ->paginate(15);

        return view('accounting.procurement-records.index', compact('packages'));
    }

    public function accountingShow($id)
    {
        $package = $this->findPackage($id);
        $this->assertPackageAudience($package, 'accounting');
        $checklist = json_decode($package->package_checklist ?? '[]', true) ?: [];
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $package->package_authority_purchase_id)
            ->first();
        $documentRows = ProcurementRecordCompiler::buildDocumentLinks(
            (int) $package->package_authority_purchase_id,
            'accounting',
            (int) $package->package_id
        );

        return view('accounting.procurement-records.show', compact('package', 'checklist', 'atp', 'documentRows'));
    }

    public function forwardToPresident($id)
    {
        $package = $this->findPackage($id);

        if ($package->package_status !== 'submitted_to_accounting') {
            return back()->with('error', 'Only packages awaiting accounting review can be forwarded.');
        }

        DB::table('procurement_record_packages_table')
            ->where('package_id', $id)
            ->update([
                'package_status' => 'forwarded_to_president',
                'package_forwarded_by' => auth()->id(),
                'package_forwarded_to_president_at' => now(),
                'package_updated_at' => now(),
            ]);

        DocumentWorkflowService::notifySubmitted(
            WorkflowNotifier::ROLE_PRESIDENT,
            'Procurement record forwarded',
            'Compiled procurement record for ATP ' . ($package->authority_purchase_form_number ?? $package->package_authority_purchase_id) . ' is available for records.',
            'procurement_package_forwarded',
            'PACKAGE',
            (int) $id,
            '/president/procurement-records/' . $id
        );

        return back()->with('success', 'Record forwarded to President.');
    }

    public function presidentIndex(Request $request)
    {
        if (!Schema::hasTable('procurement_record_packages_table')) {
            return view('president.procurement-records.index', ['packages' => collect()]);
        }

        $packages = DB::table('procurement_record_packages_table')
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
            ->where('procurement_record_packages_table.package_status', 'forwarded_to_president')
            ->where('procurement_record_packages_table.package_is_archived', 0)
            ->select(
                'procurement_record_packages_table.*',
                'authority_to_purchase_table.authority_purchase_form_number',
                'requisition_issue_slip_table.ris_form_number'
            )
            ->orderByDesc('procurement_record_packages_table.package_forwarded_to_president_at')
            ->paginate(15);

        return view('president.procurement-records.index', compact('packages'));
    }

    public function presidentShow($id)
    {
        $package = $this->findPackage($id);

        if ($package->package_status !== 'forwarded_to_president') {
            abort(404);
        }

        $checklist = json_decode($package->package_checklist ?? '[]', true) ?: [];
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $package->package_authority_purchase_id)
            ->first();
        $documentRows = ProcurementRecordCompiler::buildDocumentLinks(
            (int) $package->package_authority_purchase_id,
            'president',
            (int) $package->package_id
        );

        return view('president.procurement-records.show', compact('package', 'checklist', 'atp', 'documentRows'));
    }

    public function accountingExportDocument($packageId, $type, $docId, $format)
    {
        return $this->exportDocument((int) $packageId, (string) $type, (int) $docId, (string) $format, 'accounting');
    }

    public function presidentExportDocument($packageId, $type, $docId, $format)
    {
        return $this->exportDocument((int) $packageId, (string) $type, (int) $docId, (string) $format, 'president');
    }

    public function accountingDownloadAttachment($packageId, $attachmentId)
    {
        return $this->downloadAttachment((int) $packageId, (int) $attachmentId, 'accounting');
    }

    public function presidentDownloadAttachment($packageId, $attachmentId)
    {
        return $this->downloadAttachment((int) $packageId, (int) $attachmentId, 'president');
    }

    public function accountingViewDocument($packageId, $type, $docId)
    {
        return $this->viewDocument((int) $packageId, (string) $type, (int) $docId, 'accounting');
    }

    public function presidentViewDocument($packageId, $type, $docId)
    {
        return $this->viewDocument((int) $packageId, (string) $type, (int) $docId, 'president');
    }

    private function findPackage($id): object
    {
        $package = DB::table('procurement_record_packages_table')
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
            ->where('procurement_record_packages_table.package_id', $id)
            ->select(
                'procurement_record_packages_table.*',
                'authority_to_purchase_table.authority_purchase_form_number',
                'requisition_issue_slip_table.ris_form_number'
            )
            ->first();

        abort_if(!$package, 404);

        return $package;
    }

    private function assertPackageAudience(object $package, string $audience): void
    {
        if ($audience === 'president') {
            abort_if($package->package_status !== 'forwarded_to_president', 404);
        }
    }

    private function exportDocument(int $packageId, string $type, int $docId, string $format, string $audience): BinaryFileResponse|StreamedResponse
    {
        abort_unless(in_array($format, ['xlsx', 'docx'], true), 404);

        $package = $this->findPackage($packageId);
        $this->assertPackageAudience($package, $audience);

        $atpId = (int) $package->package_authority_purchase_id;
        ProcurementRecordCompiler::assertDocumentBelongsToPackage($atpId, $type, $docId);

        return match ($type) {
            'ris' => $format === 'xlsx'
                ? app(RisController::class)->exportExcel($docId, app(RisFormExporter::class))
                : app(RisController::class)->exportWord($docId, app(RisFormExporter::class)),
            'atp' => $format === 'xlsx'
                ? app(AuthorityToPurchaseController::class)->exportExcel($docId, app(AtpFormExporter::class))
                : app(AuthorityToPurchaseController::class)->exportWord($docId, app(AtpFormExporter::class)),
            'rfc' => $format === 'xlsx'
                ? app(RequestForCheckController::class)->exportExcel($docId, app(RfcFormExporter::class))
                : app(RequestForCheckController::class)->exportWord($docId, app(RfcFormExporter::class)),
            'rr' => $format === 'xlsx'
                ? app(ReceivingReportController::class)->exportExcel($docId, app(ReceivingReportFormExporter::class))
                : app(ReceivingReportController::class)->exportWord($docId, app(ReceivingReportFormExporter::class)),
            'liq' => $format === 'xlsx'
                ? app(LiquidationReportController::class)->exportExcel($docId, app(LiquidationReportExporter::class))
                : app(LiquidationReportController::class)->exportWord($docId, app(LiquidationReportExporter::class)),
            default => abort(404),
        };
    }

    private function viewDocument(int $packageId, string $type, int $docId, string $audience)
    {
        abort_unless(in_array($type, ['ris', 'atp', 'rfc', 'rr', 'liq'], true), 404);

        $package = $this->findPackage($packageId);
        $this->assertPackageAudience($package, $audience);

        $atpId = (int) $package->package_authority_purchase_id;
        ProcurementRecordCompiler::assertDocumentBelongsToPackage($atpId, $type, $docId);

        $payload = $this->loadDocumentViewPayload($type, $docId, $package);
        $payload['type'] = $type;

        return view('procurement-records.document-view', $payload);
    }

    private function loadDocumentViewPayload(string $type, int $docId, object $package): array
    {
        return match ($type) {
            'ris' => $this->loadRisViewPayload($docId),
            'atp' => $this->loadAtpViewPayload($docId),
            'rfc' => $this->loadRfcViewPayload($docId),
            'rr' => $this->loadRrViewPayload($docId, $package),
            'liq' => $this->loadLiqViewPayload($docId),
            default => abort(404),
        };
    }

    private function loadRisViewPayload(int $risId): array
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        abort_if(!$ris, 404);

        $risItems = $this->risItemsWithLookups([$risId])->values();

        return [
            'title' => 'RIS ' . ($ris->ris_form_number ?? $risId),
            'ris' => $ris,
            'risItems' => $risItems,
            'presidentName' => Auth::user()->user_full_name ?? 'President',
        ];
    }

    private function loadAtpViewPayload(int $atpId): array
    {
        $atp = DB::table('authority_to_purchase_table')
            ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
            ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
            ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
            ->where('authority_to_purchase_table.authority_purchase_id', $atpId)
            ->select(
                'authority_to_purchase_table.*',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            )
            ->first();
        abort_if(!$atp, 404);

        $items = DB::table('authority_to_purchase_items_table')
            ->where('authority_purchase_id', $atpId)
            ->orderBy('atp_item_id')
            ->get();

        return [
            'title' => 'ATP ' . ($atp->authority_purchase_form_number ?? $atpId),
            'atp' => $atp,
            'items' => $items,
        ];
    }

    private function loadRfcViewPayload(int $rfcId): array
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        abort_if(!$rfc, 404);

        return [
            'title' => ($rfc->request_check_form_number ?? 'RFC #' . $rfcId),
            'rfc' => $rfc,
        ];
    }

    private function loadRrViewPayload(int $rrId, object $package): array
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->first();
        abort_if(!$rr, 404);

        $rows = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rrId)
            ->orderBy('receiving_report_item_id')
            ->get();

        return [
            'title' => 'RR ' . ($rr->receiving_report_form_number ?? $rrId),
            'rr' => $rr,
            'rows' => $rows,
            'allowMultiSupplier' => ProcurementPaymentPath::allowsMultiSupplier($package->package_payment_path ?? null),
        ];
    }

    private function loadLiqViewPayload(int $liqId): array
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $liqId)->first();
        abort_if(!$liq, 404);

        $items = DB::table('liquidation_report_items_table')
            ->where('liquidation_report_id', $liqId)
            ->orderBy('liquidation_item_id')
            ->get();

        return [
            'title' => 'Liquidation ' . ($liq->liquidation_report_form_number ?? $liqId),
            'liq' => $liq,
            'items' => $items,
        ];
    }

    private function risItemsWithLookups(array $risIds)
    {
        $query = DB::table('requisition_issue_slip_items_table')
            ->whereIn('requisition_issue_slip_items_table.ris_id', $risIds)
            ->orderBy('ris_item_id');

        $select = ['requisition_issue_slip_items_table.*'];

        if (Schema::hasTable('uom_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_uom_id')) {
            $query->leftJoin('uom_table', 'uom_table.uom_id', '=', 'requisition_issue_slip_items_table.ris_item_uom_id');
            $select[] = 'uom_table.uom_name';
        }

        if (Schema::hasTable('brands_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_brand_id')) {
            $query->leftJoin('brands_table', 'brands_table.brand_id', '=', 'requisition_issue_slip_items_table.ris_item_brand_id');
            $select[] = 'brands_table.brand_name';
        }

        if (Schema::hasTable('suppliers_table') && Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_supplier_id')) {
            $query
                ->leftJoin('suppliers_table', 'suppliers_table.supplier_id', '=', 'requisition_issue_slip_items_table.ris_item_supplier_id')
                ->leftJoin('physical_suppliers_table', 'physical_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id')
                ->leftJoin('online_suppliers_table', 'online_suppliers_table.supplier_id', '=', 'suppliers_table.supplier_id');

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

    private function downloadAttachment(int $packageId, int $attachmentId, string $audience)
    {
        abort_unless(Schema::hasTable('ris_attachments_table'), 404);

        $package = $this->findPackage($packageId);
        $this->assertPackageAudience($package, $audience);

        $risId = ProcurementRecordCompiler::assertAttachmentBelongsToPackage(
            (int) $package->package_authority_purchase_id,
            $attachmentId
        );

        $file = DB::table('ris_attachments_table')
            ->where('ris_id', $risId)
            ->where('ris_attachment_id', $attachmentId)
            ->first();
        abort_if(!$file, 404);

        $path = storage_path('app/public/' . $file->ris_attachment_path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $file->ris_attachment_original_name . '"',
        ]);
    }
}
