<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Bulk CSV/XLSX import for the Faculty & Staff master list.
 * Reuses ReporterImport file parsing and column mapping.
 */
class PersonImport
{
    public const FIELDS = [
        'type' => 'Type',
        'employee_id' => 'Employee ID',
        'first_name' => 'First name',
        'middle_name' => 'Middle name',
        'last_name' => 'Last name',
        'employment_basis' => 'Employment basis',
        'department' => 'Department',
        'email_address' => 'Email address',
        'contact_number' => 'Contact number',
    ];

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
        if (Persons::tableReady()) {
            foreach (DB::table('persons_table')->get(['person_employee_id', 'person_email_address']) as $person) {
                $number = self::employeeNumber((string) $person->person_employee_id);
                if ($number) {
                    $takenNumbers[$number] = true;
                }
                $email = strtolower(trim((string) $person->person_email_address));
                if ($email !== '') {
                    $takenEmails[$email] = true;
                }
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
            $rawBasis = $cell($row, 'employment_basis');
            $email = strtolower($cell($row, 'email_address'));
            $contactRaw = ReporterImport::normalizeContact($cell($row, 'contact_number'));
            $contactDigits = preg_replace('/\D+/', '', $contactRaw) ?? '';
            if (str_starts_with($contactDigits, '63') && strlen($contactDigits) >= 12) {
                $contactDigits = '0'.substr($contactDigits, 2);
            } elseif (strlen($contactDigits) === 10 && str_starts_with($contactDigits, '9')) {
                $contactDigits = '0'.$contactDigits;
            }
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

            $lowerBasis = Str::lower($rawBasis);
            $basis = match (true) {
                $lowerBasis === '' => null,
                str_contains($lowerBasis, 'full') => 'Full time',
                str_contains($lowerBasis, 'part') => 'Part time',
                default => null,
            };

            $number = self::employeeNumber($employeeId);
            $badName = collect([$first, $middle, $last])
                ->first(fn ($value) => ! PersonNames::valid($value) || mb_strlen($value) > 100);

            $error = match (true) {
                $first === '' || $last === '' => 'First and last name are required.',
                $badName !== null => 'Name "'.$badName.'" can only use letters, spaces, hyphens, apostrophes and periods.',
                $employeeId === '' => 'Employee ID is missing.',
                ! preg_match(Persons::EMPLOYEE_ID_REGEX, $employeeId) => 'Employee ID "'.$employeeId.'" should look like OMC00127F or OMC00127S.',
                $type === null => 'Type "'.$rawType.'" should be Faculty or Staff.',
                $type !== $suffixType => 'Type is '.$type.' but '.$employeeId.' ends with '.substr($employeeId, -1).'.',
                $rawBasis !== '' && $basis === null => 'Employment basis should be Full time or Part time.',
                isset($takenNumbers[$number]) => 'Employee number '.$employeeId.' is already in Faculty & Staff.',
                isset($seenNumbers[$number]) => 'Employee number '.$employeeId.' already appears on row '.$seenNumbers[$number].'.',
                $email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL) => '"'.$email.'" is not a valid email.',
                $email !== '' && isset($takenEmails[$email]) => 'Email '.$email.' is already used by another person.',
                $email !== '' && isset($seenEmails[$email]) => 'Email '.$email.' already appears on row '.$seenEmails[$email].'.',
                $contactRaw !== '' && ! PhoneNumber::isValid($contactRaw) => 'Contact number is not a valid mobile number.',
                default => null,
            };

            if ($error === null) {
                $seenNumbers[$number] = $line;
                if ($email !== '') {
                    $seenEmails[$email] = $line;
                }
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
                'employment_basis' => $basis,
                'department' => $departmentName,
                'department_id' => $departmentId,
                'email_address' => $email,
                'contact_number' => $contactRaw !== '' ? (PhoneNumber::normalizeForStorage($contactRaw) ?? '') : '',
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

    public static function employeeNumber(string $employeeId): ?string
    {
        if (class_exists(ReporterApprovals::class) && method_exists(ReporterApprovals::class, 'employeeNumber')) {
            return ReporterApprovals::employeeNumber($employeeId);
        }

        if (preg_match('/OMC(\d{5})[FS]/i', $employeeId, $m)) {
            return $m[1];
        }

        return null;
    }
}
