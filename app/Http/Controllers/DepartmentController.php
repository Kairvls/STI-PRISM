<?php

namespace App\Http\Controllers;

use App\Support\Departments;
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

        return view('maintenance-personnel.departments.index', [
            'departments' => Departments::summary($search, $filter),
            'stats' => Departments::stats(),
            'search' => $search,
            'filter' => $filter,
            'tableReady' => Departments::tableReady(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDepartment($request);
        Departments::create($validated);

        return back()->with('success', $validated['department_name'].' was added.');
    }

    public function update(Request $request, int $department)
    {
        $this->findDepartment($department);
        $validated = $this->validateDepartment($request, $department);
        Departments::update($department, $validated);

        return back()->with('success', 'Department saved.');
    }

    public function archive(int $department)
    {
        $row = $this->findDepartment($department);
        Departments::setArchived($department, true);

        return back()->with('success', $row->department_name.' was archived. People already in it keep it, but it is hidden when adding new people.');
    }

    public function restore(int $department)
    {
        $row = $this->findDepartment($department);
        Departments::setArchived($department, false);

        return back()->with('success', $row->department_name.' is active again.');
    }

    public function destroy(int $department)
    {
        $row = $this->findDepartment($department);

        try {
            Departments::delete($department);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $row->department_name.' was deleted.');
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
