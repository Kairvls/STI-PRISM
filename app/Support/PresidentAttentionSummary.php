<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PresidentAttentionSummary
{
    public const FOCUS_AWAITING_APPROVAL = 'awaiting-approval';
    public const FOCUS_AWAITING_NOTIFY = 'awaiting-notify';

    private const NOTIFY_REMARKS = [
        'Notified Administrator for co-sign',
        'Notified Admin for co-sign',
        'Forwarded to Admin for co-sign',
    ];

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * Actionable president workload counts.
     *
     * @return array{
     *     pendingApprovalsCount: int,
     *     awaitingNotifyCount: int,
     *     attentionTotal: int
     * }
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $pendingApprovalsCount = (int) self::scopePendingApproval(DB::table('requisition_issue_slip_table'))->count();
        $awaitingNotifyCount = (int) self::scopeAwaitingNotify(DB::table('requisition_issue_slip_table'))->count();

        self::$cached = [
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'awaitingNotifyCount' => $awaitingNotifyCount,
            'attentionTotal' => $pendingApprovalsCount + $awaitingNotifyCount,
        ];

        return self::$cached;
    }

    /**
     * Forwarded RIS waiting for the President's decision (Approvals queue).
     */
    public static function scopePendingApproval($query, string $prefix = '')
    {
        return $query->whereNotNull($prefix.'ris_requested_by_date')
            ->where($prefix.'ris_status', '!=', RisWorkflow::DIRECTLY_APPROVED)
            ->where(function ($q) use ($prefix) {
                self::scopeAwaitingPresident($q, $prefix);
            });
    }

    /**
     * President-approved RIS without Issued by where the President has not yet notified Administrator.
     */
    public static function scopeAwaitingNotify($query, string $prefix = '')
    {
        $issuedSig = $prefix.'ris_issued_by_signature';
        $risId = $prefix.'ris_id';

        $query->where(function ($q) use ($prefix) {
            self::scopePresidentApproved($q, $prefix);
        })->where(function ($q) use ($issuedSig) {
            $q->whereNull($issuedSig)->orWhere($issuedSig, '');
        });

        if (Schema::hasTable('approval_logs_table')) {
            $query->whereNotExists(function ($sub) use ($risId) {
                $sub->select(DB::raw(1))
                    ->from('approval_logs_table as notify_log')
                    ->whereColumn('notify_log.approval_log_reference_id', $risId)
                    ->where('notify_log.approval_log_reference_type', 'RIS')
                    ->where('notify_log.approval_log_level', 'President')
                    ->whereIn('notify_log.approval_log_approval_remarks', self::NOTIFY_REMARKS);
            });
        }

        return $query;
    }

    /**
     * Banner metadata for a reminder focus key, or null when the key is unknown.
     *
     * @return array{label:string, description:string, scope:string}|null
     */
    public static function focusMeta(?string $focus): ?array
    {
        return match ($focus) {
            self::FOCUS_AWAITING_APPROVAL => [
                'label' => 'RIS awaiting your decision',
                'description' => 'Forwarded RIS still pending presidential approval or rejection.',
                'scope' => 'shared',
            ],
            self::FOCUS_AWAITING_NOTIFY => [
                'label' => 'Approved RIS to notify Administrator',
                'description' => 'President-approved RIS without an Issued by signature where Administrator has not been notified yet.',
                'scope' => 'shared',
            ],
            default => null,
        };
    }

    public static function scopeAwaitingPresident($query, string $prefix = '')
    {
        $status = $prefix.'ris_status';
        $sig = $prefix.'ris_approved_by_signature';
        $approvedDate = $prefix.'ris_approved_by_date';

        return $query->where(function ($q) use ($status, $sig, $approvedDate) {
            $q->where($status, 'Forwarded to President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    $legacy->where($status, 'Approved')
                        ->where(function ($empty) use ($sig) {
                            $empty->whereNull($sig)->orWhere($sig, '');
                        });
                })
                ->orWhere(function ($queuedPending) use ($status, $sig, $approvedDate) {
                    $queuedPending->where($status, 'Pending')
                        ->whereNotNull($approvedDate)
                        ->where(function ($empty) use ($sig) {
                            $empty->whereNull($sig)->orWhere($sig, '');
                        });
                });
        });
    }

    public static function scopePresidentApproved($query, string $prefix = '')
    {
        $status = $prefix.'ris_status';
        $sig = $prefix.'ris_approved_by_signature';

        return $query->where(function ($q) use ($status, $sig) {
            $q->where($status, 'Approved by the President')
                ->orWhere(function ($legacy) use ($status, $sig) {
                    $legacy->where($status, 'Approved')
                        ->whereNotNull($sig)
                        ->where($sig, '!=', '')
                        ->where($sig, 'like', 'data:image%');
                });
        });
    }

    public static function presidentHasNotifiedAdmin(int $risId): bool
    {
        try {
            return DB::table('approval_logs_table')
                ->where('approval_log_reference_type', 'RIS')
                ->where('approval_log_reference_id', $risId)
                ->where('approval_log_level', 'President')
                ->whereIn('approval_log_approval_remarks', self::NOTIFY_REMARKS)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
