<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Property QR codes for computer sets (separate from each member's equipment QR).
 * Format: OMC-CSET-{mdY}-{####}
 */
class ComputerSetQrCodes
{
    public const TYPE_CODE = 'CSET';

    public static function ready(): bool
    {
        return Schema::hasTable('computer_sets_table')
            && Schema::hasColumn('computer_sets_table', 'computer_set_qr_code');
    }

    public static function assignIfMissing(int $setId): ?string
    {
        if (! self::ready() || $setId <= 0) {
            return null;
        }

        $set = DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->first(['computer_set_id', 'computer_set_qr_code']);

        if (! $set) {
            return null;
        }

        if (filled($set->computer_set_qr_code)) {
            return $set->computer_set_qr_code;
        }

        return self::issue($setId);
    }

    public static function issue(int $setId, bool $regenerate = false): ?string
    {
        if (! self::ready() || $setId <= 0) {
            return null;
        }

        $set = DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->first(['computer_set_id', 'computer_set_qr_code']);

        if (! $set) {
            return null;
        }

        if (! $regenerate && filled($set->computer_set_qr_code)) {
            return $set->computer_set_qr_code;
        }

        $qrCode = self::nextCode();
        $update = ['computer_set_qr_code' => $qrCode];
        if (Schema::hasColumn('computer_sets_table', 'computer_set_qr_issued_at')) {
            $update['computer_set_qr_issued_at'] = now();
        }

        DB::table('computer_sets_table')
            ->where('computer_set_id', $setId)
            ->update($update);

        return $qrCode;
    }

    public static function nextCode(): string
    {
        $date = now()->format('mdY');
        $prefix = EquipmentQrCodes::CAMPUS_PREFIX.'-'.self::TYPE_CODE.'-'.$date.'-';

        do {
            $latestEquipment = DB::table('equipment_table')
                ->where('equipment_qr_code', 'like', $prefix.'%')
                ->orderByDesc('equipment_qr_code')
                ->value('equipment_qr_code');

            $latestSet = self::ready()
                ? DB::table('computer_sets_table')
                    ->where('computer_set_qr_code', 'like', $prefix.'%')
                    ->orderByDesc('computer_set_qr_code')
                    ->value('computer_set_qr_code')
                : null;

            $sequence = 1;
            foreach ([$latestEquipment, $latestSet] as $latest) {
                if (is_string($latest) && preg_match('/-(\d{4})$/', $latest, $matches)) {
                    $sequence = max($sequence, ((int) $matches[1]) + 1);
                }
            }

            $qrCode = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        } while (
            DB::table('equipment_table')->where('equipment_qr_code', $qrCode)->exists()
            || (self::ready() && DB::table('computer_sets_table')->where('computer_set_qr_code', $qrCode)->exists())
        );

        return $qrCode;
    }

    public static function findByCode(string $code): ?object
    {
        if (! self::ready()) {
            return null;
        }

        $code = trim($code);
        if ($code === '') {
            return null;
        }

        return DB::table('computer_sets_table as cs')
            ->leftJoin('rooms_table as r', 'r.room_id', '=', 'cs.computer_set_room_id')
            ->leftJoin('floors_table as f', 'f.floor_id', '=', 'r.room_floor_id')
            ->leftJoin('buildings_table as b', 'b.building_id', '=', 'f.floor_building_id')
            ->where('cs.computer_set_qr_code', $code)
            ->select(
                'cs.*',
                'r.room_name',
                'f.floor_level',
                'b.building_name'
            )
            ->first();
    }
}
