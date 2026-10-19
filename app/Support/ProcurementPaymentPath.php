<?php

namespace App\Support;

/**
 * Payment path / RFC funding type after ATP approval.
 *
 * Direct Payment and Cash Installment end at Receiving Report.
 * Cash Advance ends at Liquidation Report.
 */
class ProcurementPaymentPath
{
    public const DIRECT_PAYMENT = 'direct_payment';

    public const CASH_INSTALLMENT = 'cash_installment';

    public const CASH_ADVANCE = 'cash_advance';

    /** @deprecated Use DIRECT_PAYMENT. Kept so existing rows still resolve. */
    public const REQUEST_FOR_CHECK = 'request_for_check';

    public static function labels(): array
    {
        return [
            self::DIRECT_PAYMENT => 'Direct Payment',
            self::CASH_INSTALLMENT => 'Cash Installment',
            self::CASH_ADVANCE => 'Cash Advance',
        ];
    }

    /**
     * Values accepted on new writes (no legacy alias).
     *
     * @return list<string>
     */
    public static function choices(): array
    {
        return [
            self::DIRECT_PAYMENT,
            self::CASH_INSTALLMENT,
            self::CASH_ADVANCE,
        ];
    }

    /**
     * Values accepted when reading / validating stored rows (includes legacy).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DIRECT_PAYMENT,
            self::CASH_INSTALLMENT,
            self::CASH_ADVANCE,
            self::REQUEST_FOR_CHECK,
        ];
    }

    public static function normalize(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if ($path === self::REQUEST_FOR_CHECK) {
            return self::DIRECT_PAYMENT;
        }

        return self::isValid($path) ? $path : null;
    }

    public static function label(?string $path): string
    {
        if (! $path) {
            return 'Not chosen';
        }

        $normalized = self::normalize($path) ?? $path;

        return self::labels()[$normalized]
            ?? ucwords(str_replace('_', ' ', $path));
    }

    public static function isValid(?string $path): bool
    {
        return in_array((string) $path, self::all(), true);
    }

    /** Direct Payment and Cash Installment (plus legacy RFC) stop at RR. */
    public static function endsAtReceivingReport(?string $path): bool
    {
        $normalized = self::normalize($path);

        return in_array($normalized, [self::DIRECT_PAYMENT, self::CASH_INSTALLMENT], true)
            || $path === self::REQUEST_FOR_CHECK;
    }

    /** Cash Advance continues to Liquidation Report. */
    public static function requiresLiquidation(?string $path): bool
    {
        return self::normalize($path) === self::CASH_ADVANCE;
    }

    public static function endsAtLiquidation(?string $path): bool
    {
        return self::requiresLiquidation($path);
    }

    public static function allowsMultiSupplier(?string $path): bool
    {
        return self::requiresLiquidation($path);
    }

    public static function fundingTypeForPath(?string $path): ?string
    {
        $normalized = self::normalize($path);

        return $normalized ?: null;
    }

    /**
     * SQL-friendly list of stored values that belong to a fund filter tab.
     *
     * @return list<string>
     */
    public static function storedValuesForFilter(?string $filter): array
    {
        $normalized = self::normalize($filter) ?? $filter;

        return match ($normalized) {
            self::DIRECT_PAYMENT => [self::DIRECT_PAYMENT, self::REQUEST_FOR_CHECK],
            self::CASH_INSTALLMENT => [self::CASH_INSTALLMENT],
            self::CASH_ADVANCE => [self::CASH_ADVANCE],
            default => self::all(),
        };
    }
}
