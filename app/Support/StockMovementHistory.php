<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Transfers that touched a storage room: stock moved out to a room, returned
 * to storage, or shifted between stockrooms. Read-only record for the
 * Inventory page; the rows themselves are written by every transfer flow.
 */
class StockMovementHistory
{
    public const DIRECTIONS = [
        'out' => 'Moved out',
        'in' => 'Returned to storage',
        'between' => 'Between storage rooms',
    ];

    public static function available(): bool
    {
        return Schema::hasTable('equipment_transfer_history_table');
    }

    public static function paginate(Request $request, int $perPage = 10): ?LengthAwarePaginator
    {
        if (! self::available()) {
            return null;
        }

        $query = self::base();

        $direction = (string) $request->query('history', '');
        if (isset(self::DIRECTIONS[$direction])) {
            self::whereDirection($query, $direction);
        }

        $search = trim((string) $request->query('history_search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('equipment_table.equipment_name', 'LIKE', $like)
                    ->orWhere('equipment_table.equipment_asset_tag', 'LIKE', $like)
                    ->orWhere('equipment_table.equipment_serial_number', 'LIKE', $like)
                    ->orWhere('from_room.room_name', 'LIKE', $like)
                    ->orWhere('to_room.room_name', 'LIKE', $like)
                    ->orWhere('mover.user_full_name', 'LIKE', $like)
                    ->orWhere('transfer.remarks', 'LIKE', $like);
            });
        }

        $paginator = $query
            ->leftJoin('equipment_categories_table', 'equipment_categories_table.equipment_category_id', '=', 'equipment_table.equipment_category_id')
            ->select(
                'equipment_table.*',
                'equipment_categories_table.equipment_category_name',
                'current_room.room_name',
                'current_room.room_type',
                'transfer.transfer_id',
                'transfer.equipment_id',
                'transfer.remarks',
                'transfer.created_at',
                'from_room.room_name as from_room_name',
                'from_room.room_type as from_room_type',
                'to_room.room_name as to_room_name',
                'to_room.room_type as to_room_type',
                'current_room.room_name as current_room_name',
                'mover.user_full_name as moved_by_name'
            )
            ->orderByDesc('transfer.created_at')
            ->orderByDesc('transfer.transfer_id')
            ->paginate($perPage, ['*'], 'history_page')
            ->withQueryString()
            ->fragment('movement-history');

        $paginator->getCollection()->transform(function ($row) {
            $row->direction = self::directionOf($row->from_room_type, $row->to_room_type);
            $row->direction_label = self::DIRECTIONS[$row->direction];

            return $row;
        });

        return $paginator;
    }

    /**
     * @return array{all: int, out: int, in: int, between: int}
     */
    public static function counts(): array
    {
        if (! self::available()) {
            return ['all' => 0, 'out' => 0, 'in' => 0, 'between' => 0];
        }

        $counts = ['all' => self::base()->count()];
        foreach (array_keys(self::DIRECTIONS) as $direction) {
            $query = self::base();
            self::whereDirection($query, $direction);
            $counts[$direction] = $query->count();
        }

        return $counts;
    }

    private static function base(): Builder
    {
        $storage = RoomCategories::STORAGE_TYPE;

        return DB::table('equipment_transfer_history_table as transfer')
            ->join('equipment_table', 'equipment_table.equipment_id', '=', 'transfer.equipment_id')
            ->leftJoin('rooms_table as from_room', 'from_room.room_id', '=', 'transfer.from_room_id')
            ->leftJoin('rooms_table as to_room', 'to_room.room_id', '=', 'transfer.to_room_id')
            ->leftJoin('rooms_table as current_room', 'current_room.room_id', '=', 'equipment_table.equipment_room_id')
            ->leftJoin('users_table as mover', 'mover.user_id', '=', 'transfer.transferred_by')
            ->where(function ($q) use ($storage) {
                $q->where('from_room.room_type', $storage)
                    ->orWhere('to_room.room_type', $storage);
            });
    }

    private static function whereDirection(Builder $query, string $direction): void
    {
        $storage = RoomCategories::STORAGE_TYPE;
        $notStorage = fn (string $alias) => fn ($q) => $q->whereNull("$alias.room_type")->orWhere("$alias.room_type", '!=', $storage);

        match ($direction) {
            'out' => $query->where('from_room.room_type', $storage)->where($notStorage('to_room')),
            'in' => $query->where('to_room.room_type', $storage)->where($notStorage('from_room')),
            'between' => $query->where('from_room.room_type', $storage)->where('to_room.room_type', $storage),
        };
    }

    private static function directionOf(?string $fromType, ?string $toType): string
    {
        $fromStorage = RoomCategories::isStorageType($fromType);
        $toStorage = RoomCategories::isStorageType($toType);

        if ($fromStorage && $toStorage) {
            return 'between';
        }

        return $fromStorage ? 'out' : 'in';
    }
}
