<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Request No. (ticket) for one procurement transaction:
 * Canvas+RIS → ATP → RFC → DV → RR → Liquidation.
 *
 * Format: PR-YYYYMM-0001 (year + month, 4-digit sequence).
 */
class ProcurementRequestNumber
{
    public const PREFIX = 'PR';

    /** Current format: PR-YYYYMM-#### */
    public const FORM_NUMBER_REGEX = '/^PR-\d{6}-\d{4}$/';

    /** Legacy: PR-YYYY-#### */
    public const LEGACY_FORM_NUMBER_REGEX = '/^PR-\d{4}-\d{4}$/';

    public static function uiLabel(): string
    {
        return 'Request No.';
    }

    public static function uiHint(): string
    {
        return 'Ticket for this procurement transaction. One Request No. (PR-YYYYMM-0001) links Canvas + RIS, ATP, RFC, DV, RR, and Liquidation.';
    }

    public static function isValid(?string $value): bool
    {
        $value = trim((string) $value);

        return $value !== ''
            && (
                (bool) preg_match(self::FORM_NUMBER_REGEX, $value)
                || (bool) preg_match(self::LEGACY_FORM_NUMBER_REGEX, $value)
            );
    }

    public static function allocate(): string
    {
        $ym = now()->format('Ym');
        $prefix = self::PREFIX.'-'.$ym.'-';
        $next = 1;

        $sources = [
            ['requisition_issue_slip_table', 'ris_request_no'],
            ['authority_to_purchase_table', 'authority_purchase_request_no'],
            ['request_check_table', 'request_check_request_no'],
            ['disbursement_vouchers_table', 'dv_request_no'],
            ['receiving_reports_table', 'receiving_report_request_no'],
            ['liquidation_reports_table', 'liquidation_report_request_no'],
        ];

        foreach ($sources as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            // Sequence continues across months — scan all PR-* tickets.
            $latestRows = DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', self::PREFIX.'-%')
                ->orderByDesc($column)
                ->limit(200)
                ->pluck($column);

            foreach ($latestRows as $latest) {
                if (! is_string($latest) || $latest === '') {
                    continue;
                }
                if (preg_match('/^PR-\d{4,6}-(\d+)$/', $latest, $m)) {
                    $next = max($next, ((int) $m[1]) + 1);
                }
            }
        }

        $next = min($next, 9999);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function forRis(int $risId): ?string
    {
        if ($risId <= 0
            || ! Schema::hasTable('requisition_issue_slip_table')
            || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_request_no')) {
            return null;
        }

        $existing = DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->value('ris_request_no');

        if (filled($existing)) {
            return (string) $existing;
        }

        $allocated = self::allocate();
        DB::table('requisition_issue_slip_table')
            ->where('ris_id', $risId)
            ->update(['ris_request_no' => $allocated]);

        return $allocated;
    }

    public static function resolveFromRis(?int $risId): ?string
    {
        if (! $risId) {
            return null;
        }

        return self::forRis((int) $risId);
    }

    public static function resolveFromAtp(?int $atpId): ?string
    {
        if (! $atpId || ! Schema::hasTable('authority_to_purchase_table')) {
            return null;
        }

        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $atpId)
            ->first(['authority_purchase_request_no', 'authority_purchase_ris_id']);

        if (! $atp) {
            return null;
        }

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_request_no')
            && filled($atp->authority_purchase_request_no ?? null)) {
            return (string) $atp->authority_purchase_request_no;
        }

        return self::resolveFromRis(isset($atp->authority_purchase_ris_id)
            ? (int) $atp->authority_purchase_ris_id
            : null);
    }

    public static function resolveFromRfc(?int $rfcId): ?string
    {
        if (! $rfcId || ! Schema::hasTable('request_check_table')) {
            return null;
        }

        $rfc = DB::table('request_check_table')
            ->where('request_check_id', $rfcId)
            ->first(['request_check_request_no', 'request_check_authority_purchase_id']);

        if (! $rfc) {
            return null;
        }

        if (Schema::hasColumn('request_check_table', 'request_check_request_no')
            && filled($rfc->request_check_request_no ?? null)) {
            return (string) $rfc->request_check_request_no;
        }

        $atpId = isset($rfc->request_check_authority_purchase_id)
            ? (int) $rfc->request_check_authority_purchase_id
            : null;

        if (! $atpId && class_exists(RfcAtpLinks::class) && RfcAtpLinks::tableExists()) {
            $ids = RfcAtpLinks::atpIdsFor((int) $rfcId, null);
            $atpId = $ids[0] ?? null;
        }

        return self::resolveFromAtp($atpId ? (int) $atpId : null);
    }

    public static function applyToPayload(array &$payload, string $column, ?string $requestNo): void
    {
        if ($requestNo === null || $requestNo === '') {
            return;
        }

        // Caller must only pass columns that exist on the target table.
        $payload[$column] = $requestNo;
    }
}
