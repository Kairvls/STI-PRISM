<?php

namespace App\Http\Controllers;

use App\Support\Departments;
use App\Support\PersonImport;
use App\Support\PersonNames;
use App\Support\Persons;
use App\Support\PhoneNumber;
use App\Support\PropertyAssignments;
use App\Support\ReporterImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $filter = array_key_exists((string) $request->query('filter'), Persons::FILTERS)
            ? (string) $request->query('filter')
            : 'active';
        $departmentId = (int) $request->query('department', 0) ?: null;

        return view('admin.persons.index', [
            'tableReady' => Persons::tableReady(),
            'people' => Persons::list($search, $filter, $departmentId),
            'stats' => Persons::stats(),
            'search' => $search,
            'filter' => $filter,
            'departmentId' => $departmentId,
            'departments' => Departments::options($departmentId),
        ]);
    }

    public function show(int $id)
    {
        $person = Persons::find($id);
        abort_unless($person, 404);

        $overlays = Persons::overlays($id);
        $reports = Persons::reportsFor($id, 50);
        $heldEquipment = Persons::heldEquipmentFor($id);
        $reportsTotal = Persons::reportsCount($id);

        $resolvedCustodianId = $overlays['custodian_id']
            ? (int) $overlays['custodian_id']
            : \App\Support\Custodians::idForEmployee((string) $person->person_employee_id);

        return view('admin.persons.show', [
            'person' => $person,
            'overlays' => $overlays,
            'departments' => Departments::options((int) ($person->person_department_id ?: 0) ?: null),
            'reporterUrl' => $overlays['reporter_id']
                ? url('/admin/reporters?search='.urlencode((string) $person->person_employee_id))
                : null,
            'reporterHistoryUrl' => $overlays['reporter_id']
                ? url('/admin/reporters?history='.(int) $overlays['reporter_id'])
                : null,
            'custodianUrl' => $resolvedCustodianId
                ? PropertyAssignments::route('person', $resolvedCustodianId)
                : null,
            'userUrl' => $overlays['user_id']
                ? url('/admin/users?search='.urlencode((string) $person->person_employee_id))
                : null,
            'reports' => $reports,
            'reportsTotal' => $reportsTotal,
            'heldEquipment' => $heldEquipment,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activeOnly = ! $request->boolean('include_inactive');
        $unassignedOnly = $request->boolean('unassigned_only');
        $unassignedFirst = $request->boolean('unassigned_first') || $unassignedOnly;

        return response()->json(
            Persons::search(
                $q,
                (int) $request->query('limit', 20),
                $activeOnly,
                $unassignedOnly,
                $unassignedFirst
            )->values()
        );
    }

    public function store(Request $request)
    {
        abort_unless(Persons::tableReady(), 503);

        PersonNames::cleanRequest($request, ['first_name', 'middle_name', 'last_name']);
        $this->normalizeContactInput($request);
        $validated = $this->validatePerson($request);

        try {
            $id = Persons::create($validated);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.persons.show', $id)
            ->with('success', 'Faculty/staff added to the campus directory.');
    }

    public function update(Request $request, int $id)
    {
        abort_unless(Persons::find($id), 404);

        PersonNames::cleanRequest($request, ['first_name', 'middle_name', 'last_name']);
        $this->normalizeContactInput($request);
        $validated = $this->validatePerson($request, $id);

        try {
            Persons::update($id, $validated);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.persons.show', $id)
            ->with('success', 'Faculty/staff details saved.');
    }

    public function downloadImportTemplate()
    {
        $filename = 'person-import-template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values(PersonImport::FIELDS));
            fputcsv($handle, ['Faculty', 'OMC00130F', 'John', 'Michael', 'Smith', 'Full time', 'Academic Affairs', 'john.smith@ormoc.sti.edu.ph', "\t09171234567"]);
            fputcsv($handle, ['Staff', 'OMC00131S', 'Sarah', '', 'Connor', 'Part time', 'Registrar', 'sarah.connor@ormoc.sti.edu.ph', "\t09179876543"]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function previewImport(Request $request)
    {
        abort_unless(Persons::tableReady(), 503);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
        ]);

        $file = $request->file('file');
        $rows = ReporterImport::parseFile($file->getRealPath(), $file->getClientOriginalName());

        if (count($rows) < 2) {
            return response()->json([
                'ok' => false,
                'message' => 'The file needs a header row and at least one faculty/staff row.',
            ], 422);
        }

        $mapping = ReporterImport::suggestMapping($rows[0]);
        $missing = collect(['employee_id', 'first_name', 'last_name'])
            ->filter(fn ($field) => ($mapping[$field] ?? '') === '')
            ->map(fn ($field) => PersonImport::FIELDS[$field])
            ->values();

        if ($missing->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Missing column(s): '.$missing->implode(', ').'. Download the sample file to see the expected headers.',
            ], 422);
        }

        $plan = PersonImport::plan($rows, $mapping);

        return response()->json([
            'ok' => true,
            'mapping' => $mapping,
            'people' => collect($plan['rows'])->sortBy(fn ($row) => $row['error'] === null ? 1 : 0)->take(50)->values()->all(),
            'total' => count($plan['rows']),
            'ready' => $plan['ready'],
            'skipped' => $plan['skipped'],
            'unknown_departments' => $plan['unknown_departments'],
        ]);
    }

    public function import(Request $request)
    {
        abort_unless(Persons::tableReady(), 503);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
            'mapping' => 'required|string',
        ]);

        $mapping = json_decode($request->mapping, true);
        if (! is_array($mapping)) {
            return back()->with('error', 'Column mapping is invalid.');
        }

        $file = $request->file('file');
        $rows = ReporterImport::parseFile($file->getRealPath(), $file->getClientOriginalName());

        if (count($rows) < 2) {
            return back()->with('error', 'The file needs a header row and at least one faculty/staff row.');
        }

        $plan = PersonImport::plan($rows, $mapping);
        $now = now();

        $payloads = collect($plan['rows'])
            ->whereNull('error')
            ->map(function (array $row) use ($now) {
                $first = $row['first_name'];
                $middle = $row['middle_name'];
                $last = $row['last_name'];

                return [
                    'person_employee_id' => $row['employee_id'],
                    'person_first_name' => $first,
                    'person_middle_name' => $middle !== '' ? $middle : null,
                    'person_last_name' => $last,
                    'person_full_name' => Persons::composeFullName($first, $middle, $last),
                    'person_employment_type' => $row['type'],
                    'person_employment_basis' => $row['employment_basis'] ?? null,
                    'person_department_id' => $row['department_id'] ?? null,
                    'person_email_address' => $row['email_address'] !== '' ? $row['email_address'] : null,
                    'person_contact_number' => $row['contact_number'] !== ''
                        ? PhoneNumber::normalizeForStorage($row['contact_number'])
                        : null,
                    'person_status' => Persons::STATUS_ACTIVE,
                    'person_created_at' => $now,
                    'person_updated_at' => $now,
                ];
            })
            ->values();

        DB::transaction(function () use ($payloads) {
            foreach ($payloads->chunk(200) as $chunk) {
                DB::table('persons_table')->insert($chunk->values()->all());
            }
        });

        $errors = collect($plan['rows'])
            ->whereNotNull('error')
            ->map(fn ($row) => 'Row '.$row['line'].': '.$row['error'])
            ->values()
            ->all();

        return back()->with('import_result', [
            'created' => $payloads->count(),
            'skipped' => $plan['skipped'],
            'errors' => array_slice($errors, 0, 20),
            'unknown_departments' => $plan['unknown_departments'],
        ]);
    }

    public function assignDepartment(Request $request, int $department)
    {
        abort_unless(Departments::tableReady(), 503);

        $data = $request->validate([
            'mode' => ['required', Rule::in(['link'])],
            'person_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $personId = (int) $data['person_id'];
            abort_unless($personId > 0 && Persons::find($personId), 404);
            Persons::assignDepartment($personId, $department);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Faculty/staff assigned to this department.');
    }

    private function normalizeContactInput(Request $request): void
    {
        if ($request->has('contact')) {
            $request->merge([
                'contact' => PhoneNumber::normalizeForStorage($request->input('contact')),
            ]);
        }
    }

    private function validatePerson(Request $request, ?int $personId = null): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(Persons::TYPES)],
            'employee_id' => ['required', 'string', 'regex:'.Persons::EMPLOYEE_ID_REGEX],
            'first_name' => PersonNames::rules(),
            'middle_name' => PersonNames::rules(false),
            'last_name' => PersonNames::rules(),
            'employment_basis' => ['nullable', Rule::in(Persons::BASES)],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact' => PhoneNumber::rules(),
            'status' => ['nullable', Rule::in([Persons::STATUS_ACTIVE, Persons::STATUS_INACTIVE])],
        ], [
            'employee_id.regex' => 'Employee ID must look like OMC00127F (Faculty) or OMC00127S (Staff).',
        ]);
    }
}
