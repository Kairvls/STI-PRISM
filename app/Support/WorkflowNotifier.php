<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WorkflowNotifier
{
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_PURCHASER = 'Purchaser';
    public const ROLE_PRESIDENT = 'President';
    public const ROLE_ACCOUNTING = 'Accounting';
    public const ROLE_RECEIVING = 'Receiving Officer';
    public const ROLE_MAINTENANCE = 'Maintenance Personnel';
    public const ROLE_SCHOOL_ADMIN = 'School Administrator';

    private const ROLE_IDS = [
        self::ROLE_ADMIN => 1,
        self::ROLE_MAINTENANCE => 2,
        self::ROLE_PURCHASER => 3,
        self::ROLE_PRESIDENT => 4,
        self::ROLE_ACCOUNTING => 5,
        self::ROLE_RECEIVING => 6,
        self::ROLE_SCHOOL_ADMIN => 7,
    ];

    public static function toUser(
        $userId,
        string $role,
        string $title,
        string $message,
        string $type,
        string $refType,
        int $refId,
        string $url,
        string $category = 'workflow'
    ): void {
        self::insert($userId ? (int) $userId : null, $role, $title, $message, $type, $refType, $refId, $url, $category);
    }

    public static function toRole(
        string $role,
        string $title,
        string $message,
        string $type,
        string $refType,
        int $refId,
        string $url,
        string $category = 'workflow'
    ): void {
        $ids = self::userIdsForRole($role);
        if ($ids === []) {
            self::insert(null, $role, $title, $message, $type, $refType, $refId, $url, $category);
            return;
        }
        foreach ($ids as $id) {
            self::insert((int) $id, $role, $title, $message, $type, $refType, $refId, $url, $category);
        }
    }

    /**
     * Display name of a notification recipient, falling back to the given label (e.g. the role) when unknown.
     */
    public static function recipientName($userId, string $fallback): string
    {
        $userId = (int) ($userId ?? 0);
        if ($userId <= 0) {
            return $fallback;
        }

        try {
            $name = trim((string) DB::table('users_table')->where('user_id', $userId)->value('user_full_name'));
        } catch (\Throwable $e) {
            $name = '';
        }

        return $name !== '' ? $name : $fallback;
    }

    /**
     * Notifications addressed to this user, plus role-wide broadcasts that have no specific recipient.
     * A notification sent to another user of the same role stays private to that user.
     */
    public static function scopeVisibleTo($query, $userId, string $role)
    {
        return $query->where(function ($q) use ($userId, $role) {
            $q->where('notifications_table.notification_user_id', $userId)
                ->orWhere(function ($q) use ($role) {
                    $q->whereNull('notifications_table.notification_user_id')
                        ->where('notifications_table.notification_target_role', $role);
                });
        });
    }

    public static function userIdsForRole(string $role): array
    {
        $roleId = self::ROLE_IDS[$role] ?? null;
        if ($roleId === null || ! Schema::hasTable('users_table')) {
            return [];
        }

        try {
            $primaryIds = DB::table('users_table')
                ->where('user_role_id', $roleId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $extraIds = [];
            if (Schema::hasTable('user_roles_table')) {
                $extraIds = DB::table('user_roles_table')
                    ->where('role_id', $roleId)
                    ->pluck('user_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }

            return array_values(array_unique(array_merge($primaryIds, $extraIds)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function insert(
        ?int $userId,
        string $role,
        string $title,
        string $message,
        string $type,
        string $refType,
        int $refId,
        string $url,
        string $category
    ): void {
        if (!Schema::hasTable('notifications_table')) {
            return;
        }

        try {
            DB::table('notifications_table')->insert([
                'notification_user_id' => $userId,
                'notification_target_role' => $role,
                'notification_title' => $title,
                'notification_message' => $message,
                'notification_type' => $type,
                'notification_category' => $category,
                'notification_reference_type' => $refType,
                'notification_reference_id' => $refId,
                'notification_url' => $url,
                'notification_event_key' => Str::uuid()->toString(),
                'notification_created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
