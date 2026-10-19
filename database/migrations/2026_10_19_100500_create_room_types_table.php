<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('room_types_table')) {
            Schema::create('room_types_table', function (Blueprint $table) {
                $table->bigIncrements('room_type_id');
                $table->string('room_type_name', 120)->unique('room_type_name_unique');
                $table->string('room_type_label', 120)->nullable();
                $table->boolean('room_type_is_storage')->default(false);
                $table->boolean('room_type_is_archived')->default(false);
                $table->unsignedInteger('room_type_sort')->default(0);
                $table->timestamp('room_type_created_at')->nullable();
                $table->timestamp('room_type_updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('room_types_table')) {
            return;
        }

        $defaults = [
            ['Lecture Room', 'Lecture Room', false],
            ['Computer Laboratory', 'Computer Laboratory', false],
            ['HM Room', 'HM Room / Bar', false],
            ['Hotel Room Simulation', 'Hotel Room Simulation', false],
            ['Faculty Room', 'Faculty Room', false],
            ['Office', 'Office', false],
            ['Library', 'Library', false],
            ['School Clinic', 'School Clinic', false],
            ['Maintenance', 'Maintenance', false],
            ['Storage / Stockroom', 'Storage / Stockroom', true],
            ['Hospitality Suite', 'Hospitality Suite', false],
            ['Canteen', 'Canteen', false],
            ['Clinic', 'Clinic', false],
            ['Utility', 'Utility', false],
            ['Hallway', 'Hallway', false],
            ['Exit', 'Exit', false],
            ['Restroom', 'Restroom', false],
            ['Elevator', 'Elevator', false],
            ['Stairs', 'Stairs', false],
        ];

        $existing = [];
        if (Schema::hasTable('rooms_table') && Schema::hasColumn('rooms_table', 'room_type')) {
            $existing = DB::table('rooms_table')
                ->whereNotNull('room_type')
                ->where('room_type', '!=', '')
                ->distinct()
                ->orderBy('room_type')
                ->pluck('room_type')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values()
                ->all();
        }

        $now = now();
        $seen = DB::table('room_types_table')
            ->pluck('room_type_name')
            ->map(fn ($n) => mb_strtolower((string) $n))
            ->all();

        $sort = 10;
        foreach ($defaults as [$name, $label, $isStorage]) {
            $key = mb_strtolower($name);
            if (in_array($key, $seen, true)) {
                continue;
            }
            DB::table('room_types_table')->insert([
                'room_type_name' => $name,
                'room_type_label' => $label !== $name ? $label : null,
                'room_type_is_storage' => $isStorage,
                'room_type_is_archived' => false,
                'room_type_sort' => $sort,
                'room_type_created_at' => $now,
                'room_type_updated_at' => $now,
            ]);
            $seen[] = $key;
            $sort += 10;
        }

        foreach ($existing as $name) {
            $key = mb_strtolower($name);
            if (in_array($key, $seen, true)) {
                continue;
            }
            DB::table('room_types_table')->insert([
                'room_type_name' => $name,
                'room_type_label' => null,
                'room_type_is_storage' => mb_strtolower($name) === mb_strtolower('Storage / Stockroom'),
                'room_type_is_archived' => false,
                'room_type_sort' => $sort,
                'room_type_created_at' => $now,
                'room_type_updated_at' => $now,
            ]);
            $seen[] = $key;
            $sort += 10;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types_table');
    }
};
