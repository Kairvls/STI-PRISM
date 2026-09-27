<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULTS = [
        'Academic Affairs',
        'Accounting',
        'Administration',
        'Admission',
        'Cashier',
        'Clinic',
        'Faculty',
        'Guidance',
        'Library',
        'Maintenance',
        'MIS / IT',
        'Registrar',
        'Security',
        'Student Affairs',
        'Utility',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('departments_table')) {
            Schema::create('departments_table', function (Blueprint $table) {
                $table->id('department_id');
                $table->string('department_name', 120)->unique('dept_name_unique');
                $table->string('department_description', 255)->nullable();
                $table->boolean('department_is_archived')->default(false);
                $table->unsignedBigInteger('department_created_by')->nullable();
                $table->timestamp('department_created_at')->nullable();
                $table->timestamp('department_updated_at')->nullable();
            });
        }

        $names = self::DEFAULTS;
        if (Schema::hasColumn('custodians_table', 'custodian_department')) {
            $saved = DB::table('custodians_table')
                ->whereNotNull('custodian_department')
                ->where('custodian_department', '!=', '')
                ->distinct()
                ->pluck('custodian_department')
                ->map(fn ($name) => trim((string) $name))
                ->all();
            $names = array_merge($names, $saved);
        }

        $now = now();
        $existing = DB::table('departments_table')->pluck('department_name')->map(fn ($n) => mb_strtolower($n))->all();
        foreach (array_unique($names) as $name) {
            if ($name === '' || in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }
            DB::table('departments_table')->insert([
                'department_name' => $name,
                'department_created_at' => $now,
                'department_updated_at' => $now,
            ]);
            $existing[] = mb_strtolower($name);
        }

        if (! Schema::hasColumn('custodians_table', 'custodian_department_id')) {
            Schema::table('custodians_table', function (Blueprint $table) {
                $table->unsignedBigInteger('custodian_department_id')->nullable()->after('custodian_position')->index('cust_department_idx');
            });
        }

        if (Schema::hasColumn('custodians_table', 'custodian_department')) {
            DB::statement('UPDATE custodians_table c
                JOIN departments_table d ON d.department_name = TRIM(c.custodian_department)
                SET c.custodian_department_id = d.department_id');

            Schema::table('custodians_table', function (Blueprint $table) {
                $table->dropColumn('custodian_department');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('custodians_table') && ! Schema::hasColumn('custodians_table', 'custodian_department')) {
            Schema::table('custodians_table', function (Blueprint $table) {
                $table->string('custodian_department', 120)->nullable()->after('custodian_position');
            });
        }

        if (Schema::hasTable('departments_table') && Schema::hasColumn('custodians_table', 'custodian_department_id')) {
            DB::statement('UPDATE custodians_table c
                JOIN departments_table d ON d.department_id = c.custodian_department_id
                SET c.custodian_department = d.department_name');

            Schema::table('custodians_table', function (Blueprint $table) {
                $table->dropIndex('cust_department_idx');
                $table->dropColumn('custodian_department_id');
            });
        }

        Schema::dropIfExists('departments_table');
    }
};
