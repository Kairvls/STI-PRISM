<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unseen counts for the President sidebar.
 * A record drops off once opened, and comes back if its status changes.
 */
class PresidentSidebarCounts
{
    public const KIND_RIS = 'pres_ris';

    public const KIND_NOTIFY = 'pres_notify';

    public const KIND_DIRECT = 'pres_direct';

    public const KIND_DV = 'pres_dv';

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * @return array{ris: int, direct: int, dv: int}
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        return self::$cached = [
            'ris' => self::countRis() + self::countNotify(),
            'direct' => self::countDirect(),
            'dv' => self::countDv(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function risPending(): array
    {
        $query = self::risPendingQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'ris_id', 'ris_status');
    }

    /**
     * @return array<int, string>
     */
    public static function risNotify(): array
    {
        $query = self::risNotifyQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'ris_id', 'ris_status');
    }

    /**
     * @return array<int, string>
     */
    public static function directApprovals(): array
    {
        $query = self::directQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'ris_id', 'ris_status');
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

    private static function countRis(): int
    {
        $query = self::risPendingQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_RIS, 'ris_id', 'ris_status');

        return (int) $query->count();
    }

    private static function countNotify(): int
    {
        $query = self::risNotifyQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_NOTIFY, 'ris_id', 'ris_status');

        return (int) $query->count();
    }

    private static function countDirect(): int
    {
        $query = self::directQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_DIRECT, 'ris_id', 'ris_status');

        return (int) $query->count();
    }

    private static function countDv(): int
    {
        $query = self::dvQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen($query, self::KIND_DV, 'dv_id', 'dv_status');

        return (int) $query->count();
    }

    private static function risPendingQuery()
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        return PresidentAttentionSummary::scopePendingApproval(DB::table('requisition_issue_slip_table'));
    }

    private static function risNotifyQuery()
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        return PresidentAttentionSummary::scopeAwaitingNotify(DB::table('requisition_issue_slip_table'));
    }

    private static function directQuery()
    {
        if (! Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        $query = DB::table('requisition_issue_slip_table')
            ->where('ris_status', RisWorkflow::DIRECTLY_APPROVED);

        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('ris_is_archived')->orWhere('ris_is_archived', 0);
            });
        }

        // History feed — only surface recent direct approvals as unseen notices.
        $since = now()->subDays(30);
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_at')) {
            $query->where('ris_direct_approval_at', '>=', $since);
        } elseif (Schema::hasColumn('requisition_issue_slip_table', 'ris_updated_at')) {
            $query->where('ris_updated_at', '>=', $since);
        }

        return $query;
    }

    private static function dvQuery()
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return null;
        }

        $query = DB::table('disbursement_vouchers_table')
            ->where('dv_status', 'With President')
            ->where(function ($q) {
                $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
            });

        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_president_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'dv_assigned_president_id');
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
