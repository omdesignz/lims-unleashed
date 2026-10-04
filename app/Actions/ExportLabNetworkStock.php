<?php

namespace App\Actions;

use App\Http\Resources\LabNetworkStockResource;
use App\Models\LabNetwork;
use App\Models\User;
use App\Services\LabNetworkStock;
use App\Support\InventoryQuantity;

class ExportLabNetworkStock
{
    public function __construct(private readonly LabNetworkStock $stock) {}

    /** @param array<string, mixed> $filters */
    public function write(User $user, LabNetwork $network, array $filters): void
    {
        $labIds = $this->stock->laboratoryIds($user, $network);
        $rows = $this->stock->search($labIds, $filters)->cursor();
        $output = fopen('php://output', 'w');
        try {
            fputcsv($output, ['Material', 'Código', 'Laboratório', 'Armazém', 'Lote', 'Validade', 'Unidade',
                'Físico', 'Reservado', 'Bloqueado', 'Disponível', 'Saída pendente (já deduzida)', 'Estado',
                'Último registo da posição', 'Consultado em'], ';', '"', '');
            $asOf = now()->toIso8601String();
            foreach ($rows->chunk(200) as $chunk) {
                $visible = $this->stock->laboratoryIds($user, $network);
                abort_if($chunk->contains(fn (object $row): bool => ! in_array((int) $row->lab_id, $visible, true)), 403);
                foreach ($chunk as $row) {
                    $data = (new LabNetworkStockResource($row))->resolve();
                    $data['availability_state'] = match ($data['availability_state']) {
                        'available' => InventoryQuantity::compare($data['available_quantity'], '0') > 0 ? 'Disponível' : 'Sem saldo',
                        'reserved' => 'Reservado', 'expired' => 'Expirado',
                        'inconsistent' => 'Saldo por reconciliar', default => 'Bloqueado',
                    };
                    $values = array_map(fn (string $key): string => $this->cell($data[$key] ?? null),
                        ['name', 'code', 'lab_name', 'warehouse_name', 'lot', 'expiry_date', 'unit',
                            'physical_quantity', 'reserved_quantity', 'blocked_quantity', 'available_quantity',
                            'outgoing_quantity', 'availability_state', 'updated_at']);
                    fputcsv($output, [...$values, $asOf], ';', '"', '');
                }
            }
        } finally {
            fclose($output);
        }
    }

    private function cell(mixed $value): string
    {
        $text = (string) $value;

        return preg_match('/^[\\s]*[=+@\\-]/u', $text) ? "'".$text : $text;
    }
}
