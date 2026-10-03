<?php

namespace Tests\Feature;

use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LaboratorySampleNumberingConcurrencyTest extends TestCase
{
    public function test_parallel_postgresql_intakes_receive_unique_numbers_within_each_lab_and_year(): void
    {
        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());

        do {
            $year = (string) random_int(8100, 8999);
        } while (DB::table('sample_entries')->where('sample_year', $year)->exists());

        $labs = VAPLab::factory()->count(2)->create();
        $hashes = $labs->mapWithKeys(fn (VAPLab $lab): array => [
            $lab->id => hash('sha256', json_encode(['sample_entries', 'seq', [
                'lab_id' => (string) $lab->id,
                'sample_year' => $year,
            ]], JSON_THROW_ON_ERROR)),
        ]);

        $environment = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
            'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
            'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'DATABASE_URL' => '',
            'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
        ];
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if (! $app->environment('testing') || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'lims_unleashed_test') {
            throw new RuntimeException('Concurrency test requires its dedicated test database.');
        }
        $sample = Illuminate\Support\Facades\DB::transaction(function () use ($argv) {
            $sample = App\Models\VAPSampleEntry::create([
                'name' => 'Concurrent numbering test', 'sample_year' => $argv[1], 'sample_type' => 'AGUA',
                'lab_id' => $argv[2],
            ]);
            usleep(100000);
            return $sample;
        });
        echo json_encode(['lab_id' => (int) $sample->lab_id, 'seq' => $sample->seq, 'code' => $sample->code], JSON_THROW_ON_ERROR);
        PHP;
        $processes = [];
        try {
            foreach ($labs as $lab) {
                for ($index = 0; $index < 4; $index++) {
                    $process = new Process([PHP_BINARY, '-r', $worker, $year, (string) $lab->id], base_path(), $environment, timeout: 20);
                    $process->start();
                    $processes[] = $process;
                }
            }

            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }

            foreach ($labs as $lab) {
                $numbers = collect($results)->where('lab_id', $lab->id)->pluck('seq')->sort()->values()->all();
                $this->assertSame(range(1, 4), $numbers);
                $this->assertSame(4, (int) DB::table('sequence_counters')->where('scope_hash', $hashes[$lab->id])->value('last_value'));
            }
            $this->assertCount(8, array_unique(array_column($results, 'code')));
            $this->assertSame(8, DB::table('sample_entries')->where('sample_year', $year)->whereIn('lab_id', $labs->modelKeys())->count());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::table('sample_entries')->where('sample_year', $year)->whereIn('lab_id', $labs->modelKeys())
                ->where('name', 'Concurrent numbering test')->delete();
            DB::table('sequence_counters')->whereIn('scope_hash', $hashes->all())->delete();
            DB::table('labs')->whereIn('id', $labs->modelKeys())->delete();
        }
    }
}
