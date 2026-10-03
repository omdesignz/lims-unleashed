<?php

namespace App\Services;

use Closure;

class InventoryItemArchiveValidation
{
    /** @return array<string,array<int,string|Closure>> */
    public function rules(): array
    {
        return [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['bail', 'required', function (string $attribute, mixed $value, Closure $fail): void {
                if (! (is_int($value) || (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value)))
                    || filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $fail('O identificador deve ser um número inteiro positivo.');
                }
            }, 'integer', 'min:1', 'distinct'],
        ];
    }
}
