<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Remembers rendered form preview HTML so the list can open it without a second request.
 */
class CachedFormPreview
{
    public static function html(string $cacheKey, callable $render): string
    {
        try {
            return Cache::remember('sa-form-preview:'.$cacheKey, now()->addMinutes(10), $render);
        } catch (\Throwable $e) {
            return $render();
        }
    }
}
