<?php

namespace Tests\Feature;

use App\Actions\CreateReceipt;
use App\Actions\IssueBillingSourceInvoice;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\ISOActivityLog;
use App\Models\PaymentCategory;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;
use Tests\IsolatedPostgresTestCase;

class FinancialWorkflowConcurrencyTest extends IsolatedPostgresTestCase
{
    /** @return array{VAPLab, list<User>, Warehouse, InvoiceCategory, PaymentCategory} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $users = [];
        for ($index = 0; $index < 4; $index++) {
            $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
            DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
            foreach (['add_invoices', 'edit_invoices', 'add_receipts', 'view_quotes', 'view_import_certificates', 'view_export_certificates',
                'delete_invoices', 'restore_invoices', 'delete_receipts', 'restore_receipts', 'delete_credit_notes', 'restore_credit_notes',
                'delete_quotes', 'restore_quotes', 'delete_import_certificates', 'restore_import_certificates', 'delete_export_certificates', 'restore_export_certificates'] as $permission) {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
            $users[] = $user;
        }
        $site = Warehouse::create(['name' => 'Native site', 'customer_id' => Customer::create(['name' => 'Shared native customer'])->id]);
        $category = InvoiceCategory::create(['code' => 'FT', 'description' => 'Invoice']);
        $payment = PaymentCategory::create(['name' => 'Bank']);
        Notification::fake();

        return [$lab, $users, $site, $category, $payment];
    }

    /** @return array<string, array{string, class-string<Model>}> */
    public static function sources(): array
    {
        return ['quote' => ['quote', Quote::class], 'import' => ['import_certificate', ImportCertificate::class],
            'export' => ['export_certificate', ExportCertificate::class]];
    }

    /** @return list<array{class-string<Model>}> */
    public static function documents(): array
    {
        return array_map(fn (string $class): array => [$class], [Invoice::class, CreditNote::class, Receipt::class,
            Quote::class, ImportCertificate::class, ExportCertificate::class]);
    }

    private function document(string $class, VAPLab $lab, User $user, Warehouse $site, InvoiceCategory $category): Model
    {
        $record = new $class;
        $record->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'date' => now()->toDateString(), ...match ($class) {
            Invoice::class => ['inv_no' => 'NATIVE-'.Str::uuid(), 'invoice_month' => now()->format('Y'), 'type_id' => $category->id,
                'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'total' => '20.00', 'amount_due' => '20.00',
                'status_code' => Invoice::STATUS_CODE_NORMAL, 'unique_hash' => 'native-retained-signature'],
            CreditNote::class => ['note_no' => 'NATIVE-'.Str::uuid(), 'note_month' => now()->format('Y'), 'reason' => 'Correction',
                'customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            Receipt::class => ['rec_no' => 'NATIVE-'.Str::uuid(), 'rec_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            Quote::class => ['quote_no' => 'NATIVE-'.Str::uuid(), 'quote_month' => now()->format('Y'), 'customer_id' => $site->customer_id,
                'warehouse_id' => $site->id, 'total' => '20.00', 'sub_total' => '20.00'],
            ImportCertificate::class => ['cert_no' => 'NATIVE-'.Str::uuid(), 'importer_id' => $site->customer_id, 'importer_warehouse_id' => $site->id],
            default => ['cert_no' => 'NATIVE-'.Str::uuid(), 'exporter_id' => $site->customer_id, 'exporter_warehouse_id' => $site->id],
        }])->saveQuietly();
        if ($record instanceof Quote) {
            QuoteItem::create(['quote_id' => $record->id, 'item_description' => 'Service', 'qty' => 1, 'unit_price' => '20.00', 'total' => '20.00']);
        }

        return $record->fresh();
    }

    /** @return array<string, mixed> */
    private function issuance(int $userId, VAPLab $lab, Model $source, string $type, Warehouse $site, InvoiceCategory $category): array
    {
        return ['action' => 'issue', 'user_id' => $userId, 'lab_id' => $lab->id, 'type' => $type, 'source_id' => $source->id,
            'attributes' => ['type_id' => $category->id, 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
                'total' => '20.00', 'sub_total' => '20.00'],
            'items' => [['item_description' => 'Service', 'qty' => 1, 'unit_price' => '20.00', 'total' => '20.00']]];
    }

    /** @return array<string, mixed> */
    private function payment(int $userId, VAPLab $lab, Invoice $invoice, PaymentCategory $method, ?string $amount = null): array
    {
        return ['action' => $amount === null ? 'pay_full' : 'pay_partial', 'user_id' => $userId, 'lab_id' => $lab->id,
            'invoice_id' => $invoice->id, 'payment_id' => $method->id, 'amount' => $amount,
            'attributes' => ['customer_id' => $invoice->customer_id, 'warehouse_id' => $invoice->warehouse_id]];
    }

    /**
     * @param  list<array<string, mixed>>  $operations
     * @return list<array<string, mixed>>
     */
    private function compete(VAPLab $lab, array $operations): array
    {
        $connection = DB::connection();
        $barrier = sys_get_temp_dir().'/finance-native-'.bin2hex(random_bytes(8));
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
            throw new RuntimeException('Financial concurrency requires its dedicated test schema.');
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
                'issue' => $app->make(App\Actions\IssueBillingSourceInvoice::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['type'], $operation['source_id'], $operation['attributes'], $operation['items']),
                'pay_full' => $app->make(App\Actions\RecordInvoicePayment::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['invoice_id'], $operation['payment_id']),
                'pay_partial' => $app->make(App\Actions\CreateReceipt::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['attributes'], [['invoice_id' => $operation['invoice_id'], 'payment_id' => $operation['payment_id'], 'paid_amount' => $operation['amount']]]),
                'pay_batch' => $app->make(App\Actions\CreateReceipt::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['attributes'], $operation['items']),
                'archive' => $app->make(App\Actions\SetBillingDocumentsArchived::class)->execute($operation['user_id'], $operation['lab_id'],
                    $operation['class'], $operation['ids'], $operation['archived']),
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
            DB::commit();
            $holdingLock = false;
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
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

    #[DataProvider('sources')]
    public function test_four_distinct_operators_issue_one_invoice_per_source(string $type, string $class): void
    {
        [$lab, $users, $site, $category] = $this->fixture();
        $source = $this->document($class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, array_map(fn (User $user): array => $this->issuance($user->id, $lab, $source, $type, $site, $category), $users));
        $statuses = array_column($outcomes, 'status');
        sort($statuses);
        $this->assertSame([200, 409, 409, 409], $statuses);
        $invoice = Invoice::sole();
        $this->assertSame($invoice->id, $source->fresh()->invoice_id);
        $this->assertSame($lab->id, $invoice->lab_id);
        $this->assertSame('20.00', $invoice->amount_due);
        $this->assertNotEmpty($invoice->unique_hash);
        $this->assertSame(1, InvoiceItem::count());
        $this->assertSame(1, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
    }

    public function test_competing_full_and_partial_payments_preserve_exact_current_balance(): void
    {
        [$lab, $users, $site, $category, $method] = $this->fixture();
        $invoice = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, array_map(fn (User $user): array => $this->payment($user->id, $lab, $invoice, $method), $users));
        $statuses = array_column($outcomes, 'status');
        sort($statuses);
        $this->assertSame([200, 422, 422, 422], $statuses);
        $this->assertSame('0.00', $invoice->fresh()->amount_due);
        $this->assertSame(1, Receipt::count());
        $this->assertSame(1, InvoiceReceipt::count());
        $this->assertSame('20.00', InvoiceReceipt::sole()->paid_amount);

        $second = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, array_map(fn (User $user): array => $this->payment($user->id, $lab, $second, $method, '5.00'), $users));
        $this->assertSame([200, 200, 200, 200], array_column($outcomes, 'status'));
        $this->assertSame('0.00', $second->fresh()->amount_due);
        $this->assertTrue($second->fresh()->status);
        $this->assertSame('20.00', number_format((float) InvoiceReceipt::where('invoice_id', $second->id)->sum('paid_amount'), 2, '.', ''));
        $this->assertSame(5, Receipt::count());
        $this->assertSame(5, Receipt::distinct()->count('rec_no'));
        $this->assertSame(5, Receipt::whereNotNull('unique_hash')->count());
    }

    public function test_concurrent_overpayments_and_partial_vs_full_cannot_double_allocate(): void
    {
        [$lab, $users, $site, $category, $method] = $this->fixture();
        $invoice = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, array_map(fn (User $user): array => $this->payment($user->id, $lab, $invoice, $method, '15.00'), $users));
        $statuses = array_column($outcomes, 'status');
        sort($statuses);
        $this->assertSame([200, 422, 422, 422], $statuses);
        $this->assertSame('5.00', $invoice->fresh()->amount_due);
        $this->assertSame(1, Receipt::count());
        $this->assertFalse($invoice->fresh()->status);
        $second = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, [$this->payment($users[0]->id, $lab, $second, $method, '5.00'), $this->payment($users[1]->id, $lab, $second, $method)]);
        $this->assertContains(200, array_column($outcomes, 'status'));
        $this->assertSame('0.00', $second->fresh()->amount_due);
        $this->assertTrue($second->fresh()->status);
        $this->assertSame('20.00', number_format((float) InvoiceReceipt::where('invoice_id', $second->id)->sum('paid_amount'), 2, '.', ''));
    }

    public function test_reverse_order_multi_invoice_receipts_settle_each_invoice_once(): void
    {
        [$lab, $users, $site, $category, $method] = $this->fixture();
        $one = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $two = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $operations = [];
        foreach ($users as $index => $user) {
            $invoiceIds = $index % 2 === 0 ? [$one->id, $two->id] : [$two->id, $one->id];
            $operations[] = ['action' => 'pay_batch', 'user_id' => $user->id, 'lab_id' => $lab->id,
                'attributes' => ['customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
                'items' => array_map(fn (int $id): array => ['invoice_id' => $id, 'payment_id' => $method->id, 'paid_amount' => '5.00'], $invoiceIds)];
        }
        $outcomes = $this->compete($lab, $operations);
        $this->assertSame([200, 200, 200, 200], array_column($outcomes, 'status'));
        foreach ([$one, $two] as $invoice) {
            $this->assertSame('0.00', $invoice->fresh()->amount_due);
            $this->assertTrue($invoice->fresh()->status);
            $this->assertSame('20.00', number_format((float) InvoiceReceipt::where('invoice_id', $invoice->id)->sum('paid_amount'), 2, '.', ''));
            $this->assertSame(4, InvoiceReceipt::where('invoice_id', $invoice->id)->count());
        }
        $this->assertSame(4, Receipt::count());
        $this->assertSame(8, InvoiceReceipt::count());
        $this->assertSame(4, Receipt::distinct()->count('rec_no'));
        $this->assertSame(4, Receipt::whereNotNull('unique_hash')->count());
        $this->assertSame(4, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
    }

    #[DataProvider('sources')]
    public function test_source_archive_and_issuance_race_preserves_links_and_rejects_archived_source(string $type, string $class): void
    {
        [$lab, $users, $site, $category] = $this->fixture();
        $source = $this->document($class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, [$this->issuance($users[0]->id, $lab, $source, $type, $site, $category),
            ['action' => 'archive', 'class' => $class, 'ids' => [$source->id], 'archived' => true, 'user_id' => $users[1]->id, 'lab_id' => $lab->id]]);
        $this->assertSame(200, $outcomes[1]['status']);
        $this->assertContains($outcomes[0]['status'], [200, 404]);
        $this->assertTrue($source->fresh()->trashed());
        if ($outcomes[0]['status'] === 200) {
            $this->assertSame(Invoice::sole()->id, $source->fresh()->invoice_id);
            $this->assertSame(1, InvoiceItem::count());
        } else {
            $this->assertSame(0, Invoice::count());
            $this->assertNull($source->fresh()->invoice_id);
        }
    }

    public function test_invoice_archive_and_payment_race_preserves_settlement_evidence(): void
    {
        [$lab, $users, $site, $category, $method] = $this->fixture();
        $invoice = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $outcomes = $this->compete($lab, [$this->payment($users[0]->id, $lab, $invoice, $method),
            ['action' => 'archive', 'class' => Invoice::class, 'ids' => [$invoice->id], 'archived' => true, 'user_id' => $users[1]->id, 'lab_id' => $lab->id]]);
        $this->assertSame(200, $outcomes[1]['status']);
        $this->assertContains($outcomes[0]['status'], [200, 404]);
        $this->assertTrue($invoice->fresh()->trashed());
        $this->assertSame($outcomes[0]['status'] === 200 ? '0.00' : '20.00', $invoice->fresh()->amount_due);
        $this->assertSame($outcomes[0]['status'] === 200 ? 1 : 0, Receipt::count());
        $this->assertSame(Receipt::count(), InvoiceReceipt::count());
    }

    #[DataProvider('documents')]
    public function test_reverse_order_concurrent_bulk_archive_and_restore_are_idempotent(string $class): void
    {
        [$lab, $users, $site, $category] = $this->fixture();
        $one = $this->document($class, $lab, $users[0], $site, $category);
        $two = $this->document($class, $lab, $users[0], $site, $category);
        $before = [Arr::except($one->getAttributes(), ['updated_at', 'deleted_at']), Arr::except($two->getAttributes(), ['updated_at', 'deleted_at'])];
        foreach ([true, false] as $archived) {
            $operations = [];
            foreach ($users as $index => $user) {
                $operations[] = ['action' => 'archive', 'class' => $class, 'ids' => $index % 2 ? [$two->id, $one->id] : [$one->id, $two->id],
                    'archived' => $archived, 'user_id' => $user->id, 'lab_id' => $lab->id];
            }
            $outcomes = $this->compete($lab, $operations);
            $this->assertSame([200, 200, 200, 200], array_column($outcomes, 'status'));
            $changes = array_column($outcomes, 'result');
            sort($changes);
            $this->assertSame([0, 0, 0, 2], $changes);
            $this->assertSame($archived, $one->fresh()->trashed());
            $this->assertSame($archived, $two->fresh()->trashed());
            $this->assertSame($before, [Arr::except($one->fresh()->getAttributes(), ['updated_at', 'deleted_at']), Arr::except($two->fresh()->getAttributes(), ['updated_at', 'deleted_at'])]);
            $this->assertSame(2, ISOActivityLog::withoutGlobalScopes()->where('event', $archived ? 'archived' : 'restored')->where('properties->billing_archive', true)->count());
        }
    }

    #[DataProvider('sources')]
    public function test_real_root_rollback_removes_issued_invoice_lines_link_and_audit(string $type, string $class): void
    {
        [$lab, $users, $site, $category] = $this->fixture();
        $source = $this->document($class, $lab, $users[0], $site, $category);
        $data = $this->issuance($users[0]->id, $lab, $source, $type, $site, $category);
        $before = $source->getAttributes();
        DB::beginTransaction();
        app(IssueBillingSourceInvoice::class)->execute($data['user_id'], $data['lab_id'], $type, $source->id, $data['attributes'], $data['items']);
        $this->assertSame(1, Invoice::count());
        DB::rollBack();
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, InvoiceItem::count());
        $this->assertSame($before, $source->fresh()->getAttributes());
        $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_real_root_rollback_preserves_balances_and_removes_signed_receipt_and_allocations(): void
    {
        [$lab, $users, $site, $category, $method] = $this->fixture();
        $invoice = $this->document(Invoice::class, $lab, $users[0], $site, $category);
        $before = $invoice->getAttributes();
        DB::beginTransaction();
        app(CreateReceipt::class)->execute($users[0]->id, $lab->id, ['customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
            [['invoice_id' => $invoice->id, 'payment_id' => $method->id, 'paid_amount' => '20.00']]);
        $this->assertSame('0.00', $invoice->fresh()->amount_due);
        DB::rollBack();
        $this->assertSame($before, $invoice->fresh()->getAttributes());
        $this->assertSame(0, Receipt::count());
        $this->assertSame(0, InvoiceReceipt::count());
        $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }
}
