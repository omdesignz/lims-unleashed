<?php

namespace App\Support;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Csv\Exception as CsvException;
use League\Csv\Reader;

class MaintenanceTaskCsv
{
    public const MAX_ROWS = 500;

    /** @return list<string> */
    public function columns(): array
    {
        return ['equipment_code', ...array_values(array_diff(MaintenanceTaskValidation::EDITABLE_FIELDS, ['equipment_id', 'is_executed']))];
    }

    /** @return list<array<string, mixed>> */
    public function rows(string $path, int $labId): array
    {
        if (filesize($path) > 2 * 1024 * 1024 || ! mb_check_encoding(file_get_contents($path), 'UTF-8')) {
            throw ValidationException::withMessages(['file' => 'Use um ficheiro UTF-8 com no máximo 2 MB.']);
        }
        $rows = [];
        $header = null;
        try {
            $csv = Reader::createFromPath($path, 'r');
            $csv->setDelimiter(';');
            $csv->setEscape('');
            foreach ($csv->getRecords() as $index => $cells) {
                if ($header === null) {
                    $header = $cells;
                    if (count($header) !== count(array_unique($header))
                        || array_diff($header, $this->columns())
                        || array_diff(['equipment_code', 'name', 'category_id', 'due_date'], $header)) {
                        throw ValidationException::withMessages(['file' => 'Cabeçalho inválido. Use o modelo CSV; não inclua laboratório, numeração, datas derivadas ou estado de conclusão.']);
                    }

                    continue;
                }
                if ($cells === [null]) {
                    continue;
                }
                if (count($cells) !== count($header) || count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['file' => 'CSV inválido na linha '.($index + 1).'. Use o modelo e no máximo '.self::MAX_ROWS.' registos.']);
                }
                $data = array_combine($header, array_map(fn (?string $value): ?string => trim($value ?? '') === '' ? null : trim($value), $cells));
                $equipment = InventoryItem::forLaboratory($labId)->equipment()
                    ->where('internal_code', $data['equipment_code'])->limit(2)->pluck('id');
                if (blank($data['equipment_code']) || $equipment->count() !== 1) {
                    throw ValidationException::withMessages(['file' => 'Linha '.($index + 1).': o código deve identificar exactamente um equipamento activo deste laboratório.']);
                }
                unset($data['equipment_code']);
                $data['equipment_id'] = $equipment->sole();
                foreach (['executed_by_supplier' => false, 'is_planned' => true] as $field => $default) {
                    $data[$field] ??= $default;
                }
                $rules = MaintenanceTaskValidation::rules($labId);
                $rules['due_date'] = ['required', 'date_format:Y-m-d'];
                $validator = Validator::make(MaintenanceTaskValidation::mergeCurrentState($data), $rules, MaintenanceTaskValidation::messages());
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['file' => 'Linha '.($index + 1).': '.$validator->errors()->first()]);
                }
                $rows[] = $validator->validated();
            }
        } catch (CsvException) {
            throw ValidationException::withMessages(['file' => 'Não foi possível ler o CSV. Use UTF-8 e o modelo separado por ponto e vírgula.']);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'O CSV deve conter pelo menos uma tarefa.']);
        }

        return $rows;
    }
}
