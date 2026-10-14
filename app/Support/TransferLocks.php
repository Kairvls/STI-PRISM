<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equipment that must stay where it is: out on loan, or being repaired.
 */
class TransferLocks
{
    public const BORROWED = 'Borrowed';

    public const UNDER_MAINTENANCE = 'Under Maintenance';

    /**
     * Open loans keyed by equipment id (earliest due first).
     *
     * @param  array<int, int|string>  $equipmentIds
     * @return Collection<int, object{borrower: ?string, due: ?string, overdue: bool, quantity: int, loans: int}>
     */
    public static function openLoans(array $equipmentIds): Collection
    {
        $ids = array_values(array_filter(array_map('intval', $equipmentIds)));
        if ($ids === [] || ! Schema::hasTable('borrowing_records_table')) {
            return collect();
        }

        return DB::table('borrowing_records_table')
            ->whereIn('borrowing_equipment_id', $ids)
            ->whereIn('borrowing_status', ['Borrowed', 'Overdue'])
            ->orderBy('borrowing_expected_return_date')
            ->get([
                'borrowing_equipment_id',
                'borrowing_borrower_name',
                'borrowing_expected_return_date',
                'borrowing_quantity',
                'borrowing_status',
            ])
            ->groupBy(fn ($row) => (int) $row->borrowing_equipment_id)
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $due = $first->borrowing_expected_return_date;

                return (object) [
                    'borrower' => $first->borrowing_borrower_name,
                    'due' => $due,
                    'overdue' => $first->borrowing_status === 'Overdue'
                        || ($due && now()->startOfDay()->gt(\Illuminate\Support\Carbon::parse($due))),
                    'quantity' => (int) $rows->sum('borrowing_quantity'),
                    'loans' => $rows->count(),
                ];
            });
    }

    /**
     * Why this item cannot be transferred, or null when it can.
     *
     * @param  Collection<int, object>|null  $loans  result of openLoans() when checking many items
     */
    public static function reason(object $equipment, ?Collection $loans = null): ?string
    {
        $id = (int) ($equipment->equipment_id ?? 0);
        $name = trim((string) ($equipment->equipment_name ?? '')) ?: 'This equipment';
        $status = (string) ($equipment->equipment_inventory_status ?? '');
        $loan = ($loans ?? self::openLoans([$id]))->get($id);

        if ($loan || $status === self::BORROWED) {
            $to = $loan && filled($loan->borrower) ? ' to '.$loan->borrower : '';

            return $name.' is on loan'.$to.'. Record the return before transferring it.';
        }

        if ($status === self::UNDER_MAINTENANCE) {
            return $name.' is under maintenance. Finish the maintenance before transferring it.';
        }

        if (isset(DisposalRequests::pendingEquipmentIds()[$id])) {
            return $name.' has a disposal waiting for the Administrator\'s approval.';
        }

        return null;
    }
}
