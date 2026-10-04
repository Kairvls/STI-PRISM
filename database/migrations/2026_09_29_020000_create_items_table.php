<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items_table')) {
            Schema::create('items_table', function (Blueprint $table) {
                $table->bigIncrements('item_id');
                $table->string('item_name', 255)->unique();
                $table->enum('item_status', ['Active', 'Inactive'])->default('Active');
                $table->timestamp('item_created_at')->nullable();
                $table->timestamp('item_updated_at')->nullable();
            });
        }

        $this->seedFromExistingRisItems();
    }

    public function down(): void
    {
        Schema::dropIfExists('items_table');
    }

    private function seedFromExistingRisItems(): void
    {
        if (
            ! Schema::hasTable('requisition_issue_slip_items_table')
            || ! Schema::hasColumn('requisition_issue_slip_items_table', 'ris_item_name_description')
        ) {
            return;
        }

        $known = DB::table('items_table')
            ->pluck('item_name')
            ->mapWithKeys(fn ($name) => [mb_strtolower(trim((string) $name)) => true])
            ->all();

        $now = now();
        $rows = [];

        DB::table('requisition_issue_slip_items_table')
            ->whereNotNull('ris_item_name_description')
            ->orderBy('ris_item_name_description')
            ->pluck('ris_item_name_description')
            ->each(function ($name) use (&$known, &$rows, $now) {
                $name = trim(preg_replace('/\s+/', ' ', (string) $name));
                $key = mb_strtolower($name);
                if ($name === '' || mb_strlen($name) > 255 || isset($known[$key])) {
                    return;
                }

                $known[$key] = true;
                $rows[] = [
                    'item_name' => $name,
                    'item_status' => 'Active',
                    'item_created_at' => $now,
                    'item_updated_at' => $now,
                ];
            });

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('items_table')->insert($chunk);
        }
    }
};
