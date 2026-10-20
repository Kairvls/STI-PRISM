<?php

namespace App\Support;

/**
 * Canonical max lengths / numeric caps for text & number inputs.
 * Keep UI maxlength/max in sync with these server rules.
 */
class InputLimits
{
    public const NAME = 255;

    public const NOTES = 500;

    public const ASSET_TAG = 255;

    public const SERIAL = 255;

    public const EMPLOYEE_ID = 50;

    public const PHONE = 30;

    public const EMAIL = 255;

    public const QTY = 500;

    public const COST = 999999999.99;

    public const NUMBER_DEFAULT = 999999;

    public const SHORT = 100;

    /** Official Receipt / invoice / DR reference numbers (max length) */
    public const DOC_REF_NO = 16;

    /** Letters, digits, hyphen, slash only — e.g. OR-001234, SI/2024/15 */
    public const DOC_REF_PATTERN = '/^[A-Za-z0-9\\/-]*$/';

    /** @deprecated use DOC_REF_NO */
    public const OR_NUMBER = 16;

    public const BRAND = 255;

    public const MODEL = 255;

    public const LOCATION = 255;

    public const CATEGORY_NAME = 255;

    public const ROOM_NAME = 255;

    public const BUILDING_NAME = 255;

    public static function stringMax(int $max): string
    {
        return 'max:'.$max;
    }

    public static function name(): string
    {
        return 'max:'.self::NAME;
    }

    public static function notes(): string
    {
        return 'max:'.self::NOTES;
    }

    public static function qty(): string
    {
        return 'integer|min:1|max:'.self::QTY;
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
