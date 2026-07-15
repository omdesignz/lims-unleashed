<?php

namespace App\Exports;

use Carbon\Carbon;

class ProductsExport extends StreamedQueryExport
{
    public function headings(): array
    {
        return [
            'ID', 'Produto', 'Descrição', 'Código da matriz', 'Matriz', 'Preço base', 'Preço fixo',
            'Tributado', 'Taxa (%)', 'Categoria fiscal', 'Retenção', 'Código de isenção',
            'Motivo da isenção', 'Base legal', 'Estado', 'Criado em', 'Última alteração',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 10, 'B' => 30, 'C' => 48, 'D' => 18, 'E' => 28, 'F' => 16, 'G' => 16, 'H' => 14, 'I' => 14, 'J' => 24, 'K' => 14, 'L' => 20, 'M' => 36, 'N' => 32, 'O' => 14, 'P' => 20, 'Q' => 20];
    }

    public function title(): string
    {
        return 'Produtos';
    }

    protected function map(object $row): array
    {
        return [
            $row->id,
            $row->name,
            $row->description,
            $row->matrix_code,
            $row->matrix,
            (float) $row->price,
            (float) $row->fixed_price,
            $this->yesNo($row->charge_tax),
            (float) $row->tax_percentage,
            $row->tax_category,
            $this->yesNo($row->withhold_tax),
            $row->exemption_code,
            $row->exemption_reason,
            $row->exemption_law,
            $row->deleted_at ? 'Arquivado' : 'Activo',
            $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : null,
            $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }

    protected function headerColor(): string
    {
        return '374151';
    }

    protected function wrappedColumns(): array
    {
        return ['C', 'M', 'N'];
    }
}
