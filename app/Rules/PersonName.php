<?php

namespace App\Rules;

use App\Support\PersonNames;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = PersonNames::clean(is_scalar($value) ? (string) $value : '');

        if ($value !== '' && ! preg_match(PersonNames::PATTERN, $value)) {
            $fail('The :attribute can only use letters, spaces, hyphens (-), apostrophes (\') and periods (.), and must start with a letter.');
        }
    }
}
