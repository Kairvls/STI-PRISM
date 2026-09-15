<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_table')) {
            return;
        }

        if (! Schema::hasColumn('equipment_table', 'equipment_useful_life_years')) {
            Schema::table('equipment_table', function (Blueprint $table) {
                $table->unsignedTinyInteger('equipment_useful_life_years')
                    ->nullable()
                    ->after('equipment_warranty_expiration')
                    ->comment('Expected useful lifespan in years; null uses system default (5)');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipment_table')) {
            return;
        }

        if (Schema::hasColumn('equipment_table', 'equipment_useful_life_years')) {
            Schema::table('equipment_table', function (Blueprint $table) {
                $table->dropColumn('equipment_useful_life_years');
            });
        }
    }
};
