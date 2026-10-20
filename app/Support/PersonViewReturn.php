<?php

namespace App\Support;

use Illuminate\Http\Request;

class PersonViewReturn
{
    public static function showUrl(int $personId, ?string $returnUrl = null): string
    {
        $prefix = request()->is('school-admin') || request()->is('school-admin/*')
            ? '/school-admin/persons/'
            : '/admin/persons/';
        $url = url($prefix.$personId);
        $return = $returnUrl ?? (request()->fullUrl() ?: null);

        if (! self::isValidReturnUrl($return) || self::isPersonShowUrl($return)) {
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

        if (is_string($return) && self::isValidReturnUrl($return) && ! self::isPersonShowUrl($return)) {
            return [
                'url' => $return,
                'label' => self::labelForUrl($return),
            ];
        }

        $previous = url()->previous();

        if (self::isValidReturnUrl($previous) && ! self::isPersonShowUrl($previous)) {
            return [
                'url' => $previous,
                'label' => self::labelForUrl($previous),
            ];
        }

        $isSchoolAdmin = request()->is('school-admin') || request()->is('school-admin/*');

        return [
            'url' => $isSchoolAdmin ? url('/school-admin/departments') : url('/admin/persons'),
            'label' => $isSchoolAdmin ? 'Departments' : 'All faculty & staff',
        ];
    }

    public static function labelForUrl(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');

        return match (true) {
            str_contains($path, '/departments') => 'Departments',
            str_contains($path, '/property-assignments') => 'Property Assignment',
            str_contains($path, '/reporters') => 'Reporters',
            str_contains($path, '/users') => 'Users',
            str_contains($path, '/personnel-directory') => 'Personnel Directory',
            str_contains($path, '/operations/equipment') => 'Equipment Monitor',
            str_contains($path, '/admin/persons'), str_contains($path, '/school-admin/persons') => 'All faculty & staff',
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

        return str_starts_with($url, $appRoot.'/admin')
            || str_starts_with($url, $appRoot.'/maintenance')
            || str_starts_with($url, $appRoot.'/school-admin');
    }

    private static function isPersonShowUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return (bool) preg_match('#/(?:admin|school-admin)/persons/\d+#', $path);
    }
}
