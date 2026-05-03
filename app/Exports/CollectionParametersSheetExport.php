<?php

namespace App\Exports;

use App\Models\CollectionProduct;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CollectionParametersSheetExport implements FromGenerator, WithHeadings, WithStrictNullComparison
{
    public function __construct(
        private readonly EloquentCollection $records
    ) {
    }

    public function generator(): Generator
    {
        foreach ($this->records as $record) {
            $profiles = $record->product?->matrix?->profiles ?? collect();

            foreach ($profiles as $profile) {
                foreach ($profile->parameters as $parameter) {
                    $newDilutions = collect(json_decode($parameter->pivot->extra_data ?? '[]', true))
                        ->map(fn ($item) => collect($item)->values()->implode(' / '))
                        ->filter()
                        ->implode(' | ');

                    yield [
                        $record->collection?->collectionable_type === 'programmed' ? 'Programada' : 'Directa',
                        $record->code?->code,
                        optional($record->collection_date ?? $record->created_at)?->format('Y-m-d'),
                        $record->customer?->name,
                        $record->warehouse?->address,
                        $record->product?->name,
                        $record->comercial_brand,
                        $record->lot,
                        $record->qty,
                        $profile->name,
                        $parameter->name,
                        $parameter->description,
                        $parameter->pivot->dilutions,
                        $newDilutions ?: null,
                        $record->quality_certificate ? 'Certificado emitido' : ($record->processed ? 'Em processamento' : 'Pendente'),
                    ];
                }
            }
        }
    }

    public function headings(): array
    {
        return [
            'Tipo de colheita',
            'Código laboratorial',
            'Data de colheita',
            'Cliente',
            'Armazém',
            'Produto',
            'Marca comercial',
            'Lote',
            'Quantidade',
            'Perfil',
            'Código do parâmetro',
            'Parâmetro',
            'Diluições',
            'Diluições adicionais',
            'Estado de acompanhamento',
        ];
    }
}
