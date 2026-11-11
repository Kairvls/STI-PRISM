<?php

namespace App\Http\Controllers;

use App\Support\ComputerSets;
use App\Support\PropertyAssignments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ComputerSetController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $roomId = (int) $request->query('room', 0) ?: null;
        $status = (string) $request->query('status', ComputerSets::STATUS_ACTIVE);
        if (! in_array($status, [ComputerSets::STATUS_ACTIVE, ComputerSets::STATUS_INACTIVE, 'all'], true)) {
            $status = ComputerSets::STATUS_ACTIVE;
        }
        $name = trim((string) $request->query('name', ''));

        return view('maintenance-personnel.computer-sets.index', [
            'tableReady' => ComputerSets::tablesReady(),
            'sets' => ComputerSets::list($search, $roomId, $status, $name !== '' ? $name : null),
            'search' => $search,
            'roomId' => $roomId,
            'status' => $status,
            'name' => $name,
            'nameOptions' => ComputerSets::nameOptions($roomId, $status),
            'rooms' => $this->roomOptions(),
        ]);
    }

    public function show(int $id)
    {
        $set = ComputerSets::find($id);
        abort_unless($set, 404);

        $roomId = (int) ($set->computer_set_room_id ?? 0);
        $members = ComputerSets::members($id);
        $roomAvailable = $roomId > 0
            ? ComputerSets::availableEquipmentForRoom($roomId, $id)
            : collect();
        $rolesNeedingReplacement = $members
            ->filter(fn ($row) => ! empty($row->needs_replacement))
            ->pluck('member_role')
            ->filter()
            ->unique()
            ->values();
        $storageCandidates = collect();
        foreach ($rolesNeedingReplacement as $role) {
            $storageCandidates = $storageCandidates->concat(
                ComputerSets::storageReplacementCandidates((string) $role)
            );
        }
        $storageCandidates = $storageCandidates->unique('equipment_id')->values();
        $available = $roomAvailable
            ->concat($storageCandidates)
            ->unique('equipment_id')
            ->values();

        $memberIds = $members->pluck('equipment_id')->map(fn ($id) => (int) $id)->all();
        $holdersByEquipment = PropertyAssignments::activeByEquipment($memberIds);
        $owners = collect($holdersByEquipment)
            ->groupBy('custodian_id')
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'custodian_id' => (int) ($first['custodian_id'] ?? 0),
                    'name' => $first['name'] ?? null,
                    'employee_id' => $first['employee_id'] ?? null,
                    'position' => $first['position'] ?? null,
                    'person_url' => $first['person_url'] ?? null,
                    'held_count' => $rows->count(),
                    'document_no' => $first['document_no'] ?? null,
                    'since' => $first['since'] ?? null,
                ];
            })
            ->sortByDesc('held_count')
            ->values();

        return view('maintenance-personnel.computer-sets.show', [
            'set' => $set,
            'members' => $members,
            'memberHistory' => ComputerSets::memberHistory($id),
            'available' => $available,
            'storageByRole' => $storageCandidates->groupBy(
                fn ($row) => $row->suggested_role ?? ComputerSets::inferRoleFromName($row->equipment_name) ?? 'other'
            ),
            'rooms' => $this->roomOptions(),
            'setOwners' => $owners,
            'assignedMemberCount' => count($holdersByEquipment),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(ComputerSets::tablesReady(), 503);

        $data = $request->validate([
            'computer_set_name' => ['nullable', 'string', 'max:120'],
            'computer_set_room_id' => ['required', 'integer', 'min:1'],
            'computer_set_placement_zone' => ['nullable', 'string', 'max:120'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['integer', 'min:1'],
            'equipment_roles' => ['nullable', 'array'],
            'equipment_roles.*' => ['nullable', 'string', Rule::in(array_keys(ComputerSets::ROLES))],
        ]);

        $roles = $data['equipment_roles'] ?? [];
        $rolesById = [];
        foreach (($data['equipment_ids'] ?? []) as $equipmentId) {
            $equipmentId = (int) $equipmentId;
            $rolesById[$equipmentId] = $roles[(string) $equipmentId] ?? $roles[$equipmentId] ?? null;
        }

        try {
            ComputerSets::assertUniqueRoles($rolesById);

            $setId = ComputerSets::create([
                'computer_set_name' => $data['computer_set_name'] ?? null,
                'computer_set_room_id' => (int) $data['computer_set_room_id'],
                'computer_set_placement_zone' => $data['computer_set_placement_zone'] ?? null,
            ]);

            ComputerSets::syncMembers($setId, $rolesById);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect(ComputerSets::url('/'.$setId))
            ->with('success', 'Computer set created with tag '.ComputerSets::find($setId)?->computer_set_tag.'.');
    }

    public function update(Request $request, int $id)
    {
        abort_unless(ComputerSets::find($id), 404);

        $data = $request->validate([
            'computer_set_name' => ['nullable', 'string', 'max:120'],
            'computer_set_room_id' => ['required', 'integer', 'min:1'],
            'computer_set_placement_zone' => ['nullable', 'string', 'max:120'],
            'computer_set_status' => ['required', Rule::in([ComputerSets::STATUS_ACTIVE, ComputerSets::STATUS_INACTIVE])],
        ]);

        ComputerSets::update($id, [
            'computer_set_name' => $data['computer_set_name'] ?? null,
            'computer_set_room_id' => (int) $data['computer_set_room_id'],
            'computer_set_placement_zone' => $data['computer_set_placement_zone'] ?? null,
            'computer_set_status' => $data['computer_set_status'],
        ]);

        return redirect(ComputerSets::url('/'.$id))
            ->with('success', 'Computer set details updated.');
    }

    public function syncMembers(Request $request, int $id)
    {
        abort_unless(ComputerSets::find($id), 404);

        $data = $request->validate([
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['integer', 'min:1'],
            'equipment_roles' => ['nullable', 'array'],
            'equipment_roles.*' => ['nullable', 'string', Rule::in(array_keys(ComputerSets::ROLES))],
        ]);

        $rolesById = [];
        foreach (($data['equipment_ids'] ?? []) as $equipmentId) {
            $equipmentId = (int) $equipmentId;
            $role = $data['equipment_roles'][(string) $equipmentId]
                ?? $data['equipment_roles'][$equipmentId]
                ?? null;
            $rolesById[$equipmentId] = $role;
        }

        try {
            ComputerSets::syncMembers($id, $rolesById);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect(ComputerSets::url('/'.$id))
            ->with('success', 'Computer set equipment updated.');
    }

    public function addMember(Request $request, int $id)
    {
        abort_unless(ComputerSets::find($id), 404);

        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'min:1'],
            'member_role' => ['nullable', 'string', Rule::in(array_keys(ComputerSets::ROLES))],
        ]);

        try {
            ComputerSets::addMember($id, (int) $data['equipment_id'], $data['member_role'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect(ComputerSets::url('/'.$id))
            ->with('success', 'Equipment added to computer set.');
    }

    public function removeMember(Request $request, int $id)
    {
        abort_unless(ComputerSets::find($id), 404);

        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'min:1'],
        ]);

        ComputerSets::removeMember($id, (int) $data['equipment_id'], null, true);

        return redirect(ComputerSets::url('/'.$id))
            ->with('success', 'Equipment removed from the computer set and placed in this room’s Holding area (still in the room for interior layout).');
    }

    public function replaceMember(Request $request, int $id)
    {
        abort_unless(ComputerSets::find($id), 404);

        $data = $request->validate([
            'old_equipment_id' => ['required', 'integer', 'min:1'],
            'new_equipment_id' => ['required', 'integer', 'min:1', 'different:old_equipment_id'],
        ]);

        try {
            ComputerSets::replaceMember(
                $id,
                (int) $data['old_equipment_id'],
                (int) $data['new_equipment_id']
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect(ComputerSets::url('/'.$id))
            ->with('success', 'Part replaced from storage. The old unit was returned to storage as Replaced (swap complete). The swap is recorded in part history.');
    }

    public function availableEquipment(Request $request)
    {
        $roomId = (int) $request->query('room', 0);
        $excludeSetId = (int) $request->query('set', 0) ?: null;
        $role = trim((string) $request->query('role', ''));
        $fromStorage = $request->boolean('storage');

        if ($fromStorage) {
            return response()->json(
                ComputerSets::storageReplacementCandidates($role !== '' ? $role : null)->values()
            );
        }

        if ($roomId <= 0) {
            return response()->json([]);
        }

        return response()->json(
            ComputerSets::availableEquipmentForRoom($roomId, $excludeSetId)->values()
        );
    }

    public function availableZones(Request $request)
    {
        $roomId = (int) $request->query('room', 0);

        if ($roomId <= 0) {
            return response()->json([]);
        }

        return response()->json(ComputerSets::zonesForRoom($roomId));
    }

    private function roomOptions()
    {
        if (! Schema::hasTable('rooms_table')) {
            return collect();
        }

        return DB::table('rooms_table as r')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'r.room_floor_id')
            ->where(function ($q) {
                if (Schema::hasColumn('rooms_table', 'room_is_archived')) {
                    $q->where('r.room_is_archived', false)->orWhereNull('r.room_is_archived');
                }
            })
            ->orderBy('f.floor_level')
            ->orderBy('r.room_name')
            ->select('r.room_id', 'r.room_name', 'f.floor_level', 'r.room_type')
            ->get();
    }
}
