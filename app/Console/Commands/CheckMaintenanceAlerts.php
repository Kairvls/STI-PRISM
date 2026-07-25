<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckMaintenanceAlerts extends Command
{
    // =====================================================
    // COMMAND NAME
    // =====================================================

    protected $signature = 'maintenance:check-alerts';


    // =====================================================
    // COMMAND DESCRIPTION
    // =====================================================

    protected $description =
        'Check maintenance schedules and create alerts';


    // =====================================================
    // RUN COMMAND
    // =====================================================

    public function handle()
    {
        // =====================================================
        // CURRENT DATE
        // =====================================================

        $today = Carbon::today();


        // =====================================================
        // CHECK ALL ACTIVE / OVERDUE SCHEDULES
        // =====================================================

        $schedules = DB::table('maintenance_schedules_table')

            ->leftJoin(
                'equipment_table',
                'maintenance_schedules_table.maintenance_schedule_equipment_id',
                '=',
                'equipment_table.equipment_id'
            )

            ->whereIn(
                'maintenance_schedule_status',
                [
                    'Active',
                    'Overdue'
                ]
            )

            ->whereNotNull(
                'maintenance_schedule_next_date'
            )

            ->select(
                'maintenance_schedules_table.*',
                'equipment_table.equipment_name'
            )

            ->get();


        // =====================================================
        // PROCESS EACH SCHEDULE
        // =====================================================

        foreach ($schedules as $schedule) {

            // =================================================
            // NEXT MAINTENANCE DATE
            // =================================================

            $nextDate = Carbon::parse(
                $schedule->maintenance_schedule_next_date
            )->startOfDay();


            // =================================================
            // EQUIPMENT NAME
            // =================================================

            $equipmentName =
                $schedule->equipment_name
                ?? 'Equipment';


            // =====================================================
            // 1. OVERDUE
            // =====================================================

            if ($nextDate->lt($today)) {

                // =================================================
                // UPDATE SCHEDULE STATUS
                // =================================================

                if (
                    $schedule->maintenance_schedule_status
                    !== 'Overdue'
                ) {

                    DB::table('maintenance_schedules_table')

                        ->where(
                            'maintenance_schedule_id',
                            $schedule->maintenance_schedule_id
                        )

                        ->update([
                            'maintenance_schedule_status'
                                => 'Overdue',
                        ]);
                }


                // =================================================
                // UNIQUE EVENT KEY
                //
                // EXAMPLE:
                // maintenance_overdue_3_2026-07-13
                // =================================================

                $eventKey =
                    'maintenance_overdue_'
                    . $schedule->maintenance_schedule_id
                    . '_'
                    . $nextDate->toDateString();


                // =================================================
                // CREATE OVERDUE NOTIFICATION
                // =================================================

                DB::table('notifications_table')

                    ->insertOrIgnore([

                        'notification_user_id'
                            => null,

                        // =========================================
                        // SEND TO MAINTENANCE PERSONNEL
                        // =========================================

                        'notification_target_role'
                            => 'Maintenance Personnel',

                        'notification_title'
                            => 'Maintenance Overdue',

                        'notification_message'
                            => $equipmentName
                            . ' has passed its scheduled maintenance date.',

                        'notification_type'
                            => 'maintenance_overdue',

                        'notification_category'
                            => 'Maintenance',

                        'notification_reference_type'
                            => 'maintenance_schedule',

                        'notification_reference_id'
                            => $schedule->maintenance_schedule_id,

                        'notification_url'
                            => '/maintenance/schedules',

                        'notification_event_key'
                            => $eventKey,

                        'notification_created_at'
                            => now(),

                    ]);


                continue;
            }


            // =====================================================
            // 2. DUE TODAY
            // =====================================================

            if (
                $nextDate->isSameDay($today)
                &&
                $schedule->maintenance_schedule_status === 'Active'
            ) {

                // =================================================
                // UNIQUE EVENT KEY
                // =================================================

                $eventKey =
                    'maintenance_due_today_'
                    . $schedule->maintenance_schedule_id
                    . '_'
                    . $nextDate->toDateString();


                // =================================================
                // CREATE DUE TODAY NOTIFICATION
                // =================================================

                DB::table('notifications_table')

                    ->insertOrIgnore([

                        'notification_user_id'
                            => null,

                        'notification_target_role'
                            => 'Maintenance Personnel',

                        'notification_title'
                            => 'Maintenance Due Today',

                        'notification_message'
                            => $equipmentName
                            . ' is scheduled for maintenance today.',

                        'notification_type'
                            => 'maintenance_due_today',

                        'notification_category'
                            => 'Maintenance',

                        'notification_reference_type'
                            => 'maintenance_schedule',

                        'notification_reference_id'
                            => $schedule->maintenance_schedule_id,

                        'notification_url'
                            => '/maintenance/schedules',

                        'notification_event_key'
                            => $eventKey,

                        'notification_created_at'
                            => now(),

                    ]);


                continue;
            }


            // =====================================================
            // 3. UPCOMING MAINTENANCE WITHIN 7 DAYS
            // =====================================================

            $daysUntilMaintenance =
                $today->diffInDays(
                    $nextDate,
                    false
                );


            if (
                $daysUntilMaintenance > 0
                &&
                $daysUntilMaintenance <= 7
                &&
                $schedule->maintenance_schedule_status === 'Active'
            ) {

                // =================================================
                // UNIQUE EVENT KEY
                //
                // ONE UPCOMING ALERT PER SCHEDULE DATE
                // =================================================

                $eventKey =
                    'maintenance_upcoming_'
                    . $schedule->maintenance_schedule_id
                    . '_'
                    . $nextDate->toDateString();


                // =================================================
                // CREATE UPCOMING NOTIFICATION
                // =================================================

                DB::table('notifications_table')

                    ->insertOrIgnore([

                        'notification_user_id'
                            => null,

                        'notification_target_role'
                            => 'Maintenance Personnel',

                        'notification_title'
                            => 'Upcoming Maintenance',

                        'notification_message'
                            => $equipmentName
                            . ' is scheduled for maintenance on '
                            . $nextDate->format('F j, Y')
                            . '. '
                            . $daysUntilMaintenance
                            . ' day(s) remaining.',

                        'notification_type'
                            => 'maintenance_upcoming',

                        'notification_category'
                            => 'Maintenance',

                        'notification_reference_type'
                            => 'maintenance_schedule',

                        'notification_reference_id'
                            => $schedule->maintenance_schedule_id,

                        'notification_url'
                            => '/maintenance/schedules',

                        'notification_event_key'
                            => $eventKey,

                        'notification_created_at'
                            => now(),

                    ]);
            }
        }


        // =====================================================
        // COMMAND OUTPUT
        // =====================================================

        $this->info(
            'Maintenance alerts checked successfully.'
        );


        return Command::SUCCESS;
    }
}