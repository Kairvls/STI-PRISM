<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_event_names_table')) {
            Schema::create('borrowing_event_names_table', function (Blueprint $table) {
                $table->bigIncrements('event_name_id');
                $table->string('event_name_label', 50);
                $table->boolean('event_name_is_active')->default(true);
                $table->unsignedInteger('event_name_sort')->default(0);
                $table->timestamp('event_name_created_at')->nullable();
                $table->timestamp('event_name_updated_at')->nullable();

                $table->unique('event_name_label', 'borrowing_event_name_label_unique');
            });
        }

        if (! Schema::hasTable('borrowing_event_names_table')) {
            return;
        }

        $fromRecords = [];
        if (
            Schema::hasTable('borrowing_records_table')
            && Schema::hasColumn('borrowing_records_table', 'borrowing_event_name')
        ) {
            $fromRecords = DB::table('borrowing_records_table')
                ->whereNotNull('borrowing_event_name')
                ->where('borrowing_event_name', '!=', '')
                ->distinct()
                ->orderBy('borrowing_event_name')
                ->pluck('borrowing_event_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values()
                ->all();
        }

        $now = now();
        $seen = DB::table('borrowing_event_names_table')
            ->pluck('event_name_label')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        $sort = 10;
        foreach ($fromRecords as $name) {
            $name = mb_substr(trim((string) $name), 0, 50);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (in_array($key, $seen, true)) {
                continue;
            }

            DB::table('borrowing_event_names_table')->insert([
                'event_name_label' => $name,
                'event_name_is_active' => true,
                'event_name_sort' => $sort,
                'event_name_created_at' => $now,
                'event_name_updated_at' => $now,
            ]);
            $seen[] = $key;
            $sort += 10;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowing_event_names_table');
    }
};
