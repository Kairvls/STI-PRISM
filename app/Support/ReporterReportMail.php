<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReporterReportMail
{
    public const SPAM_THRESHOLD = 5;

    public const SPAM_WINDOW_MINUTES = 60;

    /**
     * @return array{
     *     reporter: object,
     *     report: object,
     *     ticket_code: string,
     *     status: string,
     *     submitted_at: string,
     *     items: list<array{name: string, room: string, issue: string, details: string}>,
     *     app_url: string
     * }|null
     */
    public static function payloadForReport(int $reportId): ?array
    {
        if ($reportId < 1 || ! Schema::hasTable('reports_table')) {
            return null;
        }

        $report = DB::table('reports_table')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->where('reports_table.report_id', $reportId)
            ->select(
                'reports_table.*',
                'rooms_table.room_name',
                'equipment_table.equipment_name'
            )
            ->first();

        if (! $report) {
            return null;
        }

        $employeeId = trim((string) ($report->report_reporter_employee_id ?? ''));
        if ($employeeId === '') {
            return null;
        }

        $reporter = DB::table('reporters_table')
            ->where('reporter_employee_id', $employeeId)
            ->first();

        if (! $reporter) {
            return null;
        }

        $items = self::itemLines($report);

        return [
            'reporter' => $reporter,
            'report' => $report,
            'ticket_code' => ReportGrouping::ticketCode($report),
            'status' => (string) ($report->report_current_status ?? 'Pending'),
            'submitted_at' => self::formatDateTime($report->report_submitted_at ?? null),
            'items' => $items,
            'app_url' => rtrim((string) config('app.url'), '/'),
        ];
    }

    public static function sendSubmitted(int $reportId): bool
    {
        $payload = self::payloadForReport($reportId);
        if (! $payload || ! ReporterPins::canEmail($payload['reporter'])) {
            return false;
        }

        try {
            Mail::send('emails.reporter-report-submitted', [
                'name' => $payload['reporter']->reporter_full_name,
                'employeeId' => $payload['reporter']->reporter_employee_id,
                'ticketCode' => $payload['ticket_code'],
                'status' => $payload['status'],
                'submittedAt' => $payload['submitted_at'],
                'items' => $payload['items'],
                'appUrl' => $payload['app_url'],
            ], function ($message) use ($payload) {
                $message->to($payload['reporter']->reporter_email_address)
                    ->subject('PaAyo report '.$payload['ticket_code'].' was submitted');
            });

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public static function sendStatusChanged(int $reportId, string $oldStatus, string $newStatus): bool
    {
        $oldStatus = trim($oldStatus);
        $newStatus = trim($newStatus);
        if ($oldStatus === '' || $newStatus === '' || strcasecmp($oldStatus, $newStatus) === 0) {
            return false;
        }

        $payload = self::payloadForReport($reportId);
        if (! $payload || ! ReporterPins::canEmail($payload['reporter'])) {
            return false;
        }

        try {
            Mail::send('emails.reporter-report-status', [
                'name' => $payload['reporter']->reporter_full_name,
                'employeeId' => $payload['reporter']->reporter_employee_id,
                'ticketCode' => $payload['ticket_code'],
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus,
                'changedAt' => self::formatDateTime(now()),
                'submittedAt' => $payload['submitted_at'],
                'items' => $payload['items'],
                'appUrl' => $payload['app_url'],
            ], function ($message) use ($payload, $newStatus) {
                $message->to($payload['reporter']->reporter_email_address)
                    ->subject('PaAyo report '.$payload['ticket_code'].' is now '.$newStatus);
            });

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    public static function notifyMaintenanceOfNewReport(int $reportId, ?string $urgency = null, string $source = 'Campus'): void
    {
        if ($reportId < 1 || ! Schema::hasTable('notifications_table')) {
            return;
        }

        $isUrgent = strcasecmp((string) $urgency, 'Urgent') === 0;
        $ticket = ReportGrouping::ticketCode($reportId);

        try {
            DB::table('notifications_table')->insertOrIgnore([
                'notification_user_id' => null,
                'notification_target_role' => 'Maintenance Personnel',
                'notification_title' => $isUrgent
                    ? 'Urgent Report Requires Attention'
                    : 'New Report Submitted',
                'notification_message' => $isUrgent
                    ? $source.' urgent report '.$ticket.' requires immediate attention.'
                    : $source.' maintenance report '.$ticket.' was submitted.',
                'notification_type' => $isUrgent ? 'urgent_report' : 'new_report',
                'notification_category' => 'Reports',
                'notification_reference_type' => 'report',
                'notification_reference_id' => $reportId,
                'notification_url' => '/maintenance/reports/details/'.$reportId,
                'notification_event_key' => 'report_submitted_'.$reportId,
                'notification_created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function recentReportCountForEmployee(string $employeeId): int
    {
        $employeeId = trim($employeeId);
        if ($employeeId === '' || ! Schema::hasTable('reports_table')) {
            return 0;
        }

        $since = now()->subMinutes(self::SPAM_WINDOW_MINUTES);

        return (int) DB::table('reports_table')
            ->where('report_reporter_employee_id', $employeeId)
            ->where('report_is_archived', false)
            ->where('report_submitted_at', '>=', $since)
            ->count();
    }

    /**
     * @return list<array{employee_id: string, name: string, count: int}>
     */
    public static function activeSpamReporters(): array
    {
        if (! Schema::hasTable('reports_table')) {
            return [];
        }

        $since = now()->subMinutes(self::SPAM_WINDOW_MINUTES);

        $rows = DB::table('reports_table')
            ->leftJoin(
                'reporters_table',
                'reports_table.report_reporter_employee_id',
                '=',
                'reporters_table.reporter_employee_id'
            )
            ->where('reports_table.report_is_archived', false)
            ->where('reports_table.report_submitted_at', '>=', $since)
            ->whereNotNull('reports_table.report_reporter_employee_id')
            ->where('reports_table.report_reporter_employee_id', '!=', '')
            ->groupBy(
                'reports_table.report_reporter_employee_id',
                'reporters_table.reporter_full_name'
            )
            ->havingRaw('COUNT(*) >= ?', [self::SPAM_THRESHOLD])
            ->orderByRaw('COUNT(*) DESC')
            ->select(
                'reports_table.report_reporter_employee_id as employee_id',
                'reporters_table.reporter_full_name as name',
                DB::raw('COUNT(*) as report_count')
            )
            ->get();

        return $rows->map(fn ($row) => [
            'employee_id' => (string) $row->employee_id,
            'name' => (string) ($row->name ?? ''),
            'count' => (int) $row->report_count,
        ])->all();
    }

    public static function checkAndAlertSpam(string $employeeId): void
    {
        $employeeId = trim($employeeId);
        if ($employeeId === '' || ! Schema::hasTable('notifications_table')) {
            return;
        }

        $count = self::recentReportCountForEmployee($employeeId);
        if ($count < self::SPAM_THRESHOLD) {
            return;
        }

        $reporter = DB::table('reporters_table')
            ->where('reporter_employee_id', $employeeId)
            ->first();
        $name = trim((string) ($reporter->reporter_full_name ?? ''));
        $label = $name !== '' ? $name.' ('.$employeeId.')' : $employeeId;
        $eventKey = 'reporter_spam_'.$employeeId.'_'.now()->format('YmdH');

        try {
            DB::table('notifications_table')->insertOrIgnore([
                'notification_user_id' => null,
                'notification_target_role' => 'Maintenance Personnel',
                'notification_title' => 'Possible report spam',
                'notification_message' => $label.' submitted '.$count.' reports in the last '
                    .self::SPAM_WINDOW_MINUTES.' minutes. Review for misuse of this employee ID.',
                'notification_type' => 'reporter_spam',
                'notification_category' => 'Reports',
                'notification_reference_type' => 'reporter',
                'notification_reference_id' => (int) ($reporter->reporter_id ?? 0) ?: null,
                'notification_url' => '/maintenance/reports/incoming?search='.urlencode($employeeId),
                'notification_event_key' => $eventKey,
                'notification_created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * After a successful campus/walk-in/API submit: email reporter, notify Maintenance, spam check.
     */
    public static function afterSuccessfulSubmit(
        int $reportId,
        string $employeeId,
        ?string $urgency = null,
        string $source = 'Campus'
    ): void {
        try {
            self::sendSubmitted($reportId);
        } catch (Throwable $e) {
            report($e);
        }

        try {
            self::notifyMaintenanceOfNewReport($reportId, $urgency, $source);
        } catch (Throwable $e) {
            report($e);
        }

        try {
            self::checkAndAlertSpam($employeeId);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return list<array{name: string, room: string, issue: string, details: string}>
     */
    private static function itemLines(object $report): array
    {
        $items = ReportItems::forReport((int) $report->report_id);

        if ($items->isNotEmpty()) {
            return $items->map(function ($item) {
                return [
                    'name' => ReportItems::displayName($item),
                    'room' => trim((string) ($item->room_name ?? '')),
                    'issue' => trim((string) ($item->report_item_suggested_issue ?? '')),
                    'details' => trim((string) ($item->report_item_problem_description ?? '')),
                ];
            })->all();
        }

        $name = trim((string) ($report->equipment_name ?? ''));
        if ($name === '') {
            $name = trim((string) ($report->report_unlisted_equipment_name ?? ''));
        }
        if ($name === '') {
            $name = 'Equipment';
        }

        return [[
            'name' => $name,
            'room' => trim((string) ($report->room_name ?? '')),
            'issue' => trim((string) ($report->report_suggested_issue ?? '')),
            'details' => trim((string) ($report->report_problem_description ?? '')),
        ]];
    }

    private static function formatDateTime(mixed $value): string
    {
        if (empty($value)) {
            return now()->timezone(config('app.timezone'))->format('M j, Y g:i A');
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->timezone(config('app.timezone'))
                ->format('M j, Y g:i A');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }
}
