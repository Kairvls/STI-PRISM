<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A funding request (RFC / Cash Advance) can cover one ATP or several ATPs of a
 * Purchase Order. request_check_authority_purchase_id keeps the first ATP for
 * older single-ATP code paths; request_check_atps_table holds the full list.
 */
class RfcAtpLinks
{
    public static function tableExists(): bool
    {
        return Schema::hasTable('request_check_atps_table');
    }

    /**
     * @return array<int, int>
     */
    public static function atpIdsFor(int $rfcId, ?int $primaryAtpId = null): array
    {
        return self::atpIdsForMany([$rfcId => $primaryAtpId])[$rfcId] ?? [];
    }

    /**
     * @param  array<int, int|null>  $primaryByRfc  rfcId => request_check_authority_purchase_id
     * @return array<int, array<int, int>>
     */
    public static function atpIdsForMany(array $primaryByRfc): array
    {
        $result = [];
        $rfcIds = array_values(array_filter(array_map('intval', array_keys($primaryByRfc))));
        if ($rfcIds === []) {
            return $result;
        }

        $links = self::tableExists()
            ? DB::table('request_check_atps_table')
                ->whereIn('request_check_id', $rfcIds)
                ->orderBy('request_check_atp_id')
                ->get(['request_check_id', 'authority_purchase_id'])
                ->groupBy('request_check_id')
            : collect();

        foreach ($primaryByRfc as $rfcId => $primary) {
            $rfcId = (int) $rfcId;
            $ids = collect($links->get($rfcId, []))
                ->pluck('authority_purchase_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($ids === [] && (int) $primary > 0) {
                $ids = [(int) $primary];
            }
            $result[$rfcId] = array_values(array_unique($ids));
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $atpIds
     */
    public static function sync(int $rfcId, array $atpIds): void
    {
        if (! self::tableExists() || $rfcId < 1) {
            return;
        }

        $atpIds = array_values(array_unique(array_filter(array_map('intval', $atpIds))));

        DB::table('request_check_atps_table')->where('request_check_id', $rfcId)->delete();

        if ($atpIds === []) {
            return;
        }

        $now = now();
        DB::table('request_check_atps_table')->insert(array_map(fn ($atpId) => [
            'request_check_id' => $rfcId,
            'authority_purchase_id' => $atpId,
            'created_at' => $now,
        ], $atpIds));
    }

    /**
     * ATP ids (from the given list) already covered by an active funding request.
     *
     * @param  array<int, int>  $atpIds
     * @return array<int, int>
     */
    public static function fundedAtpIds(array $atpIds, ?int $ignoreRfcId = null): array
    {
        $atpIds = array_values(array_unique(array_filter(array_map('intval', $atpIds))));
        if ($atpIds === [] || ! Schema::hasTable('request_check_table')) {
            return [];
        }

        $direct = self::activeRfcQuery($ignoreRfcId)
            ->whereIn('request_check_table.request_check_authority_purchase_id', $atpIds)
            ->pluck('request_check_table.request_check_authority_purchase_id')
            ->all();

        $viaLinks = self::tableExists()
            ? self::activeRfcQuery($ignoreRfcId)
                ->join('request_check_atps_table', 'request_check_atps_table.request_check_id', '=', 'request_check_table.request_check_id')
                ->whereIn('request_check_atps_table.authority_purchase_id', $atpIds)
                ->pluck('request_check_atps_table.authority_purchase_id')
                ->all()
            : [];

        return array_values(array_unique(array_map('intval', array_merge($direct, $viaLinks))));
    }

    /**
     * Latest funding request (any status) that covers the ATP.
     */
    public static function latestRfcForAtp(int $atpId): ?object
    {
        if ($atpId < 1 || ! Schema::hasTable('request_check_table')) {
            return null;
        }

        return DB::table('request_check_table')
            ->where(function ($q) use ($atpId) {
                $q->where('request_check_authority_purchase_id', $atpId);
                if (self::tableExists()) {
                    $q->orWhereIn('request_check_id', function ($sub) use ($atpId) {
                        $sub->select('request_check_id')
                            ->from('request_check_atps_table')
                            ->where('authority_purchase_id', $atpId);
                    });
                }
            })
            ->orderByDesc('request_check_id')
            ->first();
    }

    /**
     * Constrain a request_check_table query to requests covering the ATP.
     */
    public static function whereCoversAtp($query, int $atpId, string $table = 'request_check_table'): void
    {
        $query->where(function ($q) use ($atpId, $table) {
            $q->where($table.'.request_check_authority_purchase_id', $atpId);
            if (self::tableExists()) {
                $q->orWhereIn($table.'.request_check_id', function ($sub) use ($atpId) {
                    $sub->select('request_check_id')
                        ->from('request_check_atps_table')
                        ->where('authority_purchase_id', $atpId);
                });
            }
        });
    }

    private static function activeRfcQuery(?int $ignoreRfcId)
    {
        $query = DB::table('request_check_table')
            ->where('request_check_table.request_check_status', '!=', 'Rejected');

        if (Schema::hasColumn('request_check_table', 'request_check_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });
        }

        if ($ignoreRfcId) {
            $query->where('request_check_table.request_check_id', '!=', $ignoreRfcId);
        }

        return $query;
    }
}
