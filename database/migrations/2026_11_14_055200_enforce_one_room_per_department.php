<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'room_departments_department_unique';

    public function up(): void
    {
        if (! Schema::hasTable('room_departments_table')) {
            return;
        }

        // Keep one room per department (prefer lowest room_id).
        $duplicates = DB::table('room_departments_table')
            ->select('department_id')
            ->groupBy('department_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('department_id');

        foreach ($duplicates as $departmentId) {
            $keepRoomId = DB::table('room_departments_table')
                ->where('department_id', (int) $departmentId)
                ->orderBy('room_id')
                ->value('room_id');

            if (! $keepRoomId) {
                continue;
            }

            DB::table('room_departments_table')
                ->where('department_id', (int) $departmentId)
                ->where('room_id', '!=', (int) $keepRoomId)
                ->delete();
        }

        if ($this->hasIndex()) {
            return;
        }

        Schema::table('room_departments_table', function (Blueprint $table) {
            $table->unique('department_id', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('room_departments_table') || ! $this->hasIndex()) {
            return;
        }

        Schema::table('room_departments_table', function (Blueprint $table) {
            $table->dropUnique(self::INDEX);
        });
    }

    private function hasIndex(): bool
    {
        return collect(Schema::getIndexes('room_departments_table'))->contains('name', self::INDEX);
    }
};
