<?php

namespace App\Support;

class RisWorkflow
{
    public const FORWARDED = 'Forwarded to President';
    public const PRESIDENT_APPROVED = 'Approved by the President';
    public const DIRECTLY_APPROVED = 'Directly Approved';
    public const PRESIDENT_REJECTED = 'Rejected by the President';
    public const PRESIDENT_REJECTED_LEGACY = 'Rejected by President';
    public const APPROVED_LEGACY = 'Approved';
    public const REQUEST_TYPE_REPLACEMENT = 'Replacement Procurement';
    public const REQUEST_TYPE_NEW = 'New Procurement';

    public static function presidentRejectedStatuses(): array
    {
        return [self::PRESIDENT_REJECTED, self::PRESIDENT_REJECTED_LEGACY];
    }

    public static function hasIssuedBy(object $ris): bool
    {
        return trim((string) ($ris->ris_issued_by_signature ?? '')) !== '';
    }

    public static function presidentDigitalSignature(object $ris): bool
    {
        $sig = trim((string) ($ris->ris_approved_by_signature ?? ''));

        return $sig !== '' && str_starts_with($sig, 'data:image');
    }

    public static function isEligibleForAtp(object $ris): bool
    {
        $status = (string) ($ris->ris_status ?? '');

        if ($status === self::DIRECTLY_APPROVED) {
            return true;
        }

        if (in_array($status, [self::PRESIDENT_APPROVED, self::APPROVED_LEGACY], true)) {
            return self::hasIssuedBy($ris);
        }

        return false;
    }

    public static function applyEligibleForAtpScope($query, string $table = 'requisition_issue_slip_table')
    {
        $status = $table.'.ris_status';
        $issued = $table.'.ris_issued_by_signature';

        return $query->where(function ($q) use ($status, $issued) {
            $q->where($status, self::DIRECTLY_APPROVED)
                ->orWhere(function ($released) use ($status, $issued) {
                    $released->whereIn($status, [self::PRESIDENT_APPROVED, self::APPROVED_LEGACY])
                        ->whereNotNull($issued)
                        ->whereRaw('TRIM('.$issued.') != ""');
                });
        });
    }

    public static function isAwaitingPresident(object $ris): bool
    {
        if (($ris->ris_status ?? '') === self::DIRECTLY_APPROVED) {
            return false;
        }

        if (trim((string) ($ris->ris_approved_by_signature ?? '')) !== '') {
            return false;
        }

        return in_array((string) ($ris->ris_status ?? ''), [
            self::FORWARDED,
            self::APPROVED_LEGACY,
        ], true);
    }

    public static function isPresidentApproved(object $ris): bool
    {
        $status = (string) ($ris->ris_status ?? '');
        $sig = trim((string) ($ris->ris_approved_by_signature ?? ''));

        if ($sig === '') {
            return false;
        }

        if ($status === self::PRESIDENT_APPROVED) {
            return true;
        }

        return $status === self::APPROVED_LEGACY && str_starts_with($sig, 'data:image');
    }

    public static function isPresidentRejected(object $ris): bool
    {
        return in_array((string) ($ris->ris_status ?? ''), self::presidentRejectedStatuses(), true);
    }

    public static function statusLabel(object $ris): string
    {
        $status = (string) ($ris->ris_status ?? '');

        if (in_array($status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
            return 'Pending';
        }

        if ($status === self::DIRECTLY_APPROVED) {
            return 'Admin Approved';
        }

        if (self::isPresidentRejected($ris)) {
            return self::PRESIDENT_REJECTED;
        }

        if (self::isPresidentApproved($ris)) {
            return self::hasIssuedBy($ris)
                ? self::PRESIDENT_APPROVED
                : 'Awaiting Admin';
        }

        if ($status === self::FORWARDED || ($status === self::APPROVED_LEGACY && !self::presidentDigitalSignature($ris))) {
            return self::FORWARDED;
        }

        if (in_array($status, ['Minor Revision', 'Rejected'], true)) {
            return 'Amend';
        }

        return $status !== '' ? $status : 'N/A';
    }

    public static function requestType(?int $procurementRequestId): string
    {
        return $procurementRequestId
            ? self::REQUEST_TYPE_REPLACEMENT
            : self::REQUEST_TYPE_NEW;
    }

    public static function sourceLabel(object $ris): string
    {
        $items = trim((string) ($ris->ris_item_names ?? ''));
        if ($items !== '') {
            return $items;
        }

        $title = trim((string) ($ris->ris_manual_title ?? ''));
        if ($title !== '') {
            return $title;
        }

        $equipment = trim((string) ($ris->equipment_name ?? ''));
        if ($equipment !== '') {
            return $equipment;
        }

        $unlisted = trim((string) ($ris->report_unlisted_equipment_name ?? ''));
        if ($unlisted !== '') {
            return $unlisted;
        }

        return self::requestTypeLabel($ris);
    }

    public static function requestTypeLabel(object $ris): string
    {
        $type = (string) ($ris->ris_request_type ?? '');

        if (in_array($type, [self::REQUEST_TYPE_REPLACEMENT, 'Replacement'], true)) {
            return self::REQUEST_TYPE_REPLACEMENT;
        }

        if (in_array($type, [self::REQUEST_TYPE_NEW, 'manual', 'Manual Procurement'], true) || $type === '') {
            return self::REQUEST_TYPE_NEW;
        }

        return $type;
    }

    public static function atpNeedsRevision(object $atp): bool
    {
        return ($atp->authority_purchase_status ?? '') === 'Pending'
            && blank($atp->authority_purchase_submitted_at ?? null)
            && filled($atp->authority_purchase_rejection_reason ?? null);
    }

    public static function atpStatusLabel(object $atp): string
    {
        if (self::atpNeedsRevision($atp)) {
            return 'Minor Revision';
        }

        if (($atp->authority_purchase_status ?? '') === 'Pending') {
            return filled($atp->authority_purchase_submitted_at ?? null) ? 'Submitted' : 'Draft';
        }

        return (string) ($atp->authority_purchase_status ?? '—');
    }

    public static function isDrawnSignature(?string $value): bool
    {
        return str_starts_with(trim((string) $value), 'data:image');
    }

    public static function drawnOrName(?string $signatureData, string $fallback): string
    {
        $sig = trim((string) $signatureData);

        return self::isDrawnSignature($sig) ? $sig : $fallback;
    }

    public static function equipmentLabel(object $source): string
    {
        $name = trim((string) ($source->equipment_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $unlisted = trim((string) ($source->report_unlisted_equipment_name ?? ''));

        return $unlisted !== '' ? $unlisted : 'Unspecified equipment';
    }

    public static function replacementPurpose(object $source): string
    {
        $purpose = 'Replacement of ' . self::equipmentLabel($source);
        $room = trim((string) ($source->room_name ?? ''));
        if ($room !== '') {
            $purpose .= ' in ' . $room;
        }

        $reason = trim((string) ($source->report_replacement_notes ?? ''));
        if ($reason === '') {
            $reason = trim((string) ($source->report_problem_description ?? ''));
        }
        if ($reason !== '') {
            $purpose .= '. Reason: ' . $reason;
        }

        return $purpose;
    }
}
