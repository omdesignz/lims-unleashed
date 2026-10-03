<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use Illuminate\Container\Attributes\Give;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class InventoryItemCreationDocuments
{
    public function __construct(#[Give('db.transactions')] private readonly DatabaseTransactionsManager $transactions) {}

    /** @param list<UploadedFile> $files @return list<InventoryItemDocumentMedia> */
    public function add(InventoryItem $item, array $files, int $startingOrder = 1): array
    {
        if ($files === []) {
            return [];
        }
        abort_unless($startingOrder >= 1 && $startingOrder <= 2147483648 - count($files), 409, 'A ordem dos documentos atingiu o limite permitido.');
        $documents = [];
        foreach ($files as $index => $file) {
            $uuid = (string) Str::uuid();
            $directory = 'inventory-item-documents/'.$uuid.'/';
            $records = $this->transactions->getPendingTransactions()
                ->filter(fn (DatabaseTransactionRecord $record): bool => $record->connection === DB::connection()->getName());
            abort_unless($records->isNotEmpty(), 409, 'É necessária uma transacção para guardar o documento.');
            foreach ($records as $record) {
                $record->addCallbackForRollback(fn () => $this->discard($directory));
            }
            $filename = 'inventory-item-document-'.$uuid.'.'.$file->extension();
            $properties = ['inventory_creation_uuid' => $uuid, 'document_sha256' => hash_file('sha256', $file->getRealPath())];
            $intent = ['uuid' => $uuid, 'model_type' => $item->getMorphClass(), 'model_id' => $item->id,
                'collection_name' => 'documents', 'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_name' => $filename, 'disk' => 'local', 'conversions_disk' => 'local',
                'mime_type' => File::mimeType($file->getRealPath()), 'size' => filesize($file->getRealPath()),
                'custom_properties' => $properties, 'order_column' => $startingOrder + $index,
                'manipulations' => [], 'generated_conversions' => [], 'responsive_images' => []];
            try {
                $media = $item->addMedia($file)->preservingOriginal()->usingName($intent['name'])->usingFileName($filename)
                    ->setOrder($startingOrder + $index)->withCustomProperties($properties)
                    ->withAttributes(['uuid' => $uuid, 'inventory_creation_intent' => $intent])->toMediaCollection('documents', 'local');
                abort_unless($media instanceof InventoryItemDocumentMedia, 409, 'Não foi possível guardar o documento do item.');
                $documents[] = $media;
                $this->assert($documents);
            } catch (Throwable $exception) {
                $this->discard($directory);
                throw new HttpException(409, 'Não foi possível guardar o documento do item.', $exception);
            }
        }

        return $documents;
    }

    /** @param list<InventoryItemDocumentMedia> $documents */
    public function assert(array $documents): void
    {
        foreach ($documents as $document) {
            $document->assertCreationEvidence();
            $expected = $document->frozenCreationEvidence();
            $path = 'inventory-item-documents/'.$expected->uuid.'/'.$expected->file_name;
            $disk = Storage::disk('local');
            abort_unless($disk->exists($path) && hash_equals((string) $expected->getCustomProperty('document_sha256'), hash('sha256', $disk->get($path))),
                409, 'O ficheiro guardado difere do documento submetido.');
        }
    }

    private function discard(string $directory): void
    {
        try {
            $disk = Storage::disk('local');
            if ($disk->exists($directory) && ! $disk->deleteDirectory($directory)) {
                throw new RuntimeException('Não foi possível remover o documento de uma operação cancelada.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
