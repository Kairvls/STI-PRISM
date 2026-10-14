<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equipment transfers asked for by non-administrators. Nothing moves until the Administrator approves.
 */
class TransferRequests
{
    public const PENDING = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';
    public const CANCELLED = 'Cancelled';

    public const STATUSES = [self::PENDING, self::APPROVED, self::REJECTED, self::CANCELLED];

    private const TABLE = 'equipment_transfer_requests_table';

    public static function tableReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable(self::TABLE);
    }

    public static function requiresApproval(): bool
    {
        return self::tableReady() && ! RoleAccess::isAdmin(Auth::user());
    }

    public static function create(string $kind, array $equipmentIds, int $roomId, array $destination, ?string $remarks): int
    {
        return (int) DB::table(self::TABLE)->insertGetId([
            'transfer_request_kind' => $kind,
            'transfer_request_equipment_ids' => json_encode(array_values(array_map('intval', $equipmentIds))),
            'transfer_request_room_id' => $roomId,
            'transfer_request_destination_type' => $destination['type'],
            'transfer_request_department_id' => $destination['type'] === 'department' ? $destination['department_id'] : null,
            'transfer_request_custodian_id' => $destination['custodian_id'],
            'transfer_request_remarks' => $remarks !== null && trim($remarks) !== '' ? trim($remarks) : null,
            'transfer_request_status' => self::PENDING,
            'transfer_request_requested_by' => Auth::id(),
            'transfer_request_requested_at' => now(),
        ]);
    }

    public static function find(int $requestId, bool $lock = false): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        return DB::table(self::TABLE)
            ->where('transfer_request_id', $requestId)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public static function equipmentIds(object $row): array
    {
        $ids = json_decode((string) ($row->transfer_request_equipment_ids ?? '[]'), true);

        return array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
    }

    /**
     * Destination fields in the shape the transfer form posts them.
     */
    public static function destinationInput(object $row): array
    {
        return [
            'destination_type' => $row->transfer_request_destination_type,
            'department_id' => $row->transfer_request_department_id,
            'custodian_id' => $row->transfer_request_custodian_id,
        ];
    }

    public static function close(int $requestId, string $status, ?string $note = null, ?string $result = null): void
    {
        DB::table(self::TABLE)
            ->where('transfer_request_id', $requestId)
            ->update([
                'transfer_request_status' => $status,
                'transfer_request_reviewed_by' => Auth::id(),
                'transfer_request_reviewed_at' => now(),
                'transfer_request_review_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'transfer_request_result' => $result,
            ]);
    }

    /**
     * Equipment already waiting in a pending request, keyed by equipment ID.
     *
     * @return array<int, true>
     */
    public static function pendingEquipmentIds(): array
    {
        if (! self::tableReady()) {
            return [];
        }

        $ids = [];
        DB::table(self::TABLE)
            ->where('transfer_request_status', self::PENDING)
            ->pluck('transfer_request_equipment_ids')
            ->each(function ($json) use (&$ids) {
                foreach ((array) json_decode((string) $json, true) as $id) {
                    $ids[(int) $id] = true;
                }
            });

        return $ids;
    }

    public static function pendingCount(): int
    {
        return self::tableReady()
            ? DB::table(self::TABLE)->where('transfer_request_status', self::PENDING)->count()
            : 0;
    }

    /**
     * @return array<string, int>
     */
    public static function statusCounts(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        if (! self::tableReady()) {
            return $counts;
        }

        DB::table(self::TABLE)
            ->select('transfer_request_status', DB::raw('COUNT(*) as total'))
            ->groupBy('transfer_request_status')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[$row->transfer_request_status] = (int) $row->total;
            });

        return $counts;
    }

    /**
     * Requests with names resolved for the list page.
     */
    public static function list(?string $status, int $perPage = 20)
    {
        $query = DB::table(self::TABLE.' as tr')
            ->leftJoin('rooms_table as room', 'room.room_id', '=', 'tr.transfer_request_room_id')
            ->leftJoin('users_table as requester', 'requester.user_id', '=', 'tr.transfer_request_requested_by')
            ->leftJoin('users_table as reviewer', 'reviewer.user_id', '=', 'tr.transfer_request_reviewed_by')
            ->when($status !== null, fn ($q) => $q->where('tr.transfer_request_status', $status))
            ->orderByRaw('tr.transfer_request_status = ? DESC', [self::PENDING])
            ->orderByDesc('tr.transfer_request_requested_at')
            ->select(
                'tr.*',
                'room.room_name',
                'requester.user_full_name as requester_name',
                'reviewer.user_full_name as reviewer_name'
            );

        $page = $query->paginate($perPage)->withQueryString();

        $equipmentIds = $page->getCollection()->flatMap(fn ($row) => self::equipmentIds($row))->unique()->values()->all();
        $equipment = $equipmentIds === [] ? collect() : DB::table('equipment_table')
            ->leftJoin('rooms_table', 'rooms_table.room_id', '=', 'equipment_table.equipment_room_id')
            ->whereIn('equipment_table.equipment_id', $equipmentIds)
            ->get([
                'equipment_table.equipment_id',
                'equipment_table.equipment_name',
                'equipment_table.equipment_asset_tag',
                'equipment_table.equipment_quantity',
                'rooms_table.room_name',
            ])
            ->keyBy('equipment_id');

        $departments = Departments::tableReady()
            ? DB::table('departments_table')->pluck('department_name', 'department_id')
            : collect();
        $people = Custodians::tableReady()
            ? DB::table('custodians_table')
                ->whereIn('custodian_id', $page->getCollection()->pluck('transfer_request_custodian_id')->filter()->all())
                ->get(['custodian_id', 'custodian_full_name', 'custodian_department_id'])
                ->keyBy('custodian_id')
            : collect();

        $page->getCollection()->transform(function ($row) use ($equipment, $departments, $people) {
            $row->items = collect(self::equipmentIds($row))
                ->map(fn ($id) => $equipment->get($id))
                ->filter()
                ->values();
            $person = $row->transfer_request_custodian_id ? $people->get($row->transfer_request_custodian_id) : null;
            $row->person_name = $person->custodian_full_name ?? null;
            $row->department_name = $row->transfer_request_department_id
                ? ($departments[$row->transfer_request_department_id] ?? null)
                : ($person && $person->custodian_department_id ? ($departments[$person->custodian_department_id] ?? null) : null);

            return $row;
        });

        return $page;
    }

    /**
     * Short "Mouse" / "Mouse and 2 more" label for messages.
     */
    public static function itemsLabel(array $equipmentIds): string
    {
        $first = $equipmentIds === [] ? null : DB::table('equipment_table')
            ->where('equipment_id', $equipmentIds[0])
            ->value('equipment_name');
        $first = $first ?: 'Equipment';
        $more = count($equipmentIds) - 1;

        return $more > 0 ? $first.' and '.$more.' more' : $first;
    }
}
