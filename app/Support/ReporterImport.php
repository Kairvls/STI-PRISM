<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class ReporterImport
{
    public const FIELDS = [
        'type' => 'Type',
        'employee_id' => 'Employee ID',
        'first_name' => 'First name',
        'middle_name' => 'Middle name',
        'last_name' => 'Last name',
        'department' => 'Department',
        'email_address' => 'Email address',
        'contact_number' => 'Contact number',
    ];

    public const EMPLOYEE_ID_PATTERN = '/^OMC\d{5}[FS]$/';

    public static function headerAliases(): array
    {
        return [
            'employee_id' => ['employee_id', 'employee id', 'empid', 'emp id', 'staff id', 'id'],
            'first_name' => ['first_name', 'first name', 'firstname', 'given name', 'givenname'],
            'middle_name' => ['middle_name', 'middle name', 'middlename', 'mi', 'middle initial'],
            'last_name' => ['last_name', 'last name', 'lastname', 'surname', 'family name'],
            'type' => ['type', 'employment type', 'employment', 'employee type', 'status type', 'category', 'faculty staff', 'position type'],
            'department' => ['department', 'dept', 'office', 'department office', 'unit'],
            'email_address' => ['email_address', 'email address', 'email', 'e mail', 'mail', 'institutional email', 'school email'],
            'contact_number' => ['contact_number', 'contact number', 'contact', 'contact no', 'phone', 'phone number', 'mobile', 'mobile number', 'cellphone'],
        ];
    }

    /**
     * Checks every row of an uploaded file against the same rules as adding one reporter.
     * Rows with an error are skipped on import; unknown departments are saved as blank.
     *
     * @param  array<int, array<int, string>>  $rows  first row is the header
     * @param  array<string, mixed>  $mapping  field => column index
     * @return array{rows: array<int, array<string, mixed>>, ready: int, skipped: int, unknown_departments: array<int, string>}
     */
    public static function plan(array $rows, array $mapping): array
    {
        $cell = function (array $row, string $field) use ($mapping): string {
            $index = $mapping[$field] ?? '';

            return ($index === '' || $index === null) ? '' : trim((string) ($row[(int) $index] ?? ''));
        };

        $departments = Departments::tableReady()
            ? DB::table('departments_table')->get(['department_id', 'department_name'])
                ->mapWithKeys(fn ($d) => [Str::lower(trim($d->department_name)) => (int) $d->department_id])
                ->all()
            : [];

        $takenNumbers = [];
        $takenEmails = [];
        foreach (DB::table('reporters_table')->get(['reporter_employee_id', 'reporter_email_address']) as $reporter) {
            if ($number = ReporterApprovals::employeeNumber((string) $reporter->reporter_employee_id)) {
                $takenNumbers[$number] = true;
            }
            $email = strtolower(trim((string) $reporter->reporter_email_address));
            if ($email !== '') {
                $takenEmails[$email] = true;
            }
        }

        $seenNumbers = [];
        $seenEmails = [];
        $unknownDepartments = [];
        $planned = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            if (collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $line = $offset + 2;
            $employeeId = strtoupper(preg_replace('/\s+/', '', $cell($row, 'employee_id')));
            $first = PersonNames::clean($cell($row, 'first_name'));
            $middle = PersonNames::clean($cell($row, 'middle_name'));
            $last = PersonNames::clean($cell($row, 'last_name'));
            $rawType = $cell($row, 'type');
            $email = strtolower($cell($row, 'email_address'));
            $contact = preg_replace('/\D+/', '', self::normalizeContact($cell($row, 'contact_number')));
            $departmentName = $cell($row, 'department');
            $departmentId = $departmentName !== '' ? ($departments[Str::lower($departmentName)] ?? null) : null;

            $suffixType = match (substr($employeeId, -1)) {
                'F' => 'Faculty',
                'S' => 'Staff',
                default => null,
            };
            $lowerType = Str::lower($rawType);
            $type = match (true) {
                $lowerType === '' => $suffixType,
                str_starts_with($lowerType, 'fac') => 'Faculty',
                str_starts_with($lowerType, 'staff'), str_starts_with($lowerType, 'non') => 'Staff',
                default => null,
            };
            $number = ReporterApprovals::employeeNumber($employeeId);
            $badName = collect([$first, $middle, $last])
                ->first(fn ($value) => ! PersonNames::valid($value) || mb_strlen($value) > 100);

            $error = match (true) {
                $first === '' || $last === '' => 'First and last name are required.',
                $badName !== null => 'Name "'.$badName.'" can only use letters, spaces, hyphens, apostrophes and periods.',
                $employeeId === '' => 'Employee ID is missing.',
                ! preg_match(self::EMPLOYEE_ID_PATTERN, $employeeId) => 'Employee ID "'.$employeeId.'" should look like OMC00127F or OMC00127S.',
                $type === null => 'Type "'.$rawType.'" should be Faculty or Staff.',
                $type !== $suffixType => 'Type is '.$type.' but '.$employeeId.' ends with '.substr($employeeId, -1).'.',
                isset($takenNumbers[$number]) => 'Employee number '.$employeeId.' is already a reporter.',
                isset($seenNumbers[$number]) => 'Employee number '.$employeeId.' already appears on row '.$seenNumbers[$number].'.',
                $email === '' => 'Email address is required.',
                ! filter_var($email, FILTER_VALIDATE_EMAIL) => '"'.$email.'" is not a valid email.',
                isset($takenEmails[$email]) => 'Email '.$email.' is already used by another reporter.',
                isset($seenEmails[$email]) => 'Email '.$email.' already appears on row '.$seenEmails[$email].'.',
                $contact !== '' && strlen($contact) !== 11 => 'Contact number must have exactly 11 digits.',
                default => null,
            };

            if ($error === null) {
                $seenNumbers[$number] = $line;
                $seenEmails[$email] = $line;
                if ($departmentName !== '' && $departmentId === null) {
                    $unknownDepartments[$departmentName] = true;
                }
            }

            $planned[] = [
                'line' => $line,
                'employee_id' => $employeeId,
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'type' => $type ?? $rawType,
                'department' => $departmentName,
                'department_id' => $departmentId,
                'email_address' => $email,
                'contact_number' => $contact,
                'error' => $error,
            ];
        }

        $skipped = collect($planned)->whereNotNull('error')->count();

        return [
            'rows' => $planned,
            'ready' => count($planned) - $skipped,
            'skipped' => $skipped,
            'unknown_departments' => array_keys($unknownDepartments),
        ];
    }

    public static function normalizeHeader(?string $header): string
    {
        $header = strtolower(trim((string) $header));
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header);

        return trim($header);
    }

    public static function suggestMapping(array $headers): array
    {
        $mapping = [];

        foreach (array_keys(self::FIELDS) as $field) {
            $mapping[$field] = '';
        }

        foreach ($headers as $index => $header) {
            $normalized = self::normalizeHeader($header);

            foreach (self::headerAliases() as $field => $aliases) {
                if ($mapping[$field] !== '') {
                    continue;
                }

                if (in_array($normalized, $aliases, true)) {
                    $mapping[$field] = (string) $index;
                }
            }
        }

        return $mapping;
    }

    public static function parseFile(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === 'xlsx') {
            return self::parseXlsx($path);
        }

        return self::parseCsv($path);
    }

    public static function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $rows = [];
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
        }

        fclose($handle);

        return $rows;
    }

    public static function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml) {
            $xml = @simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $shared[] = trim((string) ($si->t ?? $si->r->t ?? ''));
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheetXml) {
            return [];
        }

        $sheet = @simplexml_load_string($sheetXml);

        if (! $sheet) {
            return [];
        }

        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $col = self::columnIndexFromRef($ref);
                $value = '';

                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    $value = ((string) $cell['t'] === 's')
                        ? ($shared[(int) $raw] ?? $raw)
                        : $raw;
                }

                $cells[$col] = trim($value);
            }

            if ($cells === []) {
                continue;
            }

            ksort($cells);
            $max = max(array_keys($cells));
            $line = [];

            for ($i = 0; $i <= $max; $i++) {
                $line[] = $cells[$i] ?? '';
            }

            $rows[] = $line;
        }

        return $rows;
    }

    protected static function columnIndexFromRef(string $ref): int
    {
        $letters = strtoupper(preg_replace('/[^A-Z]/', '', $ref));
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    public static function composeFullName(string $first, string $middle, string $last): string
    {
        return trim(preg_replace('/\s+/', ' ', $first.' '.$middle.' '.$last));
    }

    public static function normalizeContact(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^="(.*)"$/', $value, $match)) {
            $value = $match[1];
        }

        $value = ltrim($value, "'\t ");

        if (preg_match('/^\d+(?:\.\d+)?e\+\d+$/i', $value)) {
            $value = sprintf('%.0f', (float) $value);

            if (strlen($value) === 10) {
                $value = '0'.$value;
            }
        }

        return $value;
    }

    public static function hasTypeColumn(): bool
    {
        return Schema::hasColumn('reporters_table', 'reporter_employment_type');
    }

    public static function hasDepartmentColumn(): bool
    {
        return Schema::hasColumn('reporters_table', 'reporter_department_id');
    }

    public static function hasNameColumns(): bool
    {
        return Schema::hasColumn('reporters_table', 'reporter_first_name')
            && Schema::hasColumn('reporters_table', 'reporter_last_name');
    }

    public static function splitFullName(?string $fullName): array
    {
        $parts = preg_split('/\s+/', trim((string) $fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }

        $suffix = '';
        if (count($parts) > 2 && in_array(strtolower(rtrim(end($parts), '.,')), ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
            $suffix = ' '.array_pop($parts);
        }

        if (count($parts) === 1) {
            return ['first' => $parts[0], 'middle' => '', 'last' => ltrim($suffix)];
        }

        // Compound surnames such as "Dela Cruz", "De los Santos", "San Juan".
        $particles = ['de', 'del', 'dela', 'delos', 'della', 'di', 'da', 'dos', 'das', 'san', 'sta', 'santa', 'sto', 'santo', 'van', 'von', 'la', 'le', 'mac', 'mc'];
        $lastStart = count($parts) - 1;
        for ($i = 1; $i < count($parts) - 1; $i++) {
            if (in_array(strtolower(rtrim($parts[$i], '.')), $particles, true)) {
                $lastStart = $i;
                break;
            }
        }

        return [
            'first' => $parts[0],
            'middle' => implode(' ', array_slice($parts, 1, $lastStart - 1)),
            'last' => implode(' ', array_slice($parts, $lastStart)).$suffix,
        ];
    }
}
