<?php

namespace Tests\Feature;

use App\Actions\IssueBillingSourceInvoice;
use App\Actions\UpdateQuoteItem;
use App\Jobs\SendSharedDocumentEmail;
use App\Models\CollectionProduct;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\DiscountCategory;
use App\Models\DocumentDelivery;
use App\Models\ExportCertificate;
use App\Models\ExportCertificateItem;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Models\InvoiceItem;
use App\Models\ISOActivityLog;
use App\Models\LabCode;
use App\Models\Parameter;
use App\Models\PhytosanitaryProduct;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\TransportCategory;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Notifications\OperationalNotification;
use App\Observers\OperationalModelObserver;
use App\Support\ExportHubQuery;
use App\Support\NotificationTemplateService;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BillingSourceLaboratoryOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    public static function sources(): array
    {
        return [
            'quote' => ['quote', Quote::class, QuoteItem::class, 'quote_id', 'quotes', 'quotes'],
            'import' => ['import_certificate', ImportCertificate::class, ImportCertificateItem::class, 'certificate_id', 'importcertificates', 'import_certificates'],
            'export' => ['export_certificate', ExportCertificate::class, ExportCertificateItem::class, 'certificate_id', 'exportcertificates', 'export_certificates'],
        ];
    }

    public static function certificates(): array
    {
        return array_diff_key(self::sources(), ['quote' => true]);
    }

    private function operator(VAPLab $lab, array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function site(): Warehouse
    {
        return Warehouse::create(['name' => 'Shared site '.Str::uuid(), 'customer_id' => Customer::create(['name' => 'Shared customer'])->id]);
    }

    private function source(string $class, VAPLab $lab, User $user, Warehouse $site): Model
    {
        $attributes = match ($class) {
            Quote::class => ['quote_no' => 'SOURCE-'.Str::uuid(), 'quote_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'total' => '20.00', 'sub_total' => '20.00'],
            ImportCertificate::class => ['cert_no' => 'SOURCE-'.Str::uuid(), 'importer_id' => $site->customer_id, 'importer_warehouse_id' => $site->id, 'exporter_id' => $site->customer_id, 'exporter_warehouse_id' => $site->id],
            ExportCertificate::class => ['cert_no' => 'SOURCE-'.Str::uuid(), 'exporter_id' => $site->customer_id, 'exporter_warehouse_id' => $site->id],
        };
        $source = new $class([...$attributes, 'user_id' => $user->id, 'date' => now()->toDateString()]);
        $source->lab_id = $lab->id;
        $source->saveQuietly();

        return $source;
    }

    private function invoice(VAPLab $lab, User $user, Warehouse $site): Invoice
    {
        $invoice = new Invoice(['inv_no' => 'SOURCE-INVOICE-'.Str::uuid(), 'invoice_month' => now()->format('Y'),
            'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'user_id' => $user->id]);
        $invoice->lab_id = $lab->id;
        $invoice->saveQuietly();

        return $invoice;
    }

    #[DataProvider('sources')]
    public function test_staff_lookups_and_private_documents_follow_the_active_lab(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $one = VAPLab::factory()->create();
        $two = VAPLab::factory()->create();
        $user = $this->operator($one, ['view_'.$module, 'edit_'.$module]);
        DB::table('lab_user')->insert(['lab_id' => $two->id, 'user_id' => $user->id]);
        $site = $this->site();
        $local = $this->source($class, $one, $user, $site);
        $peer = $this->source($class, $two, $user, $site);
        $lookup = match ($type) {
            'quote' => 'getQuote', 'import_certificate' => 'getImportCertificate', default => 'getExportCertificate',
        };
        $this->actingAs($user)->withSession(['active_lab_id' => $one->id]);
        $this->getJson(route($routes.'.'.$lookup, ['q' => 'SOURCE-']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $local->id);
        $this->get(route($routes.'.show', $peer->id))->assertNotFound();
        $this->get(route($routes.'.edit', $peer->id))->assertNotFound();
        $this->get(route($routes.'.getPDF', ['id' => $peer->id]))->assertNotFound();
        $this->withSession(['active_lab_id' => $two->id]);
        $this->getJson(route($routes.'.'.$lookup, ['q' => 'SOURCE-']))->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $peer->id);
        $this->assertSame($site->customer_id, $local->getAttribute($type === 'quote' ? 'customer_id' : ($type === 'import_certificate' ? 'importer_id' : 'exporter_id')));
    }

    #[DataProvider('sources')]
    public function test_source_exports_and_known_audits_do_not_expose_peer_documents(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_'.$module, 'export_'.$module, 'view_activity_log']);
        $site = $this->site();
        $local = $this->source($class, $lab, $user, $site);
        $peer = $this->source($class, VAPLab::factory()->create(), $user, $site);
        $localAudit = activity()->causedBy($user)->performedOn($local)->log('Local source audit');
        $peerAudit = activity()->causedBy($user)->performedOn($peer)->log('Peer source audit');
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->getJson(route('systemactivity.show', $localAudit->id))->assertOk();
        $this->getJson(route('systemactivity.show', $peerAudit->id))->assertNotFound();
        request()->attributes->set('proposal_laboratory_id', $lab->id);
        request()->attributes->set('sample_laboratory_id', $lab->id);
        try {
            $this->assertSame([$local->id], app(ExportHubQuery::class)->forDataset($module, [])->pluck($module.'.id')->all());
            $this->assertSame([$localAudit->id], ISOActivityLog::query()->whereKey([$localAudit->id, $peerAudit->id])->pluck('id')->all());
        } finally {
            request()->attributes->remove('proposal_laboratory_id');
            request()->attributes->remove('sample_laboratory_id');
        }
    }

    #[DataProvider('sources')]
    public function test_children_derive_the_parent_owner_and_database_keys_reject_cross_lab_writes(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $other = VAPLab::factory()->create();
        $user = $this->operator($lab, []);
        $source = $this->source($class, $lab, $user, $this->site());
        $line = new $child;
        $line->setAttribute($parent, $source->id);
        $line->saveOrFail();
        $this->assertSame($lab->id, $line->lab_id);
        try {
            DB::transaction(fn () => DB::table($line->getTable())->insert([$parent => $source->id, 'lab_id' => $other->id]));
            $this->fail('Cross-lab child must fail at the database boundary.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', $exception->errorInfo[0]);
        }
        $line->lab_id = $other->id;
        $this->expectException(LogicException::class);
        $line->save();
    }

    #[DataProvider('sources')]
    public function test_invoice_links_require_same_owner_and_party_and_preserve_archived_history(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $other = VAPLab::factory()->create();
        $user = $this->operator($lab, []);
        $site = $this->site();
        $source = $this->source($class, $lab, $user, $site);
        $peer = $this->invoice($other, $user, $site);
        $wrongParty = $this->invoice($lab, $user, $this->site());
        foreach ([$peer, $wrongParty] as $invalid) {
            try {
                $source->forceFill(['invoice_id' => $invalid->id])->save();
                $this->fail('Invalid invoice link must fail.');
            } catch (ValidationException) {
                $this->assertNull($source->fresh()->invoice_id);
                $source->refresh();
            }
        }
        try {
            DB::transaction(fn () => DB::table($source->getTable())->where('id', $source->id)->update(['invoice_id' => $peer->id]));
            $this->fail('Cross-lab invoice link must fail in PostgreSQL.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', $exception->errorInfo[0]);
        }
        $invoice = $this->invoice($lab, $user, $site);
        $source->forceFill(['invoice_id' => $invoice->id])->save();
        $source->delete();
        $invoice->delete();
        $source->restore();
        $this->assertSame($invoice->id, $source->fresh()->invoice_id);
    }

    #[DataProvider('sources')]
    public function test_queued_email_rechecks_source_permission_membership_and_live_lab_before_render(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_'.$module]);
        $source = $this->source($class, $lab, $user, $this->site());
        $delivery = DocumentDelivery::create(['document_type' => $type, 'document_id' => $source->id,
            'sender_id' => $user->id, 'status' => 'queued', 'recipients' => ['demo@example.test'],
            'subject' => 'Test only', 'message' => 'Test only']);
        Mail::fake();
        $definition = app(ShareableDocumentRegistry::class)->definition($type);
        $registry = $this->mock(ShareableDocumentRegistry::class);
        $registry->shouldReceive('definition')->with($type)->andReturn($definition);
        $registry->shouldNotReceive('render');
        $user->revokePermissionTo('view_'.$module);
        foreach (['permission', 'membership', 'laboratory'] as $revocation) {
            if ($revocation === 'membership') {
                $user->givePermissionTo('view_'.$module);
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
            }
            if ($revocation === 'laboratory') {
                DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
                $lab->delete();
            }
            try {
                (new SendSharedDocumentEmail($delivery))->handle($registry, app(NotificationTemplateService::class));
                $this->fail('Revoked '.$revocation.' must prevent private document email.');
            } catch (AuthorizationException) {
                Mail::assertNothingSent();
            }
        }
    }

    public function test_quote_conversion_is_signed_owned_and_replay_safe_without_edit_quote_permission(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_quotes', 'add_invoices']);
        $source = $this->source(Quote::class, $lab, $user, $this->site());
        QuoteItem::create(['quote_id' => $source->id, 'item_description' => 'Test analysis', 'qty' => 1, 'unit_price' => '20.00', 'total' => '20.00']);
        $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['description' => 'Factura']);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->postJson(route('quotes.convertToInvoice'), ['quote_id' => $source->id, 'type_id' => ['value' => $category->id]])->assertRedirect();
        $invoice = Invoice::findOrFail($source->fresh()->invoice_id);
        $this->assertSame($lab->id, $invoice->lab_id);
        $this->assertSame('20.00', $invoice->amount_due);
        $this->assertSame(Invoice::STATUS_CODE_NORMAL, $invoice->status_code);
        $this->assertNotEmpty($invoice->unique_hash);
        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame('quote', $invoice->invoiceable_type);
        $this->postJson(route('quotes.convertToInvoice'), ['quote_id' => $source->id, 'type_id' => ['value' => $category->id]])->assertConflict();
        $this->assertSame(1, Invoice::where('invoiceable_type', 'quote')->where('invoiceable_id', $source->id)->count());
    }

    public function test_issuance_rolls_back_when_signature_or_audit_persistence_is_vetoed(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_quotes', 'add_invoices']);
        $source = $this->source(Quote::class, $lab, $user, $this->site());
        QuoteItem::create(['quote_id' => $source->id, 'total' => '20.00']);
        $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['description' => 'Factura']);
        foreach (['signature', 'audit', 'source'] as $veto) {
            $event = match ($veto) {
                'signature' => 'eloquent.saving: '.Invoice::class,
                'audit' => 'eloquent.creating: '.ISOActivityLog::class,
                default => 'eloquent.updating: '.Quote::class,
            };
            $listeners = Event::getRawListeners()[$event] ?? [];
            Event::listen($event, fn (Model $record) => match ($veto) {
                'signature' => $record->isDirty('unique_hash') ? false : null,
                'audit' => $record->event === 'issued' ? false : null,
                default => false,
            });
            try {
                app(IssueBillingSourceInvoice::class)->execute($user->id, $lab->id, 'quote', $source->id, ['type_id' => $category->id]);
                $this->fail('Vetoed '.$veto.' must reject issuance.');
            } catch (LogicException) {
                $this->assertNull($source->fresh()->invoice_id);
                $this->assertFalse((bool) $source->fresh()->converted_to_invoice);
                $this->assertSame(0, Invoice::count());
                $this->assertSame(0, InvoiceItem::count());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_certificate_creation_and_invoice_issuance_have_distinct_post_routes(): void
    {
        foreach (['importcertificates', 'exportcertificates'] as $prefix) {
            $this->assertNotSame(route($prefix.'.store'), route($prefix.'.issueInvoice'));
            $this->assertSame(['POST'], app('router')->getRoutes()->getByName($prefix.'.store')->methods());
            $this->assertSame(['POST'], app('router')->getRoutes()->getByName($prefix.'.issueInvoice')->methods());
        }
    }

    #[DataProvider('certificates')]
    public function test_certificate_forms_reject_manual_billing_fields_and_preserve_existing_links(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['add_'.$module, 'edit_'.$module]);
        $site = $this->site();
        $country = Country::create(['name' => 'Test country', 'code' => 'TEST', 'phone_code' => '000']);
        $transport = TransportCategory::create(['name' => 'Test transport']);
        $currency = Currency::create(['name' => 'Test currency', 'code' => 'TEST', 'symbol' => 'T']);
        $product = PhytosanitaryProduct::create(['name' => 'Test product']);
        $option = fn (int $id): array => ['value' => $id, 'label' => 'Test'];
        $payload = [
            'exporter_id' => $option($site->customer_id), 'exporter_warehouse_id' => $option($site->id),
            'trans_type_id' => $option($transport->id), 'authorized_personnel' => 'Test operator', 'date' => now()->toDateString(),
            'obs' => 'Metadata correction', 'items' => [['product_id' => $option($product->id), 'qty' => 1,
                'origin' => 'Test origin', 'validity' => now()->addYear()->toDateString(), 'lot' => 'Test lot', 'bl_no' => 'Test BL']],
            ...($type === 'import_certificate' ? [
                'importer_id' => $option($site->customer_id), 'importer_warehouse_id' => $option($site->id),
                'currency_id' => $option($currency->id), 'destination_country_id' => $option($country->id),
                'port_exit' => 'Exit', 'port_entry' => 'Entry', 'cost_freight' => 1, 'cost_insurance' => 1,
                'cost_final' => 10, 'vat' => 14, 'vat_cost' => 1.4,
            ] : [
                'country_origin_id' => $option($country->id), 'country_destination_id' => $option($country->id),
                'origin_city' => 'Origin', 'destination_city' => 'Destination',
                'expedition_date' => now()->toDateString(), 'expedition_location' => 'Test location',
            ]),
        ];
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        foreach ([['invoice_id' => null], ['invoice_id' => 999999], ['invoiced' => false], ['invoiced' => true]] as $forged) {
            $this->postJson(route($routes.'.store'), [...$payload, ...$forged])->assertUnprocessable()->assertJsonValidationErrors(array_keys($forged));
        }
        $this->assertSame(0, $class::count());
        $this->post(route($routes.'.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $source = $class::sole();
        $this->assertNull($source->invoice_id);
        $this->assertFalse((bool) $source->invoiced);
        $invoice = $this->invoice($lab, $user, $site);
        $source->forceFill(['invoice_id' => $invoice->id, 'invoiced' => true])->save();
        foreach ([['invoice_id' => null], ['invoice_id' => $invoice->id], ['invoiced' => false]] as $forged) {
            $this->putJson(route($routes.'.update', $source->id), [...$payload, ...$forged])->assertUnprocessable()->assertJsonValidationErrors(array_keys($forged));
        }
        $this->putJson(route($routes.'.update', $source->id), [...$payload, 'obs' => 'Rejected'])->assertUnprocessable()->assertJsonValidationErrors(['items', 'date']);
        $this->put(route($routes.'.update', $source->id), ['obs' => 'Still editable'])->assertSessionHasNoErrors()->assertRedirect();
        $source->refresh();
        $this->assertSame($invoice->id, $source->invoice_id);
        $this->assertTrue((bool) $source->invoiced);
        $this->assertSame('Still editable', $source->obs);
        $this->assertFalse($source->isFillable('invoice_id'));
        $this->assertFalse($source->isFillable('invoiced'));
        foreach ([['invoice_id' => null], ['invoice_id' => $this->invoice($lab, $user, $site)->id], ['invoiced' => false]] as $change) {
            try {
                $source->forceFill($change)->save();
                $this->fail('An issued certificate billing state must remain intact.');
            } catch (LogicException) {
                $source->refresh();
                $this->assertSame($invoice->id, $source->invoice_id);
                $this->assertTrue((bool) $source->invoiced);
            }
        }
    }

    public function test_quote_item_corrections_require_permission_and_owner_and_ignore_forged_financial_fields(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_quotes']);
        $site = $this->site();
        $quote = $this->source(Quote::class, $lab, $user, $site);
        $line = QuoteItem::create(['quote_id' => $quote->id, 'obs' => 'Original', 'total' => '20.00']);
        $peer = $this->source(Quote::class, VAPLab::factory()->create(), $user, $site);
        $peerLine = QuoteItem::create(['quote_id' => $peer->id]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->putJson(route('quoteitems.update', $line->id), ['obs' => 'Denied'])->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('edit_quotes', 'web'));
        $this->putJson(route('quoteitems.update', $peerLine->id), ['obs' => 'Denied'])->assertNotFound();
        $this->putJson(route('quoteitems.update', $line->id), ['itemable_id' => 999999, 'itemable_type' => 'collectionproduct'])->assertUnprocessable();
        $this->put(route('quoteitems.update', $line->id), ['obs' => 'Corrected', 'total' => 999, 'quote_id' => $peer->id, 'lab_id' => $peer->lab_id])->assertSessionHasNoErrors()->assertRedirect();
        $line->refresh();
        $this->assertSame('Corrected', $line->obs);
        $this->assertSame('20.00', $line->total);
        $this->assertSame($quote->id, $line->quote_id);
        $this->assertSame($lab->id, $line->lab_id);
        $this->assertTrue(ISOActivityLog::withoutGlobalScopes()->where('subject_type', $line->getMorphClass())->where('subject_id', $line->id)->where('event', 'updated')->exists());
        $product = CollectionProduct::create(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id]);
        $entry = VAPSampleEntry::factory()->make(['lab_id' => $lab->id, 'customer_id' => $site->customer_id,
            'warehouse_id' => $site->id, 'collection_product_id' => $product->id]);
        $entry->saveQuietly();
        $code = new LabCode(['collection_id' => $product->id, 'code' => 'CL-'.Str::uuid(), 'cl_month' => now()->format('Y'), 'codeable_type' => 'analysis']);
        $code->id = (int) LabCode::max('id') + $product->id + 10;
        $code->saveQuietly();
        $this->assertNotSame($product->id, $code->id);
        $this->getJson(route('labcodes.getCode', ['q' => $code->code]))->assertOk()->assertExactJson([
            ['id' => $code->id, 'code' => $code->code, 'collection_id' => $product->id],
        ]);
        $this->put(route('quoteitems.update', $line->id), ['lab_code_id' => $code->id, 'obs' => 'Selected CL'])->assertSessionHasNoErrors()->assertRedirect();
        $line->refresh();
        $this->assertSame($product->id, $line->itemable_id);
        $this->assertSame('collectionproduct', $line->itemable_type);
        $this->get(route('quotes.edit', $quote->id))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record.items.0.itemable_id.value', $product->id)->where('record.items.0.itemable_type', 'collectionproduct'));
        $parameter = Parameter::create(['name' => 'Test analysis', 'code' => 'TEST-'.Str::uuid()]);
        $discount = DiscountCategory::create(['name' => 'Test discount', 'symbol' => '%']);
        $this->put(route('quotes.update', $quote->id), [
            'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'use_matrix_price' => false, 'is_service' => false, 'items' => [[
                'catalog_type' => 'parameter', 'item_id' => $parameter->id, 'unit_id' => null,
                'collection_product_id' => $product->id, 'qty' => '1', 'agreed_unit_price' => '20.00',
                'discount_mode' => 'percentage', 'discount_value' => '0', 'obs' => 'Whole quote save',
            ]],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $replacement = $quote->items()->sole();
        $this->assertSame($product->id, $replacement->itemable_id);
        $this->assertSame('collectionproduct', $replacement->itemable_type);
        $this->put(route('quoteitems.update', $replacement->id), ['lab_code_id' => null])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($replacement->fresh()->itemable_id);
        $this->assertNull($product->fresh()->quote_id);
        $this->assertFalse((bool) $product->fresh()->quoted);
    }

    public function test_quote_item_correction_rolls_back_when_its_explicit_audit_is_vetoed(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['edit_quotes']);
        $quote = $this->source(Quote::class, $lab, $user, $this->site());
        $line = QuoteItem::create(['quote_id' => $quote->id, 'obs' => 'Original']);
        $event = 'eloquent.creating: '.ISOActivityLog::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (ISOActivityLog $audit): ?bool => $audit->event === 'updated' ? false : null);
        try {
            app(UpdateQuoteItem::class)->execute($user->id, $lab->id, $line->id, ['obs' => 'Not committed']);
            $this->fail('Audit veto must roll back the correction.');
        } catch (LogicException) {
            $this->assertSame('Original', $line->fresh()->obs);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public function test_commercial_lab_code_picker_requires_permission_and_direct_local_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        $other = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_quotes']);
        $site = $this->site();
        $codes = [];
        foreach ([$lab, $other] as $owner) {
            $product = CollectionProduct::create(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id]);
            $entry = VAPSampleEntry::factory()->make(['lab_id' => $owner->id, 'customer_id' => $site->customer_id,
                'warehouse_id' => $site->id, 'collection_product_id' => $product->id]);
            $entry->saveQuietly();
            $code = new LabCode(['collection_id' => $product->id, 'code' => 'PICKER-'.Str::uuid(), 'cl_month' => now()->format('Y'), 'codeable_type' => 'analysis']);
            $code->saveQuietly();
            $codes[] = $code;
        }
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->getJson(route('labcodes.getCode', ['q' => 'PICKER-']))->assertOk()->assertExactJson([
            ['id' => $codes[0]->id, 'code' => $codes[0]->code, 'collection_id' => $codes[0]->collection_id],
        ]);
        $user->revokePermissionTo('view_quotes');
        $this->getJson(route('labcodes.getCode', ['q' => '']))->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('view_quality_certificates', 'web'));
        $this->getJson(route('labcodes.getCode', ['q' => 'PICKER-']))->assertOk()->assertExactJson([
            ['id' => $codes[0]->id, 'code' => $codes[0]->code, 'collection_id' => $codes[0]->collection_id],
        ]);
        $user->revokePermissionTo('view_quality_certificates');
        $user->givePermissionTo('view_quotes');
        $user->update(['is_active' => false]);
        $this->getJson(route('labcodes.getCode', ['q' => '']))->assertUnauthorized();
        $user->update(['is_active' => true]);
        $this->actingAs($user);
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->getJson(route('labcodes.getCode', ['q' => '']))->assertForbidden();
    }

    public function test_issuance_revalidates_persisted_quote_line_sources_instead_of_trusting_raw_links(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_quotes', 'add_invoices']);
        $source = $this->source(Quote::class, $lab, $user, $this->site());
        $line = new QuoteItem(['quote_id' => $source->id, 'itemable_type' => 'collectionproduct', 'itemable_id' => 999999]);
        $line->lab_id = $lab->id;
        $line->saveQuietly();
        $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['description' => 'Factura']);
        try {
            app(IssueBillingSourceInvoice::class)->execute($user->id, $lab->id, 'quote', $source->id, ['type_id' => $category->id]);
            $this->fail('Invalid retained operational source must prevent billing.');
        } catch (ValidationException) {
            $this->assertSame(0, Invoice::count());
            $this->assertNull($source->fresh()->invoice_id);
        }
    }

    public function test_source_party_changes_during_invoice_events_roll_back_issuance(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_quotes', 'add_invoices']);
        $source = $this->source(Quote::class, $lab, $user, $this->site());
        $otherSite = $this->site();
        QuoteItem::create(['quote_id' => $source->id, 'total' => '20.00']);
        $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['description' => 'Factura']);
        $event = 'eloquent.created: '.Invoice::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn () => DB::table('quotes')->where('id', $source->id)->update(['customer_id' => $otherSite->customer_id, 'warehouse_id' => $otherSite->id]));
        try {
            app(IssueBillingSourceInvoice::class)->execute($user->id, $lab->id, 'quote', $source->id, ['type_id' => $category->id]);
            $this->fail('Source identity changes must roll back.');
        } catch (LogicException) {
            $this->assertSame(0, Invoice::count());
            $this->assertNull($source->fresh()->invoice_id);
            $this->assertSame($source->customer_id, $source->fresh()->customer_id);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    #[DataProvider('certificates')]
    public function test_certificate_billing_uses_its_owner_and_party_and_ignores_forged_source_links(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_'.$module, 'add_invoices']);
        $site = $this->site();
        $source = $this->source($class, $lab, $user, $site);
        $peer = $this->source($class, VAPLab::factory()->create(), $user, $site);
        $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['description' => 'Factura']);
        $attributes = ['type_id' => $category->id, 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'total' => '25.00', 'sub_total' => '25.00', 'amount_due' => '-999', 'user_id' => 999999,
            'status_code' => 'A', 'invoiceable_id' => $peer->id, 'invoiceable_type' => $type];
        $items = [['qty' => 1, 'total' => '25.00', 'unit_price' => '25.00', 'itemable_id' => 999999, 'itemable_type' => 'collectionproduct']];
        $action = app(IssueBillingSourceInvoice::class);
        try {
            $action->execute($user->id, $lab->id, $type, $peer->id, $attributes, $items);
            $this->fail('Peer source must not issue an invoice.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, Invoice::count());
        }
        try {
            $action->execute($user->id, $lab->id, $type, $source->id, [...$attributes, 'warehouse_id' => $this->site()->id], $items);
            $this->fail('Wrong party must not issue an invoice.');
        } catch (ValidationException) {
            $this->assertSame(0, Invoice::count());
        }
        $invoice = $action->execute($user->id, $lab->id, $type, $source->id, $attributes, $items);
        $this->assertSame($lab->id, $invoice->lab_id);
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame('25.00', $invoice->amount_due);
        $this->assertSame(Invoice::STATUS_CODE_NORMAL, $invoice->status_code);
        $this->assertSame($source->id, $invoice->invoiceable_id);
        $this->assertNull($invoice->items()->firstOrFail()->itemable_id);
        $this->assertNotEmpty($invoice->unique_hash);
        $this->assertSame($invoice->id, $source->fresh()->invoice_id);
        try {
            $action->execute($user->id, $lab->id, $type, $source->id, $attributes, $items);
            $this->fail('Issued source must not replay.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(1, Invoice::count());
        }
    }

    #[DataProvider('sources')]
    public function test_operational_alerts_only_target_direct_members_of_the_source_lab(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $sender = $this->operator($lab, []);
        $local = $this->operator($lab, ['view_'.$module]);
        $peer = $this->operator(VAPLab::factory()->create(), ['view_'.$module]);
        $source = $this->source($class, $lab, $sender, $this->site());
        Notification::fake();
        (new OperationalModelObserver(app(NotificationTemplateService::class)))->created($source);
        Notification::assertSentTo($local, OperationalNotification::class, fn (OperationalNotification $notification): bool => (int) $notification->payload['context']['lab_id'] === $lab->id);
        Notification::assertNotSentTo($peer, OperationalNotification::class);
    }

    public function test_quote_operational_items_require_canonical_same_lab_customer_and_site(): void
    {
        $lab = VAPLab::factory()->create();
        $other = VAPLab::factory()->create();
        $user = $this->operator($lab, []);
        $site = $this->site();
        $source = $this->source(Quote::class, $lab, $user, $site);
        foreach ([$lab, $other] as $owner) {
            $product = CollectionProduct::create(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id]);
            $entry = VAPSampleEntry::factory()->make(['lab_id' => $owner->id, 'customer_id' => $site->customer_id,
                'warehouse_id' => $site->id, 'collection_product_id' => $product->id]);
            $entry->saveQuietly();
            try {
                $line = QuoteItem::create(['quote_id' => $source->id, 'itemable_id' => $product->id, 'itemable_type' => 'collectionproduct']);
                $this->assertSame($lab->id, $owner->id);
                $this->assertSame($lab->id, $line->lab_id);
            } catch (ValidationException) {
                $this->assertSame($other->id, $owner->id);
                $this->assertSame(1, QuoteItem::where('quote_id', $source->id)->count());
            }
        }
    }

    #[DataProvider('sources')]
    public function test_export_cards_are_lab_private_and_route_access_requires_direct_membership(string $type, string $class, string $child, string $parent, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_'.$module, 'export_'.$module]);
        $site = $this->site();
        $this->source($class, $lab, $user, $site);
        $this->source($class, VAPLab::factory()->create(), $user, $site);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('exports.index', ['dataset' => $module]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('selectedCount', 1)->where('datasets.0.key', $module)->where('datasets.0.count', 1));
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->get(route($routes.'.index'))->assertForbidden();
    }

    public function test_billing_source_migration_replays_and_preserves_retained_records_under_table_locks(): void
    {
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        $schema = 'billing_migration_'.bin2hex(random_bytes(8));
        $name = 'billing_migration';
        $writerName = 'billing_migration_writer';
        $originalDefault = DB::getDefaultConnection();
        config(['database.connections.'.$name => array_merge(config('database.connections.pgsql'), ['search_path' => $schema])]);
        config(['database.connections.'.$writerName => config('database.connections.'.$name)]);
        $connection = DB::connection($name);
        $writer = DB::connection($writerName);
        $probe = false;
        $connection->statement('CREATE SCHEMA "'.$schema.'"');
        try {
            DB::setDefaultConnection($name);
            Schema::create('labs', fn (Blueprint $table) => $table->id());
            DB::table('labs')->insert(['id' => 1]);
            Schema::create('invoices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lab_id')->constrained('labs');
                $table->unique(['id', 'lab_id']);
            });
            foreach (['quotes', 'import_certificates', 'export_certificates'] as $tableName) {
                Schema::create($tableName, function (Blueprint $table): void {
                    $table->id();
                    $table->date('date')->nullable();
                    $table->foreignId('invoice_id')->nullable()->constrained('invoices');
                });
            }
            foreach (['quote_items' => ['quote_id', 'quotes'], 'import_certificate_items' => ['certificate_id', 'import_certificates'],
                'export_certificate_items' => ['certificate_id', 'export_certificates']] as $tableName => [$column, $parent]) {
                Schema::create($tableName, function (Blueprint $table) use ($column, $parent): void {
                    $table->id();
                    $table->foreignId($column)->nullable()->constrained($parent);
                });
            }
            $migration = require database_path('migrations/2026_10_01_183029_enforce_laboratory_ownership_of_billing_sources.php');
            $migration->up();
            $writer->statement("SET lock_timeout = '100ms'");
            $blocked = 0;
            DB::listen(function (QueryExecuted $query) use ($name, $writer, &$probe, &$blocked): void {
                if (! $probe || $query->connectionName !== $name || ! str_starts_with($query->sql, 'LOCK TABLE')) {
                    return;
                }
                $probe = false;
                try {
                    $writer->table('quotes')->insert(['lab_id' => 1]);
                    $this->fail('Rollback must lock out source writers before its retained-record check.');
                } catch (QueryException $exception) {
                    $this->assertSame('55P03', $exception->errorInfo[0]);
                    $blocked++;
                }
            });
            $probe = true;
            $migration->down();
            $this->assertSame(1, $blocked);
            $this->assertFalse(Schema::hasColumn('quotes', 'lab_id'));
            $migration->up();
            DB::table('quotes')->insert(['lab_id' => 1]);
            try {
                $migration->down();
                $this->fail('Retained source evidence must reject rollback.');
            } catch (\RuntimeException) {
                $this->assertSame(1, DB::table('quotes')->count());
                $this->assertTrue(Schema::hasColumn('quote_items', 'lab_id'));
            }
        } finally {
            $probe = false;
            DB::setDefaultConnection($originalDefault);
            $connection->statement('DROP SCHEMA "'.$schema.'" CASCADE');
            DB::purge($name);
            DB::purge($writerName);
            config(['database.connections.'.$name => null, 'database.connections.'.$writerName => null]);
        }
    }

    public function test_certificate_catalog_migration_replays_without_reinterpreting_retained_product_ids(): void
    {
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        $schema = 'certificate_catalog_'.bin2hex(random_bytes(8));
        $name = 'certificate_catalog';
        $originalDefault = DB::getDefaultConnection();
        config(['database.connections.'.$name => array_merge(config('database.connections.pgsql'), ['search_path' => $schema])]);
        $connection = DB::connection($name);
        $connection->statement('CREATE SCHEMA "'.$schema.'"');
        $connection->beginTransaction();
        try {
            DB::setDefaultConnection($name);
            foreach (['products', 'phytosanitary_products'] as $catalog) {
                Schema::create($catalog, fn (Blueprint $table) => $table->id());
                DB::table($catalog)->insert(['id' => 1]);
            }
            foreach (['import_certificate_items' => 'importcert_items_product_id_foreign', 'export_certificate_items' => 'exportcert_items_product_id_foreign'] as $tableName => $constraint) {
                Schema::create($tableName, function (Blueprint $table) use ($constraint): void {
                    $table->id();
                    $table->foreignId('product_id')->nullable();
                    $table->foreign('product_id', $constraint)->references('id')->on('products');
                });
                DB::table($tableName)->insert(['product_id' => null]);
            }
            $migration = require database_path('migrations/2026_10_01_184930_align_trade_certificate_items_with_phytosanitary_catalog.php');
            DB::table('export_certificate_items')->where('id', 1)->update(['product_id' => 1]);
            try {
                $migration->up();
                $this->fail('Matching numeric IDs do not establish matching product identity.');
            } catch (\RuntimeException) {
                $this->assertSame('products', Schema::getForeignKeys('export_certificate_items')[0]['foreign_table']);
                $this->assertSame(1, DB::table('export_certificate_items')->value('product_id'));
            }
            DB::table('export_certificate_items')->update(['product_id' => null]);
            $migration->up();
            $migration->down();
            $migration->up();
            foreach (['import_certificate_items', 'export_certificate_items'] as $tableName) {
                $this->assertSame('phytosanitary_products', Schema::getForeignKeys($tableName)[0]['foreign_table']);
                $this->assertSame(1, DB::table($tableName)->count());
            }
            DB::table('import_certificate_items')->update(['product_id' => 1]);
            try {
                $migration->down();
                $this->fail('Linked phytosanitary records must prevent catalog rollback.');
            } catch (\RuntimeException) {
                $this->assertSame('phytosanitary_products', Schema::getForeignKeys('import_certificate_items')[0]['foreign_table']);
                $this->assertSame(1, DB::table('import_certificate_items')->value('product_id'));
            }
            Schema::create('quotes', fn (Blueprint $table) => $table->id());
            $pricing = require database_path('migrations/2026_10_01_190243_add_service_pricing_mode_to_quotes.php');
            $pricing->up();
            $this->assertTrue(Schema::hasColumn('quotes', 'is_service'));
            $pricing->down();
            $this->assertFalse(Schema::hasColumn('quotes', 'is_service'));
            $pricing->up();
            DB::table('quotes')->insert(['is_service' => true]);
            try {
                $pricing->down();
                $this->fail('Retained quote pricing mode must prevent rollback.');
            } catch (\RuntimeException) {
                $this->assertTrue(DB::table('quotes')->value('is_service'));
            }
            foreach (['invoices', 'credit_notes'] as $name) {
                Schema::create($name, fn (Blueprint $table) => $table->id());
            }
            $financialPricing = require database_path('migrations/2026_10_01_190450_add_service_pricing_mode_to_financial_documents.php');
            $financialPricing->up();
            $financialPricing->down();
            $financialPricing->up();
            $this->assertTrue(Schema::hasColumn('invoices', 'is_service'));
            $this->assertTrue(Schema::hasColumn('credit_notes', 'is_service'));
            DB::table('invoices')->insert(['is_service' => true]);
            try {
                $financialPricing->down();
                $this->fail('Retained financial pricing mode must prevent rollback.');
            } catch (\RuntimeException) {
                $this->assertTrue(DB::table('invoices')->value('is_service'));
            }
        } finally {
            DB::setDefaultConnection($originalDefault);
            $connection->rollBack();
            $connection->statement('DROP SCHEMA "'.$schema.'" CASCADE');
            DB::purge($name);
            config(['database.connections.'.$name => null]);
        }
    }
}
