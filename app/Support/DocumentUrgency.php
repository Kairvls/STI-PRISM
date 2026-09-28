<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Urgency is only chosen on the RIS. ATP / PO / RFC / RR / LR inherit it through their linked ATPs:
 * a document is urgent when any RIS behind it is Urgent.
 */
class DocumentUrgency
{
    public const COLUMN = 'doc_is_urgent';

    private const DOCUMENTS = [
        'ATP' => ['table' => 'authority_to_purchase_table', 'key' => 'authority_purchase_id'],
        'PO' => ['table' => 'purchase_orders_table', 'key' => 'purchase_order_id'],
        'RFC' => ['table' => 'request_check_table', 'key' => 'request_check_id'],
        'RR' => ['table' => 'receiving_reports_table', 'key' => 'receiving_report_id'],
        'LIQ' => ['table' => 'liquidation_reports_table', 'key' => 'liquidation_report_id'],
    ];

    /** @var array<string, string|null> */
    private static array $sql = [];

    /** @var array<string, bool> */
    private static array $cache = [];

    /**
     * Boolean SQL expression that is true when the document row (of $table) traces back to an urgent RIS.
     */
    public static function sql(string $type, ?string $table = null): ?string
    {
        $table = $table ?: (self::DOCUMENTS[$type]['table'] ?? null);
        if (!$table) {
            return null;
        }

        $cacheKey = $type.'|'.$table;
        if (!array_key_exists($cacheKey, self::$sql)) {
            self::$sql[$cacheKey] = self::build($type, $table);
        }

        return self::$sql[$cacheKey];
    }

    /**
     * Adds a 0/1 doc_is_urgent column to the query's select list.
     */
    public static function select($query, string $type, ?string $table = null)
    {
        if ($sql = self::sql($type, $table)) {
            $base = $query instanceof \Illuminate\Database\Eloquent\Builder ? $query->getQuery() : $query;
            if (empty($base->columns)) {
                $query->select('*');
            }
            $query->addSelect(DB::raw('(CASE WHEN '.$sql.' THEN 1 ELSE 0 END) as '.self::COLUMN));
        }

        return $query;
    }

    /**
     * Must be applied before any other ordering so it becomes the primary sort key.
     */
    public static function orderUrgentFirst($query, string $type, ?string $table = null)
    {
        if ($sql = self::sql($type, $table)) {
            $query->orderByRaw('(CASE WHEN '.$sql.' THEN 0 ELSE 1 END)');
        }

        return $query;
    }

    /**
     * Accepts a row that already carries doc_is_urgent (see select()) or a document id.
     */
    public static function isUrgent(string $type, $rowOrId): bool
    {
        if (is_object($rowOrId) && isset($rowOrId->{self::COLUMN})) {
            return (int) $rowOrId->{self::COLUMN} === 1;
        }

        $config = self::DOCUMENTS[$type] ?? null;
        $id = is_object($rowOrId) ? (int) ($rowOrId->{$config['key'] ?? ''} ?? 0) : (int) $rowOrId;
        if (!$config || $id < 1) {
            return false;
        }

        $cacheKey = $type.':'.$id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $sql = self::sql($type);
            try {
                self::$cache[$cacheKey] = $sql !== null && DB::table($config['table'])
                    ->where($config['table'].'.'.$config['key'], $id)
                    ->whereRaw($sql)
                    ->exists();
            } catch (\Throwable $e) {
                self::$cache[$cacheKey] = false;
            }
        }

        return self::$cache[$cacheKey];
    }

    private static function build(string $type, string $t): ?string
    {
        try {
            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_urgency')
                || !Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_ris_id')) {
                return null;
            }

            $match = match ($type) {
                'ATP' => ["du_a.authority_purchase_id = {$t}.authority_purchase_id"],
                'PO' => array_filter([self::poAtpIn("{$t}.purchase_order_id")]),
                'RFC' => self::rfcMatch(
                    "{$t}.request_check_id",
                    Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id') ? "{$t}.request_check_authority_purchase_id" : null,
                    Schema::hasColumn('request_check_table', 'request_check_purchase_order_id') ? "{$t}.request_check_purchase_order_id" : null
                ),
                'RR' => self::rrMatch(
                    Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id') ? "{$t}.receiving_report_atp_id" : null,
                    Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id') ? "{$t}.receiving_report_request_check_id" : null
                ),
                'LIQ' => Schema::hasColumn('liquidation_reports_table', 'liquidation_report_receiving_report_id')
                    ? self::rrMatch(
                        Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')
                            ? "(SELECT du_rr.receiving_report_atp_id FROM receiving_reports_table du_rr WHERE du_rr.receiving_report_id = {$t}.liquidation_report_receiving_report_id)"
                            : null,
                        Schema::hasColumn('receiving_reports_table', 'receiving_report_request_check_id')
                            ? "(SELECT du_rr2.receiving_report_request_check_id FROM receiving_reports_table du_rr2 WHERE du_rr2.receiving_report_id = {$t}.liquidation_report_receiving_report_id)"
                            : null
                    )
                    : [],
                default => [],
            };

            if ($match === []) {
                return null;
            }

            return "EXISTS (SELECT 1 FROM authority_to_purchase_table du_a"
                ." INNER JOIN requisition_issue_slip_table du_r ON du_r.ris_id = du_a.authority_purchase_ris_id"
                ." WHERE TRIM(du_r.ris_urgency) = '".RisWorkflow::URGENCY_URGENT."'"
                ." AND (".implode(' OR ', $match)."))";
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function poAtpIn(string $poIdExpr): ?string
    {
        if (!Schema::hasTable('purchase_order_atps_table')) {
            return null;
        }

        return "du_a.authority_purchase_id IN (SELECT du_pl.authority_purchase_id FROM purchase_order_atps_table du_pl WHERE du_pl.purchase_order_id = {$poIdExpr})";
    }

    /**
     * @return array<int, string>
     */
    private static function rfcMatch(string $rfcIdExpr, ?string $primaryAtpExpr, ?string $poIdExpr): array
    {
        $match = [];
        if ($primaryAtpExpr) {
            $match[] = "du_a.authority_purchase_id = {$primaryAtpExpr}";
        }
        if (Schema::hasTable('request_check_atps_table')) {
            $match[] = "du_a.authority_purchase_id IN (SELECT du_rl.authority_purchase_id FROM request_check_atps_table du_rl WHERE du_rl.request_check_id = {$rfcIdExpr})";
        }
        if ($poIdExpr && ($poIn = self::poAtpIn($poIdExpr))) {
            $match[] = $poIn;
        }

        return $match;
    }

    /**
     * @return array<int, string>
     */
    private static function rrMatch(?string $atpIdExpr, ?string $rfcIdExpr): array
    {
        $match = [];
        if ($atpIdExpr) {
            $match[] = "du_a.authority_purchase_id = {$atpIdExpr}";
        }
        if ($rfcIdExpr) {
            $match = array_merge($match, self::rfcMatch(
                $rfcIdExpr,
                Schema::hasColumn('request_check_table', 'request_check_authority_purchase_id')
                    ? "(SELECT du_rc.request_check_authority_purchase_id FROM request_check_table du_rc WHERE du_rc.request_check_id = {$rfcIdExpr})"
                    : null,
                Schema::hasColumn('request_check_table', 'request_check_purchase_order_id')
                    ? "(SELECT du_rc2.request_check_purchase_order_id FROM request_check_table du_rc2 WHERE du_rc2.request_check_id = {$rfcIdExpr})"
                    : null
            ));
        }

        return $match;
    }
}
