<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ReporterImport
{
    public const FIELDS = [
        'employee_id' => 'Employee ID',
        'first_name' => 'First name',
        'middle_name' => 'Middle name',
        'last_name' => 'Last name',
        'type' => 'Type',
        'email_address' => 'Email address',
        'contact_number' => 'Contact number',
    ];

    public static function headerAliases(): array
    {
        return [
            'employee_id' => ['employee_id', 'employee id', 'empid', 'emp id', 'staff id', 'id'],
            'first_name' => ['first_name', 'first name', 'firstname', 'given name', 'givenname'],
            'middle_name' => ['middle_name', 'middle name', 'middlename', 'mi', 'middle initial'],
            'last_name' => ['last_name', 'last name', 'lastname', 'surname', 'family name'],
            'type' => ['type', 'employment type', 'employment', 'employee type', 'status type'],
            'email_address' => ['email_address', 'email address', 'email', 'e-mail', 'mail'],
            'contact_number' => ['contact_number', 'contact number', 'contact', 'phone', 'phone number', 'mobile', 'cellphone'],
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

    public static function nextEmployeeIds(int $count): array
    {
        $existing = DB::table('reporters_table')->pluck('reporter_employee_id');
        $max = 0;

        foreach ($existing as $id) {
            if (preg_match('/(\d+)/', (string) $id, $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $ids[] = 'OMC'.str_pad((string) ($max + $i), 4, '0', STR_PAD_LEFT).'F';
        }

        return $ids;
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

        if (count($parts) === 1) {
            return ['first' => $parts[0], 'middle' => '', 'last' => ''];
        }

        if (count($parts) === 2) {
            return ['first' => $parts[0], 'middle' => '', 'last' => $parts[1]];
        }

        return [
            'first' => array_shift($parts),
            'last' => array_pop($parts),
            'middle' => implode(' ', $parts),
        ];
    }
}
