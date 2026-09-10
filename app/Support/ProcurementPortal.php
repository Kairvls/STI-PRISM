<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves procurement portal context (prefix, layout, routes).
 * Workflow UI lives in the Purchaser portal; Maintenance users with procurement
 * use multi-role portal switching (legacy /maintenance/* URLs redirect there).
 */
class ProcurementPortal
{
    public const PURCHASER = 'purchaser';

    public const MAINTENANCE = 'maintenance';

    public const ACTOR_ROLE_IDS = [2, 3];

    public const MAINTENANCE_ROLE_ID = 2;

    public const PURCHASER_ROLE_ID = 3;

    /**
     * Whether the user may use procurement workflow routes/UI.
     * Purchaser role (primary or additional): always.
     * Maintenance with user_can_procurement: legacy flag still honored (also syncs Purchaser role in Admin Users).
     */
    public static function userCanAccessProcurement(?object $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return false;
        }

        if (\App\Support\RoleAccess::hasRole(self::PURCHASER_ROLE_ID, $user)) {
            return true;
        }

        if (! \App\Support\RoleAccess::hasRole(self::MAINTENANCE_ROLE_ID, $user)) {
            return false;
        }

        if (! Schema::hasColumn('users_table', 'user_can_procurement')) {
            return true;
        }

        return (bool) ($user->user_can_procurement ?? false);
    }

    public static function isMaintenance(?object $user = null): bool
    {
        if (request()->is('maintenance') || request()->is('maintenance/*')) {
            return true;
        }

        $route = request()->route();
        if ($route && str_starts_with((string) $route->getName(), 'maintenance.')) {
            return true;
        }

        $user = $user ?? Auth::user();

        return $user
            && \App\Support\RoleAccess::hasRole(self::MAINTENANCE_ROLE_ID, $user)
            && ! (request()->is('purchaser') || request()->is('purchaser/*'));
    }

    public static function prefix(): string
    {
        return self::isMaintenance() ? self::MAINTENANCE : self::PURCHASER;
    }

    public static function layout(): string
    {
        return self::isMaintenance()
            ? 'layouts.maintenance-layout'
            : 'layouts.purchaser-layout';
    }

    public static function isActor(?object $user = null): bool
    {
        return self::userCanAccessProcurement($user);
    }

    public static function routeName(string $suffix): string
    {
        return self::prefix().'.'.ltrim($suffix, '.');
    }

    public static function route(string $suffix, mixed $parameters = [], bool $absolute = true): string
    {
        return route(self::routeName($suffix), $parameters, $absolute);
    }

    public static function redirect(string $suffix, mixed $parameters = []): RedirectResponse
    {
        return redirect()->route(self::routeName($suffix), $parameters);
    }

    public static function url(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path === ''
            ? url('/'.self::prefix())
            : url('/'.self::prefix().'/'.$path);
    }

    public static function routeIs(string $pattern): string
    {
        return self::prefix().'.'.ltrim($pattern, '.');
    }

    public static function needsPurchaserStyles(): bool
    {
        return self::isMaintenance() && (
            request()->is('maintenance/procurement*')
            || request()->is('maintenance/ris*')
            || request()->is('maintenance/authority-to-purchase*')
            || request()->is('maintenance/request-check*')
            || request()->is('maintenance/receiving-reports*')
            || request()->is('maintenance/liquidation-reports*')
            || request()->is('maintenance/procurement-records*')
            || request()->is('maintenance/suppliers*')
            || request()->is('maintenance/file-maintenance*')
            || request()->is('maintenance/brands*')
            || request()->is('maintenance/uom*')
            || request()->is('maintenance/categories*')
            || request()->is('maintenance/subcategories*')
        );
    }
}
