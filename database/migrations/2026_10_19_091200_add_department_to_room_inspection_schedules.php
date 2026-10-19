<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('room_inspection_schedules_table')) {
            return;
        }

        if (! Schema::hasColumn('room_inspection_schedules_table', 'room_inspection_schedule_department_id')) {
            Schema::table('room_inspection_schedules_table', function (Blueprint $table) {
                $table->unsignedBigInteger('room_inspection_schedule_department_id')
                    ->nullable()
                    ->after('room_inspection_schedule_room_id')
                    ->index('room_inspection_schedules_department_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('room_inspection_schedules_table')) {
            return;
        }

        if (Schema::hasColumn('room_inspection_schedules_table', 'room_inspection_schedule_department_id')) {
            Schema::table('room_inspection_schedules_table', function (Blueprint $table) {
                $table->dropIndex('room_inspection_schedules_department_idx');
                $table->dropColumn('room_inspection_schedule_department_id');
            });
        }
    }
};
