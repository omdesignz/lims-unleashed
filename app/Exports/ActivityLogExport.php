<?php

namespace App\Exports;

use Carbon\Carbon;

class ActivityLogExport extends StreamedQueryExport
{
    public function headings(): array
    {
        return [
            'ID',
            'Data e hora',
            'Nome do registo',
            'Evento',
            'Descrição',
            'ID do responsável',
            'Responsável',
            'Correio electrónico',
            'Tipo de entidade',
            'ID da entidade',
            'UUID do lote',
            'Propriedades',
            'Última alteração',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 10, 'B' => 20, 'C' => 22, 'D' => 16, 'E' => 48, 'F' => 16, 'G' => 24, 'H' => 30, 'I' => 24, 'J' => 16, 'K' => 38, 'L' => 60, 'M' => 20];
    }

    public function title(): string
    {
        return 'Registo de actividade';
    }

    protected function map(object $row): array
    {
        $properties = json_decode((string) $row->properties, true);

        return [
            $row->id,
            $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : null,
            $row->log_name ?? 'Sistema',
            $row->event,
            $row->description,
            $row->causer_id,
            $row->causer_name ?? 'Sistema',
            $row->causer_email,
            $row->subject_type ? class_basename($row->subject_type) : null,
            $row->subject_id,
            $row->batch_uuid,
            is_array($properties) ? json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $row->properties,
            $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }

    protected function headerColor(): string
    {
        return '1F4B68';
    }

    protected function wrappedColumns(): array
    {
        return ['E', 'L'];
    }
}
