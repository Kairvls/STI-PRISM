<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * The Administrator and School Administrator portals share the procurement, signature and
 * monitor pages. These helpers resolve links for whichever portal the request came from.
 */
class AdminPortal
{
    public const ADMIN = 'admin';

    public const SCHOOL_ADMIN = 'school-admin';

    public static function prefix(): string
    {
        $request = request();

        return $request && ($request->is(self::SCHOOL_ADMIN) || $request->is(self::SCHOOL_ADMIN.'/*'))
            ? self::SCHOOL_ADMIN
            : self::ADMIN;
    }

    public static function isSchoolAdmin(): bool
    {
        return self::prefix() === self::SCHOOL_ADMIN;
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route(self::prefix().'.'.$name, $parameters, $absolute);
    }

    public static function has(string $name): bool
    {
        return Route::has(self::prefix().'.'.$name);
    }

    public static function url(string $path = ''): string
    {
        return url(self::prefix().'/'.ltrim($path, '/'));
    }

    /**
     * RIS decisions (accept, forward, direct approve, return, Sign RIS) belong to the
     * School Administrator. The Administrator portal keeps these pages read-only.
     */
    public static function canActOnRis(?object $user = null): bool
    {
        return self::isSchoolAdmin() && RoleAccess::hasRole(RoleAccess::SCHOOL_ADMIN, $user);
    }

    public static function label(): string
    {
        return self::isSchoolAdmin() ? 'School Administrator' : 'Administrator';
    }

    public static function notificationRole(): string
    {
        return self::isSchoolAdmin() ? WorkflowNotifier::ROLE_SCHOOL_ADMIN : WorkflowNotifier::ROLE_ADMIN;
    }
}
