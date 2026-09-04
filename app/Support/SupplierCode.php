<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SupplierCode
{
    /**
     * Build a display/store code: TYPE-NAMEABBR-YYMMDD
     * Examples: ONL-EASYPC-260904, PHY-ORMNET-260827
     */
    public static function generate(
        string $storeType,
        ?string $name,
        CarbonInterface|string|null $createdAt = null
    ): string {
        $type = str_contains(strtolower($storeType), 'online') ? 'ONL' : 'PHY';
        $abbr = self::abbreviateName($name);
        $date = self::resolveDate($createdAt)->format('ymd');

        return sprintf('%s-%s-%s', $type, $abbr, $date);
    }

    public static function abbreviateName(?string $name): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', (string) $name) ?? '');

        if ($clean === '') {
            return 'SUPP';
        }

        return substr($clean, 0, 8);
    }

    private static function resolveDate(CarbonInterface|string|null $createdAt): CarbonInterface
    {
        if ($createdAt instanceof CarbonInterface) {
            return $createdAt;
        }

        if (is_string($createdAt) && trim($createdAt) !== '') {
            return Carbon::parse($createdAt);
        }

        return Carbon::now();
    }
}
