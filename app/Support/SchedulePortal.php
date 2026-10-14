<?php

namespace App\Support;

/**
 * URLs for the Maintenance Schedule module (equipment, room and semestral),
 * which runs under both /maintenance and /admin.
 */
class SchedulePortal
{
    public static function inAdmin(): bool
    {
        return request()->is('admin/*');
    }

    public static function base(): string
    {
        return self::inAdmin() ? '/admin' : '/maintenance';
    }

    public static function schedulesUrl(string $path = ''): string
    {
        return url(self::base().'/schedules'.$path);
    }

    public static function semesterUrl(string $path = ''): string
    {
        $base = AdminPortal::isSchoolAdmin() ? '/school-admin' : self::base();

        return url($base.'/semester-inspections'.$path);
    }

    public static function canManageSemester(): bool
    {
        return ! AdminPortal::isSchoolAdmin()
            && (self::inAdmin() || RoleAccess::hasRole(RoleAccess::MAINTENANCE));
    }
}
