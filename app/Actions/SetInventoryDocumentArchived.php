<?php

namespace App\Actions;

use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\ISOActivityLog;
use App\Models\User;
use App\Models\VAPLab;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryItemEvidence;
use App\Services\LaboratoryWorkflowMutationAccess;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SetInventoryDocumentArchived
{
    public function __construct(
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly InventoryCatalogueAccess $catalogueAccess,
        private readonly InventoryItemEvidence $evidence,
    ) {}

    public function execute(int $labId, int $userId, int $itemId, int $mediaId, bool $archived): void
    {
        DB::transaction(function () use ($labId, $userId, $itemId, $mediaId, $archived): void {
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
            $permission = $this->catalogueAccess->permission('edit', $this->catalogueAccess->itemType($item));
            $operator = $this->access->operator($userId, $labId, $permission);
            $graph = $this->evidence->graph($itemId);
            $mediaRow = $graph[InventoryItemDocumentMedia::class][$mediaId] ?? null;
            abort_unless($mediaRow && $mediaRow['model_type'] === $item->getMorphClass()
                && (int) $mediaRow['model_id'] === $itemId && $mediaRow['collection_name'] === 'documents', 404);
            $document = new InventoryItemDocumentMedia;
            $document->setRawAttributes($mediaRow, true);
            $document->exists = true;
            $path = $document->getPathRelativeToRoot();
            $disk = Storage::disk($document->disk);
            abort_unless($disk->exists($path), 409, 'O conteúdo retido do documento não está disponível.');
            $hash = $this->hash($disk, $path);
            $history = $this->evidence->history([$itemId]);
            $audit = null;
            $intendedAudit = [];
            if ($document->trashed() !== $archived) {
                $startedAt = $document->freshTimestampString();
                abort_unless($archived ? $document->delete() : $document->restore(), 409, 'Não foi possível actualizar o arquivo do documento.');
                $expected = $mediaRow;
                $expected['deleted_at'] = $document->getRawOriginal('deleted_at');
                $expected['updated_at'] = $document->getRawOriginal('updated_at');
                abort_unless($archived ? is_string($expected['deleted_at']) && $expected['deleted_at'] >= $startedAt
                    && $expected['deleted_at'] <= $document->freshTimestampString() : $expected['deleted_at'] === null, 409);
                abort_unless(is_string($expected['updated_at']) && $expected['updated_at'] >= $startedAt
                    && $expected['updated_at'] <= $document->freshTimestampString(), 409);
                $graph[InventoryItemDocumentMedia::class][$mediaId] = $expected;
                $description = $archived ? 'arquivou o documento do item' : 'restaurou o documento do item';
                $audit = activity('inventory_item')->causedBy($operator)->performedOn($item)
                    ->event($archived ? 'document_archived' : 'document_restored')
                    ->withProperties(['lab_id' => $labId, 'document_id' => $mediaId, 'sha256' => $hash])
                    ->tap(function (ISOActivityLog $entry) use ($description, &$intendedAudit): void {
                        $entry->description = $description;
                        $entry->setCreatedAt($entry->freshTimestamp());
                        $entry->setUpdatedAt($entry->created_at);
                        $intendedAudit = $entry->getAttributes();
                    })->log($description);
                abort_unless($audit?->exists && $audit->id && ! isset($history[$audit->id]), 409);
                $intendedAudit['id'] = $audit->id;
            }
            $this->access->operator($userId, $labId, $permission);
            abort_if(request()->hasSession() && request()->session()->has('impersonate'), 403);
            abort_unless((array) VAPLab::withTrashed()->whereKey($labId)->toBase()->first() === (array) $lab
                && (array) User::withTrashed()->whereKey($userId)->toBase()->first() === (array) $actor
                && (array) DB::table('lab_user')->where('lab_id', $labId)->where('user_id', $userId)->first() === (array) $membership
                && (array) InventoryItem::withTrashed()->whereKey($itemId)->toBase()->first() === (array) $row, 409);
            abort_unless($this->evidence->graph($itemId, $graph) === $graph && $disk->exists($path)
                && hash_equals($hash, $this->hash($disk, $path)), 409, 'O conteúdo ou a evidência retida do documento mudou.');
            $retained = $this->evidence->history([$itemId], array_keys($history));
            if ($audit) {
                $actual = $retained[$audit->id] ?? null;
                abort_unless($actual && $this->evidence->canonicalAudit($actual) === $this->evidence->canonicalAudit($intendedAudit), 409);
                unset($retained[$audit->id]);
            }
            abort_unless($retained === $history, 409);
        });
    }

    private function hash(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 409);
        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }
}
