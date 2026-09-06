<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves purchaser vs maintenance procurement portal (prefix, layout, routes).
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
     * Purchaser: always. Maintenance: only when user_can_procurement is enabled.
     */
    public static function userCanAccessProcurement(?object $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return false;
        }

        $roleId = (int) ($user->user_role_id ?? 0);

        if ($roleId === self::PURCHASER_ROLE_ID) {
            return true;
        }

        if ($roleId !== self::MAINTENANCE_ROLE_ID) {
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

        return $user && (int) ($user->user_role_id ?? 0) === self::MAINTENANCE_ROLE_ID
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
