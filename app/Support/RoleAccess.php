<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-role helpers. user_role_id remains the primary (default dashboard) role.
 * Login authenticates the person; roles gate portals after sign-in.
 */
class RoleAccess
{
    public const ADMIN = 1;

    public const MAINTENANCE = 2;

    public const PURCHASER = 3;

    public const PRESIDENT = 4;

    public const ACCOUNTING = 5;

    public const RECEIVING = 6;

    public static function user(?object $user = null): ?object
    {
        return $user ?? Auth::user();
    }

    /**
     * @return array<int, int>
     */
    public static function roleIds(?object $user = null): array
    {
        $user = self::user($user);
        if (! $user) {
            return [];
        }

        $primary = (int) ($user->user_role_id ?? 0);
        $ids = $primary > 0 ? [$primary] : [];

        if ($user instanceof User && $user->relationLoaded('roles')) {
            foreach ($user->roles as $role) {
                $ids[] = (int) $role->role_id;
            }
        } elseif (Schema::hasTable('user_roles_table')) {
            $userId = (int) ($user->user_id ?? 0);
            if ($userId > 0) {
                $extra = DB::table('user_roles_table')
                    ->where('user_id', $userId)
                    ->pluck('role_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $ids = array_merge($ids, $extra);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public static function hasRole(int $roleId, ?object $user = null): bool
    {
        return in_array($roleId, self::roleIds($user), true);
    }

    public static function hasAnyRole(array $roleIds, ?object $user = null): bool
    {
        $owned = self::roleIds($user);

        foreach ($roleIds as $roleId) {
            if (in_array((int) $roleId, $owned, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isAdmin(?object $user = null): bool
    {
        return self::hasRole(self::ADMIN, $user)
            || (int) (self::user($user)->user_role_id ?? 0) === self::ADMIN;
    }

    public static function primaryRoleId(?object $user = null): int
    {
        return (int) (self::user($user)->user_role_id ?? 0);
    }

    /**
     * Sync pivot roles. Always includes primary role.
     *
     * @param  array<int, int|string>  $roleIds
     */
    public static function syncRoles(int $userId, int $primaryRoleId, array $roleIds = []): void
    {
        if (! Schema::hasTable('user_roles_table')) {
            return;
        }

        $normalized = collect($roleIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->push($primaryRoleId)
            ->unique()
            ->values()
            ->all();

        DB::table('user_roles_table')->where('user_id', $userId)->delete();

        $now = now();
        foreach ($normalized as $roleId) {
            DB::table('user_roles_table')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_at' => $now,
            ]);
        }
    }

    /**
     * Dashboard path for a primary role id.
     */
    public static function dashboardPath(int $primaryRoleId): string
    {
        return self::portalMeta()[$primaryRoleId]['path'] ?? '/';
    }

    /**
     * @return array<int, array{key: string, label: string, path: string, match: string}>
     */
    public static function portalMeta(): array
    {
        return [
            self::ADMIN => [
                'key' => 'admin',
                'label' => 'Administrator',
                'path' => '/admin/dashboard',
                'match' => 'admin',
            ],
            self::MAINTENANCE => [
                'key' => 'maintenance',
                'label' => 'Maintenance',
                'path' => '/maintenance/dashboard',
                'match' => 'maintenance',
            ],
            self::PURCHASER => [
                'key' => 'purchaser',
                'label' => 'Purchaser',
                'path' => '/purchaser/dashboard',
                'match' => 'purchaser',
            ],
            self::PRESIDENT => [
                'key' => 'president',
                'label' => 'President',
                'path' => '/president/dashboard',
                'match' => 'president',
            ],
            self::ACCOUNTING => [
                'key' => 'accounting',
                'label' => 'Accounting',
                'path' => '/accounting/dashboard',
                'match' => 'accounting',
            ],
            self::RECEIVING => [
                'key' => 'receiving',
                'label' => 'Receiving',
                'path' => '/receiving/dashboard',
                'match' => 'receiving',
            ],
        ];
    }

    /**
     * Portals the user may open based on assigned roles.
     *
     * @return array<int, array{key: string, label: string, path: string, match: string, role_id: int}>
     */
    public static function availablePortals(?object $user = null): array
    {
        $portals = [];
        $meta = self::portalMeta();

        foreach (self::roleIds($user) as $roleId) {
            if (! isset($meta[$roleId])) {
                continue;
            }
            $portals[] = array_merge($meta[$roleId], ['role_id' => $roleId]);
        }

        return $portals;
    }

    public static function hasMultiplePortals(?object $user = null): bool
    {
        return count(self::availablePortals($user)) > 1;
    }

    /**
     * Mobile app portals only (Maintenance).
     * Purchaser stays web-only; assign Maintenance as additional role for app access.
     *
     * @return array<int, array{key: string, label: string, path: string, match: string, role_id: int}>
     */
    public static function mobilePortals(?object $user = null): array
    {
        $allowed = [self::MAINTENANCE];

        return array_values(array_filter(
            self::availablePortals($user),
            fn (array $portal) => in_array((int) $portal['role_id'], $allowed, true)
        ));
    }

    public static function currentPortalKey(): ?string
    {
        foreach (self::portalMeta() as $portal) {
            $match = $portal['match'];
            if (request()->is($match) || request()->is($match.'/*')) {
                return $portal['key'];
            }
        }

        return null;
    }

    public static function currentPortalLabel(?object $user = null): string
    {
        $key = self::currentPortalKey();
        foreach (self::availablePortals($user) as $portal) {
            if ($portal['key'] === $key) {
                return $portal['label'];
            }
        }

        $primary = self::primaryRoleId($user);

        return self::portalMeta()[$primary]['label'] ?? 'Portal';
    }
}
