<?php

namespace App\Support;

/**
 * Canonical max lengths / numeric caps for text & number inputs.
 * Keep UI maxlength/max in sync with these server rules.
 *
 * Policy (system-wide):
 * - Money / amounts: 8 digits before decimal + 2 after (99999999.99); commas are display-only
 * - Other numbers (qty, counts): up to 7 digits (9999999)
 * - Names (person, product, equipment, brand, room…): 30 characters
 * - Purpose / short description fields: 400 characters (~2 RIS lined rows)
 * - Longer admin remarks / revision notes stay on NOTES
 */
class InputLimits
{
    /** Person / product / equipment / supplier display names. */
    public const NAME = 30;

    /**
     * Purpose / particulars / short descriptions.
     * Sized to fit the RIS paper PURPOSE block (~2 lined rows) without runaway input.
     */
    public const PURPOSE = 400;

    /** Alias for purpose-length description fields. */
    public const DESCRIPTION = 400;

    /** Longer remarks / revision notes (not purpose text). */
    public const NOTES = 500;

    public const ASSET_TAG = 255;

    /** Serial number: up to 16 letters, digits, or symbols (+ - / etc.). */
    public const SERIAL = 16;

    public const EMPLOYEE_ID = 50;

    public const PHONE = 30;

    public const EMAIL = 255;

    /** Quantity / count fields: max 7 digits. */
    public const QTY = 9999999;

    /** Digits allowed for integer quantity / count fields. */
    public const QTY_DIGITS = 7;

    /** Max money value: 8 digits before decimal + 2 after (99999999.99). */
    public const COST = 99999999.99;

    /** Digits allowed before the decimal point for money fields (commas do not count). */
    public const MONEY_WHOLE_DIGITS = 8;

    /** Digits allowed after the decimal point for money fields. */
    public const MONEY_DECIMAL_DIGITS = 2;

    public const NUMBER_DEFAULT = 9999999;

    public const SHORT = 30;

    /** Official Receipt / invoice / DR reference numbers (max length) */
    public const DOC_REF_NO = 16;

    /** Letters, digits, hyphen, slash only — e.g. OR-001234, SI/2024/15 */
    public const DOC_REF_PATTERN = '/^[A-Za-z0-9\\/-]*$/';

    /** @deprecated use DOC_REF_NO */
    public const OR_NUMBER = 16;

    public const BRAND = 30;

    public const MODEL = 30;

    public const LOCATION = 30;

    public const CATEGORY_NAME = 30;

    public const ROOM_NAME = 30;

    public const BUILDING_NAME = 30;

    public static function stringMax(int $max): string
    {
        return 'max:'.$max;
    }

    public static function name(): string
    {
        return 'max:'.self::NAME;
    }

    public static function purpose(): string
    {
        return 'max:'.self::PURPOSE;
    }

    public static function description(): string
    {
        return 'max:'.self::DESCRIPTION;
    }

    public static function notes(): string
    {
        return 'max:'.self::NOTES;
    }

    public static function qty(): string
    {
        return 'integer|min:1|max:'.self::QTY;
    }

    /**
     * Laravel validation rules for money / amount fields.
     *
     * @return list<string>
     */
    public static function moneyRules(bool $required = false, bool $allowZero = true): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'numeric',
            $allowZero ? 'min:0' : 'gt:0',
            'max:'.self::COST,
            'regex:/^\d{1,'.self::MONEY_WHOLE_DIGITS.'}(\.\d{1,'.self::MONEY_DECIMAL_DIGITS.'})?$/',
        ];

        return $rules;
    }

    /** Laravel validation rules for invoice / DR / OR reference fields. */
    public static function docRefRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:'.self::DOC_REF_NO,
            'regex:'.self::DOC_REF_PATTERN,
        ];
    }
}
