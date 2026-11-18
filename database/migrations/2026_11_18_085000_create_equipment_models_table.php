<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_models_table')) {
            Schema::create('equipment_models_table', function (Blueprint $table) {
                $table->bigIncrements('model_id');
                $table->string('model_name', 30);
                $table->boolean('model_is_active')->default(true);
                $table->timestamp('model_created_at')->nullable();
                $table->timestamp('model_updated_at')->nullable();

                $table->unique('model_name', 'equipment_model_name_unique');
            });
        }

        if (! Schema::hasTable('equipment_models_table')) {
            return;
        }

        $fromEquipment = [];
        if (
            Schema::hasTable('equipment_table')
            && Schema::hasColumn('equipment_table', 'equipment_model')
        ) {
            $fromEquipment = DB::table('equipment_table')
                ->whereNotNull('equipment_model')
                ->where('equipment_model', '!=', '')
                ->distinct()
                ->orderBy('equipment_model')
                ->pluck('equipment_model')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values()
                ->all();
        }

        $now = now();
        $seen = DB::table('equipment_models_table')
            ->pluck('model_name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        foreach ($fromEquipment as $name) {
            $name = mb_substr(trim((string) $name), 0, 30);
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);
            if (in_array($key, $seen, true)) {
                continue;
            }

            DB::table('equipment_models_table')->insert([
                'model_name' => $name,
                'model_is_active' => true,
                'model_created_at' => $now,
                'model_updated_at' => $now,
            ]);
            $seen[] = $key;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_models_table');
    }
};
