<?php

namespace App\Services;

use App\Models\VAPNonConformity;
use Illuminate\Container\Attributes\Give;
use Illuminate\Database\DatabaseTransactionRecord;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class NonConformityEvidence
{
    public function __construct(#[Give('db.transactions')] private readonly DatabaseTransactionsManager $transactions) {}

    /** @param list<UploadedFile> $files */
    public function add(VAPNonConformity $record, array $files): void
    {
        if ($files === []) {
            return;
        }
        $transactions = $this->transactions->getPendingTransactions()
            ->filter(fn (DatabaseTransactionRecord $transaction): bool => $transaction->connection === DB::connection()->getName());
        abort_unless($transactions->isNotEmpty(), 409, 'É necessária uma transacção para guardar evidências.');
        $expected = [];
        try {
            foreach ($files as $file) {
                $uuid = (string) Str::uuid();
                $directory = 'quality-evidence/'.$uuid.'/';
                foreach ($transactions as $transaction) {
                    $transaction->addCallbackForRollback(fn () => $this->discard($directory));
                }
                $filename = 'quality-evidence-'.$uuid.'.'.$file->extension();
                $hash = hash_file('sha256', $file->getRealPath());
                $size = filesize($file->getRealPath());
                $media = $record->addMedia($file)->preservingOriginal()
                    ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->usingFileName($filename)
                    ->withCustomProperties(['quality_evidence_uuid' => $uuid, 'document_sha256' => $hash])
                    ->withAttributes(['uuid' => $uuid])->toMediaCollection('attachments', 'local');
                $expected[] = [$media, $uuid, $filename, $hash, $size];
            }

            foreach ($expected as [$media, $uuid, $filename, $hash, $size]) {
                $stored = $media->fresh();
                $path = 'quality-evidence/'.$uuid.'/'.$filename;
                $disk = Storage::disk('local');
                abort_unless($stored && $stored->uuid === $uuid && $stored->model_type === $record->getMorphClass()
                    && (int) $stored->model_id === (int) $record->id && $stored->collection_name === 'attachments'
                    && $stored->disk === 'local' && $stored->file_name === $filename && (int) $stored->size === $size
                    && $stored->getCustomProperty('quality_evidence_uuid') === $uuid
                    && $stored->getCustomProperty('document_sha256') === $hash
                    && $disk->exists($path) && hash_equals($hash, hash('sha256', $disk->get($path))),
                    409, 'A evidência guardada difere do ficheiro submetido.');
            }
        } catch (Throwable $exception) {
            report($exception);
            throw new HttpException(409, 'Não foi possível guardar as evidências. Nenhuma alteração foi guardada; tente novamente.', $exception);
        }
    }

    private function discard(string $directory): void
    {
        try {
            $disk = Storage::disk('local');
            if ($disk->exists($directory) && ! $disk->deleteDirectory($directory)) {
                throw new RuntimeException('Não foi possível limpar a evidência de uma operação cancelada.');
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
