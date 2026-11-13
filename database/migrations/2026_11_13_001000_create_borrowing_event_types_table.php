<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_event_types_table')) {
            Schema::create('borrowing_event_types_table', function (Blueprint $table) {
                $table->bigIncrements('event_type_id');
                $table->string('event_type_name', 50);
                $table->boolean('event_type_is_active')->default(true);
                $table->unsignedInteger('event_type_sort')->default(0);
                $table->timestamp('event_type_created_at')->nullable();
                $table->timestamp('event_type_updated_at')->nullable();

                $table->unique('event_type_name', 'borrowing_event_type_name_unique');
            });
        }

        if (! Schema::hasTable('borrowing_event_types_table')) {
            return;
        }

        $defaults = [
            'Campus activity',
            'Other school / partner',
            'City / LGU event',
            'Other',
        ];

        // Keep legacy stored codes selectable for older borrow rows.
        $legacy = [
            'Campus',
            'School Partner',
            'City',
        ];

        $fromRecords = [];
        if (
            Schema::hasTable('borrowing_records_table')
            && Schema::hasColumn('borrowing_records_table', 'borrowing_event_type')
        ) {
            $fromRecords = DB::table('borrowing_records_table')
                ->whereNotNull('borrowing_event_type')
                ->where('borrowing_event_type', '!=', '')
                ->distinct()
                ->orderBy('borrowing_event_type')
                ->pluck('borrowing_event_type')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values()
                ->all();
        }

        $now = now();
        $seen = DB::table('borrowing_event_types_table')
            ->pluck('event_type_name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        $sort = 10;
        foreach (array_merge($defaults, $legacy, $fromRecords) as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (in_array($key, $seen, true)) {
                continue;
            }

            DB::table('borrowing_event_types_table')->insert([
                'event_type_name' => mb_substr($name, 0, 50),
                'event_type_is_active' => true,
                'event_type_sort' => $sort,
                'event_type_created_at' => $now,
                'event_type_updated_at' => $now,
            ]);
            $seen[] = $key;
            $sort += 10;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowing_event_types_table');
    }
};
