<?php

namespace App\Support;

/**
 * Short status words and one color per word for every procurement badge.
 * Stored database values stay as they are. This only changes what the screen shows.
 */
class ProcurementStatus
{
    public const IN_PREPARATION = 'In preparation';

    public const WITH_SCHOOL_ADMINISTRATOR = 'With School Administrator';

    public const SENT_TO_SCHOOL_ADMINISTRATOR = 'Sent to the School Administrator';

    public const TO_REVIEW = 'To review';

    public const REVIEWED = 'Reviewed';

    public const WITH_PRESIDENT = 'With President';

    public const SA_FORWARDED_TO_PRESIDENT = 'SA forward RIS to President for Review & Approval';

    public const FOR_REVISION = 'For revision';

    public const SA_RETURN_RIS_FOR_REVISION = 'SA return RIS to Purchaser for revision';

    public const PRESIDENT_RETURN_RIS_FOR_REVISION = 'President return RIS to SA for revision';

    public const SA_FORWARD_RETURNED_RIS_TO_PURCHASER = 'SA forward returned RIS by President to Purchaser';

    public const APPROVED = 'Approved';

    public const APPROVED_BY_SA_READY_FOR_ATP = 'Approved by School Administrator';

    public const APPROVED_BY_PRESIDENT_NEED_ISSUED_BY = 'Approved RIS by President need Issued by';

    public const APPROVED_BY_PRESIDENT_TO_PURCHASER = 'Approved RIS by President to Purchaser';

    public const APPROVED_BY_PRESIDENT_READY_FOR_ATP = 'Approved by President';

    public const REJECTED = 'Rejected';

    public const REJECTED_BY_SA = 'Rejected by SA';

    public const REJECTED_BY_PRESIDENT = 'Rejected by President';

    public const WITH_ACCOUNTING = 'With Accounting';

    public const ACCOUNTING_NEED_REVISION = 'Accounting need revision';

    public const WITH_RECEIVING = 'With Receiving';

    public const INCOMPLETE = 'Partial Receive';

    public const COMPLETED = 'Completed';

    public const CHECKS_RELEASED = 'Checks released';

    public const READY_FOR_COLLECTION = 'Ready for collection';

    public const CHECK_COLLECTED = 'Check collected';

    public const OVERDUE = 'Overdue';

    /**
     * Unified semantic procurement badge palette:
     * Slate = Draft · Amber = Pending/Awaiting · Blue = Reviewing · Purple = Reviewed
     * Indigo = With/Forwarded to President · Orange = Returned/Revision · Emerald = Approved/Completed
     * Teal = Accounting/Collected · Sky = Receiving · Red = Rejected/Overdue
     *
     * @return array{0: string, 1: string, 2: string} background, text, ring
     */
    public static function colors(string $label): array
    {
        return match ($label) {
            self::IN_PREPARATION => ['#f1f5f9', '#475569', '#cbd5e1'],
            self::WITH_SCHOOL_ADMINISTRATOR => ['#fffbeb', '#b45309', '#fde68a'],
            self::SENT_TO_SCHOOL_ADMINISTRATOR => ['#fffbeb', '#b45309', '#fde68a'],
            self::TO_REVIEW => ['#fffbeb', '#b45309', '#fde68a'],
            self::REVIEWED => ['#f3e8ff', '#7e22ce', '#d8b4fe'],
            self::WITH_PRESIDENT => ['#eef2ff', '#4338ca', '#c7d2fe'],
            self::SA_FORWARDED_TO_PRESIDENT => ['#eef2ff', '#4338ca', '#c7d2fe'],
            self::FOR_REVISION => ['#fff7ed', '#c2410c', '#fed7aa'],
            self::SA_RETURN_RIS_FOR_REVISION => ['#fff7ed', '#c2410c', '#fed7aa'],
            self::PRESIDENT_RETURN_RIS_FOR_REVISION => ['#fff7ed', '#c2410c', '#fed7aa'],
            self::SA_FORWARD_RETURNED_RIS_TO_PURCHASER => ['#fff7ed', '#c2410c', '#fed7aa'],
            self::ACCOUNTING_NEED_REVISION => ['#fff7ed', '#c2410c', '#fed7aa'],
            self::APPROVED => ['#ecfdf5', '#047857', '#a7f3d0'],
            self::APPROVED_BY_SA_READY_FOR_ATP => ['#ecfdf5', '#047857', '#a7f3d0'],
            self::APPROVED_BY_PRESIDENT_NEED_ISSUED_BY => ['#ecfdf5', '#047857', '#a7f3d0'],
            self::APPROVED_BY_PRESIDENT_READY_FOR_ATP => ['#ecfdf5', '#047857', '#a7f3d0'],
            self::APPROVED_BY_PRESIDENT_TO_PURCHASER => ['#ecfdf5', '#047857', '#a7f3d0'],
            self::COMPLETED => ['#ecfdf5', '#047857', '#a7f3d0'],
            self::REJECTED => ['#fef2f2', '#dc2626', '#fecaca'],
            self::REJECTED_BY_SA => ['#fef2f2', '#dc2626', '#fecaca'],
            self::REJECTED_BY_PRESIDENT => ['#fef2f2', '#dc2626', '#fecaca'],
            self::OVERDUE => ['#fef2f2', '#dc2626', '#fecaca'],
            self::WITH_ACCOUNTING => ['#f0fdfa', '#0f766e', '#99f6e4'],
            self::CHECK_COLLECTED => ['#f0fdfa', '#0f766e', '#99f6e4'],
            self::WITH_RECEIVING => ['#f0f9ff', '#0369a1', '#bae6fd'],
            self::INCOMPLETE => ['#fffbeb', '#b45309', '#fde68a'],
            self::READY_FOR_COLLECTION => ['#fffbeb', '#b45309', '#fde68a'],
            self::CHECKS_RELEASED => ['#eff6ff', '#1d4ed8', '#bfdbfe'],
            default => ['#f1f5f9', '#475569', '#cbd5e1'],
        };
    }

    public static function style(string $label): string
    {
        [$bg, $fg, $ring] = self::colors($label);

        return 'background:'.$bg.' !important;color:'.$fg.' !important;box-shadow:inset 0 0 0 1px '.$ring.' !important;';
    }

    public static function wraps(string $label): bool
    {
        return $label === self::WITH_SCHOOL_ADMINISTRATOR
            || $label === self::SENT_TO_SCHOOL_ADMINISTRATOR
            || $label === self::ACCOUNTING_NEED_REVISION
            || $label === self::APPROVED_BY_SA_READY_FOR_ATP
            || $label === self::APPROVED_BY_PRESIDENT_NEED_ISSUED_BY
            || $label === self::APPROVED_BY_PRESIDENT_READY_FOR_ATP
            || $label === self::APPROVED_BY_PRESIDENT_TO_PURCHASER
            || $label === self::SA_FORWARDED_TO_PRESIDENT
            || $label === self::SA_RETURN_RIS_FOR_REVISION
            || $label === self::PRESIDENT_RETURN_RIS_FOR_REVISION
            || $label === self::SA_FORWARD_RETURNED_RIS_TO_PURCHASER;
    }

    public static function forRis(object $ris): string
    {
        $status = (string) ($ris->ris_status ?? '');

        if (ProcurementDraftLabel::isDraftStatus($status)) {
            return self::IN_PREPARATION;
        }

        if ($status === RisWorkflow::MINOR_REVISION) {
            return self::SA_RETURN_RIS_FOR_REVISION;
        }

        if ($status === RisWorkflow::RETURNED_BY_PRESIDENT) {
            return self::PRESIDENT_RETURN_RIS_FOR_REVISION;
        }

        if ($status === RisWorkflow::PRESIDENT_REVISION) {
            return self::SA_FORWARD_RETURNED_RIS_TO_PURCHASER;
        }

        if ($status === 'Rejected') {
            return self::REJECTED_BY_SA;
        }

        if (RisWorkflow::isPresidentRejected($ris)) {
            return self::REJECTED_BY_PRESIDENT;
        }

        if ($status === RisWorkflow::DIRECTLY_APPROVED) {
            return self::APPROVED_BY_SA_READY_FOR_ATP;
        }

        if (RisWorkflow::isPresidentApproved($ris)) {
            if (! RisWorkflow::hasIssuedBy($ris)) {
                return self::APPROVED_BY_PRESIDENT_NEED_ISSUED_BY;
            }

            return self::showingToPurchaser()
                ? self::APPROVED_BY_PRESIDENT_READY_FOR_ATP
                : self::APPROVED_BY_PRESIDENT_TO_PURCHASER;
        }

        if (
            RisWorkflow::isAwaitingPresident($ris)
            || $status === RisWorkflow::FORWARDED
            || ($status === RisWorkflow::APPROVED_LEGACY && ! RisWorkflow::presidentDigitalSignature($ris))
        ) {
            return self::SA_FORWARDED_TO_PRESIDENT;
        }

        if (in_array($status, RisWorkflow::incomingStatuses(), true)) {
            return self::TO_REVIEW;
        }

        if ($status === RisWorkflow::ACCEPTED) {
            return self::REVIEWED;
        }

        if (RisWorkflow::isEligibleForAtp($ris)) {
            return self::APPROVED;
        }

        return self::label($status, 'ris');
    }

    /**
     * @param  array{submitted?: mixed, revision?: mixed, overdue?: bool}  $flags
     */
    public static function label(?string $status, ?string $doc = null, array $flags = []): string
    {
        if (! empty($flags['overdue'])) {
            return self::OVERDUE;
        }

        $raw = trim((string) $status);
        $doc = $doc ? strtolower($doc) : null;

        if ($raw === '' || $raw === '—' || $raw === 'N/A') {
            return $raw === '' ? '—' : $raw;
        }

        if (in_array($raw, self::canonical(), true)) {
            return $raw;
        }

        if (
            ProcurementDraftLabel::isDraftStatus($raw)
            || strcasecmp($raw, ProcurementDraftLabel::STATUS_LONG) === 0
        ) {
            return self::IN_PREPARATION;
        }

        if ($doc === 'ris') {
            if ($raw === RisWorkflow::MINOR_REVISION) {
                return self::SA_RETURN_RIS_FOR_REVISION;
            }

            if ($raw === RisWorkflow::RETURNED_BY_PRESIDENT) {
                return self::PRESIDENT_RETURN_RIS_FOR_REVISION;
            }

            if ($raw === RisWorkflow::PRESIDENT_REVISION) {
                return self::SA_FORWARD_RETURNED_RIS_TO_PURCHASER;
            }

            if ($raw === 'Rejected') {
                return self::REJECTED_BY_SA;
            }

            if (in_array($raw, RisWorkflow::presidentRejectedStatuses(), true)) {
                return self::REJECTED_BY_PRESIDENT;
            }
        }

        if (in_array($raw, [
            'Minor Revision',
            'Revision',
            'Revision required',
            'President Revision',
            'Returned by the President',
            'Returned',
            'For revision by President',
            'For revision by School Administrator',
        ], true)) {
            return self::FOR_REVISION;
        }

        if (in_array($raw, [
            'Rejected',
            'Rejected by the President',
            'Rejected by President',
            'President rejected',
        ], true)) {
            return self::REJECTED;
        }

        if ($raw === 'Incomplete') {
            return self::INCOMPLETE;
        }

        if (in_array($raw, ['Completed', 'Delivered'], true) || ($raw === 'Accepted' && $doc === 'rr')) {
            return self::COMPLETED;
        }

        if ($raw === 'Accepted') {
            return self::WITH_SCHOOL_ADMINISTRATOR;
        }

        if (in_array($raw, ['Check collected', 'Checks collected', 'Check Collected'], true)) {
            return self::CHECK_COLLECTED;
        }

        if (in_array($raw, ['Checks released', 'Released', 'Funds released'], true)) {
            return self::CHECKS_RELEASED;
        }

        if (in_array($raw, ['Waiting for funds', 'Waiting for Accounting'], true)) {
            return self::ACCOUNTING_NEED_REVISION;
        }

        if (in_array($raw, [
            'Approved',
            'Directly Approved',
            'Directly approved',
            'Approved by the President',
            'President approved',
            'President Approved',
        ], true)) {
            return self::APPROVED;
        }

        if (in_array($raw, ['Forwarded to President', 'Forwarded to the President'], true)) {
            return self::SA_FORWARDED_TO_PRESIDENT;
        }

        if (in_array($raw, ['Pending Admin Approval', 'For Admin'], true)) {
            return self::WITH_SCHOOL_ADMINISTRATOR;
        }

        if (in_array($raw, ['Pending', 'Submitted', 'Under Review', 'Resubmitted', 'Submitted to SA'], true)) {
            $submitted = $flags['submitted'] ?? null;
            $hasRevision = filled($flags['revision'] ?? null);

            if ($raw === 'Pending' && blank($submitted) && $hasRevision) {
                return self::FOR_REVISION;
            }

            if ($raw === 'Pending' && blank($submitted)) {
                return self::IN_PREPARATION;
            }

            if ($doc === 'rr') {
                return self::WITH_RECEIVING;
            }

            if ($doc === 'liq') {
                return self::WITH_ACCOUNTING;
            }

            if ($doc === 'atp' || $doc === 'rfc') {
                return self::SENT_TO_SCHOOL_ADMINISTRATOR;
            }

            return self::WITH_SCHOOL_ADMINISTRATOR;
        }

        if (strcasecmp($raw, 'Overdue') === 0) {
            return self::OVERDUE;
        }

        return $raw;
    }

    /**
     * Purchaser pages show the ATP-ready wording once Issued by is signed.
     */
    private static function showingToPurchaser(): bool
    {
        return request()->is('purchaser', 'purchaser/*');
    }

    /**
     * @return list<string>
     */
    public static function canonical(): array
    {
        return [
            self::IN_PREPARATION,
            self::WITH_SCHOOL_ADMINISTRATOR,
            self::SENT_TO_SCHOOL_ADMINISTRATOR,
            self::TO_REVIEW,
            self::REVIEWED,
            self::WITH_PRESIDENT,
            self::SA_FORWARDED_TO_PRESIDENT,
            self::FOR_REVISION,
            self::SA_RETURN_RIS_FOR_REVISION,
            self::PRESIDENT_RETURN_RIS_FOR_REVISION,
            self::SA_FORWARD_RETURNED_RIS_TO_PURCHASER,
            self::APPROVED,
            self::APPROVED_BY_SA_READY_FOR_ATP,
            self::APPROVED_BY_PRESIDENT_NEED_ISSUED_BY,
            self::APPROVED_BY_PRESIDENT_TO_PURCHASER,
            self::APPROVED_BY_PRESIDENT_READY_FOR_ATP,
            self::REJECTED,
            self::REJECTED_BY_SA,
            self::REJECTED_BY_PRESIDENT,
            self::WITH_ACCOUNTING,
            self::ACCOUNTING_NEED_REVISION,
            self::WITH_RECEIVING,
            self::INCOMPLETE,
            self::COMPLETED,
            self::CHECKS_RELEASED,
            self::READY_FOR_COLLECTION,
            self::CHECK_COLLECTED,
            self::OVERDUE,
        ];
    }
}
