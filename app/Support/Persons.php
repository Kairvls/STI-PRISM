<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Campus-wide person master (faculty/staff identity).
 * Reporter / property custodian / system user are overlays linked by person_id.
 */
class Persons
{
    public const STATUS_ACTIVE = 'Active';

    public const STATUS_INACTIVE = 'Inactive';

    public const FILTERS = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'all' => 'All',
    ];

    public const TYPES = ['Faculty', 'Staff'];

    public const BASES = ['Full time', 'Part time'];

    public const EMPLOYEE_ID_REGEX = '/^OMC[0-9]{5}[FS]$/i';

    public static function tableReady(): bool
    {
        return Schema::hasTable('persons_table');
    }

    public static function hasPersonLink(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'person_id');
    }

    public static function normalizeEmployeeId(?string $employeeId): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $employeeId));
    }

    public static function composeFullName(string $first, ?string $middle, string $last): string
    {
        return trim(preg_replace('/\s+/', ' ', $first.' '.trim((string) $middle).' '.$last));
    }

    public static function find(int $personId): ?object
    {
        if (! self::tableReady() || $personId <= 0) {
            return null;
        }

        return DB::table('persons_table as p')
            ->leftJoin('departments_table as d', 'd.department_id', '=', 'p.person_department_id')
            ->where('p.person_id', $personId)
            ->select('p.*', 'd.department_name')
            ->first();
    }

    public static function findByEmployeeId(string $employeeId): ?object
    {
        $employeeId = self::normalizeEmployeeId($employeeId);
        if ($employeeId === '' || ! self::tableReady()) {
            return null;
        }

        return DB::table('persons_table as p')
            ->leftJoin('departments_table as d', 'd.department_id', '=', 'p.person_department_id')
            ->whereRaw('UPPER(TRIM(p.person_employee_id)) = ?', [$employeeId])
            ->select('p.*', 'd.department_name')
            ->first();
    }

    public static function stats(): array
    {
        if (! self::tableReady()) {
            return ['active' => 0, 'inactive' => 0, 'total' => 0];
        }

        return [
            'active' => (int) DB::table('persons_table')->where('person_status', self::STATUS_ACTIVE)->count(),
            'inactive' => (int) DB::table('persons_table')->where('person_status', self::STATUS_INACTIVE)->count(),
            'total' => (int) DB::table('persons_table')->count(),
        ];
    }

    public static function list(?string $search = null, string $filter = 'active', ?int $departmentId = null): Collection
    {
        if (! self::tableReady()) {
            return collect();
        }

        $query = DB::table('persons_table as p')
            ->leftJoin('departments_table as d', 'd.department_id', '=', 'p.person_department_id')
            ->select('p.*', 'd.department_name')
            ->orderBy('p.person_full_name');

        if ($filter === 'active') {
            $query->where('p.person_status', self::STATUS_ACTIVE);
        } elseif ($filter === 'inactive') {
            $query->where('p.person_status', self::STATUS_INACTIVE);
        }

        if ($departmentId) {
            $query->where('p.person_department_id', $departmentId);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('p.person_full_name', 'like', $like)
                    ->orWhere('p.person_employee_id', 'like', $like)
                    ->orWhere('p.person_email_address', 'like', $like)
                    ->orWhere('p.person_contact_number', 'like', $like)
                    ->orWhere('d.department_name', 'like', $like);
            });
        }

        return $query->get();
    }

    public static function search(
        string $query,
        int $limit = 20,
        bool $activeOnly = true,
        bool $unassignedOnly = false,
        bool $unassignedFirst = false
    ): Collection {
        if (! self::tableReady()) {
            return collect();
        }

        $query = trim($query);
        $builder = DB::table('persons_table as p')
            ->leftJoin('departments_table as d', 'd.department_id', '=', 'p.person_department_id')
            ->select(
                'p.person_id',
                'p.person_employee_id',
                'p.person_full_name',
                'p.person_first_name',
                'p.person_middle_name',
                'p.person_last_name',
                'p.person_employment_type',
                'p.person_employment_basis',
                'p.person_department_id',
                'p.person_email_address',
                'p.person_contact_number',
                'p.person_status',
                'd.department_name'
            )
            ->limit(max(1, min($limit, 50)));

        if ($activeOnly) {
            $builder->where('p.person_status', self::STATUS_ACTIVE);
        }

        if ($unassignedOnly) {
            $builder->whereNull('p.person_department_id');
        }

        if ($query !== '') {
            $like = '%'.$query.'%';
            $builder->where(function ($q) use ($like) {
                $q->where('p.person_full_name', 'like', $like)
                    ->orWhere('p.person_employee_id', 'like', $like)
                    ->orWhere('p.person_email_address', 'like', $like);
            });
        }

        if ($unassignedFirst && ! $unassignedOnly) {
            $builder->orderByRaw('CASE WHEN p.person_department_id IS NULL THEN 0 ELSE 1 END');
        }

        $builder->orderBy('p.person_full_name');

        return $builder->get()->map(function ($row) {
            $row->unassigned = empty($row->person_department_id);
            $row->label = implode(' · ', array_filter([
                $row->person_employee_id,
                $row->unassigned ? 'No department' : $row->department_name,
                $row->person_employment_type,
            ]));

            return $row;
        });
    }

    public static function overlays(int $personId): array
    {
        $out = [
            'reporter_id' => null,
            'custodian_id' => null,
            'user_id' => null,
        ];

        if (self::hasPersonLink('reporters_table')) {
            $out['reporter_id'] = DB::table('reporters_table')->where('person_id', $personId)->value('reporter_id');
        }
        if (self::hasPersonLink('custodians_table')) {
            $out['custodian_id'] = DB::table('custodians_table')->where('person_id', $personId)->value('custodian_id');
        }
        if (self::hasPersonLink('users_table')) {
            $out['user_id'] = DB::table('users_table')->where('person_id', $personId)->value('user_id');
        }

        return $out;
    }

    /**
     * Damage/maintenance reports filed under this person's employee ID.
     */
    public static function reportsFor(int $personId, int $limit = 20): Collection
    {
        $person = self::find($personId);
        if (! $person || ! Schema::hasTable('reports_table')) {
            return collect();
        }

        $employeeId = self::normalizeEmployeeId($person->person_employee_id);
        if ($employeeId === '') {
            return collect();
        }

        $query = DB::table('reports_table')
            ->leftJoin('equipment_table', 'reports_table.report_equipment_id', '=', 'equipment_table.equipment_id')
            ->leftJoin('rooms_table', 'reports_table.report_room_id', '=', 'rooms_table.room_id')
            ->where('reports_table.report_reporter_employee_id', $employeeId)
            ->select([
                'reports_table.report_id',
                'reports_table.report_current_status',
                'reports_table.report_urgency_level',
                'reports_table.report_submitted_at',
                'reports_table.report_suggested_issue',
                'reports_table.report_unlisted_equipment_name',
                'equipment_table.equipment_name',
                'rooms_table.room_name',
            ]);

        if (Schema::hasColumn('reports_table', 'report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('reports_table.report_is_archived')
                    ->orWhere('reports_table.report_is_archived', 0);
            });
        }

        return $query
            ->orderByDesc('reports_table.report_submitted_at')
            ->orderByDesc('reports_table.report_id')
            ->limit(max(1, $limit))
            ->get();
    }

    public static function reportsCount(int $personId): int
    {
        $person = self::find($personId);
        if (! $person || ! Schema::hasTable('reports_table')) {
            return 0;
        }

        $employeeId = self::normalizeEmployeeId($person->person_employee_id);
        if ($employeeId === '') {
            return 0;
        }

        $query = DB::table('reports_table')
            ->where('report_reporter_employee_id', $employeeId);

        if (Schema::hasColumn('reports_table', 'report_is_archived')) {
            $query->where(function ($q) {
                $q->whereNull('report_is_archived')->orWhere('report_is_archived', 0);
            });
        }

        return (int) $query->count();
    }

    /**
     * Equipment currently assigned to this person as property custodian.
     */
    public static function heldEquipmentFor(int $personId): Collection
    {
        if (! PropertyAssignments::tableReady()) {
            return collect();
        }

        $overlays = self::overlays($personId);
        $custodianId = $overlays['custodian_id'] ? (int) $overlays['custodian_id'] : null;

        if (! $custodianId) {
            $person = self::find($personId);
            if ($person) {
                $custodianId = Custodians::idForEmployee((string) $person->person_employee_id);
            }
        }

        if (! $custodianId) {
            return collect();
        }

        return PropertyAssignments::activeForCustodian($custodianId);
    }

    public static function create(array $data): int
    {
        if (! self::tableReady()) {
            throw new RuntimeException('Persons table is not ready. Run migrations.');
        }

        $employeeId = self::normalizeEmployeeId($data['person_employee_id'] ?? $data['employee_id'] ?? '');
        if ($employeeId === '' || ! preg_match(self::EMPLOYEE_ID_REGEX, $employeeId)) {
            throw new RuntimeException('Employee ID must look like OMC00127F (Faculty) or OMC00127S (Staff).');
        }

        if (self::findByEmployeeId($employeeId)) {
            throw new RuntimeException('That employee ID already exists in Faculty & Staff.');
        }

        $first = PersonNames::clean($data['person_first_name'] ?? $data['first_name'] ?? '');
        $middle = PersonNames::clean($data['person_middle_name'] ?? $data['middle_name'] ?? '');
        $last = PersonNames::clean($data['person_last_name'] ?? $data['last_name'] ?? '');
        $full = self::composeFullName($first, $middle, $last);
        $type = $data['person_employment_type'] ?? $data['type'] ?? null;
        $expectedSuffix = $type === 'Staff' ? 'S' : 'F';
        if ($type && ! str_ends_with($employeeId, $expectedSuffix)) {
            throw new RuntimeException(
                $type === 'Staff'
                    ? 'Staff employee IDs must end with S (example: OMC00127S).'
                    : 'Faculty employee IDs must end with F (example: OMC00127F).'
            );
        }

        $now = now();

        return (int) DB::table('persons_table')->insertGetId([
            'person_employee_id' => $employeeId,
            'person_first_name' => $first !== '' ? $first : null,
            'person_middle_name' => $middle !== '' ? $middle : null,
            'person_last_name' => $last !== '' ? $last : null,
            'person_full_name' => $full !== '' ? $full : $employeeId,
            'person_employment_type' => $type,
            'person_employment_basis' => $data['person_employment_basis'] ?? $data['employment_basis'] ?? null,
            'person_department_id' => ! empty($data['person_department_id'] ?? $data['department_id'] ?? null)
                ? (int) ($data['person_department_id'] ?? $data['department_id'])
                : null,
            'person_email_address' => filled($data['person_email_address'] ?? $data['email'] ?? null)
                ? trim((string) ($data['person_email_address'] ?? $data['email']))
                : null,
            'person_contact_number' => PhoneNumber::normalizeForStorage(
                $data['person_contact_number'] ?? $data['contact'] ?? null
            ),
            'person_status' => $data['person_status'] ?? $data['status'] ?? self::STATUS_ACTIVE,
            'person_created_by' => Auth::id(),
            'person_created_at' => $now,
            'person_updated_at' => $now,
        ], 'person_id');
    }

    public static function update(int $personId, array $data): void
    {
        $person = self::find($personId);
        if (! $person) {
            throw new RuntimeException('Faculty/staff record not found.');
        }

        $employeeId = array_key_exists('person_employee_id', $data) || array_key_exists('employee_id', $data)
            ? self::normalizeEmployeeId($data['person_employee_id'] ?? $data['employee_id'] ?? '')
            : $person->person_employee_id;

        if ($employeeId === '' || ! preg_match(self::EMPLOYEE_ID_REGEX, $employeeId)) {
            throw new RuntimeException('Employee ID must look like OMC00127F (Faculty) or OMC00127S (Staff).');
        }

        $other = self::findByEmployeeId($employeeId);
        if ($other && (int) $other->person_id !== $personId) {
            throw new RuntimeException('That employee ID already exists in Faculty & Staff.');
        }

        $first = array_key_exists('person_first_name', $data) || array_key_exists('first_name', $data)
            ? PersonNames::clean($data['person_first_name'] ?? $data['first_name'] ?? '')
            : (string) $person->person_first_name;
        $middle = array_key_exists('person_middle_name', $data) || array_key_exists('middle_name', $data)
            ? PersonNames::clean($data['person_middle_name'] ?? $data['middle_name'] ?? '')
            : (string) $person->person_middle_name;
        $last = array_key_exists('person_last_name', $data) || array_key_exists('last_name', $data)
            ? PersonNames::clean($data['person_last_name'] ?? $data['last_name'] ?? '')
            : (string) $person->person_last_name;

        $type = $data['person_employment_type'] ?? $data['type'] ?? $person->person_employment_type;
        $expectedSuffix = $type === 'Staff' ? 'S' : 'F';
        if ($type && ! str_ends_with($employeeId, $expectedSuffix)) {
            throw new RuntimeException(
                $type === 'Staff'
                    ? 'Staff employee IDs must end with S (example: OMC00127S).'
                    : 'Faculty employee IDs must end with F (example: OMC00127F).'
            );
        }

        $update = [
            'person_employee_id' => $employeeId,
            'person_first_name' => $first !== '' ? $first : null,
            'person_middle_name' => $middle !== '' ? $middle : null,
            'person_last_name' => $last !== '' ? $last : null,
            'person_full_name' => self::composeFullName($first, $middle, $last) ?: $employeeId,
            'person_employment_type' => $type,
            'person_updated_at' => now(),
        ];

        if (array_key_exists('person_employment_basis', $data) || array_key_exists('employment_basis', $data)) {
            $update['person_employment_basis'] = $data['person_employment_basis'] ?? $data['employment_basis'] ?? null;
        }
        if (array_key_exists('person_department_id', $data) || array_key_exists('department_id', $data)) {
            $dept = $data['person_department_id'] ?? $data['department_id'] ?? null;
            $update['person_department_id'] = $dept ? (int) $dept : null;
        }
        if (array_key_exists('person_email_address', $data) || array_key_exists('email', $data)) {
            $email = $data['person_email_address'] ?? $data['email'] ?? null;
            $update['person_email_address'] = filled($email) ? trim((string) $email) : null;
        }
        if (array_key_exists('person_contact_number', $data) || array_key_exists('contact', $data)) {
            $update['person_contact_number'] = PhoneNumber::normalizeForStorage(
                $data['person_contact_number'] ?? $data['contact'] ?? null
            );
        }
        if (array_key_exists('person_status', $data) || array_key_exists('status', $data)) {
            $update['person_status'] = $data['person_status'] ?? $data['status'] ?? self::STATUS_ACTIVE;
        }

        DB::table('persons_table')->where('person_id', $personId)->update($update);
        self::syncOverlayIdentity($personId);
    }

    public static function assignDepartment(int $personId, int $departmentId): void
    {
        if (! self::find($personId)) {
            throw new RuntimeException('Faculty/staff record not found.');
        }

        DB::table('persons_table')
            ->where('person_id', $personId)
            ->update([
                'person_department_id' => $departmentId,
                'person_updated_at' => now(),
            ]);

        if (self::hasPersonLink('custodians_table') && Schema::hasColumn('custodians_table', 'custodian_department_id')) {
            DB::table('custodians_table')
                ->where('person_id', $personId)
                ->update([
                    'custodian_department_id' => $departmentId,
                    'custodian_updated_at' => now(),
                ]);
        }

        if (self::hasPersonLink('reporters_table') && Schema::hasColumn('reporters_table', 'reporter_department_id')) {
            DB::table('reporters_table')
                ->where('person_id', $personId)
                ->update(['reporter_department_id' => $departmentId]);
        }
    }

    /**
     * Push shared identity fields onto linked reporter / custodian / user rows.
     */
    public static function syncOverlayIdentity(int $personId): void
    {
        $person = self::find($personId);
        if (! $person) {
            return;
        }

        if (self::hasPersonLink('reporters_table')) {
            $payload = [
                'reporter_employee_id' => $person->person_employee_id,
                'reporter_full_name' => $person->person_full_name,
                'reporter_email_address' => $person->person_email_address,
                'reporter_contact_number' => $person->person_contact_number,
            ];
            if (Schema::hasColumn('reporters_table', 'reporter_first_name')) {
                $payload['reporter_first_name'] = $person->person_first_name;
                $payload['reporter_middle_name'] = $person->person_middle_name;
                $payload['reporter_last_name'] = $person->person_last_name;
            }
            if (Schema::hasColumn('reporters_table', 'reporter_employment_type')) {
                $payload['reporter_employment_type'] = $person->person_employment_type;
            }
            if (Schema::hasColumn('reporters_table', 'reporter_employment_basis')) {
                $payload['reporter_employment_basis'] = $person->person_employment_basis;
            }
            if (Schema::hasColumn('reporters_table', 'reporter_department_id')) {
                $payload['reporter_department_id'] = $person->person_department_id;
            }
            DB::table('reporters_table')->where('person_id', $personId)->update($payload);
        }

        if (self::hasPersonLink('custodians_table')) {
            $payload = [
                'custodian_employee_id' => $person->person_employee_id,
                'custodian_full_name' => $person->person_full_name,
                'custodian_email_address' => $person->person_email_address,
                'custodian_contact_number' => $person->person_contact_number,
                'custodian_updated_at' => now(),
            ];
            if (Schema::hasColumn('custodians_table', 'custodian_first_name')) {
                $payload['custodian_first_name'] = $person->person_first_name;
                $payload['custodian_middle_name'] = $person->person_middle_name;
                $payload['custodian_last_name'] = $person->person_last_name;
            }
            if (Schema::hasColumn('custodians_table', 'custodian_position')) {
                $payload['custodian_position'] = $person->person_employment_type;
            }
            if (Schema::hasColumn('custodians_table', 'custodian_employment_basis')) {
                $payload['custodian_employment_basis'] = $person->person_employment_basis;
            }
            if (Schema::hasColumn('custodians_table', 'custodian_department_id')) {
                $payload['custodian_department_id'] = $person->person_department_id;
            }
            DB::table('custodians_table')->where('person_id', $personId)->update($payload);
        }

        if (self::hasPersonLink('users_table')) {
            DB::table('users_table')->where('person_id', $personId)->update([
                'user_employee_id' => $person->person_employee_id,
                'user_full_name' => $person->person_full_name,
                'user_email_address' => $person->person_email_address,
                'user_contact_number' => $person->person_contact_number,
            ]);
        }
    }

    public static function ensureFromIdentity(array $data): int
    {
        $employeeId = self::normalizeEmployeeId($data['employee_id'] ?? $data['person_employee_id'] ?? '');
        $existing = $employeeId !== '' ? self::findByEmployeeId($employeeId) : null;
        if ($existing) {
            return (int) $existing->person_id;
        }

        return self::create($data);
    }
}
