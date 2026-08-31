<?php

namespace App\Support;

use Illuminate\Http\Request;

class EquipmentViewReturn
{
    public static function viewUrl(int $equipmentId, ?string $returnUrl = null): string
    {
        $url = url('/maintenance/equipment/view/'.$equipmentId);
        $return = $returnUrl ?? (request()->fullUrl() ?: null);

        if (! self::isValidReturnUrl($return) || self::isEquipmentViewUrl($return)) {
            return $url;
        }

        return $url.'?return='.rawurlencode($return);
    }

    /**
     * @return array{url: string, label: string}
     */
    public static function resolve(Request $request): array
    {
        $return = $request->query('return');

        if (is_string($return) && self::isValidReturnUrl($return) && ! self::isEquipmentViewUrl($return)) {
            return [
                'url' => $return,
                'label' => self::labelForUrl($return),
            ];
        }

        $previous = url()->previous();

        if (self::isValidReturnUrl($previous) && ! self::isEquipmentViewUrl($previous)) {
            return [
                'url' => $previous,
                'label' => self::labelForUrl($previous),
            ];
        }

        return [
            'url' => url('/maintenance/equipment/all'),
            'label' => 'All Equipment',
        ];
    }

    public static function labelForUrl(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        return match (true) {
            str_contains($path, '/equipment/inventory') => 'Inventory',
            str_contains($path, '/equipment/all') => 'All Equipment',
            str_contains($path, '/equipment/deployed') => 'Deployed Stocks',
            str_contains($path, '/equipment/categories') => 'Categories',
            str_contains($path, '/equipment/suggested-issues') => 'Suggested Issues',
            str_contains($path, '/equipment/qr-tools') => 'QR Tools',
            str_contains($path, '/equipment/history') => 'Equipment History',
            str_contains($path, '/infrastructure/monitor') => 'Building Layout',
            str_contains($path, '/infrastructure/campus-wizard') => 'Building Layout',
            str_contains($path, '/rooms') => 'Rooms',
            str_contains($path, '/reports') => 'Reports',
            str_contains($path, '/dashboard') => 'Dashboard',
            default => 'Previous page',
        };
    }

    private static function isValidReturnUrl(?string $url): bool
    {
        if (! filled($url)) {
            return false;
        }

        $appRoot = rtrim(url('/'), '/');

        return str_starts_with($url, $appRoot.'/maintenance');
    }

    private static function isEquipmentViewUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_contains($path, '/maintenance/equipment/view/');
    }
}
