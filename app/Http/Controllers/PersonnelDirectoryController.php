<?php

namespace App\Http\Controllers;

use App\Support\Departments;
use App\Support\PersonnelDirectory;
use App\Support\PersonNames;
use App\Support\ReporterApprovals;
use App\Support\ReporterImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PersonnelDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $tableReady = PersonnelDirectory::tableReady();
        $search = trim((string) $request->query('search', ''));
        $status = array_key_exists((string) $request->query('status'), PersonnelDirectory::FILTERS)
            ? (string) $request->query('status')
            : 'all';
        $type = in_array($request->query('type'), PersonnelDirectory::TYPES, true) ? (string) $request->query('type') : '';

        $people = $tableReady ? PersonnelDirectory::summary($search, $status, $type) : null;

        return view('maintenance-personnel.personnel-directory.index', [
            'tableReady' => $tableReady,
            'people' => $people,
            'reporterStatuses' => $people ? $this->reporterStatuses($people->getCollection()) : [],
            'stats' => $tableReady ? PersonnelDirectory::stats() : null,
            'departments' => Departments::tableReady()
                ? DB::table('departments_table')->orderBy('department_name')->get(['department_id', 'department_name', 'department_is_archived'])
                : collect(),
            'search' => $search,
            'status' => $status,
            'type' => $type,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePerson($request);

        if ($duplicate = $this->holdForDuplicateName($request, $data)) {
            return $duplicate;
        }

        $result = PersonnelDirectory::save($data, null, Auth::id());

        return back()->with('success', $this->savedMessage($data, $result['deactivated'], 'was added to the directory.'));
    }

    public function update(Request $request, int $personnel)
    {
        $current = PersonnelDirectory::find($personnel);
        abort_unless($current, 404, 'Person not found.');
        $data = $this->validatePerson($request, $personnel);

        if ($duplicate = $this->holdForDuplicateName($request, $data, $current)) {
            return $duplicate;
        }

        $result = PersonnelDirectory::save($data, $personnel, Auth::id());

        return back()->with('success', $this->savedMessage($data, $result['deactivated'], 'was updated.'));
    }

    public function destroy(int $personnel)
    {
        $person = PersonnelDirectory::find($personnel);
        abort_unless($person, 404, 'Person not found.');
        PersonnelDirectory::delete($personnel);

        return back()->with('success', PersonnelDirectory::fullName($person).' was removed from the directory.');
    }

    public function template()
    {
        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values(PersonnelDirectory::IMPORT_FIELDS));
            fputcsv($handle, ['OMC00127F', 'Juan', 'Santos', 'Dela Cruz', 'Faculty', 'Academic Affairs', 'juan.delacruz@ormoc.sti.edu.ph', "\t09171234567", 'Active']);
            fputcsv($handle, ['OMC00128S', 'Maria', '', 'Reyes', 'Staff', 'Registrar', 'maria.reyes@ormoc.sti.edu.ph', "\t09179876543", 'Active']);
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="personnel-directory-template.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validateWithBag('personnelImport', [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'mark_missing_separated' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'Choose a CSV or Excel (.xlsx) file.',
            'file.mimes' => 'Upload a CSV or Excel (.xlsx) file.',
        ]);

        $file = $request->file('file');
        $rows = ReporterImport::parseFile($file->getRealPath(), $file->getClientOriginalName());
        $result = PersonnelDirectory::import($rows, $request->boolean('mark_missing_separated'), Auth::id());

        if (! $result['ok']) {
            throw ValidationException::withMessages(['file' => $result['message']])->errorBag('personnelImport');
        }

        $parts = [];
        foreach (['created' => 'added', 'updated' => 'updated', 'unchanged' => 'unchanged', 'separated' => 'marked Separated'] as $key => $label) {
            if ($result[$key] > 0) {
                $parts[] = number_format($result[$key]).' '.$label;
            }
        }
        $message = 'Directory upload finished: '.($parts !== [] ? implode(', ', $parts) : 'nothing to change').'.';
        if ($result['deactivated'] > 0) {
            $message .= ' '.$result['deactivated'].' reporter '.($result['deactivated'] === 1 ? 'account was' : 'accounts were').' set to Inactive.';
        }

        return back()
            ->with('success', $message)
            ->with('personnel_import_result', $result);
    }

    /**
     * Same first + last name as another entry: reopen the form with the matches until confirmed.
     */
    private function holdForDuplicateName(Request $request, array $data, ?object $current = null)
    {
        if ($request->boolean('confirm_duplicate')) {
            return null;
        }

        $key = fn ($first, $last) => PersonNames::matchKey($first).'|'.PersonNames::matchKey($last);
        if ($current && $key($current->personnel_first_name, $current->personnel_last_name) === $key($data['first_name'], $data['last_name'])) {
            return null;
        }

        $matches = PersonnelDirectory::sameNamePeople($data['first_name'], $data['last_name'], $current?->personnel_id);

        return $matches === []
            ? null
            : back()->withInput()->with('personnel_duplicates', $matches);
    }

    private function validatePerson(Request $request, ?int $personnelId = null): array
    {
        PersonNames::cleanRequest($request, ['first_name', 'middle_name', 'last_name']);

        $request->merge([
            'employee_id' => PersonnelDirectory::normalizeEmployeeId($request->input('employee_id')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validateWithBag('personnel', [
            'employee_id' => [
                'required', 'string', 'max:50', 'regex:'.PersonnelDirectory::EMPLOYEE_ID_PATTERN,
                Rule::unique(PersonnelDirectory::TABLE, 'personnel_employee_id')->ignore($personnelId, 'personnel_id'),
            ],
            'first_name' => PersonNames::rules(),
            'middle_name' => PersonNames::rules(false),
            'last_name' => PersonNames::rules(),
            'type' => ['required', Rule::in(PersonnelDirectory::TYPES)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments_table', 'department_id')],
            'email' => ['nullable', 'email', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(PersonnelDirectory::STATUSES)],
        ], [
            'employee_id.regex' => 'Employee ID must look like OMC00127F (Faculty) or OMC00127S (Staff).',
            'employee_id.unique' => 'That employee ID is already in the directory.',
        ]);

        $errors = [];
        if (PersonnelDirectory::typeFromEmployeeId($data['employee_id']) !== $data['type']) {
            $errors['type'] = $data['type'] === 'Staff'
                ? 'Staff employee IDs end with S.'
                : 'Faculty employee IDs end with F.';
        }
        if ($taken = PersonnelDirectory::numberTaken($data['employee_id'], $personnelId)) {
            $errors['employee_id'] = 'Employee number already belongs to '.PersonnelDirectory::fullName($taken).' ('.$taken->personnel_employee_id.').';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors)->errorBag('personnel');
        }

        return $data;
    }

    /**
     * Reporter account status per employee number for the listed people.
     *
     * @return array<string, string>
     */
    private function reporterStatuses($people): array
    {
        $numbers = collect($people)->pluck('personnel_employee_number')->filter()->unique()->values();
        if ($numbers->isEmpty()) {
            return [];
        }

        $statuses = [];
        DB::table('reporters_table')
            ->whereNotNull('reporter_employee_id')
            ->get(['reporter_employee_id', 'reporter_status'])
            ->each(function ($reporter) use ($numbers, &$statuses) {
                $number = ReporterApprovals::employeeNumber((string) $reporter->reporter_employee_id);
                if ($number && $numbers->contains($number) && ($statuses[$number] ?? null) !== 'Active') {
                    $statuses[$number] = (string) $reporter->reporter_status;
                }
            });

        return $statuses;
    }

    private function savedMessage(array $data, int $deactivated, string $suffix): string
    {
        $message = trim($data['first_name'].' '.$data['last_name']).' '.$suffix;
        if ($deactivated > 0) {
            $message .= ' Their reporter account was set to Inactive because they are Separated.';
        }

        return $message;
    }
}
