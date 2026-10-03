<?php

namespace App\Support;

use App\Models\InventoryItem;
use Illuminate\Validation\ValidationException;

class EquipmentMetrologyGate
{
    public function ensureResultsReady(array $results, int $labId): void
    {
        $equipmentIds = collect($results)
            ->pluck('equipment_id')
            ->filter()
            ->unique()
            ->values();

        if ($equipmentIds->isEmpty()) {
            return;
        }

        $equipment = InventoryItem::forLaboratory($labId)
            ->whereIn('id', $equipmentIds)
            ->get()
            ->keyBy('id');

        foreach ($results as $index => $result) {
            $equipmentId = $result['equipment_id'] ?? null;

            if (! $equipmentId) {
                continue;
            }

            $item = $equipment->get($equipmentId);

            if (! $item) {
                throw ValidationException::withMessages([
                    "results.{$index}.equipment_id" => 'O equipamento seleccionado não foi encontrado.',
                ]);
            }

            if (! $item->is_metrologically_ready) {
                throw ValidationException::withMessages([
                    "results.{$index}.equipment_id" => sprintf(
                        'O equipamento %s não está liberado para ensaio. Estado metrológico actual: %s.',
                        $item->name,
                        $item->metrology_status
                    ),
                ]);
            }
        }
    }
}
