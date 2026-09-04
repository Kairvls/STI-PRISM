<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds RIS → ATP → RFC/CA → RR → LIQ (cash advance only) lineage for purchaser view surfaces.
 */
class DocumentLineage
{
    public static function forRis(int $risId): array
    {
        $chain = self::emptyChain();
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        $chain['ris'] = self::node(
            'RIS',
            $risId,
            $ris->ris_form_number ?? ('RIS #'.$risId),
            route('purchaser.ris.index', ['view_ris' => $risId]),
            $ris ? self::reviewHintForRis($ris) : self::reviewHint(null, null, 'ris')
        );

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_ris_id', $risId)
            ->orderByDesc('authority_purchase_id')
            ->first();
        if ($atp) {
            return self::extendFromAtp($chain, $atp);
        }

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
                $ris->ris_form_number ?? ('RIS #'.$risId),
                route('purchaser.ris.index', ['view_ris' => $risId]),
                $ris ? self::reviewHintForRis($ris) : null
            );
        }

        return self::extendFromAtp($chain, $atp);
    }

    public static function forRfc(int $rfcId): array
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        if (!$rfc) {
            return self::emptyChain();
        }

        if (!empty($rfc->request_check_authority_purchase_id)) {
            $chain = self::forAtp((int) $rfc->request_check_authority_purchase_id);
        } else {
            $chain = self::emptyChain();
        }

        $chain['rfc'] = self::node(
            'RFC',
            $rfcId,
            $rfc->request_check_form_number ?? ('RFC #'.$rfcId),
            route('purchaser.rfc.index', ['view_rfc' => $rfcId]),
            self::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc')
        );

        return self::extendFromRfc($chain, $rfc);
    }

    public static function forRr(int $rrId): array
    {
        $rr = DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->first();
        if (!$rr) {
            return self::emptyChain();
        }

        if (!empty($rr->receiving_report_request_check_id)) {
            $chain = self::forRfc((int) $rr->receiving_report_request_check_id);
        } else {
            $chain = self::emptyChain();
        }

        $chain['rr'] = self::node(
            'RR',
            $rrId,
            $rr->receiving_report_form_number ?? ('RR #'.$rrId),
            route('purchaser.rr.index', ['view_rr' => $rrId]),
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
            $liq->liquidation_report_form_number ?? ('LIQ #'.$liqId),
            route('purchaser.liq.index', ['view_liq' => $liqId]),
            self::reviewHint($liq->liquidation_report_status ?? null, $liq->liquidation_report_review_stage ?? null, 'liq')
        );

        return $chain;
    }

    /**
     * Human-readable post-submit / review-stage message for a document status.
     *
     * @param  string|null  $docType  ris|atp|rfc|rr|liq
     */
    public static function reviewHint(?string $status, ?string $stage = null, ?string $docType = null): ?string
    {
        $status = (string) $status;
        $docType = $docType ? strtolower($docType) : null;

        if (in_array($status, ['Draft', ''], true)) {
            return 'Draft — not submitted yet';
        }
        if ($status === 'Minor Revision') {
            return 'Revision required — update and resubmit';
        }
        if (in_array($status, ['Approved', 'Completed', 'Directly Approved'], true)) {
            return $status === 'Completed' ? 'Completed' : 'Approved';
        }
        if (in_array($status, ['Rejected', 'Returned', RisWorkflow::PRESIDENT_REJECTED, RisWorkflow::PRESIDENT_REJECTED_LEGACY], true)) {
            return str_contains($status, 'Rejected') ? 'Rejected' : $status;
        }
        if ($status === RisWorkflow::FORWARDED) {
            return 'Submitted — waiting on President';
        }
        if ($status === RisWorkflow::PRESIDENT_APPROVED || $status === RisWorkflow::APPROVED_LEGACY) {
            return 'Approved — waiting on Admin release';
        }
        if ($stage === 'purchaser') {
            return 'Returned to purchaser — update and resubmit';
        }
        if (in_array($status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending', 'Pending Admin Approval'], true)) {
            if ($stage === 'admin' || $status === 'Pending Admin Approval') {
                return 'Submitted — waiting on Admin';
            }
            if ($stage === 'receiving' || $docType === 'rr') {
                return 'Submitted — waiting on Receiving';
            }
            if ($docType === 'ris') {
                return 'Submitted — waiting on Admin';
            }

            return 'Submitted — waiting on Accounting';
        }

        return $status ?: null;
    }

    /**
     * RIS-specific hint using signature / workflow flags when available.
     */
    public static function reviewHintForRis(object $ris): ?string
    {
        $status = (string) ($ris->ris_status ?? '');

        if (in_array($status, ['Draft', ''], true)) {
            return 'Draft — not submitted yet';
        }
        if ($status === 'Minor Revision') {
            return 'Revision required — update and resubmit';
        }
        if ($status === 'Rejected' || RisWorkflow::isPresidentRejected($ris)) {
            return 'Rejected';
        }
        if (RisWorkflow::isEligibleForAtp($ris)) {
            return 'Approved — ready for ATP';
        }
        if (RisWorkflow::isPresidentApproved($ris) && !RisWorkflow::hasIssuedBy($ris)) {
            return 'Approved by President — waiting on Admin release';
        }
        if ($status === RisWorkflow::ACCEPTED) {
            return 'Accepted — waiting on Admin signing decision';
        }
        if (RisWorkflow::isAwaitingPresident($ris) || $status === RisWorkflow::FORWARDED) {
            return 'Submitted — waiting on President';
        }
        if (in_array($status, RisWorkflow::incomingStatuses(), true)) {
            return 'Submitted — waiting on Admin accept';
        }

        return self::reviewHint($status, null, 'ris');
    }

    private static function emptyChain(): array
    {
        return [
            'ris' => null,
            'atp' => null,
            'rfc' => null,
            'rr' => null,
            'liq' => null,
        ];
    }

    private static function node(string $type, int $id, string $label, string $url, ?string $hint = null): array
    {
        return compact('type', 'id', 'label', 'url', 'hint');
    }

    private static function extendFromAtp(array $chain, object $atp): array
    {
        $chain['atp'] = self::node(
            'ATP',
            (int) $atp->authority_purchase_id,
            $atp->authority_purchase_form_number ?? ('ATP #'.$atp->authority_purchase_id),
            route('purchaser.atp.index', ['view_atp' => $atp->authority_purchase_id]),
            self::reviewHint(RisWorkflow::atpStatusLabel($atp), null, 'atp')
        );

        $rfc = DB::table('request_check_table')
            ->where('request_check_authority_purchase_id', $atp->authority_purchase_id)
            ->orderByDesc('request_check_id')
            ->first();

        if ($rfc) {
            return self::extendFromRfc($chain, $rfc);
        }

        return $chain;
    }

    private static function extendFromRfc(array $chain, object $rfc): array
    {
        $chain['rfc'] = self::node(
            'RFC',
            (int) $rfc->request_check_id,
            $rfc->request_check_form_number ?? ('RFC #'.$rfc->request_check_id),
            route('purchaser.rfc.index', ['view_rfc' => $rfc->request_check_id]),
            self::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc')
        );

        $rrQuery = DB::table('receiving_reports_table');
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')) {
            $rrQuery->where('receiving_report_request_check_id', $rfc->request_check_id);
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
            $rr->receiving_report_form_number ?? ('RR #'.$rr->receiving_report_id),
            route('purchaser.rr.index', ['view_rr' => $rr->receiving_report_id]),
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
                $liq->liquidation_report_form_number ?? ('LIQ #'.$liq->liquidation_report_id),
                route('purchaser.liq.index', ['view_liq' => $liq->liquidation_report_id]),
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

        return $rfc->request_check_form_number ?? ($prefix . ' #' . $rfc->request_check_id);
    }
}
