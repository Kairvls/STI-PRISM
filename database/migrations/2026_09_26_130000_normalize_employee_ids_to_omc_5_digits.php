<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize employee IDs to OMC + 5 digits + F/S.
     * Examples: OMC0123F -> OMC00123F, ADMIN001 -> OMC00001F, PRESI001 -> OMC00002F.
     */
    public function up(): void
    {
        $targets = $this->employeeIdColumns();

        foreach ($targets as [$table, $field]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $field)) {
                continue;
            }

            $rows = DB::table($table)->select($field)->whereNotNull($field)->distinct()->get();

            foreach ($rows as $row) {
                $old = (string) $row->{$field};
                $new = $this->normalizeEmployeeId($old);

                if (! $new || $new === $old) {
                    continue;
                }

                if (DB::table($table)->where($field, $new)->exists()) {
                    continue;
                }

                DB::table($table)->where($field, $old)->update([$field => $new]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function employeeIdColumns(): array
    {
        return [
            ['users_table', 'user_employee_id'],
            ['reporters_table', 'reporter_employee_id'],
            ['reports_table', 'report_reporter_employee_id'],
            ['reporter_approval_requests', 'employee_id'],
        ];
    }

    private function normalizeEmployeeId(?string $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $raw = strtoupper(preg_replace('/\s+/', '', trim($id)));
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^OMC\d{5}[FS]$/', $raw)) {
            return $raw;
        }

        if (preg_match('/^OMC(\d{4})([FS])$/', $raw, $m)) {
            return 'OMC'.str_pad($m[1], 5, '0', STR_PAD_LEFT).$m[2];
        }

        $special = [
            'ADMIN001' => 'OMC00001F',
            'PRESI001' => 'OMC00002F',
        ];

        if (isset($special[$raw])) {
            return $special[$raw];
        }

        if (preg_match('/^ADMIN0*(\d+)$/', $raw, $m)) {
            return 'OMC'.str_pad($m[1], 5, '0', STR_PAD_LEFT).'F';
        }

        if (preg_match('/^PRESI0*(\d+)$/', $raw, $m)) {
            $n = (int) $m[1];
            if ($n === 1) {
                $n = 2;
            }

            return 'OMC'.str_pad((string) $n, 5, '0', STR_PAD_LEFT).'F';
        }

        if (preg_match('/^\d{4,5}$/', $raw)) {
            return 'OMC'.str_pad($raw, 5, '0', STR_PAD_LEFT).'F';
        }

        if (preg_match('/(\d{1,5})([FS])?$/', $raw, $m)) {
            $suffix = $m[2] ?? 'F';

            return 'OMC'.str_pad($m[1], 5, '0', STR_PAD_LEFT).$suffix;
        }

        return null;
    }
};
