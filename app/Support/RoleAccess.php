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

    public const SCHOOL_ADMIN = 7;

    public const SUPPLY_OFFICER = 8;

    public const IT_DEPARTMENT_NAME = 'IT Department';

    public const PORTAL_SESSION_KEY = 'portal_context';

    /**
     * Roles kept in code/DB but dormant: not assignable and portal not reachable.
     * Supply Officer stays in the codebase while Purchaser owns the ATP path.
     *
     * @return array<int, int>
     */
    public static function dormantRoleIds(): array
    {
        return [self::SUPPLY_OFFICER];
    }

    /** @deprecated Use dormantRoleIds() */
    public static function blockedAssignableRoleIds(): array
    {
        return self::dormantRoleIds();
    }

    public static function isPortalDormant(int $roleId): bool
    {
        return in_array($roleId, self::dormantRoleIds(), true);
    }

    /**
     * Roles limited to one account system-wide (must remove/reassign the holder first).
     *
     * @return array<int, int>
     */
    public static function singletonRoleIds(): array
    {
        return [self::PRESIDENT, self::SCHOOL_ADMIN];
    }

    public static function isSingletonRole(int $roleId): bool
    {
        return in_array($roleId, self::singletonRoleIds(), true);
    }

    /**
     * User ids that currently hold $roleId (primary or additional).
     *
     * @return array<int, int>
     */
    public static function roleHolderUserIds(int $roleId): array
    {
        $roleName = WorkflowNotifier::roleNameForId($roleId);
        if (! is_string($roleName) || $roleName === '') {
            return [];
        }

        return WorkflowNotifier::userIdsForRole($roleName);
    }

    /**
     * True when a singleton role already has another account.
     * Pass $exceptUserId when editing so the current holder can keep the role.
     */
    public static function singletonRoleTaken(int $roleId, ?int $exceptUserId = null): bool
    {
        if (! self::isSingletonRole($roleId)) {
            return false;
        }

        foreach (self::roleHolderUserIds($roleId) as $userId) {
            if ($exceptUserId !== null && (int) $userId === (int) $exceptUserId) {
                continue;
            }

            return true;
        }

        return false;
    }

    public static function singletonRoleMessage(int $roleId): string
    {
        $label = self::portalMeta()[$roleId]['label'] ?? 'This role';

        return "Only one {$label} account is allowed. Remove or reassign the existing {$label} account first.";
    }

    public static function isAssignableRoleId(int $roleId, ?int $allowIfCurrent = null): bool
    {
        if ($allowIfCurrent !== null && $roleId === $allowIfCurrent) {
            return true;
        }

        if (self::isPortalDormant($roleId)) {
            return false;
        }

        // allowIfCurrent is the user's current primary role id on edit — when assigning
        // a different role, still block singleton roles that another user already holds.
        if (self::singletonRoleTaken($roleId)) {
            return false;
        }

        return true;
    }

    public static function dormantPortalMessage(): string
    {
        return 'The School Supply / Material Officer portal is temporarily unavailable. Ask an administrator to assign another role.';
    }

    /**
     * Post-login home path, skipping dormant portals. Null = no usable portal.
     */
    public static function loginDestination(?object $user = null): ?string
    {
        $user = self::user($user);
        if (! $user) {
            return null;
        }

        $primary = self::primaryRoleId($user);
        if ($primary > 0 && ! self::isPortalDormant($primary)) {
            return self::dashboardPath($primary);
        }

        foreach (self::availablePortals($user) as $portal) {
            return $portal['path'];
        }

        return null;
    }

    /**
     * Additional roles: Staff in IT Department only, when primary is
     * Administrator, Maintenance, or Purchaser. Pool is Maintenance + Purchaser
     * (excluding the primary itself).
     *
     * @param  array<int, int|string>  $additional
     * @return array<int, int>
     */
    public static function filterAllowedAdditionalRoles(
        int $primaryRoleId,
        array $additional,
        bool $isStaff,
        bool $isItDepartment
    ): array {
        $allowed = self::allowedAdditionalRoleIds($primaryRoleId, $isStaff, $isItDepartment);
        if ($allowed === []) {
            return [];
        }

        return collect($additional)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public static function allowedAdditionalRoleIds(
        int $primaryRoleId,
        bool $isStaff = true,
        bool $isItDepartment = true
    ): array {
        if (! $isStaff || ! $isItDepartment) {
            return [];
        }

        if (! in_array($primaryRoleId, [self::ADMIN, self::MAINTENANCE, self::PURCHASER], true)) {
            return [];
        }

        return array_values(array_filter(
            [self::MAINTENANCE, self::PURCHASER],
            fn (int $id) => $id !== $primaryRoleId
        ));
    }

    /** @deprecated Prefer allowedAdditionalRoleIds() */
    public static function allowedAdditionalRoleId(
        int $primaryRoleId,
        bool $isStaff = true,
        bool $isItDepartment = true
    ): ?int {
        $ids = self::allowedAdditionalRoleIds($primaryRoleId, $isStaff, $isItDepartment);

        return $ids[0] ?? null;
    }

    public static function isItDepartmentName(?string $name): bool
    {
        return strcasecmp(trim((string) $name), self::IT_DEPARTMENT_NAME) === 0;
    }

    public static function personIsItDepartment(?object $person): bool
    {
        if (! $person) {
            return false;
        }

        $name = $person->department_name ?? null;
        if ($name === null && ! empty($person->person_department_id) && Schema::hasTable('departments_table')) {
            $name = DB::table('departments_table')
                ->where('department_id', (int) $person->person_department_id)
                ->value('department_name');
        }

        return self::isItDepartmentName(is_string($name) ? $name : null);
    }

    public static function isStaffEmploymentType(?string $type): bool
    {
        return strcasecmp(trim((string) $type), 'Staff') === 0;
    }

    public static function isStaffEmployeeId(?string $employeeId): bool
    {
        return (bool) preg_match('/S$/i', trim((string) $employeeId));
    }

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
                'label' => 'System Administrator',
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
            self::SCHOOL_ADMIN => [
                'key' => 'school-admin',
                'label' => 'School Administrator',
                'path' => '/school-admin/dashboard',
                'match' => 'school-admin',
            ],
            self::SUPPLY_OFFICER => [
                'key' => 'supply-officer',
                'label' => 'School Supply / Material Officer',
                'path' => '/supply-officer/dashboard',
                'match' => 'supply-officer',
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
            if (! isset($meta[$roleId]) || self::isPortalDormant($roleId)) {
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
     * Mobile app portals: System Administrator and Maintenance only
     * (primary or additional). Purchaser and other roles stay web-only.
     *
     * @return array<int, array{key: string, label: string, path: string, match: string, role_id: int}>
     */
    public static function mobilePortals(?object $user = null): array
    {
        $allowed = [self::ADMIN, self::MAINTENANCE];

        return array_values(array_filter(
            self::availablePortals($user),
            fn (array $portal) => in_array((int) $portal['role_id'], $allowed, true)
        ));
    }

    public static function currentPortalKey(): ?string
    {
        return self::adminShell() ? 'admin' : self::pathPortalKey();
    }

    /**
     * Remembers which portal the user last entered, so shared pages know which shell to show.
     */
    public static function enterPortal(string $key): void
    {
        if (request()->hasSession()) {
            request()->session()->put(self::PORTAL_SESSION_KEY, $key);
        }
    }

    /**
     * An administrator using a Maintenance or Purchaser page (reporters, inventory, RIS…)
     * sees it inside the admin sidebar and topbar. Admins who also hold that role get the
     * admin shell only while they are working from the admin portal.
     */
    public static function adminShell(?object $user = null): bool
    {
        $user = self::user($user);
        if (! $user || ! self::isAdmin($user)) {
            return false;
        }

        $portal = self::pathPortalKey();
        if ($portal === 'admin') {
            return true;
        }

        $roleId = ['maintenance' => self::MAINTENANCE, 'purchaser' => self::PURCHASER][$portal] ?? null;
        if ($roleId === null) {
            return false;
        }

        return ! self::hasRole($roleId, $user)
            || (request()->hasSession() && request()->session()->get(self::PORTAL_SESSION_KEY) === 'admin');
    }

    private static function pathPortalKey(): ?string
    {
        foreach (self::portalMeta() as $portal) {
            $match = $portal['match'];
            if (request()->is($match) || request()->is($match.'/*')) {
                return $portal['key'];
            }
        }

        return null;
    }

    /**
     * The person's main role (user_role_id), shown under their name in every portal.
     */
    public static function primaryRoleLabel(?object $user = null): string
    {
        return self::portalMeta()[self::primaryRoleId($user)]['label'] ?? 'User';
    }

    /**
     * Sidebar subtitle: plain role name for the user's primary portal, "X Panel" for additional roles.
     */
    public static function sidebarPortalLabel(string $portalKey, ?object $user = null): string
    {
        foreach (self::portalMeta() as $roleId => $portal) {
            if ($portal['key'] !== $portalKey) {
                continue;
            }

            return $roleId === self::primaryRoleId($user)
                ? $portal['label']
                : $portal['label'].' Panel';
        }

        return 'Portal';
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
