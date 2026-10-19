<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DV "No." values: DV-YYYYMMDD-0001
 * Assigned on submit to School Administrator — drafts stay null.
 */
class DvFormNumber
{
    /** Accepts DV-YYYYMMDD-0001 and legacy DV-YYYYMM / DV-YYYY */
    public const FORM_NUMBER_REGEX = '/^DV-\d{4,8}-\d{4}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next DV No. — YYYYMMDD is today; sequence continues across days.
     */
    public static function next(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'DV-'.$ymd.'-';
        $max = 0;

        if (! Schema::hasTable('disbursement_vouchers_table')
            || ! Schema::hasColumn('disbursement_vouchers_table', 'dv_form_number')) {
            return $prefix.'0001';
        }

        $query = DB::table('disbursement_vouchers_table')
            ->whereNotNull('dv_form_number')
            ->where('dv_form_number', 'like', 'DV-%');

        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_status')) {
            $query->where(function ($q) {
                $q->whereNull('dv_status')
                    ->orWhereNotIn('dv_status', ['Draft']);
            });
        }

        foreach ($query->pluck('dv_form_number') as $formNumber) {
            if (preg_match('/^DV-\d{4,8}-(\d{4})$/', (string) $formNumber, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = min($max + 1, 9999);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function allocateOnSubmit(?string $existing = null): string
    {
        if (self::isValid($existing)) {
            return trim((string) $existing);
        }

        return self::next();
    }
}
