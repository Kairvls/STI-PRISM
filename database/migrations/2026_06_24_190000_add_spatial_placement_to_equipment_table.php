<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_table', function (Blueprint $table): void {
            $table->string('equipment_placement_zone', 50)->nullable()->after('equipment_current_location');
            $table->unsignedTinyInteger('equipment_position_x')->nullable()->after('equipment_placement_zone');
            $table->unsignedTinyInteger('equipment_position_y')->nullable()->after('equipment_position_x');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_table', function (Blueprint $table): void {
            $table->dropColumn(['equipment_placement_zone', 'equipment_position_x', 'equipment_position_y']);
        });
    }
};
