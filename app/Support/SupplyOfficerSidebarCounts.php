<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unseen counts for the Supply Officer sidebar.
 * A record drops off once opened, and comes back if its status changes.
 */
class SupplyOfficerSidebarCounts
{
    public const KIND_RIS = 'so_ris';

    public const KIND_ATP = 'so_atp';

    /** @var array<string, int>|null */
    private static ?array $cached = null;

    /**
     * @return array{ris: int, atp: int}
     */
    public static function counts(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        return self::$cached = [
            'ris' => self::countRis(),
            'atp' => self::countAtp(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function incomingRis(): array
    {
        $query = self::risQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'requisition_issue_slip_table.ris_id', 'requisition_issue_slip_table.ris_status', 'ris_id', 'ris_status');
    }

    /**
     * Draft ATPs + approved ATPs waiting to release to Purchaser.
     *
     * @return array<int, string>
     */
    public static function actionableAtp(): array
    {
        $query = self::atpQuery();
        if ($query === null) {
            return [];
        }

        return self::pluck($query, 'authority_purchase_id', 'authority_purchase_status');
    }

    private static function countRis(): int
    {
        $query = self::risQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen(
            $query,
            self::KIND_RIS,
            'requisition_issue_slip_table.ris_id',
            'requisition_issue_slip_table.ris_status'
        );

        return (int) $query->count();
    }

    private static function countAtp(): int
    {
        $query = self::atpQuery();
        if ($query === null) {
            return 0;
        }
        PurchaserMenuSeen::excludeSeen(
            $query,
            self::KIND_ATP,
            'authority_to_purchase_table.authority_purchase_id',
            'authority_to_purchase_table.authority_purchase_status'
        );

        return (int) $query->count();
    }

    private static function risQuery()
    {
        if (! Schema::hasTable('requisition_issue_slip_table') || ! SupplyHandoff::risHandoffColumnsReady()) {
            return null;
        }

        $query = DB::table('requisition_issue_slip_table');
        SupplyHandoff::scopeIncomingRisForSupply($query);

        return $query;
    }

    private static function atpQuery()
    {
        if (! Schema::hasTable('authority_to_purchase_table')) {
            return null;
        }

        $query = DB::table('authority_to_purchase_table')
            ->where(function ($q) {
                $q->whereNull('authority_purchase_is_archived')
                    ->orWhere('authority_purchase_is_archived', 0);
            });

        if (Schema::hasColumn('authority_to_purchase_table', 'authority_purchase_created_by')) {
            $query->where('authority_purchase_created_by', Auth::id());
        }

        $query->where(function ($q) {
            $q->where(function ($draft) {
                $draft->where('authority_purchase_status', 'Pending')
                    ->whereNull('authority_purchase_submitted_at');
            })->orWhere(function ($release) {
                $release->where('authority_purchase_status', 'Approved');
                if (SupplyHandoff::atpReleaseColumnsReady()) {
                    $release->whereNull('authority_purchase_released_to_purchaser_at');
                }
            });
        });

        return $query;
    }

    /**
     * @return array<int, string>
     */
    private static function pluck($query, string $idColumn, string $statusColumn, ?string $idAlias = null, ?string $statusAlias = null): array
    {
        $idKey = $idAlias ?? last(explode('.', $idColumn));
        $statusKey = $statusAlias ?? last(explode('.', $statusColumn));
        $rows = [];
        foreach ($query->get([$idColumn, $statusColumn]) as $row) {
            $rows[(int) $row->{$idKey}] = (string) ($row->{$statusKey} ?? '');
        }

        return $rows;
    }
}
