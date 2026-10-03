<?php

namespace App\Actions;

use App\Enums\InventoryCategoryType;
use App\Models\InventoryItem;
use App\Models\ISOActivityLog;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemArchiveValidation;
use App\Services\InventoryItemEvidence;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SetInventoryItemsArchived
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly InventoryCatalogueAccess $catalogueAccess,
        private readonly InventoryItemArchiveValidation $validation,
        private readonly InventoryItemEvidence $evidence,
    ) {}

    /** @param list<int|string> $recordIds */
    public function execute(int $userId, int $labId, array $recordIds, bool $archived, ?InventoryCategoryType $requiredType = null): int
    {
        Validator::make(['recordIds' => $recordIds], $this->validation->rules())->validate();
        $recordIds = array_map(intval(...), $recordIds);
        sort($recordIds);

        return DB::transaction(function () use ($userId, $labId, $recordIds, $archived, $requiredType): int {
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $lab = VAPLab::query()->whereKey($labId)->lockForUpdate()->toBase()->first();
            abort_unless($lab, 403);
            $membership = DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->lockForUpdate()->first();
            $actor = User::withTrashed()->whereKey($userId)->lockForUpdate()->toBase()->first();
            $rows = InventoryItem::withTrashed()->where('lab_id', $labId)->whereKey($recordIds)
                ->orderBy('id')->lockForUpdate()->toBase()->get()->keyBy('id');
            abort_unless($rows->count() === count($recordIds), 404);
            $expected = $rows->map(fn (object $row): array => (array) $row)->all();
            $categories = ItemCategory::withTrashed()->whereKey($rows->pluck('category_id')->filter()->unique()->all())
                ->orderBy('id')->lockForUpdate()->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            $permissions = [];
            $operators = [];
            foreach ($rows as $row) {
                $type = $this->catalogueAccess->type($categories[$row->category_id]['inventory_type'] ?? null);
                abort_unless($requiredType === null || $type === $requiredType, 404);
                $permission = $this->catalogueAccess->permission($archived ? 'delete' : 'restore', $type);
                $permissions[$row->id] = $permission;
                $operators[$permission] = $this->access->operator($userId, $labId, $permission);
            }
            $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
            $graphs = [];
            foreach ($recordIds as $id) {
                $graphs[$id] = $this->evidence->graph($id);
            }
            $history = $this->evidence->history($recordIds);
            $audits = [];
            $changed = 0;
            foreach ($rows as $row) {
                if (($row->deleted_at !== null) === $archived) {
                    continue;
                }
                $item = new InventoryItem;
                $item->setRawAttributes((array) $row, true);
                $item->exists = true;
                $startedAt = $item->freshTimestampString();
                abort_unless($archived ? $item->delete() : $item->restore(), 409, 'Não foi possível actualizar o arquivo do item.');
                $deletedAt = $item->getAttributes()['deleted_at'] ?? null;
                $updatedAt = $item->getAttributes()['updated_at'] ?? null;
                abort_unless($archived ? is_string($deletedAt) && $deletedAt >= $startedAt && $deletedAt <= $item->freshTimestampString() : $deletedAt === null, 409);
                abort_unless(is_string($updatedAt) && $updatedAt >= $startedAt && $updatedAt <= $item->freshTimestampString(), 409);
                $expected[$row->id]['deleted_at'] = $deletedAt;
                $expected[$row->id]['updated_at'] = $updatedAt;
                $description = $archived ? 'arquivou o item de catálogo' : 'restaurou o item de catálogo';
                $auditExpected = [];
                $audit = activity('inventory_item')->causedBy($operators[$permissions[$row->id]])->performedOn($item)
                    ->event($archived ? 'archived' : 'restored')->withProperties(['lab_id' => $labId])
                    ->tap(function (ISOActivityLog $entry) use ($description, &$auditExpected): void {
                        $entry->description = $description;
                        $entry->setCreatedAt($entry->freshTimestamp());
                        $entry->setUpdatedAt($entry->created_at);
                        $auditExpected = $entry->getAttributes();
                    })->log($description);
                abort_unless($audit?->exists && $audit->id && ! isset($history[$audit->id]), 409, 'Não foi possível registar o arquivo do item.');
                $auditExpected['id'] = $audit->id;
                $audits[$audit->id] = $auditExpected;
                $changed++;
            }
            foreach (array_unique($permissions) as $permission) {
                $this->access->operator($userId, $labId, $permission);
            }
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            $this->assertAuthority($labId, $userId, $lab, $actor, $membership);
            $stored = InventoryItem::withTrashed()->whereKey($recordIds)->orderBy('id')->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            abort_unless($stored === $expected, 409, 'Os itens guardados não correspondem à operação.');
            $storedCategories = ItemCategory::withTrashed()->whereKey(array_keys($categories))->orderBy('id')->toBase()->get()
                ->mapWithKeys(fn (object $row): array => [(int) $row->id => (array) $row])->all();
            abort_unless($storedCategories === $categories, 409, 'A classificação dos itens não foi preservada.');
            foreach ($graphs as $id => $graph) {
                abort_unless($this->evidence->graph($id, $graph) === $graph, 409, 'As existências ou referências do item não foram preservadas.');
            }
            $retained = $this->evidence->history($recordIds, array_keys($history));
            foreach ($audits as $id => $intended) {
                $actual = ISOActivityLog::withoutGlobalScopes()->whereKey($id)->toBase()->first();
                abort_unless($actual && $this->evidence->canonicalAudit((array) $actual) === $this->evidence->canonicalAudit($intended), 409, 'O histórico guardado não corresponde à operação.');
                unset($retained[$id]);
            }
            abort_unless($retained === $history, 409, 'O histórico anterior dos itens não foi preservado.');

            return $changed;
        });
    }

    private function assertAuthority(int $labId, int $userId, object $lab, ?object $actor, ?object $membership): void
    {
        abort_unless((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() === (array) $lab
            && (array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor
            && (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() === (array) $membership,
            409, 'A autorização ou a identidade do operador foi alterada.');
    }
}
