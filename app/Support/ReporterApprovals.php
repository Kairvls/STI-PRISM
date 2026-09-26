<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporterApprovals
{
    public const TABLE = 'reporter_approval_requests';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public static function hasTable(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public static function query()
    {
        return DB::table(self::TABLE);
    }

    public static function pendingCount(): int
    {
        if (! self::hasTable()) {
            return 0;
        }

        return self::query()
            ->where('status', self::STATUS_PENDING)
            ->count();
    }

    public static function pendingByEmail(string $email)
    {
        if (! self::hasTable()) {
            return null;
        }

        return self::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->where('status', self::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();
    }

    public static function pendingByEmployeeId(string $employeeId)
    {
        if (! self::hasTable()) {
            return null;
        }

        return self::query()
            ->where('employee_id', trim($employeeId))
            ->where('status', self::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Five-digit core from OMC00127F / OMC00127S (same person, different type letter).
     * Legacy 4-digit IDs are left-padded to 5 for matching.
     */
    public static function employeeNumber(string $employeeId): ?string
    {
        $id = strtoupper(preg_replace('/\s+/', '', trim($employeeId)));

        if (preg_match('/^OMC(\d{4,5})[FS]$/', $id, $match)) {
            return str_pad($match[1], 5, '0', STR_PAD_LEFT);
        }

        return null;
    }

    public static function employeeIdVariants(string $employeeId): array
    {
        $number = self::employeeNumber($employeeId);

        if (! $number) {
            $trimmed = trim($employeeId);

            return $trimmed === '' ? [] : [$trimmed];
        }

        $variants = ['OMC'.$number.'F', 'OMC'.$number.'S'];

        // Keep legacy 4-digit forms discoverable while migrating to 5 digits.
        if (strlen($number) === 5 && str_starts_with($number, '0')) {
            $legacy = substr($number, 1);
            $variants[] = 'OMC'.$legacy.'F';
            $variants[] = 'OMC'.$legacy.'S';
        }

        return array_values(array_unique($variants));
    }

    public static function registeredByEmployeeNumber(string $employeeId)
    {
        $variants = self::employeeIdVariants($employeeId);

        if ($variants === []) {
            return null;
        }

        return DB::table('reporters_table')
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $query->orWhereRaw('UPPER(TRIM(reporter_employee_id)) = ?', [strtoupper($variant)]);
                }
            })
            ->first();
    }

    public static function pendingByEmployeeNumber(string $employeeId)
    {
        if (! self::hasTable()) {
            return null;
        }

        $variants = self::employeeIdVariants($employeeId);

        if ($variants === []) {
            return null;
        }

        return self::query()
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $query->orWhereRaw('UPPER(TRIM(employee_id)) = ?', [strtoupper($variant)]);
                }
            })
            ->orderByDesc('id')
            ->first();
    }
}
