<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class OccurrenceCsv
{
    public const MAX_ROWS = 500;

    /** @return list<string> */
    public function columns(): array
    {
        return array_values(array_diff(array_keys(OccurrenceValidation::rules(0)), ['lab_id', 'occurrence_no', 'occurrence_year', 'seq']));
    }

    /** @return list<array<string, mixed>> */
    public function rows(string $path, int $labId): array
    {
        $rows = [];
        $header = null;

        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setDelimiter(';');

            foreach ($csv->getRecords() as $index => $cells) {
                if ($header === null) {
                    $header = $cells;
                    if (count($header) !== count(array_unique($header))
                        || array_diff($header, $this->columns())
                        || array_diff(['date_reported', 'issue_description'], $header)) {
                        throw ValidationException::withMessages(['file' => 'Cabeçalho inválido. Use o modelo CSV; não inclua laboratório ou numeração.']);
                    }

                    continue;
                }

                if ($cells === [null]) {
                    continue;
                }

                if (count($cells) !== count($header) || count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'CSV inválido na linha '.($index + 1).'. Use o modelo e no máximo '.self::MAX_ROWS.' registos.']);
                }

                $data = array_combine($header, array_map(fn (?string $value): ?string => $value === '' ? null : $value, $cells));
                $validator = Validator::make(OccurrenceValidation::normalize($data), OccurrenceValidation::rules($labId));
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['file' => 'Linha '.($index + 1).': '.$validator->errors()->first()]);
                }

                $rows[] = $validator->validated();
            }
        } catch (CsvException) {
            throw ValidationException::withMessages(['file' => 'Não foi possível ler o CSV. Use o modelo separado por ponto e vírgula.']);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'O CSV deve conter pelo menos uma ocorrência.']);
        }

        return $rows;
    }
}
