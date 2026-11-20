<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('equipment_table', 'equipment_is_borrowable')) {
            return;
        }

        Schema::table('equipment_table', function (Blueprint $table) {
            $table->dropColumn('equipment_is_borrowable');
        });
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('equipment_table')
            || Schema::hasColumn('equipment_table', 'equipment_is_borrowable')
        ) {
            return;
        }

        Schema::table('equipment_table', function (Blueprint $table) {
            $table->boolean('equipment_is_borrowable')->default(true)->after('equipment_rotation');
        });
    }
};
