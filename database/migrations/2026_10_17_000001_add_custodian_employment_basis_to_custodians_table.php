<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            return;
        }

        if (! Schema::hasColumn('custodians_table', 'custodian_employment_basis')) {
            Schema::table('custodians_table', function (Blueprint $table) {
                $table->string('custodian_employment_basis', 50)->nullable()->after('custodian_position');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('custodians_table')) {
            return;
        }

        if (Schema::hasColumn('custodians_table', 'custodian_employment_basis')) {
            Schema::table('custodians_table', function (Blueprint $table) {
                $table->dropColumn('custodian_employment_basis');
            });
        }
    }
};
