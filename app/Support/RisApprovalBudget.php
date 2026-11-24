<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RisApprovalBudget
{
    public const DEFAULT_LIMIT = 10000.00;

    private static ?float $cachedLimit = null;

    public static function limit(): float
    {
        if (self::$cachedLimit !== null) {
            return self::$cachedLimit;
        }

        if (! Schema::hasTable('ris_approval_budget_settings_table')) {
            return self::$cachedLimit = self::DEFAULT_LIMIT;
        }

        $val = DB::table('ris_approval_budget_settings_table')
            ->orderBy('ris_approval_budget_setting_id')
            ->value('direct_approve_limit');

        if ($val === null || ! is_numeric($val)) {
            return self::$cachedLimit = self::DEFAULT_LIMIT;
        }

        return self::$cachedLimit = round(max(0.0, (float) $val), 2);
    }

    public static function saveLimit(float $limit, ?int $userId = null): float
    {
        $clean = round(max(0.0, $limit), 2);
        self::$cachedLimit = $clean;

        if (! Schema::hasTable('ris_approval_budget_settings_table')) {
            return $clean;
        }

        $payload = [
            'direct_approve_limit' => $clean,
            'updated_by' => $userId ?: null,
            'updated_at' => now(),
        ];

        $id = DB::table('ris_approval_budget_settings_table')
            ->orderBy('ris_approval_budget_setting_id')
            ->value('ris_approval_budget_setting_id');

        if ($id) {
            DB::table('ris_approval_budget_settings_table')
                ->where('ris_approval_budget_setting_id', $id)
                ->update($payload);
        } else {
            DB::table('ris_approval_budget_settings_table')->insert($payload);
        }

        return $clean;
    }

    public static function risTotal(object|int $risOrId): float
    {
        if (is_object($risOrId)) {
            if (isset($risOrId->ris_calculated_total) && is_numeric($risOrId->ris_calculated_total) && (float) $risOrId->ris_calculated_total > 0) {
                return round((float) $risOrId->ris_calculated_total, 2);
            }
            $risId = (int) ($risOrId->ris_id ?? 0);
        } else {
            $risId = (int) $risOrId;
        }

        if ($risId <= 0 || ! Schema::hasTable('requisition_issue_slip_items_table')) {
            return 0.0;
        }

        $sum = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->selectRaw('SUM(CASE WHEN COALESCE(ris_total_amount, 0) > 0 THEN ris_total_amount ELSE COALESCE(ris_quantity_requested, 0) * COALESCE(ris_unit_cost, 0) END) as total_amt')
            ->value('total_amt');

        return round((float) ($sum ?? 0), 2);
    }

    /**
     * Strictly above the configured limit => must forward to President (Direct Approve disabled).
     * At or below the configured limit => must Direct Approve (Forward to President disabled).
     */
    public static function requiresPresident(float $total, ?float $limit = null): bool
    {
        $threshold = $limit ?? self::limit();

        return $total > ($threshold + 0.0001);
    }

    public static function formatPeso(float $amount): string
    {
        return '₱'.number_format($amount, 2);
    }
}
