<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ATP "No." values: ATP-YYYYMM-0001
 * Assigned on submit only — drafts stay null.
 */
class AtpFormNumber
{
    public const FORM_NUMBER_REGEX = '/^ATP-\d{6}-\d{4}$/';

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next ATP No. for the current year-month (submitted / non-draft rows only).
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

        $query = DB::table('authority_to_purchase_table')
            ->whereNotNull('authority_purchase_form_number')
            ->where('authority_purchase_form_number', 'like', $prefix.'%');

        // Soft drafts have no submitted_at; exclude them from the sequence.
        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_submitted_at')) {
            $query->whereNotNull('authority_purchase_submitted_at');
        } elseif (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_status')) {
            $query->where(function ($q) {
                $q->whereNull('authority_purchase_status')
                    ->orWhere('authority_purchase_status', '!=', 'Draft');
            });
        }

        foreach ($query->pluck('authority_purchase_form_number') as $formNumber) {
            if (preg_match('/^ATP-'.preg_quote($ym, '/').'-(\d{4})$/', (string) $formNumber, $m)) {
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

    /**
     * Keep a valid ATP-YYYYMM-#### as-is; otherwise return empty (caller may suggest next).
     */
    public static function normalizeForEdit(?string $formNumber): string
    {
        $formNumber = trim((string) $formNumber);

        return self::isValid($formNumber) ? $formNumber : '';
    }
}
