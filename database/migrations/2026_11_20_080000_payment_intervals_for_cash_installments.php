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
            return;
        }

        Schema::table('installment_day_intervals_table', function (Blueprint $table) {
            if (! Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
                $table->string('interval_unit', 20)->default('days')->after('interval_days');
            }
            if (! Schema::hasColumn('installment_day_intervals_table', 'interval_is_preset')) {
                $table->boolean('interval_is_preset')->default(false)->after('interval_unit');
            }
        });

        $now = now();

        DB::table('installment_day_intervals_table')
            ->where('interval_unit', 'days')
            ->whereIn('interval_days', [45, 60])
            ->whereIn('interval_name', ['Every 45 days', 'Every 60 days'])
            ->update([
                'interval_is_active' => 0,
                'interval_updated_at' => $now,
            ]);

        foreach ([15 => '15 days', 30 => '30 days'] as $days => $name) {
            $row = DB::table('installment_day_intervals_table')
                ->where('interval_unit', 'days')
                ->where('interval_days', $days)
                ->orderBy('interval_id')
                ->first();

            if ($row) {
                DB::table('installment_day_intervals_table')
                    ->where('interval_id', $row->interval_id)
                    ->update([
                        'interval_name' => $name,
                        'interval_is_active' => 1,
                        'interval_is_preset' => 0,
                        'interval_updated_at' => $now,
                    ]);
            } else {
                DB::table('installment_day_intervals_table')->insert([
                    'interval_name' => $name,
                    'interval_days' => $days,
                    'interval_unit' => 'days',
                    'interval_is_preset' => 0,
                    'interval_is_active' => 1,
                    'interval_created_at' => $now,
                    'interval_updated_at' => $now,
                ]);
            }
        }

        foreach ([
            'monthly' => 'Months',
            'quarterly' => 'Quarterly',
            'semiannual' => 'Semiannually',
            'annual' => 'Annually',
        ] as $unit => $name) {
            $exists = DB::table('installment_day_intervals_table')
                ->where('interval_unit', $unit)
                ->exists();
            if ($exists) {
                DB::table('installment_day_intervals_table')
                    ->where('interval_unit', $unit)
                    ->update([
                        'interval_name' => $name,
                        'interval_is_preset' => 1,
                        'interval_is_active' => 1,
                        'interval_updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('installment_day_intervals_table')->insert([
                'interval_name' => $name,
                'interval_days' => 0,
                'interval_unit' => $unit,
                'interval_is_preset' => 1,
                'interval_is_active' => 1,
                'interval_created_at' => $now,
                'interval_updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('installment_day_intervals_table')) {
            return;
        }

        if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
            DB::table('installment_day_intervals_table')
                ->whereIn('interval_unit', ['monthly', 'quarterly', 'semiannual', 'annual'])
                ->delete();
        }

        Schema::table('installment_day_intervals_table', function (Blueprint $table) {
            if (Schema::hasColumn('installment_day_intervals_table', 'interval_is_preset')) {
                $table->dropColumn('interval_is_preset');
            }
            if (Schema::hasColumn('installment_day_intervals_table', 'interval_unit')) {
                $table->dropColumn('interval_unit');
            }
        });
    }
};
