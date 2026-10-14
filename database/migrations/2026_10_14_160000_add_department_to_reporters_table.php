<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (! Schema::hasColumn('reporters_table', 'reporter_department_id')) {
                $table->unsignedBigInteger('reporter_department_id')
                    ->nullable()
                    ->after('reporter_employment_type')
                    ->index('reporters_department_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (Schema::hasColumn('reporters_table', 'reporter_department_id')) {
                $table->dropIndex('reporters_department_idx');
                $table->dropColumn('reporter_department_id');
            }
        });
    }
};
