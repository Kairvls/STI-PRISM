<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In-app announcements for scheduled leave / absence.
 */
class AvailabilityAnnouncer
{
    /**
     * Roles that should hear about someone going away (besides the person's own role peers).
     *
     * @return list<string>
     */
    public static function audienceRolesFor(object $user): array
    {
        $roles = [
            WorkflowNotifier::ROLE_ADMIN,
            WorkflowNotifier::ROLE_SCHOOL_ADMIN,
        ];

        $primaryRoleId = (int) ($user->user_role_id ?? 0);
        $ownRole = WorkflowNotifier::roleNameForId($primaryRoleId);
        if ($ownRole) {
            $roles[] = $ownRole;
        }

        // Extra roles from pivot.
        if (Schema::hasTable('user_roles_table')) {
            try {
                $extra = DB::table('user_roles_table')
                    ->where('user_id', (int) $user->user_id)
                    ->pluck('role_id');
                foreach ($extra as $roleId) {
                    $name = WorkflowNotifier::roleNameForId((int) $roleId);
                    if ($name) {
                        $roles[] = $name;
                    }
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        return array_values(array_unique($roles));
    }

    public static function announce(
        object $user,
        string $title,
        string $message,
        string $type
    ): void {
        $userId = (int) ($user->user_id ?? 0);
        if ($userId < 1) {
            return;
        }

        $url = '/';
        foreach (self::audienceRolesFor($user) as $role) {
            $ids = WorkflowNotifier::userIdsForRole($role);
            foreach ($ids as $id) {
                if ((int) $id === $userId) {
                    continue; // don't notify yourself
                }
                WorkflowNotifier::toUser(
                    (int) $id,
                    $role,
                    $title,
                    $message,
                    $type,
                    'USER',
                    $userId,
                    $url,
                    'availability'
                );
            }
        }
    }

    public static function announceScheduled(object $user): void
    {
        $name = trim((string) ($user->user_full_name ?? '')) ?: 'A teammate';
        $label = UserAvailability::statusLabel(UserAvailability::statusOf($user));
        $range = UserAvailability::formatRange(
            UserAvailability::startsAt($user),
            UserAvailability::endsAt($user)
        );
        $note = UserAvailability::noteOf($user);
        $suffix = $note !== '' ? ' Note: '.$note : '';

        self::announce(
            $user,
            $name.' scheduled '.$label,
            $name.' will be away '.$range.'.'.$suffix,
            'user_away_scheduled'
        );
    }

    public static function announceUpdated(object $user): void
    {
        $name = trim((string) ($user->user_full_name ?? '')) ?: 'A teammate';
        $label = UserAvailability::statusLabel(UserAvailability::statusOf($user));
        $range = UserAvailability::formatRange(
            UserAvailability::startsAt($user),
            UserAvailability::endsAt($user)
        );

        self::announce(
            $user,
            $name.' updated '.$label.' schedule',
            $name.' rescheduled away time to '.$range.'.',
            'user_away_updated'
        );
    }

    public static function announceCancelled(object $user, string $previousLabel = 'leave'): void
    {
        $name = trim((string) ($user->user_full_name ?? '')) ?: 'A teammate';

        self::announce(
            $user,
            $name.' cancelled '.$previousLabel,
            $name.' is available again (scheduled away time was cancelled).',
            'user_away_cancelled'
        );
    }

    public static function announceReminder(object $user): void
    {
        $name = trim((string) ($user->user_full_name ?? '')) ?: 'A teammate';
        $label = UserAvailability::statusLabel(UserAvailability::statusOf($user));
        $range = UserAvailability::formatRange(
            UserAvailability::startsAt($user),
            UserAvailability::endsAt($user)
        );

        self::announce(
            $user,
            $name.' '.$label.' starts soon',
            $name.' is on '.$label.' by this schedule: '.$range.'.',
            'user_away_reminder'
        );
    }
}
