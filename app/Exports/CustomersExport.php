<?php

namespace App\Exports;

use Carbon\Carbon;

class CustomersExport extends StreamedQueryExport
{
    public function headings(): array
    {
        return [
            'ID', 'Código', 'Cliente', 'Categoria', 'Descrição', 'Estado', 'Código do local principal',
            'Local principal', 'NIF', 'Morada', 'Município', 'Província', 'Telefone', 'Correio electrónico',
            'Correio de facturação', 'Ponto focal', 'Contacto do ponto focal', 'Correio do ponto focal',
            'Número de locais', 'Criado em', 'Última alteração',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 10, 'B' => 16, 'C' => 28, 'D' => 22, 'E' => 42, 'F' => 14, 'G' => 20, 'H' => 28, 'I' => 18, 'J' => 42, 'K' => 20, 'L' => 20, 'M' => 20, 'N' => 28, 'O' => 30, 'P' => 24, 'Q' => 24, 'R' => 30, 'S' => 16, 'T' => 20, 'U' => 20];
    }

    public function title(): string
    {
        return 'Clientes';
    }

    protected function map(object $row): array
    {
        return [
            $row->id,
            $row->code,
            $row->name,
            $row->category,
            $row->description,
            $row->deleted_at ? 'Arquivado' : 'Activo',
            $row->warehouse_code,
            $row->warehouse_name,
            $row->nif,
            $row->address,
            $row->municipality,
            $row->province,
            $row->primary_phone,
            $row->email,
            $row->invoicing_email,
            $row->focal_point,
            $row->focal_point_contact,
            $row->focal_point_email,
            (int) $row->warehouse_count,
            $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : null,
            $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }

    protected function headerColor(): string
    {
        return '0F766E';
    }

    protected function wrappedColumns(): array
    {
        return ['E', 'J'];
    }
}
