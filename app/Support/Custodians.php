<?php

namespace App\Support;

use App\Models\PropertyAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * People who can be accountable for property or borrow equipment.
 * Campus faculty/staff linking is optional; External people (outside school) can be registered too.
 */
class Custodians
{
    public const STATUS_ACTIVE = 'Active';

    public const STATUSES = ['Active', 'Inactive'];

    public const AFFILIATION_CAMPUS = 'Campus';

    public const AFFILIATION_EXTERNAL = 'External';

    public const AFFILIATIONS = ['Campus', 'External'];

    public static function tableReady(): bool
    {
        return Schema::hasTable('custodians_table');
    }

    public static function find(int $custodianId): ?object
    {
        if (! self::tableReady()) {
            return null;
        }

        $person = self::withDepartment(DB::table('custodians_table as person'))
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

        if ($person) {
            self::hydrateFromCampusDirectory($person);
            self::hydrateRoomFromDepartment($person);
        }

        return $person;
    }

    /**
     * When a property person is linked to Faculty & Staff, prefer campus directory
     * identity for display and backfill drifted overlay columns.
     */
    public static function hydrateFromCampusDirectory(object $person): void
    {
        if (! Schema::hasColumn('custodians_table', 'person_id')) {
            return;
        }

        $personId = (int) ($person->person_id ?? 0);
        if ($personId < 1 || ! Persons::tableReady()) {
            return;
        }

        $campus = Persons::find($personId);
        if (! $campus) {
            return;
        }

        $patch = [];

        $campusDeptId = ! empty($campus->person_department_id) ? (int) $campus->person_department_id : null;
        if ((int) ($person->custodian_department_id ?? 0) !== (int) ($campusDeptId ?? 0)) {
            $person->custodian_department_id = $campusDeptId;
            $patch['custodian_department_id'] = $campusDeptId;
        }
        if (! empty($campus->department_name)) {
            $person->custodian_department = $campus->department_name;
        } elseif ($campusDeptId === null) {
            $person->custodian_department = null;
        }

        if ((string) ($person->custodian_employment_basis ?? '') !== (string) ($campus->person_employment_basis ?? '')) {
            $person->custodian_employment_basis = $campus->person_employment_basis;
            $patch['custodian_employment_basis'] = $campus->person_employment_basis;
        }

        if ((string) ($person->custodian_employee_id ?? '') !== (string) ($campus->person_employee_id ?? '')) {
            $person->custodian_employee_id = $campus->person_employee_id;
            $patch['custodian_employee_id'] = $campus->person_employee_id;
        }
        if ((string) ($person->custodian_full_name ?? '') !== (string) ($campus->person_full_name ?? '')) {
            $person->custodian_full_name = $campus->person_full_name;
            $patch['custodian_full_name'] = $campus->person_full_name;
        }
        if (Schema::hasColumn('custodians_table', 'custodian_first_name')) {
            foreach (['first_name', 'middle_name', 'last_name'] as $part) {
                $cKey = 'custodian_'.$part;
                $pKey = 'person_'.$part;
                if ((string) ($person->{$cKey} ?? '') !== (string) ($campus->{$pKey} ?? '')) {
                    $person->{$cKey} = $campus->{$pKey};
                    $patch[$cKey] = $campus->{$pKey};
                }
            }
        }
        if (Schema::hasColumn('custodians_table', 'custodian_position')
            && (string) ($person->custodian_position ?? '') !== (string) ($campus->person_employment_type ?? '')) {
            $person->custodian_position = $campus->person_employment_type;
            $patch['custodian_position'] = $campus->person_employment_type;
        }
        if ((string) ($person->custodian_email_address ?? '') !== (string) ($campus->person_email_address ?? '')) {
            $person->custodian_email_address = $campus->person_email_address;
            $patch['custodian_email_address'] = $campus->person_email_address;
        }
        if ((string) ($person->custodian_contact_number ?? '') !== (string) ($campus->person_contact_number ?? '')) {
            $person->custodian_contact_number = $campus->person_contact_number;
            $patch['custodian_contact_number'] = $campus->person_contact_number;
        }

        if ($patch !== []) {
            $patch['custodian_updated_at'] = now();
            DB::table('custodians_table')
                ->where('custodian_id', (int) $person->custodian_id)
                ->update($patch);
        }
    }

    /**
     * When personal home room is blank, use the department's linked office room.
     */
    public static function hydrateRoomFromDepartment(object $person): void
    {
        if (! empty($person->custodian_room_id) || ! empty($person->home_room_name)) {
            return;
        }

        $departmentId = (int) ($person->custodian_department_id ?? 0);
        if ($departmentId < 1 || ! RoomDepartments::ready()) {
            return;
        }

        $room = RoomDepartments::primaryForDepartment($departmentId);
        if (! $room) {
            return;
        }

        $person->custodian_room_id = (int) $room['id'];
        $person->home_room_name = (string) $room['name'];

        DB::table('custodians_table')
            ->where('custodian_id', (int) $person->custodian_id)
            ->where(function ($q) {
                $q->whereNull('custodian_room_id')->orWhere('custodian_room_id', 0);
            })
            ->update([
                'custodian_room_id' => (int) $room['id'],
                'custodian_updated_at' => now(),
            ]);
    }

    /**
     * Whether this person_id can be attached to the given custodian (unique person_id).
     */
    public static function canAttachPersonId(int $personId, ?int $custodianId = null): bool
    {
        if (! Schema::hasColumn('custodians_table', 'person_id')) {
            return false;
        }

        $owner = DB::table('custodians_table')
            ->where('person_id', $personId)
            ->value('custodian_id');

        if (! $owner) {
            return true;
        }

        return $custodianId !== null && (int) $owner === (int) $custodianId;
    }

    /**
     * Active people for the "accountable person" dropdowns.
     */
    public static function assignable(): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $columns = [
            'person.custodian_id',
            'person.custodian_employee_id',
            'person.custodian_full_name',
            'person.custodian_position',
            'person.custodian_room_id',
            'person.custodian_department_id',
            self::departmentNameColumn(),
        ];
        if (Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            $columns[] = 'person.custodian_affiliation';
        }
        if (Schema::hasColumn('custodians_table', 'custodian_organization')) {
            $columns[] = 'person.custodian_organization';
        }

        $query = self::withDepartment(DB::table('custodians_table as person'))
            ->where('person.custodian_status', self::STATUS_ACTIVE);

        if (Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            $query->orderByRaw("CASE WHEN COALESCE(person.custodian_affiliation, 'Campus') = 'External' THEN 1 ELSE 0 END");
        }

        return $query->orderBy('person.custodian_full_name')->get($columns);
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
     * Label for dropdowns: "Maria Santos · Registrar Staff · OMC00128F"
     * or "Juan Dela Cruz · External · Acme Corp".
     */
    public static function optionLabel(object $person): string
    {
        $affiliation = (string) ($person->custodian_affiliation ?? self::AFFILIATION_CAMPUS);
        if ($affiliation === self::AFFILIATION_EXTERNAL) {
            return implode(' · ', array_filter([
                $person->custodian_full_name,
                'External',
                $person->custodian_organization ?? null,
                $person->custodian_position ?? null,
            ]));
        }

        return implode(' · ', array_filter([
            $person->custodian_full_name,
            $person->custodian_position ?? null,
            $person->custodian_employee_id ?? null,
        ]));
    }

    public static function isExternal(?object $person): bool
    {
        return (string) ($person->custodian_affiliation ?? self::AFFILIATION_CAMPUS) === self::AFFILIATION_EXTERNAL;
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
                'url' => PropertyAssignments::route('person', $person->custodian_id),
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

        // 1A: when picked from Faculty & Staff, link only — do not push form identity to persons.
        $pickedPersonId = ! empty($data['person_id']) ? (int) $data['person_id'] : null;
        $personId = $pickedPersonId;
        if ($personId && Persons::tableReady() && Persons::find($personId)) {
            if (self::canAttachPersonId($personId)) {
                $data['person_id'] = $personId;
            }
        } else {
            // New campus row without a pick: create/link master once, then identity is owned by Faculty & Staff.
            $personId = self::writeThroughCampusIdentity($data);
            if ($personId && self::canAttachPersonId($personId)) {
                $data['person_id'] = $personId;
            }
        }

        $id = (int) DB::table('custodians_table')->insertGetId(self::columns($data) + [
            'custodian_created_by' => Auth::id(),
            'custodian_created_at' => $now,
            'custodian_updated_at' => $now,
        ]);

        if ($personId) {
            Persons::syncOverlayIdentity($personId);
        }

        return $id;
    }

    public static function update(int $custodianId, array $data): void
    {
        $existing = DB::table('custodians_table')
            ->where('custodian_id', $custodianId)
            ->first(['person_id', 'custodian_affiliation']);

        if ($existing && ! array_key_exists('custodian_affiliation', $data)
            && Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            $data['custodian_affiliation'] = $existing->custodian_affiliation;
        }

        $linkedPersonId = $existing && ! empty($existing->person_id) ? (int) $existing->person_id : null;
        $affiliation = (string) ($data['custodian_affiliation'] ?? $existing->custodian_affiliation ?? self::AFFILIATION_CAMPUS);
        $isExternal = $affiliation === self::AFFILIATION_EXTERNAL;

        // 1A: linked campus people — Property may only change role fields; identity comes from Faculty & Staff.
        if ($linkedPersonId && ! $isExternal && Persons::tableReady()) {
            $columns = self::roleColumns($data);
            $columns['person_id'] = $linkedPersonId;

            if (($columns['custodian_status'] ?? null) === 'Inactive') {
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

            Persons::syncOverlayIdentity($linkedPersonId);

            return;
        }

        // Unlinked campus or external: allow local identity edit; campus may create/link master once.
        $personId = $isExternal ? null : self::writeThroughCampusIdentity($data, $linkedPersonId);
        if ($personId && self::canAttachPersonId($personId, $custodianId)) {
            $data['person_id'] = $personId;
        }

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

        if ($personId) {
            Persons::syncOverlayIdentity($personId);
        }
    }

    /**
     * Role-only columns editable on Property Assignment when linked to Faculty & Staff.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function roleColumns(array $data): array
    {
        $clean = fn ($key) => ($value = trim((string) ($data[$key] ?? ''))) !== '' ? $value : null;
        $status = in_array($data['custodian_status'] ?? null, self::STATUSES, true)
            ? $data['custodian_status']
            : self::STATUS_ACTIVE;

        $out = [
            'custodian_room_id' => ! empty($data['custodian_room_id']) ? (int) $data['custodian_room_id'] : null,
            'custodian_reporter_id' => ! empty($data['custodian_reporter_id']) ? (int) $data['custodian_reporter_id'] : null,
            'custodian_status' => $status,
            'custodian_notes' => $clean('custodian_notes'),
        ];

        if (Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            $affiliation = in_array($data['custodian_affiliation'] ?? null, self::AFFILIATIONS, true)
                ? $data['custodian_affiliation']
                : self::AFFILIATION_CAMPUS;
            $out['custodian_affiliation'] = $affiliation;
        }
        if (Schema::hasColumn('custodians_table', 'custodian_organization')) {
            $out['custodian_organization'] = $clean('custodian_organization');
        }

        return $out;
    }

    /**
     * Push campus identity to Faculty & Staff; external people stay local-only.
     * Used for first-time campus create/link only (1A: later edits stay in Faculty & Staff).
     *
     * @param  array<string, mixed>  $data
     */
    public static function writeThroughCampusIdentity(array $data, ?int $existingPersonId = null): ?int
    {
        if (! Persons::tableReady() || ! Schema::hasColumn('custodians_table', 'person_id')) {
            return $existingPersonId;
        }

        return Persons::writeThroughFromCustodian($data, $existingPersonId);
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
     * Prefers the campus-linked / newest row when duplicate custodians share an employee ID.
     */
    public static function idForEmployee(string $employeeId): ?int
    {
        $ids = self::idsForEmployee($employeeId);

        return $ids[0] ?? null;
    }

    /**
     * All active custodian rows that belong to this employee (reporter, campus person, or employee ID).
     * Used so held-equipment lookups still work when legacy duplicate custodian rows exist.
     *
     * @return list<int>
     */
    public static function idsForEmployee(string $employeeId): array
    {
        if (! self::tableReady() || trim($employeeId) === '') {
            return [];
        }

        $normalized = strtoupper(trim($employeeId));
        $reporter = Schema::hasTable('reporters_table')
            ? DB::table('reporters_table')
                ->whereRaw('UPPER(TRIM(reporter_employee_id)) = ?', [$normalized])
                ->where('reporter_status', 'Active')
                ->first(['reporter_id', 'person_id'])
            : null;

        if (! $reporter) {
            return [];
        }

        $reporterId = (int) $reporter->reporter_id;
        $personId = (int) ($reporter->person_id ?? 0);
        $hasPersonCol = Schema::hasColumn('custodians_table', 'person_id');

        $query = DB::table('custodians_table')
            ->where('custodian_status', '!=', 'Inactive')
            ->where(function ($q) use ($reporterId, $normalized, $personId, $hasPersonCol) {
                $q->where('custodian_reporter_id', $reporterId)
                    ->orWhereRaw('UPPER(TRIM(custodian_employee_id)) = ?', [$normalized]);
                if ($hasPersonCol && $personId > 0) {
                    $q->orWhere('person_id', $personId);
                }
            });

        if ($hasPersonCol && $personId > 0) {
            $query->orderByRaw('CASE WHEN person_id = ? THEN 0 ELSE 1 END', [$personId]);
        }
        $query->orderByRaw('CASE WHEN custodian_reporter_id = ? THEN 0 ELSE 1 END', [$reporterId])
            ->orderByDesc('custodian_id');

        return $query
            ->pluck('custodian_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
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

        $affiliation = in_array($data['custodian_affiliation'] ?? null, self::AFFILIATIONS, true)
            ? $data['custodian_affiliation']
            : self::AFFILIATION_CAMPUS;

        $out = $name + [
            'custodian_employee_id' => $clean('custodian_employee_id'),
            'custodian_position' => $clean('custodian_position'),
            'custodian_employment_basis' => $clean('custodian_employment_basis'),
            'custodian_department_id' => ! empty($data['custodian_department_id']) ? (int) $data['custodian_department_id'] : null,
            'custodian_room_id' => ! empty($data['custodian_room_id']) ? (int) $data['custodian_room_id'] : null,
            'custodian_email_address' => $clean('custodian_email_address'),
            'custodian_contact_number' => PhoneNumber::normalizeForStorage($clean('custodian_contact_number')),
            'custodian_reporter_id' => ! empty($data['custodian_reporter_id']) ? (int) $data['custodian_reporter_id'] : null,
            'custodian_status' => $status,
            'custodian_notes' => $clean('custodian_notes'),
        ];

        if (Schema::hasColumn('custodians_table', 'custodian_affiliation')) {
            $out['custodian_affiliation'] = $affiliation;
        }
        if (Schema::hasColumn('custodians_table', 'custodian_organization')) {
            $out['custodian_organization'] = $clean('custodian_organization');
        }

        if (Schema::hasColumn('custodians_table', 'person_id') && ! empty($data['person_id'])) {
            $out['person_id'] = (int) $data['person_id'];
        }

        return $out;
    }
}
