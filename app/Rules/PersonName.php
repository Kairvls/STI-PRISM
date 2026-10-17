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
            $fail('The :attribute can only contain letters, spaces, hyphens (-), apostrophes (\'), and the Philippine ñ/Ñ characters. It cannot contain numbers or other symbols.');
        }
    }
}
