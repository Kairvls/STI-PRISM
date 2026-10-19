<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purchaser → Accounting package handoff (approved Canvas+RIS+ATP+RFC for DV).
 */
class AccountingHandoff
{
    public static function rfcHandoffColumnsReady(): bool
    {
        return Schema::hasTable('request_check_table')
            && Schema::hasColumn('request_check_table', 'request_check_sent_to_accounting_at')
            && Schema::hasColumn('request_check_table', 'request_check_sent_to_accounting_by');
    }

    public static function rfcIsSentToAccounting(?object $rfc): bool
    {
        if (! $rfc || ! self::rfcHandoffColumnsReady()) {
            return false;
        }

        return ! blank($rfc->request_check_sent_to_accounting_at ?? null);
    }

    public static function purchaserCanSendRfc(?object $rfc): bool
    {
        if (! $rfc || ! self::rfcHandoffColumnsReady()) {
            return false;
        }

        if (($rfc->request_check_status ?? null) !== 'Approved') {
            return false;
        }

        if ((int) ($rfc->request_check_is_archived ?? 0) === 1) {
            return false;
        }

        return ! self::rfcIsSentToAccounting($rfc);
    }

    public static function sendRfcToAccounting(object $rfc, int $fromUserId): void
    {
        if (! self::rfcHandoffColumnsReady()) {
            throw new \RuntimeException('Accounting package handoff is not available yet. Please run migrations.');
        }

        if (! self::purchaserCanSendRfc($rfc)) {
            throw new \RuntimeException('Only an approved Request for Check that has not been sent yet can go to Accounting.');
        }

        $rfcId = (int) $rfc->request_check_id;

        DB::table('request_check_table')
            ->where('request_check_id', $rfcId)
            ->update([
                'request_check_sent_to_accounting_at' => now(),
                'request_check_sent_to_accounting_by' => $fromUserId,
                'request_check_updated_at' => now(),
            ]);

        $label = $rfc->request_check_form_number ?: ('RFC #'.$rfcId);

        WorkflowNotifier::toRole(
            WorkflowNotifier::ROLE_ACCOUNTING,
            'Package ready for DV',
            $label.' (Canvas + RIS + ATP + RFC) was submitted by the Purchaser. Create a Disbursement Voucher.',
            'rfc_sent_to_accounting',
            'RFC',
            $rfcId,
            '/accounting/disbursement-vouchers?rfc='.$rfcId,
            'purchaser'
        );
    }

    /**
     * Scope: approved RFCs sent to Accounting and still needing a DV (or draft DV).
     */
    public static function scopeIncomingPackagesForAccounting($query)
    {
        if (! self::rfcHandoffColumnsReady()) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('request_check_table.request_check_status', 'Approved')
            ->whereNotNull('request_check_table.request_check_sent_to_accounting_at')
            ->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });

        return $query;
    }

    public static function dvForRfc(int $rfcId): ?object
    {
        if (! Schema::hasTable('disbursement_vouchers_table') || $rfcId < 1) {
            return null;
        }

        return DB::table('disbursement_vouchers_table')
            ->where('dv_request_check_id', $rfcId)
            ->where(function ($q) {
                $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
            })
            ->orderByDesc('dv_id')
            ->first();
    }
}
