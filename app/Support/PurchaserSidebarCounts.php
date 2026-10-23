<?php

namespace App\Support;

use App\Services\DocumentWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unseen open-list sizes for the purchaser sidebar.
 * A record drops off once this user has opened it, and comes back if its status changes.
 */
class PurchaserSidebarCounts
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * @return array{
     *     replacement: int,
     *     atp: int,
     *     rfc: int,
     *     rfc_dp: int,
     *     rfc_ci: int,
     *     rfc_ca: int,
     *     rr: int,
     *     back_orders: int,
     *     back_orders_rfc: int,
     *     back_orders_ca: int,
     *     liq: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $rfc = self::requestChecks();
        $backOrders = self::backOrders();

        return self::$cached = [
            'replacement' => self::replacementRequests(true),
            'atp' => self::ownedCount('authority_to_purchase_table', 'authority_purchase_id', 'authority_purchase_status', 'authority_purchase_is_archived', 'atp', true),
            'rfc' => $rfc['total'],
            'rfc_dp' => $rfc['dp'],
            'rfc_ci' => $rfc['ci'],
            'rfc_ca' => $rfc['ca'],
            'rr' => self::ownedCount('receiving_reports_table', 'receiving_report_id', 'receiving_report_status', 'receiving_report_is_archived', 'rr', true),
            'back_orders' => $backOrders['all'],
            'back_orders_rfc' => $backOrders['rfc'],
            'back_orders_ca' => $backOrders['ca'],
            'liq' => self::ownedCount('liquidation_reports_table', 'liquidation_report_id', 'liquidation_report_status', 'liquidation_report_is_archived', 'liq', true),
        ];
    }

    /**
     * Open records for a sidebar module, including ones already viewed.
     *
     * @return array<int, string> record id => status
     */
    public static function openRecords(string $kind, ?string $bucket = null): array
    {
        $rows = match ($kind) {
            'replacement' => self::replacementRows(),
            'atp' => self::ownedRows('authority_to_purchase_table', 'authority_purchase_id', 'authority_purchase_status', 'authority_purchase_is_archived', 'atp'),
            'rfc' => self::requestCheckRows(),
            'rr' => self::ownedRows('receiving_reports_table', 'receiving_report_id', 'receiving_report_status', 'receiving_report_is_archived', 'rr'),
            'bo' => self::backOrderRows(),
            'liq' => self::ownedRows('liquidation_reports_table', 'liquidation_report_id', 'liquidation_report_status', 'liquidation_report_is_archived', 'liq'),
            default => [],
        };

        if ($bucket === null) {
            return $rows;
        }

        return array_filter(
            $rows,
            fn ($status, $id) => self::bucketFor($kind, (int) $id, (string) $status) === $bucket,
            ARRAY_FILTER_USE_BOTH
        );
    }

    private static function replacementRequests(bool $unseen): int
    {
        $query = self::replacementQuery();
        if ($query === null) {
            return 0;
        }
        if ($unseen) {
            PurchaserMenuSeen::excludeSeen(
                $query,
                'replacement',
                'procurement_requests_table.procurement_request_id',
                'procurement_requests_table.procurement_request_status'
            );
        }

        return (int) $query->count();
    }

    /**
     * @return array<int, string>
     */
    private static function replacementRows(): array
    {
        $query = self::replacementQuery();
        if ($query === null) {
            return [];
        }

        return self::pluckStatus(
            $query,
            'procurement_request_id',
            'procurement_request_status'
        );
    }

    private static function replacementQuery()
    {
        if (! Schema::hasTable('procurement_requests_table')) {
            return null;
        }

        $query = DB::table('procurement_requests_table');
        if (Schema::hasColumn('procurement_requests_table', 'procurement_request_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'procurement_requests_table.procurement_request_is_archived',
                false
            );
        }

        return $query;
    }

    private static function ownedCount(
        string $table,
        string $idColumn,
        string $statusColumn,
        string $archiveColumn,
        string $document,
        bool $unseen
    ): int {
        $query = self::ownedQuery($table, $archiveColumn, $document);
        if ($query === null) {
            return 0;
        }
        if ($unseen) {
            PurchaserMenuSeen::excludeSeen($query, $document, $table.'.'.$idColumn, $table.'.'.$statusColumn);
        }

        return (int) $query->count();
    }

    /**
     * @return array<int, string>
     */
    private static function ownedRows(
        string $table,
        string $idColumn,
        string $statusColumn,
        string $archiveColumn,
        string $document
    ): array {
        $query = self::ownedQuery($table, $archiveColumn, $document);
        if ($query === null) {
            return [];
        }

        return self::pluckStatus($query, $idColumn, $statusColumn);
    }

    private static function ownedQuery(string $table, string $archiveColumn, string $document)
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, $archiveColumn)) {
            DocumentWorkflowService::applyArchiveFilter($query, $table.'.'.$archiveColumn, false);
        }
        PurchaserDocumentAccess::scopeOwned($query, $document, $table);

        return $query;
    }

    /**
     * @return array{total: int, dp: int, ci: int, ca: int}
     */
    private static function requestChecks(): array
    {
        $empty = ['total' => 0, 'dp' => 0, 'ci' => 0, 'ca' => 0];
        $query = self::requestCheckQuery();
        if ($query === null) {
            return $empty;
        }

        PurchaserMenuSeen::excludeSeen(
            $query,
            'rfc',
            'request_check_table.request_check_id',
            'request_check_table.request_check_status'
        );

        if (! Schema::hasColumn('request_check_table', 'request_check_funding_type')) {
            $total = (int) $query->count();

            return ['total' => $total, 'dp' => $total, 'ci' => 0, 'ca' => 0];
        }

        $rows = $query
            ->select('request_check_funding_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('request_check_funding_type')
            ->get();

        return self::tallyFunds($rows, 'request_check_funding_type');
    }

    /**
     * @return array<int, string>
     */
    private static function requestCheckRows(): array
    {
        $query = self::requestCheckQuery();
        if ($query === null) {
            return [];
        }

        $select = ['request_check_id', 'request_check_status'];
        if (Schema::hasColumn('request_check_table', 'request_check_funding_type')) {
            $select[] = 'request_check_funding_type';
        }

        $rows = [];
        foreach ($query->get($select) as $row) {
            $rows[(int) $row->request_check_id] = (string) ($row->request_check_status ?? '');
            self::rememberBucket('rfc', (int) $row->request_check_id, self::fundKey($row->request_check_funding_type ?? null));
        }

        return $rows;
    }

    private static function requestCheckQuery()
    {
        if (! Schema::hasTable('request_check_table')) {
            return null;
        }

        $query = DB::table('request_check_table');
        if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
            DocumentWorkflowService::applyArchiveFilter(
                $query,
                'request_check_table.request_check_is_archived',
                false
            );
        }
        PurchaserDocumentAccess::scopeOwned($query, 'rfc', 'request_check_table');

        return $query;
    }

    /**
     * @return array{all: int, rfc: int, ca: int}
     */
    private static function backOrders(): array
    {
        $empty = ['all' => 0, 'rfc' => 0, 'ca' => 0];
        $query = self::backOrderQuery();
        if ($query === null) {
            return $empty;
        }

        PurchaserMenuSeen::excludeSeen(
            $query,
            'bo',
            'bo.back_order_id',
            'bo.back_order_status'
        );

        $rows = $query
            ->select('bo.back_order_payment_path', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('bo.back_order_payment_path')
            ->get();

        $rfc = 0;
        $ca = 0;
        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            if (self::fundKey($row->back_order_payment_path) === 'ca') {
                $ca += $count;
            } else {
                $rfc += $count;
            }
        }

        return ['all' => $rfc + $ca, 'rfc' => $rfc, 'ca' => $ca];
    }

    /**
     * @return array<int, string>
     */
    private static function backOrderRows(): array
    {
        $query = self::backOrderQuery();
        if ($query === null) {
            return [];
        }

        $rows = [];
        foreach ($query->get(['bo.back_order_id', 'bo.back_order_status', 'bo.back_order_payment_path']) as $row) {
            $rows[(int) $row->back_order_id] = (string) ($row->back_order_status ?? '');
            $bucket = self::fundKey($row->back_order_payment_path) === 'ca' ? 'ca' : 'rfc';
            self::rememberBucket('bo', (int) $row->back_order_id, $bucket);
        }

        return $rows;
    }

    private static function backOrderQuery()
    {
        if (! BackOrders::supported() || ! Schema::hasTable('receiving_reports_table')) {
            return null;
        }

        $query = DB::table(BackOrders::TABLE.' as bo')
            ->join('receiving_reports_table as root', 'root.receiving_report_id', '=', 'bo.back_order_root_receiving_report_id')
            ->whereIn('bo.back_order_status', BackOrders::UNRESOLVED);
        PurchaserDocumentAccess::scopeOwned($query, 'rr', 'root');

        return $query;
    }

    /**
     * @param  iterable<int, object>  $rows
     * @return array{total: int, dp: int, ci: int, ca: int}
     */
    private static function tallyFunds(iterable $rows, string $column): array
    {
        $dp = 0;
        $ci = 0;
        $ca = 0;
        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            match (self::fundKey($row->{$column} ?? null)) {
                'ci' => $ci += $count,
                'ca' => $ca += $count,
                default => $dp += $count,
            };
        }

        return ['total' => $dp + $ci + $ca, 'dp' => $dp, 'ci' => $ci, 'ca' => $ca];
    }

    private static function fundKey(mixed $path): string
    {
        return match (ProcurementPaymentPath::normalize(is_string($path) ? $path : null)) {
            ProcurementPaymentPath::CASH_INSTALLMENT => 'ci',
            ProcurementPaymentPath::CASH_ADVANCE => 'ca',
            default => 'dp',
        };
    }

    /** @var array<string, array<int, string>> */
    private static array $buckets = [];

    private static function rememberBucket(string $kind, int $id, string $bucket): void
    {
        self::$buckets[$kind][$id] = $bucket;
    }

    private static function bucketFor(string $kind, int $id, string $status): ?string
    {
        if (! isset(self::$buckets[$kind])) {
            match ($kind) {
                'rfc' => self::requestCheckRows(),
                'bo' => self::backOrderRows(),
                default => null,
            };
        }

        return self::$buckets[$kind][$id] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private static function pluckStatus($query, string $idColumn, string $statusColumn): array
    {
        $rows = [];
        foreach ($query->get([$idColumn, $statusColumn]) as $row) {
            $rows[(int) $row->{$idColumn}] = (string) ($row->{$statusColumn} ?? '');
        }

        return $rows;
    }
}
