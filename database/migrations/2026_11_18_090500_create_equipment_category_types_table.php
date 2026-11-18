<?php

use App\Support\SuggestedIssues;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_category_types_table')) {
            Schema::create('equipment_category_types_table', function (Blueprint $table) {
                $table->bigIncrements('type_id');
                // Match equipment_categories_table.equipment_category_id (signed bigint).
                $table->bigInteger('equipment_category_id');
                $table->string('type_name', 30);
                $table->string('type_aliases', 255)->nullable();
                $table->boolean('type_is_active')->default(true);
                $table->timestamp('type_created_at')->nullable();
                $table->timestamp('type_updated_at')->nullable();

                $table->unique(
                    ['equipment_category_id', 'type_name'],
                    'equipment_category_type_unique'
                );
                $table->foreign('equipment_category_id', 'eq_cat_type_category_fk')
                    ->references('equipment_category_id')
                    ->on('equipment_categories_table')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('equipment_category_types_table')) {
            return;
        }

        $categories = DB::table('equipment_categories_table')
            ->get(['equipment_category_id', 'equipment_category_name'])
            ->keyBy(fn ($row) => mb_strtolower(trim((string) $row->equipment_category_name)));

        $needles = SuggestedIssues::componentNeedles();
        $now = now();
        $seen = DB::table('equipment_category_types_table')
            ->get(['equipment_category_id', 'type_name'])
            ->map(fn ($row) => ((int) $row->equipment_category_id).'|'.mb_strtolower(trim((string) $row->type_name)))
            ->all();

        // Do not seed bare "wire" / "cable" aliases — those caused wrong Computer Equipment predictions.
        $skipAliasNeedles = ['wire', 'cable', 'cord'];

        foreach (SuggestedIssues::defaultIssues() as $categoryName => $components) {
            $category = $categories->get(mb_strtolower($categoryName));
            if (! $category) {
                continue;
            }

            foreach (array_keys($components) as $typeName) {
                $typeName = mb_substr(trim((string) $typeName), 0, 30);
                if ($typeName === '') {
                    continue;
                }

                $key = ((int) $category->equipment_category_id).'|'.mb_strtolower($typeName);
                if (in_array($key, $seen, true)) {
                    continue;
                }

                $aliases = collect($needles[$typeName] ?? [])
                    ->map(fn ($n) => trim((string) $n))
                    ->filter()
                    ->reject(fn ($n) => in_array(mb_strtolower($n), $skipAliasNeedles, true)
                        || mb_strtolower($n) === mb_strtolower($typeName))
                    ->unique(fn ($n) => mb_strtolower($n))
                    ->values();

                DB::table('equipment_category_types_table')->insert([
                    'equipment_category_id' => (int) $category->equipment_category_id,
                    'type_name' => $typeName,
                    'type_aliases' => $aliases->isEmpty() ? null : $aliases->implode(', '),
                    'type_is_active' => true,
                    'type_created_at' => $now,
                    'type_updated_at' => $now,
                ]);
                $seen[] = $key;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_category_types_table');
    }
};
