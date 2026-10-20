<?php

namespace App\Support;

use App\Models\RoomStatusPaletteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Global Critical / Under Maintenance / Normal colors for layout monitoring.
 */
class RoomStatusPalette
{
    public const DEFAULT_NORMAL = BuildingLayout3D::COLOR_NORMAL;

    public const DEFAULT_UNDER_MAINTENANCE = BuildingLayout3D::COLOR_UNDER_MAINTENANCE;

    public const DEFAULT_CRITICAL = BuildingLayout3D::COLOR_CRITICAL;

    /**
     * @return array{normal: string, under_maintenance: string, critical: string}
     */
    public static function defaults(): array
    {
        return [
            'normal' => self::DEFAULT_NORMAL,
            'under_maintenance' => self::DEFAULT_UNDER_MAINTENANCE,
            'critical' => self::DEFAULT_CRITICAL,
        ];
    }

    /**
     * @return array{normal: string, under_maintenance: string, critical: string}
     */
    public static function colors(): array
    {
        $defaults = self::defaults();

        if (! Schema::hasTable('room_status_palette_settings_table')) {
            return $defaults;
        }

        $row = RoomStatusPaletteSetting::query()->orderBy('room_status_palette_setting_id')->first();

        if (! $row) {
            return $defaults;
        }

        return [
            'normal' => self::normalizeHex($row->palette_normal) ?? $defaults['normal'],
            'under_maintenance' => self::normalizeHex($row->palette_under_maintenance) ?? $defaults['under_maintenance'],
            'critical' => self::normalizeHex($row->palette_critical) ?? $defaults['critical'],
        ];
    }

    public static function colorForStatus(string $status): string
    {
        $colors = self::colors();
        $key = strtolower(trim($status));

        return match (true) {
            $key === BuildingLayout3D::STATUS_CRITICAL || $key === 'critical' => $colors['critical'],
            $key === BuildingLayout3D::STATUS_UNDER_MAINTENANCE
                || str_contains($key, 'maintenance') => $colors['under_maintenance'],
            default => $colors['normal'],
        };
    }

    /**
     * @param  array{normal?:string,under_maintenance?:string,critical?:string}  $input
     * @return array{normal: string, under_maintenance: string, critical: string}
     */
    public static function update(array $input): array
    {
        $defaults = self::defaults();
        $colors = [
            'normal' => self::normalizeHex($input['normal'] ?? null) ?? $defaults['normal'],
            'under_maintenance' => self::normalizeHex($input['under_maintenance'] ?? null)
                ?? $defaults['under_maintenance'],
            'critical' => self::normalizeHex($input['critical'] ?? null) ?? $defaults['critical'],
        ];

        $row = RoomStatusPaletteSetting::query()->orderBy('room_status_palette_setting_id')->first();

        $payload = [
            'palette_normal' => $colors['normal'],
            'palette_under_maintenance' => $colors['under_maintenance'],
            'palette_critical' => $colors['critical'],
            'palette_updated_by' => Auth::id(),
            'palette_updated_at' => now(),
        ];

        if ($row) {
            $row->update($payload);
        } else {
            RoomStatusPaletteSetting::query()->create($payload);
        }

        return $colors;
    }

    public static function reset(): array
    {
        return self::update(self::defaults());
    }

    public static function normalizeHex(mixed $value): ?string
    {
        $raw = strtoupper(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        if ($raw[0] !== '#') {
            $raw = '#'.$raw;
        }

        if (preg_match('/^#([0-9A-F]{3})$/', $raw, $m)) {
            $h = $m[1];

            return '#'.$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
        }

        if (preg_match('/^#[0-9A-F]{6}$/', $raw)) {
            return $raw;
        }

        return null;
    }
}
