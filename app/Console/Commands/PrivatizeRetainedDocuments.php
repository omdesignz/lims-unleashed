<?php

namespace App\Console\Commands;

use App\Models\GestlabMedia;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\User;
use App\Services\InventoryItemDocumentPathGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Throwable;

class PrivatizeRetainedDocuments extends Command
{
    protected $signature = 'app:privatize-documents {--database= : Exact expected PostgreSQL database name}';

    protected $description = 'Preserve private document bytes and resumably remove verified public copies';

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'pgsql' || ! is_string($this->option('database'))
            || $this->option('database') === '' || $connection->getDatabaseName() !== $this->option('database')
            || $connection->transactionLevel() !== 0) {
            $this->error('Refusing file or database writes: verify the exact PostgreSQL database and root transaction boundary.');

            return self::FAILURE;
        }
        try {
            $count = 0;
            $items = InventoryItemDocumentMedia::withTrashed()->where('model_type', (new InventoryItem)->getMorphClass())
                ->where('collection_name', 'documents')->orderBy('id')->cursor();
            foreach ($items as $record) {
                $this->privatize($record);
                $count++;
            }
            $personal = Media::query()->where('model_type', (new User)->getMorphClass())
                ->whereIn('collection_name', ['signature', 'exports'])->orderBy('id')->cursor();
            foreach ($personal as $record) {
                $this->privatize($record);
                $count++;
            }
            foreach (GestlabMedia::withTrashed()->orderBy('id')->cursor() as $record) {
                $this->privatize($record);
                $count++;
            }
            $this->info('Verified private retention and public-copy cleanup for '.$count.' records.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Private retention is incomplete. Original/private copies are preserved; resolve the failure and rerun.');

            return self::FAILURE;
        }
    }

    private function privatize(Model $record): void
    {
        if (! in_array($record->disk, ['public', 'local'], true)) {
            throw new RuntimeException('An unsupported document disk requires explicit review.');
        }
        if (! is_string($record->file_name) || $record->file_name === '' || basename($record->file_name) !== $record->file_name
            || in_array($record->file_name, ['.', '..'], true)) {
            throw new RuntimeException('A retained filename requires explicit review.');
        }
        $path = $record instanceof GestlabMedia ? $record->path : $record->getPathRelativeToRoot();
        if (str_starts_with($path, '/') || in_array('..', explode('/', $path), true) || str_contains($path, '\\') || str_contains($path, "\0")) {
            throw new RuntimeException('An invalid retained path requires explicit review.');
        }
        $directory = dirname($path);
        $expectedDirectory = $record instanceof GestlabMedia
            ? 'media/'.$record->created_at->format('Y/m/d').'/'.$record->id
            : rtrim(($record instanceof InventoryItemDocumentMedia
                ? new InventoryItemDocumentPathGenerator : new DefaultPathGenerator)->getPath($record), '/');
        if (in_array($directory, ['', '.', '/'], true) || $directory !== $expectedDirectory) {
            throw new RuntimeException('Refusing cleanup outside the exact record-specific directory.');
        }
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $files = $public->allFiles($directory);
        foreach ($files as $file) {
            $sourceHash = $this->hash($public, $file);
            if ($private->exists($file)) {
                if (! hash_equals($sourceHash, $this->hash($private, $file))) {
                    throw new RuntimeException('Private-copy conflict: neither retained copy was replaced.');
                }
            } else {
                $stream = $public->readStream($file);
                if (! is_resource($stream)) {
                    throw new RuntimeException('Cannot read retained public bytes.');
                }
                try {
                    if (! $private->put($file, $stream)) {
                        throw new RuntimeException('Cannot preserve retained private bytes.');
                    }
                } finally {
                    fclose($stream);
                }
            }
            if (! hash_equals($sourceHash, $this->hash($private, $file))) {
                throw new RuntimeException('Retained-copy verification failed.');
            }
        }
        if (! $private->exists($path)) {
            throw new RuntimeException('Retained original content is missing; no record was discarded.');
        }
        DB::transaction(function () use ($record): void {
            $row = DB::table($record->getTable())->where('id', $record->id)->lockForUpdate()->first();
            if (! $row || (array) $row !== $record->getRawOriginal()) {
                throw new RuntimeException('Retained document metadata changed before privacy conversion.');
            }
            $record->disk = 'local';
            if ($record instanceof Media) {
                if (! in_array($record->conversions_disk, [null, '', 'public', 'local'], true)) {
                    throw new RuntimeException('An unsupported conversion disk requires explicit review.');
                }
                $record->conversions_disk = 'local';
            }
            if ($record->isDirty()) {
                $record->setUpdatedAt($record->freshTimestamp());
                $expected = $record->getAttributes();
                if (! $record::withoutTimestamps(fn (): bool => $record->save())) {
                    throw new RuntimeException('Privacy metadata persistence was cancelled.');
                }
                $actual = (array) DB::table($record->getTable())->where('id', $record->id)->first();
                foreach (['created_at', 'updated_at', 'deleted_at'] as $field) {
                    if (array_key_exists($field, $expected) && $expected[$field] !== null) {
                        $expected[$field] = Carbon::parse($expected[$field])->format('Y-m-d H:i:s.u');
                        $actual[$field] = Carbon::parse($actual[$field])->format('Y-m-d H:i:s.u');
                    }
                }
                if ($actual !== $expected) {
                    throw new RuntimeException('Privacy conversion changed retained metadata unexpectedly.');
                }
            }
        });
        foreach ($files as $file) {
            if (! hash_equals($this->hash($public, $file), $this->hash($private, $file)) || ! $public->delete($file)) {
                throw new RuntimeException('Verified public-copy cleanup failed; rerun is safe.');
            }
        }
        if ($public->allFiles($directory) !== []) {
            throw new RuntimeException('Public counterparts remain; privacy conversion is incomplete.');
        }
    }

    private function hash(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Cannot verify retained document bytes.');
        }
        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
