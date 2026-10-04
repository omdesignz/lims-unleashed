<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventoryTransaction;
use App\Models\InventoryUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class InventoryReceivingConcurrencyTest extends IsolatedPostgresTestCase
{
    /** @return array<string, array{string}> */
    public static function replayQuantities(): array
    {
        return ['partial' => ['0.1250'], 'complete' => ['1.0000']];
    }

    #[DataProvider('replayQuantities')]
    public function test_simultaneous_identical_receipts_publish_one_stock_effect(string $quantity): void
    {
        $fixture = $this->fixture();
        $payload = $this->payload($fixture['line'], $quantity);

        $this->assertSame([200, 200], $this->compete($fixture['order'], $fixture['user'], [$payload, $payload]));
        $this->assertSame($quantity, $fixture['line']->fresh()->received_qty);
        $this->assertSame($quantity, Inventory::query()->sole()->qty_available);
        $this->assertSame(1, InventoryTransaction::query()->count());
        $this->assertCount(1, $fixture['order']->fresh()->receipt_history);
        $this->assertSame($payload['request_id'], $fixture['order']->fresh()->receipt_history[0]['request_id']);
    }

    public function test_competing_distinct_receipts_cannot_exceed_ordered_quantity(): void
    {
        $fixture = $this->fixture();
        $results = $this->compete($fixture['order'], $fixture['user'], [
            $this->payload($fixture['line'], '0.7500'),
            $this->payload($fixture['line'], '0.7500'),
        ]);
        sort($results);

        $this->assertSame([200, 422], $results);
        $this->assertSame('0.7500', $fixture['line']->fresh()->received_qty);
        $this->assertSame('0.7500', Inventory::query()->sole()->qty_available);
        $this->assertSame(1, InventoryTransaction::query()->count());
        $this->assertCount(1, $fixture['order']->fresh()->receipt_history);
    }

    public function test_waiting_receipt_observes_archived_destination_before_writing(): void
    {
        $fixture = $this->fixture();
        $results = $this->compete($fixture['order'], $fixture['user'], [
            $this->payload($fixture['line'], '0.1250'),
        ], fn () => $fixture['warehouse']->delete());

        $this->assertSame([422], $results);
        $this->assertSame('0.0000', $fixture['line']->fresh()->received_qty);
        $this->assertSame(0, Inventory::query()->count());
        $this->assertSame(0, InventoryTransaction::query()->count());
        $this->assertNull($fixture['order']->fresh()->receipt_history);
    }

    #[DataProvider('authorityRevocations')]
    public function test_waiting_receipt_rechecks_actor_authority_after_the_boundary_lock(string $revocation): void
    {
        $fixture = $this->fixture();
        $fixture['user']->removeRole('admin');
        $fixture['user']->givePermissionTo(Permission::findOrCreate('edit_iorders', 'web'));
        $results = $this->compete($fixture['order'], $fixture['user'], [$this->payload($fixture['line'], '0.1250')], function () use ($fixture, $revocation): void {
            match ($revocation) {
                'permission' => $fixture['user']->revokePermissionTo('edit_iorders'),
                'membership' => DB::table('lab_user')->where('lab_id', $fixture['order']->lab_id)->where('user_id', $fixture['user']->id)->delete(),
                'activation' => User::whereKey($fixture['user']->id)->update(['is_active' => false]),
                'verification' => User::whereKey($fixture['user']->id)->update(['email_verified_at' => null]),
            };
        });
        $this->assertSame([403], $results);
        $this->assertSame('0.0000', $fixture['line']->fresh()->received_qty);
        $this->assertSame(0, Inventory::query()->count());
        $this->assertSame(0, InventoryTransaction::query()->count());
        $this->assertNull($fixture['order']->fresh()->receipt_history);
    }

    public static function authorityRevocations(): array
    {
        return ['permission' => ['permission'], 'membership' => ['membership'],
            'activation' => ['activation'], 'verification' => ['verification']];
    }

    /** @return array{order: InventoryOrder, line: InventoryOrderDetail, user: User, warehouse: InventoryItemWarehouse} */
    private function fixture(): array
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $unit = InventoryUnit::query()->create(['code' => 'receipt-unit', 'description' => 'Grams']);
        $item = InventoryItem::query()->create(['lab_id' => $lab->id, 'name' => 'Receipt material', 'code' => 'RECEIPT', 'unit_id' => $unit->id]);
        $warehouse = InventoryItemWarehouse::query()->create(['lab_id' => $lab->id, 'name' => 'Receipt warehouse']);
        $supplier = InventoryItemSupplier::query()->create(['name' => 'Receipt supplier', 'currency' => 'AOA']);
        $order = InventoryOrder::query()->create([
            'lab_id' => $lab->id, 'date' => now()->toDateString(), 'user_id' => $user->id,
            'supplier_id' => $supplier->id, 'order_year' => now()->format('Y'), 'status' => 'ORDERED', 'currency' => 'AOA',
        ]);
        $line = InventoryOrderDetail::query()->create([
            'order_id' => $order->id, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id,
            'qty' => '1.0000', 'received_qty' => '0.0000', 'unit_price' => '10.0000', 'status' => 'ORDERED', 'currency' => 'AOA',
        ]);

        return compact('order', 'line', 'user', 'warehouse');
    }

    /** @return array{request_id: string, items: list<array{id: int, received_qty: string, unit_price: string}>, receive_date: string} */
    private function payload(InventoryOrderDetail $line, string $quantity): array
    {
        return ['request_id' => (string) Str::uuid(), 'items' => [
            ['id' => $line->id, 'received_qty' => $quantity, 'unit_price' => '10.0000'],
        ], 'receive_date' => now()->toDateString()];
    }

    /** @param list<array<string, mixed>> $payloads @return list<int> */
    private function compete(InventoryOrder $order, User $user, array $payloads, ?callable $beforeRelease = null): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/receipt-race-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test' || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Receiving concurrency requires its dedicated test schema.');
        }
        $payload = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        $user = App\Models\User::query()->findOrFail($argv[3]);
        $order = App\Models\InventoryOrder::query()->findOrFail($argv[4]);
        $request = Illuminate\Http\Request::create('/receiving-test', 'POST', $payload);
        $request->setUserResolver(fn () => $user);
        $session = $app->make('session')->driver();
        $session->start();
        $session->put('active_lab_id', $order->lab_id);
        $request->setLaravelSession($session);
        $app->instance('request', $request);
        Illuminate\Support\Facades\Auth::setUser($user);
        $pid = $connection->selectOne('select pg_backend_pid() as pid')->pid;
        $connection->beforeExecuting(function (string $query) use ($argv, $pid): void {
            if (str_contains($query, 'from "labs"') && str_contains($query, 'for update')) {
                file_put_contents($argv[5].'/boundary-'.$argv[6], (string) $pid);
            }
        });
        try {
            $app->make(App\Http\Controllers\VAPInventoryOrderController::class)->receive($request, $order);
            $status = 200;
        } catch (Illuminate\Validation\ValidationException) {
            $status = 422;
        } catch (Illuminate\Auth\Access\AuthorizationException) {
            $status = 403;
        }
        echo json_encode(['status' => $status]);
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'), 'DB_DATABASE' => $connection->getDatabaseName(),
            'DB_USERNAME' => $connection->getConfig('username'), 'DB_PASSWORD' => $connection->getConfig('password') ?? '',
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            VAPLab::query()->whereKey($order->lab_id)->lockForUpdate()->firstOrFail();
            InventoryOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            foreach ($payloads as $index => $payload) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, json_encode($payload, JSON_THROW_ON_ERROR),
                    (string) $user->id, (string) $order->id, $barrier, (string) $index], base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/boundary-*') ?: []) < count($payloads) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(count($payloads), glob($barrier.'/boundary-*') ?: [], collect($processes)->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            $workerIds = array_map(fn (string $path): int => (int) file_get_contents($path), glob($barrier.'/boundary-*') ?: []);
            $deadline = microtime(true) + 15;
            do {
                DB::select('select pg_stat_clear_snapshot()');
                $waiting = DB::table('pg_stat_activity')->whereIn('pid', $workerIds)->where('wait_event_type', 'Lock')->pluck('pid')->all();
                if (count($waiting) === count($payloads)) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertCount(count($payloads), $waiting, 'Every receipt must actually wait on the held PostgreSQL laboratory boundary lock.');
            $beforeRelease?->__invoke();
            DB::commit();
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR)['status'];
            }

            return $results;
        } finally {
            while (DB::transactionLevel() > 0) {
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
