<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Product;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VAPSampleEntriesTemplateExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        $product = Product::query()
            ->whereHas('matrix.profiles.type')
            ->with(['matrix.profiles.type'])
            ->first();
        $profile = $product?->matrix?->profiles?->first();
        $department = $profile?->type?->department_id
            ? Department::query()->find($profile->type->department_id)
            : Department::query()->first();
        $customer = Customer::query()->first();
        $lab = VAPLab::query()->first();
        $warehouse = $customer
            ? (Warehouse::query()->where('customer_id', $customer->id)->first() ?: Warehouse::query()->first())
            : Warehouse::query()->first();

        return collect([
            [
                'Nome da amostra' => 'Amostra de farinha lote A',
                'Código da amostra' => '',
                'Tipo de amostra' => 'ROTINA',
                'Origem do trabalho' => 'cliente',
                'Fluxo de colheita' => 'direta',
                'Cliente' => $customer?->name,
                'Código do cliente' => $customer?->code,
                'Laboratório' => $lab?->name,
                'Departamento' => $department?->name,
                'Armazém' => $warehouse?->name ?: $warehouse?->address,
                'Produto' => $product?->name,
                'Matriz' => $product?->matrix?->description,
                'Perfis analíticos' => $profile?->name,
                'Recebido em' => now()->format('Y-m-d H:i:s'),
                'Colhido em' => '',
                'Quantidade recebida' => '1 embalagem',
                'Quantidade colhida' => '500 g',
                'Lote' => 'LT-001',
                'Batch' => 'BATCH-001',
                'Origem' => 'Luanda',
                'Local de colheita' => 'Recepção técnica',
                'Data de produção' => now()->subMonth()->format('Y-m-d'),
                'Data de validade' => now()->addMonths(6)->format('Y-m-d'),
                'Temperatura' => 'Ambiente',
                'Contentor' => '',
                'DU' => '',
                'Termo' => '',
                'BL' => '',
                'Plano de amostragem' => 'PL-AM-001',
                'Fornecedor' => '',
                'Condição de aceitação' => 'aceite',
                'Estado da embalagem' => 'Íntegra',
                'Condição térmica' => 'Ambiente',
                'Observações de integridade' => '',
                'Notas de custódia' => '',
                'Serviços solicitados' => 'Ensaios conforme perfis selecionados',
                'Observações' => 'Linha de exemplo. Remova ou substitua antes de importar.',
                'Estado' => 'POR_INICIAR',
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Nome da amostra',
            'Código da amostra',
            'Tipo de amostra',
            'Origem do trabalho',
            'Fluxo de colheita',
            'Cliente',
            'Código do cliente',
            'Laboratório',
            'Departamento',
            'Armazém',
            'Produto',
            'Matriz',
            'Perfis analíticos',
            'Recebido em',
            'Colhido em',
            'Quantidade recebida',
            'Quantidade colhida',
            'Lote',
            'Batch',
            'Origem',
            'Local de colheita',
            'Data de produção',
            'Data de validade',
            'Temperatura',
            'Contentor',
            'DU',
            'Termo',
            'BL',
            'Plano de amostragem',
            'Fornecedor',
            'Condição de aceitação',
            'Estado da embalagem',
            'Condição térmica',
            'Observações de integridade',
            'Notas de custódia',
            'Serviços solicitados',
            'Observações',
            'Estado',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:AL1')->getAlignment()->setWrapText(true);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF0F766E'],
                ],
            ],
        ];
    }
}
