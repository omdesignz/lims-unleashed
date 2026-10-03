<?php

namespace App\Actions;

use App\Models\InventoryItemWarehouse;
use App\Models\ISOActivityLog;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryWarehouseEvidence;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SetInventoryWarehousesArchived
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly InventoryWarehouseEvidence $evidence,
    ) {}

    /** @param list<int> $recordIds */
    public function execute(int $userId, int $labId, array $recordIds, bool $archived): int
    {
        Validator::make(['recordIds' => $recordIds], [
            'recordIds' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        return DB::transaction(function () use ($userId, $labId, $recordIds, $archived): int {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
            abort_unless($lab, 403);
            $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
            $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
            $permission = $archived ? 'delete_iwarehouses' : 'restore_iwarehouses';
            $operator = $this->access->operator($userId, $labId, $permission);
            $rows = InventoryItemWarehouse::withTrashed()->where('lab_id', $labId)->whereKey($recordIds)
                ->orderBy('id')->lockForUpdate()->toBase()->get()->keyBy('id');
            abort_unless($rows->count() === count($recordIds), 404);
            $expected = $rows->map(fn (object $row): array => (array) $row)->all();
            $activeIds = $rows->filter(fn (object $row): bool => $row->deleted_at === null)->keys()->all();
            $references = $this->evidence->references($recordIds);
            if ($archived) {
                $this->evidence->assertUnused($activeIds);
            }
            $history = $this->evidence->history($recordIds);
            $audits = [];
            $changed = 0;
            foreach ($rows as $row) {
                if (($row->deleted_at !== null) === $archived) {
                    continue;
                }
                $warehouse = new InventoryItemWarehouse;
                $warehouse->setRawAttributes((array) $row, true);
                $warehouse->exists = true;
                $startedAt = $warehouse->freshTimestampString();
                abort_unless($archived ? $warehouse->delete() : $warehouse->restore(), 409, 'Não foi possível actualizar o arquivo do armazém.');
                $attributes = $warehouse->getAttributes();
                $deletedAt = $attributes['deleted_at'] ?? null;
                $updatedAt = $attributes['updated_at'] ?? null;
                abort_unless($archived ? is_string($deletedAt) && $deletedAt >= $startedAt && $deletedAt <= $warehouse->freshTimestampString() : $deletedAt === null, 409);
                abort_unless(is_string($updatedAt) && $updatedAt >= $startedAt && $updatedAt <= $warehouse->freshTimestampString(), 409);
                $expected[$row->id]['deleted_at'] = $deletedAt;
                $expected[$row->id]['updated_at'] = $updatedAt;
                $description = $archived ? 'arquivou o armazém de existências' : 'restaurou o armazém de existências';
                $auditExpected = [];
                $audit = activity('inventory_warehouse')->causedBy($operator)->performedOn($warehouse)
                    ->event($archived ? 'archived' : 'restored')->withProperties(['lab_id' => $labId])
                    ->tap(function (ISOActivityLog $entry) use ($description, &$auditExpected): void {
                        $entry->description = $description;
                        $entry->setCreatedAt($entry->freshTimestamp());
                        $entry->setUpdatedAt($entry->created_at);
                        $auditExpected = $entry->getAttributes();
                    })->log($description);
                abort_unless($audit?->exists && $audit->id && ! isset($history[$audit->id]), 409, 'Não foi possível registar o arquivo do armazém.');
                $auditExpected['id'] = $audit->id;
                $audits[$audit->id] = $auditExpected;
                $changed++;
            }
            $this->access->operator($userId, $labId, $permission);
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $storedLab = VAPLab::withTrashed()->whereKey($labId)->toBase()->first();
            abort_unless($storedLab && $storedLab->deleted_at === null && User::query()->whereKey($userId)
                ->where('is_active', true)->whereNotNull('email_verified_at')->whereIn('id',
                    DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))->toBase()->exists(), 403);
            abort_unless((array) $storedLab === (array) $lab, 409, 'Os dados do laboratório não foram preservados.');
            abort_unless((array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor
                && (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() === (array) $membership,
                409, 'Os dados do operador não foram preservados.');
            $stored = InventoryItemWarehouse::withTrashed()->whereKey($recordIds)->orderBy('id')->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            abort_unless($stored === $expected, 409, 'O armazém guardado não corresponde à operação.');
            abort_unless($this->evidence->references($recordIds, $references) === $references, 409, 'As referências do armazém não foram preservadas.');
            if ($archived) {
                $this->evidence->assertUnused($activeIds);
            }
            $retained = $this->evidence->history($recordIds, array_keys($history));
            foreach ($audits as $id => $intended) {
                $actual = ISOActivityLog::withoutGlobalScopes()->whereKey($id)->toBase()->first();
                abort_unless($actual && $this->evidence->canonicalAudit((array) $actual) === $this->evidence->canonicalAudit($intended), 409, 'O histórico guardado não corresponde à operação.');
                unset($retained[$id]);
            }
            abort_unless($retained === $history, 409, 'O histórico anterior dos armazéns não foi preservado.');

            return $changed;
        });
    }
}
