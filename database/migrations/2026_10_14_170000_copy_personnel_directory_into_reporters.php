<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Personnel Directory is now a read-only view of reporters_table. Anyone who was only in
 * the old directory becomes a reporter (Separated people as Inactive). The old table is
 * left in place as a backup.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personnel_directory_table') || ! Schema::hasTable('reporters_table')) {
            return;
        }

        $number = function (?string $employeeId): ?string {
            $id = strtoupper(preg_replace('/\s+/', '', trim((string) $employeeId)));

            return preg_match('/^OMC(\d{4,5})[FS]$/', $id, $match)
                ? str_pad($match[1], 5, '0', STR_PAD_LEFT)
                : null;
        };

        $takenNumbers = [];
        $takenEmails = [];
        foreach (DB::table('reporters_table')->get(['reporter_employee_id', 'reporter_email_address']) as $reporter) {
            if ($key = $number($reporter->reporter_employee_id)) {
                $takenNumbers[$key] = true;
            }
            $email = strtolower(trim((string) $reporter->reporter_email_address));
            if ($email !== '') {
                $takenEmails[$email] = true;
            }
        }

        $columns = array_flip(Schema::getColumnListing('reporters_table'));
        $now = now();
        $rows = [];

        foreach (DB::table('personnel_directory_table')->orderBy('personnel_id')->get() as $person) {
            $key = $number($person->personnel_employee_id);
            if (! $key || isset($takenNumbers[$key])) {
                continue;
            }
            $takenNumbers[$key] = true;

            $email = strtolower(trim((string) $person->personnel_email));
            if ($email !== '' && isset($takenEmails[$email])) {
                $email = '';
            }
            if ($email !== '') {
                $takenEmails[$email] = true;
            }

            $first = trim((string) $person->personnel_first_name);
            $middle = trim((string) $person->personnel_middle_name);
            $last = trim((string) $person->personnel_last_name);

            $row = [
                'reporter_employee_id' => strtoupper(preg_replace('/\s+/', '', $person->personnel_employee_id)),
                'reporter_full_name' => trim(preg_replace('/\s+/', ' ', $first.' '.$middle.' '.$last)),
                'reporter_first_name' => $first,
                'reporter_middle_name' => $middle !== '' ? $middle : null,
                'reporter_last_name' => $last,
                'reporter_employment_type' => $person->personnel_type,
                'reporter_department_id' => $person->personnel_department_id,
                'reporter_email_address' => $email !== '' ? $email : null,
                'reporter_contact_number' => $person->personnel_contact ?: null,
                'reporter_status' => $person->personnel_status === 'Separated' ? 'Inactive' : 'Active',
                'reporter_created_at' => $now,
            ];

            $rows[] = array_intersect_key($row, $columns);
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('reporters_table')->insert($chunk);
        }
    }

    public function down(): void
    {
        // Copied reporters may already have PINs and reports; they are not removed automatically.
    }
};
