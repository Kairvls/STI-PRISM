<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Format a Philippine mobile number as 0XXX XXX XXXX when possible.
     */
    public static function formatForDisplay(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $trimmed = trim($number);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return $trimmed;
        }

        // +63 / 63 national mobile → leading 0
        if (str_starts_with($digits, '63') && strlen($digits) >= 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 4);
        }

        // Keep raw digits if we cannot fully format, so edits are never dropped.
        if ($digits !== '') {
            return $digits;
        }

        return $trimmed;
    }

    /**
     * Normalize submitted phone values so PH mobiles are stored with a leading 0.
     */
    public static function normalizeForStorage(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        if (trim($number) === '') {
            return null;
        }

        return self::formatForDisplay($number);
    }

    /**
     * Format a Philippine landline as (02) XXXX-XXXX or (0XX) XXX-XXXX.
     */
    public static function formatLandlineForDisplay(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $trimmed = trim($number);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return $trimmed;
        }

        if (str_starts_with($digits, '63') && strlen($digits) > 10) {
            $digits = '0' . substr($digits, 2);
        }

        if ($digits !== '' && !str_starts_with($digits, '0')) {
            $digits = '0' . $digits;
        }

        $digits = substr($digits, 0, 10);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '02')) {
            $rest = substr($digits, 2);
            $a = substr($rest, 0, 4);
            $b = substr($rest, 4, 4);
            $out = '(02)';
            if ($a !== '') {
                $out .= ' ' . $a;
            }
            if ($b !== '') {
                $out .= '-' . $b;
            }

            return $out;
        }

        $area = substr($digits, 0, 3);
        $rest = substr($digits, 3);
        $a = substr($rest, 0, 3);
        $b = substr($rest, 3, 4);
        $out = '(' . $area . ')';
        if ($a !== '') {
            $out .= ' ' . $a;
        }
        if ($b !== '') {
            $out .= '-' . $b;
        }

        return $out;
    }

    public static function normalizeLandlineForStorage(?string $number): ?string
    {
        return self::formatLandlineForDisplay($number);
    }
}
