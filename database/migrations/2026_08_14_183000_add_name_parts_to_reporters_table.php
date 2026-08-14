<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (! Schema::hasColumn('reporters_table', 'reporter_first_name')) {
                $table->string('reporter_first_name', 100)->nullable()->after('reporter_employee_id');
            }
            if (! Schema::hasColumn('reporters_table', 'reporter_middle_name')) {
                $table->string('reporter_middle_name', 100)->nullable()->after('reporter_first_name');
            }
            if (! Schema::hasColumn('reporters_table', 'reporter_last_name')) {
                $table->string('reporter_last_name', 100)->nullable()->after('reporter_middle_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            foreach (['reporter_first_name', 'reporter_middle_name', 'reporter_last_name'] as $column) {
                if (Schema::hasColumn('reporters_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
