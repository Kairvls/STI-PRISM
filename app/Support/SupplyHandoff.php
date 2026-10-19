<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purchaser ↔ School Supply / Material Officer handoffs for RIS and ATP.
 */
class SupplyHandoff
{
    public static function risHandoffColumnsReady(): bool
    {
        return Schema::hasTable('requisition_issue_slip_table')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_sent_to_supply_at')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_sent_to_supply_by')
            && Schema::hasColumn('requisition_issue_slip_table', 'ris_supply_officer_user_id');
    }

    public static function atpReleaseColumnsReady(): bool
    {
        return Schema::hasTable('authority_to_purchase_table')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_released_to_purchaser_at')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_released_to_purchaser_by')
            && Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_released_to_user_id');
    }

    public static function risIsSentToSupply(?object $ris): bool
    {
        if (! $ris || ! self::risHandoffColumnsReady()) {
            return false;
        }

        return ! blank($ris->ris_sent_to_supply_at ?? null);
    }

    public static function atpIsReleasedToPurchaser(?object $atp): bool
    {
        if (! $atp || ! self::atpReleaseColumnsReady()) {
            return false;
        }

        return ! blank($atp->authority_purchase_released_to_purchaser_at ?? null);
    }

    /**
     * Approved RIS owned by the purchaser that can still be sent to Supply.
     * Hidden once already sent, or once every supplier group already has an ATP.
     */
    public static function purchaserCanSendRis(?object $ris): bool
    {
        if (! $ris || ! self::risHandoffColumnsReady()) {
            return false;
        }

        if (! RisWorkflow::isEligibleForAtp($ris)) {
            return false;
        }

        if (self::risIsSentToSupply($ris)) {
            return false;
        }

        // Legacy / already-covered RIS: nothing left for Supply to create.
        if (self::risAlreadyHasFullAtpCoverage($ris)) {
            return false;
        }

        return true;
    }

    /**
     * True when the RIS has supplier groups and none still need an ATP.
     */
    public static function risAlreadyHasFullAtpCoverage(?object $ris): bool
    {
        $risId = (int) ($ris->ris_id ?? 0);
        if ($risId <= 0 || ! Schema::hasTable('authority_to_purchase_table')) {
            return false;
        }

        $progress = AtpRisSuppliers::progress([$risId])[$risId] ?? null;

        return $progress !== null && (int) ($progress['open'] ?? 0) === 0;
    }

    /**
     * Scope: RIS already passed to Supply Officer and still usable for ATP.
     */
    public static function scopeIncomingRisForSupply($query, ?int $officerUserId = null)
    {
        if (! self::risHandoffColumnsReady()) {
            return $query->whereRaw('1 = 0');
        }

        $officerUserId = $officerUserId ?? (int) Auth::id();

        $query->whereNotNull('requisition_issue_slip_table.ris_sent_to_supply_at');

        $query->where(function ($q) use ($officerUserId) {
            $q->whereNull('requisition_issue_slip_table.ris_supply_officer_user_id')
                ->orWhere('requisition_issue_slip_table.ris_supply_officer_user_id', $officerUserId);
        });

        RisWorkflow::applyEligibleForAtpScope($query);

        // Hide RIS that already have a live whole-RIS ATP (Create ATP would find nothing).
        // Per-supplier splits still appear until every supplier group has an ATP.
        if (Schema::hasTable('authority_to_purchase_table')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('authority_to_purchase_table')
                    ->whereColumn(
                        'authority_to_purchase_table.authority_purchase_ris_id',
                        'requisition_issue_slip_table.ris_id'
                    )
                    ->where(function ($q) {
                        $q->whereNull('authority_purchase_is_archived')
                            ->orWhere('authority_purchase_is_archived', 0);
                    })
                    ->where('authority_purchase_status', '!=', 'Rejected')
                    ->when(AtpRisSuppliers::hasSplitColumn(), function ($q) {
                        $q->where('authority_purchase_ris_supplier_split', 0);
                    });
            });
        }

        return $query;
    }

    public static function sendRisToSupply(object $ris, ?int $officerUserId, int $fromUserId): void
    {
        if (! self::risHandoffColumnsReady()) {
            throw new \RuntimeException('Supply handoff columns are not ready. Run migrations.');
        }

        if (! self::purchaserCanSendRis($ris)) {
            if (self::risAlreadyHasFullAtpCoverage($ris)) {
                throw new \RuntimeException('This RIS already has an Authority to Purchase, so it cannot be sent to the School Supply / Material Officer.');
            }
            if (self::risIsSentToSupply($ris)) {
                throw new \RuntimeException('This RIS was already sent to the School Supply / Material Officer.');
            }

            throw new \RuntimeException('This RIS cannot be sent to the School Supply / Material Officer.');
        }

        $risId = (int) $ris->ris_id;

        DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->update([
            'ris_sent_to_supply_at' => now(),
            'ris_sent_to_supply_by' => $fromUserId,
            'ris_supply_officer_user_id' => $officerUserId ?: null,
            'ris_updated_at' => now(),
        ]);

        $form = RisWorkflow::formNumber($ris);
        $url = '/supply-officer/incoming-ris';
        $title = 'Approved RIS ready for ATP';
        $message = $form.' was sent by the Purchaser. Create an Authority to Purchase based on this Canvas + RIS.';

        if ($officerUserId) {
            WorkflowNotifier::toUser(
                $officerUserId,
                WorkflowNotifier::ROLE_SUPPLY_OFFICER,
                $title,
                $message,
                'ris_sent_to_supply',
                'RIS',
                $risId,
                $url
            );
        } else {
            WorkflowNotifier::toRole(
                WorkflowNotifier::ROLE_SUPPLY_OFFICER,
                $title,
                $message,
                'ris_sent_to_supply',
                'RIS',
                $risId,
                $url
            );
        }
    }

    public static function defaultPurchaserForAtp(object $atp): ?int
    {
        $risId = (int) ($atp->authority_purchase_ris_id ?? 0);
        if ($risId <= 0 || ! Schema::hasTable('requisition_issue_slip_table')) {
            return null;
        }

        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        if (! $ris) {
            return null;
        }

        foreach (['ris_created_by', 'ris_submitted_by', 'ris_sent_to_supply_by'] as $field) {
            if (isset($ris->{$field}) && (int) $ris->{$field} > 0) {
                return (int) $ris->{$field};
            }
        }

        return null;
    }

    public static function releaseAtpToPurchaser(object $atp, int $purchaserUserId, int $fromUserId): void
    {
        if (! self::atpReleaseColumnsReady()) {
            throw new \RuntimeException('ATP release columns are not ready. Run migrations.');
        }

        if (($atp->authority_purchase_status ?? null) !== 'Approved') {
            throw new \RuntimeException('Only approved ATPs can be released to the Purchaser.');
        }

        if (self::atpIsReleasedToPurchaser($atp)) {
            throw new \RuntimeException('This ATP was already released to the Purchaser.');
        }

        $atpId = (int) $atp->authority_purchase_id;

        DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->update([
            'authority_purchase_released_to_purchaser_at' => now(),
            'authority_purchase_released_to_purchaser_by' => $fromUserId,
            'authority_purchase_released_to_user_id' => $purchaserUserId,
            'authority_purchase_updated_at' => now(),
        ]);

        $form = $atp->authority_purchase_form_number ?: ('ATP #'.$atpId);

        WorkflowNotifier::toUser(
            $purchaserUserId,
            WorkflowNotifier::ROLE_PURCHASER,
            'ATP released for Request for Check',
            $form.' (with approved Canvas + RIS) was released by the School Supply / Material Officer. You may create a Request for Check.',
            'atp_released_to_purchaser',
            'ATP',
            $atpId,
            '/purchaser/request-check?selected_atp='.$atpId
        );
    }

    /**
     * Approved ATPs released to the current purchaser (for RFC linking).
     */
    public static function scopeReleasedAtpsForPurchaser($query, ?int $purchaserUserId = null)
    {
        $purchaserUserId = $purchaserUserId ?? (int) Auth::id();

        $query->where('authority_to_purchase_table.authority_purchase_status', 'Approved');

        if (! self::atpReleaseColumnsReady()) {
            return $query;
        }

        return $query
            ->whereNotNull('authority_to_purchase_table.authority_purchase_released_to_purchaser_at')
            ->where(function ($q) use ($purchaserUserId) {
                $q->where('authority_to_purchase_table.authority_purchase_released_to_user_id', $purchaserUserId)
                    ->orWhereNull('authority_to_purchase_table.authority_purchase_released_to_user_id');
            });
    }
}
