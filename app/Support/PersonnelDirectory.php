<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Official campus faculty/staff masterlist used to verify reporter applications.
 */
class PersonnelDirectory
{
    public const TABLE = 'personnel_directory_table';

    public const TYPES = ['Faculty', 'Staff'];

    public const STATUS_ACTIVE = 'Active';

    public const STATUS_ON_LEAVE = 'On leave';

    public const STATUS_SEPARATED = 'Separated';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_ON_LEAVE, self::STATUS_SEPARATED];

    public const FILTERS = [
        'all' => 'All',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_ON_LEAVE => 'On leave',
        self::STATUS_SEPARATED => 'Separated',
    ];

    public const EMPLOYEE_ID_PATTERN = '/^OMC\d{4,5}[FS]$/';

    public const VERDICT_VERIFIED = 'verified';

    public const VERDICT_REVIEW = 'review';

    public const VERDICT_NOT_FOUND = 'not_found';

    public const VERDICT_INACTIVE = 'inactive';

    public const IMPORT_FIELDS = [
        'employee_id' => 'Employee ID',
        'first_name' => 'First name',
        'middle_name' => 'Middle name',
        'last_name' => 'Last name',
        'type' => 'Type',
        'department' => 'Department',
        'email' => 'Email address',
        'contact' => 'Contact number',
        'status' => 'Status',
    ];

    private const REQUIRED_IMPORT_FIELDS = ['employee_id', 'first_name', 'last_name'];

    /** Checks whose mismatch stops an application from counting as verified. */
    private const BLOCKING_CHECKS = ['employee_id', 'type', 'name', 'email'];

    private static ?bool $empty = null;

    public static function tableReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public static function query()
    {
        return DB::table(self::TABLE);
    }

    public static function isEmpty(): bool
    {
        if (self::$empty === null) {
            self::$empty = ! self::tableReady() || ! self::query()->exists();
        }

        return self::$empty;
    }

    public static function normalizeEmployeeId(?string $employeeId): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $employeeId)));
    }

    public static function typeFromEmployeeId(string $employeeId): ?string
    {
        $id = self::normalizeEmployeeId($employeeId);

        return match (substr($id, -1)) {
            'F' => 'Faculty',
            'S' => 'Staff',
            default => null,
        };
    }

    public static function normalizeName(?string $value): string
    {
        $value = Str::lower(Str::ascii((string) $value));
        $value = preg_replace('/[^a-z]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public static function fullName(object $person): string
    {
        return ReporterImport::composeFullName(
            (string) $person->personnel_first_name,
            (string) ($person->personnel_middle_name ?? ''),
            (string) $person->personnel_last_name
        );
    }

    public static function summary(string $search = '', string $status = 'all', string $type = ''): LengthAwarePaginator
    {
        $query = self::query()
            ->leftJoin('departments_table as d', 'd.department_id', '=', self::TABLE.'.personnel_department_id')
            ->select(self::TABLE.'.*', 'd.department_name');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('personnel_employee_id', 'LIKE', $like)
                    ->orWhere('personnel_first_name', 'LIKE', $like)
                    ->orWhere('personnel_last_name', 'LIKE', $like)
                    ->orWhereRaw("CONCAT(personnel_first_name, ' ', personnel_last_name) LIKE ?", [$like])
                    ->orWhere('personnel_email', 'LIKE', $like);
            });
        }

        if ($status !== 'all' && in_array($status, self::STATUSES, true)) {
            $query->where('personnel_status', $status);
        }

        if (in_array($type, self::TYPES, true)) {
            $query->where('personnel_type', $type);
        }

        return $query
            ->orderBy('personnel_last_name')
            ->orderBy('personnel_first_name')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return array{total:int, active:int, faculty:int, staff:int, separated:int, on_leave:int, updated_at:?string}
     */
    public static function stats(): array
    {
        $rows = self::query()
            ->selectRaw('personnel_status, personnel_type, COUNT(*) as total')
            ->groupBy('personnel_status', 'personnel_type')
            ->get();

        $stats = ['total' => 0, 'active' => 0, 'faculty' => 0, 'staff' => 0, 'separated' => 0, 'on_leave' => 0];
        foreach ($rows as $row) {
            $count = (int) $row->total;
            $stats['total'] += $count;
            if ($row->personnel_status === self::STATUS_ACTIVE) {
                $stats['active'] += $count;
                $stats[$row->personnel_type === 'Staff' ? 'staff' : 'faculty'] += $count;
            } elseif ($row->personnel_status === self::STATUS_SEPARATED) {
                $stats['separated'] += $count;
            } else {
                $stats['on_leave'] += $count;
            }
        }
        $stats['updated_at'] = self::query()->max('personnel_updated_at');

        return $stats;
    }

    public static function find(int $personnelId): ?object
    {
        return self::query()->where('personnel_id', $personnelId)->first();
    }

    /**
     * Directory entries with the same first + last name.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sameNamePeople(string $first, string $last, ?int $ignoreId = null): array
    {
        if (! self::tableReady()) {
            return [];
        }

        $ids = PersonNames::duplicates(
            self::TABLE, 'personnel_first_name', 'personnel_last_name',
            $first, $last, 'personnel_id', $ignoreId
        )->pluck('personnel_id')->all();

        if ($ids === []) {
            return [];
        }

        return self::query()
            ->leftJoin('departments_table as d', 'd.department_id', '=', self::TABLE.'.personnel_department_id')
            ->whereIn('personnel_id', $ids)
            ->orderBy('personnel_id')
            ->get([self::TABLE.'.*', 'd.department_name'])
            ->map(fn ($person) => [
                'id' => (int) $person->personnel_id,
                'name' => self::fullName($person),
                'employee_id' => $person->personnel_employee_id,
                'type' => $person->personnel_type,
                'department' => $person->department_name,
                'status' => $person->personnel_status,
                'url' => route('maintenance.personnel-directory.index', ['search' => $person->personnel_employee_id]),
            ])
            ->all();
    }

    public static function numberTaken(string $employeeId, ?int $ignoreId = null): ?object
    {
        $number = ReporterApprovals::employeeNumber($employeeId);
        if (! $number) {
            return null;
        }

        return self::query()
            ->where('personnel_employee_number', $number)
            ->when($ignoreId, fn ($q) => $q->where('personnel_id', '!=', $ignoreId))
            ->first();
    }

    /**
     * @param  array{employee_id:string, first_name:string, middle_name:?string, last_name:string, type:string, department_id:?int, email:?string, contact:?string, status:string}  $data
     * @return array{id:int, deactivated:int}
     */
    public static function save(array $data, ?int $personnelId = null, ?int $userId = null): array
    {
        $employeeId = self::normalizeEmployeeId($data['employee_id']);
        $row = [
            'personnel_employee_id' => $employeeId,
            'personnel_employee_number' => ReporterApprovals::employeeNumber($employeeId),
            'personnel_first_name' => trim($data['first_name']),
            'personnel_middle_name' => self::nullable($data['middle_name'] ?? null),
            'personnel_last_name' => trim($data['last_name']),
            'personnel_type' => $data['type'],
            'personnel_department_id' => ! empty($data['department_id']) ? (int) $data['department_id'] : null,
            'personnel_email' => self::nullable(isset($data['email']) ? strtolower((string) $data['email']) : null),
            'personnel_contact' => self::nullable(isset($data['contact']) ? ReporterImport::normalizeContact((string) $data['contact']) : null),
            'personnel_status' => $data['status'],
            'personnel_updated_at' => now(),
        ];

        $previous = $personnelId ? self::find($personnelId) : null;

        return DB::transaction(function () use ($row, $personnelId, $previous, $userId, $employeeId) {
            if ($personnelId) {
                self::query()->where('personnel_id', $personnelId)->update($row);
                $id = $personnelId;
            } else {
                $id = (int) self::query()->insertGetId($row + [
                    'personnel_source' => 'manual',
                    'personnel_created_by' => $userId,
                    'personnel_created_at' => now(),
                ], 'personnel_id');
            }
            self::$empty = false;

            $becameSeparated = $row['personnel_status'] === self::STATUS_SEPARATED
                && ($previous === null || $previous->personnel_status !== self::STATUS_SEPARATED);

            return [
                'id' => $id,
                'deactivated' => $becameSeparated ? self::deactivateReporters([$employeeId]) : 0,
            ];
        });
    }

    public static function delete(int $personnelId): void
    {
        self::query()->where('personnel_id', $personnelId)->delete();
        self::$empty = null;
    }

    /**
     * Sets matching active reporter accounts to Inactive. Returns how many changed.
     *
     * @param  array<int, string>  $employeeIds
     */
    public static function deactivateReporters(array $employeeIds): int
    {
        $variants = [];
        foreach ($employeeIds as $employeeId) {
            foreach (ReporterApprovals::employeeIdVariants((string) $employeeId) as $variant) {
                $variants[strtoupper($variant)] = true;
            }
        }
        if ($variants === [] || ! Schema::hasTable('reporters_table')) {
            return 0;
        }

        $changed = 0;
        foreach (array_chunk(array_keys($variants), 200) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $changed += DB::table('reporters_table')
                ->where('reporter_status', 'Active')
                ->whereRaw('UPPER(TRIM(reporter_employee_id)) IN ('.$placeholders.')', $chunk)
                ->update(['reporter_status' => 'Inactive']);
        }

        return $changed;
    }

    // =====================================================
    // MATCHING
    // =====================================================

    public static function findForEmployeeId(string $employeeId): ?object
    {
        return self::lookup([$employeeId])[self::normalizeEmployeeId($employeeId)] ?? null;
    }

    /**
     * @param  iterable<int, object>  $applications  rows from reporter_approval_requests
     * @return array<int, array> keyed by application id
     */
    public static function checkMany(iterable $applications): array
    {
        $applications = collect($applications);
        if ($applications->isEmpty()) {
            return [];
        }

        $people = self::tableReady()
            ? self::lookup($applications->pluck('employee_id')->all())
            : [];

        $results = [];
        foreach ($applications as $application) {
            $results[(int) $application->id] = self::check(
                $application,
                $people[self::normalizeEmployeeId($application->employee_id)] ?? null
            );
        }

        return $results;
    }

    /**
     * @return array{verdict:string, personnel_id:?int, person:?array, checks:array<int, array>, summary:string}
     */
    public static function check(object $application, ?object $person): array
    {
        $applicantId = self::normalizeEmployeeId($application->employee_id);

        if (! $person) {
            return [
                'verdict' => self::VERDICT_NOT_FOUND,
                'personnel_id' => null,
                'person' => null,
                'checks' => [],
                'summary' => self::isEmpty()
                    ? 'The personnel directory is empty. Add or upload the HR faculty and staff list first.'
                    : 'No one with employee number '.$applicantId.' is in the personnel directory.',
            ];
        }

        $directoryId = self::normalizeEmployeeId($person->personnel_employee_id);
        $checks = [];

        $checks[] = self::checkRow(
            'employee_id',
            'Employee ID',
            $applicantId,
            $directoryId,
            $applicantId === $directoryId ? 'match' : 'mismatch',
            $applicantId === $directoryId ? null : 'Same employee number, but the Faculty/Staff letter is different.'
        );

        $applicantType = trim((string) $application->employment_type);
        $checks[] = self::checkRow(
            'type',
            'Type',
            $applicantType,
            (string) $person->personnel_type,
            strcasecmp($applicantType, (string) $person->personnel_type) === 0 ? 'match' : 'mismatch'
        );

        [$nameStatus, $nameNote] = self::compareNames($application, $person);
        $checks[] = self::checkRow(
            'name',
            'Name',
            (string) $application->full_name,
            self::fullName($person),
            $nameStatus,
            $nameNote
        );

        $applicantEmail = strtolower(trim((string) $application->email));
        $directoryEmail = strtolower(trim((string) ($person->personnel_email ?? '')));
        $checks[] = self::checkRow(
            'email',
            'Email',
            $applicantEmail,
            $directoryEmail !== '' ? $directoryEmail : null,
            $directoryEmail === '' ? 'missing' : ($directoryEmail === $applicantEmail ? 'match' : 'mismatch'),
            $directoryEmail === '' ? 'No email on file in the directory.' : null
        );

        $applicantPhone = preg_replace('/\D+/', '', (string) $application->contact);
        $directoryPhone = preg_replace('/\D+/', '', (string) ($person->personnel_contact ?? ''));
        $checks[] = self::checkRow(
            'contact',
            'Contact',
            (string) $application->contact,
            $directoryPhone !== '' ? (string) $person->personnel_contact : null,
            $directoryPhone === '' ? 'missing' : ($directoryPhone === $applicantPhone ? 'match' : 'warn'),
            $directoryPhone === '' ? 'No number on file.' : ($directoryPhone === $applicantPhone ? null : 'Numbers can change. Not required to match.')
        );

        $status = (string) $person->personnel_status;
        if ($status !== self::STATUS_ACTIVE) {
            $verdict = self::VERDICT_INACTIVE;
            $summary = 'Found in the directory, but marked '.$status.'.';
        } else {
            $blocked = collect($checks)->contains(function ($check) {
                return in_array($check['key'], self::BLOCKING_CHECKS, true)
                    && ($check['status'] === 'mismatch' || ($check['key'] === 'name' && $check['status'] === 'warn'));
            });
            $verdict = $blocked ? self::VERDICT_REVIEW : self::VERDICT_VERIFIED;
            $summary = $blocked
                ? 'Found in the directory, but some details do not match.'
                : 'Employee ID, name and type match an active record in the directory.';
        }

        return [
            'verdict' => $verdict,
            'personnel_id' => (int) $person->personnel_id,
            'person' => [
                'employee_id' => $directoryId,
                'name' => self::fullName($person),
                'type' => (string) $person->personnel_type,
                'status' => $status,
            ],
            'checks' => $checks,
            'summary' => $summary,
        ];
    }

    public static function verdictLabel(?string $verdict): string
    {
        return match ($verdict) {
            self::VERDICT_VERIFIED => 'Verified',
            self::VERDICT_REVIEW => 'Needs review',
            self::VERDICT_INACTIVE => 'Not active',
            self::VERDICT_NOT_FOUND => 'Not in directory',
            default => 'Not checked',
        };
    }

    /**
     * @param  array<int, string>  $employeeIds
     * @return array<string, object> keyed by the normalized requested employee id
     */
    private static function lookup(array $employeeIds): array
    {
        $requested = collect($employeeIds)
            ->map(fn ($id) => self::normalizeEmployeeId($id))
            ->filter()
            ->unique()
            ->values();

        if ($requested->isEmpty() || ! self::tableReady()) {
            return [];
        }

        $numbers = $requested->map(fn ($id) => ReporterApprovals::employeeNumber($id))->filter()->unique()->values();

        $rows = self::query()
            ->where(function ($q) use ($requested, $numbers) {
                $q->whereIn('personnel_employee_id', $requested->all());
                if ($numbers->isNotEmpty()) {
                    $q->orWhereIn('personnel_employee_number', $numbers->all());
                }
            })
            ->get();

        $byId = $rows->keyBy(fn ($row) => self::normalizeEmployeeId($row->personnel_employee_id));
        $byNumber = $rows->keyBy('personnel_employee_number');

        $found = [];
        foreach ($requested as $id) {
            $number = ReporterApprovals::employeeNumber($id);
            $person = $byId->get($id) ?? ($number ? $byNumber->get($number) : null);
            if ($person) {
                $found[$id] = $person;
            }
        }

        return $found;
    }

    /**
     * @return array{0:string, 1:?string}
     */
    private static function compareNames(object $application, object $person): array
    {
        $aFirst = self::normalizeName($application->first_name ?? '');
        $aMiddle = self::normalizeName($application->middle_name ?? '');
        $aLast = self::normalizeName($application->last_name ?? '');
        $dFirst = self::normalizeName($person->personnel_first_name);
        $dMiddle = self::normalizeName($person->personnel_middle_name ?? '');
        $dLast = self::normalizeName($person->personnel_last_name);

        if ($aFirst === $dFirst && $aLast === $dLast) {
            if ($aMiddle === '' || $dMiddle === '' || $aMiddle === $dMiddle) {
                return ['match', null];
            }
            $initialOnly = strlen($aMiddle) === 1 || strlen($dMiddle) === 1;
            if ($initialOnly && $aMiddle[0] === $dMiddle[0]) {
                return ['match', 'Middle name written as an initial.'];
            }

            return ['warn', 'First and last name match, but the middle name is different.'];
        }

        $close = function (string $a, string $b): bool {
            if ($a === $b) {
                return true;
            }
            if ($a === '' || $b === '') {
                return false;
            }
            if (str_starts_with($a.' ', $b.' ') || str_starts_with($b.' ', $a.' ')) {
                return true;
            }
            similar_text($a, $b, $percent);

            return $percent >= 80 || levenshtein($a, $b) <= 1;
        };

        if ($close($aFirst, $dFirst) && $close($aLast, $dLast)) {
            return ['warn', 'Close, but not an exact match. Check the spelling.'];
        }

        return ['mismatch', 'The name does not match the directory record.'];
    }

    private static function checkRow(string $key, string $label, ?string $applicant, ?string $directory, string $status, ?string $note = null): array
    {
        return compact('key', 'label', 'applicant', 'directory', 'status', 'note');
    }

    // =====================================================
    // IMPORT
    // =====================================================

    public static function headerAliases(): array
    {
        return [
            'employee_id' => ['employee id', 'employee_id', 'employee no', 'employee number', 'emp id', 'empid', 'emp no', 'id', 'id number', 'staff id'],
            'first_name' => ['first name', 'firstname', 'given name', 'givenname'],
            'middle_name' => ['middle name', 'middlename', 'mi', 'middle initial'],
            'last_name' => ['last name', 'lastname', 'surname', 'family name'],
            'type' => ['type', 'employment type', 'employee type', 'category', 'faculty staff', 'position type'],
            'department' => ['department', 'dept', 'office', 'department office', 'unit'],
            'email' => ['email', 'email address', 'e mail', 'institutional email', 'school email'],
            'contact' => ['contact', 'contact number', 'contact no', 'phone', 'phone number', 'mobile', 'mobile number', 'cellphone'],
            'status' => ['status', 'employment status', 'employee status'],
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, ?int>
     */
    public static function suggestMapping(array $headers): array
    {
        $mapping = array_fill_keys(array_keys(self::IMPORT_FIELDS), null);

        foreach ($headers as $index => $header) {
            $normalized = ReporterImport::normalizeHeader($header);
            foreach (self::headerAliases() as $field => $aliases) {
                if ($mapping[$field] === null && in_array($normalized, $aliases, true)) {
                    $mapping[$field] = (int) $index;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * @param  array<int, array<int, string>>  $rows  first row is the header
     * @return array{ok:bool, message?:string, created?:int, updated?:int, unchanged?:int, separated?:int, deactivated?:int, skipped?:int, errors?:array<int, string>, unknown_departments?:array<int, string>}
     */
    public static function import(array $rows, bool $markMissingSeparated = false, ?int $userId = null): array
    {
        if (count($rows) < 2) {
            return ['ok' => false, 'message' => 'The file needs a header row and at least one person.'];
        }

        $mapping = self::suggestMapping($rows[0]);
        $missing = array_filter(self::REQUIRED_IMPORT_FIELDS, fn ($field) => $mapping[$field] === null);
        if ($missing !== []) {
            $labels = array_map(fn ($field) => self::IMPORT_FIELDS[$field], $missing);

            return [
                'ok' => false,
                'message' => 'Missing column(s): '.implode(', ', $labels).'. Download the template to see the expected headers.',
            ];
        }

        $departments = Schema::hasTable('departments_table')
            ? DB::table('departments_table')->get(['department_id', 'department_name'])
                ->mapWithKeys(fn ($d) => [Str::lower(trim($d->department_name)) => (int) $d->department_id])
                ->all()
            : [];

        $existing = self::query()
            ->get(['personnel_id', 'personnel_employee_id', 'personnel_employee_number', 'personnel_first_name', 'personnel_middle_name', 'personnel_last_name', 'personnel_type', 'personnel_department_id', 'personnel_email', 'personnel_contact', 'personnel_status'])
            ->keyBy('personnel_employee_number');

        $cell = function (array $row, string $field) use ($mapping): string {
            $index = $mapping[$field];

            return $index === null ? '' : trim((string) ($row[$index] ?? ''));
        };

        $errors = [];
        $unknownDepartments = [];
        $seen = [];
        $plan = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $line = $offset + 2;
            if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $employeeId = self::normalizeEmployeeId($cell($row, 'employee_id'));
            $first = PersonNames::clean($cell($row, 'first_name'));
            $middle = PersonNames::clean($cell($row, 'middle_name'));
            $last = PersonNames::clean($cell($row, 'last_name'));

            if (! preg_match(self::EMPLOYEE_ID_PATTERN, $employeeId)) {
                $errors[] = 'Row '.$line.': employee ID "'.($employeeId ?: 'blank').'" should look like OMC00127F or OMC00127S.';
                continue;
            }
            if ($first === '' || $last === '') {
                $errors[] = 'Row '.$line.': first and last name are required.';
                continue;
            }
            $badName = collect(['first' => $first, 'middle' => $middle, 'last' => $last])
                ->first(fn ($value) => ! PersonNames::valid($value) || mb_strlen($value) > 100);
            if ($badName !== null) {
                $errors[] = 'Row '.$line.': name "'.$badName.'" can only use letters, spaces, hyphens, apostrophes and periods (up to 100 characters).';
                continue;
            }

            $suffixType = self::typeFromEmployeeId($employeeId);
            $rawType = Str::lower($cell($row, 'type'));
            $type = match (true) {
                $rawType === '' => $suffixType,
                str_starts_with($rawType, 'fac') => 'Faculty',
                str_starts_with($rawType, 'staff'), str_starts_with($rawType, 'non') => 'Staff',
                default => null,
            };
            if ($type === null) {
                $errors[] = 'Row '.$line.': type "'.$cell($row, 'type').'" should be Faculty or Staff.';
                continue;
            }
            if ($type !== $suffixType) {
                $errors[] = 'Row '.$line.': type is '.$type.' but '.$employeeId.' ends with '.substr($employeeId, -1).'.';
                continue;
            }

            $email = strtolower($cell($row, 'email'));
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Row '.$line.': "'.$email.'" is not a valid email.';
                continue;
            }

            $rawStatus = Str::lower($cell($row, 'status'));
            $status = match (true) {
                $rawStatus === '', in_array($rawStatus, ['active', 'regular', 'employed', 'current'], true) => self::STATUS_ACTIVE,
                str_contains($rawStatus, 'leave') => self::STATUS_ON_LEAVE,
                in_array($rawStatus, ['separated', 'resigned', 'inactive', 'terminated', 'retired', 'ended'], true) => self::STATUS_SEPARATED,
                default => null,
            };
            if ($status === null) {
                $errors[] = 'Row '.$line.': status "'.$cell($row, 'status').'" should be Active, On leave or Separated.';
                continue;
            }

            $number = ReporterApprovals::employeeNumber($employeeId);
            if (isset($seen[$number])) {
                $errors[] = 'Row '.$line.': employee number '.$number.' already appears on row '.$seen[$number].'.';
                continue;
            }
            $seen[$number] = $line;

            $departmentName = $cell($row, 'department');
            $departmentId = null;
            if ($departmentName !== '') {
                $departmentId = $departments[Str::lower($departmentName)] ?? null;
                if ($departmentId === null) {
                    $unknownDepartments[$departmentName] = true;
                }
            }

            $contact = ReporterImport::normalizeContact($cell($row, 'contact'));
            $plan[$number] = [
                'personnel_employee_id' => $employeeId,
                'personnel_employee_number' => $number,
                'personnel_first_name' => $first,
                'personnel_middle_name' => self::nullable($middle),
                'personnel_last_name' => $last,
                'personnel_type' => $type,
                'personnel_department_id' => $departmentId ?? ($existing->get($number)->personnel_department_id ?? null),
                'personnel_email' => self::nullable($email),
                'personnel_contact' => self::nullable($contact),
                'personnel_status' => $status,
            ];
        }

        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'separated' => 0, 'deactivated' => 0];

        DB::transaction(function () use ($plan, $existing, $markMissingSeparated, $userId, &$counts) {
            $now = now();
            $newlySeparated = [];

            foreach ($plan as $number => $row) {
                $current = $existing->get($number);
                if (! $current) {
                    self::query()->insert($row + [
                        'personnel_source' => 'import',
                        'personnel_created_by' => $userId,
                        'personnel_created_at' => $now,
                        'personnel_updated_at' => $now,
                    ]);
                    $counts['created']++;
                    if ($row['personnel_status'] === self::STATUS_SEPARATED) {
                        $newlySeparated[] = $row['personnel_employee_id'];
                    }

                    continue;
                }

                $changed = collect($row)->contains(fn ($value, $column) => (string) ($current->{$column} ?? '') !== (string) ($value ?? ''));
                if (! $changed) {
                    $counts['unchanged']++;

                    continue;
                }

                self::query()->where('personnel_id', $current->personnel_id)->update($row + ['personnel_updated_at' => $now]);
                $counts['updated']++;
                if ($row['personnel_status'] === self::STATUS_SEPARATED && $current->personnel_status !== self::STATUS_SEPARATED) {
                    $newlySeparated[] = $row['personnel_employee_id'];
                }
            }

            if ($markMissingSeparated && $plan !== []) {
                $missing = $existing->filter(fn ($row, $number) => ! isset($plan[$number]) && $row->personnel_status !== self::STATUS_SEPARATED);
                if ($missing->isNotEmpty()) {
                    self::query()
                        ->whereIn('personnel_id', $missing->pluck('personnel_id')->all())
                        ->update(['personnel_status' => self::STATUS_SEPARATED, 'personnel_updated_at' => $now]);
                    $counts['separated'] = $missing->count();
                    $newlySeparated = array_merge($newlySeparated, $missing->pluck('personnel_employee_id')->all());
                }
            }

            $counts['deactivated'] = self::deactivateReporters($newlySeparated);
        });

        self::$empty = null;

        return ['ok' => true] + $counts + [
            'skipped' => count($errors),
            'errors' => array_slice($errors, 0, 15),
            'unknown_departments' => array_keys($unknownDepartments),
        ];
    }

    private static function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
