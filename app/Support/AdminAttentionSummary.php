<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminAttentionSummary
{
    public const FOCUS_PENDING_REVIEW = 'pending-review';
    public const FOCUS_AMENDMENTS = 'amendments';
    public const FOCUS_AWAITING_COSIGN = 'awaiting-cosign';

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * Actionable admin workload counts (RIS review / cosign / amendments).
     *
     * @return array{
     *     pendingRis: int,
     *     awaitingCosign: int,
     *     amendRis: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $pendingRis = 0;
        $awaitingCosign = 0;
        $amendRis = 0;

        if (Schema::hasTable('requisition_issue_slip_table')) {
            $reviewBase = DB::table('requisition_issue_slip_table');
            if (Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_reviewer_id')) {
                ReviewerAssignment::applyQueueFilter($reviewBase, 'ris_assigned_reviewer_id');
            }

            $pendingRis = (int) self::scopePendingRis(clone $reviewBase)->count();
            $amendRis = (int) self::scopeAmendRis(clone $reviewBase)->count();
            $awaitingCosign = (int) self::scopeAwaitingCosign(DB::table('requisition_issue_slip_table'))->count();
        }

        self::$cached = [
            'pendingRis' => $pendingRis,
            'awaitingCosign' => $awaitingCosign,
            'amendRis' => $amendRis,
            'attentionTotal' => $pendingRis + $awaitingCosign + $amendRis,
        ];

        return self::$cached;
    }

    /**
     * Borrowed equipment past its expected return date (Borrowing is handled in the Administrator portal).
     */
    public static function overdueBorrows(): int
    {
        if (! Schema::hasTable('borrowing_records_table')) {
            return 0;
        }

        return (int) MaintenanceAttentionSummary::scopeOverdueBorrowings(DB::table('borrowing_records_table'))->count();
    }

    /**
     * Equipment maintenance schedules past their next date (Administrator Maintenance Schedule module).
     */
    public static function overdueMaintenanceSchedules(): int
    {
        if (! Schema::hasTable('maintenance_schedules_table')) {
            return 0;
        }

        return (int) MaintenanceAttentionSummary::scopeOverdueSchedules(
            DB::table('maintenance_schedules_table')
        )->count();
    }

    /**
     * Submitted / resubmitted RIS waiting for Administrator accept (Procurement Review).
     */
    public static function scopePendingRis($query, string $prefix = '')
    {
        return $query->whereNotNull($prefix.'ris_requested_by_date')
            ->whereIn($prefix.'ris_status', RisWorkflow::incomingStatuses());
    }

    /**
     * RIS returned for minor revision or rejected by Administrator (Procurement Review).
     */
    public static function scopeAmendRis($query, string $prefix = '')
    {
        return $query->whereNotNull($prefix.'ris_requested_by_date')
            ->whereIn($prefix.'ris_status', ['Minor Revision', 'Rejected']);
    }

    /**
     * Sign RIS "Pending" work: Accepted decisions plus President-approved RIS without Issued by.
     */
    public static function scopeAwaitingCosign($query, string $prefix = '')
    {
        return $query->where(function ($pending) use ($prefix) {
            $pending->where($prefix.'ris_status', RisWorkflow::ACCEPTED)
                ->orWhere(fn ($awaiting) => self::scopeAwaitingIssuedBy($awaiting, $prefix));
        });
    }

    /**
     * President-approved RIS that still need the School Administrator's Issued by signature.
     */
    public static function scopeAwaitingIssuedBy($query, string $prefix = '')
    {
        $status = $prefix.'ris_status';
        $approvedSig = $prefix.'ris_approved_by_signature';
        $issuedSig = $prefix.'ris_issued_by_signature';

        return $query->where(function ($approved) use ($status, $approvedSig) {
            $approved->where(function ($president) use ($status, $approvedSig) {
                $president->where($status, RisWorkflow::PRESIDENT_APPROVED)
                    ->whereNotNull($approvedSig)
                    ->whereRaw('TRIM('.$approvedSig.') != ""');
            })->orWhere(function ($legacy) use ($status, $approvedSig) {
                $legacy->where($status, RisWorkflow::APPROVED_LEGACY)
                    ->whereNotNull($approvedSig)
                    ->where($approvedSig, 'like', 'data:image%');
            });
        })->where(function ($unsigned) use ($issuedSig) {
            $unsigned->whereNull($issuedSig)
                ->orWhereRaw('TRIM('.$issuedSig.') = ""');
        });
    }

    /**
     * Banner metadata for a reminder focus key, or null when the key is unknown.
     *
     * @return array{label:string, description:string, scope:string}|null
     */
    public static function focusMeta(?string $focus): ?array
    {
        return match ($focus) {
            self::FOCUS_PENDING_REVIEW => [
                'label' => 'RIS to accept',
                'description' => 'Submitted, resubmitted, or under-review RIS waiting on Pending Accept.',
                'scope' => 'shared',
            ],
            self::FOCUS_AMENDMENTS => [
                'label' => 'Amendments / returned RIS',
                'description' => 'RIS marked for minor revision or rejected and still in the pipeline.',
                'scope' => 'shared',
            ],
            self::FOCUS_AWAITING_COSIGN => [
                'label' => 'RIS awaiting Ready to Sign action',
                'description' => 'Accepted RIS waiting for a decision plus President-approved RIS waiting for your Issued by signature.',
                'scope' => 'shared',
            ],
            default => null,
        };
    }
}
