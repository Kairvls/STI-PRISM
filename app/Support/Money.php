<?php

namespace App\Support;

class Money
{
    /**
     * Screen and paper format: ₱1,234.56. Empty stays blank.
     */
    public static function format(mixed $value, string $empty = ''): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        if (is_string($value)) {
            $value = str_replace([',', '₱', 'PHP', ' '], '', $value);
        }

        if (! is_numeric($value)) {
            return $empty;
        }

        return '₱'.number_format((float) $value, 2);
    }
}
