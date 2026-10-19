<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves procurement portal context (prefix, layout, routes).
 *
 * Purchaser: RIS → RFC → RR → Liquidation
 * Supply Officer: ATP create/submit (from Purchaser-sent RIS)
 * Administrators may still open Purchaser pages in the admin shell.
 * School Administrator approves ATP / RFC; Accounting reviews Liquidation;
 * Receiving still reviews RR.
 */
class ProcurementPortal
{
    public const PURCHASER = 'purchaser';

    public const MAINTENANCE = 'maintenance';

    public const SUPPLY_OFFICER = 'supply-officer';

    public const ACTOR_ROLE_IDS = [2, 3, 8];

    public const MAINTENANCE_ROLE_ID = 2;

    public const PURCHASER_ROLE_ID = 3;

    public const SUPPLY_OFFICER_ROLE_ID = 8;

    public const ADMIN_ROLE_ID = 1;

    /**
     * Whether the user may use Purchaser procurement workflow routes/UI.
     */
    public static function userCanAccessProcurement(?object $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return false;
        }

        if (RoleAccess::hasRole(self::PURCHASER_ROLE_ID, $user)
            || RoleAccess::isAdmin($user)) {
            return true;
        }

        if (! RoleAccess::hasRole(self::MAINTENANCE_ROLE_ID, $user)) {
            return false;
        }

        if (! Schema::hasColumn('users_table', 'user_can_procurement')) {
            return true;
        }

        return (bool) ($user->user_can_procurement ?? false);
    }

    public static function userCanAccessAtpWorkflow(?object $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return false;
        }

        return RoleAccess::hasRole(self::SUPPLY_OFFICER_ROLE_ID, $user)
            || RoleAccess::isAdmin($user);
    }

    public static function isSupplyOfficerContext(): bool
    {
        if (request()->is('supply-officer') || request()->is('supply-officer/*')) {
            return true;
        }

        $route = request()->route();

        return $route && str_starts_with((string) $route->getName(), 'supply-officer.');
    }

    public static function isMaintenance(?object $user = null): bool
    {
        if (self::isSupplyOfficerContext()) {
            return false;
        }

        if (request()->is('maintenance') || request()->is('maintenance/*')) {
            return true;
        }

        $route = request()->route();
        if ($route && str_starts_with((string) $route->getName(), 'maintenance.')) {
            return true;
        }

        $user = $user ?? Auth::user();

        return $user
            && RoleAccess::hasRole(self::MAINTENANCE_ROLE_ID, $user)
            && ! (request()->is('purchaser') || request()->is('purchaser/*'));
    }

    public static function prefix(): string
    {
        if (self::isSupplyOfficerContext()) {
            return self::SUPPLY_OFFICER;
        }

        return self::isMaintenance() ? self::MAINTENANCE : self::PURCHASER;
    }

    public static function layout(): string
    {
        if (self::isSupplyOfficerContext()) {
            return 'layouts.supply-officer-layout';
        }

        return self::isMaintenance()
            ? 'layouts.maintenance-layout'
            : 'layouts.purchaser-layout';
    }

    public static function isActor(?object $user = null): bool
    {
        return self::userCanAccessProcurement($user) || self::userCanAccessAtpWorkflow($user);
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
        if (self::isSupplyOfficerContext()) {
            return true;
        }

        return self::isMaintenance() && (
            request()->is('maintenance/procurement*')
            || request()->is('maintenance/ris*')
            || request()->is('maintenance/authority-to-purchase*')
            || request()->is('maintenance/purchase-orders*')
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
