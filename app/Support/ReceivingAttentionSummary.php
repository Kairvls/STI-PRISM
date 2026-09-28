<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReceivingAttentionSummary
{
    /** @var array<string, int>|null */
    private static ?array $cached = null;

    public const QUEUE_STATUSES = ['Pending', 'Submitted', 'Resubmitted', 'Under Review'];

    /**
     * Daily-reminder items and the list filter each one opens (?focus=key on /receiving/reports).
     *
     * @return array<string, array{label:string, description:string, scope:string, status:string}>
     */
    public static function focusOptions(): array
    {
        return [
            'queue' => [
                'label' => 'Pending second count / inspection',
                'description' => 'Receiving reports in Pending, Submitted, Resubmitted or Under Review.',
                'scope' => 'mine',
                'status' => 'queue',
            ],
            'leftover' => [
                'label' => 'Leftover from before today',
                'description' => 'Queue reports submitted before today that are still waiting for second count.',
                'scope' => 'mine',
                'status' => 'queue',
            ],
            'returned' => [
                'label' => 'Returned for correction',
                'description' => 'Receiving reports sent back to the Purchaser that still need follow-up.',
                'scope' => 'mine',
                'status' => 'returned',
            ],
        ];
    }

    /**
     * Apply a reminder item's rule to a receiving_reports_table query.
     */
    public static function applyFocus($query, string $key): void
    {
        match ($key) {
            'queue' => self::scopeQueue($query),
            'leftover' => self::scopeLeftover($query),
            'returned' => self::scopeReturned($query),
        };
    }

    /**
     * Base query for the signed-in officer's review queue: not archived, assigned to me or unassigned.
     */
    public static function queue()
    {
        $query = DB::table('receiving_reports_table');
        self::scopeActive($query);
        self::scopeReviewer($query);

        return $query;
    }

    public static function scopeActive($query): void
    {
        if (!Schema::hasColumn('receiving_reports_table', 'receiving_report_is_archived')) {
            return;
        }

        $query->where(function ($q) {
            $q->whereNull('receiving_reports_table.receiving_report_is_archived')
                ->orWhere('receiving_reports_table.receiving_report_is_archived', 0);
        });
    }

    public static function scopeReviewer($query): void
    {
        if (Schema::hasColumn('receiving_reports_table', 'receiving_report_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'receiving_reports_table.receiving_report_assigned_reviewer_id');
        }
    }

    public static function scopeQueue($query): void
    {
        $query->whereIn('receiving_reports_table.receiving_report_status', self::QUEUE_STATUSES);
    }

    /**
     * Queue reports whose submission (or creation) date is before today.
     */
    public static function scopeLeftover($query): void
    {
        self::scopeQueue($query);

        $columns = array_values(array_filter(
            ['receiving_report_submitted_at', 'receiving_report_created_at', 'receiving_report_date'],
            fn ($column) => Schema::hasColumn('receiving_reports_table', $column)
        ));

        if ($columns === []) {
            $query->whereRaw('0 = 1');
            return;
        }

        $expr = count($columns) === 1
            ? 'receiving_reports_table.'.$columns[0]
            : 'COALESCE('.implode(', ', array_map(fn ($c) => 'receiving_reports_table.'.$c, $columns)).')';

        $query->whereRaw('DATE('.$expr.') < ?', [today()->toDateString()]);
    }

    public static function scopeReturned($query): void
    {
        $query->where('receiving_reports_table.receiving_report_status', 'Returned');
    }

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
            $count = function (string $key): int {
                $query = self::queue();
                self::applyFocus($query, $key);

                return (int) $query->count();
            };

            $pendingCount = $count('queue');
            $leftoverCount = $count('leftover');
            $returnedCount = $count('returned');
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
}
