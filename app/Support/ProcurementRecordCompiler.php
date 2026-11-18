<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds checklist packages and determines workflow completion for procurement records.
 */
class ProcurementRecordCompiler
{
    public static function checklistForAtp(int $atpId): array
    {
        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        if (!$atp) {
            return [];
        }

        $path = $atp->authority_purchase_payment_path ?? null;
        $risId = (int) ($atp->authority_purchase_ris_id ?? 0);
        $rfcs = self::activeRfcsForAtp($atpId);
        $rfc = self::primaryRfc($rfcs);
        $rr = self::findReceivingReportForAtp($atpId, $rfcs);

        $liq = null;
        if ($rr && Schema::hasTable('liquidation_reports_table') && ProcurementPaymentPath::requiresLiquidation($path)) {
            $liq = DB::table('liquidation_reports_table')
                ->where('liquidation_report_receiving_report_id', $rr->receiving_report_id)
                ->where('liquidation_report_status', '!=', 'Rejected')
                ->when(
                    Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived'),
                    fn ($q) => $q->where(function ($inner) {
                        $inner->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
                    })
                )
                ->orderByDesc('liquidation_report_id')
                ->first();
        }

        $ris = $risId > 0
            ? DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first()
            : null;

        $fundingLabel = ProcurementPaymentPath::label($path);
        $fundingDocLabel = $path === ProcurementPaymentPath::CASH_ADVANCE ? 'Cash Advance' : 'Request for Check';

        $items = [
            self::item(
                'supporting_docs',
                'Canvassing documents',
                self::hasSupportingDocsForRis($risId),
                $risId ?: null,
                'RIS'
            ),
            self::item('ris', 'RIS', (bool) $ris, $risId ?: null, 'RIS', $ris->ris_form_number ?? null),
            self::item(
                'atp',
                'ATP',
                $atp->authority_purchase_status === 'Approved',
                $atpId,
                'ATP',
                $atp->authority_purchase_form_number ?? null
            ),
            self::item(
                'funding',
                $fundingDocLabel,
                self::hasApprovedFunding($rfcs),
                $rfc ? (int) $rfc->request_check_id : null,
                'RFC',
                $rfc->request_check_form_number ?? null
            ),
        ];

        $dv = self::findDvForRfcs($rfcs);
        if (Schema::hasTable('disbursement_vouchers_table')) {
            // Legacy packages may reach RR without a DV row; treat those as satisfied.
            $dvReady = self::isDvComplete($dv) || ($dv === null && self::isRrComplete($rr));
            $items[] = self::item(
                'dv',
                'Disbursement Voucher',
                $dvReady,
                $dv ? (int) $dv->dv_id : null,
                'DV',
                $dv->dv_form_number ?? null
            );
        }

        $items[] = self::item(
            'rr',
            'Receiving Report',
            self::isRrComplete($rr),
            $rr ? (int) $rr->receiving_report_id : null,
            'RR',
            $rr->receiving_report_form_number ?? null
        );

        if (ProcurementPaymentPath::requiresLiquidation($path)) {
            $items[] = self::item(
                'liq',
                'Liquidation Report',
                $liq && $liq->liquidation_report_status === 'Approved',
                $liq ? (int) $liq->liquidation_report_id : null,
                'LIQ',
                $liq->liquidation_report_form_number ?? null
            );
        }

        return [
            'atp_id' => $atpId,
            'payment_path' => $path,
            'payment_path_label' => $fundingLabel,
            'items' => $items,
            'complete' => self::isChecklistComplete($items, $path),
        ];
    }

    public static function isWorkflowComplete(int $atpId): bool
    {
        $checklist = self::checklistForAtp($atpId);

        return (bool) ($checklist['complete'] ?? false);
    }

    public static function isChecklistComplete(array $items, ?string $path): bool
    {
        foreach ($items as $entry) {
            if (empty($entry['ready'])) {
                return false;
            }
        }

        return ProcurementPaymentPath::isValid($path);
    }

    public static function isRrComplete(?object $rr): bool
    {
        if (!$rr) {
            return false;
        }

        return in_array($rr->receiving_report_status ?? '', ['Completed', 'Accepted'], true);
    }

    private static function item(string $key, string $label, bool $ready, ?int $refId, string $refType, ?string $valueId = null): array
    {
        $valueId = $valueId !== null ? trim($valueId) : '';

        return [
            'key' => $key,
            'label' => $label,
            'ready' => $ready,
            'ref_id' => $refId,
            'ref_type' => $refType,
            'value_id' => $valueId !== '' ? $valueId : null,
        ];
    }

    /**
     * Display PR value id (request no preferred; otherwise ATP form number rewritten as PR).
     */
    public static function prDisplayNumber(?object $atp): ?string
    {
        if (! $atp) {
            return null;
        }

        foreach (['authority_purchase_request_no'] as $column) {
            $value = trim((string) ($atp->{$column} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $risId = (int) ($atp->authority_purchase_ris_id ?? 0);
        if ($risId > 0 && Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            $requestNo = trim((string) (DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->value('ris_request_no') ?? ''));
            if ($requestNo !== '') {
                return $requestNo;
            }
        }

        $form = trim((string) ($atp->authority_purchase_form_number ?? ''));
        if ($form === '') {
            return null;
        }

        return preg_replace('/^ATP\b/i', 'PR', $form) ?: $form;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function activeRfcsForAtp(int $atpId)
    {
        $query = DB::table('request_check_table')
            ->where('request_check_status', '!=', 'Rejected');
        RfcAtpLinks::whereCoversAtp($query, $atpId);

        if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
            $query->where(function ($inner) {
                $inner->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
            });
        }

        return $query->orderByDesc('request_check_id')->get();
    }

    private static function primaryRfc($rfcs): ?object
    {
        if ($rfcs->isEmpty()) {
            return null;
        }

        $approved = $rfcs->first(fn ($row) => ($row->request_check_status ?? '') === 'Approved');

        return $approved ?? $rfcs->first();
    }

    private static function hasApprovedFunding($rfcs): bool
    {
        return $rfcs->contains(fn ($row) => ($row->request_check_status ?? '') === 'Approved');
    }

    private static function findDvForRfcs($rfcs): ?object
    {
        if (! Schema::hasTable('disbursement_vouchers_table') || $rfcs->isEmpty()) {
            return null;
        }

        $rfcIds = $rfcs->pluck('request_check_id')->map(fn ($id) => (int) $id)->all();

        return DB::table('disbursement_vouchers_table')
            ->whereIn('dv_request_check_id', $rfcIds)
            ->when(
                Schema::hasColumn('disbursement_vouchers_table', 'dv_is_archived'),
                fn ($q) => $q->where(function ($inner) {
                    $inner->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                })
            )
            ->orderByDesc('dv_id')
            ->first();
    }

    private static function isDvComplete(?object $dv): bool
    {
        if (! $dv) {
            return false;
        }

        return in_array((string) ($dv->dv_status ?? ''), [
            'Approved',
            'President Approved',
            'Completed',
            'Check Released',
            'Check Collected',
            'Released',
        ], true);
    }

    private static function findReceivingReportForAtp(int $atpId, $rfcs): ?object
    {
        if (!Schema::hasTable('receiving_reports_table') || $rfcs->isEmpty()) {
            return null;
        }

        $rfcIds = $rfcs->pluck('request_check_id')->map(fn ($id) => (int) $id)->all();
        $hasRrAtpColumn = Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id');

        if ($hasRrAtpColumn) {
            $rr = self::receivingReportQuery()
                ->where('receiving_report_atp_id', $atpId)
                ->orderByDesc('receiving_report_id')
                ->get()
                ->first(fn ($row) => self::isRrComplete($row));

            if ($rr) {
                return $rr;
            }
        }

        $rr = self::receivingReportQuery()
            ->whereIn('receiving_report_request_check_id', $rfcIds)
            ->when($hasRrAtpColumn, fn ($q) => $q->whereNull('receiving_report_atp_id'))
            ->orderByDesc('receiving_report_id')
            ->get()
            ->first(fn ($row) => self::isRrComplete($row));

        if ($rr) {
            return $rr;
        }

        if (Schema::hasColumn('request_check_table', 'request_check_receiving_report_id')) {
            $rrIds = $rfcs
                ->pluck('request_check_receiving_report_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($rrIds !== []) {
                $rr = self::receivingReportQuery()
                    ->whereIn('receiving_report_id', $rrIds)
                    ->when($hasRrAtpColumn, fn ($q) => $q->where(function ($own) use ($atpId) {
                        $own->whereNull('receiving_report_atp_id')->orWhere('receiving_report_atp_id', $atpId);
                    }))
                    ->orderByDesc('receiving_report_id')
                    ->get()
                    ->first(fn ($row) => self::isRrComplete($row));

                if ($rr) {
                    return $rr;
                }
            }
        }

        return self::receivingReportQuery()
            ->whereIn('receiving_report_request_check_id', $rfcIds)
            ->when($hasRrAtpColumn, fn ($q) => $q->where(function ($own) use ($atpId) {
                $own->whereNull('receiving_report_atp_id')->orWhere('receiving_report_atp_id', $atpId);
            }))
            ->orderByDesc('receiving_report_id')
            ->first();
    }

    private static function receivingReportQuery()
    {
        return DB::table('receiving_reports_table')
            ->where('receiving_report_status', '!=', 'Returned')
            ->when(
                Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived'),
                fn ($q) => $q->where(function ($inner) {
                    $inner->whereNull('receiving_report_is_archived')->orWhere('receiving_report_is_archived', 0);
                })
            );
    }

    private static function hasSupportingDocsForRis(?int $risId): bool
    {
        if (!$risId || !Schema::hasTable('ris_attachments_table')) {
            return false;
        }

        return DB::table('ris_attachments_table')
            ->where('ris_id', $risId)
            ->exists();
    }

    public static function purchaserUrlForItem(array $item, ?int $atpId = null): ?string
    {
        if (empty($item['ref_id'])) {
            return null;
        }

        $docId = (int) $item['ref_id'];
        $type = match ($item['ref_type'] ?? '') {
            'RIS' => 'ris',
            'ATP' => 'atp',
            'RFC' => 'rfc',
            'DV' => 'dv',
            'RR' => 'rr',
            'LIQ' => 'liq',
            default => null,
        };

        // Prefer paper-form preview in a new tab (same as Accounting compiled records).
        if ($atpId && $atpId > 0 && $type !== null && \Illuminate\Support\Facades\Route::has('purchaser.procurement-records.preview')) {
            return route('purchaser.procurement-records.preview', [
                'atp' => $atpId,
                'type' => $type,
                'docId' => $docId,
            ]);
        }

        return match ($item['ref_type'] ?? '') {
            'RIS' => route('purchaser.ris.index', ['view_ris' => $docId]),
            'ATP' => route('purchaser.atp.index', ['view_atp' => $docId]),
            'RFC' => route('purchaser.rfc.index', ['view_rfc' => $docId]),
            'DV' => RequestExplorer::viewUrlFor('dv', $docId),
            'RR' => route('purchaser.rr.index', ['view_rr' => $docId]),
            'LIQ' => route('purchaser.liq.index', ['view_liq' => $docId]),
            default => null,
        };
    }

    /**
     * Resolved document IDs for an ATP workflow chain.
     *
     * @return array{
     *     atp_id:int,
     *     ris_id:?int,
     *     rfc_id:?int,
     *     dv_id:?int,
     *     rr_id:?int,
     *     liq_id:?int,
     *     rfc_ids:int[],
     *     attachment_ids:int[]
     * }
     */
    public static function resolveDocuments(int $atpId): array
    {
        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        $rfcs = self::activeRfcsForAtp($atpId);
        $rfc = self::primaryRfc($rfcs);
        $dv = self::findDvForRfcs($rfcs);
        $rr = self::findReceivingReportForAtp($atpId, $rfcs);
        $path = $atp->authority_purchase_payment_path ?? null;

        $liq = null;
        if ($rr && Schema::hasTable('liquidation_reports_table') && ProcurementPaymentPath::requiresLiquidation($path)) {
            $liq = DB::table('liquidation_reports_table')
                ->where('liquidation_report_receiving_report_id', $rr->receiving_report_id)
                ->where('liquidation_report_status', '!=', 'Rejected')
                ->when(
                    Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived'),
                    fn ($q) => $q->where(function ($inner) {
                        $inner->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
                    })
                )
                ->orderByDesc('liquidation_report_id')
                ->first();
        }

        $risId = $atp ? (int) ($atp->authority_purchase_ris_id ?? 0) ?: null : null;

        $attachmentIds = [];
        if ($risId && Schema::hasTable('ris_attachments_table')) {
            $attachmentIds = DB::table('ris_attachments_table')
                ->where('ris_id', $risId)
                ->orderBy('ris_attachment_original_name')
                ->pluck('ris_attachment_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [
            'atp_id' => $atpId,
            'ris_id' => $risId,
            'rfc_id' => $rfc ? (int) $rfc->request_check_id : null,
            'dv_id' => $dv ? (int) $dv->dv_id : null,
            'rr_id' => $rr ? (int) $rr->receiving_report_id : null,
            'liq_id' => $liq ? (int) $liq->liquidation_report_id : null,
            'rfc_ids' => $rfcs->pluck('request_check_id')->map(fn ($id) => (int) $id)->all(),
            'attachment_ids' => $attachmentIds,
            'ris_form_number' => $atp && ($atp->authority_purchase_ris_id ?? null)
                ? DB::table('requisition_issue_slip_table')->where('ris_id', $atp->authority_purchase_ris_id)->value('ris_form_number')
                : null,
            'atp_form_number' => $atp->authority_purchase_form_number ?? null,
            'rfc_form_number' => $rfc?->request_check_form_number ?? null,
            'dv_form_number' => $dv?->dv_form_number ?? null,
            'rr_form_number' => $rr?->receiving_report_form_number ?? null,
            'liq_form_number' => $liq?->liquidation_report_form_number ?? null,
            'payment_path' => $path,
        ];
    }

    public static function assertDocumentBelongsToPackage(int $atpId, string $type, int $docId): void
    {
        $resolved = self::resolveDocuments($atpId);

        $allowed = match ($type) {
            'ris' => $resolved['ris_id'],
            'atp' => $resolved['atp_id'],
            'rfc' => in_array($docId, $resolved['rfc_ids'], true) ? $docId : null,
            'dv' => $resolved['dv_id'],
            'rr' => $resolved['rr_id'],
            'liq' => $resolved['liq_id'],
            default => null,
        };

        if ((int) $allowed !== $docId) {
            abort(404);
        }
    }

    /**
     * Best-effort ATP id for a document so paper preview can be resolved without a package.
     */
    public static function resolveAtpIdForDocument(string $type, int $docId): ?int
    {
        if ($docId < 1) {
            return null;
        }

        return match ($type) {
            'atp' => $docId,
            'ris' => (int) (DB::table('authority_to_purchase_table')
                ->where('authority_purchase_ris_id', $docId)
                ->orderByDesc('authority_purchase_id')
                ->value('authority_purchase_id') ?: 0) ?: null,
            'rfc' => (int) (DB::table('request_check_table')
                ->where('request_check_id', $docId)
                ->value('request_check_authority_purchase_id') ?: 0) ?: null,
            'dv' => Schema::hasTable('disbursement_vouchers_table')
                ? ((int) (DB::table('disbursement_vouchers_table as dv')
                    ->leftJoin('request_check_table as rfc', 'rfc.request_check_id', '=', 'dv.dv_request_check_id')
                    ->where('dv.dv_id', $docId)
                    ->value('rfc.request_check_authority_purchase_id') ?: 0) ?: null)
                : null,
            'rr' => (int) (DB::table('receiving_reports_table')
                ->where('receiving_report_id', $docId)
                ->value('receiving_report_atp_id') ?: 0) ?: null,
            'liq' => (function () use ($docId) {
                $rrId = (int) (DB::table('liquidation_reports_table')
                    ->where('liquidation_report_id', $docId)
                    ->value('liquidation_report_receiving_report_id') ?: 0);
                if ($rrId < 1) {
                    return null;
                }

                return (int) (DB::table('receiving_reports_table')
                    ->where('receiving_report_id', $rrId)
                    ->value('receiving_report_atp_id') ?: 0) ?: null;
            })(),
            default => null,
        };
    }

    public static function assertAttachmentBelongsToPackage(int $atpId, int $attachmentId): int
    {
        $resolved = self::resolveDocuments($atpId);
        if (!in_array($attachmentId, $resolved['attachment_ids'], true)) {
            abort(404);
        }

        $risId = DB::table('ris_attachments_table')
            ->where('ris_attachment_id', $attachmentId)
            ->value('ris_id');

        abort_if(!$risId || (int) $risId !== (int) ($resolved['ris_id'] ?? 0), 404);

        return (int) $risId;
    }

    /**
     * Checklist rows with per-document view/download links for accounting or president.
     *
     * @return array<int, array{key:string,label:string,ready:bool,links:array<int,array{label:string,url:string}>}>
     */
    public static function buildDocumentLinks(int $atpId, string $audience, int $packageId): array
    {
        $checklist = self::checklistForAtp($atpId);
        $resolved = self::resolveDocuments($atpId);
        $routePrefix = $audience === 'president' ? 'president' : 'accounting';

        $export = fn (string $type, int $id, string $format) => route(
            "{$routePrefix}.procurement-records.export",
            ['package' => $packageId, 'type' => $type, 'docId' => $id, 'format' => $format]
        );
        $view = fn (string $type, int $id) => route(
            "{$routePrefix}.procurement-records.view",
            ['package' => $packageId, 'type' => $type, 'docId' => $id]
        );
        $itemValueIds = collect($checklist['items'] ?? [])
            ->mapWithKeys(fn ($row) => [($row['key'] ?? '') => $row['value_id'] ?? null])
            ->all();
        $formNumberFor = fn (string $key) => match ($key) {
            'ris' => $resolved['ris_form_number'] ?? null,
            'atp' => $itemValueIds['atp'] ?? $resolved['atp_form_number'] ?? null,
            'funding' => $resolved['rfc_form_number'] ?? null,
            'dv' => $resolved['dv_form_number'] ?? null,
            'rr' => $resolved['rr_form_number'] ?? null,
            'liq' => $resolved['liq_form_number'] ?? null,
            default => null,
        };

        $rows = [];
        foreach ($checklist['items'] ?? [] as $item) {
            $key = $item['key'] ?? '';
            $links = [];

            if ($key === 'supporting_docs' && $resolved['attachment_ids'] !== []) {
                foreach ($resolved['attachment_ids'] as $attachmentId) {
                    $name = DB::table('ris_attachments_table')
                        ->where('ris_attachment_id', $attachmentId)
                        ->value('ris_attachment_original_name') ?? ('Attachment #' . $attachmentId);

                    $links[] = [
                        'label' => $name,
                        'url' => route("{$routePrefix}.procurement-records.attachment", [
                            'package' => $packageId,
                            'attachmentId' => $attachmentId,
                        ]),
                    ];
                }
            }

            if ($key === 'ris' && $resolved['ris_id']) {
                $links[] = ['label' => 'View', 'url' => $view('ris', $resolved['ris_id'])];
                $links[] = ['label' => 'Excel', 'url' => $export('ris', $resolved['ris_id'], 'xlsx')];
                $links[] = ['label' => 'Word', 'url' => $export('ris', $resolved['ris_id'], 'docx')];
            }

            if ($key === 'atp' && $resolved['atp_id']) {
                $links[] = ['label' => 'View', 'url' => $view('atp', $resolved['atp_id'])];
                $links[] = ['label' => 'Excel', 'url' => $export('atp', $resolved['atp_id'], 'xlsx')];
                $links[] = ['label' => 'Word', 'url' => $export('atp', $resolved['atp_id'], 'docx')];
            }

            if ($key === 'funding' && $resolved['rfc_id']) {
                $links[] = ['label' => 'View', 'url' => $view('rfc', $resolved['rfc_id'])];
                $links[] = ['label' => 'Excel', 'url' => $export('rfc', $resolved['rfc_id'], 'xlsx')];
                $links[] = ['label' => 'Word', 'url' => $export('rfc', $resolved['rfc_id'], 'docx')];
            }

            if ($key === 'dv' && ! empty($resolved['dv_id'])) {
                $links[] = ['label' => 'View', 'url' => $view('dv', (int) $resolved['dv_id'])];
            }

            if ($key === 'rr' && $resolved['rr_id']) {
                $links[] = ['label' => 'View', 'url' => $view('rr', $resolved['rr_id'])];
                $links[] = ['label' => 'Excel', 'url' => $export('rr', $resolved['rr_id'], 'xlsx')];
                $links[] = ['label' => 'Word', 'url' => $export('rr', $resolved['rr_id'], 'docx')];
            }

            if ($key === 'liq' && $resolved['liq_id']) {
                $links[] = ['label' => 'View', 'url' => $view('liq', $resolved['liq_id'])];
                $links[] = ['label' => 'Excel', 'url' => $export('liq', $resolved['liq_id'], 'xlsx')];
                $links[] = ['label' => 'Word', 'url' => $export('liq', $resolved['liq_id'], 'docx')];
            }

            $label = $item['label'] ?? ucfirst($key);
            $formNumber = $formNumberFor($key);
            // Always keep the ATP document row as ATP (heading elsewhere uses PR).
            if ($key === 'atp') {
                $label = 'ATP';
                $formNumber = $resolved['atp_form_number'] ?? $formNumber;
            }

            $rows[] = [
                'key' => $key,
                'label' => $label,
                'form_number' => $formNumber,
                'ready' => (bool) ($item['ready'] ?? false),
                'links' => $links,
            ];
        }

        return $rows;
    }

    /**
     * RIS supporting-document download links for purchaser compiled records.
     *
     * @return array<int, array{label:string,url:string}>
     */
    public static function supportingDocLinksForAtp(int $atpId): array
    {
        if (!Schema::hasTable('ris_attachments_table')) {
            return [];
        }

        $resolved = self::resolveDocuments($atpId);
        $links = [];

        foreach ($resolved['attachment_ids'] as $attachmentId) {
            $name = DB::table('ris_attachments_table')
                ->where('ris_attachment_id', $attachmentId)
                ->value('ris_attachment_original_name') ?? ('Attachment #' . $attachmentId);

            $links[] = [
                'label' => $name,
                'url' => route('purchaser.ris.attachments.download', $attachmentId),
            ];
        }

        return $links;
    }
}
