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

    public static function historyReady(): bool
    {
        return Schema::hasTable('computer_set_member_history_table');
    }

    /**
     * Roles that may appear only once per computer set.
     */
    public static function uniqueRoles(): array
    {
        return array_values(array_filter(
            array_keys(self::ROLES),
            fn ($role) => $role !== 'other'
        ));
    }

    public static function roleIsUnique(?string $role): bool
    {
        $role = trim((string) $role);

        return $role !== '' && $role !== 'other' && isset(self::ROLES[$role]);
    }

    public static function resolveRole(int $equipmentId, ?string $role = null): string
    {
        $role = trim((string) $role);
        if ($role !== '' && isset(self::ROLES[$role])) {
            return $role;
        }

        $name = DB::table('equipment_table')->where('equipment_id', $equipmentId)->value('equipment_name');

        return self::inferRoleFromName($name) ?: 'other';
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

    /**
     * Distinct display names for the index name filter dropdown.
     *
     * @return list<string>
     */
    public static function nameOptions(?int $roomId = null, string $status = self::STATUS_ACTIVE): array
    {
        if (! self::tablesReady()) {
            return [];
        }

        $query = DB::table('computer_sets_table')
            ->whereNotNull('computer_set_name')
            ->where('computer_set_name', '!=', '');

        if ($status !== 'all') {
            $query->where('computer_set_status', $status);
        }
        if ($roomId) {
            $query->where('computer_set_room_id', $roomId);
        }

        return $query
            ->distinct()
            ->pluck('computer_set_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->sort(fn ($a, $b) => strnatcasecmp($a, $b))
            ->values()
            ->all();
    }

    public static function list(
        ?string $search = null,
        ?int $roomId = null,
        string $status = self::STATUS_ACTIVE,
        ?string $name = null
    ): Collection {
        if (! self::tablesReady()) {
            return collect();
        }

        $query = DB::table('computer_sets_table as cs')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'cs.computer_set_room_id')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'r.room_floor_id')
            ->leftJoin('computer_set_members_table as m', 'm.computer_set_id', '=', 'cs.computer_set_id')
            ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'm.equipment_id')
            ->select(
                'cs.*',
                'r.room_name',
                'f.floor_level',
                DB::raw('COUNT(m.computer_set_member_id) as member_count'),
                DB::raw("SUM(CASE WHEN e.equipment_inventory_status = 'For Replacement' THEN 1 ELSE 0 END) as needs_replacement_count")
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
            );

        if ($status !== 'all') {
            $query->where('cs.computer_set_status', $status);
        }

        if ($roomId) {
            $query->where('cs.computer_set_room_id', $roomId);
        }

        $name = trim((string) $name);
        if ($name !== '') {
            $query->where('cs.computer_set_name', $name);
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

        // Natural name order so "Computer Set 10" follows 9, not 1.
        $sets = $query->get()
            ->sort(function ($a, $b) {
                $room = strnatcasecmp(
                    trim(($a->floor_level ? $a->floor_level.' — ' : '').($a->room_name ?? '')),
                    trim(($b->floor_level ? $b->floor_level.' — ' : '').($b->room_name ?? ''))
                );
                if ($room !== 0) {
                    return $room;
                }

                $nameA = (string) ($a->computer_set_name ?: $a->computer_set_tag);
                $nameB = (string) ($b->computer_set_name ?: $b->computer_set_tag);
                $nameCmp = strnatcasecmp($nameA, $nameB);
                if ($nameCmp !== 0) {
                    return $nameCmp;
                }

                return strnatcasecmp((string) $a->computer_set_tag, (string) $b->computer_set_tag);
            })
            ->values();

        return self::attachAccountablePeople($sets);
    }

    /**
     * Attach primary accountable person (from property assignments on members).
     */
    public static function attachAccountablePeople(Collection $sets): Collection
    {
        if ($sets->isEmpty()) {
            return $sets;
        }

        $blank = function ($set) {
            $set->accountable_person_name = null;
            $set->accountable_person_id = null;
            $set->accountable_employee_id = null;
            $set->accountable_person_url = null;

            return $set;
        };

        if (! PropertyAssignments::tableReady()) {
            return $sets->map($blank)->values();
        }

        $setIds = $sets->pluck('computer_set_id')->map(fn ($id) => (int) $id)->all();
        $owners = DB::table('computer_set_members_table as m')
            ->join('property_assignments_table as pa', 'pa.assignment_equipment_id', '=', 'm.equipment_id')
            ->leftJoin('custodians_table as person', 'person.custodian_id', '=', 'pa.assignment_custodian_id')
            ->whereIn('m.computer_set_id', $setIds)
            ->where('pa.assignment_status', \App\Models\PropertyAssignment::STATUS_ACTIVE)
            ->groupBy(
                'm.computer_set_id',
                'pa.assignment_custodian_id',
                'person.custodian_full_name',
                'person.custodian_employee_id'
            )
            ->select(
                'm.computer_set_id',
                'pa.assignment_custodian_id',
                'person.custodian_full_name',
                'person.custodian_employee_id',
                DB::raw('COUNT(*) as held_count')
            )
            ->get()
            ->groupBy('computer_set_id')
            ->map(fn ($rows) => $rows->sortByDesc('held_count')->first());

        return $sets->map(function ($set) use ($owners, $blank) {
            $owner = $owners[(int) $set->computer_set_id] ?? null;
            if (! $owner || empty($owner->assignment_custodian_id)) {
                return $blank($set);
            }

            $set->accountable_person_name = $owner->custodian_full_name;
            $set->accountable_person_id = (int) $owner->assignment_custodian_id;
            $set->accountable_employee_id = $owner->custodian_employee_id;
            $set->accountable_person_url = PropertyAssignments::url('/people/'.$owner->assignment_custodian_id);

            return $set;
        })->values();
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
                'e.equipment_inventory_status',
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
        })->map(function ($row) {
            $row->needs_replacement = ($row->equipment_inventory_status ?? null) === 'For Replacement';

            return $row;
        })->values();
    }

    public static function memberHistory(int $setId, int $limit = 50): Collection
    {
        if (! self::historyReady() || $setId <= 0) {
            return collect();
        }

        $rows = DB::table('computer_set_member_history_table as h')
            ->leftJoin('equipment_table as e', 'e.equipment_id', '=', 'h.equipment_id')
            ->leftJoin('equipment_table as n', 'n.equipment_id', '=', 'h.replaced_by_equipment_id')
            ->where('h.computer_set_id', $setId)
            ->orderByDesc('h.performed_at')
            ->orderByDesc('h.history_id')
            ->limit($limit)
            ->select(
                'h.*',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_qr_code',
                'n.equipment_name as replacement_equipment_name',
                'n.equipment_asset_tag as replacement_equipment_asset_tag',
                'n.equipment_qr_code as replacement_equipment_qr_code'
            )
            ->get();

        return $rows->values();
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
                'e.equipment_condition_status',
                'e.equipment_inventory_status',
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
            $set->needs_replacement_count = $members
                ->filter(fn ($row) => ($row->equipment_inventory_status ?? null) === 'For Replacement')
                ->count();

            return $set;
        });
    }

    /**
     * Room-layout friendly computer sets, keyed by room_id.
     *
     * @param  array<int, int>  $roomIds
     * @return array<int, list<array<string, mixed>>>
     */
    public static function layoutPayloadByRoom(array $roomIds): array
    {
        $roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds))));
        if ($roomIds === [] || ! self::tablesReady()) {
            return [];
        }

        $payload = [];
        foreach ($roomIds as $roomId) {
            $payload[$roomId] = self::forRoom($roomId)
                ->map(function ($set) {
                    $members = collect($set->members ?? [])->map(function ($member) {
                        return [
                            'id' => (int) $member->equipment_id,
                            'name' => $member->equipment_name,
                            'role' => $member->member_role,
                            'role_label' => self::roleLabel($member->member_role),
                            'asset_tag' => $member->equipment_asset_tag,
                            'qr_code' => $member->equipment_qr_code,
                            'serial_number' => $member->equipment_serial_number,
                            'condition' => $member->equipment_condition_status ?? null,
                            'inventory_status' => $member->equipment_inventory_status ?? null,
                            'placement_zone' => $member->equipment_placement_zone ?? null,
                            'needs_replacement' => ($member->equipment_inventory_status ?? null) === 'For Replacement',
                        ];
                    })->values()->all();

                    return [
                        'id' => (int) $set->computer_set_id,
                        'tag' => $set->computer_set_tag,
                        'name' => $set->computer_set_name,
                        'placement_zone' => $set->computer_set_placement_zone,
                        'status' => $set->computer_set_status,
                        'member_count' => (int) ($set->member_count ?? count($members)),
                        'needs_replacement_count' => (int) ($set->needs_replacement_count ?? 0),
                        'manage_url' => self::url('/'.$set->computer_set_id),
                        'members' => $members,
                    ];
                })
                ->values()
                ->all();
        }

        return $payload;
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
                'e.equipment_inventory_status',
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
                $row->from_storage = false;

                return $row;
            })
            ->values();
    }

    /**
     * Free Computer Equipment in storage rooms that can replace a set part.
     * Optionally filter to one role (monitor, keyboard, …).
     */
    public static function storageReplacementCandidates(?string $role = null, ?int $excludeEquipmentId = null): Collection
    {
        if (! Schema::hasTable('equipment_table') || ! Schema::hasTable('rooms_table')) {
            return collect();
        }

        $role = $role !== null ? trim($role) : null;

        $query = DB::table('equipment_table as e')
            ->join('rooms_table as r', 'r.room_id', '=', 'e.equipment_room_id')
            ->leftJoin(
                'equipment_categories_table as c',
                'c.equipment_category_id',
                '=',
                'e.equipment_category_id'
            )
            ->select(
                'e.equipment_id',
                'e.equipment_name',
                'e.equipment_asset_tag',
                'e.equipment_qr_code',
                'e.equipment_serial_number',
                'e.equipment_brand_name',
                'e.equipment_model',
                'e.equipment_condition_status',
                'e.equipment_inventory_status',
                'e.equipment_placement_zone',
                'e.equipment_room_id',
                'r.room_name',
                'r.room_type',
                'c.equipment_category_name'
            )
            ->orderBy('e.equipment_name');

        if (Schema::hasColumn('equipment_table', 'equipment_inventory_status')) {
            $query->where(function ($q) {
                $q->whereNull('e.equipment_inventory_status')
                    ->orWhere('e.equipment_inventory_status', '!=', 'Disposed');
            });
        }

        if (self::tablesReady()) {
            $query->leftJoin('computer_set_members_table as m', 'm.equipment_id', '=', 'e.equipment_id')
                ->whereNull('m.computer_set_member_id');
        }

        if ($excludeEquipmentId) {
            $query->where('e.equipment_id', '!=', $excludeEquipmentId);
        }

        return $query->get()
            ->filter(function ($row) use ($role) {
                if (! RoomCategories::isStorageType($row->room_type ?? null)) {
                    return false;
                }
                if (! self::isComputerSetCandidate(
                    $row->equipment_category_name ?? null,
                    $row->equipment_name ?? null
                )) {
                    return false;
                }
                $suggested = self::inferRoleFromName($row->equipment_name ?? null) ?: 'other';
                if ($role !== null && $role !== '' && $suggested !== $role) {
                    return false;
                }
                $status = (string) ($row->equipment_inventory_status ?? '');
                if (in_array($status, ['For Replacement', EquipmentInventoryStatuses::REPLACED], true)) {
                    return false;
                }
                $condition = (string) ($row->equipment_condition_status ?? '');
                if (in_array($condition, ['Disposed', 'Damaged', 'Defective'], true)) {
                    return false;
                }

                return true;
            })
            ->map(function ($row) {
                $row->suggested_role = self::inferRoleFromName($row->equipment_name ?? null) ?: 'other';
                $row->from_storage = true;

                return $row;
            })
            ->values();
    }

    /**
     * Swap a set member with a free storage unit of the same role.
     * Logs set history, room transfers, moves the new part into the set room,
     * and returns the old part to storage as Replaced (swap complete — no longer For Replacement).
     */
    public static function replaceMember(int $setId, int $oldEquipmentId, int $newEquipmentId): void
    {
        if ($oldEquipmentId <= 0 || $newEquipmentId <= 0 || $oldEquipmentId === $newEquipmentId) {
            throw new \RuntimeException('Choose a different equipment unit to replace with.');
        }

        DB::transaction(function () use ($setId, $oldEquipmentId, $newEquipmentId) {
            $set = DB::table('computer_sets_table')->where('computer_set_id', $setId)->lockForUpdate()->first();
            if (! $set) {
                throw new \RuntimeException('Computer set not found.');
            }

            $member = DB::table('computer_set_members_table')
                ->where('computer_set_id', $setId)
                ->where('equipment_id', $oldEquipmentId)
                ->lockForUpdate()
                ->first();
            if (! $member) {
                throw new \RuntimeException('That equipment is not part of this computer set.');
            }

            $role = self::resolveRole($oldEquipmentId, $member->member_role ?? null);

            $old = DB::table('equipment_table')->where('equipment_id', $oldEquipmentId)->lockForUpdate()->first();
            $new = DB::table('equipment_table')->where('equipment_id', $newEquipmentId)->lockForUpdate()->first();
            if (! $old || ! $new) {
                throw new \RuntimeException('Equipment not found.');
            }

            $newRoom = DB::table('rooms_table')->where('room_id', $new->equipment_room_id)->first();
            if (! $newRoom || ! RoomCategories::isStorageType($newRoom->room_type ?? null)) {
                throw new \RuntimeException('The replacement unit must currently be in storage.');
            }

            $taken = DB::table('computer_set_members_table')
                ->where('equipment_id', $newEquipmentId)
                ->exists();
            if ($taken) {
                throw new \RuntimeException('That replacement unit already belongs to a computer set.');
            }

            $newRole = self::resolveRole($newEquipmentId, null);
            if (self::roleIsUnique($role) && $newRole !== $role) {
                throw new \RuntimeException(
                    'Pick a storage '.self::roleLabel($role).' to replace this part (selected unit is a '.self::roleLabel($newRole).').'
                );
            }

            $setRoomId = (int) ($set->computer_set_room_id ?? 0);
            $setRoom = $setRoomId > 0
                ? DB::table('rooms_table')->where('room_id', $setRoomId)->first()
                : null;
            if (! $setRoom) {
                throw new \RuntimeException('This computer set has no room. Set the room first.');
            }

            $storageRoomId = (int) $new->equipment_room_id;
            $userId = Auth::id();
            $hasTransfers = Schema::hasTable('equipment_transfer_history_table');
            $oldTag = $old->equipment_asset_tag ?: ('ID #'.$old->equipment_id);
            $newTag = $new->equipment_asset_tag ?: ('ID #'.$new->equipment_id);

            if ($hasTransfers && (int) $new->equipment_room_id !== $setRoomId) {
                DB::table('equipment_transfer_history_table')->insert([
                    'equipment_id' => $newEquipmentId,
                    'from_room_id' => (int) $new->equipment_room_id,
                    'to_room_id' => $setRoomId,
                    'transferred_by' => $userId,
                    'remarks' => 'Computer set swap: replaces '.$oldTag.' in set '.$set->computer_set_tag.'.',
                    'created_at' => now(),
                ]);
            }

            $newUpdate = [
                'equipment_room_id' => $setRoomId,
                'equipment_inventory_status' => 'Active',
            ];
            if (Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
                $zone = trim((string) ($set->computer_set_placement_zone ?? ''));
                if ($zone !== '') {
                    $newUpdate['equipment_placement_zone'] = $zone;
                    if (Schema::hasColumn('equipment_table', 'equipment_current_location')) {
                        $newUpdate['equipment_current_location'] = $zone;
                    }
                } else {
                    $newUpdate['equipment_placement_zone'] = $old->equipment_placement_zone;
                    if (Schema::hasColumn('equipment_table', 'equipment_current_location')) {
                        $newUpdate['equipment_current_location'] = $old->equipment_current_location;
                    }
                }
            }
            foreach (['equipment_position_x', 'equipment_position_y', 'equipment_rotation'] as $col) {
                if (Schema::hasColumn('equipment_table', $col) && isset($old->{$col})) {
                    $newUpdate[$col] = $old->{$col};
                }
            }
            if (Schema::hasColumn('equipment_table', 'equipment_replaces_id')) {
                $newUpdate['equipment_replaces_id'] = $oldEquipmentId;
            }
            DB::table('equipment_table')->where('equipment_id', $newEquipmentId)->update($newUpdate);

            if ($hasTransfers && (int) $old->equipment_room_id !== $storageRoomId) {
                DB::table('equipment_transfer_history_table')->insert([
                    'equipment_id' => $oldEquipmentId,
                    'from_room_id' => (int) $old->equipment_room_id,
                    'to_room_id' => $storageRoomId,
                    'transferred_by' => $userId,
                    'remarks' => 'Computer set swap: replaced by '.$newTag.' in set '.$set->computer_set_tag.'.',
                    'created_at' => now(),
                ]);
            }

            // Swap is complete — mark retired unit Replaced (not For Replacement / Under Maintenance).
            $oldUpdate = [
                'equipment_room_id' => $storageRoomId,
                'equipment_inventory_status' => EquipmentInventoryStatuses::REPLACED,
            ];
            if (Schema::hasColumn('equipment_table', 'equipment_replaced_by_id')) {
                $oldUpdate['equipment_replaced_by_id'] = $newEquipmentId;
            }
            if (Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
                $oldUpdate['equipment_placement_zone'] = 'Holding';
                if (Schema::hasColumn('equipment_table', 'equipment_current_location')) {
                    $oldUpdate['equipment_current_location'] = 'Holding';
                }
            }
            DB::table('equipment_table')->where('equipment_id', $oldEquipmentId)->update($oldUpdate);

            self::removeMember($setId, $oldEquipmentId, $newEquipmentId);
            self::addMember($setId, $newEquipmentId, $role);
            self::applySetPlacementToMembers($setId);

            // Snapshot rooms before the swap for the Replacement Log.
            ReplacementStock::logStockSwap($old, $new, [
                'from_room_id' => $storageRoomId,
                'to_room_id' => $setRoomId,
                'notes' => 'Computer set swap in '.$set->computer_set_tag.'.',
                'performed_by' => $userId,
            ]);
        });
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

        if (array_key_exists('computer_set_placement_zone', $data)
            || array_key_exists('computer_set_room_id', $data)
        ) {
            self::applySetPlacementToMembers($setId);
        }
    }

    /**
     * Mirror the set's room/row onto member equipment so Room Interior Layout stays aligned.
     */
    public static function applySetPlacementToMembers(int $setId): void
    {
        if (! self::tablesReady() || ! Schema::hasTable('equipment_table')) {
            return;
        }

        $set = DB::table('computer_sets_table')->where('computer_set_id', $setId)->first();
        if (! $set) {
            return;
        }

        $memberIds = DB::table('computer_set_members_table')
            ->where('computer_set_id', $setId)
            ->pluck('equipment_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if ($memberIds === []) {
            return;
        }

        $zone = trim((string) ($set->computer_set_placement_zone ?? ''));
        $roomId = (int) ($set->computer_set_room_id ?? 0);

        $equipmentUpdate = [];
        if ($zone !== '' && Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
            $equipmentUpdate['equipment_placement_zone'] = $zone;
            if (Schema::hasColumn('equipment_table', 'equipment_current_location')) {
                $equipmentUpdate['equipment_current_location'] = $zone;
            }
        }
        if ($roomId > 0 && Schema::hasColumn('equipment_table', 'equipment_room_id')) {
            $equipmentUpdate['equipment_room_id'] = $roomId;
        }

        if ($equipmentUpdate === []) {
            return;
        }

        DB::table('equipment_table')
            ->whereIn('equipment_id', $memberIds)
            ->update($equipmentUpdate);
    }

    public static function assertUniqueRoles(array $rolesByEquipmentId): void
    {
        $seen = [];
        foreach ($rolesByEquipmentId as $equipmentId => $role) {
            $role = self::resolveRole((int) $equipmentId, is_string($role) ? $role : null);
            if (! self::roleIsUnique($role)) {
                continue;
            }
            if (isset($seen[$role])) {
                throw new \RuntimeException(
                    'A computer set can only have one '.self::roleLabel($role).'. Remove the extra one before saving.'
                );
            }
            $seen[$role] = (int) $equipmentId;
        }
    }

    public static function assertRoleAvailable(int $setId, string $role, ?int $exceptEquipmentId = null): void
    {
        if (! self::roleIsUnique($role)) {
            return;
        }

        $query = DB::table('computer_set_members_table')
            ->where('computer_set_id', $setId)
            ->where('member_role', $role);

        if ($exceptEquipmentId) {
            $query->where('equipment_id', '!=', $exceptEquipmentId);
        }

        if ($query->exists()) {
            throw new \RuntimeException(
                'This set already has a '.self::roleLabel($role).'. Only one is allowed per computer set.'
            );
        }
    }

    public static function recordHistory(
        int $setId,
        string $event,
        int $equipmentId,
        ?string $role = null,
        ?int $replacedByEquipmentId = null,
        ?string $notes = null
    ): void {
        if (! self::historyReady()) {
            return;
        }

        DB::table('computer_set_member_history_table')->insert([
            'computer_set_id' => $setId,
            'member_role' => $role,
            'equipment_id' => $equipmentId,
            'event' => $event,
            'replaced_by_equipment_id' => $replacedByEquipmentId,
            'notes' => $notes,
            'performed_at' => now(),
            'performed_by' => Auth::id(),
        ]);
    }

    public static function addMember(int $setId, int $equipmentId, ?string $role = null): void
    {
        $existing = DB::table('computer_set_members_table')
            ->where('equipment_id', $equipmentId)
            ->first();

        if ($existing && (int) $existing->computer_set_id !== $setId) {
            throw new \RuntimeException('This equipment already belongs to another computer set.');
        }

        $role = self::resolveRole($equipmentId, $role);

        if ($existing) {
            self::assertRoleAvailable($setId, $role, $equipmentId);
            DB::table('computer_set_members_table')
                ->where('computer_set_member_id', $existing->computer_set_member_id)
                ->update([
                    'member_role' => $role,
                    'member_sort' => self::roleSort($role),
                ]);

            return;
        }

        self::assertRoleAvailable($setId, $role);

        DB::table('computer_set_members_table')->insert([
            'computer_set_id' => $setId,
            'equipment_id' => $equipmentId,
            'member_role' => $role,
            'member_sort' => self::roleSort($role),
            'member_added_at' => now(),
            'member_added_by' => Auth::id(),
        ]);

        self::recordHistory($setId, 'added', $equipmentId, $role);

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update(['computer_set_updated_at' => now()]);

        self::applySetPlacementToMembers($setId);
    }

    /**
     * First storage / stockroom room id, if any.
     */
    public static function defaultStorageRoomId(): ?int
    {
        if (! Schema::hasTable('rooms_table')) {
            return null;
        }

        $rooms = DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where(fn ($inner) => $inner->whereNull('room_is_archived')->orWhere('room_is_archived', false))
            )
            ->orderBy('room_name')
            ->get(['room_id', 'room_type']);

        $storage = $rooms->first(fn ($room) => RoomCategories::isStorageType($room->room_type ?? null));

        return $storage ? (int) $storage->room_id : null;
    }

    /**
     * Unlink equipment from a set. When $releaseToRoomHolding is true (explicit Remove),
     * keep it in the same room and move it to the room's Holding area for interior layout.
     */
    public static function removeMember(
        int $setId,
        int $equipmentId,
        ?int $replacedByEquipmentId = null,
        bool $releaseToRoomHolding = false
    ): void {
        $row = DB::table('computer_set_members_table')
            ->where('computer_set_id', $setId)
            ->where('equipment_id', $equipmentId)
            ->first();

        if (! $row) {
            return;
        }

        DB::table('computer_set_members_table')
            ->where('computer_set_member_id', $row->computer_set_member_id)
            ->delete();

        $event = $replacedByEquipmentId ? 'replaced' : 'removed';
        self::recordHistory(
            $setId,
            $event,
            $equipmentId,
            $row->member_role,
            $replacedByEquipmentId,
            $replacedByEquipmentId
                ? 'Replaced in this computer set'
                : ($releaseToRoomHolding
                    ? 'Removed from this computer set; left in room Holding'
                    : 'Removed from this computer set')
        );

        if ($releaseToRoomHolding && ! $replacedByEquipmentId && Schema::hasTable('equipment_table')) {
            $equipment = DB::table('equipment_table')->where('equipment_id', $equipmentId)->first();
            $set = DB::table('computer_sets_table')->where('computer_set_id', $setId)->first();
            if ($equipment) {
                $roomId = (int) ($equipment->equipment_room_id ?? 0)
                    ?: (int) ($set->computer_set_room_id ?? 0);
                $update = [];
                if ($roomId > 0) {
                    $update['equipment_room_id'] = $roomId;
                }
                if (Schema::hasColumn('equipment_table', 'equipment_placement_zone')) {
                    $update['equipment_placement_zone'] = 'Holding';
                    if (Schema::hasColumn('equipment_table', 'equipment_current_location')) {
                        $update['equipment_current_location'] = 'Holding';
                    }
                }
                if (Schema::hasColumn('equipment_table', 'equipment_position_x')) {
                    $update['equipment_position_x'] = 50;
                }
                if (Schema::hasColumn('equipment_table', 'equipment_position_y')) {
                    $update['equipment_position_y'] = 90;
                }
                if ($update !== []) {
                    DB::table('equipment_table')->where('equipment_id', $equipmentId)->update($update);
                }
            }
        }

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update(['computer_set_updated_at' => now()]);
    }

    public static function syncMembers(int $setId, array $equipmentRoles): void
    {
        $desired = [];
        foreach ($equipmentRoles as $equipmentId => $role) {
            $equipmentId = (int) $equipmentId;
            if ($equipmentId <= 0) {
                continue;
            }
            $desired[$equipmentId] = self::resolveRole($equipmentId, is_string($role) ? $role : null);
        }

        self::assertUniqueRoles($desired);

        $current = DB::table('computer_set_members_table')
            ->where('computer_set_id', $setId)
            ->get()
            ->keyBy(fn ($row) => (int) $row->equipment_id);

        $toRemove = [];
        foreach ($current as $equipmentId => $row) {
            if (! isset($desired[$equipmentId])) {
                $toRemove[$equipmentId] = (string) ($row->member_role ?? 'other');
            }
        }

        $toAdd = [];
        foreach ($desired as $equipmentId => $role) {
            if (! $current->has($equipmentId)) {
                $toAdd[$equipmentId] = $role;
            }
        }

        foreach ($toRemove as $equipmentId => $role) {
            $replacementId = null;
            if (self::roleIsUnique($role)) {
                foreach ($toAdd as $newId => $newRole) {
                    if ($newRole === $role) {
                        $replacementId = (int) $newId;
                        break;
                    }
                }
            }
            self::removeMember($setId, (int) $equipmentId, $replacementId);
        }

        foreach ($desired as $equipmentId => $role) {
            self::addMember($setId, (int) $equipmentId, $role);
        }

        self::applySetPlacementToMembers($setId);
    }

    /**
     * Map layout-side role aliases onto ComputerSets::ROLES keys.
     */
    public static function normalizeLayoutRole(?string $role): ?string
    {
        $role = trim((string) $role);
        if ($role === '') {
            return null;
        }

        $aliases = [
            'power' => 'avr',
            'peripheral' => 'other',
            'system-unit' => 'system_unit',
            'system unit' => 'system_unit',
        ];

        $normalized = $aliases[mb_strtolower($role)] ?? $role;

        return isset(self::ROLES[$normalized]) ? $normalized : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function layoutPayloadForRoom(int $roomId): array
    {
        return self::layoutPayloadByRoom([$roomId])[$roomId] ?? [];
    }

    /**
     * Persist Room Interior Layout computer sets into the Computer Sets module.
     * Creates missing COMPSETs, updates zone/name/members, and deactivates room sets
     * omitted from the payload (layout is spatial source of truth while editing).
     *
     * @param  list<array<string, mixed>>  $sets
     * @return list<array<string, mixed>>
     */
    public static function syncRoomFromLayout(int $roomId, array $sets): array
    {
        if (! self::tablesReady() || $roomId <= 0) {
            return [];
        }

        $normalized = [];
        $claimedEquipment = [];

        foreach ($sets as $index => $set) {
            if (! is_array($set)) {
                continue;
            }

            $members = [];
            foreach (($set['members'] ?? []) as $member) {
                if (! is_array($member)) {
                    continue;
                }
                $equipmentId = (int) ($member['id'] ?? $member['equipment_id'] ?? 0);
                if ($equipmentId <= 0 || isset($claimedEquipment[$equipmentId])) {
                    continue;
                }

                $role = self::normalizeLayoutRole(
                    isset($member['role']) && is_string($member['role']) ? $member['role'] : null
                );
                $members[$equipmentId] = $role;
                $claimedEquipment[$equipmentId] = true;
            }

            if ($members === [] && empty($set['id'])) {
                // Skip empty inferred placeholders; keep named module sets even if emptied.
                continue;
            }

            try {
                self::assertUniqueRoles($members);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException(
                    'Computer set '.($index + 1).': '.$e->getMessage(),
                    0,
                    $e
                );
            }

            $normalized[] = [
                'id' => isset($set['id']) ? (int) $set['id'] : 0,
                'name' => isset($set['name']) ? trim((string) $set['name']) : null,
                'placement_zone' => isset($set['placement_zone'])
                    ? trim((string) $set['placement_zone'])
                    : null,
                'members' => $members,
            ];
        }

        return DB::transaction(function () use ($roomId, $normalized, $claimedEquipment) {
            $existing = self::forRoom($roomId, false)
                ->keyBy(fn ($set) => (int) $set->computer_set_id);

            $keepIds = [];

            foreach ($normalized as $set) {
                $setId = (int) ($set['id'] ?? 0);
                if ($setId > 0) {
                    $row = $existing->get($setId);
                    if (! $row || (int) ($row->computer_set_room_id ?? 0) !== $roomId) {
                        throw new \RuntimeException(
                            'Computer set #'.$setId.' does not belong to this room.'
                        );
                    }
                    $keepIds[$setId] = true;
                }
            }

            // Free claimed equipment that is leaving its current set so moves do not collide.
            if ($claimedEquipment !== []) {
                $desiredSetByEquipment = [];
                foreach ($normalized as $candidate) {
                    $candidateId = (int) ($candidate['id'] ?? 0);
                    foreach (array_keys($candidate['members']) as $equipmentId) {
                        $desiredSetByEquipment[(int) $equipmentId] = $candidateId;
                    }
                }

                $currentMemberships = DB::table('computer_set_members_table')
                    ->whereIn('equipment_id', array_keys($claimedEquipment))
                    ->get(['computer_set_id', 'equipment_id']);

                foreach ($currentMemberships as $membership) {
                    $memberSetId = (int) $membership->computer_set_id;
                    $equipmentId = (int) $membership->equipment_id;
                    $desiredSetId = (int) ($desiredSetByEquipment[$equipmentId] ?? 0);
                    // Keep only when staying on the same existing set; otherwise detach first.
                    if ($desiredSetId > 0 && $desiredSetId === $memberSetId) {
                        continue;
                    }
                    self::removeMember($memberSetId, $equipmentId);
                }
            }

            foreach ($normalized as &$set) {
                $setId = (int) ($set['id'] ?? 0);
                $name = $set['name'] !== null && $set['name'] !== '' ? $set['name'] : null;
                $zone = $set['placement_zone'] !== null && $set['placement_zone'] !== ''
                    ? $set['placement_zone']
                    : null;

                if ($setId <= 0) {
                    $setId = self::create([
                        'computer_set_name' => $name,
                        'computer_set_room_id' => $roomId,
                        'computer_set_placement_zone' => $zone,
                        'computer_set_status' => self::STATUS_ACTIVE,
                    ]);
                    $set['id'] = $setId;
                } else {
                    self::update($setId, [
                        'computer_set_name' => $name,
                        'computer_set_room_id' => $roomId,
                        'computer_set_placement_zone' => $zone,
                        'computer_set_status' => self::STATUS_ACTIVE,
                    ]);
                }

                $keepIds[$setId] = true;
                self::syncMembers($setId, $set['members']);
            }
            unset($set);

            foreach ($existing as $setId => $row) {
                if (isset($keepIds[$setId])) {
                    continue;
                }
                if (($row->computer_set_status ?? null) === self::STATUS_INACTIVE) {
                    continue;
                }
                // Soft-retire sets removed from the layout rather than deleting history.
                self::update((int) $setId, [
                    'computer_set_status' => self::STATUS_INACTIVE,
                ]);
            }

            return self::layoutPayloadForRoom($roomId);
        });
    }
}

