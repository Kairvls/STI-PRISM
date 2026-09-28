<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Back order "No." values: BO-RFC-YYYYMM-00001 (Request for Check) or BO-CA-YYYYMM-00001 (Cash Advance).
 * Each prefix has its own monthly sequence.
 */
class BackOrderNumber
{
    public const REGEX = '/^BO-(RFC|CA)-\d{6}-\d{5}$/';

    public static function code(?string $paymentPath): string
    {
        return $paymentPath === ProcurementPaymentPath::CASH_ADVANCE ? 'CA' : 'RFC';
    }

    public static function prefix(?string $paymentPath, ?\DateTimeInterface $date = null): string
    {
        return 'BO-'.self::code($paymentPath).'-'.($date ? $date->format('Ym') : now()->format('Ym')).'-';
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::REGEX, trim($value));
    }

    public static function next(?string $paymentPath, ?\DateTimeInterface $date = null): string
    {
        $prefix = self::prefix($paymentPath, $date);
        $max = 0;
        foreach (DB::table(BackOrders::TABLE)->where('back_order_number', 'like', $prefix.'%')->pluck('back_order_number') as $number) {
            if (preg_match('/^'.preg_quote($prefix, '/').'(\d{5})$/', (string) $number, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $prefix.str_pad((string) min($max + 1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Insert a back order with the next number, retrying if another request took the same number first.
     */
    public static function insert(array $row): int
    {
        if (! Schema::hasColumn(BackOrders::TABLE, 'back_order_number')) {
            return (int) DB::table(BackOrders::TABLE)->insertGetId($row, 'back_order_id');
        }

        for ($attempt = 1; ; $attempt++) {
            $row['back_order_number'] = self::next($row['back_order_payment_path'] ?? null);
            try {
                return (int) DB::table(BackOrders::TABLE)->insertGetId($row, 'back_order_id');
            } catch (QueryException $e) {
                if ($attempt >= 5 || (int) ($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }
            }
        }
    }

    /** Display label, falling back to the row id for back orders created before numbering. */
    public static function label(?object $bo): string
    {
        if (! $bo) {
            return 'Back order';
        }

        return ($bo->back_order_number ?? null) ?: 'BO #'.(int) ($bo->back_order_id ?? 0);
    }
}
