<?php

namespace App\Actions;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Services\InventoryConsumptionEvidence;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\InventoryQuantity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MutateInventoryPositions
{
    public function __construct(private readonly InventoryConsumptionEvidence $evidence, private readonly LaboratoryWorkflowMutationAccess $access) {}

    /** @param list<int> $recordIds @param array<string,mixed> $data */
    public function execute(int $labId, int $userId, array $recordIds, string $operation, array $data = []): void
    {
        abort_unless(in_array($operation, ['update', 'archive', 'restore'], true), 404);
        Validator::make(['recordIds' => $recordIds], [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX, 'distinct'],
        ])->validate();
        if ($operation === 'update') {
            abort_unless(count($recordIds) === 1, 422);
            $data = Validator::make($data, [
                'item_id' => ['required', 'integer', 'min:1'], 'warehouse_id' => ['required', 'integer', 'min:1'],
                'qty_available' => ['prohibited'], 'min_stock_level' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
                'reorder_point' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            ])->validate();
            foreach (['min_stock_level', 'reorder_point'] as $field) {
                try {
                    $data[$field] = InventoryQuantity::fromScaled(InventoryQuantity::toScaled($data[$field]));
                } catch (InvalidArgumentException) {
                    throw ValidationException::withMessages([$field => 'Indique uma quantidade válida com até quatro casas decimais.']);
                }
            }
        }
        try {
            DB::transaction(function () use ($labId, $userId, $recordIds, $operation, $data): void {
                $permission = match ($operation) {
                    'update' => 'edit_inventory', 'archive' => 'delete_inventory', 'restore' => 'restore_inventory',
                };
                $this->access->operator($userId, $labId, $permission);
                $rows = Inventory::withTrashed()->where('lab_id', $labId)->whereKey($recordIds)->orderBy('id')->toBase()->get();
                abort_unless($rows->count() === count($recordIds), 404);
                $contexts = [];
                foreach ($rows as $row) {
                    $context = $this->evidence->lock($labId, $userId, (int) $row->item_id, (int) $row->warehouse_id, $permission);
                    $position = $this->evidence->record($context, Inventory::class, (int) $row->id);
                    $item = $this->evidence->record($context, InventoryItem::class, (int) $row->item_id);
                    $warehouse = $this->evidence->record($context, InventoryItemWarehouse::class, (int) $row->warehouse_id);
                    abort_unless($position && $position->lab_id === $labId && $item?->lab_id === $labId
                        && $warehouse?->lab_id === $labId && ! $warehouse->trashed(), 404);
                    if ($operation === 'update') {
                        abort_if($position->trashed() || $item->trashed(), 404);
                        if ((int) $data['item_id'] !== $position->item_id || (int) $data['warehouse_id'] !== $position->warehouse_id) {
                            throw ValidationException::withMessages(['item_id' => 'A identidade da posição emitida não pode ser alterada.']);
                        }
                    } elseif ($operation === 'archive' && ! $position->trashed()) {
                        if ($position->qty_available > 0 || InventoryTransaction::withTrashed()->where('inventory_id', $position->id)->exists()
                            || InventoryItemTransfer::withTrashed()->where('item_id', $position->item_id)
                                ->where(fn (Builder $query): Builder => $query->where('source_id', $position->warehouse_id)->orWhere('destination_id', $position->warehouse_id))->exists()) {
                            throw ValidationException::withMessages(['recordIds' => 'Uma posição com existências ou histórico de movimentos não pode ser arquivada.']);
                        }
                    } elseif ($operation === 'restore') {
                        abort_if($item->trashed(), 404);
                        if (Inventory::query()->where('item_id', $position->item_id)->where('warehouse_id', $position->warehouse_id)
                            ->where('id', '<>', $position->id)->exists()) {
                            throw ValidationException::withMessages(['recordIds' => 'Já existe uma posição activa para este item e armazém.']);
                        }
                    }
                    $key = $position->item_id.':'.$position->warehouse_id;
                    if (isset($contexts[$key])) {
                        throw ValidationException::withMessages(['recordIds' => 'Seleccione uma única posição por item e armazém.']);
                    }
                    $contexts[$key] = [$context, $position];
                }
                foreach ($contexts as &$entry) {
                    [$context, $position] = $entry;
                    if ($operation === 'update') {
                        $position->fill(['min_stock_level' => $data['min_stock_level'], 'reorder_point' => $data['reorder_point']]);
                        if ($position->isDirty()) {
                            $this->evidence->save($context, $position);
                        }
                    } elseif ($position->trashed() !== ($operation === 'archive')) {
                        $startedAt = $position->freshTimestampString();
                        abort_unless($operation === 'archive' ? $position->delete() : $position->restore(), 409);
                        $expected = $context['rows'][Inventory::class][$position->id];
                        $deletedAt = $position->getRawOriginal('deleted_at');
                        $updatedAt = $position->getRawOriginal('updated_at');
                        abort_unless($operation === 'archive' ? is_string($deletedAt) && $deletedAt >= $startedAt && $deletedAt <= $position->freshTimestampString() : $deletedAt === null, 409);
                        abort_unless(is_string($updatedAt) && $updatedAt >= $startedAt && $updatedAt <= $position->freshTimestampString(), 409);
                        $expected['deleted_at'] = $deletedAt;
                        $expected['updated_at'] = $updatedAt;
                        $context['rows'][Inventory::class][$position->id] = $expected;
                    }
                    $entry = [$context, $position];
                }
                unset($entry);
                foreach ($contexts as [$context]) {
                    $this->evidence->assert($context);
                }
            });
        } catch (LogicException $exception) {
            throw new HttpException(409, 'A posição ou o seu histórico mudou; a operação foi cancelada.', $exception);
        }
    }
}
