<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('room_departments_table')) {
            Schema::create('room_departments_table', function (Blueprint $table) {
                $table->bigIncrements('room_department_link_id');
                $table->unsignedBigInteger('room_id')->index('room_departments_room_idx');
                $table->unsignedBigInteger('department_id')->index('room_departments_department_idx');
                $table->timestamp('created_at')->nullable();
                $table->unique(['room_id', 'department_id'], 'room_departments_unique');
            });
        }

        if (! Schema::hasTable('room_departments_table') || ! Schema::hasTable('departments_table')) {
            return;
        }

        $now = now();
        $pairs = [];

        if (Schema::hasColumn('rooms_table', 'room_department_id')) {
            DB::table('rooms_table')
                ->whereNotNull('room_department_id')
                ->orderBy('room_id')
                ->get(['room_id', 'room_department_id'])
                ->each(function ($row) use (&$pairs) {
                    $pairs[(int) $row->room_id.':'.(int) $row->room_department_id] = [
                        'room_id' => (int) $row->room_id,
                        'department_id' => (int) $row->room_department_id,
                    ];
                });
        }

        if (
            Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_department_id')
            && Schema::hasColumn('equipment_table', 'equipment_room_id')
        ) {
            DB::table('equipment_table')
                ->whereNotNull('equipment_room_id')
                ->whereNotNull('equipment_department_id')
                ->where('equipment_inventory_status', '!=', 'Disposed')
                ->groupBy('equipment_room_id', 'equipment_department_id')
                ->select('equipment_room_id', 'equipment_department_id')
                ->get()
                ->each(function ($row) use (&$pairs) {
                    $pairs[(int) $row->equipment_room_id.':'.(int) $row->equipment_department_id] = [
                        'room_id' => (int) $row->equipment_room_id,
                        'department_id' => (int) $row->equipment_department_id,
                    ];
                });
        }

        if (
            Schema::hasTable('custodians_table')
            && Schema::hasColumn('custodians_table', 'custodian_room_id')
            && Schema::hasColumn('custodians_table', 'custodian_department_id')
        ) {
            DB::table('custodians_table')
                ->whereNotNull('custodian_room_id')
                ->whereNotNull('custodian_department_id')
                ->groupBy('custodian_room_id', 'custodian_department_id')
                ->select('custodian_room_id', 'custodian_department_id')
                ->get()
                ->each(function ($row) use (&$pairs) {
                    $pairs[(int) $row->custodian_room_id.':'.(int) $row->custodian_department_id] = [
                        'room_id' => (int) $row->custodian_room_id,
                        'department_id' => (int) $row->custodian_department_id,
                    ];
                });
        }

        if (
            Schema::hasTable('persons_table')
            && Schema::hasColumn('persons_table', 'person_department_id')
            && Schema::hasTable('custodians_table')
            && Schema::hasColumn('custodians_table', 'custodian_room_id')
            && Schema::hasColumn('custodians_table', 'person_id')
        ) {
            DB::table('custodians_table as c')
                ->join('persons_table as p', 'p.person_id', '=', 'c.person_id')
                ->whereNotNull('c.custodian_room_id')
                ->whereNotNull('p.person_department_id')
                ->groupBy('c.custodian_room_id', 'p.person_department_id')
                ->select('c.custodian_room_id', 'p.person_department_id')
                ->get()
                ->each(function ($row) use (&$pairs) {
                    $pairs[(int) $row->custodian_room_id.':'.(int) $row->person_department_id] = [
                        'room_id' => (int) $row->custodian_room_id,
                        'department_id' => (int) $row->person_department_id,
                    ];
                });
        }

        $validDeptIds = DB::table('departments_table')->pluck('department_id')->map(fn ($id) => (int) $id)->all();
        $validRoomIds = DB::table('rooms_table')->pluck('room_id')->map(fn ($id) => (int) $id)->all();

        foreach ($pairs as $pair) {
            if (! in_array($pair['room_id'], $validRoomIds, true) || ! in_array($pair['department_id'], $validDeptIds, true)) {
                continue;
            }

            $exists = DB::table('room_departments_table')
                ->where('room_id', $pair['room_id'])
                ->where('department_id', $pair['department_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('room_departments_table')->insert([
                'room_id' => $pair['room_id'],
                'department_id' => $pair['department_id'],
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_departments_table');
    }
};
