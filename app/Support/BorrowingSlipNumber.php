<?php

namespace App\Support;

use Carbon\Carbon;
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

    /**
     * Give older one-item borrows a slip number so they can be opened as a BRW.
     */
    public static function backfillMissing(): void
    {
        if (
            ! Schema::hasTable('borrowing_records_table')
            || ! Schema::hasColumn('borrowing_records_table', 'borrowing_transaction_no')
        ) {
            return;
        }

        $columns = ['borrowing_record_id'];
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_date')) {
            $columns[] = 'borrowing_date';
        }
        if (Schema::hasColumn('borrowing_records_table', 'borrowing_created_at')) {
            $columns[] = 'borrowing_created_at';
        }

        $missing = DB::table('borrowing_records_table')
            ->where(function ($query) {
                $query->whereNull('borrowing_transaction_no')
                    ->orWhere('borrowing_transaction_no', '');
            })
            ->orderBy('borrowing_record_id')
            ->get($columns);

        if ($missing->isEmpty()) {
            return;
        }

        $used = [];
        $existing = DB::table('borrowing_records_table')
            ->whereNotNull('borrowing_transaction_no')
            ->where('borrowing_transaction_no', '!=', '')
            ->pluck('borrowing_transaction_no');

        foreach ($existing as $number) {
            if (preg_match('/^BRW-(\d{6})-(\d+)$/', (string) $number, $match)) {
                $used[$match[1]] = max($used[$match[1]] ?? 0, (int) $match[2]);
            }
        }

        foreach ($missing as $row) {
            $when = $row->borrowing_date ?? $row->borrowing_created_at ?? null;
            try {
                $month = $when ? Carbon::parse($when)->format('Ym') : now()->format('Ym');
            } catch (\Throwable) {
                $month = now()->format('Ym');
            }

            $sequence = min(($used[$month] ?? 0) + 1, 9999);
            $used[$month] = $sequence;
            DB::table('borrowing_records_table')
                ->where('borrowing_record_id', $row->borrowing_record_id)
                ->where(function ($query) {
                    $query->whereNull('borrowing_transaction_no')
                        ->orWhere('borrowing_transaction_no', '');
                })
                ->update([
                    'borrowing_transaction_no' => self::PREFIX.'-'.$month.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                ]);
        }
    }
}
