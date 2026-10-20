<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_status_palette_settings_table', function (Blueprint $table) {
            $table->bigIncrements('room_status_palette_setting_id');
            $table->string('palette_normal', 32)->default('#3B82F6');
            $table->string('palette_under_maintenance', 32)->default('#F59E0B');
            $table->string('palette_critical', 32)->default('#EF4444');
            $table->unsignedBigInteger('palette_updated_by')->nullable();
            $table->timestamp('palette_updated_at')->nullable();
        });

        DB::table('room_status_palette_settings_table')->insert([
            'palette_normal' => '#3B82F6',
            'palette_under_maintenance' => '#F59E0B',
            'palette_critical' => '#EF4444',
            'palette_updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('room_status_palette_settings_table');
    }
};
