<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComputerSets
{
    public const STATUS_ACTIVE = 'Active';

    public const STATUS_INACTIVE = 'Inactive';

    public const ROLES = [
        'monitor' => 'Monitor',
        'tv' => 'TV',
        'keyboard' => 'Keyboard',
        'mouse' => 'Mouse',
        'system_unit' => 'System Unit',
        'avr' => 'AVR / UPS',
        'other' => 'Other',
    ];

    public static function tablesReady(): bool
    {
        return Schema::hasTable('computer_sets_table')
            && Schema::hasTable('computer_set_members_table');
    }

    public static function nextTag(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'COMPSET-'.$date.'-';

        do {
            $latest = DB::table('computer_sets_table')
                ->where('computer_set_tag', 'like', $prefix.'%')
                ->orderByDesc('computer_set_tag')
                ->value('computer_set_tag');

            $sequence = 1;

            if (is_string($latest) && preg_match('/-(\d{5})$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            $tag = $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        } while (
            DB::table('computer_sets_table')->where('computer_set_tag', $tag)->exists()
        );

        return $tag;
    }

    public static function inferRoleFromName(?string $name): ?string
    {
        $n = mb_strtolower(trim((string) $name));

        if ($n === '') {
            return null;
        }

        if (preg_match('/chair|table|curtain|projector|floor mat|long table|cabinet|whiteboard|door|air\s*con|printer|speaker|fan|bulb|ethernet|cable/', $n)) {
            return null;
        }
        if (preg_match('/\btv\b|television/', $n)) {
            return 'tv';
        }
        if (preg_match('/monitor|display/', $n)) {
            return 'monitor';
        }
        if (preg_match('/keyboard/', $n)) {
            return 'keyboard';
        }
        if (preg_match('/mouse/', $n)) {
            return 'mouse';
        }
        if (preg_match('/system\s*unit|\bcpu\b|desktop|tower|\bpc\b/', $n)) {
            return 'system_unit';
        }
        if (preg_match('/\bavr\b|\bups\b/', $n)) {
            return 'avr';
        }

        return 'other';
    }

    public static function roleLabel(?string $role): string
    {
        $key = trim((string) $role);

        return self::ROLES[$key] ?? ($key !== '' ? ucwords(str_replace('_', ' ', $key)) : 'Other');
    }

    public static function roleSort(?string $role): int
    {
        $order = array_flip(array_keys(self::ROLES));

        return $order[trim((string) $role)] ?? 99;
    }

    public static function url(string $suffix = ''): string
    {
        // Computer sets are Administrator-only.
        $prefix = '/admin/computer-sets';
        $suffix = ltrim($suffix, '/');

        return url($suffix === '' ? $prefix : $prefix.'/'.$suffix);
    }

    public static function stats(): array
    {
        if (! self::tablesReady()) {
            return ['sets' => 0, 'members' => 0, 'rooms' => 0];
        }

        return [
            'sets' => (int) DB::table('computer_sets_table')
                ->where('computer_set_status', self::STATUS_ACTIVE)
                ->count(),
            'members' => (int) DB::table('computer_set_members_table')->count(),
            'rooms' => (int) DB::table('computer_sets_table')
                ->where('computer_set_status', self::STATUS_ACTIVE)
                ->whereNotNull('computer_set_room_id')
                ->distinct('computer_set_room_id')
                ->count('computer_set_room_id'),
        ];
    }

    public static function list(?string $search = null, ?int $roomId = null, string $status = self::STATUS_ACTIVE): Collection
    {
        if (! self::tablesReady()) {
            return collect();
        }

        $query = DB::table('computer_sets_table as cs')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'cs.computer_set_room_id')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'r.room_floor_id')
            ->leftJoin('computer_set_members_table as m', 'm.computer_set_id', '=', 'cs.computer_set_id')
            ->select(
                'cs.*',
                'r.room_name',
                'f.floor_level',
                DB::raw('COUNT(m.computer_set_member_id) as member_count')
            )
            ->groupBy(
                'cs.computer_set_id',
                'cs.computer_set_tag',
                'cs.computer_set_name',
                'cs.computer_set_room_id',
                'cs.computer_set_placement_zone',
                'cs.computer_set_status',
                'cs.computer_set_created_by',
                'cs.computer_set_created_at',
                'cs.computer_set_updated_at',
                'r.room_name',
                'f.floor_level'
            )
            ->orderByDesc('cs.computer_set_created_at')
            ->orderBy('cs.computer_set_tag');

        if ($status !== 'all') {
            $query->where('cs.computer_set_status', $status);
        }

        if ($roomId) {
            $query->where('cs.computer_set_room_id', $roomId);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('cs.computer_set_tag', 'like', $like)
                    ->orWhere('cs.computer_set_name', 'like', $like)
                    ->orWhere('cs.computer_set_placement_zone', 'like', $like)
                    ->orWhere('r.room_name', 'like', $like);
            });
        }

        return $query->get();
    }

    public static function find(int $setId): ?object
    {
        if (! self::tablesReady()) {
            return null;
        }

        return DB::table('computer_sets_table as cs')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'cs.computer_set_room_id')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'r.room_floor_id')
            ->where('cs.computer_set_id', $setId)
            ->select('cs.*', 'r.room_name', 'f.floor_level')
            ->first();
    }

    public static function members(int $setId): Collection
    {
        if (! self::tablesReady()) {
            return collect();
        }

        $rows = DB::table('computer_set_members_table as m')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'm.equipment_id')
            ->leftJoin(
                'equipment_categories_table as c',
                'c.equipment_category_id',
                '=',
                'e.equipment_category_id'
            )
            ->where('m.computer_set_id', $setId)
            ->select(
                'm.computer_set_member_id',
                'm.computer_set_id',
                'm.equipment_id',
                'm.member_role',
                'm.member_sort',
                'm.member_added_at',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_qr_code',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_condition_status',
                'e.equipment_placement_zone',
                'c.equipment_category_name'
            )
            ->get();

        return $rows->sortBy(function ($row) {
            return sprintf(
                '%03d-%s',
                self::roleSort($row->member_role),
                mb_strtolower((string) ($row->equipment_name ?? ''))
            );
        })->values();
    }

    public static function membershipMapForEquipmentIds(array $equipmentIds): array
    {
        if (! self::tablesReady() || $equipmentIds === []) {
            return [];
        }

        $rows = DB::table('computer_set_members_table as m')
            ->join('computer_sets_table as cs', 'cs.computer_set_id', '=', 'm.computer_set_id')
            ->whereIn('m.equipment_id', $equipmentIds)
            ->where('cs.computer_set_status', self::STATUS_ACTIVE)
            ->select(
                'm.equipment_id',
                'm.member_role',
                'cs.computer_set_id',
                'cs.computer_set_tag',
                'cs.computer_set_name'
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->equipment_id] = $row;
        }

        return $map;
    }

    public static function forRoom(int $roomId, bool $activeOnly = true): Collection
    {
        if (! self::tablesReady() || $roomId <= 0) {
            return collect();
        }

        $sets = DB::table('computer_sets_table')
            ->where('computer_set_room_id', $roomId)
            ->when($activeOnly, fn ($q) => $q->where('computer_set_status', self::STATUS_ACTIVE))
            ->orderBy('computer_set_tag')
            ->get();

        if ($sets->isEmpty()) {
            return collect();
        }

        $membersBySet = DB::table('computer_set_members_table as m')
            ->join('equipment_table as e', 'e.equipment_id', '=', 'm.equipment_id')
            ->leftJoin(
                'equipment_categories_table as c',
                'c.equipment_category_id',
                '=',
                'e.equipment_category_id'
            )
            ->whereIn('m.computer_set_id', $sets->pluck('computer_set_id')->all())
            ->select(
                'm.computer_set_id',
                'm.equipment_id',
                'm.member_role',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_qr_code',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_category_id',
                'e.equipment_placement_zone',
                'c.equipment_category_name'
            )
            ->get()
            ->groupBy('computer_set_id');

        return $sets->map(function ($set) use ($membersBySet) {
            $members = ($membersBySet->get($set->computer_set_id) ?? collect())
                ->sortBy(fn ($row) => sprintf('%03d-%s', self::roleSort($row->member_role), mb_strtolower((string) $row->equipment_name)))
                ->values();

            $set->members = $members;
            $set->member_count = $members->count();

            return $set;
        });
    }

    public static function isComputerSetCandidate(?string $categoryName, ?string $equipmentName): bool
    {
        $category = trim((string) $categoryName);
        if (strcasecmp($category, 'Computer Equipment') === 0) {
            return true;
        }

        $role = self::inferRoleFromName($equipmentName);

        return in_array($role, ['monitor', 'tv', 'keyboard', 'mouse', 'system_unit', 'avr'], true);
    }

    /**
     * Zones / rows maintained for a room (comlab layout rows + used placement zones).
     *
     * @return list<string>
     */
    public static function zonesForRoom(int $roomId): array
    {
        if ($roomId <= 0 || ! Schema::hasTable('rooms_table')) {
            return [];
        }

        $zones = [];

        $room = DB::table('rooms_table')->where('room_id', $roomId)->first(['room_metadata']);
        if ($room && filled($room->room_metadata ?? null)) {
            $metadata = is_array($room->room_metadata)
                ? $room->room_metadata
                : json_decode((string) $room->room_metadata, true);
            $rows = $metadata['comlab_rows'] ?? [];
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $name = trim((string) (is_array($row) ? ($row['name'] ?? '') : $row));
                    if ($name !== '') {
                        $zones[$name] = $name;
                    }
                }
            }
        }

        if (Schema::hasTable('equipment_table') && Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
            $used = DB::table('equipment_table')
                ->where('equipment_room_id', $roomId)
                ->whereNotNull('equipment_placement_zone')
                ->where('equipment_placement_zone', '!=', '')
                ->distinct()
                ->orderBy('equipment_placement_zone')
                ->pluck('equipment_placement_zone');

            foreach ($used as $zone) {
                $name = trim((string) $zone);
                if ($name === '' || strcasecmp($name, 'Holding') === 0) {
                    continue;
                }
                $zones[$name] = $name;
            }
        }

        $list = array_values($zones);
        natcasesort($list);

        return array_values($list);
    }

    public static function availableEquipmentForRoom(int $roomId, ?int $excludeSetId = null): Collection
    {
        if (! Schema::hasTable('equipment_table') || $roomId <= 0) {
            return collect();
        }

        $query = DB::table('equipment_table as e')
            ->leftJoin(
                'equipment_categories_table as c',
                'c.equipment_category_id',
                '=',
                'e.equipment_category_id'
            )
            ->where('e.equipment_room_id', $roomId)
            ->select(
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_qr_code',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_condition_status',
                'e.equipment_placement_zone',
                'c.equipment_category_name'
            )
            ->orderBy('e.equipment_name');

        if (self::tablesReady()) {
            $query->leftJoin('computer_set_members_table as m', 'm.equipment_id', '=', 'e.equipment_id');
            if ($excludeSetId) {
                $query->where(function ($q) use ($excludeSetId) {
                    $q->whereNull('m.computer_set_member_id')
                        ->orWhere('m.computer_set_id', $excludeSetId);
                });
            } else {
                $query->whereNull('m.computer_set_member_id');
            }
        }

        return $query->get()
            ->filter(fn ($row) => self::isComputerSetCandidate(
                $row->equipment_category_name ?? null,
                $row->equipment_name ?? null
            ))
            ->map(function ($row) {
                $row->suggested_role = self::inferRoleFromName($row->equipment_name ?? null) ?: 'other';

                return $row;
            })
            ->values();
    }

    public static function create(array $data): int
    {
        $now = now();
        $tag = trim((string) ($data['computer_set_tag'] ?? '')) ?: self::nextTag();

        return (int) DB::table('computer_sets_table')->insertGetId([
            'computer_set_tag' => $tag,
            'computer_set_name' => $data['computer_set_name'] ?? null,
            'computer_set_room_id' => $data['computer_set_room_id'] ?? null,
            'computer_set_placement_zone' => $data['computer_set_placement_zone'] ?? null,
            'computer_set_status' => $data['computer_set_status'] ?? self::STATUS_ACTIVE,
            'computer_set_created_by' => $data['computer_set_created_by'] ?? Auth::id(),
            'computer_set_created_at' => $now,
            'computer_set_updated_at' => $now,
        ], 'computer_set_id');
    }

    public static function update(int $setId, array $data): void
    {
        $update = ['computer_set_updated_at' => now()];
        foreach (['computer_set_name', 'computer_set_room_id', 'computer_set_placement_zone', 'computer_set_status'] as $key) {
            if (array_key_exists($key, $data)) {
                $update[$key] = $data[$key];
            }
        }

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update($update);
    }

    public static function addMember(int $setId, int $equipmentId, ?string $role = null): void
    {
        $existing = DB::table('computer_set_members_table')
            ->where('equipment_id', $equipmentId)
            ->first();

        if ($existing && (int) $existing->computer_set_id !== $setId) {
            throw new \RuntimeException('This equipment already belongs to another computer set.');
        }

        if ($existing) {
            DB::table('computer_set_members_table')
                ->where('computer_set_member_id', $existing->computer_set_member_id)
                ->update([
                    'member_role' => $role ?: $existing->member_role,
                    'member_sort' => self::roleSort($role ?: $existing->member_role),
                ]);

            return;
        }

        if ($role === null || $role === '') {
            $name = DB::table('equipment_table')->where('equipment_id', $equipmentId)->value('equipment_name');
            $role = self::inferRoleFromName($name) ?: 'other';
        }

        DB::table('computer_set_members_table')->insert([
            'computer_set_id' => $setId,
            'equipment_id' => $equipmentId,
            'member_role' => $role,
            'member_sort' => self::roleSort($role),
            'member_added_at' => now(),
            'member_added_by' => Auth::id(),
        ]);

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update(['computer_set_updated_at' => now()]);
    }

    public static function removeMember(int $setId, int $equipmentId): void
    {
        DB::table('computer_set_members_table')
            ->where('computer_set_id', $setId)
            ->where('equipment_id', $equipmentId)
            ->delete();

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update(['computer_set_updated_at' => now()]);
    }

    public static function syncMembers(int $setId, array $equipmentRoles): void
    {
        $keepIds = [];

        foreach ($equipmentRoles as $equipmentId => $role) {
            $equipmentId = (int) $equipmentId;
            if ($equipmentId <= 0) {
                continue;
            }
            self::addMember($setId, $equipmentId, is_string($role) ? $role : null);
            $keepIds[] = $equipmentId;
        }

        $query = DB::table('computer_set_members_table')->where('computer_set_id', $setId);
        if ($keepIds !== []) {
            $query->whereNotIn('equipment_id', $keepIds);
        }
        $query->delete();

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update(['computer_set_updated_at' => now()]);
    }
}
