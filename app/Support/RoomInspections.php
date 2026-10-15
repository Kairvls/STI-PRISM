<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring inspections of a whole room (lights, ventilation, safety...),
 * alongside per-equipment maintenance and semester campaigns.
 */
class RoomInspections
{
    public const SCHEDULES = 'room_inspection_schedules_table';

    public const LOGS = 'room_inspection_logs_table';

    public const ONE_TIME = 'One-time';

    public const FREQUENCIES = ['Weekly', 'Monthly', 'Quarterly', 'Semi Annual', 'Annual', self::ONE_TIME];

    public const RESULT_GOOD = 'Good';

    public const RESULT_ATTENTION = 'Needs Attention';

    public const RESULT_UNSAFE = 'Unsafe';

    public const RESULTS = [self::RESULT_GOOD, self::RESULT_ATTENTION, self::RESULT_UNSAFE];

    public const CHECK_PASS = 'Pass';

    public const CHECK_ISSUE = 'Issue';

    public const CHECK_NA = 'N/A';

    public const DEFAULT_CHECKLIST = [
        'Furniture & Fixtures',
        'Audio-Visual Equipment',
        'Lighting & Fixtures',
        'Air Conditioning',
        'Computer Equipment',
        'Network Assets',
        'Other',
    ];

    public static function ready(): bool
    {
        static $ready;

        return $ready ??= Schema::hasTable(self::SCHEDULES) && Schema::hasTable(self::LOGS);
    }

    /**
     * @return list<string>
     */
    public static function checklistFor(?object $schedule): array
    {
        $items = json_decode((string) ($schedule->room_inspection_schedule_checklist ?? ''), true);

        return is_array($items) && $items !== [] ? array_values($items) : self::DEFAULT_CHECKLIST;
    }

    /**
     * @return list<string>
     */
    public static function parseChecklist(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => mb_substr(trim($line), 0, 120))
            ->filter()
            ->unique()
            ->take(30)
            ->values()
            ->all();
    }

    /**
     * The first due date after today, stepping from the date that was scheduled;
     * null for one-time inspections.
     */
    public static function nextDate(string $frequency, ?string $scheduledFor): ?string
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

        $date = $scheduledFor ? Carbon::parse($scheduledFor)->startOfDay() : today();
        do {
            $date = $step($date);
        } while ($date->lte(today()));

        return $date->toDateString();
    }

    public static function refreshOverdue(): void
    {
        if (! self::ready()) {
            return;
        }

        DB::table(self::SCHEDULES)
            ->where('room_inspection_schedule_status', 'Active')
            ->whereNotNull('room_inspection_schedule_next_date')
            ->whereDate('room_inspection_schedule_next_date', '<', today())
            ->update(['room_inspection_schedule_status' => 'Overdue']);
    }

    public static function overdueCount(): int
    {
        if (! self::ready()) {
            return 0;
        }

        return DB::table(self::SCHEDULES)
            ->where('room_inspection_schedule_status', '!=', 'Completed')
            ->whereNotNull('room_inspection_schedule_next_date')
            ->whereDate('room_inspection_schedule_next_date', '<', today())
            ->count();
    }

    public static function resultTone(?string $result): string
    {
        return match ($result) {
            self::RESULT_GOOD => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            self::RESULT_ATTENTION => 'bg-amber-50 text-amber-700 ring-amber-100',
            self::RESULT_UNSAFE => 'bg-rose-50 text-rose-700 ring-rose-100',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }
}
