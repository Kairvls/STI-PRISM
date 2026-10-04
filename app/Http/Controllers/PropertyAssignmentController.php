<?php

namespace App\Http\Controllers;

use App\Support\Custodians;
use App\Support\Departments;
use App\Support\EquipmentViewReturn;
use App\Support\PersonNames;
use App\Support\PropertyAssignments;
use App\Support\RoomCategories;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class PropertyAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->query('view') === 'offices' ? 'offices' : 'people';
        $search = trim((string) $request->query('search', ''));
        $filter = array_key_exists((string) $request->query('filter'), PropertyAssignments::PEOPLE_FILTERS)
            ? (string) $request->query('filter')
            : 'all';
        $departmentId = (int) $request->query('department', 0) ?: null;
        $tableReady = PropertyAssignments::tableReady();

        $rows = match (true) {
            ! $tableReady => collect(),
            $view === 'offices' => PropertyAssignments::roomsSummary($search),
            default => PropertyAssignments::peopleSummary($search, $filter, $departmentId),
        };

        return view('maintenance-personnel.property-assignments.index', [
            'view' => $view,
            'search' => $search,
            'filter' => $filter,
            'departmentId' => $departmentId,
            'departments' => Departments::options($departmentId),
            'rows' => $rows,
            'stats' => PropertyAssignments::stats(),
            'tableReady' => $tableReady,
            'unlinkedReporters' => $tableReady ? Custodians::unlinkedReporterCount() : 0,
        ]);
    }

    public function person(Request $request, int $custodian)
    {
        $person = $this->findPerson($custodian);
        $canReceive = $person->custodian_status === Custodians::STATUS_ACTIVE;
        $itemSearch = trim((string) $request->query('item_search', ''));
        $itemRoomId = (int) $request->query('item_room', 0) ?: null;

        return view('maintenance-personnel.property-assignments.person', [
            'person' => $person,
            'activeItems' => PropertyAssignments::activeForCustodian($custodian),
            'pastItems' => PropertyAssignments::pastForCustodian($custodian),
            'canReceive' => $canReceive,
            'itemSearch' => $itemSearch,
            'itemRoomId' => $itemRoomId,
            'assignableItems' => $canReceive
                ? PropertyAssignments::assignableItems($custodian, $itemSearch, $itemRoomId)
                : collect(),
            'assignableRooms' => $canReceive ? PropertyAssignments::assignableRoomOptions() : collect(),
        ]);
    }

    public function personForm(int $custodian)
    {
        $person = $this->findPerson($custodian);
        $items = PropertyAssignments::activeForCustodian($custodian);

        $pdf = Pdf::loadView('maintenance-personnel.property-assignments.accountability-form-pdf', [
            'person' => $person,
            'items' => $items,
            'preparedBy' => Auth::user()?->user_full_name,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeId = preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) ($person->custodian_employee_id ?: 'person-'.$person->custodian_id));

        return $pdf->stream('property-accountability-'.trim($safeId, '-').'.pdf');
    }

    public function createPerson(Request $request)
    {
        PropertyAssignments::assertCanManage();

        return view('maintenance-personnel.property-assignments.person-form', [
            'person' => null,
            'rooms' => $this->roomOptions(),
            'departments' => Departments::options(),
            'linkableReporters' => Custodians::linkableReporters(),
            'defaultRoomId' => (int) $request->query('room', 0) ?: null,
        ]);
    }

    public function storePerson(Request $request)
    {
        PropertyAssignments::assertCanManage();
        $validated = $this->validatePerson($request);

        if ($duplicate = $this->holdForDuplicateName($request, $validated)) {
            return $duplicate;
        }

        $id = Custodians::create($validated);

        return redirect()
            ->route('maintenance.property-assignments.person', $id)
            ->with('success', trim($validated['custodian_first_name'].' '.$validated['custodian_last_name']).' was added. You can now assign equipment to them.');
    }

    public function editPerson(int $custodian)
    {
        PropertyAssignments::assertCanManage();
        $person = $this->findPerson($custodian);

        return view('maintenance-personnel.property-assignments.person-form', [
            'person' => $person,
            'rooms' => $this->roomOptions(),
            'departments' => Departments::options((int) $person->custodian_department_id ?: null),
            'linkableReporters' => Custodians::linkableReporters($custodian),
            'defaultRoomId' => null,
        ]);
    }

    public function updatePerson(Request $request, int $custodian)
    {
        PropertyAssignments::assertCanManage();
        $this->findPerson($custodian);
        $validated = $this->validatePerson($request, $custodian);

        if ($duplicate = $this->holdForDuplicateName($request, $validated, $custodian)) {
            return $duplicate;
        }

        try {
            Custodians::update($custodian, $validated);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('maintenance.property-assignments.person', $custodian)
            ->with('success', 'Person details saved.');
    }

    public function importReporters()
    {
        PropertyAssignments::assertCanManage();
        $count = Custodians::importReporters();

        return back()->with(
            'success',
            $count > 0
                ? 'Added '.$count.' faculty/staff '.Str::plural('member', $count).' to the people directory.'
                : 'Everyone from the faculty/staff list is already in the directory.'
        );
    }

    public function room(int $room)
    {
        $roomRow = DB::table('rooms_table as room')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'room.room_floor_id')
            ->leftJoin('buildings_table as b', 'b.building_id', '=', 'f.floor_building_id')
            ->where('room.room_id', $room)
            ->first(['room.room_id', 'room.room_name', 'room.room_type', 'f.floor_level', 'b.building_name']);

        abort_unless($roomRow, 404, 'Room not found.');

        return view('maintenance-personnel.property-assignments.room', [
            'room' => $roomRow,
            'custodians' => PropertyAssignments::activeForRoom($room)->groupBy('assignment_custodian_id'),
            'unassignedItems' => PropertyAssignments::unassignedForRoom($room),
            'assignablePeople' => Custodians::assignable(),
            'isStorageRoom' => RoomCategories::isStorageType($roomRow->room_type),
        ]);
    }

    public function store(Request $request, int $equipment)
    {
        PropertyAssignments::assertCanManage();
        $validated = $request->validate([
            'custodian_id' => ['required', 'integer'],
            'document_no' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'custodian_id.required' => 'Choose the accountable person.',
        ]);

        try {
            $assignment = PropertyAssignments::issue($equipment, (int) $validated['custodian_id'], $validated);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect($this->equipmentUrl($request, $equipment))
            ->with('success', 'Property assigned. Document no. '.$assignment->assignment_document_no.'.');
    }

    public function storeBatch(Request $request)
    {
        PropertyAssignments::assertCanManage();
        $validated = $request->validate([
            'custodian_id' => ['required', 'integer'],
            'equipment_ids' => ['required', 'array', 'min:1', 'max:100'],
            'equipment_ids.*' => ['integer'],
            'document_no' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'equipment_ids.required' => 'Select at least one item to assign.',
            'custodian_id.required' => 'Choose the accountable person.',
        ]);

        try {
            $issued = PropertyAssignments::issueMany(
                $validated['equipment_ids'],
                (int) $validated['custodian_id'],
                $validated
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $name = DB::table('custodians_table')->where('custodian_id', $validated['custodian_id'])->value('custodian_full_name');
        $count = $issued->count();

        return back()->with(
            'success',
            'Assigned '.$count.' '.Str::plural('item', $count).' to '.$name
                .'. Document no. '.$issued->first()->assignment_document_no.'.'
        );
    }

    public function returnAssignment(Request $request, int $assignment)
    {
        PropertyAssignments::assertCanManage();
        $validated = $request->validate([
            'return_condition' => ['required', Rule::in(PropertyAssignments::RETURN_CONDITIONS)],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $record = PropertyAssignments::returnAssignment(
                $assignment,
                $validated['return_condition'],
                $validated['return_notes'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect($this->equipmentUrl($request, (int) $record->assignment_equipment_id))
            ->with('success', 'Property returned and the assignment was closed.');
    }

    /**
     * Same first + last name as someone already listed: send the form back with the matches
     * until the user confirms this is a different person.
     */
    private function holdForDuplicateName(Request $request, array $validated, ?int $custodianId = null)
    {
        if ($request->boolean('confirm_duplicate')) {
            return null;
        }

        if ($custodianId) {
            $current = Custodians::nameParts(Custodians::find($custodianId));
            if (PersonNames::matchKey($current['first']).'|'.PersonNames::matchKey($current['last'])
                === PersonNames::matchKey($validated['custodian_first_name']).'|'.PersonNames::matchKey($validated['custodian_last_name'])) {
                return null;
            }
        }

        $matches = Custodians::sameNamePeople(
            $validated['custodian_first_name'],
            $validated['custodian_last_name'],
            $custodianId
        );

        return $matches === []
            ? null
            : back()->withInput()->with('duplicate_people', $matches);
    }

    private function validatePerson(Request $request, ?int $custodianId = null): array
    {
        PersonNames::cleanRequest($request, ['custodian_first_name', 'custodian_middle_name', 'custodian_last_name']);

        return $request->validate([
            'custodian_first_name' => PersonNames::rules(),
            'custodian_middle_name' => PersonNames::rules(false),
            'custodian_last_name' => PersonNames::rules(),
            'custodian_employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('custodians_table', 'custodian_employee_id')->ignore($custodianId, 'custodian_id'),
            ],
            'custodian_position' => ['nullable', 'string', 'max:120'],
            'custodian_department_id' => ['nullable', 'integer', Rule::exists('departments_table', 'department_id')],
            'custodian_room_id' => ['nullable', 'integer', Rule::exists('rooms_table', 'room_id')],
            'custodian_email_address' => ['nullable', 'email', 'max:150'],
            'custodian_contact_number' => ['nullable', 'string', 'max:50'],
            'custodian_reporter_id' => [
                'nullable', 'integer',
                Rule::exists('reporters_table', 'reporter_id'),
                Rule::unique('custodians_table', 'custodian_reporter_id')->ignore($custodianId, 'custodian_id'),
            ],
            'custodian_status' => ['required', Rule::in(Custodians::STATUSES)],
            'custodian_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'custodian_first_name.required' => 'Enter the first name.',
            'custodian_last_name.required' => 'Enter the last name.',
            'custodian_employee_id.unique' => 'Another person in the directory already uses this employee ID.',
            'custodian_reporter_id.unique' => 'That faculty/staff account is already linked to another person.',
        ], [
            'custodian_first_name' => 'first name',
            'custodian_middle_name' => 'middle name',
            'custodian_last_name' => 'last name',
        ]);
    }

    private function roomOptions()
    {
        return DB::table('rooms_table')
            ->when(
                Schema::hasColumn('rooms_table', 'room_is_archived'),
                fn ($q) => $q->where('room_is_archived', false)
            )
            ->where(function ($q) {
                $q->whereNull('room_type')->orWhere('room_type', '!=', RoomCategories::STORAGE_TYPE);
            })
            ->orderBy('room_name')
            ->get(['room_id', 'room_name', 'room_type']);
    }

    private function findPerson(int $custodianId): object
    {
        $person = Custodians::find($custodianId);

        abort_unless($person, 404, 'Person not found.');

        return $person;
    }

    private function equipmentUrl(Request $request, int $equipmentId): string
    {
        $return = $request->input('return');

        return EquipmentViewReturn::viewUrl($equipmentId, is_string($return) ? $return : '');
    }
}
