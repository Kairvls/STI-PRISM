<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unseen counts for the Accounting sidebar.
 * A record drops off once this accountant has opened it, and comes back if its status changes.
 */
class AccountingSidebarCounts
{
    /** RFCs sent for DV with no voucher yet. */
    public const KIND_DV_PKG = 'acc_dv_pkg';

    /** DVs needing draft work or check release. */
    public const KIND_DV = 'acc_dv';

    public const KIND_LIQ = 'acc_liq';

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * @return array{dv: int, liq: int}
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        return self::$cached = [
            'dv' => self::countDvPackages() + self::countDvActionable(),
            'liq' => self::countLiq(),
        ];
    }

    /**
     * RFCs that still need a DV created.
     *
     * @return array<int, string>
     */
    public static function dvPackages(): array
    {
        $query = self::dvPackageQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'request_check_table.request_check_id', DB::raw("'needs_dv' as seen_status"), 'request_check_id', 'seen_status');
    }

    /**
     * Draft / revision / ready-to-release DVs.
     *
     * @return array<int, string>
     */
    public static function dvActionable(): array
    {
        $query = self::dvActionableQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'dv_id', 'dv_status');
    }

    /**
     * @return array<int, string>
     */
    public static function liqIncoming(): array
    {
        $query = self::liqQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'liquidation_report_id', 'liquidation_report_status');
    }

    private static function countDvPackages(): int
    {
        $all = self::dvPackages();
        if ($all === []) {
            return 0;
        }

        return count(self::unseenOnly(self::KIND_DV_PKG, $all));
    }

    private static function countDvActionable(): int
    {
        $query = self::dvActionableQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen(
            $query,
            self::KIND_DV,
            'disbursement_vouchers_table.dv_id',
            'disbursement_vouchers_table.dv_status'
        );

        return (int) $query->count();
    }

    private static function countLiq(): int
    {
        $query = self::liqQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen(
            $query,
            self::KIND_LIQ,
            'liquidation_reports_table.liquidation_report_id',
            'liquidation_reports_table.liquidation_report_status'
        );

        return (int) $query->count();
    }

    private static function dvPackageQuery()
    {
        if (! Schema::hasTable('request_check_table') || ! AccountingHandoff::rfcHandoffColumnsReady()) {
            return null;
        }

        $query = DB::table('request_check_table');
        AccountingHandoff::scopeIncomingPackagesForAccounting($query);
        $query->leftJoin('disbursement_vouchers_table as dv', function ($join) {
            $join->on('dv.dv_request_check_id', '=', 'request_check_table.request_check_id')
                ->where(function ($q) {
                    $q->whereNull('dv.dv_is_archived')->orWhere('dv.dv_is_archived', 0);
                });
        })->whereNull('dv.dv_id');

        return $query;
    }

    private static function dvActionableQuery()
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return null;
        }

        return DB::table('disbursement_vouchers_table')
            ->where(function ($q) {
                $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
            })
            ->whereIn('dv_status', ['Draft', 'Revision', 'President Approved']);
    }

    private static function liqQuery()
    {
        if (! Schema::hasTable('liquidation_reports_table')) {
            return null;
        }

        $query = AccountingAttentionSummary::queue('liquidation_reports_table');
        AccountingAttentionSummary::scopeLiqIncoming($query);

        return $query;
    }

    /**
     * @param  array<int, string>  $rows
     * @return array<int, string>
     */
    private static function unseenOnly(string $kind, array $rows): array
    {
        $userId = (int) (\Illuminate\Support\Facades\Auth::id() ?? 0);
        if ($userId < 1 || $rows === [] || ! Schema::hasTable(PurchaserMenuSeen::TABLE)) {
            return $rows;
        }

        $seen = DB::table(PurchaserMenuSeen::TABLE)
            ->where('seen_user_id', $userId)
            ->where('seen_kind', $kind)
            ->whereIn('seen_record_id', array_keys($rows))
            ->get(['seen_record_id', 'seen_status'])
            ->keyBy(fn ($row) => (int) $row->seen_record_id);

        return array_filter(
            $rows,
            function ($status, $id) use ($seen) {
                $row = $seen->get((int) $id);
                if (! $row) {
                    return true;
                }

                return (string) $row->seen_status !== (string) $status;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @return array<int, string>
     */
    private static function pluck($query, string $idColumn, $statusColumn, ?string $idAlias = null, ?string $statusAlias = null): array
    {
        $idKey = $idAlias ?? (str_contains($idColumn, '.') ? last(explode('.', $idColumn)) : $idColumn);
        $statusKey = $statusAlias ?? (is_string($statusColumn) && str_contains($statusColumn, '.')
            ? last(explode('.', $statusColumn))
            : (is_string($statusColumn) ? $statusColumn : 'seen_status'));

        $select = [$idColumn, $statusColumn];

        $rows = [];
        foreach ($query->get($select) as $row) {
            $rows[(int) $row->{$idKey}] = (string) ($row->{$statusKey} ?? '');
        }

        return $rows;
    }
}
