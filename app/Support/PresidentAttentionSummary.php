<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PresidentAttentionSummary
{
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

        $pendingApprovalsCount = (int) DB::table('requisition_issue_slip_table')
            ->where(function ($q) {
                self::scopeAwaitingPresident($q);
            })
            ->count();

        $awaitingNotifyCount = DB::table('requisition_issue_slip_table')
            ->where(function ($q) {
                self::scopePresidentApproved($q);
            })
            ->where(function ($q) {
                $q->whereNull('ris_issued_by_signature')
                    ->orWhere('ris_issued_by_signature', '');
            })
            ->get()
            ->filter(fn ($ris) => ! self::presidentHasNotifiedAdmin((int) $ris->ris_id))
            ->count();

        self::$cached = [
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'awaitingNotifyCount' => (int) $awaitingNotifyCount,
            'attentionTotal' => $pendingApprovalsCount + (int) $awaitingNotifyCount,
        ];

        return self::$cached;
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
                ->where(function ($q) {
                    $q->where('approval_log_approval_remarks', 'Notified Admin for co-sign')
                        ->orWhere('approval_log_approval_remarks', 'Forwarded to Admin for co-sign');
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
