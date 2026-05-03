<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PathToZip implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //

        $result = str()->endsWith($value, '.zip');

        !$result ? $fail('The path is invalid.') : '';
    }

    public function message()
    {
        return 'The given value must be a path to a zip file.';
    }
}
