<?php

namespace App\Actions;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\ItemCategory;
use App\Models\ReagentConsumption;
use App\Models\User;
use App\Services\InventoryConsumptionEvidence;
use App\Support\InventoryQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ConsumeInventoryReagent
{
    public function __construct(private readonly InventoryConsumptionEvidence $evidence) {}

    /**
     * @param  array{warehouse_id: int, quantity_used: string|int|float, used_by: string, date?: string, used_at?: ?string, remarks?: ?string, batch_id?: ?int, usage_type?: ?string, project?: ?string}  $data
     * @return array{consumption: ReagentConsumption, new_quantity: string}
     */
    public function execute(int $labId, User $operator, InventoryItem $item, array $data): array
    {
        $data = Validator::make($data, [
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'quantity_used' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001'],
            'used_by' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'usage_type' => ['nullable', 'string', 'max:255'], 'project' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'], 'used_at' => ['nullable', 'date'],
        ])->validate();
        try {
            $quantity = InventoryQuantity::fromScaled(InventoryQuantity::toScaled($data['quantity_used']));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['quantity_used' => 'Indique uma quantidade válida com até quatro casas decimais.']);
        }

        return DB::transaction(function () use ($labId, $operator, $item, $data, $quantity): array {
            $context = $this->evidence->lock($labId, $operator->id, $item->id, (int) $data['warehouse_id'], 'add_reagent_consumption');
            $item = $this->evidence->record($context, InventoryItem::class, $item->id);
            $warehouse = $this->evidence->record($context, InventoryItemWarehouse::class, (int) $data['warehouse_id']);
            abort_unless($item && (int) $item->lab_id === $labId && ! $item->trashed()
                && $warehouse && (int) $warehouse->lab_id === $labId && ! $warehouse->trashed(), 404);
            $category = $item->category_id === null ? null : $this->evidence->record($context, ItemCategory::class, $item->category_id);
            $item->setRelation('category', $category && ! $category->trashed() ? $category : null);
            if ((int) $item->lab_id !== $labId || ! $item->is_reagent) {
                throw ValidationException::withMessages(['reagent_id' => 'Seleccione um reagente do laboratório activo.']);
            }

            if ($item->unit_id === null) {
                throw ValidationException::withMessages(['reagent_id' => 'Defina a unidade do reagente antes de registar consumo.']);
            }

            $inventory = $this->evidence->position($context);
            if (InventoryQuantity::compare($quantity, '0') <= 0) {
                throw ValidationException::withMessages(['quantity_used' => 'Indique uma quantidade positiva.']);
            }
            if (InventoryQuantity::compare($inventory->qty_available, $quantity) < 0) {
                throw ValidationException::withMessages(['quantity_used' => 'Existências insuficientes. Quantidade disponível: '.$inventory->qty_available]);
            }

            $batch = null;
            if (isset($data['batch_id'])) {
                $batch = $this->evidence->record($context, InventoryBatch::class, (int) $data['batch_id']);
                if ($batch === null || (int) $batch->lab_id !== $labId || (int) $batch->inventory_id !== $inventory->id) {
                    throw ValidationException::withMessages(['batch_id' => 'O lote não pertence às existências seleccionadas.']);
                }
                if (InventoryQuantity::compare($batch->qty_remaining, $quantity) < 0) {
                    throw ValidationException::withMessages(['quantity_used' => 'Quantidade insuficiente no lote seleccionado.']);
                }
            }

            $newQuantity = InventoryQuantity::subtract($inventory->qty_available, $quantity);
            $reserved = collect($context['rows'][InventoryBatch::class])->where('inventory_id', $inventory->id)
                ->reduce(fn (string $sum, array $row): string => InventoryQuantity::add($sum, $row['qty_remaining']), '0.0000');
            if ($batch === null && InventoryQuantity::compare($newQuantity, $reserved) < 0) {
                throw ValidationException::withMessages(['quantity_used' => 'A quantidade livre não cobre este consumo; seleccione um lote.']);
            }
            $inventory->qty_available = $newQuantity;
            $this->evidence->save($context, $inventory);
            if ($batch !== null) {
                $batch->qty_remaining = InventoryQuantity::subtract($batch->qty_remaining, $quantity);
                $this->evidence->save($context, $batch);
            }

            $transactionType = $this->evidence->type($context, 'consumption');
            $transaction = new InventoryTransaction([
                'lab_id' => $labId,
                'inventory_id' => $inventory->id,
                'user_id' => $operator->id,
                'warehouse_id' => $inventory->warehouse_id,
                'item_id' => $item->id,
                'type_id' => $transactionType->id,
                'batch_id' => $batch?->id,
                'qty' => InventoryQuantity::fromScaled(-InventoryQuantity::toScaled($quantity)),
                'reason' => 'Consumo de reagente',
                'notes' => $data['remarks'] ?? null,
            ]);
            $transaction->deleted_at = null;
            $this->evidence->save($context, $transaction);

            $consumption = new ReagentConsumption([
                'lab_id' => $labId,
                'date' => $data['date'] ?? now()->toDateString(),
                'user_id' => $operator->id,
                'reagent_id' => $item->id,
                'reagent_name' => $item->name,
                'quantity_used' => $quantity,
                'usage_type' => $data['usage_type'] ?? 'experiment',
                'project' => $data['project'] ?? null,
                'used_by' => $data['used_by'],
                'used_at' => $data['used_at'] ?? now(),
                'remarks' => $data['remarks'] ?? null,
                'batch_id' => $batch?->id,
                'warehouse_id' => $inventory->warehouse_id,
                'inventory_transaction_id' => $transaction->id,
            ]);
            $this->evidence->save($context, $consumption);
            $this->evidence->assert($context);

            return ['consumption' => $consumption, 'new_quantity' => $newQuantity];
        });
    }
}
