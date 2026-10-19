<?php

namespace App\Console\Commands;

use App\Support\WorkflowNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckMaintenanceAlerts extends Command
{
    protected $signature = 'maintenance:check-alerts';

    protected $description = 'Check maintenance schedules and create alerts';

    public function handle()
    {
        if (! Schema::hasTable('maintenance_schedules_table') || ! Schema::hasTable('notifications_table')) {
            $this->warn('Required tables are missing.');

            return Command::SUCCESS;
        }

        $today = Carbon::today();

        $schedules = DB::table('maintenance_schedules_table')
            ->leftJoin(
                'equipment_table',
                'maintenance_schedules_table.maintenance_schedule_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )
            ->whereIn('maintenance_schedule_status', ['Active', 'Overdue'])
            ->whereNotNull('maintenance_schedule_next_date')
            ->select(
                'maintenance_schedules_table.*',
                'equipment_table.equipment_name'
            )
            ->get();

        foreach ($schedules as $schedule) {
            $nextDate = Carbon::parse($schedule->maintenance_schedule_next_date)->startOfDay();
            $equipmentName = $schedule->equipment_name ?? 'Equipment';
            $scheduleId = (int) $schedule->maintenance_schedule_id;

            // 1. OVERDUE
            if ($nextDate->lt($today)) {
                if ($schedule->maintenance_schedule_status !== 'Overdue') {
                    DB::table('maintenance_schedules_table')
                        ->where('maintenance_schedule_id', $scheduleId)
                        ->update(['maintenance_schedule_status' => 'Overdue']);
                }

                $this->notifyRoles(
                    'maintenance_overdue_'.$scheduleId.'_'.$nextDate->toDateString(),
                    'Maintenance Overdue',
                    $equipmentName.' has passed its scheduled maintenance date.',
                    'maintenance_overdue',
                    $scheduleId
                );

                continue;
            }

            // 2. DUE TODAY
            if ($nextDate->isSameDay($today) && $schedule->maintenance_schedule_status === 'Active') {
                $this->notifyRoles(
                    'maintenance_due_today_'.$scheduleId.'_'.$nextDate->toDateString(),
                    'Maintenance Due Today',
                    $equipmentName.' is scheduled for maintenance today.',
                    'maintenance_due_today',
                    $scheduleId
                );

                continue;
            }

            // 3. UPCOMING WITHIN 7 DAYS
            $daysUntilMaintenance = $today->diffInDays($nextDate, false);

            if (
                $daysUntilMaintenance > 0
                && $daysUntilMaintenance <= 7
                && $schedule->maintenance_schedule_status === 'Active'
            ) {
                $this->notifyRoles(
                    'maintenance_upcoming_'.$scheduleId.'_'.$nextDate->toDateString(),
                    'Upcoming Maintenance',
                    $equipmentName
                        .' is scheduled for maintenance on '
                        .$nextDate->format('F j, Y')
                        .'. '
                        .$daysUntilMaintenance
                        .' day(s) remaining.',
                    'maintenance_upcoming',
                    $scheduleId
                );
            }
        }

        $this->info('Maintenance alerts checked successfully.');

        return Command::SUCCESS;
    }

    private function notifyRoles(
        string $eventKey,
        string $title,
        string $message,
        string $type,
        int $scheduleId
    ): void {
        $targets = [
            WorkflowNotifier::ROLE_MAINTENANCE => '/maintenance/schedules?record='.$scheduleId,
            WorkflowNotifier::ROLE_ADMIN => '/admin/schedules?record='.$scheduleId,
        ];

        foreach ($targets as $role => $url) {
            DB::table('notifications_table')->insertOrIgnore([
                'notification_user_id' => null,
                'notification_target_role' => $role,
                'notification_title' => $title,
                'notification_message' => $message,
                'notification_type' => $type,
                'notification_category' => 'Maintenance',
                'notification_reference_type' => 'maintenance_schedule',
                'notification_reference_id' => $scheduleId,
                'notification_url' => $url,
                'notification_event_key' => $eventKey.'_'.strtolower(str_replace(' ', '_', $role)),
                'notification_created_at' => now(),
            ]);
        }
    }
}
