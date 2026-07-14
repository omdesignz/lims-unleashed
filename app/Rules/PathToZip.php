<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PathToZip implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //

        $result = str()->endsWith($value, '.zip');

        ! $result ? $fail('O caminho indicado não é válido.') : '';
    }

    public function message()
    {
        return 'O valor indicado deve ser o caminho para um ficheiro ZIP.';
    }
}
