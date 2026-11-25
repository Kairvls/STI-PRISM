<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds RIS → ATP → RFC/CA → DV → RR → LIQ (cash advance only) lineage for view surfaces.
 */
class DocumentLineage
{
    public static function forRis(int $risId, ?object $ris = null): array
    {
        $chain = self::emptyChain();
        $ris ??= DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        $chain['ris'] = self::node(
            'RIS',
            $risId,
            RisWorkflow::formNumber($ris, $risId),
            self::openUrl('ris', $risId),
            $ris ? self::reviewHintForRis($ris) : self::reviewHint(null, null, 'ris')
        );

        // A multi-supplier RIS has one ATP per supplier; follow the latest, list the rest.
        $atps = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_ris_id', $risId)
            ->orderByDesc('authority_purchase_id')
            ->get();
        $atp = $atps->first();
        if (!$atp) {
            return $chain;
        }

        $chain = self::extendFromAtp($chain, $atp);
        $chain['atp_siblings'] = $atps->slice(1)
            ->reject(fn ($row) => (string) ($row->authority_purchase_status ?? '') === 'Rejected')
            ->map(fn ($row) => self::node(
                'ATP',
                (int) $row->authority_purchase_id,
                $row->authority_purchase_form_number ?? '',
                self::openUrl('atp', (int) $row->authority_purchase_id),
                self::reviewHint(RisWorkflow::atpStatusLabel($row), null, 'atp')
            ))
            ->values()
            ->all();

        return $chain;
    }

    public static function forAtp(int $atpId): array
    {
        $chain = self::emptyChain();
        $atp = DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->first();
        if (!$atp) {
            return $chain;
        }

        if ($atp->authority_purchase_ris_id) {
            $risId = (int) $atp->authority_purchase_ris_id;
            $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
            $chain['ris'] = self::node(
                'RIS',
                $risId,
                RisWorkflow::formNumber($ris, $risId),
                self::openUrl('ris', $risId),
                $ris ? self::reviewHintForRis($ris) : null
            );
        }

        return self::extendFromAtp($chain, $atp);
    }

    /**
     * Lightweight RIS node only (no ATP/RFC chain lookups) for closed list modals.
     */
    public static function stubRis(object $ris): array
    {
        $risId = (int) ($ris->ris_id ?? 0);
        $chain = self::emptyChain();
        $chain['ris'] = self::node(
            'RIS',
            $risId,
            RisWorkflow::formNumber($ris, $risId),
            self::openUrl('ris', $risId),
            self::reviewHintForRis($ris)
        );

        return $chain;
    }

    /**
     * Prefer {@see forAtp()} for view modals so prior pipeline steps stay clickable.
     * Stub keeps the linked RIS when present so previous forms are never locked.
     */
    public static function stubAtp(object $atp): array
    {
        $chain = self::emptyChain();
        if (!empty($atp->authority_purchase_ris_id)) {
            $risId = (int) $atp->authority_purchase_ris_id;
            $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
            $chain['ris'] = self::node(
                'RIS',
                $risId,
                RisWorkflow::formNumber($ris, $risId),
                self::openUrl('ris', $risId),
                $ris ? self::reviewHintForRis($ris) : null
            );
        }
        $chain['atp'] = self::node(
            'ATP',
            (int) $atp->authority_purchase_id,
            $atp->authority_purchase_form_number ?? '',
            self::openUrl('atp', (int) $atp->authority_purchase_id),
            self::reviewHint(RisWorkflow::atpStatusLabel($atp), null, 'atp')
        );
        if (!empty($atp->authority_purchase_payment_path)) {
            $chain['payment_path'] = ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path);
        }

        return $chain;
    }

    /**
     * Prefer {@see forRfc()} for view modals so prior pipeline steps stay clickable.
     * Stub walks back to ATP (and RIS) when linked so previous forms are never locked.
     */
    public static function stubRfc(object $rfc): array
    {
        $atpId = (int) ($rfc->request_check_authority_purchase_id ?? 0);
        $chain = $atpId > 0 ? self::forAtp($atpId) : self::emptyChain();
        $chain['rfc'] = self::node(
            'RFC',
            (int) $rfc->request_check_id,
            $rfc->request_check_form_number ?? '',
            self::openUrl('rfc', (int) $rfc->request_check_id),
            self::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc')
        );
        if (empty($chain['payment_path']) && !empty($rfc->request_check_funding_type)) {
            $chain['payment_path'] = ProcurementPaymentPath::normalize($rfc->request_check_funding_type);
        }

        return $chain;
    }

    /**
     * @param  int|null  $atpId  For a funding request covering several ATPs, the ATP to trace.
     */
    public static function forRfc(int $rfcId, ?int $atpId = null): array
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        if (!$rfc) {
            return self::emptyChain();
        }

        $atpId = $atpId ?: ((int) ($rfc->request_check_authority_purchase_id ?? 0) ?: null);
        $chain = $atpId ? self::forAtp($atpId) : self::emptyChain();

        $chain['rfc'] = self::node(
            'RFC',
            $rfcId,
            $rfc->request_check_form_number ?? '',
            self::openUrl('rfc', $rfcId),
            self::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc')
        );
        if (empty($chain['payment_path']) && !empty($rfc->request_check_funding_type)) {
            $chain['payment_path'] = ProcurementPaymentPath::normalize($rfc->request_check_funding_type);
        }

        return self::extendFromRfc($chain, $rfc, $atpId);
    }

    public static function forRr(int $rrId): array
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->first();
        if (!$rr) {
            return self::emptyChain();
        }

        if (!empty($rr->receiving_report_request_check_id)) {
            $chain = self::forRfc(
                (int) $rr->receiving_report_request_check_id,
                (int) ($rr->receiving_report_atp_id ?? 0) ?: null
            );
        } else {
            $chain = self::emptyChain();
        }

        $chain['rr'] = self::node(
            'RR',
            $rrId,
            $rr->receiving_report_form_number ?? '',
            self::openUrl('rr', $rrId),
            self::reviewHint($rr->receiving_report_status ?? null, null, 'rr')
        );

        return self::extendFromRr($chain, $rr);
    }

    public static function forLiq(int $liqId): array
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $liqId)->first();
        if (!$liq) {
            return self::emptyChain();
        }

        if (!empty($liq->liquidation_report_receiving_report_id)) {
            $chain = self::forRr((int) $liq->liquidation_report_receiving_report_id);
        } else {
            $chain = self::emptyChain();
        }

        $chain['liq'] = self::node(
            'LIQ',
            $liqId,
            $liq->liquidation_report_form_number ?? '',
            self::openUrl('liq', $liqId),
            self::reviewHint($liq->liquidation_report_status ?? null, $liq->liquidation_report_review_stage ?? null, 'liq')
        );
        if (empty($chain['payment_path'])) {
            $chain['payment_path'] = ProcurementPaymentPath::CASH_ADVANCE;
        }

        return $chain;
    }

    /**
     * Human-readable post-submit / review-stage message for a document status.
     *
     * @param  string|null  $docType  ris|atp|rfc|dv|rr|liq
     */
    public static function reviewHint(?string $status, ?string $stage = null, ?string $docType = null): ?string
    {
        $raw = trim((string) $status);
        if ($raw === '') {
            return null;
        }

        $docType = $docType ? strtolower($docType) : null;
        $stage = $stage ? strtolower($stage) : null;
        $open = in_array($raw, ['Submitted', 'Under Review', 'Resubmitted', 'Pending'], true);

        if ($stage === 'purchaser' && $open) {
            return ProcurementStatus::FOR_REVISION;
        }

        $docForLabel = $docType;
        if ($docType === 'liq' && $stage === 'admin') {
            $docForLabel = 'rfc';
        }

        $label = ProcurementStatus::label($raw, $docForLabel);

        return $label === '—' ? null : $label;
    }

    /**
     * RIS-specific hint using signature / workflow flags when available.
     */
    public static function reviewHintForRis(object $ris): ?string
    {
        $label = RisWorkflow::statusLabel($ris);

        return $label !== '' && $label !== '—' ? $label : null;
    }

    private static function emptyChain(): array
    {
        return [
            'ris' => null,
            'atp' => null,
            'rfc' => null,
            'dv' => null,
            'rr' => null,
            'liq' => null,
            'payment_path' => null,
        ];
    }

    private static function openUrl(string $type, int $id): string
    {
        $url = RequestExplorer::viewUrlFor($type, $id);
        if (is_string($url) && $url !== '') {
            return $url;
        }

        return '#';
    }

    private static function dvUrl(int $dvId): string
    {
        return self::openUrl('dv', $dvId);
    }

    private static function node(string $type, int $id, string $label, string $url, ?string $hint = null): array
    {
        return [
            'type' => $type,
            'id' => $id,
            'label' => self::pipelineLabel($label),
            'url' => $url,
            'hint' => $hint,
        ];
    }

    /**
     * Pipeline chip text: real form number, or "-" (never TYPE #id).
     */
    private static function pipelineLabel(?string $label): string
    {
        $label = trim((string) $label);
        if ($label === '' || preg_match('/^(RIS|ATP|RFC|RR|LR|LIQ|CA|DV)\s*#\d+$/i', $label)) {
            return '-';
        }

        return $label;
    }

    private static function extendFromAtp(array $chain, object $atp): array
    {
        $chain['atp'] = self::node(
            'ATP',
            (int) $atp->authority_purchase_id,
            $atp->authority_purchase_form_number ?? '',
            self::openUrl('atp', (int) $atp->authority_purchase_id),
            self::reviewHint(RisWorkflow::atpStatusLabel($atp), null, 'atp')
        );
        if (!empty($atp->authority_purchase_payment_path)) {
            $chain['payment_path'] = ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path);
        }

        $rfc = RfcAtpLinks::latestRfcForAtp((int) $atp->authority_purchase_id);

        if ($rfc) {
            return self::extendFromRfc($chain, $rfc, (int) $atp->authority_purchase_id);
        }

        return $chain;
    }

    private static function extendFromRfc(array $chain, object $rfc, ?int $atpId = null): array
    {
        $chain['rfc'] = self::node(
            'RFC',
            (int) $rfc->request_check_id,
            $rfc->request_check_form_number ?? '',
            self::openUrl('rfc', (int) $rfc->request_check_id),
            self::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc')
        );

        if (
            Schema::hasTable('disbursement_vouchers_table')
            && Schema::hasColumn('disbursement_vouchers_table', 'dv_request_check_id')
        ) {
            $dv = DB::table('disbursement_vouchers_table')
                ->where('dv_request_check_id', $rfc->request_check_id)
                ->when(
                    Schema::hasColumn('disbursement_vouchers_table', 'dv_is_archived'),
                    fn ($q) => $q->where(function ($inner) {
                        $inner->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
                    })
                )
                ->orderByDesc('dv_id')
                ->first();

            if ($dv) {
                $chain['dv'] = self::node(
                    'DV',
                    (int) $dv->dv_id,
                    $dv->dv_form_number ?? '',
                    self::dvUrl((int) $dv->dv_id),
                    self::reviewHint($dv->dv_status ?? null, null, 'dv')
                );
            }
        }

        $rrQuery = DB::table('receiving_reports_table');
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')) {
            $rrQuery->where('receiving_report_request_check_id', $rfc->request_check_id);
            if ($atpId && Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
                $isPrimary = (int) ($rfc->request_check_authority_purchase_id ?? 0) === $atpId;
                $rrQuery->where(function ($q) use ($atpId, $isPrimary) {
                    $q->where('receiving_report_atp_id', $atpId);
                    if ($isPrimary) {
                        $q->orWhereNull('receiving_report_atp_id');
                    }
                });
            }
        } elseif (Schema::hasColumn('request_check_table', 'request_check_receiving_report_id') && !empty($rfc->request_check_receiving_report_id)) {
            $rrQuery->where('receiving_report_id', $rfc->request_check_receiving_report_id);
        } else {
            return $chain;
        }

        $rr = $rrQuery->orderByDesc('receiving_report_id')->first();
        if ($rr) {
            return self::extendFromRr($chain, $rr);
        }

        return $chain;
    }

    private static function extendFromRr(array $chain, object $rr): array
    {
        $chain['rr'] = self::node(
            'RR',
            (int) $rr->receiving_report_id,
            $rr->receiving_report_form_number ?? '',
            self::openUrl('rr', (int) $rr->receiving_report_id),
            self::reviewHint($rr->receiving_report_status ?? null, null, 'rr')
        );

        if (!Schema::hasTable('liquidation_reports_table')) {
            return $chain;
        }

        $paymentPath = null;
        if (!empty($chain['rfc']['id'] ?? null)) {
            $rfcRow = DB::table('request_check_table')->where('request_check_id', $chain['rfc']['id'])->first();
            if ($rfcRow) {
                $paymentPath = self::paymentPathForRfc($rfcRow);
            }
        }

        if (!ProcurementPaymentPath::requiresLiquidation($paymentPath)) {
            return $chain;
        }

        $liq = DB::table('liquidation_reports_table')
            ->where('liquidation_report_receiving_report_id', $rr->receiving_report_id)
            ->orderByDesc('liquidation_report_id')
            ->first();

        if ($liq) {
            $chain['liq'] = self::node(
                'LIQ',
                (int) $liq->liquidation_report_id,
                $liq->liquidation_report_form_number ?? '',
                self::openUrl('liq', (int) $liq->liquidation_report_id),
                self::reviewHint($liq->liquidation_report_status ?? null, $liq->liquidation_report_review_stage ?? null, 'liq')
            );
        }

        return $chain;
    }

    private static function paymentPathForRfc(object $rfc): ?string
    {
        if (!empty($rfc->request_check_funding_type ?? null)) {
            return $rfc->request_check_funding_type;
        }

        if (!empty($rfc->request_check_authority_purchase_id)) {
            $atp = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $rfc->request_check_authority_purchase_id)
                ->first();

            return $atp->authority_purchase_payment_path ?? null;
        }

        return null;
    }

    private static function rfcLabel(object $rfc): string
    {
        $type = self::paymentPathForRfc($rfc);
        $prefix = $type === ProcurementPaymentPath::CASH_ADVANCE ? 'CA' : 'RFC';

        return self::pipelineLabel($rfc->request_check_form_number ?? '');
    }
}
