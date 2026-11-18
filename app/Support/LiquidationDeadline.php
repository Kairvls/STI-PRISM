<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Liquidation creation window: 15 days after activity end / date released / RR completion.
 *
 * In-preparation (Draft) LRs do not start that clock and are never marked overdue.
 * Overdue applies to cash-advance RRs that still have no LR past the window,
 * and to returned revisions that remain past deadline.
 */
class LiquidationDeadline
{
    public const DAYS = 15;

    /** Statuses still editable by the purchaser. */
    public const OPEN_STATUSES = ['Draft', 'Minor Revision'];

    public static function resolveDeadline(
        ?string $activityEnd,
        ?string $dateReleased = null,
        ?string $fallbackDate = null
    ): ?string {
        $anchor = self::firstDate($activityEnd, $dateReleased, $fallbackDate);
        if ($anchor === null) {
            return null;
        }

        return Carbon::parse($anchor)->startOfDay()->addDays(self::DAYS)->toDateString();
    }

    public static function isPastDeadline(?string $deadline, $asOf = null): bool
    {
        if (! $deadline) {
            return false;
        }

        $day = Carbon::parse($asOf ?? now())->startOfDay();
        $due = Carbon::parse($deadline)->startOfDay();

        return $day->greaterThan($due);
    }

    /**
     * Days past the deadline (0 if on time / same day). Null if no deadline.
     */
    public static function daysLapsed(?string $deadline, $asOf = null): ?int
    {
        if (! $deadline) {
            return null;
        }

        $day = Carbon::parse($asOf ?? now())->startOfDay();
        $due = Carbon::parse($deadline)->startOfDay();
        if ($day->lessThanOrEqualTo($due)) {
            return 0;
        }

        return (int) $due->diffInDays($day);
    }

    public static function isCreationOverdue(object $liq, $asOf = null): bool
    {
        $status = (string) ($liq->liquidation_report_status ?? '');
        // In preparation: do not start / apply the 15-day overdue verdict on this LR yet.
        if (ProcurementDraftLabel::isDraftStatus($status)) {
            return false;
        }
        if ($status !== 'Minor Revision') {
            return false;
        }

        $deadline = self::deadlineForLiq($liq);
        if (! $deadline) {
            return false;
        }

        return self::isPastDeadline($deadline, $asOf);
    }

    public static function deadlineForLiq(object $liq): ?string
    {
        $stored = $liq->liquidation_report_submission_deadline ?? null;
        if ($stored) {
            return Carbon::parse($stored)->toDateString();
        }

        // Never fall back to created_at — that incorrectly started the clock on drafts.
        return self::resolveDeadline(
            $liq->liquidation_report_activity_end_date ?? null,
            $liq->liquidation_report_date_released ?? null,
            null
        );
    }

    /**
     * Sync deadline fields for open LRs; collect overdue items for this owner.
     * Draft / in-preparation LRs are never returned as overdue.
     *
     * @return array{liquidations: list<object>, pending_rrs: list<object>}
     */
    public static function syncAndCollectForUser(?int $userId = null): array
    {
        $userId = $userId ?: (int) (auth()->id() ?? 0);
        $liquidations = [];
        $pendingRrs = [];

        if (! Schema::hasTable('liquidation_reports_table')) {
            return ['liquidations' => $liquidations, 'pending_rrs' => $pendingRrs];
        }

        $query = DB::table('liquidation_reports_table')
            ->whereIn('liquidation_report_status', self::OPEN_STATUSES);

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')
                    ->orWhere('liquidation_report_is_archived', 0);
            });
        }

        if ($userId > 0) {
            PurchaserDocumentAccess::scopeOwned($query, 'liq', 'liquidation_reports_table');
        }

        foreach ($query->get() as $liq) {
            $isDraft = ProcurementDraftLabel::isDraftStatus($liq->liquidation_report_status ?? null);
            $deadline = self::resolveDeadline(
                $liq->liquidation_report_activity_end_date ?? null,
                $liq->liquidation_report_date_released ?? null,
                null
            );

            // Clear a deadline that was wrongly derived from created_at while still in preparation.
            if ($isDraft && ! $deadline) {
                if (($liq->liquidation_report_submission_deadline ?? null) !== null
                    || ($liq->liquidation_report_days_lapse ?? null) !== null
                ) {
                    $payload = [
                        'liquidation_report_submission_deadline' => null,
                        'liquidation_report_days_lapse' => null,
                    ];
                    if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_updated_at')) {
                        $payload['liquidation_report_updated_at'] = now();
                    }
                    DB::table('liquidation_reports_table')
                        ->where('liquidation_report_id', $liq->liquidation_report_id)
                        ->update($payload);
                }
                continue;
            }

            if (! $deadline) {
                continue;
            }

            $days = $isDraft ? null : self::daysLapsed($deadline);
            $needsUpdate = (($liq->liquidation_report_submission_deadline ?? null) !== $deadline)
                || (($liq->liquidation_report_days_lapse ?? null) != $days);

            if ($needsUpdate) {
                $payload = [
                    'liquidation_report_submission_deadline' => $deadline,
                    'liquidation_report_days_lapse' => $days,
                ];
                if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_updated_at')) {
                    $payload['liquidation_report_updated_at'] = now();
                }
                DB::table('liquidation_reports_table')
                    ->where('liquidation_report_id', $liq->liquidation_report_id)
                    ->update($payload);
                $liq->liquidation_report_submission_deadline = $deadline;
                $liq->liquidation_report_days_lapse = $days;
            }

            // In preparation never counts as overdue; only returned revisions past deadline.
            if (! $isDraft && self::isPastDeadline($deadline)) {
                $liquidations[] = $liq;
            }
        }

        $pendingRrs = self::overduePendingReceivingReports($userId);

        return ['liquidations' => $liquidations, 'pending_rrs' => $pendingRrs];
    }

    /**
     * Completed cash-advance RRs with no active LR, past RR-complete + 15 days.
     *
     * @return list<object>
     */
    public static function overduePendingReceivingReports(?int $userId = null): array
    {
        if (! Schema::hasTable('receiving_reports_table') || ! Schema::hasTable('liquidation_reports_table')) {
            return [];
        }

        $query = DB::table('receiving_reports_table')
            ->leftJoin(
                'request_check_table',
                'receiving_reports_table.receiving_report_request_check_id',
                '=',
                'request_check_table.request_check_id'
            )
            ->leftJoin(
                'authority_to_purchase_table',
                function ($join) {
                    if (Schema::hasColumn('receiving_reports_table', 'receiving_report_atp_id')) {
                        $join->on(
                            'authority_to_purchase_table.authority_purchase_id',
                            '=',
                            DB::raw('COALESCE(receiving_reports_table.receiving_report_atp_id, request_check_table.request_check_authority_purchase_id)')
                        );
                    } else {
                        $join->on(
                            'authority_to_purchase_table.authority_purchase_id',
                            '=',
                            'request_check_table.request_check_authority_purchase_id'
                        );
                    }
                }
            )
            ->where('receiving_reports_table.receiving_report_status', 'Completed')
            ->where(function ($q) {
                $q->where('authority_to_purchase_table.authority_purchase_payment_path', ProcurementPaymentPath::CASH_ADVANCE)
                    ->orWhere('request_check_table.request_check_funding_type', ProcurementPaymentPath::CASH_ADVANCE);
            })
            ->where(function ($q) {
                $q->whereNull('receiving_reports_table.receiving_report_is_archived')
                    ->orWhere('receiving_reports_table.receiving_report_is_archived', 0);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('liquidation_reports_table')
                    ->whereColumn(
                        'liquidation_reports_table.liquidation_report_receiving_report_id',
                        'receiving_reports_table.receiving_report_id'
                    )
                    ->where(function ($inner) {
                        $inner->whereNull('liquidation_report_is_archived')
                            ->orWhere('liquidation_report_is_archived', 0);
                    })
                    ->whereNotIn('liquidation_report_status', ['Rejected']);
            });

        if ($userId && $userId > 0) {
            PurchaserDocumentAccess::scopeOwned($query, 'rr', 'receiving_reports_table');
        }

        $rows = $query->select(
            'receiving_reports_table.receiving_report_id',
            'receiving_reports_table.receiving_report_form_number',
            'receiving_reports_table.receiving_report_created_at',
            'receiving_reports_table.receiving_report_updated_at'
        )->get();

        $overdue = [];
        foreach ($rows as $rr) {
            $anchor = $rr->receiving_report_updated_at ?: $rr->receiving_report_created_at;
            if (! $anchor) {
                continue;
            }
            $deadline = Carbon::parse($anchor)->startOfDay()->addDays(self::DAYS)->toDateString();
            if (! self::isPastDeadline($deadline)) {
                continue;
            }
            $rr->liquidation_deadline = $deadline;
            $rr->days_lapsed = self::daysLapsed($deadline);
            $overdue[] = $rr;
        }

        return $overdue;
    }

    public static function overdueCountForUser(?int $userId = null): int
    {
        $bag = self::syncAndCollectForUser($userId);

        return count($bag['liquidations']) + count($bag['pending_rrs']);
    }

    /**
     * Ensure bell notifications exist for overdue items (deduped by event key).
     *
     * @param  array{liquidations?: list<object>, pending_rrs?: list<object>}|null  $bag
     */
    public static function ensureNotifications(?int $userId = null, ?array $bag = null): void
    {
        if (! Schema::hasTable('notifications_table')) {
            return;
        }

        $userId = $userId ?: (int) (auth()->id() ?? 0);
        if ($userId < 1) {
            return;
        }

        $bag = $bag ?? self::syncAndCollectForUser($userId);
        $portal = '/purchaser/liquidation-reports';

        foreach ($bag['liquidations'] as $liq) {
            $id = (int) $liq->liquidation_report_id;
            $label = $liq->liquidation_report_form_number
                ?: ProcurementDraftLabel::untitledWithId('lr', $id);
            $days = (int) ($liq->liquidation_report_days_lapse ?? self::daysLapsed(self::deadlineForLiq($liq)) ?? 0);
            self::notifyOnce(
                $userId,
                'liq_overdue_'.$id.'_u'.$userId,
                'Liquidation overdue',
                $label.' is past the '.self::DAYS.'-day creation window'
                    .($days > 0 ? ' ('.$days.' day'.($days === 1 ? '' : 's').' late).' : '.'),
                'liq',
                $id,
                $portal.'?view_liq='.$id
            );
        }

        foreach ($bag['pending_rrs'] as $rr) {
            $id = (int) $rr->receiving_report_id;
            $label = $rr->receiving_report_form_number ?: ('RR #'.$id);
            $days = (int) ($rr->days_lapsed ?? 0);
            self::notifyOnce(
                $userId,
                'liq_rr_overdue_'.$id.'_u'.$userId,
                'Liquidation overdue',
                $label.' still needs a Liquidation Report — past the '.self::DAYS.'-day window'
                    .($days > 0 ? ' ('.$days.' day'.($days === 1 ? '' : 's').' late).' : '.'),
                'rr',
                $id,
                $portal.'?selected_rr='.$id
            );
        }
    }

    private static function notifyOnce(
        int $userId,
        string $eventKey,
        string $title,
        string $message,
        string $refType,
        int $refId,
        string $url
    ): void {
        try {
            $exists = DB::table('notifications_table')
                ->where('notification_event_key', $eventKey)
                ->exists();
            if ($exists) {
                return;
            }

            DB::table('notifications_table')->insert([
                'notification_user_id' => $userId,
                'notification_target_role' => WorkflowNotifier::ROLE_PURCHASER,
                'notification_title' => $title,
                'notification_message' => $message,
                'notification_type' => 'liquidation_overdue',
                'notification_category' => 'workflow',
                'notification_reference_type' => $refType,
                'notification_reference_id' => $refId,
                'notification_url' => $url,
                'notification_event_key' => $eventKey,
                'notification_created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    private static function firstDate(?string ...$candidates): ?string
    {
        foreach ($candidates as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Throwable $e) {
            }
        }

        return null;
    }
}
