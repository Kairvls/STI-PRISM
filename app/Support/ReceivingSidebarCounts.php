<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unseen counts for the receiving sidebar.
 * A record drops off once this officer has opened it, and comes back if its status changes.
 */
class ReceivingSidebarCounts
{
    public const KIND_REPORT = 'recv_rr';

    public const KIND_BACK_ORDER = 'recv_bo';

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * @return array{pending: int, dp: int, ci: int, ca: int, back_orders: int}
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $backOrders = self::backOrderCounts();

        return self::$cached = [
            'pending' => self::pendingCount(),
            'dp' => $backOrders['dp'],
            'ci' => $backOrders['ci'],
            'ca' => $backOrders['ca'],
            'back_orders' => $backOrders['dp'] + $backOrders['ci'] + $backOrders['ca'],
        ];
    }

    /**
     * @return array<int, string> record id => status
     */
    public static function pendingReports(bool $leftover = false): array
    {
        if (! Schema::hasTable('receiving_reports_table')) {
            return [];
        }

        $query = ReceivingAttentionSummary::queue();
        if ($leftover) {
            ReceivingAttentionSummary::scopeLeftover($query);
        } else {
            ReceivingAttentionSummary::scopeQueue($query);
        }

        $rows = [];
        foreach ($query->get(['receiving_report_id', 'receiving_report_status']) as $row) {
            $rows[(int) $row->receiving_report_id] = (string) ($row->receiving_report_status ?? '');
        }

        return $rows;
    }

    /**
     * @return array<int, string> back order id => status
     */
    public static function backOrders(?string $bucket = null, ?string $status = null, ?int $rootReportId = null): array
    {
        $query = self::backOrderQuery();
        if ($query === null) {
            return [];
        }

        if ($status !== null && $status !== '' && $status !== 'unresolved') {
            $query->where('bo.back_order_status', $status);
        }
        if ($rootReportId) {
            $query->where('bo.back_order_root_receiving_report_id', $rootReportId);
        }

        $rows = [];
        foreach ($query->get(['bo.back_order_id', 'bo.back_order_status', 'bo.back_order_payment_path']) as $row) {
            $rowBucket = self::bucket($row->back_order_payment_path);
            if ($bucket !== null && $rowBucket !== $bucket) {
                continue;
            }
            $rows[(int) $row->back_order_id] = (string) ($row->back_order_status ?? '');
        }

        return $rows;
    }

    private static function pendingCount(): int
    {
        if (! Schema::hasTable('receiving_reports_table')) {
            return 0;
        }

        $query = ReceivingAttentionSummary::queue();
        ReceivingAttentionSummary::scopeQueue($query);
        PurchaserMenuSeen::excludeSeen(
            $query,
            self::KIND_REPORT,
            'receiving_reports_table.receiving_report_id',
            'receiving_reports_table.receiving_report_status'
        );

        return (int) $query->count();
    }

    /**
     * @return array{dp: int, ci: int, ca: int}
     */
    private static function backOrderCounts(): array
    {
        $counts = ['dp' => 0, 'ci' => 0, 'ca' => 0];
        $query = self::backOrderQuery();
        if ($query === null) {
            return $counts;
        }

        PurchaserMenuSeen::excludeSeen(
            $query,
            self::KIND_BACK_ORDER,
            'bo.back_order_id',
            'bo.back_order_status'
        );

        $rows = $query
            ->select('bo.back_order_payment_path', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('bo.back_order_payment_path')
            ->get();

        foreach ($rows as $row) {
            $bucket = self::bucket($row->back_order_payment_path);
            if ($bucket === null) {
                continue;
            }
            $counts[$bucket] += (int) $row->aggregate;
        }

        return $counts;
    }

    private static function backOrderQuery()
    {
        if (! BackOrders::supported()) {
            return null;
        }

        return DB::table(BackOrders::TABLE.' as bo')
            ->whereIn('bo.back_order_status', BackOrders::UNRESOLVED);
    }

    private static function bucket(mixed $path): ?string
    {
        return match (ProcurementPaymentPath::normalize(is_string($path) ? $path : null)) {
            ProcurementPaymentPath::DIRECT_PAYMENT => 'dp',
            ProcurementPaymentPath::CASH_INSTALLMENT => 'ci',
            ProcurementPaymentPath::CASH_ADVANCE => 'ca',
            default => null,
        };
    }
}
