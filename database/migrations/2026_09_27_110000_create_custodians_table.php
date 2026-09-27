<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            Schema::create('custodians_table', function (Blueprint $table) {
                $table->id('custodian_id');
                $table->unsignedBigInteger('custodian_reporter_id')->nullable()->index('cust_reporter_idx');
                $table->string('custodian_employee_id', 50)->nullable()->index('cust_employee_idx');
                $table->string('custodian_full_name', 150);
                $table->string('custodian_position', 120)->nullable();
                $table->string('custodian_department', 120)->nullable();
                $table->unsignedBigInteger('custodian_room_id')->nullable()->index('cust_room_idx');
                $table->string('custodian_email_address', 150)->nullable();
                $table->string('custodian_contact_number', 50)->nullable();
                $table->string('custodian_status', 20)->default('Active');
                $table->text('custodian_notes')->nullable();
                $table->unsignedBigInteger('custodian_created_by')->nullable();
                $table->timestamp('custodian_created_at')->nullable();
                $table->timestamp('custodian_updated_at')->nullable();
            });
        }

        $this->seedFromReporters();

        if (Schema::hasTable('property_assignments_table')
            && Schema::hasColumn('property_assignments_table', 'assignment_reporter_id')) {
            Schema::table('property_assignments_table', function (Blueprint $table) {
                if (! Schema::hasColumn('property_assignments_table', 'assignment_custodian_id')) {
                    $table->unsignedBigInteger('assignment_custodian_id')->nullable()->after('assignment_equipment_id');
                }
            });

            DB::statement('UPDATE property_assignments_table pa
                JOIN custodians_table c ON c.custodian_reporter_id = pa.assignment_reporter_id
                SET pa.assignment_custodian_id = c.custodian_id');

            Schema::table('property_assignments_table', function (Blueprint $table) {
                $table->dropIndex('pa_reporter_status_idx');
                $table->dropColumn('assignment_reporter_id');
                $table->index(['assignment_custodian_id', 'assignment_status'], 'pa_custodian_status_idx');
            });
        }

        if (Schema::hasTable('semester_inspection_items_table')
            && Schema::hasColumn('semester_inspection_items_table', 'item_custodian_reporter_id')) {
            Schema::table('semester_inspection_items_table', function (Blueprint $table) {
                if (! Schema::hasColumn('semester_inspection_items_table', 'item_custodian_id')) {
                    $table->unsignedBigInteger('item_custodian_id')->nullable()->index('sii_custodian_person_idx');
                }
            });

            DB::statement('UPDATE semester_inspection_items_table i
                JOIN custodians_table c ON c.custodian_reporter_id = i.item_custodian_reporter_id
                SET i.item_custodian_id = c.custodian_id');

            Schema::table('semester_inspection_items_table', function (Blueprint $table) {
                $table->dropIndex('sii_custodian_idx');
                $table->dropColumn('item_custodian_reporter_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('property_assignments_table')
            && Schema::hasColumn('property_assignments_table', 'assignment_custodian_id')) {
            Schema::table('property_assignments_table', function (Blueprint $table) {
                $table->unsignedBigInteger('assignment_reporter_id')->nullable()->after('assignment_equipment_id');
            });

            DB::statement('UPDATE property_assignments_table pa
                JOIN custodians_table c ON c.custodian_id = pa.assignment_custodian_id
                SET pa.assignment_reporter_id = c.custodian_reporter_id');

            Schema::table('property_assignments_table', function (Blueprint $table) {
                $table->dropIndex('pa_custodian_status_idx');
                $table->dropColumn('assignment_custodian_id');
                $table->index(['assignment_reporter_id', 'assignment_status'], 'pa_reporter_status_idx');
            });
        }

        if (Schema::hasTable('semester_inspection_items_table')
            && Schema::hasColumn('semester_inspection_items_table', 'item_custodian_id')) {
            Schema::table('semester_inspection_items_table', function (Blueprint $table) {
                $table->unsignedBigInteger('item_custodian_reporter_id')->nullable()->index('sii_custodian_idx');
            });

            DB::statement('UPDATE semester_inspection_items_table i
                JOIN custodians_table c ON c.custodian_id = i.item_custodian_id
                SET i.item_custodian_reporter_id = c.custodian_reporter_id');

            Schema::table('semester_inspection_items_table', function (Blueprint $table) {
                $table->dropIndex('sii_custodian_person_idx');
                $table->dropColumn('item_custodian_id');
            });
        }

        Schema::dropIfExists('custodians_table');
    }

    private function seedFromReporters(): void
    {
        if (! Schema::hasTable('reporters_table')) {
            return;
        }

        $hasType = Schema::hasColumn('reporters_table', 'reporter_employment_type');
        $now = now();

        DB::table('reporters_table')
            ->whereNotIn('reporter_id', DB::table('custodians_table')->whereNotNull('custodian_reporter_id')->select('custodian_reporter_id'))
            ->orderBy('reporter_id')
            ->get()
            ->each(function ($reporter) use ($hasType, $now) {
                DB::table('custodians_table')->insert([
                    'custodian_reporter_id' => $reporter->reporter_id,
                    'custodian_employee_id' => $reporter->reporter_employee_id,
                    'custodian_full_name' => $reporter->reporter_full_name,
                    'custodian_position' => $hasType ? ($reporter->reporter_employment_type ?? null) : null,
                    'custodian_email_address' => $reporter->reporter_email_address ?? null,
                    'custodian_contact_number' => $reporter->reporter_contact_number ?? null,
                    'custodian_status' => ($reporter->reporter_status ?? 'Active') === 'Active' ? 'Active' : 'Inactive',
                    'custodian_created_at' => $now,
                    'custodian_updated_at' => $now,
                ]);
            });
    }
};
