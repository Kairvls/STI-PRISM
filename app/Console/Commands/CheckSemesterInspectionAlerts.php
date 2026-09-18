<?php

namespace App\Console\Commands;

use App\Support\EquipmentLifecycle;
use App\Support\SemesterInspections;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSemesterInspectionAlerts extends Command
{
    protected $signature = 'maintenance:check-semester-inspections';

    protected $description = 'Alert Maintenance and Admin about semester inspections and aging equipment';

    public function handle(): int
    {
        $this->alertSemesterCampaigns();
        $this->alertAgingEquipment();

        $this->info('Semester inspection and lifecycle alerts checked successfully.');

        return self::SUCCESS;
    }

    private function alertSemesterCampaigns(): void
    {
        if (! SemesterInspections::tablesReady()) {
            return;
        }

        $today = Carbon::today();

        $campaigns = DB::table('semester_inspection_campaigns_table')
            ->whereIn('campaign_status', ['Active', 'In Progress'])
            ->whereNotNull('campaign_due_date')
            ->get();

        foreach ($campaigns as $campaign) {
            $dueDate = Carbon::parse($campaign->campaign_due_date)->startOfDay();
            $title = $campaign->campaign_title ?: 'Semester inspection';
            $progress = SemesterInspections::campaignProgress((int) $campaign->campaign_id);
            $pendingNote = $progress['pending'] > 0
                ? ' '.$progress['pending'].' of '.$progress['total'].' equipment still pending.'
                : '';

            if ($dueDate->lt($today)) {
                $this->notifyRoles(
                    eventKey: 'semester_inspection_overdue_'.$campaign->campaign_id.'_'.$dueDate->toDateString(),
                    title: 'Semester inspection overdue',
                    message: $title.' was due '.$dueDate->format('F j, Y').'.'.$pendingNote,
                    type: 'semester_inspection_overdue',
                    category: 'Inspection',
                    referenceId: (int) $campaign->campaign_id,
                    url: '/maintenance/semester-inspections/'.$campaign->campaign_id
                );
                continue;
            }

            if ($dueDate->isSameDay($today)) {
                $this->notifyRoles(
                    eventKey: 'semester_inspection_due_today_'.$campaign->campaign_id.'_'.$dueDate->toDateString(),
                    title: 'Semester inspection due today',
                    message: $title.' is due today.'.$pendingNote,
                    type: 'semester_inspection_due_today',
                    category: 'Inspection',
                    referenceId: (int) $campaign->campaign_id,
                    url: '/maintenance/semester-inspections/'.$campaign->campaign_id
                );
                continue;
            }

            $daysUntil = $today->diffInDays($dueDate, false);
            if ($daysUntil > 0 && $daysUntil <= 7) {
                $this->notifyRoles(
                    eventKey: 'semester_inspection_upcoming_'.$campaign->campaign_id.'_'.$dueDate->toDateString(),
                    title: 'Semester inspection in '.$daysUntil.' day(s)',
                    message: $title.' is due on '.$dueDate->format('F j, Y').'.'.$pendingNote,
                    type: 'semester_inspection_upcoming',
                    category: 'Inspection',
                    referenceId: (int) $campaign->campaign_id,
                    url: '/maintenance/semester-inspections/'.$campaign->campaign_id
                );
            }
        }
    }

    private function alertAgingEquipment(): void
    {
        if (! Schema::hasTable('equipment_table') || ! Schema::hasTable('notifications_table')) {
            return;
        }

        $alerts = EquipmentLifecycle::agingAlerts(25);

        foreach ($alerts as $alert) {
            $yearsLeft = (int) ($alert->years_remaining ?? 0);
            if ($yearsLeft > 0) {
                continue;
            }

            $suggestion = EquipmentLifecycle::suggestAction(
                $yearsLeft,
                $alert->equipment_inventory_status ?? null
            );

            $this->notifyRoles(
                eventKey: 'equipment_lifecycle_'.$alert->equipment_id.'_'.now()->format('Y-m'),
                title: 'Equipment replacement suggested',
                message: ($alert->equipment_name ?? 'Equipment')
                    .' — '.$suggestion['label'].' ('.$suggestion['hint'].').',
                type: 'equipment_lifecycle',
                category: 'Equipment',
                referenceId: (int) $alert->equipment_id,
                url: '/maintenance/replacement-suggestions'
            );
        }
    }

    private function notifyRoles(
        string $eventKey,
        string $title,
        string $message,
        string $type,
        string $category,
        int $referenceId,
        string $url
    ): void {
        if (! Schema::hasTable('notifications_table')) {
            return;
        }

        foreach (['Maintenance Personnel', 'Admin'] as $role) {
            DB::table('notifications_table')->insertOrIgnore([
                'notification_user_id' => null,
                'notification_target_role' => $role,
                'notification_title' => $title,
                'notification_message' => $message,
                'notification_type' => $type,
                'notification_category' => $category,
                'notification_reference_type' => str_starts_with($type, 'semester_')
                    ? 'semester_inspection'
                    : 'equipment',
                'notification_reference_id' => $referenceId,
                'notification_url' => $url,
                'notification_event_key' => $eventKey.'_'.strtolower(str_replace(' ', '_', $role)),
                'notification_created_at' => now(),
            ]);
        }
    }
}
