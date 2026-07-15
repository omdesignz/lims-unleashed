<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ConfiguredQueryExport extends StreamedQueryExport
{
    /**
     * @param  array<string, mixed>  $dataset
     * @param  array<int, array{key: string, label: string, width: int, type?: string, wrap?: bool}>  $columns
     */
    public function __construct(Builder $query, private readonly array $dataset, private readonly array $columns)
    {
        parent::__construct($query);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_column($this->columns, 'label');
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->columns as $index => $column) {
            $widths[Coordinate::stringFromColumnIndex($index + 1)] = $column['width'];
        }

        return $widths;
    }

    public function title(): string
    {
        return $this->dataset['sheet'];
    }

    /**
     * @return array<int, mixed>
     */
    protected function map(object $row): array
    {
        return array_map(function (array $column) use ($row): mixed {
            $value = $row->{$column['key']} ?? null;

            return match ($column['type'] ?? null) {
                'float' => $value === null ? null : (float) $value,
                'integer' => $value === null ? null : (int) $value,
                'yes_no' => $this->yesNo($value),
                'record_status' => $value ? 'Arquivado' : 'Activo',
                'payment_status' => match ($value) {
                    'paid' => 'Paga',
                    'canceled' => 'Anulada',
                    default => 'Não paga',
                },
                'credit_reason' => match ($value) {
                    'R' => 'Rectificação',
                    'A' => 'Anulação',
                    default => $value,
                },
                default => $value,
            };
        }, $this->columns);
    }

    protected function headerColor(): string
    {
        return $this->dataset['color'];
    }

    /**
     * @return array<int, string>
     */
    protected function wrappedColumns(): array
    {
        return collect($this->columns)
            ->map(fn (array $column, int $index): ?string => ($column['wrap'] ?? false) ? Coordinate::stringFromColumnIndex($index + 1) : null)
            ->filter()
            ->values()
            ->all();
    }
}
