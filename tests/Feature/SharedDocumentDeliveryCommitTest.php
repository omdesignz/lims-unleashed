<?php

namespace Tests\Feature;

use App\Actions\QueueSharedDocumentDelivery;
use App\Jobs\SendSharedDocumentEmail;
use App\Models\Customer;
use App\Models\DocumentDelivery;
use App\Models\Invoice;
use App\Models\ISOActivityLog;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class SharedDocumentDeliveryCommitTest extends IsolatedPostgresTestCase
{
    /** @return array{VAPLab, User, Invoice} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $user->givePermissionTo(Permission::findOrCreate('view_invoices', 'web'));
        $customer = Customer::create(['name' => 'Delivery commit customer']);
        $site = Warehouse::create(['name' => 'Delivery commit site', 'customer_id' => $customer->id]);
        $invoice = new Invoice;
        $invoice->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'customer_id' => $customer->id,
            'warehouse_id' => $site->id, 'inv_no' => 'DEL-COMMIT', 'invoice_month' => now()->format('Y')])->saveQuietly();

        return [$lab, $user, $invoice];
    }

    /** @return array<string, mixed> */
    private function data(Invoice $invoice): array
    {
        return ['document_type' => 'invoice', 'document_id' => $invoice->id, 'recipients' => ['recipient@example.test'],
            'cc' => [], 'subject' => 'Private document', 'message' => 'Local test only.'];
    }

    public function test_nested_preparation_queues_only_after_real_root_commit_and_discards_rollback(): void
    {
        [$lab, $user, $invoice] = $this->fixture();
        $action = app(QueueSharedDocumentDelivery::class);
        DB::beginTransaction();
        $action->execute($user->id, $lab->id, $this->data($invoice));
        $this->assertSame(0, DB::table('jobs')->count());
        DB::rollBack();
        $this->assertSame(0, DocumentDelivery::count());
        $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'delivery_queued')->count());
        $this->assertSame(0, DB::table('jobs')->count());

        DB::beginTransaction();
        DB::beginTransaction();
        $delivery = $action->execute($user->id, $lab->id, $this->data($invoice));
        DB::commit();
        $this->assertSame(0, DB::table('jobs')->count());
        DB::commit();
        $queued = DB::table('jobs')->sole();
        $this->assertSame('mail', $queued->queue);
        $job = unserialize(json_decode($queued->payload, true, flags: JSON_THROW_ON_ERROR)['data']['command']);
        $this->assertInstanceOf(SendSharedDocumentEmail::class, $job);
        $this->assertSame($delivery->id, $job->delivery->id);
        $this->assertSame($lab->id, $job->identity['lab_id']);
        $this->assertSame(1, ISOActivityLog::withoutGlobalScopes()->where('event', 'delivery_queued')->count());

        $realRegistry = app(ShareableDocumentRegistry::class);
        $renderer = Mockery::mock(ShareableDocumentRegistry::class);
        $renderer->shouldReceive('definition')->andReturnUsing(fn (string $type): ?array => $realRegistry->definition($type));
        $renderer->shouldReceive('render')->once()->andReturn(['content' => '%PDF-local-test', 'filename' => 'test.pdf',
            'number' => 'TEST', 'label' => 'Document', 'url' => '/documents', 'default_recipients' => []]);
        $this->app->instance(ShareableDocumentRegistry::class, $renderer);
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->andReturn(0);
        $this->app->instance(NotificationTemplateService::class, $templates);
        $transportBoundaryChecked = false;
        Event::listen(MessageSending::class, function () use ($delivery, &$transportBoundaryChecked): void {
            $this->assertSame(0, DB::connection()->transactionLevel());
            $independent = DB::build(config('database.connections.pgsql'));
            try {
                $state = $independent->table('document_deliveries')->where('id', $delivery->id)->first();
                $this->assertSame('sending', $state->status);
                $this->assertNotNull($state->attempt_id);
                $transportBoundaryChecked = true;
            } finally {
                $independent->disconnect();
            }
        });
        $workerJob = Queue::connection('database')->pop('mail');
        $this->assertInstanceOf(DatabaseJob::class, $workerJob);
        $workerJob->fire();
        $this->assertTrue($workerJob->isDeleted());
        $this->assertTrue($transportBoundaryChecked);
        $this->assertSame('sent', $delivery->fresh()->status);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_four_fresh_workers_claim_one_transport_and_replays_do_not_resend(): void
    {
        [$lab, $user, $invoice] = $this->fixture();
        $delivery = DocumentDelivery::create([...$this->data($invoice), 'sender_id' => $user->id]);
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/document-delivery-'.bin2hex(random_bytes(8));
        mkdir($barrier, 0700);
        $processes = [];
        $holdingLock = false;
        $worker = <<<'PHP'
        require getcwd().'/vendor/autoload.php';
        $app = require getcwd().'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $connection = Illuminate\Support\Facades\DB::connection();
        if (! $app->environment('testing') || $connection->getDatabaseName() !== 'lims_unleashed_test'
            || $connection->getConfig('search_path') !== $argv[1]) {
            throw new RuntimeException('Document concurrency requires its private test schema.');
        }
        $renderer = Mockery::mock(App\Support\ShareableDocumentRegistry::class);
        $renderer->shouldReceive('render')->andReturn([
            'content' => '%PDF-local-test', 'filename' => 'test.pdf', 'number' => 'TEST',
            'label' => 'Document', 'url' => '/documents', 'default_recipients' => [],
        ]);
        $templates = Mockery::mock(App\Support\NotificationTemplateService::class);
        $templates->shouldReceive('notify')->andReturn(0);
        $connection->beforeExecuting(function (string $query) use ($argv): void {
            if (str_contains($query, 'from "document_deliveries"') && str_contains($query, 'for update')) {
                touch($argv[3].'/claim-'.$argv[4]);
            }
        });
        $job = new App\Jobs\SendSharedDocumentEmail(App\Models\DocumentDelivery::findOrFail($argv[2]));
        $job->handle($renderer, $templates);
        $job->handle($renderer, $templates);
        echo json_encode(['sends' => Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport()->messages()->count()], JSON_THROW_ON_ERROR);
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '',
            'DB_SCHEMA' => $this->schema, 'DB_HOST' => $connection->getConfig('host'),
            'DB_PORT' => (string) $connection->getConfig('port'), 'DB_DATABASE' => $connection->getDatabaseName(),
            'DB_USERNAME' => $connection->getConfig('username'), 'DB_PASSWORD' => $connection->getConfig('password') ?? '',
            'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            $holdingLock = true;
            DB::table('document_deliveries')->where('id', $delivery->id)->lockForUpdate()->first();
            for ($index = 0; $index < 4; $index++) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, (string) $delivery->id, $barrier, (string) $index],
                    base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/claim-*') ?: []) < 4 && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(4, glob($barrier.'/claim-*') ?: [], collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            DB::commit();
            $holdingLock = false;
            $sends = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $sends[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR)['sends'];
            }
            sort($sends);
            $this->assertSame([0, 0, 0, 1], $sends);
            $this->assertSame('sent', $delivery->fresh()->status);
            $this->assertNotNull($delivery->fresh()->attempt_id);
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

    public function test_ownership_migration_replays_empty_schema_and_refuses_retained_evidence(): void
    {
        $migration = require database_path('migrations/2026_10_01_195241_enforce_laboratory_ownership_of_document_deliveries.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('document_deliveries', 'lab_id'));
        $legacy = DB::table('document_deliveries')->insertGetId(['document_type' => 'invoice', 'document_id' => 987,
            'recipients' => '[]', 'subject' => 'Retained evidence', 'message' => '', 'status' => 'queued']);
        try {
            $migration->up();
            $this->fail('Retained delivery was silently assigned or removed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('retained delivery', $exception->getMessage());
            $this->assertSame('Retained evidence', DB::table('document_deliveries')->where('id', $legacy)->value('subject'));
            $this->assertFalse(Schema::hasColumn('document_deliveries', 'lab_id'));
        }
        DB::table('document_deliveries')->where('id', $legacy)->delete();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('document_deliveries', 'lab_id'));
        [$lab, $user, $invoice] = $this->fixture();
        $delivery = DocumentDelivery::create([...$this->data($invoice), 'sender_id' => $user->id]);
        try {
            $migration->down();
            $this->fail('Rollback removed retained delivery ownership.');
        } catch (RuntimeException) {
            $this->assertSame($lab->id, $delivery->fresh()->lab_id);
            $this->assertTrue(Schema::hasColumn('document_deliveries', 'attempt_id'));
        }
        foreach (['lab_id' => 987654321, 'lab_id_null' => null] as $case => $value) {
            try {
                DB::transaction(fn () => DB::table('document_deliveries')->where('id', $delivery->id)->update(['lab_id' => $value]));
                $this->fail('PostgreSQL accepted invalid delivery ownership.');
            } catch (QueryException $exception) {
                $this->assertSame($case === 'lab_id' ? '23503' : '23502', $exception->errorInfo[0]);
            }
        }
    }

    public function test_root_commit_enqueue_failure_is_recorded_without_claiming_transport(): void
    {
        [$lab, $user, $invoice] = $this->fixture();
        $templates = Mockery::mock(NotificationTemplateService::class);
        $templates->shouldReceive('notify')->once()->withArgs(fn ($recipients, string $key): bool => $key === 'documents.share_failed')->andReturn(0);
        $this->app->instance(NotificationTemplateService::class, $templates);
        DB::connection()->beforeExecuting(function (string $query): void {
            if (str_starts_with($query, 'insert into "jobs"')) {
                throw new RuntimeException('Queue broker unavailable');
            }
        });
        DB::beginTransaction();
        $delivery = app(QueueSharedDocumentDelivery::class)->execute($user->id, $lab->id, $this->data($invoice));
        $this->assertSame(0, DB::table('jobs')->count());
        try {
            DB::commit();
            $this->fail('Queue insertion failure was hidden.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Queue broker unavailable', $exception->getMessage());
            $this->assertSame(0, DB::connection()->transactionLevel());
            $this->assertSame('failed', $delivery->fresh()->status);
            $this->assertNull($delivery->fresh()->attempt_id);
            $this->assertSame('Queue broker unavailable', $delivery->fresh()->failure_message);
            $this->assertSame(0, DB::table('jobs')->count());
            $this->assertSame(1, ISOActivityLog::withoutGlobalScopes()->where('event', 'delivery_queued')->count());
        }
    }
}
