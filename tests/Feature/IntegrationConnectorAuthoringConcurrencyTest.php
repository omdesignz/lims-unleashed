<?php

namespace Tests\Feature;

use App\Actions\SaveIntegrationConnector;
use App\Models\IntegrationConnector;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Permission;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class IntegrationConnectorAuthoringConcurrencyTest extends IsolatedPostgresTestCase
{
    #[DataProvider('waitingChanges')]
    public function test_waiting_authoring_uses_fresh_equipment_connector_and_authority(string $change, bool $commit): void
    {
        [$lab, $user, $item, $replacement] = $this->fixture();
        $creating = $change === 'equipment_archived';
        $connector = $creating ? null : IntegrationConnector::factory()->create(['lab_id' => $lab->id, 'inventory_item_id' => $item->id]);
        if ($change === 'archived_link_reassigned') {
            $item->delete();
        }
        $payload = ['name' => 'Waiting connector', 'direction' => 'inbound', 'adapter' => 'rest_json',
            'status' => 'draft', 'inventory_item_id' => $item->id];
        $barrier = sys_get_temp_dir().'/connector-authoring-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $worker = <<<'PHP'
            require getcwd().'/vendor/autoload.php';
            $app = require getcwd().'/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $db = Illuminate\Support\Facades\DB::connection();
            if (! $app->environment('testing') || $db->getDatabaseName() !== 'lims_unleashed_test' || $db->getConfig('search_path') !== $argv[1]) {
                throw new RuntimeException('Connector concurrency requires its isolated test schema.');
            }
            $pid = $db->selectOne('SELECT pg_backend_pid() AS pid')->pid;
            $db->beforeExecuting(function (string $query) use ($argv, $pid): void {
                if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                    file_put_contents($argv[2].'/boundary', (string) $pid);
                }
            });
            try {
                $saved = $app->make(App\Actions\SaveIntegrationConnector::class)->execute((int) $argv[3], (int) $argv[4],
                    json_decode($argv[6], true, flags: JSON_THROW_ON_ERROR), $argv[5] === '' ? null : (int) $argv[5]);
                echo json_encode(['status' => 200, 'id' => $saved['connector']->id,
                    'token_matches' => $saved['token'] === null || $saved['connector']->fresh()->matchesIngestToken($saved['token'])], JSON_THROW_ON_ERROR);
            } catch (Illuminate\Validation\ValidationException $exception) {
                echo json_encode(['status' => 422, 'errors' => array_keys($exception->errors())], JSON_THROW_ON_ERROR);
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
            $connector ? (string) $connector->id : '', json_encode($payload, JSON_THROW_ON_ERROR)], base_path(), $environment, timeout: 30);
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
            $this->assertTrue($waiting, 'The authoring worker must actually wait on the lab-first boundary.');
            switch ($change) {
                case 'equipment_archived':
                    DB::table('i_items')->where('id', $item->id)->update(['deleted_at' => now()]);
                    break;
                case 'archived_link_reassigned':
                    DB::table('integration_connectors')->where('id', $connector->id)->update(['inventory_item_id' => $replacement->id]);
                    break;
                case 'connector_archived':
                    DB::table('integration_connectors')->where('id', $connector->id)->update(['deleted_at' => now()]);
                    break;
                case 'permission_revoked':
                    DB::table('model_has_permissions')->where('model_id', $user->id)->where('model_type', $user->getMorphClass())->delete();
                    break;
                case 'membership_removed':
                    DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
                    break;
            }
            $commit ? DB::commit() : DB::rollBack();
            $expected = $connector ? (array) DB::table('integration_connectors')->where('id', $connector->id)->first() : null;
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
            $outcome = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            $status = ! $commit ? 200 : match ($change) {
                'equipment_archived', 'archived_link_reassigned' => 422,
                'connector_archived' => 404,
                default => 403,
            };
            $this->assertSame($status, $outcome['status']);
            if ($commit) {
                $this->assertSame($expected, $connector ? (array) DB::table('integration_connectors')->where('id', $connector->id)->first() : null);
                $this->assertSame($creating ? 0 : 1, IntegrationConnector::withTrashed()->count());
                $this->assertSame(0, DB::table('integration_mappings')->count());
                $this->assertSame(0, DB::table('activity_log')->where('subject_type', (new IntegrationConnector)->getMorphClass())->count());
            } else {
                $this->assertTrue($outcome['token_matches']);
                $stored = IntegrationConnector::query()->findOrFail($outcome['id']);
                $this->assertSame($item->id, $stored->inventory_item_id);
                $this->assertSame('Waiting connector', $stored->name);
                $this->assertSame($creating ? 1 : 0, $stored->mappings()->count());
                $this->assertSame(1, DB::table('activity_log')->where('subject_type', (new IntegrationConnector)->getMorphClass())->count());
            }
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

    #[DataProvider('commitOutcomes')]
    public function test_connector_mapping_token_and_audit_follow_the_real_outer_transaction(bool $commit): void
    {
        [$lab, $user, $item] = $this->fixture();
        config(['database.connections.connector_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('connector_observer');
        try {
            $this->assertNotSame(DB::selectOne('SELECT pg_backend_pid() AS pid')->pid, $observer->selectOne('SELECT pg_backend_pid() AS pid')->pid);
            DB::beginTransaction();
            $saved = app(SaveIntegrationConnector::class)->execute($lab->id, $user->id, [
                'name' => 'Outer transaction connector', 'direction' => 'inbound', 'adapter' => 'rest_json',
                'status' => 'draft', 'inventory_item_id' => $item->id,
            ]);
            $this->assertTrue($saved['connector']->matchesIngestToken($saved['token']));
            $this->assertSame(0, $observer->table('integration_connectors')->count());
            $this->assertSame(0, $observer->table('integration_mappings')->count());
            $this->assertSame(0, $observer->table('activity_log')->where('subject_type', (new IntegrationConnector)->getMorphClass())->count());
            $commit ? DB::commit() : DB::rollBack();
            foreach (['integration_connectors', 'integration_mappings'] as $table) {
                $this->assertSame($commit ? 1 : 0, $observer->table($table)->count());
            }
            $this->assertSame($commit ? 1 : 0, $observer->table('activity_log')->where('subject_type', (new IntegrationConnector)->getMorphClass())->count());
            if ($commit) {
                $this->assertSame(hash('sha256', $saved['token']), $observer->table('integration_connectors')->where('id', $saved['connector']->id)->value('ingest_token_hash'));
            }
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('connector_observer');
            config(['database.connections.connector_observer' => null]);
        }
    }

    /** @return array<string,array{string,bool}> */
    public static function waitingChanges(): array
    {
        $cases = [];
        foreach (['equipment_archived', 'archived_link_reassigned', 'connector_archived', 'permission_revoked', 'membership_removed'] as $change) {
            foreach ([true, false] as $commit) {
                $cases[$change.($commit ? ' commits' : ' rolls back')] = [$change, $commit];
            }
        }

        return $cases;
    }

    /** @return array<string,array{bool}> */
    public static function commitOutcomes(): array
    {
        return ['commit' => [true], 'rollback' => [false]];
    }

    /** @return array{VAPLab,User,InventoryItem,InventoryItem} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->givePermissionTo(Permission::findOrCreate('edit_settings', 'web'));
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $category = ItemCategory::query()->create(['name' => 'Equipment', 'inventory_type' => 'equipment']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Equipment A']);
        $replacement = InventoryItem::query()->create(['lab_id' => $lab->id, 'category_id' => $category->id, 'name' => 'Equipment B']);

        return [$lab, $user, $item, $replacement];
    }
}
