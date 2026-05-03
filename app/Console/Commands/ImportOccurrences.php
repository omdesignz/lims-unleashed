<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\LazyCollection;
use App\Jobs\ImportOccurrencesChunk;

class ImportOccurrences extends Command
{
    protected $signature = 'import:occurrences {file} {--chunk=1000}';
    protected $description = 'Import occurrences from a CSV file';

    public function handle()
    {
        $file = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        $this->info("Starting import...");

        $rows = LazyCollection::make(function () use ($file) {
            $handle = fopen($file, 'r');
            // Optionally skip header row
            fgetcsv($handle);
            while (($line = fgetcsv($handle)) !== false) {
                yield $line;
            }
            fclose($handle);
        });

        $jobs = [];
        foreach ($rows->chunk($chunkSize) as $chunk) {
            $jobs[] = new ImportOccurrencesChunk($chunk->all());
        }

        Bus::batch($jobs)
            ->then(fn() => $this->info('Import completed!'))
            ->catch(fn($e) => $this->error('Import failed: ' . $e->getMessage()))
            ->dispatch();

        $this->info('Import jobs dispatched!');
        return 0;
    }
}
