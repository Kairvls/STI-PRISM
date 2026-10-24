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

    /** Item rows allowed on one RIS (also caps replacement-request baskets). */
    public const MAX_ITEMS = 50;

    /** RIS No. pattern: RIS-YYYYMMDD-0000000 (also accepts legacy RIS-YYYYMM-0000000) */
    public const FORM_NUMBER_REGEX = '/^RIS-\d{6,8}-\d{7}$/';

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
     * Must be applied before any other ordering so it becomes the primary sort key.
     */
    public static function orderUrgentFirst($query, string $table = 'requisition_issue_slip_table')
    {
        if (Schema::hasColumn($table, 'ris_urgency')) {
            $query->orderByRaw(
                "CASE WHEN TRIM({$table}.ris_urgency) = ? THEN 0 ELSE 1 END",
                [self::URGENCY_URGENT]
            );
        }

        return $query;
    }

    /**
     * Display / stored RIS "No." value (RIS-YYYYMMDD-0000000).
     * Prefers the saved form number; otherwise builds a display fallback from id + date.
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
        $ymd = now()->format('Ymd');
        if (! empty($createdAt)) {
            try {
                $ymd = \Carbon\Carbon::parse($createdAt)->format('Ymd');
            } catch (\Throwable $e) {
                // keep today
            }
        }

        if ($id > 0) {
            return 'RIS-'.$ymd.'-'.str_pad((string) min($id, 9999999), 7, '0', STR_PAD_LEFT);
        }

        return '';
    }

    public static function isValidFormNumber(?string $value): bool
    {
        return is_string($value) && (bool) preg_match(self::FORM_NUMBER_REGEX, trim($value));
    }

    /**
     * Next RIS No. — YYYYMMDD is today; sequence continues across days
     * (submitted / non-draft only).
     */
    public static function nextFormNumber(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'RIS-'.$ymd.'-';
        $max = 0;

        if (Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_form_number')) {
            $query = DB::table('requisition_issue_slip_table')
                ->whereNotNull('ris_form_number')
                ->where('ris_form_number', 'like', 'RIS-%')
                ->where(function ($q) {
                    $q->whereNull('ris_status')
                        ->orWhere('ris_status', '!=', 'Draft');
                });

            foreach ($query->pluck('ris_form_number') as $formNumber) {
                if (preg_match('/^RIS-\d{6,8}-(\d{7})$/', (string) $formNumber, $matches)) {
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

    /**
     * Role title while one person holds the job. Their name when two or more do.
     */
    public static function roleHolder(object $ris, string $role, string $roleLabel): string
    {
        $name = $role === WorkflowNotifier::ROLE_PRESIDENT
            ? self::presidentName($ris)
            : self::schoolAdminName($ris);

        return RoleHolderLabel::who($role, $name, $roleLabel);
    }

    /** @var array<int, string|null> */
    private static array $schoolAdminNames = [];

    /** @var array<int, string|null> */
    private static array $presidentNames = [];

    private static function schoolAdminName(object $ris): ?string
    {
        $preset = trim((string) ($ris->assigned_reviewer_name ?? ''));
        if ($preset !== '') {
            return $preset;
        }

        if (! RoleHolderLabel::multiple(WorkflowNotifier::ROLE_SCHOOL_ADMIN)) {
            return null;
        }

        $id = (int) ($ris->ris_id ?? 0);
        if ($id < 1 || ! Schema::hasTable('requisition_issue_slip_table') || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_assigned_reviewer_id')) {
            return null;
        }

        if (! array_key_exists($id, self::$schoolAdminNames)) {
            try {
                self::$schoolAdminNames[$id] = DB::table('requisition_issue_slip_table')
                    ->leftJoin('users_table', 'users_table.user_id', '=', 'requisition_issue_slip_table.ris_assigned_reviewer_id')
                    ->where('requisition_issue_slip_table.ris_id', $id)
                    ->value('users_table.user_full_name');
            } catch (\Throwable) {
                self::$schoolAdminNames[$id] = null;
            }
        }

        $name = trim((string) (self::$schoolAdminNames[$id] ?? ''));

        return $name !== '' ? $name : null;
    }

    private static function presidentName(object $ris): ?string
    {
        $preset = trim((string) ($ris->president_name ?? ''));
        if ($preset !== '') {
            return $preset;
        }

        if (! RoleHolderLabel::multiple(WorkflowNotifier::ROLE_PRESIDENT)) {
            return null;
        }

        $id = (int) ($ris->ris_id ?? 0);
        if ($id < 1 || ! Schema::hasTable('approval_logs_table')) {
            return null;
        }

        if (! array_key_exists($id, self::$presidentNames)) {
            try {
                self::$presidentNames[$id] = DB::table('approval_logs_table')
                    ->leftJoin('users_table', 'users_table.user_id', '=', 'approval_logs_table.approval_log_approved_by')
                    ->where('approval_logs_table.approval_log_reference_type', 'RIS')
                    ->where('approval_logs_table.approval_log_reference_id', $id)
                    ->where('approval_logs_table.approval_log_level', 'President')
                    ->where('approval_logs_table.approval_log_approval_status', 'Approved')
                    ->orderByDesc('approval_logs_table.approval_log_id')
                    ->value('users_table.user_full_name');
            } catch (\Throwable) {
                self::$presidentNames[$id] = null;
            }
        }

        $name = trim((string) (self::$presidentNames[$id] ?? ''));

        return $name !== '' ? $name : null;
    }

    public static function statusLabel(object $ris): string
    {
        $status = (string) ($ris->ris_status ?? '');

        $schoolAdmin = self::roleHolder($ris, WorkflowNotifier::ROLE_SCHOOL_ADMIN, 'the School Administrator');
        $president = self::roleHolder($ris, WorkflowNotifier::ROLE_PRESIDENT, 'the President');

        if (in_array($status, self::incomingStatuses(), true)) {
            return 'Under review by '.$schoolAdmin;
        }

        if ($status === self::ACCEPTED) {
            return 'Accepted — waiting on '.$schoolAdmin.' signing decision';
        }

        if ($status === self::DIRECTLY_APPROVED) {
            return 'Directly approved by '.$schoolAdmin;
        }

        if (self::isPresidentRejected($ris)) {
            return self::PRESIDENT_REJECTED;
        }

        if (self::isPresidentApproved($ris)) {
            return self::hasIssuedBy($ris)
                ? self::PRESIDENT_APPROVED
                : 'Approved by '.$president.' — waiting on '.$schoolAdmin.' release';
        }

        if ($status === self::FORWARDED || ($status === self::APPROVED_LEGACY && !self::presidentDigitalSignature($ris))) {
            return 'Forwarded to the President by '.$schoolAdmin;
        }

        if ($status === 'Minor Revision') {
            return 'Revision required — update and resubmit';
        }

        if ($status === 'Rejected') {
            return 'Rejected — amend and resubmit';
        }

        if ($status === 'Draft' || $status === '') {
            return 'Draft — not submitted yet';
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

        $status = (string) ($atp->authority_purchase_status ?? '');

        if ($status === 'Pending') {
            return filled($atp->authority_purchase_submitted_at ?? null) ? 'Under Review' : 'Draft';
        }

        if ($status === 'Directly Approved') {
            return 'Directly Approved';
        }

        return $status !== '' ? $status : '—';
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
        return self::requesterName($ris, '');
    }

    /** @var array<int, string>|null */
    private static ?array $userNames = null;

    /**
     * Full name of the account that owns the RIS (creator, then submitter).
     * Falls back to the name typed on the form for legacy rows without an owner.
     */
    public static function requesterName(?object $ris, string $fallback = '—'): string
    {
        if (!$ris) {
            return $fallback;
        }

        foreach (['ris_created_by', 'ris_submitted_by'] as $field) {
            $name = self::userFullName($ris->{$field} ?? null);
            if ($name !== '') {
                return $name;
            }
        }

        $typed = trim((string) ($ris->ris_requested_by_signature ?? ''));
        if ($typed !== '' && !self::isDrawnSignature($typed)) {
            return $typed;
        }

        return $fallback;
    }

    /**
     * @return array<int, int>
     */
    public static function userIdsMatchingName(string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        try {
            return DB::table('users_table')
                ->where('user_full_name', 'like', '%'.$search.'%')
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function userFullName($userId): string
    {
        $userId = (int) ($userId ?? 0);
        if ($userId <= 0) {
            return '';
        }

        if (self::$userNames === null) {
            try {
                self::$userNames = DB::table('users_table')
                    ->pluck('user_full_name', 'user_id')
                    ->map(fn ($name) => trim((string) $name))
                    ->all();
            } catch (\Throwable $e) {
                self::$userNames = [];
            }
        }

        if (!array_key_exists($userId, self::$userNames)) {
            try {
                self::$userNames[$userId] = trim((string) DB::table('users_table')->where('user_id', $userId)->value('user_full_name'));
            } catch (\Throwable $e) {
                self::$userNames[$userId] = '';
            }
        }

        return self::$userNames[$userId];
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

    /**
     * Save Issued quantities for an existing RIS.
     * Each named item needs an issued quantity of at least 1, and the total
     * issued for an item cannot be higher than what was requested.
     *
     * @param  array<int|string, mixed>  $posted  item id => issued quantity
     */
    public static function applyIssuedQuantities(int $risId, array $posted): ?string
    {
        $items = DB::table('requisition_issue_slip_items_table')
            ->where('ris_id', $risId)
            ->orderBy('ris_item_id')
            ->get();

        $groups = [];
        $updates = [];

        foreach ($items as $item) {
            $name = trim((string) ($item->ris_item_name_description ?? ''));
            if ($name === '') {
                continue;
            }

            $itemId = (int) $item->ris_item_id;
            $raw = $posted[$itemId] ?? $posted[(string) $itemId] ?? null;
            if ($raw === null || $raw === '' || ! is_numeric($raw) || (int) $raw != $raw || (int) $raw < 1) {
                return '"'.$name.'" needs an Issued quantity of at least 1.';
            }

            $issued = (int) $raw;
            if ($issued > 9999999) {
                return '"'.$name.'" Issued quantity can be up to 7 digits only.';
            }

            $key = mb_strtolower($name);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $name,
                    'asked' => 0,
                    'allocated' => 0,
                ];
            }
            $requested = (int) ($item->ris_quantity_requested ?? 0);
            if ($groups[$key]['asked'] < 1 && $requested > 0) {
                $groups[$key]['asked'] = $requested;
            }
            $groups[$key]['allocated'] += $issued;

            $updates[$itemId] = [
                'ris_quantity_issued' => $issued,
                'ris_total_amount' => round($issued * (float) ($item->ris_unit_cost ?? 0), 2),
            ];
        }

        if ($updates === []) {
            return 'This RIS has no items to issue.';
        }

        foreach ($groups as $group) {
            if ($group['asked'] < 1) {
                return '"'.$group['label'].'" needs a requested quantity before Issued can be saved.';
            }
            if ($group['allocated'] > $group['asked']) {
                return '"'.$group['label'].'" is over the requested amount ('
                    .$group['allocated'].' issued of '.$group['asked'].' requested).';
            }
        }

        foreach ($updates as $itemId => $payload) {
            DB::table('requisition_issue_slip_items_table')
                ->where('ris_item_id', $itemId)
                ->where('ris_id', $risId)
                ->update($payload);
        }

        return null;
    }
}
