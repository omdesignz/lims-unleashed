<?php

namespace App\Exports;

use Generator;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class StreamedQueryExport implements FromGenerator, WithColumnWidths, WithHeadings, WithStrictNullComparison, WithStyles, WithTitle
{
    public function __construct(
        protected readonly Builder $query
    ) {}

    public function generator(): Generator
    {
        foreach ((clone $this->query)->cursor() as $row) {
            yield $this->sanitizeRow($this->map($row));
        }
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->headerColor()]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A:{$lastColumn}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach ($this->wrappedColumns() as $column) {
            $sheet->getStyle("{$column}:{$column}")->getAlignment()->setWrapText(true);
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    abstract protected function map(object $row): array;

    abstract protected function headerColor(): string;

    /**
     * @return array<int, string>
     */
    protected function wrappedColumns(): array
    {
        return [];
    }

    protected function yesNo(mixed $value): string
    {
        return $value ? 'Sim' : 'Não';
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function sanitizeRow(array $row): array
    {
        return array_map(function (mixed $value): mixed {
            if (! is_string($value) || ! preg_match('/^[=+\-@]/', $value)) {
                return $value;
            }

            return "'{$value}";
        }, $row);
    }
}
