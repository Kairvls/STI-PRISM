<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFC "No." values: RFC-YYYYMM-00001
 * Assigned on submit only — drafts stay null.
 */
class RfcFormNumber
{
    public const FORM_NUMBER_REGEX = '/^RFC-\d{6}-\d{5}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next RFC No. for the current year-month (non-draft rows only).
     */
    public static function next(): string
    {
        $ym = now()->format('Ym');
        $prefix = 'RFC-'.$ym.'-';
        $max = 0;

        if (! Schema::hasTable('request_check_table')
            || ! Schema::hasColumn('request_check_table', 'request_check_form_number')) {
            return $prefix.'00001';
        }

        $query = DB::table('request_check_table')
            ->whereNotNull('request_check_form_number')
            ->where('request_check_form_number', 'like', $prefix.'%');

        if (Schema::hasColumn('request_check_table', 'request_check_status')) {
            $query->where(function ($q) {
                $q->whereNull('request_check_status')
                    ->orWhere('request_check_status', '!=', 'Draft');
            });
        }

        foreach ($query->pluck('request_check_form_number') as $formNumber) {
            if (preg_match('/^RFC-'.preg_quote($ym, '/').'-(\d{5})$/', (string) $formNumber, $m)) {
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
