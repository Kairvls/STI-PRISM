<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Equipment disposals asked for by non-administrators. Nothing is disposed until the Administrator approves.
 */
class DisposalRequests
{
    public const PENDING = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';
    public const CANCELLED = 'Cancelled';

    public const STATUSES = [self::PENDING, self::APPROVED, self::REJECTED, self::CANCELLED];

    private const TABLE = 'equipment_disposal_requests_table';

    public static function tableReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable(self::TABLE);
    }

    public static function requiresApproval(): bool
    {
        return self::tableReady() && ! RoleAccess::isAdmin(Auth::user());
    }

    /**
     * @param  array{reason: string, method: ?string, location: ?string, residual_value: mixed}  $details
     */
    public static function create(array $equipmentIds, array $details): int
    {
        return (int) DB::table(self::TABLE)->insertGetId([
            'disposal_request_equipment_ids' => json_encode(array_values(array_map('intval', $equipmentIds))),
            'disposal_request_reason' => trim((string) $details['reason']),
            'disposal_request_method' => filled($details['method'] ?? null) ? $details['method'] : null,
            'disposal_request_location' => filled($details['location'] ?? null) ? trim((string) $details['location']) : null,
            'disposal_request_residual_value' => ($details['residual_value'] ?? null) !== null && $details['residual_value'] !== ''
                ? (float) $details['residual_value']
                : null,
            'disposal_request_status' => self::PENDING,
            'disposal_request_requested_by' => Auth::id(),
            'disposal_request_requested_at' => now(),
        ]);
    }

    public static function find(int $requestId, bool $lock = false): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        return DB::table(self::TABLE)
            ->where('disposal_request_id', $requestId)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public static function equipmentIds(object $row): array
    {
        $ids = json_decode((string) ($row->disposal_request_equipment_ids ?? '[]'), true);

        return array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
    }

    /**
     * Disposal fields in the shape the dispose form posts them.
     */
    public static function details(object $row): array
    {
        return [
            'reason' => (string) $row->disposal_request_reason,
            'method' => $row->disposal_request_method,
            'location' => $row->disposal_request_location,
            'residual_value' => $row->disposal_request_residual_value,
        ];
    }

    public static function close(int $requestId, string $status, ?string $note = null, ?string $result = null): void
    {
        DB::table(self::TABLE)
            ->where('disposal_request_id', $requestId)
            ->update([
                'disposal_request_status' => $status,
                'disposal_request_reviewed_by' => Auth::id(),
                'disposal_request_reviewed_at' => now(),
                'disposal_request_review_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'disposal_request_result' => $result,
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
            ->where('disposal_request_status', self::PENDING)
            ->pluck('disposal_request_equipment_ids')
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
            ? DB::table(self::TABLE)->where('disposal_request_status', self::PENDING)->count()
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
            ->select('disposal_request_status', DB::raw('COUNT(*) as total'))
            ->groupBy('disposal_request_status')
            ->get()
            ->each(function ($row) use (&$counts) {
                $counts[$row->disposal_request_status] = (int) $row->total;
            });

        return $counts;
    }

    /**
     * Requests with names resolved for the list page.
     */
    public static function list(?string $status, int $perPage = 20)
    {
        $query = DB::table(self::TABLE.' as dr')
            ->leftJoin('users_table as requester', 'requester.user_id', '=', 'dr.disposal_request_requested_by')
            ->leftJoin('users_table as reviewer', 'reviewer.user_id', '=', 'dr.disposal_request_reviewed_by')
            ->when($status !== null, fn ($q) => $q->where('dr.disposal_request_status', $status))
            ->orderByRaw('dr.disposal_request_status = ? DESC', [self::PENDING])
            ->orderByDesc('dr.disposal_request_requested_at')
            ->select(
                'dr.*',
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
                'equipment_table.equipment_inventory_status',
                'rooms_table.room_name',
            ])
            ->keyBy('equipment_id');

        $page->getCollection()->transform(function ($row) use ($equipment) {
            $row->items = collect(self::equipmentIds($row))
                ->map(fn ($id) => $equipment->get($id))
                ->filter()
                ->values();

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
