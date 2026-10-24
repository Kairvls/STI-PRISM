<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unseen counts for the school administrator sidebar.
 * A record drops off once this administrator has opened it, and comes back if its status changes.
 */
class SchoolAdminSidebarCounts
{
    public const KIND_RIS_ACCEPT = 'sa_ris_accept';

    public const KIND_RIS_SIGN = 'sa_ris_sign';

    public const KIND_ATP = 'sa_atp';

    public const KIND_RFC = 'sa_rfc';

    public const KIND_DV = 'sa_dv';

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * @return array{ris: int, atp: int, rfc: int, dv: int}
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        return self::$cached = [
            'ris' => self::countRisAccept() + self::countRisSign(),
            'atp' => self::countAtp(),
            'rfc' => self::countRfc(),
            'dv' => self::countDv(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function risAccept(): array
    {
        $query = self::risAcceptQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'ris_id', 'ris_status');
    }

    /**
     * @return array<int, string>
     */
    public static function risSign(): array
    {
        $query = self::risSignQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'ris_id', 'ris_status');
    }

    /**
     * @return array<int, string>
     */
    public static function atpIncoming(): array
    {
        $query = self::atpQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'authority_purchase_id', 'authority_purchase_status');
    }

    /**
     * @return array<int, string>
     */
    public static function rfcIncoming(): array
    {
        $query = self::rfcQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'request_check_id', 'request_check_status');
    }

    /**
     * @return array<int, string>
     */
    public static function dvIncoming(): array
    {
        $query = self::dvQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'dv_id', 'dv_status');
    }

    private static function countRisAccept(): int
    {
        $query = self::risAcceptQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_RIS_ACCEPT, 'requisition_issue_slip_table.ris_id', 'requisition_issue_slip_table.ris_status');

        return (int) $query->count();
    }

    private static function countRisSign(): int
    {
        $query = self::risSignQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_RIS_SIGN, 'requisition_issue_slip_table.ris_id', 'requisition_issue_slip_table.ris_status');

        return (int) $query->count();
    }

    private static function countAtp(): int
    {
        $query = self::atpQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_ATP, 'authority_to_purchase_table.authority_purchase_id', 'authority_to_purchase_table.authority_purchase_status');

        return (int) $query->count();
    }

    private static function countRfc(): int
    {
        $query = self::rfcQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_RFC, 'request_check_table.request_check_id', 'request_check_table.request_check_status');

        return (int) $query->count();
    }

    private static function countDv(): int
    {
        $query = self::dvQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_DV, 'disbursement_vouchers_table.dv_id', 'disbursement_vouchers_table.dv_status');

        return (int) $query->count();
    }

    private static function risAcceptQuery()
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        $query = DB::table('requisition_issue_slip_table');
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'ris_assigned_reviewer_id');
        }
        AdminAttentionSummary::scopePendingRis($query);

        return $query;
    }

    private static function risSignQuery()
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        $query = DB::table('requisition_issue_slip_table');
        AdminAttentionSummary::scopeAwaitingCosign($query);

        return $query;
    }

    private static function atpQuery()
    {
        if (! Schema::hasTable('authority_to_purchase_table')) {
            return null;
        }

        $query = AccountingAttentionSummary::queue('authority_to_purchase_table');
        AccountingAttentionSummary::scopeAtpIncoming($query);

        return $query;
    }

    private static function rfcQuery()
    {
        if (! Schema::hasTable('request_check_table')) {
            return null;
        }

        $query = AccountingAttentionSummary::queue('request_check_table');
        AccountingAttentionSummary::scopeRfcIncoming($query);

        return $query;
    }

    private static function dvQuery()
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return null;
        }

        $query = DB::table('disbursement_vouchers_table')
            ->where(function ($q) {
                $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
            })
            ->where('dv_status', 'Pending');
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'dv_assigned_reviewer_id');
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    private static function pluck($query, string $idColumn, string $statusColumn): array
    {
        $rows = [];
        foreach ($query->get([$idColumn, $statusColumn]) as $row) {
            $rows[(int) $row->{$idColumn}] = (string) ($row->{$statusColumn} ?? '');
        }

        return $rows;
    }
}
