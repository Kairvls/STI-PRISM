<?php

namespace App\Support;

use App\Rules\PersonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared first / middle / last name cleanup, validation and duplicate-name lookup.
 */
class PersonNames
{
    /** Starts with a letter; then letters, marks, spaces, hyphens, apostrophes and periods. */
    public const PATTERN = "/^[\p{L}\p{M}][\p{L}\p{M}\s'.\-]*$/u";

    public static function clean(?string $value): string
    {
        $value = str_replace(["\u{2019}", "\u{2018}", '`'], "'", (string) $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * @return array<int, mixed>
     */
    public static function rules(bool $required = true, int $max = 100): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:'.$max, new PersonName];
    }

    /**
     * Trims and collapses spaces in the given request fields before validation.
     *
     * @param  array<int, string>  $fields
     */
    public static function cleanRequest(Request $request, array $fields): void
    {
        $cleaned = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $cleaned[$field] = self::clean($request->input($field));
            }
        }
        $request->merge($cleaned);
    }

    public static function matchKey(?string $value): string
    {
        $value = Str::lower(Str::ascii(self::clean($value)));
        $value = preg_replace('/[^a-z]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    public static function valid(?string $value): bool
    {
        $value = self::clean($value);

        return $value === '' || (bool) preg_match(self::PATTERN, $value);
    }

    /**
     * Rows in $table whose first + last name match (ignoring case, accents, spacing and punctuation).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function duplicates(
        string $table,
        string $firstColumn,
        string $lastColumn,
        string $first,
        string $last,
        string $idColumn,
        ?int $ignoreId = null
    ) {
        $firstKey = self::matchKey($first);
        $lastKey = self::matchKey($last);
        if ($firstKey === '' || $lastKey === '') {
            return collect();
        }

        $lastWords = explode(' ', $lastKey);
        $anchor = end($lastWords);

        return DB::table($table)
            ->where($lastColumn, 'LIKE', '%'.$anchor.'%')
            ->when($ignoreId, fn ($q) => $q->where($idColumn, '!=', $ignoreId))
            ->limit(200)
            ->get()
            ->filter(fn ($row) => self::matchKey($row->{$firstColumn} ?? '') === $firstKey
                && self::matchKey($row->{$lastColumn} ?? '') === $lastKey)
            ->values();
    }
}
