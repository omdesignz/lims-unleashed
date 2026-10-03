<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class SpecimenParameterSelection
{
    /** @return list<int> */
    public static function normalize(mixed $parameters, string $attribute = 'parameters'): array
    {
        if ($parameters === null || $parameters === '') {
            return [];
        }

        if (is_string($parameters)) {
            if (strlen($parameters) > 4000) {
                self::reject($attribute);
            }

            $parameters = explode(',', $parameters);
        }

        if (! is_array($parameters) || ! array_is_list($parameters) || count($parameters) > 100) {
            self::reject($attribute);
        }

        $identifiers = [];
        foreach ($parameters as $parameter) {
            if (is_string($parameter)) {
                $parameter = trim($parameter);
            }

            if ((! is_int($parameter) && ! is_string($parameter))
                || ! preg_match('/^[1-9][0-9]*$/D', (string) $parameter)
                || filter_var($parameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                self::reject($attribute);
            }

            $identifiers[] = (int) $parameter;
        }

        return array_values(array_unique($identifiers));
    }

    private static function reject(string $attribute): never
    {
        throw ValidationException::withMessages([
            $attribute => 'Seleccione uma lista de até 100 identificadores inteiros positivos.',
        ]);
    }
}
