<?php

namespace App\Console\Commands;

use App\Jobs\ImportOccurrencesChunk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\LazyCollection;

class ImportOccurrences extends Command
{
    protected $signature = 'import:occurrences {file} {--chunk=1000}';

    protected $description = 'Importar ocorrências a partir de um ficheiro CSV';

    public function handle()
    {
        $file = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');

        if (! file_exists($file)) {
            $this->error("Ficheiro não encontrado: $file");

            return 1;
        }

        $this->info('A iniciar a importação...');

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
            ->then(fn () => $this->info('Importação concluída.'))
            ->catch(fn ($e) => $this->error('Falha na importação: '.$e->getMessage()))
            ->dispatch();

        $this->info('Tarefas de importação enviadas.');

        return 0;
    }
}
