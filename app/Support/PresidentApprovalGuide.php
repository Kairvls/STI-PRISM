<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Compares a waiting request with the president's own past approvals
 * so the dashboard can suggest approve, review, or hold.
 */
class PresidentApprovalGuide
{
    public static function attach(Collection $rows): Collection
    {
        $stats = self::stats();

        return $rows->map(function ($row) use ($stats) {
            $row->guide = self::forRequest(
                (float) ($row->total_amount ?? 0),
                trim((string) ($row->ris_purpose_description ?? '')) !== '',
                $stats
            );

            return $row;
        });
    }

    /**
     * @return array{typical: float, month_usual: float, month_so_far: float, samples: int}
     */
    private static function stats(): array
    {
        $empty = ['typical' => 0.0, 'month_usual' => 0.0, 'month_so_far' => 0.0, 'samples' => 0];

        try {
            $rows = DB::table('requisition_issue_slip_table as ris')
                ->leftJoin('requisition_issue_slip_items_table as items', 'ris.ris_id', '=', 'items.ris_id')
                ->where(function ($query) {
                    $query->where('ris.ris_status', 'Approved by the President')
                        ->orWhere(function ($legacy) {
                            $legacy->where('ris.ris_status', 'Approved')
                                ->where('ris.ris_approved_by_signature', 'like', 'data:image%');
                        });
                })
                ->where('ris.ris_approved_by_date', '>=', now()->subYear()->toDateString())
                ->groupBy('ris.ris_id')
                ->selectRaw('COALESCE(SUM(items.ris_total_amount), 0) as amount, MAX(DATE_FORMAT(ris.ris_approved_by_date, "%Y-%m")) as period')
                ->get();
        } catch (\Throwable $e) {
            return $empty;
        }

        if ($rows->isEmpty()) {
            return $empty;
        }

        $byMonth = $rows->groupBy('period')->map(fn ($group) => (float) $group->sum('amount'));
        $thisMonth = now()->format('Y-m');

        return [
            'typical' => (float) $rows->avg('amount'),
            'month_usual' => (float) $byMonth->avg(),
            'month_so_far' => (float) ($byMonth[$thisMonth] ?? 0),
            'samples' => $rows->count(),
        ];
    }

    /**
     * @param  array{typical: float, month_usual: float, month_so_far: float, samples: int}  $stats
     * @return array{tone: string, label: string, reason: string}
     */
    private static function forRequest(float $amount, bool $hasPurpose, array $stats): array
    {
        if ($stats['samples'] < 3) {
            return [
                'tone' => 'review',
                'label' => 'Review first',
                'reason' => 'Not enough past approvals yet to suggest a decision.',
            ];
        }

        if ($amount <= 0) {
            return [
                'tone' => 'hold',
                'label' => 'Hold',
                'reason' => 'This request has no amount, so it is not ready to approve.',
            ];
        }

        $typical = $stats['typical'];
        $wouldExceedMonth = $stats['month_usual'] > 0
            && ($stats['month_so_far'] + $amount) > ($stats['month_usual'] * 1.25);
        $muchHigher = $typical > 0 && $amount > ($typical * 2);
        $inLine = $typical > 0 && $amount <= ($typical * 1.25);

        if ($muchHigher || $wouldExceedMonth || ! $hasPurpose) {
            $reason = match (true) {
                ! $hasPurpose => 'The purpose is blank. Read the request before approving.',
                $muchHigher => 'This amount is much higher than what you usually approve.',
                default => 'Approving this would push this month above your usual budget.',
            };

            return [
                'tone' => $muchHigher || ! $hasPurpose ? 'hold' : 'review',
                'label' => $muchHigher || ! $hasPurpose ? 'Hold' : 'Review first',
                'reason' => $reason,
            ];
        }

        if ($inLine) {
            return [
                'tone' => 'approve',
                'label' => 'Lean approve',
                'reason' => 'The amount is in line with requests you usually approve.',
            ];
        }

        return [
            'tone' => 'review',
            'label' => 'Review first',
            'reason' => 'The amount is a bit above what you usually approve.',
        ];
    }
}
