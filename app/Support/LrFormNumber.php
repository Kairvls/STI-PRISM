<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LR "No." values: LR-YYYYMM-00001
 * Assigned on submit only — drafts stay null.
 */
class LrFormNumber
{
    public const FORM_NUMBER_REGEX = '/^LR-\d{6}-\d{5}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next LR No. for the current year-month (non-draft rows only).
     */
    public static function next(): string
    {
        $ym = now()->format('Ym');
        $prefix = 'LR-'.$ym.'-';
        $max = 0;

        if (! Schema::hasTable('liquidation_reports_table')
            || ! Schema::hasColumn('liquidation_reports_table', 'liquidation_report_form_number')) {
            return $prefix.'00001';
        }

        $query = DB::table('liquidation_reports_table')
            ->whereNotNull('liquidation_report_form_number')
            ->where('liquidation_report_form_number', 'like', $prefix.'%');

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_status')) {
            $query->where(function ($q) {
                $q->whereNull('liquidation_report_status')
                    ->orWhere('liquidation_report_status', '!=', 'Draft');
            });
        }

        foreach ($query->pluck('liquidation_report_form_number') as $formNumber) {
            if (preg_match('/^LR-'.preg_quote($ym, '/').'-(\d{5})$/', (string) $formNumber, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = min($max + 1, 99999);

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function allocateOnSubmit(?string $existing = null): string
    {
        if (self::isValid($existing)) {
            return trim((string) $existing);
        }

        return self::next();
    }

    public static function normalizeForEdit(?string $formNumber): string
    {
        $formNumber = trim((string) $formNumber);

        return self::isValid($formNumber) ? $formNumber : '';
    }
}
