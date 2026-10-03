<?php

namespace Tests\Feature;

use App\Actions\SaveTradeCertificate;
use App\Actions\UpdateFinancialDocumentObservation;
use App\Models\Country;
use App\Models\Customer;
use App\Models\ExportCertificate;
use App\Models\ExportCertificateItem;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\ISOActivityLog;
use App\Models\PhytosanitaryProduct;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\TransportCategory;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TradeCertificateAuthoringTest extends TestCase
{
    use DatabaseTransactions;

    public static function kinds(): array
    {
        return ['import' => ['import', ImportCertificate::class, ImportCertificateItem::class],
            'export' => ['export', ExportCertificate::class, ExportCertificateItem::class]];
    }

    private function operator(VAPLab $lab): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['add_import_certificates', 'edit_import_certificates', 'add_export_certificates', 'edit_export_certificates', 'edit_quotes'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function payload(string $kind): array
    {
        $customer = Customer::create(['name' => 'Shared customer']);
        $site = Warehouse::create(['name' => 'Shared site '.Str::uuid(), 'customer_id' => $customer->id]);
        $country = Country::create(['name' => 'Angola', 'code' => 'AO', 'phone_code' => '244']);
        $transport = TransportCategory::create(['name' => 'Air '.Str::uuid()]);
        $product = PhytosanitaryProduct::create(['name' => 'Test product '.Str::uuid()]);

        return ['date' => now()->toDateString(), 'exporter_id' => $customer->id, 'exporter_warehouse_id' => $site->id,
            'authorized_personnel' => 'Inspector', 'trans_type_id' => $transport->id, 'obs' => 'Before',
            'items' => [['product_id' => ['value' => $product->id], 'qty' => '1.25']],
            ...($kind === 'import' ? ['importer_id' => ['value' => $customer->id], 'importer_warehouse_id' => ['value' => $site->id],
                'destination_country_id' => $country->id, 'port_exit' => 'Exit', 'port_entry' => 'Entry',
                'vat' => '14.00', 'vat_cost' => '1.40', 'cost_freight' => '1.00', 'cost_insurance' => '1.00', 'cost_final' => '10.00']
                : ['country_origin_id' => $country->id, 'country_destination_id' => $country->id,
                    'origin_city' => 'Luanda', 'destination_city' => 'Lisbon', 'expedition_date' => now()->toDateString(), 'expedition_location' => 'Lab'])];
    }

    #[DataProvider('kinds')]
    public function test_authoring_numbers_by_lab_year_and_kind_and_retains_replaced_lines(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $action = app(SaveTradeCertificate::class);
        $this->travelTo(now()->startOfSecond());
        $first = $action->execute($user->id, $lab->id, $kind, $payload);
        $second = $action->execute($user->id, $lab->id, $kind, $payload);
        $this->assertSame(1, (int) $first->seq);
        $this->assertSame(2, (int) $second->seq);
        $this->assertNotSame($first->cert_no, $second->cert_no);
        $this->assertSame(strtoupper(substr($kind, 0, 3)).'-'.now()->year.'-L'.$lab->id.'-00001', $first->cert_no);
        $this->assertSame($user->id, $first->user_id);
        $line = $first->items()->sole();
        $original = Arr::except($line->getAttributes(), ['deleted_at', 'updated_at']);
        $editor = $this->operator($lab);
        $updated = $action->execute($editor->id, $lab->id, $kind, [...$payload, 'obs' => 'Corrected', 'items' => [['product_id' => $payload['items'][0]['product_id'], 'qty' => '2.50', 'lab_id' => 999, 'certificate_id' => $second->id]]], $first->id);
        $this->assertSame($first->cert_no, $updated->cert_no);
        $this->assertSame($user->id, $updated->user_id);
        $this->assertTrue($line->fresh()->trashed());
        $this->assertSame($original, Arr::except($line->fresh()->getAttributes(), ['deleted_at', 'updated_at']));
        $this->assertSame(2, $updated->items()->withTrashed()->count());
        $this->assertSame('2.50', $updated->items()->sole()->qty);
        $this->assertSame($lab->id, $updated->items()->sole()->lab_id);
        $peer = VAPLab::factory()->create();
        $peerUser = $this->operator($peer);
        $this->assertSame(1, (int) $action->execute($peerUser->id, $peer->id, $kind, $payload)->seq);
        $this->travelTo(now()->addYear());
        $this->assertSame(1, (int) $action->execute($user->id, $lab->id, $kind, $payload)->seq);
        $this->travelBack();
    }

    #[DataProvider('kinds')]
    public function test_billed_core_and_lines_are_locked_but_observations_and_archive_remain_available(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $action = app(SaveTradeCertificate::class);
        $record = $action->execute($user->id, $lab->id, $kind, $payload);
        $line = $record->items()->sole();
        $record->forceFill(['invoiced' => true])->save();
        $rootBefore = Arr::except($record->fresh()->getAttributes(), ['obs', 'updated_at']);
        $linesBefore = $line->fresh()->getAttributes();
        try {
            $action->execute($user->id, $lab->id, $kind, [...$payload, 'obs' => 'Rejected'], $record->id);
            $this->fail('Billed core was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }
        $action->execute($user->id, $lab->id, $kind, ['obs' => 'Allowed'], $record->id);
        $this->assertSame('Allowed', $record->fresh()->obs);
        $this->assertSame($rootBefore, Arr::except($record->fresh()->getAttributes(), ['obs', 'updated_at']));
        $this->assertSame($linesBefore, $line->fresh()->getAttributes());
        foreach ([fn () => $record->fresh()->update(['date' => '2000-01-01']), fn () => $line->fresh()->update(['qty' => 9]),
            fn () => $line->fresh()->delete(), fn () => (new $lineClass)->forceFill(['certificate_id' => $record->id, 'lab_id' => $lab->id, 'qty' => 1])->save()] as $mutation) {
            try {
                $mutation();
                $this->fail('Billed source mutation succeeded.');
            } catch (ValidationException) {
                $this->assertSame($linesBefore, $line->fresh()->getAttributes());
            }
        }
        $record->delete();
        $record->restore();
        $this->assertSame($rootBefore, Arr::except($record->fresh()->getAttributes(), ['obs', 'updated_at']));
    }

    #[DataProvider('kinds')]
    public function test_invalid_input_and_forged_identity_are_rejected_without_writes(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $action = app(SaveTradeCertificate::class);
        $invalid = [['user_id' => $user->id], ['lab_id' => $lab->id], ['cert_no' => 'FAKE'], ['seq' => 99], ['certificate_year' => 1999],
            ['invoice_id' => null], ['invoiced' => false], ['exporter_id' => []], ['exporter_warehouse_id' => $this->payload($kind)['exporter_warehouse_id']],
            ['items' => []], ['items' => 'malformed'], ['items' => ['not-a-list' => $payload['items'][0]]], ['items' => [null]],
            ['items' => [['product_id' => [], 'qty' => 1]]], ['items' => [['product_id' => 99999999, 'qty' => 1]]]];
        foreach ([0, -1, '1.001', '1e2', '100000000.00'] as $quantity) {
            $invalid[] = ['items' => [[...$payload['items'][0], 'qty' => $quantity]]];
        }
        foreach ($invalid as $override) {
            try {
                $action->execute($user->id, $lab->id, $kind, [...$payload, ...$override]);
                $this->fail('Invalid certificate was accepted: '.json_encode($override));
            } catch (ValidationException) {
                $this->assertSame(0, $class::withoutGlobalScopes()->count());
                $this->assertSame(0, $lineClass::withoutGlobalScopes()->count());
            }
        }
    }

    #[DataProvider('kinds')]
    public function test_authority_and_owner_are_rechecked(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $action = app(SaveTradeCertificate::class);
        $record = $action->execute($user->id, $lab->id, $kind, $payload);
        $peer = VAPLab::factory()->create();
        $peerUser = $this->operator($peer);
        try {
            $action->execute($peerUser->id, $peer->id, $kind, $payload, $record->id);
            $this->fail('Another laboratory source was editable.');
        } catch (ModelNotFoundException) {
            $this->assertSame('Before', $record->fresh()->obs);
        }
        $user->revokePermissionTo('edit_'.$kind.'_certificates');
        try {
            $action->execute($user->id, $lab->id, $kind, $payload, $record->id);
            $this->fail('Revoked permission remained usable.');
        } catch (AuthorizationException) {
            $this->assertSame('Before', $record->fresh()->obs);
        }
    }

    #[DataProvider('kinds')]
    public function test_save_line_audit_veto_mutation_and_revocation_roll_back_entire_authoring(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        foreach (['root_veto', 'root_mutation', 'number_mutation', 'line_veto', 'line_mutation', 'audit_veto', 'audit_identity', 'audit_root', 'audit_line', 'revoke'] as $failure) {
            $event = 'eloquent.creating: '.match ($failure) {
                'root_veto', 'root_mutation', 'number_mutation' => $class,
                'line_veto', 'line_mutation' => $lineClass,
                default => ISOActivityLog::class,
            };
            $listeners = Event::getRawListeners()[$event] ?? [];
            Event::listen($event, function (Model $model) use ($failure, $class, $lineClass, $user): ?bool {
                if ($model instanceof ISOActivityLog && $model->event !== 'authored') {
                    return null;
                }
                if (str_ends_with($failure, '_veto')) {
                    return false;
                }
                match ($failure) {
                    'root_mutation' => $model->obs = 'Mutated',
                    'number_mutation' => $model->forceFill(['seq' => 999, 'cert_no' => ($model instanceof ImportCertificate ? 'IMP' : 'EXP').'-'.$model->certificate_year.'-L'.$model->lab_id.'-00999']),
                    'line_mutation' => $model->qty = 99,
                    'audit_identity' => $model->causer_id = 99999999,
                    'audit_root' => DB::table((new $class)->getTable())->update(['obs' => 'Mutated']),
                    'audit_line' => DB::table((new $lineClass)->getTable())->update(['qty' => 99]),
                    'revoke' => DB::table('lab_user')->where('user_id', $user->id)->delete(),
                };

                return null;
            });
            try {
                app(SaveTradeCertificate::class)->execute($user->id, $lab->id, $kind, $payload);
                $this->fail('Failed authoring committed: '.$failure);
            } catch (LogicException|AuthorizationException) {
                $this->assertSame(0, $class::withoutGlobalScopes()->count());
                $this->assertSame(0, $lineClass::withoutGlobalScopes()->count());
                $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('event', 'authored')->count());
                $this->assertSame(0, DB::table('sequence_counters')->where('table_name', (new $class)->getTable())->count());
                $this->assertTrue(DB::table('lab_user')->where('user_id', $user->id)->exists());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_billed_quote_observations_preserve_all_lines_and_reject_core_edits(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = new Quote(['quote_month' => now()->year, 'user_id' => $user->id, 'obs' => 'Before']);
        $quote->lab_id = $lab->id;
        $quote->save();
        $line = QuoteItem::create(['quote_id' => $quote->id, 'qty' => 1]);
        $quote->forceFill(['converted_to_invoice' => true])->save();
        $before = $line->fresh()->getAttributes();
        app(UpdateFinancialDocumentObservation::class)->execute($user->id, $lab->id, Quote::class, $quote->id, 'Allowed');
        $this->assertSame($before, $line->fresh()->getAttributes());
        $this->assertSame('Allowed', $quote->fresh()->obs);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->putJson(route('quotes.update', $quote->id), ['total' => 999, 'obs' => 'Denied'])->assertUnprocessable()->assertJsonValidationErrors('total');
        $this->put(route('quotes.update', $quote->id), ['obs' => 'HTTP allowed'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('HTTP allowed', $quote->fresh()->obs);
        $this->get(route('quotes.edit', $quote->id))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Quotes/Edit')
            ->where('record', ['id' => $quote->id, 'document_no' => $quote->quote_no, 'obs' => 'HTTP allowed', 'invoice_id' => null, 'converted_to_invoice' => true]));
        foreach ([fn () => $quote->fresh()->update(['total' => 9]), fn () => $line->fresh()->update(['qty' => 2]), fn () => $line->fresh()->delete(),
            fn () => QuoteItem::create(['quote_id' => $quote->id, 'qty' => 1])] as $mutation) {
            try {
                $mutation();
                $this->fail('Billed quote mutation succeeded.');
            } catch (ValidationException) {
                $this->assertSame($before, $line->fresh()->getAttributes());
            }
        }
    }

    public function test_billed_quote_correction_rolls_back_changed_audit_identity_or_retained_lines(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $quote = new Quote(['quote_month' => now()->year, 'user_id' => $user->id, 'obs' => 'Before']);
        $quote->lab_id = $lab->id;
        $quote->save();
        $line = QuoteItem::create(['quote_id' => $quote->id, 'qty' => 1]);
        $quote->forceFill(['converted_to_invoice' => true])->save();
        $before = $line->fresh()->getAttributes();
        foreach (['audit', 'line'] as $failure) {
            $auditsBefore = ISOActivityLog::withoutGlobalScopes()->where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)
                ->orderBy('id')->get()->map->getAttributes()->all();
            $event = 'eloquent.creating: '.ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            Event::listen($event, function (Model $audit) use ($failure, $line): void {
                if ($audit->description !== 'Corrigiu as observações de um documento financeiro emitido.') {
                    return;
                }
                if ($failure === 'audit') {
                    $audit->causer_id = 99999999;
                } else {
                    DB::table('quote_items')->where('id', $line->id)->update(['qty' => 9]);
                }
            });
            try {
                app(UpdateFinancialDocumentObservation::class)->execute($user->id, $lab->id, Quote::class, $quote->id, 'Rejected');
                $this->fail('A changed quote correction committed.');
            } catch (LogicException) {
                $this->assertSame('Before', $quote->fresh()->obs);
                $this->assertSame($before, $line->fresh()->getAttributes());
                $this->assertSame($auditsBefore, ISOActivityLog::withoutGlobalScopes()->where('subject_type', $quote->getMorphClass())->where('subject_id', $quote->id)
                    ->orderBy('id')->get()->map->getAttributes()->all());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    #[DataProvider('kinds')]
    public function test_directory_retirement_during_audit_rolls_back_authoring(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $productId = $payload['items'][0]['product_id']['value'];
        $event = 'eloquent.creating: '.ISOActivityLog::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, function (Model $audit) use ($productId): void {
            if ($audit->event === 'authored') {
                PhytosanitaryProduct::findOrFail($productId)->delete();
            }
        });
        try {
            app(SaveTradeCertificate::class)->execute($user->id, $lab->id, $kind, $payload);
            $this->fail('A retired catalogue product was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
            $this->assertSame(0, $class::count());
            $this->assertSame(0, $lineClass::count());
            $this->assertNotNull(PhytosanitaryProduct::find($productId));
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    #[DataProvider('kinds')]
    public function test_failed_line_retirement_or_audit_rolls_back_edits_and_preserves_every_old_line(string $kind, string $class, string $lineClass): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $payload = $this->payload($kind);
        $record = app(SaveTradeCertificate::class)->execute($user->id, $lab->id, $kind, $payload);
        $root = $record->getAttributes();
        $lines = $record->items()->withTrashed()->get()->map->getAttributes()->all();
        foreach (['retirement', 'late_line', 'audit'] as $failure) {
            $event = $failure === 'retirement' ? 'eloquent.deleting: '.$lineClass : 'eloquent.creating: '.ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            Event::listen($event, function (Model $model) use ($failure, $lineClass, $record): ?bool {
                if ($failure !== 'retirement' && $model->event !== 'authored') {
                    return null;
                }
                if ($failure === 'late_line') {
                    DB::table((new $lineClass)->getTable())->where('certificate_id', $record->id)->update(['qty' => 99]);

                    return null;
                }

                return false;
            });
            try {
                app(SaveTradeCertificate::class)->execute($user->id, $lab->id, $kind, [...$payload, 'obs' => 'Rejected'], $record->id);
                $this->fail('A partial certificate edit committed.');
            } catch (LogicException) {
                $this->assertSame($root, $record->fresh()->getAttributes());
                $this->assertSame($lines, $record->items()->withTrashed()->get()->map->getAttributes()->all());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }
}
