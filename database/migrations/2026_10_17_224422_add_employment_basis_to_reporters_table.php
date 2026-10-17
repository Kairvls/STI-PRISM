<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (! Schema::hasColumn('reporters_table', 'reporter_employment_basis')) {
                $table->string('reporter_employment_basis', 50)
                    ->nullable()
                    ->after('reporter_employment_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reporters_table', function (Blueprint $table) {
            if (Schema::hasColumn('reporters_table', 'reporter_employment_basis')) {
                $table->dropColumn('reporter_employment_basis');
            }
        });
    }
};
