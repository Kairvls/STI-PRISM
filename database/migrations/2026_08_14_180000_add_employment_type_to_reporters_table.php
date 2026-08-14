<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (! Schema::hasColumn('reporters_table', 'reporter_employment_type')) {
                $table->string('reporter_employment_type', 50)
                    ->nullable()
                    ->after('reporter_full_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (Schema::hasColumn('reporters_table', 'reporter_employment_type')) {
                $table->dropColumn('reporter_employment_type');
            }
        });
    }
};
