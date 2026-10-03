<?php

namespace App\Actions;

use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Services\InventoryConsumptionEvidence;
use App\Support\InventoryQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseInventoryReagentConsumption
{
    public function __construct(private readonly InventoryConsumptionEvidence $evidence) {}

    public function execute(int $labId, int $userId, int $consumptionId): ReagentConsumptionReversal
    {
        return DB::transaction(function () use ($labId, $userId, $consumptionId): ReagentConsumptionReversal {
            $source = ReagentConsumption::forLaboratory($labId)->whereKey($consumptionId)->toBase()->first();
            abort_unless($source, 404);
            $context = $this->evidence->lock($labId, $userId, (int) $source->reagent_id, (int) $source->warehouse_id, 'delete_reagent_consumption');
            $consumption = $this->evidence->record($context, ReagentConsumption::class, $consumptionId);
            abort_unless($consumption && (int) $consumption->lab_id === $labId, 404);
            $transaction = $consumption->inventory_transaction_id === null ? null
                : $this->evidence->record($context, InventoryTransaction::class, $consumption->inventory_transaction_id);
            $inventory = $transaction === null ? null : $this->evidence->record($context, Inventory::class, $transaction->inventory_id);
            $type = $transaction === null ? null : $this->evidence->record($context, InventoryTransactionType::class, $transaction->type_id);
            $sharedLink = collect($context['rows'][ReagentConsumption::class])->where('inventory_transaction_id', $consumption->inventory_transaction_id)->count();
            if (! $transaction || ! $inventory || $transaction->trashed() || $sharedLink !== 1 || ! $type || $type->code !== 'consumption'
                || (int) $transaction->inventory_id !== $inventory->id || (int) $transaction->lab_id !== $labId
                || (int) $transaction->item_id !== (int) $consumption->reagent_id
                || (int) $transaction->warehouse_id !== (int) $consumption->warehouse_id
                || $transaction->user_id !== $consumption->user_id
                || $transaction->batch_id !== $consumption->batch_id
                || InventoryQuantity::toScaled($transaction->qty) !== -InventoryQuantity::toScaled($consumption->quantity_used)) {
                throw ValidationException::withMessages(['consumption' => 'A evidência do movimento não corresponde a este consumo.']);
            }
            $existing = collect($context['rows'][ReagentConsumptionReversal::class])->firstWhere('consumption_id', $consumptionId);
            if ($existing !== null) {
                $movement = $this->evidence->record($context, InventoryTransaction::class, (int) $existing['inventory_transaction_id']);
                $movementType = $movement === null ? null : $this->evidence->record($context, InventoryTransactionType::class, $movement->type_id);
                if (! $movement || $movement->trashed() || ! $movementType || $movementType->code !== 'consumption_reversal'
                    || (int) $movement->lab_id !== $labId || (int) $movement->inventory_id !== $inventory->id
                    || (int) $movement->item_id !== (int) $consumption->reagent_id
                    || (int) $movement->warehouse_id !== (int) $consumption->warehouse_id
                    || $movement->batch_id !== $consumption->batch_id
                    || (int) $movement->user_id !== (int) $existing['user_id']
                    || InventoryQuantity::toScaled($movement->qty) !== InventoryQuantity::toScaled($consumption->quantity_used)) {
                    throw ValidationException::withMessages(['consumption' => 'A evidência da reposição não corresponde a este consumo.']);
                }
                $this->evidence->assert($context);

                return $this->evidence->record($context, ReagentConsumptionReversal::class, (int) $existing['id']);
            }
            abort_if($inventory->trashed(), 404);
            $quantityScaled = InventoryQuantity::toScaled($consumption->quantity_used);
            $restoredScaled = InventoryQuantity::toScaled($inventory->qty_available) + $quantityScaled;
            if ($quantityScaled <= 0 || $restoredScaled > InventoryQuantity::MAX_SCALED) {
                throw ValidationException::withMessages(['consumption' => 'A reposição excede o limite de existências.']);
            }
            $batch = $consumption->batch_id === null ? null : $this->evidence->record($context, InventoryBatch::class, $consumption->batch_id);
            if ($consumption->batch_id !== null && (! $batch || (int) $batch->lab_id !== $labId || (int) $batch->inventory_id !== $inventory->id)) {
                throw ValidationException::withMessages(['consumption' => 'O lote não corresponde às existências deste consumo.']);
            }
            if ($batch !== null && InventoryQuantity::toScaled($batch->qty_remaining) + $quantityScaled > InventoryQuantity::MAX_SCALED) {
                throw ValidationException::withMessages(['consumption' => 'A reposição excede o limite do lote.']);
            }
            $inventory->qty_available = InventoryQuantity::fromScaled($restoredScaled);
            $this->evidence->save($context, $inventory);
            if ($batch !== null) {
                $batch->qty_remaining = InventoryQuantity::add($batch->qty_remaining, $consumption->quantity_used);
                $this->evidence->save($context, $batch);
            }
            $reversalType = $this->evidence->type($context, 'consumption_reversal');
            $movement = new InventoryTransaction(['lab_id' => $labId, 'inventory_id' => $inventory->id, 'user_id' => $userId,
                'warehouse_id' => $inventory->warehouse_id, 'item_id' => $inventory->item_id, 'type_id' => $reversalType->id,
                'batch_id' => $batch?->id, 'qty' => $consumption->quantity_used, 'reason' => 'Reposição de consumo #'.$consumptionId,
                'notes' => null]);
            $movement->deleted_at = null;
            $this->evidence->save($context, $movement);
            $reversal = new ReagentConsumptionReversal(['lab_id' => $labId, 'consumption_id' => $consumptionId,
                'inventory_transaction_id' => $movement->id, 'user_id' => $userId, 'reversed_at' => now()]);
            $this->evidence->save($context, $reversal);
            $this->evidence->assert($context);

            return $reversal;
        });
    }
}
