<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class InventoryCategoryTypeConcurrencyTest extends IsolatedPostgresTestCase
{
    #[DataProvider('orderings')]
    public function test_first_use_and_reclassification_serialize_on_the_category_row(bool $useFirst, bool $commit): void
    {
        $lab = VAPLab::factory()->create();
        $category = ItemCategory::query()->create(['name' => 'Unused material', 'code' => 'MAT']);
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/category-type-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $db = Illuminate\Support\Facades\DB::connection();
            if (! $app->environment('testing') || $db->getDatabaseName() !== 'lims_unleashed_test' || $db->getConfig('search_path') !== $argv[1]) {
                throw new RuntimeException('Category concurrency requires its isolated test schema.');
            }
            $pid = $db->selectOne('SELECT pg_backend_pid() AS pid')->pid;
            $db->beforeExecuting(function (string $query) use ($argv, $pid): void {
                if (str_starts_with($query, 'insert into "i_items"') || str_starts_with($query, 'update "item_categories"')) {
                    file_put_contents($argv[2].'/boundary', (string) $pid);
                }
            });
            try {
                $db->transaction(function () use ($db, $argv): void {
                    if ($argv[5] === 'use') {
                        $db->table('i_items')->insert(['name' => 'Concurrent item', 'lab_id' => (int) $argv[3], 'category_id' => (int) $argv[4]]);
                    } else {
                        $db->table('item_categories')->where('id', (int) $argv[4])->update(['inventory_type' => 'equipment']);
                    }
                });
                echo '200';
            } catch (Illuminate\Database\QueryException $exception) {
                if ($exception->getCode() !== '23514') throw $exception;
                echo '409';
            }
            PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
            'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
            'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array'];
        $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, $barrier, (string) $lab->id, (string) $category->id,
            $useFirst ? 'classify' : 'use'], base_path(), $environment, timeout: 30);
        try {
            DB::beginTransaction();
            DB::table('item_categories')->where('id', $category->id)->lockForUpdate()->first();
            $process->start();
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'/boundary') && microtime(true) < $deadline && $process->isRunning()) {
                usleep(10000);
            }
            $this->assertFileExists($barrier.'/boundary', $process->getErrorOutput().$process->getOutput());
            $pid = (int) file_get_contents($barrier.'/boundary');
            $waiting = false;
            do {
                DB::select('SELECT pg_stat_clear_snapshot()');
                $waiting = DB::table('pg_stat_activity')->where('pid', $pid)->where('wait_event_type', 'Lock')->exists();
                if (! $waiting) {
                    usleep(10000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'The competing operation must actually wait on the category lock.');
            if ($useFirst) {
                DB::table('i_items')->insert(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'First item']);
            } else {
                DB::table('item_categories')->where('id', $category->id)->update(['inventory_type' => 'equipment']);
            }
            $commit ? DB::commit() : DB::rollBack();
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
            $this->assertSame($useFirst && $commit ? '409' : '200', trim($process->getOutput()));
            $this->assertSame($useFirst && $commit || ! $useFirst && ! $commit ? 'material' : 'equipment',
                DB::table('item_categories')->where('id', $category->id)->value('inventory_type'));
            $this->assertSame($useFirst && ! $commit ? 0 : 1, DB::table('inventory_category_usage')->count());
            $this->assertSame($useFirst && ! $commit ? 0 : 1, DB::table('i_items')->count());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($process->isRunning()) {
                $process->stop();
            }
            foreach (glob($barrier.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($barrier);
        }
    }

    /** @return array<string,array{bool,bool}> */
    public static function orderings(): array
    {
        return ['use commits' => [true, true], 'use rolls back' => [true, false],
            'classification commits' => [false, true], 'classification rolls back' => [false, false]];
    }
}
