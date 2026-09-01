<?php

namespace App\Support;

/**
 * Payment path constants for the purchaser procurement fork after ATP approval.
 */
class ProcurementPaymentPath
{
    public const REQUEST_FOR_CHECK = 'request_for_check';

    public const CASH_ADVANCE = 'cash_advance';

    public static function labels(): array
    {
        return [
            self::REQUEST_FOR_CHECK => 'Request for Check',
            self::CASH_ADVANCE => 'Cash Advance',
        ];
    }

    public static function label(?string $path): string
    {
        if (!$path) {
            return 'Not chosen';
        }

        return self::labels()[$path] ?? ucwords(str_replace('_', ' ', $path));
    }

    public static function isValid(?string $path): bool
    {
        return in_array($path, [self::REQUEST_FOR_CHECK, self::CASH_ADVANCE], true);
    }

    public static function requiresLiquidation(?string $path): bool
    {
        return $path === self::CASH_ADVANCE;
    }

    public static function allowsMultiSupplier(?string $path): bool
    {
        return $path === self::CASH_ADVANCE;
    }

    public static function fundingTypeForPath(?string $path): ?string
    {
        return self::isValid($path) ? $path : null;
    }
}
