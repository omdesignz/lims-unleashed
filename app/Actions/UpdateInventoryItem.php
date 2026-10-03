<?php

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionType;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemCreationDocuments;
use App\Services\InventoryItemEvidence;
use App\Services\InventoryItemUpdateValidation;
use App\Services\LaboratoryWorkflowMutationAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateInventoryItem
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly InventoryItemUpdateValidation $validation,
        private readonly InventoryItemCreationDocuments $documents,
        private readonly InventoryCatalogueAccess $catalogueAccess,
        private readonly InventoryItemEvidence $evidence,
    ) {}

    /** @param array<string,mixed> $data */
    public function execute(int $labId, int $userId, int $itemId, array $data): InventoryItem
    {
        try {
            return DB::transaction(function () use ($labId, $userId, $itemId, $data): InventoryItem {
                abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
                $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
                abort_unless($lab, 403);
                $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
                $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
                $row = InventoryItem::query()->where('lab_id', $labId)->whereKey($itemId)->lockForUpdate()->toBase()->first();
                abort_unless($row, 404);
                $item = new InventoryItem;
                $item->setRawAttributes((array) $row, true);
                $item->exists = true;
                $graph = $this->evidence->graph($itemId);
                $data = Validator::make($data, $this->validation->rules($labId, $item), [
                    'category_id.in' => 'A categoria integra a sequência emitida e não pode ser alterada.',
                    'unit_id.in' => 'A unidade não pode ser alterada depois de criar existências para o item.',
                ])->validate();
                $references = $this->references($item, $data, $graph);
                $scope = ['category_id' => $item->category_id === null ? null : (string) $item->category_id, 'lab_id' => (string) $labId];
                $hash = hash('sha256', json_encode([$item->getTable(), 'seq', $scope], JSON_THROW_ON_ERROR));
                $counter = DB::table('sequence_counters')->where('scope_hash', $hash)->lockForUpdate()->first();
                $permission = $this->catalogueAccess->permission('edit', $this->catalogueAccess->type(
                    $references[ItemCategory::class][(int) $item->category_id]['inventory_type'] ?? null));
                $this->access->operator($userId, $labId, $permission);
                $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
                abort_unless((array) InventoryItem::withTrashed()->whereKey($itemId)->toBase()->first() === (array) $row,
                    409, 'O item foi alterado durante a autorização.');
                Validator::make($data, $this->validation->rules($labId, $item))->validate();
                $attributes = Arr::except($data, ['documents', 'warehouses']);
                foreach (array_keys(InventoryItemUpdateValidation::references()) as $field) {
                    if (array_key_exists($field, $attributes)) {
                        $attributes[$field] = $attributes[$field] === null ? null : (int) $attributes[$field];
                    }
                }
                foreach (['reorder_qty', 'packed_depth', 'packed_width', 'packed_height', 'packed_weight'] as $field) {
                    if (array_key_exists($field, $attributes)) {
                        $attributes[$field] ??= '0.00';
                    }
                }
                $item->fill($attributes);
                $hasMetadataChanges = $item->isDirty();
                if ($hasMetadataChanges) {
                    $item->setUpdatedAt($item->freshTimestamp());
                }
                $expected = clone $item;
                if ($hasMetadataChanges) {
                    abort_unless(InventoryItem::withoutTimestamps(fn (): bool => $item->save()), 409, 'Não foi possível actualizar o item.');
                }
                $this->assertItem($expected);
                $orders = array_map(fn (array $row): int => (int) $row['order_column'],
                    array_filter($graph[InventoryItemDocumentMedia::class], fn (array $row): bool => $row['collection_name'] === 'documents'));
                $documents = $this->documents->add($item, $data['documents'] ?? [], max([0, ...$orders]) + 1);
                $newIds = array_map(fn (InventoryItemDocumentMedia $document): int => $document->id, $documents);
                $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
                $this->access->operator($userId, $labId, $permission);
                abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
                $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
                $this->assertItem($expected);
                abort_unless($this->evidence->graph($itemId, $graph, $newIds) === $graph, 409, 'As existências, o histórico ou os documentos do item foram alterados.');
                foreach ($references as $class => $rows) {
                    foreach ($rows as $id => $reference) {
                        abort_unless((array) (new $class)->newQueryWithoutScopes()->whereKey($id)->toBase()->first() === $reference,
                            409, 'Um registo de referência do item foi alterado.');
                    }
                }
                abort_unless((array) DB::table('sequence_counters')->where('scope_hash', $hash)->first() === (array) $counter,
                    409, 'A sequência emitida do item foi alterada.');
                $this->documents->assert($documents);
                $expected->syncOriginal();

                return $expected;
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

    private function assertAuthority(int $labId, int $userId, object $lab, ?object $actor, ?object $membership): void
    {
        abort_unless((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() === (array) $lab
            && (array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor
            && (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() === (array) $membership,
            409, 'A autorização ou a identidade do operador foi alterada.');
    }

    /** @param array<string,mixed> $data @param array<class-string<\Illuminate\Database\Eloquent\Model>,array<int,array<string,mixed>>> $graph
     * @return array<class-string<Model>,array<int,array<string,mixed>>> */
    private function references(InventoryItem $item, array $data, array $graph): array
    {
        $references = [];
        foreach (InventoryItemUpdateValidation::references() as $field => $class) {
            $ids = array_values(array_unique(array_filter([$item->getRawOriginal($field), $data[$field] ?? null], fn ($id): bool => $id !== null)));
            foreach ((new $class)->newQueryWithoutScopes()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->toBase()->get() as $row) {
                $references[$class][(int) $row->id] = (array) $row;
            }
            if (count($references[$class] ?? []) !== count($ids)) {
                throw ValidationException::withMessages([$field => 'O registo seleccionado já não existe.']);
            }
        }
        $warehouseIds = [];
        foreach ($graph as $rows) {
            foreach ($rows as $row) {
                foreach (['warehouse_id', 'source_id', 'destination_id'] as $field) {
                    if (isset($row[$field])) {
                        $warehouseIds[] = (int) $row[$field];
                    }
                }
            }
        }
        foreach ([InventoryItemWarehouse::class => array_values(array_unique($warehouseIds)),
            InventoryTransactionType::class => array_values(array_unique(array_column($graph[InventoryTransaction::class], 'type_id')))] as $class => $ids) {
            foreach ((new $class)->newQueryWithoutScopes()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->toBase()->get() as $row) {
                $references[$class][(int) $row->id] = (array) $row;
            }
        }

        return $references;
    }

    private function assertItem(InventoryItem $expected): void
    {
        $stored = (array) $expected->newQueryWithoutScopes()->whereKey($expected->id)->toBase()->first();
        abort_unless(array_keys($stored) === array_keys($expected->getAttributes()), 409, 'O registo do item desapareceu ou mudou de estrutura.');
        foreach ($expected->getAttributes() as $field => $value) {
            abort_unless($this->normalize($expected, $field, $stored[$field]) === $this->normalize($expected, $field, $value),
                409, 'O item guardado difere das alterações submetidas: '.$field.'.');
        }
    }

    private function normalize(InventoryItem $item, string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $cast = $item->getCasts()[$field] ?? '';
        if ($cast === 'boolean') {
            return (bool) $value;
        }
        if (str_starts_with($cast, 'decimal:') || in_array($field, ['standard_cost', 'last_purchase_price'], true)) {
            $precision = str_starts_with($cast, 'decimal:') ? (int) substr($cast, 8) : 4;
            $parts = explode('.', (string) $value, 2);

            return (ltrim($parts[0], '0') ?: '0').'.'.str_pad($parts[1] ?? '', $precision, '0');
        }
        if (in_array($field, ['created_at', 'updated_at', 'deleted_at'], true) || in_array($cast, ['date', 'datetime'], true)) {
            return Carbon::parse($value)->format($cast === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s.u');
        }

        return is_scalar($value) ? (string) $value : $value;
    }
}
