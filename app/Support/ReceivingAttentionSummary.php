<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReceivingAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    private const QUEUE_STATUSES = ['Pending', 'Submitted', 'Resubmitted', 'Under Review'];

    /**
     * Actionable receiving officer workload counts.
     *
     * @return array{
     *     pendingCount: int,
     *     leftoverCount: int,
     *     returnedCount: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $pendingCount = 0;
        $leftoverCount = 0;
        $returnedCount = 0;

        if (Schema::hasTable('receiving_reports_table')) {
            $pendingCount = (int) self::baseActiveQuery()
                ->whereIn('receiving_report_status', self::QUEUE_STATUSES)
                ->count();

            $returnedCount = (int) self::baseActiveQuery()
                ->where('receiving_report_status', 'Returned')
                ->count();

            $dateColumn = null;
            foreach ([
                'receiving_report_submitted_at',
                'receiving_report_created_at',
                'receiving_report_date',
            ] as $column) {
                if (Schema::hasColumn('receiving_reports_table', $column)) {
                    $dateColumn = $column;
                    break;
                }
            }

            if ($dateColumn !== null) {
                $leftoverCount = (int) self::baseActiveQuery()
                    ->whereIn('receiving_report_status', self::QUEUE_STATUSES)
                    ->whereNotNull($dateColumn)
                    ->whereDate($dateColumn, '<', today())
                    ->count();
            }
        }

        self::$cached = [
            'pendingCount' => $pendingCount,
            'leftoverCount' => $leftoverCount,
            'returnedCount' => $returnedCount,
            // Leftover is a subset of pending — do not double-count in the badge total.
            'attentionTotal' => $pendingCount + $returnedCount,
        ];

        return self::$cached;
    }

    private static function baseActiveQuery()
    {
        $query = DB::table('receiving_reports_table');

        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('receiving_report_is_archived')
                    ->orWhere('receiving_report_is_archived', 0);
            });
        }

        return $query;
    }
}
