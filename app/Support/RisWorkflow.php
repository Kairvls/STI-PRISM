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
    public const URGENCY_URGENT = 'Urgent';
    public const URGENCY_NON_URGENT = 'Non-Urgent';

    /** RIS No. pattern: RIS-YYYYMM-0000000 */
    public const FORM_NUMBER_REGEX = '/^RIS-\d{6}-\d{7}$/';

    public static function urgencyOptions(): array
    {
        return [self::URGENCY_NON_URGENT, self::URGENCY_URGENT];
    }

    public static function normalizeUrgency(?string $value): string
    {
        $value = trim((string) $value);

        return $value === self::URGENCY_URGENT
            ? self::URGENCY_URGENT
            : self::URGENCY_NON_URGENT;
    }

    public static function urgencyLabel(?object $ris = null, ?string $value = null): string
    {
        $raw = $value ?? (string) ($ris->ris_urgency ?? '');

        return self::normalizeUrgency($raw);
    }

    public static function isUrgent(?object $ris = null, ?string $value = null): bool
    {
        return self::urgencyLabel($ris, $value) === self::URGENCY_URGENT;
    }

    /**
     * Display / stored RIS "No." value (RIS-YYYYMM-0000000).
     * Prefers the saved form number; otherwise builds a display fallback from id + month.
     *
     * @param  object|string|null  $risOrNumber
     */
    public static function formNumber($risOrNumber = null, ?int $risId = null, $createdAt = null): string
    {
        if (is_string($risOrNumber)) {
            $stored = trim($risOrNumber);
            if ($stored !== '') {
                return $stored;
            }
        } elseif (is_object($risOrNumber)) {
            $stored = trim((string) ($risOrNumber->ris_form_number ?? ''));
            if ($stored !== '') {
                return $stored;
            }
            // Drafts intentionally have no RIS No. until submitted.
            if ((string) ($risOrNumber->ris_status ?? '') === 'Draft') {
                return '';
            }
            $risId = $risId ?? (int) ($risOrNumber->ris_id ?? 0);
            $createdAt = $createdAt ?? ($risOrNumber->ris_created_at ?? null);
        }

        $id = (int) ($risId ?? 0);
        $ym = now()->format('Ym');
        if (! empty($createdAt)) {
            try {
                $ym = \Carbon\Carbon::parse($createdAt)->format('Ym');
            } catch (\Throwable $e) {
                // keep current month
            }
        }

        if ($id > 0) {
            return 'RIS-'.$ym.'-'.str_pad((string) min($id, 9999999), 7, '0', STR_PAD_LEFT);
        }

        return '';
    }

    public static function isValidFormNumber(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /** Next RIS No. for the current year-month (submitted / non-draft only). */
    public static function nextFormNumber(): string
    {
        $ym = now()->format('Ym');
        $prefix = 'RIS-'.$ym.'-';
        $max = 0;

        if (Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_form_number')) {
            $query = DB::table('requisition_issue_slip_table')
                ->whereNotNull('ris_form_number')
                ->where('ris_form_number', 'like', $prefix.'%')
                ->where(function ($q) {
                    $q->whereNull('ris_status')
                        ->orWhere('ris_status', '!=', 'Draft');
                });

            foreach ($query->pluck('ris_form_number') as $formNumber) {
                if (preg_match('/^RIS-'.preg_quote($ym, '/').'-(\d{7})$/', (string) $formNumber, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            }
        }

        $next = min($max + 1, 9999999);

        return $prefix.str_pad((string) $next, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Assign the next RIS No. Call inside a DB transaction with a locked row when possible.
     * Purchaser override is not supported (multi-purchaser safe).
     */
    public static function allocateFormNumberOnSubmit(?string $existing = null): string
    {
        if (self::isValidFormNumber($existing)) {
            return trim((string) $existing);
        }

        return self::nextFormNumber();
    }

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
                : 'Pending Admin Review';
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

        return self::trimDrawnSignatureDataUrl($value) ?? $value;
    }

    /**
     * Crop transparent padding so ink is centered when overlaid on a printed name.
     */
    public static function trimDrawnSignatureDataUrl(?string $value, int $padding = 10): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! self::isDrawnSignature($value)) {
            return null;
        }

        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,(.+)$#is', $value, $matches)) {
            return $value;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecreatetruecolor')) {
            return $value;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return $value;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return $value;
        }

        imagealphablending($source, false);
        imagesavealpha($source, true);

        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($source, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                // GD alpha: 0 opaque … 127 transparent. Keep nearly-opaque ink.
                if ($alpha < 120) {
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        if ($maxX < 0) {
            imagedestroy($source);

            return $value;
        }

        $minX = max(0, $minX - $padding);
        $minY = max(0, $minY - $padding);
        $maxX = min($width - 1, $maxX + $padding);
        $maxY = min($height - 1, $maxY + $padding);
        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;

        $cropped = imagecreatetruecolor($cropW, $cropH);
        if ($cropped === false) {
            imagedestroy($source);

            return $value;
        }

        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
        imagecopy($cropped, $source, 0, 0, $minX, $minY, $cropW, $cropH);

        ob_start();
        imagepng($cropped);
        $png = ob_get_clean();

        imagedestroy($source);
        imagedestroy($cropped);

        if (! is_string($png) || $png === '') {
            return $value;
        }

        return 'data:image/png;base64,'.base64_encode($png);
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
        $lines = collect($source->line_items ?? []);
        if ($lines->isNotEmpty()) {
            return \App\Support\ReplacementRequestBasket::summaryLabel($lines, $source);
        }

        $name = trim((string) ($source->equipment_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $unlisted = trim((string) ($source->report_unlisted_equipment_name ?? ''));

        return $unlisted !== '' ? $unlisted : 'Unspecified equipment';
    }

    public static function replacementPurpose(object $source): string
    {
        $lines = collect($source->line_items ?? []);
        if ($lines->isEmpty() && isset($source->procurement_request_id)) {
            $lines = \App\Support\ReplacementRequestBasket::itemsForRequest((int) $source->procurement_request_id);
        }

        if ($lines->isNotEmpty()) {
            $parts = $lines->map(function ($line) {
                $name = \App\Support\ReplacementRequestBasket::displayName($line);
                $room = trim((string) ($line->room_name ?? ''));

                return $room !== '' ? $name.' in '.$room : $name;
            })->filter()->unique()->values();

            $purpose = 'Replacement of '.$parts->implode(', ');
            $reason = trim((string) ($source->report_replacement_notes ?? ''));
            if ($reason === '') {
                $reason = trim((string) ($lines->first()->replacement_notes ?? $source->report_problem_description ?? ''));
            }
            if ($reason !== '') {
                $purpose .= '. Reason: '.$reason;
            }

            return $purpose;
        }

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
