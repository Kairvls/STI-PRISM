<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persons_table')) {
            Schema::create('persons_table', function (Blueprint $table) {
                $table->id('person_id');
                $table->string('person_employee_id', 50)->unique();
                $table->string('person_first_name', 80)->nullable();
                $table->string('person_middle_name', 80)->nullable();
                $table->string('person_last_name', 80)->nullable();
                $table->string('person_full_name', 255);
                $table->string('person_employment_type', 20)->nullable()->index();
                $table->string('person_employment_basis', 20)->nullable();
                $table->unsignedBigInteger('person_department_id')->nullable()->index();
                $table->string('person_email_address', 255)->nullable();
                $table->string('person_contact_number', 50)->nullable();
                $table->string('person_status', 20)->default('Active')->index();
                $table->unsignedBigInteger('person_created_by')->nullable();
                $table->timestamp('person_created_at')->nullable();
                $table->timestamp('person_updated_at')->nullable();
            });
        }

        if (Schema::hasTable('reporters_table') && ! Schema::hasColumn('reporters_table', 'person_id')) {
            Schema::table('reporters_table', function (Blueprint $table) {
                $table->unsignedBigInteger('person_id')->nullable()->unique()->after('reporter_id');
            });
        }

        if (Schema::hasTable('custodians_table') && ! Schema::hasColumn('custodians_table', 'person_id')) {
            Schema::table('custodians_table', function (Blueprint $table) {
                $table->unsignedBigInteger('person_id')->nullable()->unique()->after('custodian_id');
            });
        }

        if (Schema::hasTable('users_table') && ! Schema::hasColumn('users_table', 'person_id')) {
            Schema::table('users_table', function (Blueprint $table) {
                $table->unsignedBigInteger('person_id')->nullable()->unique()->after('user_id');
            });
        }

        $this->backfillPersons();
    }

    public function down(): void
    {
        foreach (['reporters_table', 'custodians_table', 'users_table'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'person_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('person_id');
                });
            }
        }

        Schema::dropIfExists('persons_table');
    }

    private function backfillPersons(): void
    {
        if (! Schema::hasTable('persons_table')) {
            return;
        }

        $byEmployee = [];

        if (Schema::hasTable('reporters_table')) {
            foreach (DB::table('reporters_table')->orderBy('reporter_id')->get() as $row) {
                $key = strtoupper(trim((string) ($row->reporter_employee_id ?? '')));
                if ($key === '') {
                    continue;
                }
                $byEmployee[$key] = [
                    'employee_id' => $key,
                    'first' => $row->reporter_first_name ?? null,
                    'middle' => $row->reporter_middle_name ?? null,
                    'last' => $row->reporter_last_name ?? null,
                    'full' => $row->reporter_full_name ?? $key,
                    'type' => $row->reporter_employment_type ?? null,
                    'basis' => $row->reporter_employment_basis ?? null,
                    'department_id' => $row->reporter_department_id ?? null,
                    'email' => $row->reporter_email_address ?? null,
                    'contact' => $row->reporter_contact_number ?? null,
                    'status' => (($row->reporter_status ?? '') === 'Inactive') ? 'Inactive' : 'Active',
                    'reporter_id' => $row->reporter_id,
                ];
            }
        }

        if (Schema::hasTable('custodians_table')) {
            foreach (DB::table('custodians_table')->orderBy('custodian_id')->get() as $row) {
                $key = strtoupper(trim((string) ($row->custodian_employee_id ?? '')));
                if ($key === '') {
                    continue;
                }
                if (! isset($byEmployee[$key])) {
                    $position = trim((string) ($row->custodian_position ?? ''));
                    $type = in_array($position, ['Faculty', 'Staff'], true) ? $position : null;
                    $byEmployee[$key] = [
                        'employee_id' => $key,
                        'first' => $row->custodian_first_name ?? null,
                        'middle' => $row->custodian_middle_name ?? null,
                        'last' => $row->custodian_last_name ?? null,
                        'full' => $row->custodian_full_name ?? $key,
                        'type' => $type,
                        'basis' => $row->custodian_employment_basis ?? null,
                        'department_id' => $row->custodian_department_id ?? null,
                        'email' => $row->custodian_email_address ?? null,
                        'contact' => $row->custodian_contact_number ?? null,
                        'status' => (($row->custodian_status ?? '') === 'Inactive') ? 'Inactive' : 'Active',
                        'custodian_id' => $row->custodian_id,
                    ];
                } else {
                    $byEmployee[$key]['custodian_id'] = $row->custodian_id;
                    if (empty($byEmployee[$key]['department_id']) && ! empty($row->custodian_department_id)) {
                        $byEmployee[$key]['department_id'] = $row->custodian_department_id;
                    }
                }
            }
        }

        if (Schema::hasTable('users_table')) {
            foreach (DB::table('users_table')->orderBy('user_id')->get() as $row) {
                $key = strtoupper(trim((string) ($row->user_employee_id ?? '')));
                if ($key === '') {
                    continue;
                }
                if (! isset($byEmployee[$key])) {
                    $suffix = substr($key, -1);
                    $byEmployee[$key] = [
                        'employee_id' => $key,
                        'first' => null,
                        'middle' => null,
                        'last' => null,
                        'full' => $row->user_full_name ?? $key,
                        'type' => $suffix === 'S' ? 'Staff' : ($suffix === 'F' ? 'Faculty' : null),
                        'basis' => null,
                        'department_id' => null,
                        'email' => $row->user_email_address ?? null,
                        'contact' => $row->user_contact_number ?? null,
                        'status' => 'Active',
                        'user_id' => $row->user_id,
                    ];
                } else {
                    $byEmployee[$key]['user_id'] = $row->user_id;
                    if (empty($byEmployee[$key]['email']) && ! empty($row->user_email_address)) {
                        $byEmployee[$key]['email'] = $row->user_email_address;
                    }
                }
            }
        }

        $now = now();

        foreach ($byEmployee as $row) {
            $existingId = DB::table('persons_table')
                ->whereRaw('UPPER(TRIM(person_employee_id)) = ?', [$row['employee_id']])
                ->value('person_id');

            if ($existingId) {
                $personId = (int) $existingId;
            } else {
                $personId = (int) DB::table('persons_table')->insertGetId([
                    'person_employee_id' => $row['employee_id'],
                    'person_first_name' => $row['first'] ?: null,
                    'person_middle_name' => $row['middle'] ?: null,
                    'person_last_name' => $row['last'] ?: null,
                    'person_full_name' => $row['full'],
                    'person_employment_type' => $row['type'],
                    'person_employment_basis' => $row['basis'],
                    'person_department_id' => $row['department_id'],
                    'person_email_address' => $row['email'],
                    'person_contact_number' => $row['contact'],
                    'person_status' => $row['status'],
                    'person_created_at' => $now,
                    'person_updated_at' => $now,
                ], 'person_id');
            }

            if (! empty($row['reporter_id']) && Schema::hasColumn('reporters_table', 'person_id')) {
                DB::table('reporters_table')
                    ->where('reporter_id', $row['reporter_id'])
                    ->whereNull('person_id')
                    ->update(['person_id' => $personId]);
            }
            if (! empty($row['custodian_id']) && Schema::hasColumn('custodians_table', 'person_id')) {
                DB::table('custodians_table')
                    ->where('custodian_id', $row['custodian_id'])
                    ->whereNull('person_id')
                    ->update(['person_id' => $personId]);
            }
            if (! empty($row['user_id']) && Schema::hasColumn('users_table', 'person_id')) {
                DB::table('users_table')
                    ->where('user_id', $row['user_id'])
                    ->whereNull('person_id')
                    ->update(['person_id' => $personId]);
            }
        }
    }
};
