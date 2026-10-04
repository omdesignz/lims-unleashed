<?php

namespace App\Exports;

use App\Models\VAPNonConformity;
use App\Support\NonConformityLifecycleReport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NonConformityDetailsExport implements WithMultipleSheets
{
    public function __construct(private readonly VAPNonConformity $nonConformity)
    {
        $nonConformity->load(['lab:id,name', 'department:id,name',
            'actions' => fn ($query) => $query->withTrashed()->where('lab_id', $nonConformity->lab_id)->orderBy('id')]);
    }

    public function sheets(): array
    {
        return [new NonConformityDetailsSheet($this->nonConformity), new NonConformityActionsSheet($this->nonConformity), new NonConformityLifecycleSheet($this->nonConformity)];
    }
}

class NonConformityDetailsSheet extends StringValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly VAPNonConformity $nonConformity) {}

    public function title(): string
    {
        return 'Dossier';
    }

    public function headings(): array
    {
        return ['Campo', 'Valor'];
    }

    public function collection(): Collection
    {
        $record = $this->nonConformity;

        return collect([
            ['Número NC', $record->nc_number],
            ['Título', $record->title],
            ['Descrição', $record->description],
            ['Estado', ['opened' => 'Aberta', 'in_progress' => 'Em progresso', 'resolved' => 'Resolvida', 'closed' => 'Fechada'][$record->status] ?? $record->status],
            ['Severidade', ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'][$record->severity] ?? $record->severity],
            ['Categoria', $record->category],
            ['Laboratório', $record->lab?->name],
            ['Departamento', $record->department?->name],
            ['Reportado por', $record->reported_by],
            ['Data do Relato', $record->reported_at?->format('d/m/Y H:i')],
            ['Data de Vencimento', $record->due_date?->format('d/m/Y H:i')],
            ['Atribuído para', $record->assigned_to],
            ['Amostra ID', $record->sample_id],
            ['Método de Teste', $record->test_method],
            ['Equipamento ID', $record->equipment_id],
            ['Número do Lote', $record->batch_number],
            ['Área de Ocorrência', $record->occurrence_area],
            ['Causa Raiz', $record->root_cause],
            ['Acções Correctivas', $record->corrective_actions],
            ['Acções Preventivas', $record->preventive_actions],
            ['Comentários', $record->comments],
            ['Criada em', $record->created_at?->format('d/m/Y H:i')],
            ['Actualizada em', $record->updated_at?->format('d/m/Y H:i')],
            ['Arquivo', $record->trashed() ? 'Arquivada' : 'Activa'],
            ['Arquivada em', $record->deleted_at?->format('d/m/Y H:i')],
            ...NonConformityLifecycleReport::summary($record),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(85);
        $sheet->getStyle('A1:B'.$sheet->getHighestRow())->getAlignment()->setWrapText(true);

        return [1 => ['font' => ['bold' => true]]];
    }
}

class NonConformityLifecycleSheet extends StringValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly VAPNonConformity $nonConformity) {}

    public function title(): string
    {
        return 'Histórico do fluxo';
    }

    public function headings(): array
    {
        return ['Revisão', 'Etapa', 'Estado anterior', 'Estado seguinte', 'Responsável', 'Data e hora', 'Evidência / motivo'];
    }

    public function collection(): Collection
    {
        return collect(NonConformityLifecycleReport::history($this->nonConformity))->map(fn (array $entry): array => array_values($entry));
    }

    public function styles(Worksheet $sheet): array
    {
        foreach (['A' => 12, 'B' => 25, 'C' => 20, 'D' => 20, 'E' => 32, 'F' => 24, 'G' => 85] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle('A1:G'.$sheet->getHighestRow())->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');

        return [1 => ['font' => ['bold' => true]]];
    }
}

class NonConformityActionsSheet extends StringValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly VAPNonConformity $nonConformity) {}

    public function title(): string
    {
        return 'Histórico CAPA';
    }

    public function collection(): Collection
    {
        return $this->nonConformity->actions;
    }

    public function headings(): array
    {
        return ['ID', 'Correcção', 'Acção Correctiva', 'Prazo', 'Aprovada em', 'Efectiva?', 'Evidências', 'Criada em', 'Actualizada em', 'Arquivo', 'Arquivada em'];
    }

    public function map($action): array
    {
        return [$action->id, $action->correction, $action->corrective_action,
            $action->due_at?->format('d/m/Y H:i'), $action->approved_at?->format('d/m/Y H:i'),
            $action->was_effective === null ? 'Não avaliada' : ($action->was_effective ? 'Sim' : 'Não'),
            $action->evidence, $action->created_at?->format('d/m/Y H:i'), $action->updated_at?->format('d/m/Y H:i'),
            $action->trashed() ? 'Arquivada' : 'Activa', $action->deleted_at?->format('d/m/Y H:i')];
    }

    public function styles(Worksheet $sheet): array
    {
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setWidth(in_array($column, ['B', 'C', 'G']) ? 45 : 22);
        }
        $sheet->getStyle('A1:K'.$sheet->getHighestRow())->getAlignment()->setWrapText(true);

        return [1 => ['font' => ['bold' => true]]];
    }
}
