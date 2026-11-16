<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('installment_day_intervals_table')) {
            Schema::create('installment_day_intervals_table', function (Blueprint $table) {
                $table->bigIncrements('interval_id');
                $table->string('interval_name', 120);
                $table->unsignedSmallInteger('interval_days');
                $table->boolean('interval_is_active')->default(true);
                $table->timestamp('interval_created_at')->nullable();
                $table->timestamp('interval_updated_at')->nullable();
            });
        }

        if (Schema::hasTable('cash_installment_plans_table')) {
            Schema::table('cash_installment_plans_table', function (Blueprint $table) {
                if (! Schema::hasColumn('cash_installment_plans_table', 'day_interval_id')) {
                    $table->unsignedBigInteger('day_interval_id')->nullable()->after('term_id');
                }
                if (! Schema::hasColumn('cash_installment_plans_table', 'plan_interval_days')) {
                    $table->unsignedSmallInteger('plan_interval_days')->nullable()->after('plan_months');
                }
            });
        }

        if (Schema::hasTable('installment_day_intervals_table')
            && DB::table('installment_day_intervals_table')->count() === 0) {
            $now = now();
            foreach ([
                ['Every 15 days', 15],
                ['Every 30 days', 30],
                ['Every 45 days', 45],
                ['Every 60 days', 60],
            ] as [$name, $days]) {
                DB::table('installment_day_intervals_table')->insert([
                    'interval_name' => $name,
                    'interval_days' => $days,
                    'interval_is_active' => 1,
                    'interval_created_at' => $now,
                    'interval_updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_installment_plans_table')) {
            Schema::table('cash_installment_plans_table', function (Blueprint $table) {
                if (Schema::hasColumn('cash_installment_plans_table', 'plan_interval_days')) {
                    $table->dropColumn('plan_interval_days');
                }
                if (Schema::hasColumn('cash_installment_plans_table', 'day_interval_id')) {
                    $table->dropColumn('day_interval_id');
                }
            });
        }

        Schema::dropIfExists('installment_day_intervals_table');
    }
};
