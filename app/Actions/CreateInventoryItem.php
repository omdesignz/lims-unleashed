<?php

namespace App\Actions;

use App\Enums\InventoryCategoryType;
use App\Models\Inventory;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ItemCategory;
use App\Models\ReagentConsumption;
use App\Models\ReagentConsumptionReversal;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemCreationDocuments;
use App\Services\InventoryItemCreationValidation;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Support\InventoryQuantity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateInventoryItem
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly InventoryItemCreationValidation $validation,
        private readonly InventoryItemCreationDocuments $documents,
        private readonly InventoryCatalogueAccess $catalogueAccess,
    ) {}

    /** @param array<string,mixed> $data */
    public function execute(int $labId, int $userId, array $data, ?InventoryCategoryType $requiredType = null): InventoryItem
    {
        try {
            return DB::transaction(function () use ($labId, $userId, $data, $requiredType): InventoryItem {
                abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
                $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
                abort_unless($lab, 403);
                $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
                $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
                $data = Validator::make($data, $this->validation->rules($labId))->validate();
                $references = $this->lockReferences($labId, $data);
                $retained = $this->scopeItems($labId, (int) $data['category_id']);
                [$counter, $next] = $this->lockSequence($labId, (int) $data['category_id'], $retained);
                $retainedGraph = $this->retainedGraph(array_keys($retained));
                $typeRow = InventoryTransactionType::withTrashed()->where('code', 'stock_in')->lockForUpdate()->toBase()->first();
                if ($typeRow !== null) {
                    $references[InventoryTransactionType::class][(int) $typeRow->id] = (array) $typeRow;
                }
                $type = $this->catalogueAccess->type($references[ItemCategory::class][(int) $data['category_id']]['inventory_type'] ?? null);
                if ($requiredType !== null && $type !== $requiredType) {
                    throw ValidationException::withMessages(['category_id' => 'A categoria não corresponde ao tipo desta importação.']);
                }
                $permission = $this->catalogueAccess->permission('add', $type);
                $this->access->operator($userId, $labId, $permission);
                $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
                $this->assertReferences($references);
                Validator::make($data, $this->validation->rules($labId))->validate();

                $item = new InventoryItem;
                $attributes = array_fill_keys($item->getFillable(), null);
                $attributes = array_replace($attributes, [
                    'reorder_qty' => '0.00', 'packed_depth' => '0.00', 'packed_width' => '0.00',
                    'packed_height' => '0.00', 'packed_weight' => '0.00',
                    'refrigerated' => false, 'has_safety_documentation' => true, 'is_reagent' => false,
                ], Arr::except($data, ['warehouses', 'documents']), ['lab_id' => $labId, 'user_id' => $userId]);
                foreach (['reorder_qty', 'packed_depth', 'packed_width', 'packed_height', 'packed_weight'] as $field) {
                    $attributes[$field] ??= '0.00';
                }
                foreach (array_keys(InventoryItemCreationValidation::references()) as $field) {
                    $attributes[$field] = isset($attributes[$field]) ? (int) $attributes[$field] : null;
                }
                $item->forceFill($attributes);
                $category = new ItemCategory;
                $category->setRawAttributes($references[ItemCategory::class][$attributes['category_id']], true);
                $category->exists = true;
                $item->setRelation('category', $category);
                if (blank($item->internal_code)) {
                    $item->internal_code = $item->generatedInternalCode($next);
                }
                $expectedItem = $this->saveNew($item, ['seq' => $next]);
                abort_if(isset($retained[$item->id]), 409, 'A identidade do item foi reutilizada.');
                $positions = [];
                $ledgers = [];
                $type = null;
                $warehouseData = $data['warehouses'] ?? [];
                usort($warehouseData, fn (array $left, array $right): int => (int) $left['id'] <=> (int) $right['id']);
                foreach ($warehouseData as $warehouse) {
                    $quantities = [];
                    foreach (['qty_available', 'min_stock_level', 'reorder_point'] as $field) {
                        $quantities[$field] = InventoryQuantity::fromScaled(InventoryQuantity::toScaled($warehouse[$field] ?? '0'));
                    }
                    $position = new Inventory($quantities + ['lab_id' => $labId, 'item_id' => $item->id,
                        'warehouse_id' => (int) $warehouse['id'], 'status' => 'AVAILABLE']);
                    $expectedPosition = $this->saveNew($position);
                    $positions[$position->id] = $expectedPosition;
                    if (InventoryQuantity::toScaled($quantities['qty_available']) > 0) {
                        $type ??= $this->openingType();
                        $ledger = new InventoryTransaction(['lab_id' => $labId, 'inventory_id' => $position->id,
                            'user_id' => $userId, 'warehouse_id' => (int) $warehouse['id'], 'item_id' => $item->id,
                            'type_id' => $type->id, 'batch_id' => null, 'qty' => $quantities['qty_available'],
                            'reason' => 'Existências iniciais', 'notes' => null]);
                        $expectedLedger = $this->saveNew($ledger);
                        $ledgers[$ledger->id] = $expectedLedger;
                    }
                }
                $documents = $this->documents->add($item, $data['documents'] ?? []);
                $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
                $this->access->operator($userId, $labId, $permission);
                abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
                $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
                $this->assertReferences($references);
                $this->assertModel($expectedItem);
                $storedItems = $this->scopeItems($labId, (int) $data['category_id'], array_keys($retained));
                unset($storedItems[$item->id]);
                abort_unless($storedItems === $retained, 409, 'Os itens já existentes foram alterados.');
                abort_unless($this->retainedGraph(array_keys($retained), $retainedGraph) === $retainedGraph,
                    409, 'As existências, o histórico ou os documentos já existentes foram alterados.');
                $counter['last_value'] = $next;
                abort_unless((array) DB::table('sequence_counters')->where('scope_hash', $counter['scope_hash'])->first() === $counter,
                    409, 'A reserva da sequência do item foi alterada.');
                $this->assertNewGraph($item, $positions, $ledgers, $documents);
                if ($type !== null) {
                    $this->assertModel($type);
                }
                $this->documents->assert($documents);
                $expectedItem->exists = true;
                $expectedItem->wasRecentlyCreated = true;
                $expectedItem->syncOriginal();

                return $expectedItem;
            });
        } catch (UniqueConstraintViolationException $exception) {
            foreach (['code', 'barcode', 'internal_code'] as $field) {
                if (str_contains($exception->getMessage(), 'i_items_lab_id_'.$field.'_unique')) {
                    throw ValidationException::withMessages([$field => 'Este identificador já existe neste laboratório.']);
                }
            }
            throw $exception;
        }
    }

    /** @param array<string,mixed> $data @return array<class-string<Model>,array<int,array<string,mixed>>> */
    private function lockReferences(int $labId, array $data): array
    {
        $references = [];
        foreach (InventoryItemCreationValidation::references() as $field => $class) {
            if (isset($data[$field])) {
                $row = (new $class)->newQuery()->whereKey((int) $data[$field])->lockForUpdate()->toBase()->first();
                if (! $row) {
                    throw ValidationException::withMessages([$field => 'Seleccione um registo activo.']);
                }
                $references[$class][(int) $row->id] = (array) $row;
            }
        }
        $ids = array_map(fn (array $warehouse): int => (int) $warehouse['id'], $data['warehouses'] ?? []);
        $rows = InventoryItemWarehouse::query()->where('lab_id', $labId)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->toBase()->get();
        if ($rows->count() !== count($ids)) {
            throw ValidationException::withMessages(['warehouses' => 'Seleccione apenas armazéns activos deste laboratório.']);
        }
        foreach ($rows as $row) {
            $references[InventoryItemWarehouse::class][(int) $row->id] = (array) $row;
        }

        return $references;
    }

    /** @param array<class-string<Model>,array<int,array<string,mixed>>> $references */
    private function assertReferences(array $references): void
    {
        foreach ($references as $class => $rows) {
            foreach ($rows as $id => $expected) {
                abort_unless((array) (new $class)->newQueryWithoutScopes()->whereKey($id)->toBase()->first() === $expected,
                    409, 'Um registo de referência do item foi alterado.');
            }
        }
    }

    private function assertAuthority(int $labId, int $userId, object $lab, ?object $actor, ?object $membership): void
    {
        abort_unless((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() === (array) $lab
            && (array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor
            && (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() === (array) $membership,
            409, 'A autorização ou a identidade do operador foi alterada.');
    }

    /** @param list<int> $retainedIds @return array<int,array<string,mixed>> */
    private function scopeItems(int $labId, int $categoryId, array $retainedIds = []): array
    {
        return InventoryItem::withTrashed()->where(fn (Builder $query): Builder => $query
            ->where(fn (Builder $query): Builder => $query->where('lab_id', $labId)->where('category_id', $categoryId))
            ->orWhereIn('id', $retainedIds))->orderBy('id')->lockForUpdate()->toBase()->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
    }

    /** @param array<int,array<string,mixed>> $retained @return array{array<string,mixed>,int} */
    private function lockSequence(int $labId, int $categoryId, array $retained): array
    {
        $scope = ['category_id' => (string) $categoryId, 'lab_id' => (string) $labId];
        $hash = hash('sha256', json_encode(['i_items', 'seq', $scope], JSON_THROW_ON_ERROR));
        DB::table('sequence_counters')->insertOrIgnore(['scope_hash' => $hash, 'table_name' => 'i_items', 'field_name' => 'seq',
            'scope_values' => json_encode($scope, JSON_THROW_ON_ERROR), 'last_value' => 0]);
        $counter = (array) DB::table('sequence_counters')->where('scope_hash', $hash)->lockForUpdate()->first();
        $highest = max([(int) $counter['last_value'], ...array_map(fn (array $row): int => (int) $row['seq'], $retained)]);
        abort_if($highest === PHP_INT_MAX, 409, 'A sequência do item atingiu o limite permitido.');

        return [$counter, $highest + 1];
    }

    private function openingType(): InventoryTransactionType
    {
        $row = InventoryTransactionType::withTrashed()->where('code', 'stock_in')->lockForUpdate()->toBase()->first();
        if ($row !== null) {
            if ($row->deleted_at !== null) {
                throw ValidationException::withMessages(['warehouses' => 'O tipo de entrada de existências está arquivado.']);
            }
            $type = new InventoryTransactionType;
            $type->setRawAttributes((array) $row, true);
            $type->exists = true;

            return $type;
        }
        try {
            return DB::transaction(fn (): InventoryTransactionType => $this->saveNew(new InventoryTransactionType([
                'code' => 'stock_in', 'name' => 'Entrada de existências',
                'description' => 'Entrada inicial registada na criação do item.',
            ])));
        } catch (UniqueConstraintViolationException $exception) {
            $row = InventoryTransactionType::query()->where('code', 'stock_in')->lockForUpdate()->toBase()->first();
            if (! $row) {
                throw $exception;
            }
            $type = new InventoryTransactionType;
            $type->setRawAttributes((array) $row, true);
            $type->exists = true;

            return $type;
        }
    }

    /** @template T of Model @param T $model @param array<string,mixed> $generated @return T */
    private function saveNew(Model $model, array $generated = []): Model
    {
        $model->deleted_at = null;
        $model->setCreatedAt($model->freshTimestamp());
        $model->setUpdatedAt($model->created_at);
        $expected = clone $model;
        $expected->forceFill($generated);
        abort_unless($model::withoutTimestamps(fn (): bool => $model->save()) && $model->exists && $model->getKey(),
            409, 'Não foi possível guardar todos os registos do item.');
        $expected->setAttribute($model->getKeyName(), $model->getKey());
        $expected->exists = true;
        $this->assertModel($expected);

        return $expected;
    }

    private function assertModel(Model $expected): void
    {
        $row = $expected->newQueryWithoutScopes()->whereKey($expected->getKey())->toBase()->first();
        abort_unless($row, 409, 'Um registo do item desapareceu.');
        $stored = (array) $row;
        foreach ($expected->getAttributes() as $field => $value) {
            abort_unless($this->normalize($expected, $field, $value) === $this->normalize($expected, $field, $stored[$field] ?? null),
                409, 'O registo guardado difere do item submetido: '.$field.'.');
        }
    }

    private function normalize(Model $model, string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $cast = $model->getCasts()[$field] ?? '';
        if ($cast === 'boolean') {
            return (bool) $value;
        }
        if (str_starts_with($cast, 'decimal:') || in_array($field, ['last_purchase_price', 'standard_cost'], true)) {
            $precision = str_starts_with($cast, 'decimal:') ? (int) substr($cast, 8) : 4;
            $parts = explode('.', (string) $value, 2);

            return (ltrim($parts[0], '0') ?: '0').'.'.str_pad($parts[1] ?? '', $precision, '0');
        }
        if (in_array($field, ['created_at', 'updated_at', 'deleted_at'], true) || in_array($cast, ['date', 'datetime'], true)) {
            return Carbon::parse($value)->format($cast === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s.u');
        }

        return is_scalar($value) ? (string) $value : $value;
    }

    /** @param array<int,Inventory> $positions @param array<int,InventoryTransaction> $ledgers @param list<\App\Models\InventoryItemDocumentMedia> $documents */
    private function assertNewGraph(InventoryItem $item, array $positions, array $ledgers, array $documents): void
    {
        foreach ([Inventory::class => $positions, InventoryTransaction::class => $ledgers] as $class => $expected) {
            $ids = (new $class)->newQueryWithoutScopes()->where('item_id', $item->id)->orderBy('id')->toBase()->pluck('id')->all();
            $expectedIds = array_keys($expected);
            sort($expectedIds);
            abort_unless($ids === $expectedIds, 409, 'As existências ou os movimentos do item foram alterados.');
            foreach ($expected as $record) {
                $this->assertModel($record);
            }
        }
        $positionIds = array_keys($positions);
        abort_if((new InventoryBatch)->newQueryWithoutScopes()->whereIn('inventory_id', $positionIds)->toBase()->exists()
            || InventoryItemTransfer::withTrashed()->where('item_id', $item->id)->toBase()->exists()
            || ReagentConsumption::query()->where('reagent_id', $item->id)->toBase()->exists(),
            409, 'A criação do item gerou operações inesperadas.');
        $documentIds = array_map(fn ($document): int => $document->id, $documents);
        sort($documentIds);
        abort_unless(DB::table('media')->where('model_type', $item->getMorphClass())->where('model_id', $item->id)
            ->orderBy('id')->pluck('id')->all() === $documentIds, 409, 'Os documentos do item foram alterados.');
    }

    /** @param list<int> $itemIds @param array<string,array<int,array<string,mixed>>> $expected @return array<string,array<int,array<string,mixed>>> */
    private function retainedGraph(array $itemIds, array $expected = []): array
    {
        $graph = [];
        foreach ([Inventory::class, InventoryTransaction::class, InventoryItemTransfer::class, ReagentConsumption::class, ReagentConsumptionReversal::class,
            InventoryBatch::class, InventoryItemDocumentMedia::class] as $class) {
            $model = new $class;
            $query = $model->newQueryWithoutScopes()->where(function (Builder $query) use ($class, $itemIds, $graph, $expected): void {
                if ($class === ReagentConsumptionReversal::class) {
                    $query->whereIn('consumption_id', array_keys($graph[ReagentConsumption::class]));
                } elseif ($class === InventoryBatch::class) {
                    $query->whereIn('inventory_id', array_keys($graph[Inventory::class]));
                } elseif ($class === InventoryItemDocumentMedia::class) {
                    $query->where('model_type', (new InventoryItem)->getMorphClass())->whereIn('model_id', $itemIds);
                } else {
                    $query->whereIn($class === ReagentConsumption::class ? 'reagent_id' : 'item_id', $itemIds);
                }
                $query->orWhereIn('id', array_keys($expected[$class] ?? []));
            });
            $graph[$class] = $query->orderBy('id')->lockForUpdate()->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
        }

        return $graph;
    }
}
