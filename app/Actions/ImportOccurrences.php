<?php

namespace App\Actions;

use App\Support\OccurrenceCsv;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportOccurrences
{
    public function __construct(private readonly OccurrenceCsv $csv, private readonly SaveOccurrence $save) {}

    public function execute(int $labId, int $userId, string $path): int
    {
        $rows = $this->csv->rows($path, $labId);

        return DB::transaction(function () use ($labId, $userId, $rows): int {
            foreach ($rows as $index => $row) {
                try {
                    $this->save->execute($labId, $userId, $row);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['file' => 'Registo '.($index + 1).': '.$exception->validator->errors()->first()]);
                }
            }

            return count($rows);
        }, 3);
    }
}
