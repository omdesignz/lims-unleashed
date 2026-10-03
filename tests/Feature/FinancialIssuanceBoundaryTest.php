<?php

namespace Tests\Feature;

use App\Actions\CreateReceipt;
use App\Actions\IssueBillingSourceInvoice;
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
use App\Support\DocumentSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FinancialIssuanceBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{VAPLab, User, Warehouse, InvoiceCategory, PaymentCategory} */
    private function fixture(): array
    {
        $lab = VAPLab::factory()->create();
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['add_invoices', 'view_quotes', 'view_import_certificates', 'view_export_certificates', 'add_receipts'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $site = Warehouse::create(['name' => 'Boundary site', 'customer_id' => Customer::create(['name' => 'Boundary customer'])->id]);
        $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['description' => 'Invoice']);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        Queue::fake();

        return [$lab, $user, $site, $category, $payment];
    }

    /** @return array<string, array{string, class-string<Model>}> */
    public static function sources(): array
    {
        return ['quote' => ['quote', Quote::class], 'import' => ['import_certificate', ImportCertificate::class],
            'export' => ['export_certificate', ExportCertificate::class]];
    }

    private function source(string $class, VAPLab $lab, User $user, Warehouse $site): Model
    {
        $record = new $class;
        $record->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'date' => now()->toDateString(), ...match ($class) {
            Quote::class => ['quote_no' => 'BOUNDARY-'.Str::uuid(), 'quote_month' => now()->format('Y'), 'customer_id' => $site->customer_id,
                'warehouse_id' => $site->id, 'total' => '20.00', 'sub_total' => '20.00'],
            ImportCertificate::class => ['cert_no' => 'BOUNDARY-'.Str::uuid(), 'importer_id' => $site->customer_id, 'importer_warehouse_id' => $site->id],
            default => ['cert_no' => 'BOUNDARY-'.Str::uuid(), 'exporter_id' => $site->customer_id, 'exporter_warehouse_id' => $site->id],
        }])->saveQuietly();
        if ($record instanceof Quote) {
            QuoteItem::create(['quote_id' => $record->id, 'item_description' => 'Service', 'qty' => 1, 'unit_price' => '20.00', 'total' => '20.00']);
        }

        return $record->fresh();
    }

    private function invoice(VAPLab $lab, User $user, Warehouse $site, InvoiceCategory $category): Invoice
    {
        $record = new Invoice;
        $record->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'customer_id' => $site->customer_id,
            'warehouse_id' => $site->id, 'type_id' => $category->id, 'inv_no' => 'BOUNDARY-'.Str::uuid(),
            'invoice_month' => now()->format('Y'), 'date' => now()->toDateString(), 'total' => '20.00', 'amount_due' => '20.00',
            'status_code' => Invoice::STATUS_CODE_NORMAL, 'unique_hash' => 'retained-issued-hash'])->saveQuietly();

        return $record->fresh();
    }

    #[DataProvider('sources')]
    public function test_late_invoice_line_source_and_audit_mutations_roll_back_entire_issuance(string $type, string $class): void
    {
        [$lab, $user, $site, $category] = $this->fixture();
        $source = $this->source($class, $lab, $user, $site);
        $before = $source->getAttributes();
        foreach (['invoice', 'line', 'remove_line', 'source', 'source_archive', 'audit_subject', 'audit_actor', 'audit_owner', 'audit_link'] as $change) {
            $event = 'eloquent.creating: '.ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (ISOActivityLog $audit) use ($change, $source, &$called): void {
                if ($audit->event !== 'issued') {
                    return;
                }
                $called = true;
                match ($change) {
                    'invoice' => DB::table('invoices')->where('id', $audit->properties->get('invoice_id'))->update(['total' => '999.00']),
                    'line' => DB::table('invoice_items')->where('invoice_id', $audit->properties->get('invoice_id'))->update(['total' => '999.00']),
                    'remove_line' => DB::table('invoice_items')->where('invoice_id', $audit->properties->get('invoice_id'))->delete(),
                    'source' => DB::table($source->getTable())->where('id', $source->id)->update(['user_id' => null]),
                    'source_archive' => DB::table($source->getTable())->where('id', $source->id)->update(['deleted_at' => now()]),
                    'audit_subject' => $audit->subject_id = null,
                    'audit_actor' => $audit->causer_id = null,
                    'audit_owner' => $audit->properties = $audit->properties->put('lab_id', 0),
                    'audit_link' => $audit->properties = $audit->properties->put('invoice_id', 0),
                };
            });
            try {
                app(IssueBillingSourceInvoice::class)->execute($user->id, $lab->id, $type, $source->id,
                    ['type_id' => $category->id, 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
                        'total' => '20.00', 'sub_total' => '20.00'], [['item_description' => 'Service', 'qty' => 1, 'unit_price' => '20.00', 'total' => '20.00']]);
                $this->fail('Late '.$change.' changed issued evidence.');
            } catch (LogicException) {
                $this->assertTrue($called);
                $this->assertSame($before, $source->fresh()->getAttributes());
                $this->assertSame(0, Invoice::count());
                $this->assertSame(0, InvoiceItem::count());
                $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_late_receipt_allocation_every_invoice_and_audit_mutations_roll_back(): void
    {
        [$lab, $user, $site, $category, $payment] = $this->fixture();
        $one = $this->invoice($lab, $user, $site, $category);
        $two = $this->invoice($lab, $user, $site, $category);
        $before = [$one->getAttributes(), $two->getAttributes()];
        foreach (['receipt', 'allocation', 'remove_allocation', 'first_balance', 'second_status', 'payment_method', 'audit_subject', 'audit_actor', 'audit_owner', 'audit_allocations'] as $change) {
            $event = 'eloquent.creating: '.ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (ISOActivityLog $audit) use ($change, $one, $two, &$called): void {
                if ($audit->event !== 'issued') {
                    return;
                }
                $called = true;
                match ($change) {
                    'receipt' => DB::table('receipts')->where('id', $audit->subject_id)->update(['user_id' => null]),
                    'allocation' => DB::table('invoice_receipt')->where('receipt_id', $audit->subject_id)->update(['paid_amount' => '99.00']),
                    'remove_allocation' => DB::table('invoice_receipt')->where('receipt_id', $audit->subject_id)->delete(),
                    'first_balance' => DB::table('invoices')->where('id', $one->id)->update(['amount_due' => '19.00']),
                    'second_status' => DB::table('invoices')->where('id', $two->id)->update(['status' => false]),
                    'payment_method' => DB::table('invoices')->where('id', $one->id)->update(['payment_method' => 'Forged']),
                    'audit_subject' => $audit->subject_id = null,
                    'audit_actor' => $audit->causer_id = null,
                    'audit_owner' => $audit->properties = $audit->properties->put('lab_id', 0),
                    'audit_allocations' => $audit->properties = $audit->properties->put('allocations', []),
                };
            });
            try {
                app(CreateReceipt::class)->execute($user->id, $lab->id, ['customer_id' => $site->customer_id, 'warehouse_id' => $site->id], [
                    ['invoice_id' => $one->id, 'payment_id' => $payment->id, 'paid_amount' => '5.00'],
                    ['invoice_id' => $two->id, 'payment_id' => $payment->id, 'paid_amount' => '20.00'],
                ]);
                $this->fail('Late '.$change.' changed payment evidence.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
                $this->assertTrue($called);
                $this->assertSame($before, [$one->fresh()->getAttributes(), $two->fresh()->getAttributes()]);
                $this->assertSame(0, Receipt::count());
                $this->assertSame(0, InvoiceReceipt::count());
                $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    #[DataProvider('sources')]
    public function test_creation_hooks_cannot_replace_intended_invoice_header_or_lines(string $type, string $class): void
    {
        [$lab, $user, $site, $category] = $this->fixture();
        $source = $this->source($class, $lab, $user, $site);
        $before = $source->getAttributes();
        foreach (['invoice_creating_total', 'invoice_creating_actor', 'invoice_creating_signature', 'invoice_created_signature', 'invoice_created_total', 'line_creating_qty', 'line_created_price', 'line_created_description'] as $change) {
            $lineChange = str_starts_with($change, 'line_');
            $event = 'eloquent.'.(str_contains($change, '_creating_') ? 'creating' : 'created').': '.($lineChange ? InvoiceItem::class : Invoice::class);
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (Model $record) use ($change, &$called): void {
                $called = true;
                match ($change) {
                    'invoice_creating_total' => $record->total = '99.00',
                    'invoice_creating_actor' => $record->user_id = null,
                    'invoice_creating_signature' => $record->unique_hash = 'forged-signature',
                    'invoice_created_signature' => DB::table($record->getTable())->where('id', $record->id)->update(['unique_hash' => 'forged-signature']),
                    'invoice_created_total' => DB::table($record->getTable())->where('id', $record->id)->update(['total' => '99.00']),
                    'line_creating_qty' => $record->qty = 9,
                    'line_created_price' => DB::table($record->getTable())->where('id', $record->id)->update(['unit_price' => '99.00']),
                    'line_created_description' => DB::table($record->getTable())->where('id', $record->id)->update(['item_description' => 'Replaced']),
                };
            });
            try {
                app(IssueBillingSourceInvoice::class)->execute($user->id, $lab->id, $type, $source->id,
                    ['type_id' => $category->id, 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
                        'total' => '20.00', 'sub_total' => '20.00'], [['item_description' => 'Service', 'qty' => 1, 'unit_price' => 20, 'total' => 20]]);
                $this->fail('Issuance accepted '.$change.'.');
            } catch (LogicException) {
                $this->assertTrue($called);
                $this->assertSame($before, $source->fresh()->getAttributes());
                $this->assertSame(0, Invoice::count());
                $this->assertSame(0, InvoiceItem::count());
                $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_receipt_creation_hooks_cannot_replace_intended_customer_actor_or_signature(): void
    {
        [$lab, $user, $site, $category, $payment] = $this->fixture();
        $invoice = $this->invoice($lab, $user, $site, $category);
        $before = $invoice->getAttributes();
        $otherCustomer = Customer::create(['name' => 'Other shared customer']);
        foreach (['customer_id', 'user_id', 'unique_hash'] as $field) {
            $event = 'eloquent.creating: '.Receipt::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (Receipt $receipt) use ($field, $otherCustomer, &$called): void {
                $called = true;
                $receipt->setAttribute($field, match ($field) {
                    'customer_id' => $otherCustomer->id,
                    'unique_hash' => 'forged-signature',
                    default => null,
                });
            });
            try {
                app(CreateReceipt::class)->execute($user->id, $lab->id,
                    ['customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
                    [['invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'paid_amount' => '5.00']]);
                $this->fail('Receipt creation accepted changed '.$field.'.');
            } catch (LogicException) {
                $this->assertTrue($called);
                $this->assertSame($before, $invoice->fresh()->getAttributes());
                $this->assertSame(0, Receipt::count());
                $this->assertSame(0, InvoiceReceipt::count());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_cash_date_initialization_rejects_veto_or_content_changes_before_signing(): void
    {
        [$lab, $user, $site] = $this->fixture();
        $cash = InvoiceCategory::firstOrCreate(['code' => 'FR'], ['description' => 'Cash invoice']);
        $invoice = $this->invoice($lab, $user, $site, $cash);
        DB::table('invoices')->where('id', $invoice->id)->update(['unique_hash' => null]);
        $before = $invoice->fresh()->getAttributes();
        $this->mock(DocumentSignature::class)->shouldNotReceive('sign');
        foreach (['veto', 'total', 'paid_date'] as $change) {
            $event = 'eloquent.updating: '.Invoice::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (Invoice $record) use ($change, &$called): ?bool {
                $called = true;
                if ($change === 'veto') {
                    return false;
                }
                $record->setAttribute($change, $change === 'total' ? '99.00' : '2000-01-01');

                return null;
            });
            try {
                Artisan::call('app:sign-invoice-with-hash', ['invoice' => $invoice->id]);
                $this->fail('Cash-date initialization accepted '.$change.'.');
            } catch (LogicException) {
                $this->assertTrue($called);
                $this->assertSame($before, $invoice->fresh()->getAttributes());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_allocation_creation_cannot_inject_parent_receipt_signature(): void
    {
        [$lab, $user, $site, $category, $payment] = $this->fixture();
        $invoice = $this->invoice($lab, $user, $site, $category);
        $before = $invoice->getAttributes();
        $event = 'eloquent.created: '.InvoiceReceipt::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        $called = false;
        $this->mock(DocumentSignature::class)->shouldNotReceive('sign');
        Event::listen($event, function (InvoiceReceipt $allocation) use (&$called): void {
            $called = true;
            DB::table('receipts')->where('id', $allocation->receipt_id)->update(['unique_hash' => 'forged-signature']);
        });
        try {
            app(CreateReceipt::class)->execute($user->id, $lab->id,
                ['customer_id' => $site->customer_id, 'warehouse_id' => $site->id],
                [['invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'paid_amount' => '5.00']]);
            $this->fail('Receipt allocation creation injected a parent signature.');
        } catch (LogicException) {
            $this->assertTrue($called);
            $this->assertSame($before, $invoice->fresh()->getAttributes());
            $this->assertSame(0, Receipt::count());
            $this->assertSame(0, InvoiceReceipt::count());
            $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public function test_archived_invoice_and_receipt_predecessors_are_used_without_rewriting_existing_signatures(): void
    {
        [$lab, $user, $site, $category] = $this->fixture();
        $previous = $this->invoice($lab, $user, $site, $category);
        $previous->delete();
        $unsigned = $this->invoice($lab, $user, $site, $category);
        DB::table('invoices')->where('id', $unsigned->id)->update(['unique_hash' => null]);
        $invoice = $this->invoice($lab, $user, $site, $category);
        DB::table('invoices')->where('id', $invoice->id)->update(['unique_hash' => null]);
        $payloads = [];
        $this->mock(DocumentSignature::class)->shouldReceive('sign')->andReturnUsing(function (string $payload) use (&$payloads): string {
            $payloads[] = $payload;

            return 'new-signed-hash';
        });
        $this->assertSame(0, Artisan::call('app:sign-invoice-with-hash', ['invoice' => $invoice->id]));
        $this->assertStringEndsWith(';retained-issued-hash', $payloads[0]);
        $this->assertSame('retained-issued-hash', $previous->fresh()->unique_hash);
        $this->assertNull($unsigned->fresh()->unique_hash);
        $this->assertSame(0, Artisan::call('app:sign-invoice-with-hash', ['invoice' => $invoice->id]));
        $this->assertCount(1, $payloads);

        $previousReceipt = new Receipt;
        $previousReceipt->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'customer_id' => $site->customer_id,
            'warehouse_id' => $site->id, 'rec_no' => 'RC-PREV', 'rec_month' => now()->format('Y'), 'unique_hash' => 'receipt-predecessor'])->saveQuietly();
        $previousReceipt->delete();
        $unsignedReceipt = $previousReceipt->replicate(['rec_no', 'unique_hash', 'deleted_at']);
        $unsignedReceipt->forceFill(['rec_no' => 'RC-UNSIGNED', 'date' => now()->toDateString()])->saveQuietly();
        $receipt = $previousReceipt->replicate(['rec_no', 'unique_hash', 'deleted_at']);
        $receipt->forceFill(['rec_no' => 'RC-NEW', 'date' => now()->toDateString()])->saveQuietly();
        $this->assertSame(0, Artisan::call('app:sign-receipt-with-hash', ['receipt' => $receipt->id]));
        $this->assertStringEndsWith(';receipt-predecessor', $payloads[1]);
        $this->assertSame('receipt-predecessor', $previousReceipt->fresh()->unique_hash);
        $this->assertNull($unsignedReceipt->fresh()->unique_hash);
        $this->assertSame(0, Artisan::call('app:sign-receipt-with-hash', ['receipt' => $receipt->id]));
        $this->assertCount(2, $payloads);
    }

    public function test_signature_predecessors_stay_in_year_and_invoice_type_group_and_cash_code_not_numeric_id(): void
    {
        [$lab, $user, $site, $category] = $this->fixture();
        $oldYear = $this->invoice($lab, $user, $site, $category);
        DB::table('invoices')->where('id', $oldYear->id)->update(['invoice_month' => '2000']);
        InvoiceCategory::create(['code' => 'BOUNDARY', 'description' => 'Category identity guard']);
        $cash = InvoiceCategory::firstOrCreate(['code' => 'FR'], ['description' => 'Cash invoice']);
        $this->assertNotSame(2, $cash->id);
        $otherType = $this->invoice($lab, $user, $site, $cash);
        $invoice = $this->invoice($lab, $user, $site, $category);
        DB::table('invoices')->where('id', $invoice->id)->update(['unique_hash' => null]);
        $payloads = [];
        $this->mock(DocumentSignature::class)->shouldReceive('sign')->andReturnUsing(function (string $payload) use (&$payloads): string {
            $payloads[] = $payload;

            return 'signature';
        });
        $this->assertSame(0, Artisan::call('app:sign-invoice-with-hash', ['invoice' => $invoice->id]));
        $this->assertStringEndsWith(';20.00;', $payloads[0]);
        $this->assertNull($invoice->fresh()->paid_date);
        DB::table('invoices')->where('id', $otherType->id)->update(['unique_hash' => null]);
        $this->assertSame(0, Artisan::call('app:sign-invoice-with-hash', ['invoice' => $otherType->id]));
        $this->assertSame(now()->toDateString(), $otherType->fresh()->paid_date);
        $oldReceipt = new Receipt;
        $oldReceipt->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'customer_id' => $site->customer_id,
            'warehouse_id' => $site->id, 'rec_no' => 'RC-OLD', 'rec_month' => '2000', 'unique_hash' => 'old-year-signature'])->saveQuietly();
        $oldReceipt->delete();
        $receipt = $oldReceipt->replicate(['rec_no', 'unique_hash', 'deleted_at']);
        $receipt->forceFill(['rec_no' => 'RC-CURRENT', 'rec_month' => now()->format('Y'), 'date' => now()->toDateString()])->saveQuietly();
        $this->assertSame(0, Artisan::call('app:sign-receipt-with-hash', ['receipt' => $receipt->id]));
        $this->assertStringEndsWith(';0;', $payloads[2]);
    }

    /** @return array<string, array{string, string}> */
    public static function signerFailures(): array
    {
        $cases = [];
        foreach (['invoice', 'receipt'] as $type) {
            foreach (['empty_signature', 'save_veto', 'core_mutation', 'hash_mutation'] as $change) {
                $cases[$type.'_'.$change] = [$type, $change];
            }
        }
        $cases['receipt_allocation_mutation'] = ['receipt', 'allocation_mutation'];
        $cases['receipt_allocation_removal'] = ['receipt', 'allocation_removal'];

        return $cases;
    }

    #[DataProvider('signerFailures')]
    public function test_signer_failure_rolls_back_signature_content_cash_date_and_allocations(string $type, string $change): void
    {
        [$lab, $user, $site, $category, $payment] = $this->fixture();
        $invoice = $this->invoice($lab, $user, $site, $category);
        $allocation = null;
        if ($type === 'invoice') {
            $cash = InvoiceCategory::firstOrCreate(['code' => 'FR'], ['description' => 'Cash invoice']);
            DB::table('invoices')->where('id', $invoice->id)->update(['type_id' => $cash->id, 'unique_hash' => null]);
            $document = $invoice->fresh();
        } else {
            $document = new Receipt;
            $document->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'customer_id' => $site->customer_id,
                'warehouse_id' => $site->id, 'rec_no' => 'RC-BOUNDARY', 'rec_month' => now()->format('Y'),
                'date' => now()->toDateString()])->saveQuietly();
            $allocation = new InvoiceReceipt;
            $allocation->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'receipt_id' => $document->id,
                'invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'paid_amount' => '5.00',
                'invoice_pending_amount' => '20.00', 'pending_amount' => '15.00'])->saveQuietly();
            $allocation = $allocation->fresh();
            $document = $document->fresh();
        }
        $before = $document->getAttributes();
        $allocationBefore = $allocation?->getAttributes();
        $this->mock(DocumentSignature::class)->shouldReceive('sign')->once()->andReturn($change === 'empty_signature' ? '' : 'intended-signature');
        $event = 'eloquent.'.($change === 'save_veto' ? 'saving' : 'saved').': '.$document::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        $called = false;
        Event::listen($event, function (Model $record) use ($change, $document, $allocation, &$called): ?bool {
            if ((int) $record->id !== (int) $document->id || blank($record->unique_hash)) {
                return null;
            }
            $called = true;
            match ($change) {
                'core_mutation' => DB::table($record->getTable())->where('id', $record->id)->update(['date' => '2000-01-01']),
                'hash_mutation' => DB::table($record->getTable())->where('id', $record->id)->update(['unique_hash' => 'forged-signature']),
                'allocation_mutation' => DB::table($allocation->getTable())->where('id', $allocation->id)->update(['paid_amount' => '19.00']),
                'allocation_removal' => DB::table($allocation->getTable())->where('id', $allocation->id)->delete(),
                default => null,
            };

            return $change === 'save_veto' ? false : null;
        });
        try {
            Artisan::call('app:sign-'.$type.'-with-hash', [$type => $document->id]);
            $this->fail('Signing accepted '.$change.'.');
        } catch (LogicException) {
            $this->assertSame($change !== 'empty_signature', $called);
            $this->assertSame($before, $document->fresh()->getAttributes());
            if ($allocation !== null) {
                $this->assertSame($allocationBefore, $allocation->fresh()->getAttributes());
            }
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
