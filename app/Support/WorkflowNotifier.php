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

    private const ROLE_IDS = [
        self::ROLE_ADMIN => 1,
        self::ROLE_PURCHASER => 3,
        self::ROLE_PRESIDENT => 4,
        self::ROLE_ACCOUNTING => 5,
        self::ROLE_RECEIVING => 6,
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

    public static function userIdsForRole(string $role): array
    {
        $roleId = self::ROLE_IDS[$role] ?? null;
        if ($roleId === null || !Schema::hasTable('users_table')) {
            return [];
        }

        try {
            return DB::table('users_table')
                ->where('user_role_id', $roleId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
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
