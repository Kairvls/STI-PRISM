<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingSigner
{
    public static function currentUserName(): string
    {
        $user = Auth::user();
        if (!$user) {
            return '';
        }

        foreach (['user_full_name', 'user_username'] as $field) {
            $value = trim((string) ($user->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public static function fromUserId(?int $userId): string
    {
        $userId = (int) ($userId ?? 0);
        if ($userId <= 0 || !Schema::hasTable('users_table')) {
            return '';
        }

        try {
            $row = DB::table('users_table')->where('user_id', $userId)->first([
                'user_full_name',
                'user_username',
            ]);
        } catch (\Throwable $e) {
            return '';
        }

        if (!$row) {
            return '';
        }

        foreach (['user_full_name', 'user_username'] as $field) {
            $value = trim((string) ($row->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public static function fromApprovalLog(string $referenceType, int $referenceId): string
    {
        if ($referenceId <= 0 || !Schema::hasTable('approval_logs_table') || !Schema::hasTable('users_table')) {
            return '';
        }

        try {
            $query = DB::table('approval_logs_table')
                ->leftJoin('users_table', 'approval_logs_table.approval_log_approved_by', '=', 'users_table.user_id')
                ->where('approval_logs_table.approval_log_reference_type', $referenceType)
                ->where('approval_logs_table.approval_log_reference_id', $referenceId)
                ->where('approval_logs_table.approval_log_level', 'Accounting')
                ->orderByDesc('approval_logs_table.approval_log_approved_at')
                ->select([
                    'users_table.user_full_name',
                    'users_table.user_username',
                    'approval_logs_table.approval_log_approved_by',
                ]);

            // Prefer an Approved action when present.
            $row = (clone $query)
                ->where('approval_logs_table.approval_log_approval_status', 'Approved')
                ->first();

            if (!$row) {
                $row = $query->first();
            }
        } catch (\Throwable $e) {
            return '';
        }

        if (!$row) {
            return '';
        }

        foreach (['user_full_name', 'user_username'] as $field) {
            $value = trim((string) ($row->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return self::fromUserId((int) ($row->approval_log_approved_by ?? 0));
    }

    /**
     * Printed name under a drawn accounting signature on ATP.
     * Never uses the currently logged-in user (Purchaser would incorrectly appear).
     */
    public static function forAtp(?object $atp): string
    {
        if (!$atp) {
            return '';
        }

        $sig = trim((string) ($atp->authority_purchase_authorized_by_signature ?? ''));
        if ($sig !== '' && !RisWorkflow::isDrawnSignature($sig)) {
            return $sig;
        }

        if (
            Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by_name')
        ) {
            $stored = trim((string) ($atp->authority_purchase_authorized_by_name ?? ''));
            if ($stored !== '' && !RisWorkflow::isDrawnSignature($stored)) {
                return $stored;
            }
        }

        if (
            Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_authorized_by')
        ) {
            $fromUser = self::fromUserId((int) ($atp->authority_purchase_authorized_by ?? 0));
            if ($fromUser !== '') {
                return $fromUser;
            }
        }

        return self::fromApprovalLog('ATP', (int) ($atp->authority_purchase_id ?? 0));
    }

    /**
     * Printed name under a drawn accounting signature on RFC.
     */
    public static function forRfc(?object $rfc): string
    {
        if (!$rfc) {
            return '';
        }

        $adminField = trim((string) ($rfc->request_check_approved_by_admin ?? ''));
        if ($adminField !== '' && !RisWorkflow::isDrawnSignature($adminField)) {
            return $adminField;
        }

        $sig = trim((string) ($rfc->request_check_approved_by_signature ?? ''));
        if ($sig !== '' && !RisWorkflow::isDrawnSignature($sig)) {
            return $sig;
        }

        $fromUser = self::fromUserId((int) ($rfc->request_check_approved_by_user_id ?? $rfc->request_check_accounting_verified_by ?? 0));
        if ($fromUser !== '') {
            return $fromUser;
        }

        return self::fromApprovalLog('RFC', (int) ($rfc->request_check_id ?? 0));
    }

    /**
     * Printed name under a drawn accounting signature on Liquidation.
     */
    public static function forLiq(?object $liq): string
    {
        if (!$liq) {
            return '';
        }

        $sig = trim((string) ($liq->liquidation_report_checked_by_accountant ?? ''));
        if ($sig !== '' && !RisWorkflow::isDrawnSignature($sig)) {
            return $sig;
        }

        if (Schema::hasColumn('liquidation_reports_table', 'liquidation_report_checked_by_user_id')) {
            $fromUser = self::fromUserId((int) ($liq->liquidation_report_checked_by_user_id ?? 0));
            if ($fromUser !== '') {
                return $fromUser;
            }
        }

        return self::fromApprovalLog('LIQ', (int) ($liq->liquidation_report_id ?? 0));
    }
}
