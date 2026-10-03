<?php

namespace Tests\Feature;

use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\VAPLab;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class TradeCertificateNumberingMigrationTest extends IsolatedPostgresTestCase
{
    public function test_migration_replay_preserves_legacy_identifiers_and_history(): void
    {
        $lab = VAPLab::factory()->create();
        $before = [];
        foreach ([ImportCertificate::class, ExportCertificate::class] as $class) {
            $record = new $class;
            $record->forceFill(['lab_id' => $lab->id, 'cert_no' => '20250101010101', 'obs' => 'Historical', 'deleted_at' => now()])->saveQuietly();
            $before[$class] = Arr::except($record->fresh()->getAttributes(), ['certificate_year', 'seq']);
        }
        $migration = require database_path('migrations/2026_10_01_204026_add_scoped_numbering_to_trade_certificates.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('import_certificates', 'seq'));
        $this->assertFalse(Schema::hasColumn('export_certificates', 'certificate_year'));
        $migration->up();
        foreach ($before as $class => $attributes) {
            $record = $class::withTrashed()->sole();
            $this->assertNull($record->seq);
            $this->assertNull($record->certificate_year);
            $this->assertSame($attributes, Arr::except($record->getAttributes(), ['certificate_year', 'seq']));
        }
    }

    public function test_rollback_refuses_numbered_archived_evidence_in_either_table(): void
    {
        $lab = VAPLab::factory()->create();
        $migration = require database_path('migrations/2026_10_01_204026_add_scoped_numbering_to_trade_certificates.php');
        foreach ([ImportCertificate::class, ExportCertificate::class] as $class) {
            DB::beginTransaction();
            try {
                $record = new $class;
                $record->forceFill(['lab_id' => $lab->id])->save();
                $number = $record->cert_no;
                $record->delete();
                try {
                    $migration->down();
                    $this->fail('Numbering evidence was removed.');
                } catch (LogicException) {
                    $this->assertTrue(Schema::hasColumn('import_certificates', 'seq'));
                    $this->assertTrue(Schema::hasColumn('export_certificates', 'seq'));
                    $this->assertSame($number, $class::withTrashed()->sole()->cert_no);
                }
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_real_outer_rollback_restores_counter_and_new_identifiers_are_immutable(): void
    {
        $lab = VAPLab::factory()->create();
        DB::beginTransaction();
        $record = new ImportCertificate;
        $record->lab_id = $lab->id;
        $record->save();
        $number = $record->cert_no;
        DB::rollBack();
        $this->assertSame(0, ImportCertificate::count());
        $this->assertSame(0, DB::table('sequence_counters')->where('table_name', 'import_certificates')->count());
        $record = new ImportCertificate;
        $record->forceFill(['lab_id' => $lab->id, 'cert_no' => 'Forged', 'certificate_year' => 1900, 'seq' => 999])->save();
        $this->assertSame($number, $record->cert_no);
        foreach (['cert_no' => 'Changed', 'certificate_year' => 1900, 'seq' => 999] as $field => $value) {
            try {
                $record->fresh()->forceFill([$field => $value])->save();
                $this->fail('An issued identifier was changed.');
            } catch (LogicException) {
                $this->assertSame($number, $record->fresh()->cert_no);
            }
        }
    }

    public static function kinds(): array
    {
        return ['import' => [ImportCertificate::class], 'export' => [ExportCertificate::class]];
    }

    #[DataProvider('kinds')]
    public function test_four_fresh_workers_allocate_unique_numbers_behind_a_held_counter(string $class): void
    {
        $lab = VAPLab::factory()->create();
        $baseline = new $class;
        $baseline->forceFill(['lab_id' => $lab->id])->save();
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/trade-number-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        $holdingLock = false;
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Certificate numbering requires its dedicated test schema.');
        }
        Illuminate\Support\Facades\Notification::fake();
        $connection->beforeExecuting(function (string $query) use ($argv): void {
            if (str_starts_with($query, 'insert into "sequence_counters"')) {
                touch($argv[4].'/boundary-'.$argv[5]);
            }
        });
        $record = $connection->transaction(function () use ($argv) {
            $class = $argv[2];
            $record = new $class;
            $record->forceFill(['lab_id' => (int) $argv[3]])->save();
            return $record;
        });
        echo json_encode(['seq' => (int) $record->seq, 'cert_no' => $record->cert_no], JSON_THROW_ON_ERROR);
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
            'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
            'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            $holdingLock = true;
            DB::table('sequence_counters')->where('table_name', (new $class)->getTable())->lockForUpdate()->first();
            for ($index = 0; $index < 4; $index++) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, $class, (string) $lab->id, $barrier, (string) $index], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/boundary-*') ?: []) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/boundary-*') ?: [], collect($processes)->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            DB::commit();
            $holdingLock = false;
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }
            $sequences = array_column($results, 'seq');
            sort($sequences);
            $this->assertSame([2, 3, 4, 5], $sequences);
            $this->assertSame(4, count(array_unique(array_column($results, 'cert_no'))));
            $this->assertSame(5, $class::distinct()->count('cert_no'));
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
        }
    }
}
