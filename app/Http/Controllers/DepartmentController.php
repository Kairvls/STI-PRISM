<?php

namespace App\Http\Controllers;

use App\Support\Departments;
use App\Support\PropertyAssignments;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $filter = array_key_exists((string) $request->query('filter'), Departments::FILTERS)
            ? (string) $request->query('filter')
            : 'active';
        $readOnly = $this->isSchoolAdminPortal($request);

        return view('admin.departments.index', [
            'departments' => Departments::summary($search, $filter),
            'stats' => Departments::stats(),
            'search' => $search,
            'filter' => $filter,
            'tableReady' => Departments::tableReady(),
            'readOnly' => $readOnly,
            'departmentsIndexRoute' => $readOnly ? 'school-admin.departments.index' : 'admin.departments.index',
            'departmentsBaseUrl' => $readOnly ? url('/school-admin/departments') : url('/admin/departments'),
        ]);
    }

    public function show(Request $request, int $department)
    {
        $row = $this->findDepartment($department);
        ['people' => $people, 'equipment' => $equipment] = Departments::members($department);
        $readOnly = $this->isSchoolAdminPortal($request);

        return response()->json([
            'department' => [
                'id' => (int) $row->department_id,
                'name' => $row->department_name,
                'description' => $row->department_description,
                'archived' => (bool) $row->department_is_archived,
            ],
            'people' => $people->map(function ($person) use ($readOnly) {
                $personId = isset($person->person_id) ? (int) $person->person_id : 0;
                $custodianId = $person->custodian_id ? (int) $person->custodian_id : 0;
                $profileUrl = null;
                if (! $readOnly) {
                    $profileUrl = $personId > 0 && \App\Support\Persons::tableReady()
                        ? route('admin.persons.show', $personId)
                        : ($custodianId > 0 ? PropertyAssignments::route('person', $custodianId) : route('admin.persons.index'));
                }

                return [
                    'id' => $personId > 0 ? $personId : $custodianId,
                    'person_id' => $personId ?: null,
                    'custodian_id' => $custodianId ?: null,
                    'name' => $person->custodian_full_name,
                    'employee_id' => $person->custodian_employee_id,
                    'position' => $person->custodian_position,
                    'email' => $person->custodian_email_address,
                    'contact' => $person->custodian_contact_number,
                    'status' => $person->custodian_status ?: 'Active',
                    'room' => $person->room_name,
                    'held_count' => (int) $person->held_count,
                    'profile_url' => $profileUrl,
                ];
            })->values(),
            'equipment' => $equipment->map(fn ($item) => [
                'id' => (int) $item->equipment_id,
                'name' => $item->equipment_name,
                'asset_tag' => $item->equipment_asset_tag,
                'brand_model' => trim(($item->equipment_brand_name ?? '').' '.($item->equipment_model ?? '')) ?: null,
                'serial' => $item->equipment_serial_number,
                'quantity' => (int) ($item->equipment_quantity ?: 1),
                'category' => $item->equipment_category_name,
                'room' => $item->room_name,
                'condition' => $item->equipment_condition_status,
                'status' => $item->equipment_inventory_status,
                'image_url' => $item->equipment_image ? asset('storage/'.$item->equipment_image) : null,
                'charged' => (int) $item->equipment_department_id === $department,
                'holder' => $item->holder_name,
                'holder_in_department' => $item->holder_id !== null && (int) $item->holder_department_id === $department,
                'issued_at' => $item->assignment_issued_at,
                'profile_url' => url('/maintenance/equipment/view/'.$item->equipment_id),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManageDepartments($request);
        $validated = $this->validateDepartment($request);
        Departments::create($validated);

        return back()->with('success', $validated['department_name'].' was added.');
    }

    public function update(Request $request, int $department)
    {
        $this->ensureCanManageDepartments($request);
        $this->findDepartment($department);
        $validated = $this->validateDepartment($request, $department);
        Departments::update($department, $validated);

        return back()->with('success', 'Department saved.');
    }

    public function archive(Request $request, int $department)
    {
        $this->ensureCanManageDepartments($request);
        $row = $this->findDepartment($department);
        Departments::setArchived($department, true);

        return back()->with('success', $row->department_name.' was archived. People already in it keep it, but it is hidden when adding new people.');
    }

    public function restore(Request $request, int $department)
    {
        $this->ensureCanManageDepartments($request);
        $row = $this->findDepartment($department);
        Departments::setArchived($department, false);

        return back()->with('success', $row->department_name.' is active again.');
    }

    public function destroy(Request $request, int $department)
    {
        $this->ensureCanManageDepartments($request);
        $row = $this->findDepartment($department);

        try {
            Departments::delete($department);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $row->department_name.' was deleted.');
    }

    private function isSchoolAdminPortal(Request $request): bool
    {
        return $request->is('school-admin') || $request->is('school-admin/*');
    }

    private function ensureCanManageDepartments(Request $request): void
    {
        if ($this->isSchoolAdminPortal($request)) {
            abort(403, 'School Administrators can view departments only.');
        }
    }

    private function validateDepartment(Request $request, ?int $departmentId = null): array
    {
        $request->merge(['department_name' => trim((string) $request->input('department_name'))]);

        return $request->validateWithBag('department', [
            'department_name' => [
                'required', 'string', 'max:120',
                Rule::unique('departments_table', 'department_name')->ignore($departmentId, 'department_id'),
            ],
            'department_description' => ['nullable', 'string', 'max:255'],
        ], [
            'department_name.required' => 'Enter the department name.',
            'department_name.unique' => 'That department already exists.',
        ]);
    }

    private function findDepartment(int $departmentId): object
    {
        $row = Departments::find($departmentId);
        abort_unless($row, 404, 'Department not found.');

        return $row;
    }
}
