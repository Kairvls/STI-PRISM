<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class EquipmentQrCodes
{
    public const CAMPUS_PREFIX = 'OMC';

    public const AUTO_CATEGORY_CODES = [
        'Computer Equipment' => 'COE',
        'Audio Visual Equipment' => 'AVE',
    ];

    public static function codeForCategoryName(?string $categoryName): ?string
    {
        $name = trim((string) $categoryName);

        return self::AUTO_CATEGORY_CODES[$name] ?? null;
    }

    public static function assignIfEligible(int $equipmentId): ?string
    {
        $equipment = DB::table('equipment_table')
            ->leftJoin(
                'equipment_categories_table',
                'equipment_table.equipment_category_id',
                '=',
                'equipment_categories_table.equipment_category_id'
            )
            ->where('equipment_table.equipment_id', $equipmentId)
            ->select(
                'equipment_table.equipment_id',
                'equipment_table.equipment_qr_code',
                'equipment_categories_table.equipment_category_name'
            )
            ->first();

        if (!$equipment) {
            return null;
        }

        if (filled($equipment->equipment_qr_code)) {
            return $equipment->equipment_qr_code;
        }

        $typeCode = self::codeForCategoryName($equipment->equipment_category_name);

        if (!$typeCode) {
            return null;
        }

        $qrCode = self::nextCode($typeCode);

        DB::table('equipment_table')
            ->where('equipment_id', $equipmentId)
            ->update(['equipment_qr_code' => $qrCode]);

        return $qrCode;
    }

    public static function nextCode(string $typeCode): string
    {
        $date = now()->format('mdY');
        $prefix = self::CAMPUS_PREFIX.'-'.strtoupper($typeCode).'-'.$date.'-';

        do {
            $latest = DB::table('equipment_table')
                ->where('equipment_qr_code', 'like', $prefix.'%')
                ->orderByDesc('equipment_qr_code')
                ->value('equipment_qr_code');

            $sequence = 1;

            if (is_string($latest) && preg_match('/-(\d{4})$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            $qrCode = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        } while (
            DB::table('equipment_table')->where('equipment_qr_code', $qrCode)->exists()
        );

        return $qrCode;
    }
}
