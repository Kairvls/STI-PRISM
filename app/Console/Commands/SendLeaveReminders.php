<?php

namespace App\Console\Commands;

use App\Support\AvailabilityAnnouncer;
use App\Support\UserAvailability;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendLeaveReminders extends Command
{
    protected $signature = 'availability:send-leave-reminders';

    protected $description = 'Announce upcoming leave/absence about 2 days before start';

    public function handle(): int
    {
        if (! UserAvailability::hasScheduleColumns() || ! Schema::hasTable('notifications_table')) {
            $this->warn('Availability schedule columns or notifications table missing.');

            return self::SUCCESS;
        }

        $now = Carbon::now();
        $windowStart = $now->copy();
        $windowEnd = $now->copy()->addDays(UserAvailability::REMINDER_DAYS)->endOfDay();

        $rows = DB::table('users_table')
            ->whereIn('user_away_status', [UserAvailability::LEAVE, UserAvailability::ABSENT])
            ->whereNotNull('user_away_starts_at')
            ->whereNotNull('user_away_until')
            ->where('user_away_starts_at', '>', $windowStart)
            ->where('user_away_starts_at', '<=', $windowEnd)
            ->whereNull('user_away_reminder_sent_at')
            ->get();

        $sent = 0;
        foreach ($rows as $row) {
            if (! UserAvailability::isUpcoming($row)) {
                continue;
            }

            AvailabilityAnnouncer::announceReminder($row);
            DB::table('users_table')
                ->where('user_id', (int) $row->user_id)
                ->update(['user_away_reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Leave reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
