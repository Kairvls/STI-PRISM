<?php

namespace App\Support;

use App\Models\PropertyAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * People who can be accountable for property. Anyone can be added here;
 * linking to a faculty/staff reporter account is optional.
 */
class Custodians
{
    public const STATUS_ACTIVE = 'Active';

    public const STATUSES = ['Active', 'On Leave', 'Inactive'];

    public static function tableReady(): bool
    {
        return Schema::hasTable('custodians_table');
    }

    public static function find(int $custodianId): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        return self::withDepartment(DB::table('custodians_table as person'))
            ->leftJoin('rooms_table as home', 'home.room_id', '=', 'person.custodian_room_id')
            ->leftJoin('reporters_table as account', 'account.reporter_id', '=', 'person.custodian_reporter_id')
            ->where('person.custodian_id', $custodianId)
            ->first([
                'person.*',
                self::departmentNameColumn(),
                'home.room_name as home_room_name',
                'account.reporter_employee_id as linked_employee_id',
                'account.reporter_full_name as linked_reporter_name',
            ]);
    }

    /**
     * Active people for the "accountable person" dropdowns.
     */
    public static function assignable(): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        return self::withDepartment(DB::table('custodians_table as person'))
            ->where('person.custodian_status', self::STATUS_ACTIVE)
            ->orderBy('person.custodian_full_name')
            ->get([
                'person.custodian_id',
                'person.custodian_employee_id',
                'person.custodian_full_name',
                'person.custodian_position',
                self::departmentNameColumn(),
            ]);
    }

    /**
     * Joins the department onto a query over "custodians_table as person".
     */
    public static function withDepartment($query)
    {
        if (Departments::tableReady()) {
            $query->leftJoin('departments_table as dept', 'dept.department_id', '=', 'person.custodian_department_id');
        }

        return $query;
    }

    /**
     * Select expression exposing the department name as custodian_department.
     */
    public static function departmentNameColumn()
    {
        return Departments::tableReady()
            ? 'dept.department_name as custodian_department'
            : DB::raw('NULL as custodian_department');
    }

    /**
     * Label for dropdowns: "Maria Santos · Registrar Staff · OMC00128F".
     */
    public static function optionLabel(object $person): string
    {
        return implode(' · ', array_filter([
            $person->custodian_full_name,
            $person->custodian_position ?? null,
            $person->custodian_employee_id ?? null,
        ]));
    }

    /**
     * People already in the directory with the same first + last name.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sameNamePeople(string $first, string $last, ?int $exceptCustodianId = null): array
    {
        if (! self::tableReady() || ! self::hasNameColumns()) {
            return [];
        }

        $ids = PersonNames::duplicates(
            'custodians_table', 'custodian_first_name', 'custodian_last_name',
            $first, $last, 'custodian_id', $exceptCustodianId
        )->pluck('custodian_id')->all();

        if ($ids === []) {
            return [];
        }

        return self::withDepartment(DB::table('custodians_table as person'))
            ->whereIn('person.custodian_id', $ids)
            ->orderBy('person.custodian_id')
            ->get(['person.*', self::departmentNameColumn()])
            ->map(fn ($person) => [
                'id' => (int) $person->custodian_id,
                'name' => $person->custodian_full_name,
                'employee_id' => $person->custodian_employee_id,
                'department' => $person->custodian_department,
                'position' => $person->custodian_position,
                'status' => $person->custodian_status,
                'items' => self::activeItemCount((int) $person->custodian_id),
                'url' => route('maintenance.property-assignments.person', $person->custodian_id),
            ])
            ->all();
    }

    public static function activeItemCount(int $custodianId): int
    {
        if (! Schema::hasTable('property_assignments_table')) {
            return 0;
        }

        return DB::table('property_assignments_table')
            ->where('assignment_custodian_id', $custodianId)
            ->where('assignment_status', PropertyAssignment::STATUS_ACTIVE)
            ->count();
    }

    public static function create(array $data): int
    {
        $now = now();

        return (int) DB::table('custodians_table')->insertGetId(self::columns($data) + [
            'custodian_created_by' => Auth::id(),
            'custodian_created_at' => $now,
            'custodian_updated_at' => $now,
        ]);
    }

    public static function update(int $custodianId, array $data): void
    {
        $columns = self::columns($data);

        if ($columns['custodian_status'] === 'Inactive') {
            $held = self::activeItemCount($custodianId);
            if ($held > 0) {
                throw new RuntimeException(
                    "This person still holds {$held} ".($held === 1 ? 'item' : 'items')
                    .'. Return or transfer them before marking the person inactive.'
                );
            }
        }

        DB::table('custodians_table')
            ->where('custodian_id', $custodianId)
            ->update($columns + ['custodian_updated_at' => now()]);
    }

    /**
     * Adds faculty/staff reporters that are not in the directory yet.
     */
    public static function importReporters(): int
    {
        if (! self::tableReady() || ! Schema::hasTable('reporters_table')) {
            return 0;
        }

        $hasType = ReporterImport::hasTypeColumn();
        $now = now();

        $missing = DB::table('reporters_table')
            ->whereNotIn('reporter_id', DB::table('custodians_table')->whereNotNull('custodian_reporter_id')->select('custodian_reporter_id'))
            ->get();

        foreach ($missing as $reporter) {
            $parts = trim((string) ($reporter->reporter_first_name ?? '')) !== ''
                ? [
                    'first' => trim((string) $reporter->reporter_first_name),
                    'middle' => trim((string) ($reporter->reporter_middle_name ?? '')),
                    'last' => trim((string) ($reporter->reporter_last_name ?? '')),
                ]
                : ReporterImport::splitFullName($reporter->reporter_full_name);
            $nameColumns = self::hasNameColumns()
                ? [
                    'custodian_first_name' => $parts['first'] ?: null,
                    'custodian_middle_name' => $parts['middle'] ?: null,
                    'custodian_last_name' => $parts['last'] ?: null,
                ]
                : [];

            DB::table('custodians_table')->insert($nameColumns + [
                'custodian_reporter_id' => $reporter->reporter_id,
                'custodian_employee_id' => $reporter->reporter_employee_id,
                'custodian_full_name' => $reporter->reporter_full_name,
                'custodian_position' => $hasType ? ($reporter->reporter_employment_type ?? null) : null,
                'custodian_email_address' => $reporter->reporter_email_address ?? null,
                'custodian_contact_number' => PhoneNumber::normalizeForStorage($reporter->reporter_contact_number ?? null),
                'custodian_status' => ($reporter->reporter_status ?? '') === 'Active' ? self::STATUS_ACTIVE : 'Inactive',
                'custodian_created_by' => Auth::id(),
                'custodian_created_at' => $now,
                'custodian_updated_at' => $now,
            ]);
        }

        return $missing->count();
    }

    public static function unlinkedReporterCount(): int
    {
        if (! self::tableReady() || ! Schema::hasTable('reporters_table')) {
            return 0;
        }

        return DB::table('reporters_table')
            ->whereNotIn('reporter_id', DB::table('custodians_table')->whereNotNull('custodian_reporter_id')->select('custodian_reporter_id'))
            ->count();
    }

    /**
     * Reporters that can be linked to a directory entry (not linked elsewhere).
     */
    public static function linkableReporters(?int $exceptCustodianId = null): Collection
    {
        if (! Schema::hasTable('reporters_table')) {
            return collect();
        }

        return DB::table('reporters_table')
            ->whereNotIn('reporter_id', DB::table('custodians_table')
                ->whereNotNull('custodian_reporter_id')
                ->when($exceptCustodianId, fn ($q) => $q->where('custodian_id', '!=', $exceptCustodianId))
                ->select('custodian_reporter_id'))
            ->orderBy('reporter_full_name')
            ->get(['reporter_id', 'reporter_employee_id', 'reporter_full_name']);
    }

    /**
     * Directory entry for a faculty/staff employee ID (used by the public report form).
     */
    public static function idForEmployee(string $employeeId): ?int
    {
        if (! self::tableReady() || trim($employeeId) === '') {
            return null;
        }

        $reporterId = DB::table('reporters_table')
            ->where('reporter_employee_id', $employeeId)
            ->where('reporter_status', 'Active')
            ->value('reporter_id');

        if (! $reporterId) {
            return null;
        }

        $id = DB::table('custodians_table')
            ->where('custodian_status', '!=', 'Inactive')
            ->where(function ($q) use ($reporterId, $employeeId) {
                $q->where('custodian_reporter_id', $reporterId)
                    ->orWhere('custodian_employee_id', $employeeId);
            })
            ->orderByRaw('custodian_reporter_id = ? DESC', [$reporterId])
            ->value('custodian_id');

        return $id ? (int) $id : null;
    }

    public static function hasNameColumns(): bool
    {
        static $has = null;

        return $has ??= Schema::hasColumn('custodians_table', 'custodian_first_name');
    }

    /**
     * First / middle / last for the form, splitting the full name for rows saved before the split columns existed.
     *
     * @return array{first:string, middle:string, last:string}
     */
    public static function nameParts(?object $person): array
    {
        if (! $person) {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }
        if (trim((string) ($person->custodian_first_name ?? '')) !== '') {
            return [
                'first' => (string) $person->custodian_first_name,
                'middle' => (string) ($person->custodian_middle_name ?? ''),
                'last' => (string) ($person->custodian_last_name ?? ''),
            ];
        }

        return ReporterImport::splitFullName($person->custodian_full_name ?? '');
    }

    private static function columns(array $data): array
    {
        $clean = fn ($key) => ($value = trim((string) ($data[$key] ?? ''))) !== '' ? $value : null;
        $status = in_array($data['custodian_status'] ?? null, self::STATUSES, true)
            ? $data['custodian_status']
            : self::STATUS_ACTIVE;

        $first = $clean('custodian_first_name');
        $middle = $clean('custodian_middle_name');
        $last = $clean('custodian_last_name');
        $name = $first !== null || $last !== null
            ? ['custodian_full_name' => ReporterImport::composeFullName((string) $first, (string) $middle, (string) $last)]
            : ['custodian_full_name' => $clean('custodian_full_name')];
        if (self::hasNameColumns()) {
            $name += [
                'custodian_first_name' => $first,
                'custodian_middle_name' => $middle,
                'custodian_last_name' => $last,
            ];
        }

        return $name + [
            'custodian_employee_id' => $clean('custodian_employee_id'),
            'custodian_position' => $clean('custodian_position'),
            'custodian_department_id' => ! empty($data['custodian_department_id']) ? (int) $data['custodian_department_id'] : null,
            'custodian_room_id' => ! empty($data['custodian_room_id']) ? (int) $data['custodian_room_id'] : null,
            'custodian_email_address' => $clean('custodian_email_address'),
            'custodian_contact_number' => PhoneNumber::normalizeForStorage($clean('custodian_contact_number')),
            'custodian_reporter_id' => ! empty($data['custodian_reporter_id']) ? (int) $data['custodian_reporter_id'] : null,
            'custodian_status' => $status,
            'custodian_notes' => $clean('custodian_notes'),
        ];
    }
}
