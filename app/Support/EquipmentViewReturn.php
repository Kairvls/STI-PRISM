<?php

namespace App\Support;

use Illuminate\Http\Request;

class EquipmentViewReturn
{
    public static function viewUrl(int $equipmentId, ?string $returnUrl = null): string
    {
        $url = url('/maintenance/equipment/view/'.$equipmentId);
        $return = $returnUrl ?? (request()->fullUrl() ?: null);

        if (! self::isValidReturnUrl($return) || self::isEquipmentDetailUrl($return)) {
            return $url;
        }

        return $url.'?return='.rawurlencode($return);
    }

    /** Portal monitor detail URL (admin / school-admin operations). */
    public static function monitorShowUrl(int $equipmentId, ?string $returnUrl = null): string
    {
        $url = AdminPortal::route('operations.equipment.show', $equipmentId);
        $return = $returnUrl ?? (request()->fullUrl() ?: null);

        if (! self::isValidReturnUrl($return) || self::isEquipmentDetailUrl($return)) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'return='.rawurlencode($return);
    }

    /**
     * @return array{url: string, label: string}
     */
    public static function resolve(Request $request): array
    {
        $return = $request->query('return');

        if (is_string($return) && self::isValidReturnUrl($return) && ! self::isEquipmentDetailUrl($return)) {
            return [
                'url' => $return,
                'label' => self::labelForUrl($return),
            ];
        }

        $previous = url()->previous();

        if (self::isValidReturnUrl($previous) && ! self::isEquipmentDetailUrl($previous)) {
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

    /**
     * Back target for the shared operations equipment detail page.
     *
     * @return array{url: string, label: string}
     */
    public static function resolveForMonitor(Request $request): array
    {
        $return = $request->query('return');

        if (is_string($return) && self::isValidReturnUrl($return) && ! self::isEquipmentDetailUrl($return)) {
            return [
                'url' => $return,
                'label' => self::labelForUrl($return),
            ];
        }

        $previous = url()->previous();

        if (self::isValidReturnUrl($previous) && ! self::isEquipmentDetailUrl($previous)) {
            return [
                'url' => $previous,
                'label' => self::labelForUrl($previous),
            ];
        }

        return [
            'url' => AdminPortal::route('operations.equipment'),
            'label' => 'Equipment Monitor',
        ];
    }

    public static function labelForUrl(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        return match (true) {
            str_contains($path, '/equipment/deployed'), str_contains($path, '/admin/inventory/deployed') => 'Deployed Equipment',
            str_contains($path, '/equipment/inventory'), str_contains($path, '/admin/inventory') => 'Inventory',
            str_contains($path, '/equipment/all') => 'All Equipment',
            str_contains($path, '/equipment/categories') => 'Classifications',
            str_contains($path, '/equipment/suggested-issues') => 'Suggested Issues',
            str_contains($path, '/qr-tools') => 'QR Tools',
            str_contains($path, '/equipment/history') => 'Equipment History',
            str_contains($path, '/infrastructure') => 'Building Layout',
            str_contains($path, '/departments') => 'Departments',
            str_contains($path, '/property-assignments') => 'Property Assignment',
            str_contains($path, '/persons') => 'Faculty & Staff',
            str_contains($path, '/operations/equipment') => 'Equipment Monitor',
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

        return str_starts_with($url, $appRoot.'/maintenance')
            || str_starts_with($url, $appRoot.'/admin')
            || str_starts_with($url, $appRoot.'/school-admin');
    }

    private static function isEquipmentDetailUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_contains($path, '/maintenance/equipment/view/')
            || (bool) preg_match('#/operations/equipment/\d+#', $path);
    }

    /** @deprecated use isEquipmentDetailUrl */
    private static function isEquipmentViewUrl(string $url): bool
    {
        return self::isEquipmentDetailUrl($url);
    }
}
