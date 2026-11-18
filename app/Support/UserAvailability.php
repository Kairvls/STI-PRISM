<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Soft availability / scheduled leave for routing — does not lock login.
 *
 * Phases:
 * - scheduled (before start): still assignable, labeled as upcoming leave
 * - active (start..end): unavailable in assign dropdowns
 * - ended / available: clear
 *
 * Edit/cancel is locked within LOCK_HOURS before start. Early return after start is allowed.
 */
class UserAvailability
{
    public const AVAILABLE = 'available';

    public const LEAVE = 'leave';

    public const ABSENT = 'absent';

    /** Hours before leave start when cancel/reschedule is locked. */
    public const LOCK_HOURS = 3;

    /** Days before leave start to send the reminder announcement. */
    public const REMINDER_DAYS = 2;

    /** Default clock when the UI only cares about the day. */
    public const DEFAULT_TIME = '08:00';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::AVAILABLE, self::LEAVE, self::ABSENT];
    }

    public static function supported(): bool
    {
        return Schema::hasTable('users_table')
            && Schema::hasColumn('users_table', 'user_away_status');
    }

    public static function hasScheduleColumns(): bool
    {
        return self::supported()
            && Schema::hasColumn('users_table', 'user_away_starts_at');
    }

    public static function statusOf(object|array|null $user): string
    {
        if (! $user || ! self::supported()) {
            return self::AVAILABLE;
        }

        $status = strtolower(trim((string) (is_array($user)
            ? ($user['user_away_status'] ?? self::AVAILABLE)
            : ($user->user_away_status ?? self::AVAILABLE))));

        return in_array($status, self::statuses(), true) ? $status : self::AVAILABLE;
    }

    public static function parseDateTime(mixed $raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function startsAt(object|array|null $user): ?Carbon
    {
        if (! $user || ! self::supported()) {
            return null;
        }

        $raw = is_array($user)
            ? ($user['user_away_starts_at'] ?? null)
            : ($user->user_away_starts_at ?? null);

        return self::parseDateTime($raw);
    }

    public static function endsAt(object|array|null $user): ?Carbon
    {
        if (! $user || ! self::supported()) {
            return null;
        }

        $raw = is_array($user)
            ? ($user['user_away_until'] ?? null)
            : ($user->user_away_until ?? null);

        $parsed = self::parseDateTime($raw);
        if ($parsed && strlen(trim((string) $raw)) <= 10) {
            // Legacy date-only "back on" → morning of that day.
            return $parsed->copy()->setTimeFromTimeString(self::DEFAULT_TIME.':00');
        }

        return $parsed;
    }

    public static function noteOf(object|array|null $user): string
    {
        if (! $user) {
            return '';
        }

        return trim((string) (is_array($user)
            ? ($user['user_away_note'] ?? '')
            : ($user->user_away_note ?? '')));
    }

    /** True when status is leave/absent and a window is set (even if not started yet). */
    public static function hasAwayPlan(object|array|null $user): bool
    {
        $status = self::statusOf($user);

        return in_array($status, [self::LEAVE, self::ABSENT], true)
            && self::endsAt($user) !== null;
    }

    /** Currently inside the leave window (routing should deprioritize). */
    public static function isUnavailable(object|array|null $user): bool
    {
        if (! self::hasAwayPlan($user)) {
            return false;
        }

        $now = Carbon::now();
        $start = self::startsAt($user) ?? $now->copy()->subSecond();
        $end = self::endsAt($user);

        if ($end === null) {
            return true;
        }

        return $now->gte($start) && $now->lt($end);
    }

    /** Leave is saved but has not started yet. */
    public static function isUpcoming(object|array|null $user): bool
    {
        if (! self::hasAwayPlan($user) || self::isUnavailable($user)) {
            return false;
        }

        $start = self::startsAt($user);
        if ($start === null) {
            return false;
        }

        return Carbon::now()->lt($start);
    }

    /** Cancel/reschedule locked within LOCK_HOURS of start (and before start). */
    public static function isScheduleLocked(object|array|null $user): bool
    {
        if (! self::hasAwayPlan($user)) {
            return false;
        }

        $start = self::startsAt($user);
        if ($start === null) {
            return false;
        }

        $now = Carbon::now();
        if ($now->gte($start)) {
            // Already started — early return (Available) is allowed; changing dates is locked.
            return true;
        }

        return $now->gte($start->copy()->subHours(self::LOCK_HOURS));
    }

    /** May clear to Available even when dates are locked (after leave has started). */
    public static function canReturnEarly(object|array|null $user): bool
    {
        return self::isUnavailable($user);
    }

    public static function canEditSchedule(object|array|null $user): bool
    {
        return ! self::isScheduleLocked($user);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::LEAVE => 'On leave',
            self::ABSENT => 'Absent',
            default => 'Available',
        };
    }

    public static function formatRange(?Carbon $start, ?Carbon $end): string
    {
        if ($start && $end) {
            if ($start->isSameDay($end)) {
                return $start->format('M j, Y g:i A').' – '.$end->format('g:i A');
            }

            return $start->format('M j, Y g:i A').' – '.$end->format('M j, Y g:i A');
        }

        if ($end) {
            return 'until '.$end->format('M j, Y g:i A');
        }

        if ($start) {
            return 'from '.$start->format('M j, Y g:i A');
        }

        return '';
    }

    public static function shortLabel(object|array|null $user): string
    {
        if (! self::hasAwayPlan($user)) {
            return '';
        }

        $label = self::statusLabel(self::statusOf($user));
        $range = self::formatRange(self::startsAt($user), self::endsAt($user));

        if (self::isUpcoming($user)) {
            return $label.' scheduled '.$range;
        }

        if (self::isUnavailable($user)) {
            return $label.' '.$range;
        }

        return '';
    }

    /**
     * @return array{
     *   status:string,
     *   starts_at:?string,
     *   until:?string,
     *   note:string,
     *   unavailable:bool,
     *   upcoming:bool,
     *   locked:bool,
     *   can_return_early:bool,
     *   label:string
     * }
     */
    public static function snapshot(object|array|null $user): array
    {
        $start = self::startsAt($user);
        $end = self::endsAt($user);

        return [
            'status' => self::statusOf($user),
            'starts_at' => $start?->format('Y-m-d\TH:i'),
            'until' => $end?->format('Y-m-d\TH:i'),
            'note' => self::noteOf($user),
            'unavailable' => self::isUnavailable($user),
            'upcoming' => self::isUpcoming($user),
            'locked' => self::isScheduleLocked($user),
            'can_return_early' => self::canReturnEarly($user),
            'label' => self::shortLabel($user),
        ];
    }

    /**
     * Combine date + optional time (HH:MM) into a Carbon instance.
     */
    public static function combineInput(?string $date, ?string $time, string $fallbackTime = self::DEFAULT_TIME): ?Carbon
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        $time = trim((string) $time);
        if ($time === '') {
            $time = $fallbackTime;
        }

        return self::parseDateTime($date.' '.$time);
    }
}
