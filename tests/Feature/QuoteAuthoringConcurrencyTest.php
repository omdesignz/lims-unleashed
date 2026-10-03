<?php

namespace Tests\Feature;

use App\Actions\SaveQuote;
use App\Actions\SignQuoteRevision;
use App\Actions\UpdateQuoteItem;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Models\ISOActivityLog;
use App\Models\Parameter;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\DocumentSignature;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class QuoteAuthoringConcurrencyTest extends IsolatedPostgresTestCase
{
    /** @return array{VAPLab, list<User>, array<string, mixed>, list<CollectionProduct>} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $users = [];
        for ($index = 0; $index < 4; $index++) {
            $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
            foreach (['add_quotes', 'edit_quotes', 'view_quotes', 'delete_quotes', 'add_invoices'] as $permission) {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
            $users[] = $user;
        }
        $customer = Customer::create(['name' => 'Native quote customer']);
        $site = Warehouse::create(['name' => 'Native quote site', 'customer_id' => $customer->id]);
        $catalog = Parameter::create(['name' => 'Native agreed service', 'price' => '999.00', 'charge_tax' => false]);
        $sources = [];
        $lines = [];
        for ($index = 0; $index < 4; $index++) {
            $source = CollectionProduct::create(['customer_id' => $customer->id, 'warehouse_id' => $site->id]);
            VAPSampleEntry::factory()->make(['lab_id' => $lab->id, 'customer_id' => $customer->id,
                'warehouse_id' => $site->id, 'collection_product_id' => $source->id])->saveQuietly();
            $sources[] = $source;
            $lines[] = ['catalog_type' => 'parameter', 'item_id' => $catalog->id, 'collection_product_id' => $source->id,
                'unit_id' => null, 'qty' => '1.00', 'agreed_unit_price' => '5.00', 'discount_mode' => 'fixed', 'discount_value' => '0.00'];
        }
        Notification::fake();

        return [$lab, $users, ['customer_id' => $customer->id, 'warehouse_id' => $site->id,
            'use_matrix_price' => false, 'is_service' => false, 'obs' => 'Native draft', 'items' => $lines], $sources];
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function saveOperation(User $user, VAPLab $lab, array $attributes, ?Quote $quote = null): array
    {
        return ['action' => 'save', 'user_id' => $user->id, 'lab_id' => $lab->id, 'attributes' => $attributes, 'id' => $quote?->id];
    }

    /** @return list<array<string, mixed>> */
    private function signedPayloads(Quote $quote): array
    {
        $history = $quote->activities()->where('event', 'signed_revision')->orderBy('id')->get();
        $payloads = [];
        $previous = null;
        foreach ($history as $audit) {
            $payload = json_decode($audit->properties->get('signed_payload'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($audit->properties->get('signature'), app(DocumentSignature::class)->sign($audit->properties->get('signed_payload')));
            $this->assertSame($audit->properties->get('previous_signature'), $payload['previous_signature']);
            if ($previous !== null) {
                $this->assertSame($previous->properties->get('signature'), $payload['previous_signature']);
                $this->assertSame(end($payloads)['snapshot'], $payload['previous_snapshot']);
            }
            $payloads[] = $payload;
            $previous = $audit;
        }
        $this->assertCount(count($payloads), array_unique(array_column($payloads, 'revision_id')));
        $this->assertSame($history->last()->properties->get('signature'), $quote->fresh()->unique_hash);
        $this->assertSame(end($payloads)['snapshot'], app(SignQuoteRevision::class)->snapshot($quote->fresh(), $quote->items()->orderBy('id')->get()));

        return $payloads;
    }

    public function test_four_creators_reserve_shared_sources_for_exactly_one_quote(): void
    {
        [$lab, $users, $payload, $sources] = $this->fixture();
        $operations = [];
        foreach ($users as $index => $user) {
            $attributes = $payload;
            if ($index % 2 === 1) {
                $attributes['items'] = array_reverse($attributes['items']);
            }
            $operations[] = $this->saveOperation($user, $lab, $attributes);
        }
        $outcomes = $this->compete($lab, $operations);
        $statuses = array_column($outcomes, 'status');
        sort($statuses);
        $this->assertSame([200, 422, 422, 422], $statuses);
        $quote = Quote::sole();
        $winner = array_search(200, array_column($outcomes, 'status'), true);
        $this->assertSame($users[$winner]->id, $quote->user_id);
        $this->assertSame($lab->id, $quote->lab_id);
        $this->assertSame(1, (int) $quote->seq);
        $this->assertSame('20.00', $quote->total);
        $this->assertSame(4, QuoteItem::withTrashed()->count());
        $this->assertCount(1, $this->signedPayloads($quote));
        $this->assertSame(1, ISOActivityLog::withoutGlobalScopes()->where('event', 'authored')->count());
        foreach ($sources as $source) {
            $this->assertSame($quote->id, $source->fresh()->quote_id);
            $this->assertTrue((bool) $source->fresh()->quoted);
            $this->assertSame(1, $quote->items()->where('itemable_id', $source->id)->count());
        }
    }

    public function test_competing_draft_updates_retain_every_revision_in_serial_order(): void
    {
        [$lab, $users, $payload, $sources] = $this->fixture();
        $quote = app(SaveQuote::class)->execute($users[0]->id, $lab->id, $payload);
        $identity = Arr::only($quote->getAttributes(), ['quote_no', 'seq', 'quote_month', 'user_id']);
        $initialLines = $quote->items()->orderBy('id')->get()->mapWithKeys(fn (QuoteItem $line): array => [$line->id => Arr::except($line->getAttributes(), ['updated_at', 'deleted_at'])])->all();
        $operations = [];
        foreach ($users as $index => $user) {
            $attributes = $payload;
            $attributes['description'] = 'Revision by '.$user->id;
            foreach ($attributes['items'] as &$line) {
                $line['agreed_unit_price'] = ($index + 6).'.00';
            }
            unset($line);
            $operations[] = $this->saveOperation($user, $lab, $attributes, $quote);
        }
        $outcomes = $this->compete($lab, $operations);
        $this->assertSame([200, 200, 200, 200], array_column($outcomes, 'status'));
        $this->assertSame($identity, Arr::only($quote->fresh()->getAttributes(), array_keys($identity)));
        $payloads = $this->signedPayloads($quote);
        $this->assertCount(5, $payloads);
        $this->assertSame(20, $quote->items()->withTrashed()->count());
        $this->assertSame(4, $quote->items()->count());
        $this->assertSame(5, ISOActivityLog::withoutGlobalScopes()->where('event', 'authored')->count());
        foreach ($quote->activities()->where('event', 'signed_revision')->orderBy('id')->skip(1)->get() as $audit) {
            $signed = json_decode($audit->properties->get('signed_payload'), true, flags: JSON_THROW_ON_ERROR);
            $index = array_search((int) $audit->causer_id, array_map(fn (User $user): int => $user->id, $users), true);
            $this->assertNotFalse($index);
            $this->assertSame('Revision by '.$audit->causer_id, $signed['snapshot']['quote']['description']);
            $this->assertSame(($index + 6).'.00', json_decode($signed['snapshot']['lines'][0]['extra_data'], true)['agreed_unit_price']);
        }
        foreach ($initialLines as $id => $line) {
            $retained = QuoteItem::withTrashed()->findOrFail($id);
            $this->assertTrue($retained->trashed());
            $this->assertSame($line, Arr::except($retained->getAttributes(), ['updated_at', 'deleted_at']));
        }
        foreach ($sources as $source) {
            $this->assertSame($quote->id, $source->fresh()->quote_id);
        }
    }

    public function test_draft_edit_and_invoice_issuance_have_one_coherent_linearization(): void
    {
        [$lab, $users, $payload] = $this->fixture();
        $quote = app(SaveQuote::class)->execute($users[0]->id, $lab->id, $payload);
        $category = InvoiceCategory::create(['code' => 'FT', 'description' => 'Invoice']);
        $payload['items'][0]['agreed_unit_price'] = '9.00';
        $outcomes = $this->compete($lab, [
            $this->saveOperation($users[1], $lab, $payload, $quote),
            ['action' => 'issue', 'user_id' => $users[2]->id, 'lab_id' => $lab->id, 'id' => $quote->id, 'attributes' => ['type_id' => $category->id]],
        ]);
        $this->assertSame(200, $outcomes[1]['status']);
        $this->assertContains($outcomes[0]['status'], [200, 422]);
        $invoice = Invoice::sole();
        $this->assertSame($invoice->id, $quote->fresh()->invoice_id);
        $this->assertSame($outcomes[0]['status'] === 200 ? '24.00' : '20.00', $invoice->total);
        $this->assertSame($quote->fresh()->total, $invoice->total);
        $this->assertCount($outcomes[0]['status'] === 200 ? 2 : 1, $this->signedPayloads($quote));
        $this->assertSame($outcomes[0]['status'] === 200 ? 8 : 4, $quote->items()->withTrashed()->count());
        foreach ($quote->items()->orderBy('id')->get()->values() as $index => $line) {
            $issued = $invoice->items()->orderBy('id')->get()[$index];
            $this->assertSame($line->extra_data->all(), $issued->extra_data->all());
            $this->assertSame($line->unit_price, $issued->unit_price);
            $this->assertSame($line->total, $issued->total);
        }
    }

    public function test_draft_edit_and_archive_preserve_the_winning_evidence(): void
    {
        [$lab, $users, $payload, $sources] = $this->fixture();
        $quote = app(SaveQuote::class)->execute($users[0]->id, $lab->id, $payload);
        $payload['items'][0]['agreed_unit_price'] = '9.00';
        $outcomes = $this->compete($lab, [
            $this->saveOperation($users[1], $lab, $payload, $quote),
            ['action' => 'archive', 'user_id' => $users[2]->id, 'lab_id' => $lab->id, 'id' => $quote->id, 'attributes' => []],
        ]);
        $this->assertSame(200, $outcomes[1]['status']);
        $this->assertContains($outcomes[0]['status'], [200, 404]);
        $this->assertTrue($quote->fresh()->trashed());
        $this->assertSame($outcomes[0]['status'] === 200 ? '24.00' : '20.00', $quote->fresh()->total);
        $this->assertCount($outcomes[0]['status'] === 200 ? 2 : 1, $this->signedPayloads($quote));
        foreach ($sources as $source) {
            $this->assertSame($quote->id, $source->fresh()->quote_id);
            $this->assertTrue((bool) $source->fresh()->quoted);
        }
    }

    public static function rollbackOperations(): array
    {
        return [['create'], ['revise'], ['correct']];
    }

    #[DataProvider('rollbackOperations')]
    public function test_real_root_rollback_restores_signature_graph_and_source_reservations(string $operation): void
    {
        [$lab, $users, $payload, $sources] = $this->fixture();
        $quote = $operation === 'create' ? null : app(SaveQuote::class)->execute($users[0]->id, $lab->id, $payload);
        $tables = ['quotes' => (new Quote)->getTable(), 'lines' => (new QuoteItem)->getTable(),
            'sources' => (new CollectionProduct)->getTable(), 'history' => (new ISOActivityLog)->getTable(), 'counters' => 'sequence_counters'];
        $before = collect($tables)->map(fn (string $table): array => DB::table($table)->orderBy($table === 'sequence_counters' ? 'scope_hash' : 'id')->get()->all())->all();
        config(['database.connections.quote_observer' => config('database.connections.pgsql')]);
        $observer = DB::connection('quote_observer');
        $this->assertSame($this->schema, $observer->getConfig('search_path'));
        try {
            DB::beginTransaction();
            if ($operation === 'correct') {
                app(UpdateQuoteItem::class)->execute($users[1]->id, $lab->id, $quote->items()->firstOrFail()->id, ['obs' => 'Provisional correction']);
            } else {
                $payload['items'][0]['agreed_unit_price'] = '9.00';
                app(SaveQuote::class)->execute($users[1]->id, $lab->id, $payload, $quote?->id);
            }
            $this->assertSame(1, DB::transactionLevel());
            $this->assertSame($quote === null ? 1 : 2, ISOActivityLog::withoutGlobalScopes()->where('event', 'signed_revision')->count());
            foreach ($tables as $key => $table) {
                $this->assertEquals($before[$key], $observer->table($table)->orderBy($key === 'counters' ? 'scope_hash' : 'id')->get()->all());
            }
            DB::rollBack();
            $this->assertSame(0, DB::transactionLevel());
            foreach ($tables as $key => $table) {
                $this->assertEquals($before[$key], DB::table($table)->orderBy($key === 'counters' ? 'scope_hash' : 'id')->get()->all());
                $this->assertEquals($before[$key], $observer->table($table)->orderBy($key === 'counters' ? 'scope_hash' : 'id')->get()->all());
            }
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('quote_observer');
            config(['database.connections.quote_observer' => null]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     * @return list<array<string, mixed>>
     */
    private function compete(VAPLab $lab, array $operations): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/quote-native-'.bin2hex(random_bytes(8));
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
            throw new RuntimeException('Quote authoring concurrency requires its dedicated test schema.');
        }
        Illuminate\Support\Facades\Notification::fake();
        $connection->beforeExecuting(function (string $query) use ($argv): void {
            if (str_starts_with($query, 'select * from "labs"') && str_contains($query, 'for update')) {
                touch($argv[3].'/boundary-'.$argv[4]);
            }
        });
        $operation = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
        try {
            $result = match ($operation['action']) {
                'save' => $app->make(App\Actions\SaveQuote::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['attributes'], $operation['id'] ?? null),
                'correct' => $app->make(App\Actions\UpdateQuoteItem::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['id'], $operation['attributes']),
                'issue' => $app->make(App\Actions\IssueBillingSourceInvoice::class)->execute($operation['user_id'], $operation['lab_id'],
                    'quote', $operation['id'], $operation['attributes']),
                'archive' => $app->make(App\Actions\SetBillingDocumentsArchived::class)->execute($operation['user_id'], $operation['lab_id'],
                    App\Models\Quote::class, [$operation['id']], true),
            };
            echo json_encode(['status' => 200, 'result' => $result instanceof Illuminate\Database\Eloquent\Model ? $result->id : $result], JSON_THROW_ON_ERROR);
        } catch (Illuminate\Auth\Access\AuthorizationException) {
            echo json_encode(['status' => 403]);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException) {
            echo json_encode(['status' => 404]);
        } catch (Illuminate\Validation\ValidationException) {
            echo json_encode(['status' => 422]);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            echo json_encode(['status' => $exception->getStatusCode()]);
        }
        PHP;
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DATABASE_URL' => '', 'DB_SCHEMA' => $this->schema,
            'DB_HOST' => $connection->getConfig('host'), 'DB_PORT' => (string) $connection->getConfig('port'),
            'DB_DATABASE' => $connection->getDatabaseName(), 'DB_USERNAME' => $connection->getConfig('username'),
            'DB_PASSWORD' => $connection->getConfig('password') ?? '', 'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'array'];
        try {
            DB::beginTransaction();
            $holdingLock = true;
            DB::table('labs')->where('id', $lab->id)->lockForUpdate()->first();
            foreach ($operations as $index => $operation) {
                $process = new Process([PHP_BINARY, '-r', $worker, $this->schema, json_encode($operation, JSON_THROW_ON_ERROR), $barrier, (string) $index],
                    base_path(), $environment, timeout: 30);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'/boundary-*') ?: []) < count($operations) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertCount(count($operations), glob($barrier.'/boundary-*') ?: [], collect($processes)
                ->map(fn (Process $process): string => $process->getErrorOutput().$process->getOutput())->implode(' | '));
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning(), $process->getErrorOutput().$process->getOutput());
                $this->assertSame('', trim($process->getOutput()));
            }
            DB::commit();
            $holdingLock = false;
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
            }
            foreach ($processes as $process) {
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
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
