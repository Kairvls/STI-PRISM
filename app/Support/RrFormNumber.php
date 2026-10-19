<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RR "No." values: RR-YYYYMMDD-0000001
 * Assigned on submit only — drafts stay null.
 */
class RrFormNumber
{
    /** Accepts RR-YYYYMMDD-0000001 and legacy RR-YYYYMM-0000001 */
    public const FORM_NUMBER_REGEX = '/^RR-\d{6,8}-\d{7}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next RR No. — YYYYMMDD is today; sequence continues across days.
     */
    public static function next(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'RR-'.$ymd.'-';
        $max = 0;

        if (! Schema::hasTable('receiving_reports_table')
            || ! Schema::hasColumn('receiving_reports_table', 'receiving_report_form_number')) {
            return $prefix.'0000001';
        }

        $query = DB::table('receiving_reports_table')
            ->whereNotNull('receiving_report_form_number')
            ->where('receiving_report_form_number', 'like', 'RR-%');

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_status')) {
            $query->where(function ($q) {
                $q->whereNull('receiving_report_status')
                    ->orWhere('receiving_report_status', '!=', 'Draft');
            });
        }

        foreach ($query->pluck('receiving_report_form_number') as $formNumber) {
            if (preg_match('/^RR-\d{6,8}-(\d{7})$/', (string) $formNumber, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        $next = min($max + 1, 9999999);

        return $prefix.str_pad((string) $next, 7, '0', STR_PAD_LEFT);
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
