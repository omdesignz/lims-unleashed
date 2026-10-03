<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WorksheetValidation
{
    /** @return array<string, array<mixed>> */
    public function rules(array $data): array
    {
        $this->checkBudget($data);

        return [
            'lab_id' => ['missing'], 'user_id' => ['missing'], 'analysis_id' => ['missing'],
            'name' => ['required', 'string', 'max:255'],
            'worksheets' => ['required', 'array:sheets'],
            'worksheets.sheets' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'worksheets.sheets.*' => ['required', 'array:id,name,data'],
            'worksheets.sheets.*.id' => ['required', 'string', 'max:80', 'distinct:strict'],
            'worksheets.sheets.*.name' => ['required', 'string', 'max:120'],
            'worksheets.sheets.*.data' => ['required', 'array', 'list', 'min:1', 'max:2000'],
            'worksheets.sheets.*.data.*' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'worksheets.sheets.*.data.*.*' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_scalar($value) || (is_float($value) && ! is_finite($value)) || mb_strlen((string) $value) > 2000) {
                    $fail('Cada célula deve conter um valor simples com até 2.000 caracteres.');
                }
            }],
        ];
    }

    /** @return array{name: string, worksheets: array{sheets: array}} */
    public function validate(array $data): array
    {
        return Validator::make($data, $this->rules($data), [], $this->attributes())->validate();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nome da folha de trabalho', 'worksheets' => 'folhas de trabalho',
            'worksheets.sheets' => 'folhas', 'worksheets.sheets.*.name' => 'nome da folha',
            'worksheets.sheets.*.data' => 'linhas da folha'];
    }

    private function checkBudget(array $data): void
    {
        $sheets = data_get($data, 'worksheets.sheets', []);
        if (! is_array($sheets)) {
            return;
        }
        if (count($sheets) > 20) {
            throw ValidationException::withMessages(['worksheets.sheets' => 'A folha de trabalho não pode exceder 20 folhas.']);
        }
        $cellCount = 0;
        foreach ($sheets as $sheetIndex => $sheet) {
            $rows = data_get($sheet, 'data', []);
            if (! is_array($rows)) {
                continue;
            }
            if (count($rows) > 2000) {
                throw ValidationException::withMessages(["worksheets.sheets.{$sheetIndex}.data" => 'Cada folha pode conter até 2.000 linhas.']);
            }
            foreach ($rows as $rowIndex => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (count($row) > 100) {
                    throw ValidationException::withMessages(["worksheets.sheets.{$sheetIndex}.data.{$rowIndex}" => 'Cada linha pode conter até 100 colunas.']);
                }
                $cellCount += count($row);
                if ($cellCount > 50000) {
                    throw ValidationException::withMessages(['worksheets' => 'A folha de trabalho não pode exceder 50.000 células.']);
                }
            }
        }
    }
}
