<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use stdClass;
use ZipArchive;

class ProposalTemplateImportFileReader
{
    /** @return list<array<string,mixed>> */
    public function read(UploadedFile $file): array
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['xlsx', 'csv'], true)) {
                $this->checkSpreadsheetBudget($file, $extension);
                $sheets = Excel::toCollection(new ProposalTemplatesSpreadsheetImport, $file);
                $rows = [];
                foreach ($sheets as $sheet) {
                    foreach ($sheet as $row) {
                        $data = $row->all();
                        if (collect($data)->every(fn (mixed $value): bool => $value === null || (is_string($value) && Str::trim($value) === ''))) {
                            continue;
                        }
                        $this->checkRowCount(count($rows) + 1);
                        foreach (['layout_schema_json' => 'layout_schema', 'export_settings_json' => 'export_settings'] as $column => $field) {
                            if (array_key_exists($column, $data)) {
                                if (array_key_exists($field, $data)) {
                                    throw ValidationException::withMessages(['template_file' => 'Não combine colunas JSON e estruturadas para o mesmo campo.']);
                                }
                                $value = is_string($data[$column]) ? Str::trim($data[$column]) : $data[$column];
                                if ($value === null || $value === '') {
                                    $data[$field] = [];
                                } else {
                                    $decoded = json_decode((string) $value, false, 512, JSON_THROW_ON_ERROR);
                                    if ($field === 'layout_schema') {
                                        $this->checkLayoutListShapes($decoded);
                                    }
                                    $data[$field] = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
                                }
                                unset($data[$column]);
                            }
                        }
                        if (array_key_exists('is_active', $data)) {
                            $value = is_string($data['is_active']) ? Str::trim($data['is_active']) : $data['is_active'];
                            if ($value === null || $value === '') {
                                unset($data['is_active']);
                            } else {
                                $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                                if ($boolean === null || is_array($value)) {
                                    throw ValidationException::withMessages(['rows.'.count($rows).'.is_active' => 'Indique um valor booleano válido.']);
                                }
                                $data['is_active'] = $boolean;
                            }
                        }
                        $rows[] = $this->normalize($data);
                    }
                }
            } else {
                $content = $file->getContent();
                $decodedRows = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                if (! is_array($decodedRows)) {
                    throw ValidationException::withMessages(['template_file' => 'O ficheiro deve conter uma lista de modelos.']);
                }
                $this->checkRowCount(count($decodedRows));
                foreach ($decodedRows as $row) {
                    if (! $row instanceof stdClass || get_object_vars($row) === []) {
                        throw ValidationException::withMessages(['template_file' => 'Cada modelo deve ser um objecto com nome e conteúdo.']);
                    }
                    $this->checkLayoutListShapes($row->layout_schema ?? null);
                }
                $rows = array_map($this->normalize(...), json_decode($content, true, 512, JSON_THROW_ON_ERROR));
            }
            $this->checkRowCount(count($rows));
            if ($rows === []) {
                throw ValidationException::withMessages(['template_file' => 'O ficheiro não contém modelos para importar.']);
            }

            return $rows;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (JsonException|SpreadsheetException $exception) {
            throw ValidationException::withMessages(['template_file' => 'Não foi possível ler o ficheiro. Verifique o formato e os campos JSON.']);
        }
    }

    /** @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalize(array $row): array
    {
        $row = Arr::except($row, ['created_by', 'created_at', 'updated_at']);
        foreach (['name', 'category', 'theme_preset'] as $field) {
            if (is_string($row[$field] ?? null)) {
                $value = Str::trim($row[$field]);
                $row[$field] = $value === '' ? null : $value;
            }
        }

        return $row;
    }

    private function checkLayoutListShapes(mixed $layout): void
    {
        if (! $layout instanceof stdClass) {
            return;
        }
        foreach (['canvas_blocks', 'variable_catalog'] as $field) {
            $list = $layout->{$field} ?? null;
            if ($list instanceof stdClass) {
                throw ValidationException::withMessages(['template_file' => 'Os objectos e variáveis do modelo devem ser listas ordenadas.']);
            }
        }
        if (! is_array($layout->canvas_blocks ?? null)) {
            return;
        }
        foreach ($layout->canvas_blocks as $block) {
            if (! $block instanceof stdClass) {
                continue;
            }
            foreach (['chart_labels', 'chart_values', 'chart_colors'] as $field) {
                if (($block->{$field} ?? null) instanceof stdClass) {
                    throw ValidationException::withMessages(['template_file' => 'Os dados do gráfico devem ser listas ordenadas.']);
                }
            }
        }
    }

    private function checkRowCount(int $count): void
    {
        if ($count > 100) {
            throw ValidationException::withMessages(['template_file' => 'Importe no máximo 100 modelos por ficheiro.']);
        }
    }

    private function checkSpreadsheetBudget(UploadedFile $file, string $extension): void
    {
        if ($extension === 'xlsx') {
            $archive = new ZipArchive;
            if ($archive->open($file->path(), ZipArchive::RDONLY) !== true) {
                throw ValidationException::withMessages(['template_file' => 'O ficheiro XLSX não é válido.']);
            }
            try {
                $expandedSize = 0;
                if ($archive->numFiles > 512) {
                    throw ValidationException::withMessages(['template_file' => 'O ficheiro XLSX excede o limite de estrutura.']);
                }
                for ($index = 0; $index < $archive->numFiles; $index++) {
                    $expandedSize += (int) ($archive->statIndex($index)['size'] ?? 0);
                    if ($expandedSize > 20 * 1024 * 1024) {
                        throw ValidationException::withMessages(['template_file' => 'O ficheiro XLSX excede o limite de conteúdo descomprimido.']);
                    }
                }
            } finally {
                $archive->close();
            }
        }
        $reader = IOFactory::createReader($extension === 'xlsx' ? 'Xlsx' : 'Csv');
        $sheets = $reader->listWorksheetInfo($file->path());
        $physicalRows = 0;
        if (count($sheets) > 16) {
            throw ValidationException::withMessages(['template_file' => 'O ficheiro excede o limite de folhas.']);
        }
        foreach ($sheets as $sheet) {
            $physicalRows += (int) $sheet['totalRows'];
            if ($physicalRows > 1000 || (int) $sheet['totalColumns'] > 32) {
                throw ValidationException::withMessages(['template_file' => 'O ficheiro excede o limite de linhas ou colunas.']);
            }
        }
    }
}
