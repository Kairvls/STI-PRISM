<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EquipmentSchedules
{
    /** Days before next_date when "Mark complete" becomes available again. */
    public const COMPLETION_OPEN_DAYS = 7;

    public const FREQUENCIES = ['Monthly', 'Quarterly', 'Semi Annual', 'Annual'];

    /**
     * First due date after today for a recurring frequency, stepping from the scheduled date.
     */
    public static function nextDateAfter(string $frequency, ?string $scheduledFor): ?string
    {
        $step = match ($frequency) {
            'Weekly' => fn (CarbonInterface $d) => $d->addWeeks(1),
            'Monthly' => fn (CarbonInterface $d) => $d->addMonthsNoOverflow(1),
            'Quarterly' => fn (CarbonInterface $d) => $d->addMonthsNoOverflow(3),
            'Semi Annual' => fn (CarbonInterface $d) => $d->addMonthsNoOverflow(6),
            'Annual' => fn (CarbonInterface $d) => $d->addYearsNoOverflow(1),
            default => null,
        };

        if (! $step) {
            return null;
        }

        $date = $scheduledFor
            ? Carbon::parse($scheduledFor)->startOfDay()
            : today();

        do {
            $date = $step($date->copy());
        } while ($date->lte(today()));

        return $date->toDateString();
    }

    public static function isRecurring(?string $frequency): bool
    {
        return self::nextDateAfter((string) $frequency, today()->toDateString()) !== null
            || in_array((string) $frequency, self::FREQUENCIES, true)
            || strcasecmp((string) $frequency, 'Weekly') === 0;
    }

    /**
     * Completable when the next due date is within COMPLETION_OPEN_DAYS (or already overdue),
     * and the schedule is not locked as a finished one-time / stuck Completed without a future cycle.
     */
    public static function canMarkComplete(object $schedule): bool
    {
        return self::cannotMarkCompleteReason($schedule) === null;
    }

    public static function cannotMarkCompleteReason(object $schedule): ?string
    {
        $status = trim((string) ($schedule->maintenance_schedule_status ?? 'Active'));
        $nextRaw = $schedule->maintenance_schedule_next_date ?? null;

        if (strcasecmp($status, 'Completed') === 0) {
            return 'This maintenance was already completed for this cycle. '
                .'Mark complete again only within '
                .self::COMPLETION_OPEN_DAYS
                .' days of the next scheduled date.';
        }

        if ($nextRaw === null || $nextRaw === '') {
            return 'This schedule has no next maintenance date.';
        }

        try {
            $next = Carbon::parse($nextRaw)->startOfDay();
        } catch (\Throwable $e) {
            return 'This schedule has an invalid next maintenance date.';
        }

        $openFrom = $next->copy()->subDays(self::COMPLETION_OPEN_DAYS);
        $today = today();

        if ($today->lt($openFrom)) {
            return 'Too early to mark complete. You can complete this starting '
                .$openFrom->format('M j, Y').' ('
                .self::COMPLETION_OPEN_DAYS.' days before '
                .$next->format('M j, Y').').';
        }

        return null;
    }

    /**
     * Move stuck "Completed" recurring rows forward so the next cycle is Active.
     */
    public static function healStuckCompletedCycles(): int
    {
        if (! Schema::hasTable('maintenance_schedules_table')) {
            return 0;
        }

        $rows = DB::table('maintenance_schedules_table')
            ->where('maintenance_schedule_status', 'Completed')
            ->whereNotNull('maintenance_schedule_next_date')
            ->get([
                'maintenance_schedule_id',
                'maintenance_schedule_frequency',
                'maintenance_schedule_next_date',
                'maintenance_schedule_last_date',
            ]);

        $healed = 0;
        foreach ($rows as $row) {
            if (! self::isRecurring($row->maintenance_schedule_frequency ?? null)) {
                continue;
            }

            $from = $row->maintenance_schedule_next_date
                ?? $row->maintenance_schedule_last_date
                ?? today()->toDateString();
            $next = self::nextDateAfter((string) $row->maintenance_schedule_frequency, (string) $from);
            if (! $next) {
                continue;
            }

            DB::table('maintenance_schedules_table')
                ->where('maintenance_schedule_id', $row->maintenance_schedule_id)
                ->update([
                    'maintenance_schedule_status' => 'Active',
                    'maintenance_schedule_next_date' => $next,
                ]);
            $healed++;
        }

        return $healed;
    }

    /**
     * After a successful completion: advance next date and reopen as Active for the next cycle.
     *
     * @return array{maintenance_schedule_status: string, maintenance_schedule_last_date: \Carbon\Carbon, maintenance_schedule_next_date?: string}
     */
    public static function completionUpdatePayload(object $schedule): array
    {
        $payload = [
            'maintenance_schedule_status' => 'Completed',
            'maintenance_schedule_last_date' => now(),
        ];

        $frequency = (string) ($schedule->maintenance_schedule_frequency ?? '');
        $from = $schedule->maintenance_schedule_next_date
            ?? ($schedule->maintenance_schedule_last_date ?? today()->toDateString());
        $next = self::nextDateAfter($frequency, (string) $from);

        if ($next) {
            $payload['maintenance_schedule_status'] = 'Active';
            $payload['maintenance_schedule_next_date'] = $next;
        }

        return $payload;
    }
}
