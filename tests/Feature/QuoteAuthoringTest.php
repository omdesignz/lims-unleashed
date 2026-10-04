<?php

namespace Tests\Feature;

use App\Actions\IssueBillingSourceInvoice;
use App\Actions\SaveQuote;
use App\Actions\SignQuoteRevision;
use App\Actions\UpdateQuoteItem;
use App\Http\Resources\QuoteResource;
use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\InvoiceCategory;
use App\Models\ISOActivityLog;
use App\Models\Parameter;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\QuoteAuthoringData;
use App\Settings\GeneralSettings;
use App\Support\DocumentSignature;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuoteAuthoringTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['add_quotes', 'edit_quotes', 'view_quotes', 'add_invoices'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function payload(string $kind = 'parameter'): array
    {
        $class = QuoteAuthoringData::CATALOGS[$kind];
        $catalog = $class::create([$kind === 'matrix' ? 'description' : 'name' => 'Catalogue '.$kind,
            'code' => 'QUOTE-'.Str::uuid(), 'price' => 999, 'fixed_price' => 999,
            'charge_tax' => true, 'tax_percentage' => '14.00']);
        $customer = Customer::create(['name' => 'Quote customer']);
        $site = Warehouse::create(['name' => 'Quote site', 'customer_id' => $customer->id]);

        return ['customer_id' => $customer->id, 'warehouse_id' => $site->id, 'use_matrix_price' => false, 'is_service' => $kind === 'paid_service',
            'obs' => 'Draft', 'items' => [['catalog_type' => $kind, 'item_id' => $catalog->id, 'qty' => '2.50',
                'agreed_unit_price' => '10.00', 'discount_mode' => 'percentage', 'discount_value' => '10.00', 'unit_id' => null]]];
    }

    public static function kinds(): array
    {
        return [['parameter'], ['matrix'], ['product'], ['paid_service']];
    }

    public function test_add_only_operator_lands_on_an_authorized_success_destination(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $user->syncPermissions([Permission::findOrCreate('add_quotes', 'web')]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('quotes.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('quotes.create'))->assertSessionHas('toast');
        $this->get(route('quotes.create'))->assertOk();
        $this->assertSame(1, Quote::query()->where('lab_id', $lab->id)->count());
    }

    public function test_saved_site_keeps_a_readable_name_when_its_address_is_absent(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $payload);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('quotes.edit', $quote))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Quotes/Edit')->where('record.warehouse_id.value', $payload['warehouse_id'])
            ->where('record.warehouse_id.label', 'Quote site'));
    }

    public function test_line_corrections_reject_archived_units_and_roll_back_audit_stage_retirement(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $this->payload());
        $line = $quote->items()->sole();
        $unit = Unit::create(['code' => 'QUOTE-'.Str::random(8)]);
        $unit->delete();
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->putJson(route('quoteitems.update', $line->id), ['unit_id' => $unit->id])->assertUnprocessable()->assertJsonValidationErrors('unit_id');
        $unit->restore();
        $event = 'eloquent.creating: '.ISOActivityLog::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, function (ISOActivityLog $audit) use ($line, $unit): void {
            if ($audit->event === 'updated' && $audit->subject_type === $line->getMorphClass()) {
                $unit->fresh()->delete();
            }
        });
        try {
            app(UpdateQuoteItem::class)->execute($user->id, $lab->id, $line->id, ['unit_id' => $unit->id]);
            $this->fail('Unit retirement was not detected.');
        } catch (ValidationException) {
            $this->assertNull($line->fresh()->unit_id);
            $this->assertFalse($unit->fresh()->trashed());
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    #[DataProvider('kinds')]
    public function test_agreed_prices_and_fractional_quantities_are_server_calculated_and_round_trip(string $kind): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $quote = Quote::query()->where('lab_id', $lab->id)->sole();
        $line = $quote->items()->sole();
        $this->assertEquals('22.50', $quote->sub_total);
        $this->assertEquals('3.15', $quote->tax);
        $this->assertEquals('2.50', $quote->discount);
        $this->assertEquals('25.65', $quote->total);
        $this->assertSame('9.00', $line->unit_price);
        $this->assertSame('1.00', $line->discount_amount);
        $this->assertSame($kind, $line->extra_data->get('catalog_type'));
        $this->assertSame('10.00', $line->extra_data->get('agreed_unit_price'));
        $this->assertSame($lab->id, $line->lab_id);
        $this->assertSame($user->id, $quote->user_id);
        $resource = QuoteResource::make($quote)->resolve();
        $this->assertEquals('3.15', $resource['tax']);
        $document = app(ReportStudioPdfBuilder::class)->buildQuotePayload($quote->load('items', 'customer', 'warehouse', 'user'), app(GeneralSettings::class));
        $this->assertStringContainsString('Catalogue '.$kind, $document['data']['bodyHtml']);
        $this->assertStringContainsString('25,65', $document['data']['bodyHtml']);
        $this->get(route('quotes.edit', $quote->id))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('record.items.0.catalog_type', $kind)->where('record.items.0.agreed_unit_price', '10.00')
            ->where('record.items.0.discount_value', '10.00'));
        $category = InvoiceCategory::create(['name' => 'Invoice', 'code' => 'QT'.Str::random(5)]);
        $invoice = app(IssueBillingSourceInvoice::class)->execute($user->id, $lab->id, 'quote', $quote->id, ['type_id' => $category->id]);
        $this->assertSame($line->extra_data->all(), $invoice->items()->sole()->extra_data->all());
        $this->assertEquals($quote->total, $invoice->total);
        $document = app(ReportStudioPdfBuilder::class)->buildInvoicePayload($invoice->load('items', 'customer', 'warehouse', 'user'), app(GeneralSettings::class));
        $this->assertStringContainsString('Catalogue '.$kind, $document['data']['bodyHtml']);
    }

    public function test_update_retains_history_author_and_identity_and_releases_replaced_source(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        $source = CollectionProduct::create(['customer_id' => $payload['customer_id'], 'warehouse_id' => $payload['warehouse_id']]);
        VAPSampleEntry::factory()->make(['lab_id' => $lab->id, 'customer_id' => $payload['customer_id'],
            'warehouse_id' => $payload['warehouse_id'], 'collection_product_id' => $source->id])->saveQuietly();
        $payload['items'][0]['collection_product_id'] = $source->id;
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $payload);
        $this->assertSame($quote->id, $source->fresh()->quote_id);
        $line = $quote->items()->sole();
        $before = Arr::except($line->getAttributes(), ['updated_at', 'deleted_at']);
        $identity = Arr::only($quote->getAttributes(), ['quote_no', 'quote_month', 'seq', 'user_id']);
        $previousSignature = $quote->unique_hash;
        unset($payload['items'][0]['collection_product_id']);
        $payload['items'][0]['agreed_unit_price'] = '12.00';
        $editor = $this->operator($lab);
        $updated = app(SaveQuote::class)->execute($editor->id, $lab->id, $payload, $quote->id);
        $this->assertSame($identity, Arr::only($updated->getAttributes(), array_keys($identity)));
        $this->assertNotSame($previousSignature, $updated->unique_hash);
        $revision = $updated->activities()->where('event', 'signed_revision')->latest('id')->firstOrFail();
        $this->assertSame($previousSignature, $revision->properties->get('previous_signature'));
        $payloadEvidence = json_decode($revision->properties->get('signed_payload'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($before['unit_price'], $payloadEvidence['previous_snapshot']['lines'][0]['unit_price']);
        $this->assertSame($updated->unique_hash, app(DocumentSignature::class)->sign($revision->properties->get('signed_payload')));
        $this->assertTrue($line->fresh()->trashed());
        $this->assertSame($before, Arr::except($line->fresh()->getAttributes(), ['updated_at', 'deleted_at']));
        $this->assertNull($source->fresh()->quote_id);
        $this->assertFalse((bool) $source->fresh()->quoted);
        $this->assertSame('12.00', $updated->items()->sole()->extra_data->get('agreed_unit_price'));
    }

    public static function invalidInputs(): array
    {
        return [
            ['total', '100'], ['user_id', 9], ['quote_no', 'FORGED'],
            ['items.0.catalog_type', 'unknown'], ['items.0.qty', '0'], ['items.0.qty', '1.001'],
            ['items.0.agreed_unit_price', '-1'], ['items.0.discount_value', '101'],
            ['items.0.item_description', 'Forged'], ['items.0.agreed_unit_price', '999999999'],
            ['items.0.item_id', 999999999], ['items.0.collection_product_id', 999999999],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_or_server_owned_inputs_do_not_create_documents(string $field, mixed $value): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        Arr::set($payload, $field, $value);
        try {
            app(SaveQuote::class)->execute($user->id, $lab->id, $payload);
            $this->fail('Invalid authoring input accepted.');
        } catch (ValidationException) {
            $this->assertSame(0, Quote::query()->where('lab_id', $lab->id)->count());
        }
    }

    public function test_fixed_discount_rounding_and_tax_disabled(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        Parameter::findOrFail($payload['items'][0]['item_id'])->update(['charge_tax' => false]);
        $payload['items'][0] = [...$payload['items'][0], 'qty' => '1.50', 'agreed_unit_price' => '0.05', 'discount_mode' => 'fixed', 'discount_value' => '0.02'];
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $payload);
        $this->assertEquals('0.05', $quote->sub_total);
        $this->assertEquals('0.03', $quote->discount);
        $this->assertEquals('0.00', $quote->tax);
    }

    public static function revisionFaults(): array
    {
        return [['save_veto'], ['audit_veto'], ['history_tamper'], ['root_history_tamper'], ['retired_line_history_tamper'], ['revocation']];
    }

    #[DataProvider('revisionFaults')]
    public function test_revision_failures_retain_prior_signature_lines_and_history(string $fault): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        $action = app(SaveQuote::class);
        $quote = $action->execute($user->id, $lab->id, $payload);
        $root = $quote->getAttributes();
        $lines = $quote->items()->withTrashed()->orderBy('id')->get()->toArray();
        $history = app(SignQuoteRevision::class)->history($quote);
        $auditEvent = 'eloquent.creating: '.ISOActivityLog::class;
        $saveEvent = 'eloquent.saving: '.Quote::class;
        $deleteEvent = 'eloquent.deleting: '.QuoteItem::class;
        $listeners = array_intersect_key(Event::getRawListeners(), array_flip([$auditEvent, $saveEvent, $deleteEvent]));
        $mutateHistory = fn (): int => DB::table('activity_log')->where('id', array_key_first($history))->update(['description' => 'Tampered early']);
        Event::listen($saveEvent, function (Quote $record) use ($fault, $mutateHistory): ?bool {
            if ($fault === 'root_history_tamper' && ! $record->isDirty('unique_hash')) {
                $mutateHistory();
            }

            return $fault === 'save_veto' && $record->isDirty('unique_hash') ? false : null;
        });
        Event::listen($deleteEvent, function () use ($fault, $mutateHistory): void {
            if ($fault === 'retired_line_history_tamper') {
                $mutateHistory();
            }
        });
        Event::listen($auditEvent, function (ISOActivityLog $audit) use ($fault, $history, $user, $lab): ?bool {
            if ($audit->event !== 'signed_revision') {
                return null;
            }
            if ($fault === 'audit_veto') {
                return false;
            }
            if ($fault === 'history_tamper') {
                DB::table('activity_log')->where('id', array_key_first($history))->update(['description' => 'Tampered']);
            }
            if ($fault === 'revocation') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
            }

            return null;
        });
        $payload['items'][0]['agreed_unit_price'] = '15.00';
        try {
            $action->execute($user->id, $lab->id, $payload, $quote->id);
            $this->fail('Failed revision committed.');
        } catch (LogicException|AuthorizationException) {
            $this->assertSame($root, $quote->fresh()->getAttributes());
            $this->assertSame($lines, $quote->items()->withTrashed()->orderBy('id')->get()->toArray());
            $this->assertSame($history, app(SignQuoteRevision::class)->history($quote));
        } finally {
            foreach ([$auditEvent, $saveEvent, $deleteEvent] as $event) {
                Event::forget($event);
                foreach ($listeners[$event] ?? [] as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_row_correction_signs_a_revision_but_billed_observations_keep_signed_core(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $this->payload());
        $previousSignature = $quote->unique_hash;
        app(UpdateQuoteItem::class)->execute($user->id, $lab->id, $quote->items()->sole()->id, ['obs' => 'Metadata correction']);
        $quote->refresh();
        $this->assertNotSame($previousSignature, $quote->unique_hash);
        $history = app(SignQuoteRevision::class)->history($quote);
        $this->assertCount(2, $history);
        $revision = json_decode($quote->activities()->where('event', 'signed_revision')->latest('id')->firstOrFail()->properties->get('signed_payload'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertNull($revision['previous_snapshot']['lines'][0]['obs']);
        $this->assertSame('Metadata correction', $revision['snapshot']['lines'][0]['obs']);
        $snapshot = app(SignQuoteRevision::class)->snapshot($quote, $quote->items()->get());
        $quote->forceFill(['converted_to_invoice' => true])->saveQuietly();
        app(SaveQuote::class)->execute($user->id, $lab->id, ['obs' => 'Billed annotation'], $quote->id);
        $this->assertSame($quote->unique_hash, $quote->fresh()->unique_hash);
        $this->assertSame($snapshot, app(SignQuoteRevision::class)->snapshot($quote->fresh(), $quote->items()->get()));
        $this->assertSame($history, app(SignQuoteRevision::class)->history($quote));
    }

    public static function observationFaults(): array
    {
        return [['save'], ['audit']];
    }

    #[DataProvider('observationFaults')]
    public function test_billed_observations_cannot_change_retained_signature_history(string $stage): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $this->payload());
        $quote->forceFill(['converted_to_invoice' => true])->saveQuietly();
        $root = $quote->getAttributes();
        $history = app(SignQuoteRevision::class)->history($quote);
        $events = ['eloquent.saving: '.Quote::class, 'eloquent.creating: '.ISOActivityLog::class];
        $listeners = array_intersect_key(Event::getRawListeners(), array_flip($events));
        $mutate = fn (): int => DB::table('activity_log')->where('id', array_key_first($history))->delete();
        Event::listen($events[0], function (Quote $record) use ($stage, $quote, $mutate): void {
            if ($stage === 'save' && $record->id === $quote->id) {
                $mutate();
            }
        });
        Event::listen($events[1], function (ISOActivityLog $audit) use ($stage, $quote, $mutate): void {
            if ($stage === 'audit' && $audit->event === 'updated' && $audit->subject_type === $quote->getMorphClass() && (int) $audit->subject_id === $quote->id) {
                $mutate();
            }
        });
        try {
            app(SaveQuote::class)->execute($user->id, $lab->id, ['obs' => 'Billed annotation'], $quote->id);
            $this->fail('A billed observation removed retained signature history.');
        } catch (LogicException) {
            $this->assertSame($root, $quote->fresh()->getAttributes());
            $this->assertSame($history, app(SignQuoteRevision::class)->history($quote));
        } finally {
            foreach ($events as $event) {
                Event::forget($event);
                foreach ($listeners[$event] ?? [] as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public static function correctionFaults(): array
    {
        return [['line_save', false], ['signature_save', false], ['signed_audit', false], ['correction_audit', false], ['correction_audit', true]];
    }

    #[DataProvider('correctionFaults')]
    public function test_row_correction_cannot_change_a_retained_sibling_line(string $stage, bool $archived): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        $payload['items'][] = $payload['items'][0];
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $payload);
        [$target, $sibling] = $quote->items()->orderBy('id')->get()->all();
        if ($archived) {
            $sibling->delete();
        }
        $root = $quote->fresh()->getAttributes();
        $lines = $quote->items()->withTrashed()->orderBy('id')->get()->toArray();
        $history = app(SignQuoteRevision::class)->history($quote);
        $events = ['eloquent.saving: '.QuoteItem::class, 'eloquent.saving: '.Quote::class, 'eloquent.creating: '.ISOActivityLog::class];
        $listeners = array_intersect_key(Event::getRawListeners(), array_flip($events));
        $mutate = fn (): int => DB::table('quote_items')->where('id', $sibling->id)->update(['qty' => 777]);
        Event::listen($events[0], function (QuoteItem $line) use ($stage, $target, $mutate): void {
            if ($stage === 'line_save' && $line->id === $target->id) {
                $mutate();
            }
        });
        Event::listen($events[1], function (Quote $record) use ($stage, $quote, $mutate): void {
            if ($stage === 'signature_save' && $record->id === $quote->id && $record->isDirty('unique_hash')) {
                $mutate();
            }
        });
        Event::listen($events[2], function (ISOActivityLog $audit) use ($stage, $target, $quote, $mutate): void {
            if (($stage === 'signed_audit' && $audit->event === 'signed_revision' && (int) $audit->subject_id === $quote->id)
                || ($stage === 'correction_audit' && $audit->event === 'updated' && $audit->subject_type === $target->getMorphClass() && (int) $audit->subject_id === $target->id)) {
                $mutate();
            }
        });
        try {
            app(UpdateQuoteItem::class)->execute($user->id, $lab->id, $target->id, ['obs' => 'Correction']);
            $this->fail('A row correction changed another retained line.');
        } catch (LogicException) {
            $this->assertSame($root, $quote->fresh()->getAttributes());
            $this->assertSame($lines, $quote->items()->withTrashed()->orderBy('id')->get()->toArray());
            $this->assertSame($history, app(SignQuoteRevision::class)->history($quote));
        } finally {
            foreach ($events as $event) {
                Event::forget($event);
                foreach ($listeners[$event] ?? [] as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_legacy_sign_command_cannot_replace_a_signed_revision(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $this->payload());
        $root = $quote->getAttributes();
        $history = app(SignQuoteRevision::class)->history($quote);
        $this->artisan('app:sign-quote-with-hash', ['quote' => $quote->id])->assertSuccessful();
        $this->assertSame($root, $quote->fresh()->getAttributes());
        $this->assertSame($history, app(SignQuoteRevision::class)->history($quote));
    }

    public function test_row_correction_rejects_history_tampering_before_signing(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = app(SaveQuote::class)->execute($user->id, $lab->id, $this->payload());
        $target = $quote->items()->sole();
        $root = $quote->getAttributes();
        $line = $target->getAttributes();
        $history = app(SignQuoteRevision::class)->history($quote);
        $event = 'eloquent.saving: '.QuoteItem::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, function (QuoteItem $item) use ($target, $history): void {
            if ($item->id === $target->id) {
                DB::table('activity_log')->where('id', array_key_first($history))->delete();
            }
        });
        try {
            app(UpdateQuoteItem::class)->execute($user->id, $lab->id, $target->id, ['obs' => 'Correction']);
            $this->fail('A row correction removed retained signature history.');
        } catch (LogicException) {
            $this->assertSame($root, $quote->fresh()->getAttributes());
            $this->assertSame($line, $target->fresh()->getAttributes());
            $this->assertSame($history, app(SignQuoteRevision::class)->history($quote));
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public static function faults(): array
    {
        return [['root_veto'], ['line_veto'], ['audit_veto'], ['audit_root'], ['audit_line'], ['retire_catalog'], ['retire_customer'], ['revoke'], ['late_identity'], ['signature_veto'], ['revision_audit_veto']];
    }

    #[DataProvider('faults')]
    public function test_failed_persistence_or_final_mutation_rolls_back_entire_authoring(string $fault): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload();
        $events = ['eloquent.creating: '.Quote::class, 'eloquent.created: '.Quote::class, 'eloquent.saving: '.Quote::class,
            'eloquent.creating: '.QuoteItem::class, 'eloquent.creating: '.ISOActivityLog::class];
        $listeners = array_intersect_key(Event::getRawListeners(), array_flip($events));
        if ($fault === 'root_veto') {
            Event::listen($events[0], fn () => false);
        }
        if ($fault === 'line_veto') {
            Event::listen($events[3], fn () => false);
        }
        if ($fault === 'signature_veto') {
            Event::listen($events[2], fn (Quote $quote): ?bool => $quote->exists && $quote->isDirty('unique_hash') ? false : null);
        }
        if ($fault === 'late_identity') {
            Event::listen($events[1], function (Quote $quote): void {
                DB::table('quotes')->where('id', $quote->id)->update(['seq' => $quote->seq + 100, 'quote_no' => 'PP '.$quote->quote_month.'/'.($quote->seq + 100)]);
            });
        }
        Event::listen($events[4], function (ISOActivityLog $audit) use ($fault, $payload, $user, $lab): ?bool {
            if ($fault === 'revision_audit_veto' && $audit->event === 'signed_revision') {
                return false;
            }
            if ($audit->event !== 'authored') {
                return null;
            }
            if ($fault === 'audit_veto') {
                return false;
            }
            if ($fault === 'audit_root') {
                DB::table('quotes')->where('id', $audit->subject_id)->update(['total' => 666]);
            }
            if ($fault === 'audit_line') {
                DB::table('quote_items')->where('quote_id', $audit->subject_id)->update(['qty' => 666]);
            }
            if ($fault === 'retire_catalog') {
                Parameter::findOrFail($payload['items'][0]['item_id'])->delete();
            }
            if ($fault === 'retire_customer') {
                Customer::findOrFail($payload['customer_id'])->delete();
            }
            if ($fault === 'revoke') {
                DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->delete();
            }

            return null;
        });
        try {
            app(SaveQuote::class)->execute($user->id, $lab->id, $payload);
            $this->fail('Fault did not abort authoring: '.$fault);
        } catch (LogicException|ValidationException|AuthorizationException $exception) {
            $this->assertSame(0, Quote::query()->where('lab_id', $lab->id)->count());
            $this->assertSame(0, QuoteItem::withoutGlobalScopes()->where('lab_id', $lab->id)->count());
            $this->assertTrue(Customer::query()->whereKey($payload['customer_id'])->exists());
            $this->assertTrue(DB::table('lab_user')->where('lab_id', $lab->id)->where('user_id', $user->id)->exists());
        } finally {
            foreach ($events as $event) {
                Event::forget($event);
                foreach ($listeners[$event] ?? [] as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
        $this->assertNotNull(app(SaveQuote::class)->execute($user->id, $lab->id, $payload)->unique_hash);
    }
}
