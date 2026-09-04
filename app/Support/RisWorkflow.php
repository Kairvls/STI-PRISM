<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RisWorkflow
{
    public const ACCEPTED = 'Accepted';
    public const FORWARDED = 'Forwarded to President';
    public const PRESIDENT_APPROVED = 'Approved by the President';
    public const DIRECTLY_APPROVED = 'Directly Approved';
    public const PRESIDENT_REJECTED = 'Rejected by the President';
    public const PRESIDENT_REJECTED_LEGACY = 'Rejected by President';
    public const APPROVED_LEGACY = 'Approved';
    public const REQUEST_TYPE_REPLACEMENT = 'Replacement Procurement';
    public const REQUEST_TYPE_NEW = 'New Procurement';

    /** Purchaser-submitted RIS waiting for Admin accept on Procurement Requests. */
    public static function incomingStatuses(): array
    {
        return ['Pending', 'Submitted', 'Under Review', 'Resubmitted'];
    }

    public static function isIncoming(object $ris): bool
    {
        return in_array((string) ($ris->ris_status ?? ''), self::incomingStatuses(), true);
    }

    /** Accepted by Admin — ready for Forward / Direct Approve / Return on Sign RIS. */
    public static function isAccepted(object $ris): bool
    {
        return (string) ($ris->ris_status ?? '') === self::ACCEPTED;
    }

    /** Needs a signing decision (forward / direct approve / return) on Sign RIS. */
    public static function needsSignDecision(object $ris): bool
    {
        return self::isAccepted($ris);
    }

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

    /**
     * President approved, Issued by still blank — Admin must sign on Sign RIS.
     */
    public static function needsAdminIssuedBy(object $ris): bool
    {
        return self::isPresidentApproved($ris) && !self::hasIssuedBy($ris);
    }

    /**
     * Can Admin return this RIS to Purchaser for Minor Revision from Sign RIS.
     */
    public static function canReturnForRevision(object $ris): bool
    {
        return self::isPresidentRejected($ris)
            || (string) ($ris->ris_status ?? '') === 'Rejected';
    }

    public static function statusLabel(object $ris): string
    {
        $status = (string) ($ris->ris_status ?? '');

        if (in_array($status, self::incomingStatuses(), true)) {
            return 'Pending';
        }

        if ($status === self::ACCEPTED) {
            return 'Accepted';
        }

        if ($status === self::DIRECTLY_APPROVED) {
            return 'Directly approved by the Administrator';
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

    public static function normalizeDrawnSignature(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || !self::isDrawnSignature($value) || strlen($value) > 2000000) {
            return null;
        }

        return $value;
    }

    public static function requestedBySignatureDiskPath(int $risId): string
    {
        return 'ris-requested-by/'.$risId.'.png';
    }

    public static function storeRequestedBySignature(int $risId, string $dataUrl): ?string
    {
        $normalized = self::normalizeDrawnSignature($dataUrl);
        if (!$normalized || !preg_match('#^data:image/(png|jpeg|jpg);base64,(.+)$#is', $normalized, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        Storage::disk('public')->put(self::requestedBySignatureDiskPath($risId), $binary);

        return $normalized;
    }

    public static function requestedByDrawnSignature(?object $ris): string
    {
        if (!$ris) {
            return '';
        }

        $id = (int) ($ris->ris_id ?? 0);
        if ($id > 0) {
            $path = self::requestedBySignatureDiskPath($id);
            if (Storage::disk('public')->exists($path)) {
                $binary = Storage::disk('public')->get($path);
                if (is_string($binary) && $binary !== '') {
                    return 'data:image/png;base64,'.base64_encode($binary);
                }
            }
        }

        $image = self::normalizeDrawnSignature($ris->ris_requested_by_signature_image ?? null);
        if ($image) {
            return $image;
        }

        return self::normalizeDrawnSignature($ris->ris_requested_by_signature ?? null) ?? '';
    }

    public static function requestedByPrintedName(?object $ris): string
    {
        if (!$ris) {
            return '';
        }

        $name = trim((string) ($ris->ris_requested_by_signature ?? ''));
        if ($name === '' || self::isDrawnSignature($name)) {
            return '';
        }

        return $name;
    }

    /**
     * Printed name under Approved by / Checked by when the stored value is a drawn signature image.
     */
    public static function approvedByPrintedName(?object $ris, ?string $fallback = null): string
    {
        if (!$ris) {
            return trim((string) ($fallback ?? ''));
        }

        $raw = trim((string) ($ris->ris_approved_by_signature ?? ''));
        if ($raw === '') {
            return '';
        }

        if (!self::isDrawnSignature($raw)) {
            return $raw;
        }

        $named = trim((string) ($ris->ris_approved_by_name ?? ''));
        if ($named !== '') {
            return $named;
        }

        $isDirect = (($ris->ris_status ?? '') === self::DIRECTLY_APPROVED);
        $fromActor = self::resolveActorFullName($ris, $isDirect
            ? ['Admin Approval', 'Admin']
            : ['President']);
        if ($fromActor !== '') {
            return $fromActor;
        }

        if (
            $isDirect
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_by')
            && !empty($ris->ris_direct_approval_by)
            && Schema::hasTable('users_table')
        ) {
            try {
                $admin = DB::table('users_table')
                    ->where('user_id', (int) $ris->ris_direct_approval_by)
                    ->value('user_full_name');
                $adminName = trim((string) ($admin ?? ''));
                if ($adminName !== '') {
                    return $adminName;
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        $fallback = trim((string) ($fallback ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        return $isDirect ? 'Administrator' : 'President';
    }

    /**
     * Printed name under Issued by when the stored value is a drawn signature image.
     */
    public static function issuedByPrintedName(?object $ris, ?string $fallback = null): string
    {
        if (!$ris) {
            return trim((string) ($fallback ?? ''));
        }

        $raw = trim((string) ($ris->ris_issued_by_signature ?? ''));
        if ($raw === '') {
            return '';
        }

        if (!self::isDrawnSignature($raw)) {
            return $raw;
        }

        $named = trim((string) ($ris->ris_issued_by_name ?? ''));
        if ($named !== '') {
            return $named;
        }

        $fromActor = self::resolveActorFullName($ris, ['Admin Co-sign', 'Admin Approval', 'Admin']);
        if ($fromActor !== '') {
            return $fromActor;
        }

        if (
            Schema::hasColumn('requisition_issue_slip_table', 'ris_direct_approval_by')
            && !empty($ris->ris_direct_approval_by)
            && Schema::hasTable('users_table')
        ) {
            try {
                $admin = DB::table('users_table')
                    ->where('user_id', (int) $ris->ris_direct_approval_by)
                    ->value('user_full_name');
                $adminName = trim((string) ($admin ?? ''));
                if ($adminName !== '') {
                    return $adminName;
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        $fallback = trim((string) ($fallback ?? ''));

        return $fallback !== '' ? $fallback : 'Administrator';
    }

    public static function approvedByColumnLabel(?object $ris): string
    {
        return (($ris->ris_status ?? '') === self::DIRECTLY_APPROVED)
            ? 'Checked by:'
            : 'Approved by:';
    }

    private static function resolveActorFullName(?object $ris, array $levels): string
    {
        $risId = (int) ($ris->ris_id ?? 0);
        if (
            $risId <= 0
            || $levels === []
            || !Schema::hasTable('approval_logs_table')
            || !Schema::hasTable('users_table')
        ) {
            return '';
        }

        try {
            $row = DB::table('approval_logs_table')
                ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                ->where('approval_logs_table.approval_log_reference_id', $risId)
                ->whereIn('approval_logs_table.approval_log_level', $levels)
                ->orderByDesc('approval_logs_table.approval_log_approved_at')
                ->select('users_table.user_full_name')
                ->first();

            return trim((string) ($row->user_full_name ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
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
