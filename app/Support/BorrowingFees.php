<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BorrowingFees
{
    /**
     * @return array{late_fee_per_day: float, damage_fee: float}
     */
    public static function rates(): array
    {
        if (! Schema::hasTable('borrowing_fee_settings_table')) {
            return ['late_fee_per_day' => 0.0, 'damage_fee' => 0.0];
        }

        $row = DB::table('borrowing_fee_settings_table')->orderBy('borrowing_fee_setting_id')->first();

        return [
            'late_fee_per_day' => round((float) ($row->late_fee_per_day ?? 0), 2),
            'damage_fee' => round((float) ($row->damage_fee ?? 0), 2),
        ];
    }

    public static function saveRates(float $lateFeePerDay, float $damageFee): void
    {
        if (! Schema::hasTable('borrowing_fee_settings_table')) {
            return;
        }

        $payload = [
            'late_fee_per_day' => round(max(0, $lateFeePerDay), 2),
            'damage_fee' => round(max(0, $damageFee), 2),
            'updated_at' => now(),
        ];

        $id = DB::table('borrowing_fee_settings_table')->orderBy('borrowing_fee_setting_id')->value('borrowing_fee_setting_id');
        if ($id) {
            DB::table('borrowing_fee_settings_table')->where('borrowing_fee_setting_id', $id)->update($payload);

            return;
        }

        DB::table('borrowing_fee_settings_table')->insert($payload);
    }

    public static function daysLate(mixed $expectedReturn): int
    {
        if (! $expectedReturn) {
            return 0;
        }

        try {
            $due = Carbon::parse($expectedReturn)->startOfDay();
        } catch (\Throwable $e) {
            return 0;
        }

        $today = now()->startOfDay();
        if ($today->lte($due)) {
            return 0;
        }

        return (int) round($due->diffInDays($today));
    }

    /**
     * @return array{days:int,late_fee_per_day:float,late_fee:float,damage_fee_rate:float,damage_fee:float,total:float,overdue:bool}
     */
    public static function assess(mixed $expectedReturn, bool $chargeDamage): array
    {
        $rates = self::rates();
        $days = self::daysLate($expectedReturn);
        $lateFee = round($days * $rates['late_fee_per_day'], 2);
        $damageFee = $chargeDamage ? $rates['damage_fee'] : 0.0;

        return [
            'days' => $days,
            'late_fee_per_day' => $rates['late_fee_per_day'],
            'late_fee' => $lateFee,
            'damage_fee_rate' => $rates['damage_fee'],
            'damage_fee' => $damageFee,
            'total' => round($lateFee + $damageFee, 2),
            'overdue' => $days > 0,
        ];
    }

    public static function money(float $amount): string
    {
        return '₱'.number_format($amount, 2);
    }
}
