<?php

namespace App\Actions;

use App\Models\InventoryItemLocation;
use App\Models\InventoryItemWarehouse;
use App\Models\ISOActivityLog;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryWarehouseEvidence;
use App\Services\InventoryWarehouseValidation;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveInventoryWarehouse
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly InventoryWarehouseValidation $validation,
        private readonly InventoryWarehouseEvidence $evidence,
    ) {}

    /** @param array<string,mixed> $data */
    public function execute(int $labId, int $userId, array $data, ?int $warehouseId = null): InventoryItemWarehouse
    {
        return DB::transaction(function () use ($labId, $userId, $data, $warehouseId): InventoryItemWarehouse {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
            abort_unless($lab, 403);
            $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
            $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
            $roots = $this->warehouses($labId);
            $warehouse = new InventoryItemWarehouse;
            if ($warehouseId !== null) {
                abort_unless(isset($roots[$warehouseId]) && $roots[$warehouseId]['deleted_at'] === null, 404);
                $warehouse->setRawAttributes($roots[$warehouseId], true);
                $warehouse->exists = true;
            }
            $data = Validator::make($data, $this->validation->rules($labId, $warehouse->exists ? $warehouse : null))->validate();
            $locationIds = array_values(array_unique(array_filter([(int) $data['location_id'], $warehouse->location_id])));
            $locations = InventoryItemLocation::withTrashed()->whereKey($locationIds)->orderBy('id')->lockForUpdate()->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            $references = $this->evidence->references(array_keys($roots));
            $history = $this->evidence->history(array_keys($roots));
            $permission = $warehouseId === null ? 'add_iwarehouses' : 'edit_iwarehouses';
            $operator = $this->access->operator($userId, $labId, $permission);
            $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
            Validator::make($data, $this->validation->rules($labId, $warehouse->exists ? $warehouse : null))->validate();
            $data['location_id'] = (int) $data['location_id'];
            foreach (['is_refrigerated', 'is_ventilated', 'has_air_exhaustion'] as $field) {
                $data[$field] = (bool) $data[$field];
            }
            $warehouse->fill($data);
            $auditExpected = null;
            if (! $warehouse->exists || $warehouse->isDirty()) {
                if (! $warehouse->exists) {
                    $warehouse->lab_id = $labId;
                    $warehouse->deleted_at = null;
                    $warehouse->setCreatedAt($warehouse->freshTimestamp());
                }
                $warehouse->setUpdatedAt($warehouse->freshTimestamp());
                $intended = clone $warehouse;
                abort_unless(InventoryItemWarehouse::withoutTimestamps(fn (): bool => $warehouse->save()) && $warehouse->exists && $warehouse->id,
                    409, 'Não foi possível guardar o armazém.');
                $intended->id = $warehouse->id;
                abort_if($warehouseId === null && isset($roots[$warehouse->id]), 409, 'A identidade do armazém foi reutilizada.');
                $this->assertWarehouse($intended);
                $description = $warehouseId === null ? 'criou o armazém de existências' : 'actualizou o armazém de existências';
                $audit = activity('inventory_warehouse')->causedBy($operator)->performedOn($intended)
                    ->event($warehouseId === null ? 'created' : 'updated')->withProperties(['lab_id' => $labId])
                    ->tap(function (ISOActivityLog $entry) use ($description, &$auditExpected): void {
                        $entry->description = $description;
                        $entry->setCreatedAt($entry->freshTimestamp());
                        $entry->setUpdatedAt($entry->created_at);
                        $auditExpected = $entry->getAttributes();
                    })->log($description);
                abort_unless($audit?->exists && $audit->id && ! isset($history[$audit->id]), 409, 'Não foi possível registar o armazém.');
                $auditExpected['id'] = $audit->id;
                $roots[$intended->id] = $intended->getAttributes();
                $warehouse = $intended;
            }
            $this->access->operator($userId, $labId, $permission);
            $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
            $storedRoots = $this->warehouses($labId, array_keys($roots));
            foreach ($roots as $id => $expected) {
                ksort($expected);
                $stored = $storedRoots[$id] ?? [];
                ksort($stored);
                abort_unless($stored === $expected, 409, 'O armazém guardado não corresponde à operação.');
            }
            abort_unless(count($storedRoots) === count($roots), 409, 'Os armazéns já existentes foram alterados.');
            $storedLocations = InventoryItemLocation::withTrashed()->whereKey(array_keys($locations))->orderBy('id')->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            abort_unless($storedLocations === $locations, 409, 'As localizações do armazém não foram preservadas.');
            abort_unless($this->evidence->references(array_keys($roots), $references) === $references, 409, 'As referências do armazém não foram preservadas.');
            $storedHistory = $this->evidence->history(array_keys($roots), array_keys($history));
            if ($auditExpected !== null) {
                $actual = $storedHistory[$auditExpected['id']] ?? null;
                abort_unless($actual && $this->evidence->canonicalAudit($actual) === $this->evidence->canonicalAudit($auditExpected),
                    409, 'O histórico guardado não corresponde à operação.');
                unset($storedHistory[$auditExpected['id']]);
            }
            abort_unless($storedHistory === $history, 409, 'O histórico anterior dos armazéns não foi preservado.');
            $warehouse->exists = true;
            $warehouse->wasRecentlyCreated = $warehouseId === null;
            $warehouse->syncOriginal();

            return $warehouse;
        });
    }

    /** @param list<int> $retainedIds @return array<int,array<string,mixed>> */
    private function warehouses(int $labId, array $retainedIds = []): array
    {
        return InventoryItemWarehouse::withTrashed()->where(fn (Builder $query): Builder => $query
            ->where('lab_id', $labId)->orWhereIn('id', $retainedIds))->orderBy('id')->lockForUpdate()->toBase()->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
    }

    private function assertWarehouse(InventoryItemWarehouse $expected): void
    {
        $row = InventoryItemWarehouse::withTrashed()->whereKey($expected->id)->toBase()->first();
        $actual = $row ? (array) $row : [];
        $attributes = $expected->getAttributes();
        ksort($actual);
        ksort($attributes);
        abort_unless($actual === $attributes, 409, 'O armazém guardado não corresponde à operação.');
    }

    private function assertAuthority(int $labId, int $userId, object $lab, ?object $actor, ?object $membership): void
    {
        abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
        $storedLab = VAPLab::withTrashed()->whereKey($labId)->toBase()->first();
        abort_unless($storedLab && $storedLab->deleted_at === null && User::query()->whereKey($userId)
            ->where('is_active', true)->whereNotNull('email_verified_at')->whereIn('id',
                DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))->toBase()->exists(), 403);
        abort_unless((array) $storedLab === (array) $lab && (array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor
            && (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() === (array) $membership,
            409, 'Os dados do laboratório ou operador não foram preservados.');
    }
}
