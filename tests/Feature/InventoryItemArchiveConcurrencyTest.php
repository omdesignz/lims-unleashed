<?php

namespace Tests\Feature;

use App\Actions\SetInventoryItemsArchived;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class InventoryItemArchiveConcurrencyTest extends IsolatedPostgresTestCase
{
    #[DataProvider('waitingChanges')]
    public function test_waiting_reversed_batch_uses_fresh_authority_and_replays_archival(string $change, bool $commit): void
    {
        [$lab, $user, $first, $second] = $this->fixture();
        $barrier = sys_get_temp_dir().'/catalogue-archive-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $db = Illuminate\Support\Facades\DB::connection();
            if (! $app->environment('testing') || $db->getDatabaseName() !== 'lims_unleashed_test' || $db->getConfig('search_path') !== $argv[1]) {
                throw new RuntimeException('Catalogue concurrency requires its isolated test schema.');
            }
            $pid = $db->selectOne('SELECT pg_backend_pid() AS pid')->pid;
            $db->beforeExecuting(function (string $query) use ($argv, $pid): void {
                if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                    file_put_contents($argv[2].'/boundary', (string) $pid);
                }
            });
            try {
                $changed = $app->make(App\Actions\SetInventoryItemsArchived::class)->execute((int) $argv[4], (int) $argv[3], [(int) $argv[6], (int) $argv[5]], true);
                echo json_encode(['status' => 200, 'changed' => $changed], JSON_THROW_ON_ERROR);
            } catch (Illuminate\Auth\Access\AuthorizationException $exception) {
                echo json_encode(['status' => 403], JSON_THROW_ON_ERROR);
            } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                echo json_encode(['status' => $exception->getStatusCode()], JSON_THROW_ON_ERROR);
            }
            PHP;
        $connection = DB::connection();
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
            'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
            'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, $barrier, (string) $lab->id, (string) $user->id,
            (string) $first->id, (string) $second->id], base_path(), $environment, timeout: 30);
        try {
            DB::beginTransaction();
            DB::table('labs')->where('id', $lab->id)->lockForUpdate()->first();
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
            $this->assertTrue($waiting, 'The lifecycle worker must actually wait on the lab-first boundary.');
            match ($change) {
                'permission' => DB::table('model_has_permissions')->where('model_id', $user->id)->where('model_type', $user->getMorphClass())->delete(),
                'membership' => DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete(),
                'archive' => app(SetInventoryItemsArchived::class)->execute($user->id, $lab->id, [$first->id, $second->id], true),
            };
            $commit ? DB::commit() : DB::rollBack();
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
            $outcome = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            $denied = $commit && $change !== 'archive';
            $this->assertSame($denied ? 403 : 200, $outcome['status']);
            if (! $denied) {
                $this->assertSame($commit && $change === 'archive' ? 0 : 2, $outcome['changed']);
            }
            $this->assertSame($denied ? 0 : 2, InventoryItem::onlyTrashed()->count());
            $this->assertSame($denied ? 0 : 2, DB::table('activity_log')->where('log_name', 'inventory_item')->count());
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

    #[DataProvider('outerOutcomes')]
    public function test_real_outer_transaction_controls_item_and_audit_visibility(bool $archived, bool $commit): void
    {
        [$lab, $user, $first, $second] = $this->fixture();
        if (! $archived) {
            $first->delete();
            $second->delete();
        }
        config(['database.connections.catalogue_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('catalogue_observer');
        $before = $observer->table('i_items')->orderBy('id')->get()->all();
        try {
            $this->assertNotSame(DB::selectOne('SELECT pg_backend_pid() AS pid')->pid, $observer->selectOne('SELECT pg_backend_pid() AS pid')->pid);
            DB::beginTransaction();
            $this->assertSame(2, app(SetInventoryItemsArchived::class)->execute($user->id, $lab->id, [$second->id, $first->id], $archived));
            $this->assertEquals($before, $observer->table('i_items')->orderBy('id')->get()->all());
            $this->assertSame(0, $observer->table('activity_log')->where('log_name', 'inventory_item')->count());
            $commit ? DB::commit() : DB::rollBack();
            $this->assertSame($commit ? 2 : 0, $observer->table('activity_log')->where('log_name', 'inventory_item')->count());
            $this->assertSame(($commit ? $archived : ! $archived) ? 2 : 0, $observer->table('i_items')->whereNotNull('deleted_at')->count());
            if (! $commit) {
                $this->assertEquals($before, $observer->table('i_items')->orderBy('id')->get()->all());
            }
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('catalogue_observer');
            config(['database.connections.catalogue_observer' => null]);
        }
    }

    public static function waitingChanges(): array
    {
        $cases = [];
        foreach (['archive', 'permission', 'membership'] as $change) {
            foreach ([true, false] as $commit) {
                $cases[$change.($commit ? ' commit' : ' rollback')] = [$change, $commit];
            }
        }

        return $cases;
    }

    public static function outerOutcomes(): array
    {
        return ['archive commit' => [true, true], 'archive rollback' => [true, false],
            'restore commit' => [false, true], 'restore rollback' => [false, false]];
    }

    /** @return array{VAPLab,User,InventoryItem,InventoryItem} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->givePermissionTo(Permission::findOrCreate('delete_iitems', 'web'), Permission::findOrCreate('restore_iitems', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = ItemCategory::query()->create(['name' => 'Material', 'inventory_type' => 'material']);
        $first = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Item A']);
        $second = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Item B']);

        return [$lab, $user, $first, $second];
    }
}
