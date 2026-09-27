<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reports_table', 'report_last_reported_at')) {
            Schema::table('reports_table', function (Blueprint $table) {
                $table->timestamp('report_last_reported_at')
                    ->nullable()
                    ->after('report_submitted_at')
                    ->index();
            });
        }

        DB::table('reports_table')
            ->whereNull('report_last_reported_at')
            ->update(['report_last_reported_at' => DB::raw('report_submitted_at')]);

        if (! Schema::hasColumn('reports_table', 'report_related_notes')) {
            return;
        }

        $reReported = DB::table('reports_table')
            ->whereNotNull('report_related_notes')
            ->where('report_related_notes', '!=', '')
            ->get(['report_id', 'report_submitted_at', 'report_related_notes']);

        foreach ($reReported as $report) {
            $latest = null;

            foreach (preg_split('/\R/', (string) $report->report_related_notes) as $line) {
                if (! preg_match('/^([A-Z][a-z]{2} \d{2}, \d{4} \d{2}:\d{2} [AP]M)/', trim($line), $match)) {
                    continue;
                }

                try {
                    $at = Carbon::createFromFormat('M d, Y h:i A', $match[1]);
                } catch (\Throwable $e) {
                    continue;
                }

                if (! $latest || $at->greaterThan($latest)) {
                    $latest = $at;
                }
            }

            if ($latest && $latest->greaterThan(Carbon::parse($report->report_submitted_at))) {
                DB::table('reports_table')
                    ->where('report_id', $report->report_id)
                    ->update(['report_last_reported_at' => $latest]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reports_table', 'report_last_reported_at')) {
            Schema::table('reports_table', function (Blueprint $table) {
                $table->dropIndex(['report_last_reported_at']);
                $table->dropColumn('report_last_reported_at');
            });
        }
    }
};
