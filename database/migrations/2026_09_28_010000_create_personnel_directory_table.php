<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personnel_directory_table')) {
            Schema::create('personnel_directory_table', function (Blueprint $table) {
                $table->id('personnel_id');
                $table->string('personnel_employee_id', 50)->unique('pd_employee_id_unique');
                $table->string('personnel_employee_number', 10)->nullable()->index('pd_employee_number_idx');
                $table->string('personnel_first_name', 100);
                $table->string('personnel_middle_name', 100)->nullable();
                $table->string('personnel_last_name', 100);
                $table->string('personnel_type', 20);
                $table->unsignedBigInteger('personnel_department_id')->nullable()->index('pd_department_idx');
                $table->string('personnel_email', 255)->nullable()->index('pd_email_idx');
                $table->string('personnel_contact', 50)->nullable();
                $table->string('personnel_status', 20)->default('Active');
                $table->string('personnel_source', 20)->default('manual');
                $table->unsignedBigInteger('personnel_created_by')->nullable();
                $table->timestamp('personnel_created_at')->nullable();
                $table->timestamp('personnel_updated_at')->nullable();
            });
        }

        if (Schema::hasTable('reporter_approval_requests')) {
            Schema::table('reporter_approval_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('reporter_approval_requests', 'directory_personnel_id')) {
                    $table->unsignedBigInteger('directory_personnel_id')->nullable()->after('reviewed_at');
                }
                if (! Schema::hasColumn('reporter_approval_requests', 'directory_verdict')) {
                    $table->string('directory_verdict', 20)->nullable()->after('directory_personnel_id');
                }
                if (! Schema::hasColumn('reporter_approval_requests', 'directory_checks')) {
                    $table->text('directory_checks')->nullable()->after('directory_verdict');
                }
                if (! Schema::hasColumn('reporter_approval_requests', 'override_reason')) {
                    $table->string('override_reason', 500)->nullable()->after('directory_checks');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reporter_approval_requests')) {
            Schema::table('reporter_approval_requests', function (Blueprint $table) {
                foreach (['override_reason', 'directory_checks', 'directory_verdict', 'directory_personnel_id'] as $column) {
                    if (Schema::hasColumn('reporter_approval_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('personnel_directory_table');
    }
};
