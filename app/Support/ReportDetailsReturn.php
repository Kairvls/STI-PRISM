<?php

namespace App\Support;

use Illuminate\Http\Request;

class ReportDetailsReturn
{
    public static function detailsUrl(int $reportId, ?string $returnUrl = null): string
    {
        $url = url('/maintenance/reports/details/'.$reportId);
        $return = $returnUrl ?? (request()->fullUrl() ?: null);

        if (! self::isValidReturnUrl($return) || self::isDetailsUrl($return)) {
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

        if (is_string($return) && self::isValidReturnUrl($return) && ! self::isDetailsUrl($return)) {
            return [
                'url' => $return,
                'label' => self::labelForUrl($return),
            ];
        }

        $previous = url()->previous();

        if (self::isValidReturnUrl($previous) && ! self::isDetailsUrl($previous)) {
            return [
                'url' => $previous,
                'label' => self::labelForUrl($previous),
            ];
        }

        if (RoleAccess::adminShell()) {
            return [
                'url' => url('/admin/rooms'),
                'label' => 'Rooms',
            ];
        }

        return [
            'url' => url('/maintenance/reports'),
            'label' => 'Reports',
        ];
    }

    public static function labelForUrl(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        return match (true) {
            str_contains($path, '/admin/rooms') => 'Rooms',
            str_contains($path, '/admin/reporters') => 'Reporters',
            str_contains($path, '/admin/persons') => 'Faculty & Staff',
            str_contains($path, '/admin/infrastructure') => 'Rooms Layout',
            str_contains($path, '/personnel-directory') => 'Reporters Directory',
            str_contains($path, '/maintenance/reports') => 'Reports',
            str_contains($path, '/maintenance/building-layout') => 'Campus Monitoring',
            str_contains($path, '/maintenance/dashboard') => 'Dashboard',
            str_contains($path, '/admin/dashboard') => 'Dashboard',
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
            || str_starts_with($url, $appRoot.'/admin');
    }

    private static function isDetailsUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return (bool) preg_match('#/maintenance/reports/details/\d+#', $path);
    }
}
