<?php

namespace Tests\Feature;

use App\Models\IntegrationConnector;
use App\Models\IntegrationDelivery;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class IntegrationDeliveryConcurrencyTest extends TestCase
{
    public function test_parallel_retries_claim_one_delivery_and_queue_one_job(): void
    {
        $connection = DB::connection();
        $this->assertSame('pgsql', $connection->getDriverName());
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());

        $lab = null;
        $connector = null;
        $delivery = null;
        $processes = [];
        $holdingLock = false;
        $barrier = sys_get_temp_dir().'/integration-delivery-'.Str::uuid();
        mkdir($barrier, 0700);

        try {
            $lab = VAPLab::factory()->create();
            $connector = IntegrationConnector::factory()->create(['lab_id' => $lab->id]);
            $delivery = IntegrationDelivery::factory()->for($connector, 'connector')->create([
                'event_type' => 'lims.connector.test',
                'status' => 'failed',
            ]);

            $environment = [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '',
                'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
                'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
                'DB_PASSWORD' => $connection->getConfig('password') ?? '',
                'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
            ];
            $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            if (! $app->environment('testing') || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'lims_unleashed_test') {
                throw new RuntimeException('Integration concurrency requires its dedicated test database.');
            }
            Illuminate\Support\Facades\Queue::fake();
            Illuminate\Support\Facades\DB::connection()->beforeExecuting(function (string $query) use ($argv): void {
                if (str_contains($query, 'from "integration_deliveries"') && str_contains($query, 'for update')) {
                    touch($argv[3].'/write-boundary-'.$argv[4]);
                }
            });
            $queued = $app->make(App\Actions\RetryIntegrationDelivery::class)->execute((int) $argv[1], (int) $argv[2]);
            echo json_encode([
                'queued' => $queued,
                'jobs' => Illuminate\Support\Facades\Queue::pushed(App\Jobs\DeliverIntegrationWebhook::class)->count(),
            ], JSON_THROW_ON_ERROR);
            PHP;

            DB::beginTransaction();
            $holdingLock = true;
            DB::table('integration_deliveries')->where('id', $delivery->id)->lockForUpdate()->first();

            for ($index = 0; $index < 4; $index++) {
                $process = new Process([
                    PHP_BINARY, '-r', $worker, (string) $delivery->id, (string) $lab->id, $barrier, (string) $index,
                ], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }

            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/write-boundary-*') ?: []) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/write-boundary-*') ?: [], 'All workers must reach the locked delivery. '.collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));

            DB::commit();
            $holdingLock = false;

            $outcomes = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $outcomes[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }

            $claims = array_column($outcomes, 'queued');
            sort($claims);
            $this->assertSame([false, false, false, true], $claims);
            $this->assertSame(1, array_sum(array_column($outcomes, 'jobs')));
            $this->assertSame('pending', $delivery->fresh()->status);
            $this->assertSame(0, $delivery->fresh()->attempts);
        } finally {
            if ($holdingLock) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($barrier.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($barrier);

            if ($delivery) {
                DB::table('integration_deliveries')->where('id', $delivery->id)->delete();
            }
            if ($connector) {
                DB::table('integration_connectors')->where('id', $connector->id)->delete();
            }
            if ($lab) {
                DB::table('labs')->where('id', $lab->id)->delete();
            }
        }
    }
}
