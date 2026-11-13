<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ReporterAuditPack
{
    public static function download(int $reporterId): Response
    {
        $reporter = DB::table('reporters_table as r')
            ->when(ReporterImport::hasDepartmentColumn() && Departments::tableReady(), function ($query) {
                $query->leftJoin('departments_table as d', 'd.department_id', '=', 'r.reporter_department_id');
            })
            ->where('r.reporter_id', $reporterId)
            ->select('r.*')
            ->when(
                ReporterImport::hasDepartmentColumn() && Departments::tableReady(),
                fn ($query) => $query->addSelect('d.department_name as reporter_department_name')
            )
            ->first();

        if (! $reporter) {
            abort(404, 'Reporter not found.');
        }

        $reports = DB::table('reports_table')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->where('reports_table.report_reporter_employee_id', $reporter->reporter_employee_id)
            ->select(
                'reports_table.*',
                'equipment_table.equipment_name',
                'rooms_table.room_name'
            )
            ->orderByDesc('reports_table.report_submitted_at')
            ->limit(500)
            ->get()
            ->map(function ($report) {
                $issue = trim((string) ($report->report_suggested_issue ?: $report->report_problem_description ?: ''));

                return [
                    'ticket' => ReportGrouping::ticketCode($report),
                    'issue' => $issue !== '' ? $issue : '—',
                    'equipment' => (string) ($report->equipment_name
                        ?: ($report->report_unlisted_equipment_name ?? '—')),
                    'room' => (string) ($report->room_name ?: '—'),
                    'priority' => ReportSeverity::forReport($report),
                    'status' => (string) ($report->report_current_status ?? '—'),
                    'submitted_at' => ! empty($report->report_submitted_at)
                        ? \Carbon\Carbon::parse($report->report_submitted_at)->format('M j, Y g:i A')
                        : '—',
                ];
            })
            ->all();

        $profile = [
            'id' => (int) $reporter->reporter_id,
            'name' => (string) ($reporter->reporter_full_name ?? '—'),
            'employee_id' => (string) ($reporter->reporter_employee_id ?? '—'),
            'type' => (string) ($reporter->reporter_employment_type ?? '—'),
            'department' => (string) ($reporter->reporter_department_name
                ?? $reporter->department_name
                ?? '—'),
            'email' => (string) ($reporter->reporter_email_address ?: '—'),
            'contact' => PhoneNumber::formatForDisplay($reporter->reporter_contact_number) ?: '—',
            'status' => (string) ($reporter->reporter_status ?? '—'),
        ];

        $pdf = Pdf::loadView('admin.reporters.audit-pack-pdf', [
            'reporter' => $profile,
            'reports' => $reports,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $safeName = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $profile['name']);
        $filename = 'reporter-records-'.$reporterId.'-'.trim((string) $safeName, '-').'.pdf';

        return $pdf->download($filename);
    }
}
