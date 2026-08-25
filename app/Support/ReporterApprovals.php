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
}
