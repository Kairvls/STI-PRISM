<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ATP "No." values: ATP-YYYYMM-0001
 */
class AtpFormNumber
{
    public const FORM_NUMBER_REGEX = '/^ATP-\d{6}-\d{4}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next ATP No. for the current year-month.
     */
    public static function next(): string
    {
        $ym = now()->format('Ym');
        $prefix = 'ATP-'.$ym.'-';
        $max = 0;

        if (! Schema::hasTable('authority_to_purchase_table')
            || ! Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_form_number')) {
            return $prefix.'0001';
        }

        foreach (
            DB::table('authority_to_purchase_table')
                ->whereNotNull('authority_purchase_form_number')
                ->where('authority_purchase_form_number', 'like', $prefix.'%')
                ->pluck('authority_purchase_form_number') as $formNumber
        ) {
            if (preg_match('/^ATP-'.preg_quote($ym, '/').'-(\d{4})$/', (string) $formNumber, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = min($max + 1, 9999);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Keep a valid ATP-YYYYMM-#### as-is; otherwise return empty (caller may suggest next).
     */
    public static function normalizeForEdit(?string $formNumber): string
    {
        $formNumber = trim((string) $formNumber);

        return self::isValid($formNumber) ? $formNumber : '';
    }
}
