<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('borrowing_fee_settings_table')) {
            Schema::create('borrowing_fee_settings_table', function (Blueprint $table) {
                $table->id('borrowing_fee_setting_id');
                $table->decimal('late_fee_per_day', 10, 2)->default(0);
                $table->decimal('damage_fee', 10, 2)->default(0);
                $table->timestamp('updated_at')->nullable();
            });

            DB::table('borrowing_fee_settings_table')->insert([
                'late_fee_per_day' => 0,
                'damage_fee' => 0,
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_late_days')) {
                $table->unsignedSmallInteger('borrowing_late_days')->nullable();
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_late_fee')) {
                $table->decimal('borrowing_late_fee', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('borrowing_records_table', 'borrowing_damage_fee')) {
                $table->decimal('borrowing_damage_fee', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowing_fee_settings_table');

        if (! Schema::hasTable('borrowing_records_table')) {
            return;
        }

        Schema::table('borrowing_records_table', function (Blueprint $table) {
            foreach (['borrowing_damage_fee', 'borrowing_late_fee', 'borrowing_late_days'] as $column) {
                if (Schema::hasColumn('borrowing_records_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
