<?php

namespace App\Support;

use Closure;

class PhoneNumber
{
    /**
     * Format a phone number for display.
     * Philippine mobiles → 0XXX XXX XXXX; other E.164 values keep their international form.
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

        // Non-PH international values stored as +E.164 — keep as-is for the country picker.
        if (str_starts_with($trimmed, '+') && ! preg_match('/^\+63\b/', $trimmed)) {
            $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

            return $digits !== '' ? '+'.$digits : $trimmed;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return $trimmed;
        }

        // +63 / 63 national mobile → leading 0
        if (str_starts_with($digits, '63') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return substr($digits, 0, 4).' '.substr($digits, 4, 3).' '.substr($digits, 7, 4);
        }

        // International without explicit PH shape — store/display as +E.164.
        if (! str_starts_with($digits, '0') && strlen($digits) >= 8 && strlen($digits) <= 15) {
            return '+'.$digits;
        }

        // Keep raw digits if we cannot fully format, so edits are never dropped.
        if ($digits !== '') {
            return $digits;
        }

        return $trimmed;
    }

    /**
     * Normalize submitted phone values for storage.
     * PH mobiles → 0XXX XXX XXXX; other countries → +E.164.
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
     * Digits only (no + / spaces). Empty string when none.
     */
    public static function digits(?string $number): string
    {
        if ($number === null) {
            return '';
        }

        return preg_replace('/\D+/', '', trim($number)) ?? '';
    }

    /**
     * Accept PH mobiles and general E.164 international numbers.
     */
    public static function isValid(?string $number): bool
    {
        if ($number === null) {
            return false;
        }

        $trimmed = trim($number);
        if ($trimmed === '') {
            return false;
        }

        $hasPlus = str_starts_with($trimmed, '+');
        $digits = self::digits($trimmed);
        if ($digits === '') {
            return false;
        }

        // PH mobile: 09XXXXXXXXX, 9XXXXXXXXX, or 63XXXXXXXXXX
        if (str_starts_with($digits, '63') && strlen($digits) >= 12 && strlen($digits) <= 13) {
            $national = '0'.substr($digits, 2);

            return strlen($national) === 11 && str_starts_with($national, '09');
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return true;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return true;
        }

        // International E.164: 8–15 digits, country code cannot start with 0.
        if ($hasPlus || ! str_starts_with($digits, '0')) {
            return strlen($digits) >= 8 && strlen($digits) <= 15 && $digits[0] !== '0';
        }

        return false;
    }

    /**
     * Laravel validation rules for optional/required contact number fields.
     *
     * @return array<int, string|Closure>
     */
    public static function rules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:50',
            function (string $attribute, mixed $value, Closure $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }

                if (! self::isValid((string) $value)) {
                    $fail('Enter a valid mobile number for the selected country.');
                }
            },
        ];
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
            $digits = '0'.substr($digits, 2);
        }

        if ($digits !== '' && ! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
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
                $out .= ' '.$a;
            }
            if ($b !== '') {
                $out .= '-'.$b;
            }

            return $out;
        }

        $area = substr($digits, 0, 3);
        $rest = substr($digits, 3);
        $a = substr($rest, 0, 3);
        $b = substr($rest, 3, 4);
        $out = '('.$area.')';
        if ($a !== '') {
            $out .= ' '.$a;
        }
        if ($b !== '') {
            $out .= '-'.$b;
        }

        return $out;
    }

    public static function normalizeLandlineForStorage(?string $number): ?string
    {
        return self::formatLandlineForDisplay($number);
    }
}
