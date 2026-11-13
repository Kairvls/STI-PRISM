<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared slip / transaction number for one multi-item borrow operation.
 *
 * Format: BRW-YYYYMM-0001
 */
class BorrowingSlipNumber
{
    public const PREFIX = 'BRW';

    public const FORM_NUMBER_REGEX = '/^BRW-\d{6}-\d{4}$/';

    public static function uiLabel(): string
    {
        return 'Slip No.';
    }

    public static function allocate(): string
    {
        $ym = now()->format('Ym');
        $prefix = self::PREFIX.'-'.$ym.'-';
        $next = 1;

        if (
            Schema::hasTable('borrowing_records_table')
            && Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')
        ) {
            $latestRows = DB::table('borrowing_records_table')
                ->whereNotNull('borrowing_transaction_no')
                ->where('borrowing_transaction_no', 'like', self::PREFIX.'-%')
                ->orderByDesc('borrowing_transaction_no')
                ->limit(200)
                ->pluck('borrowing_transaction_no');

            foreach ($latestRows as $latest) {
                if (! is_string($latest) || $latest === '') {
                    continue;
                }
                if (preg_match('/^BRW-\d{6}-(\d+)$/', $latest, $m)) {
                    $next = max($next, ((int) $m[1]) + 1);
                }
            }
        }

        $next = min($next, 9999);

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
