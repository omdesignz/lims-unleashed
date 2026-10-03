<?php

namespace Tests\Feature;

use App\Actions\UpdateFinancialDocumentObservation;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceCategory;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\ISOActivityLog;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use App\Services\FinancialDocumentAssembly;
use App\Support\DocumentSignature;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model;
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

class IssuedFinancialDocumentIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array<string, array{class-string<Model>, string, string}> */
    public static function documents(): array
    {
        return [
            'invoice' => [Invoice::class, 'invoices', 'edit_invoices'],
            'credit note' => [CreditNote::class, 'creditnotes', 'edit_credit_notes'],
            'receipt' => [Receipt::class, 'receipts', 'edit_receipts'],
        ];
    }

    private function operator(VAPLab $lab, string $permission): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user;
    }

    /** @param class-string<Model> $class */
    private function document(string $class, VAPLab $lab, User $user): Model
    {
        $site = Warehouse::create(['name' => 'Issued '.Str::uuid(), 'customer_id' => Customer::create(['name' => 'Shared customer'])->id]);
        $record = new $class(['user_id' => $user->id, 'customer_id' => $site->customer_id,
            'warehouse_id' => $site->id, 'date' => now()->toDateString(), 'obs' => 'Before', 'unique_hash' => 'issued-signature']);
        $record->lab_id = $lab->id;
        $record->forceFill(match ($class) {
            Invoice::class => ['inv_no' => 'LOCK-'.Str::uuid(), 'invoice_month' => now()->format('Y'), 'total' => '10.00', 'amount_due' => '10.00'],
            CreditNote::class => ['note_no' => 'LOCK-'.Str::uuid(), 'note_month' => now()->format('Y'), 'reason' => 'R', 'total' => '10.00'],
            Receipt::class => ['rec_no' => 'LOCK-'.Str::uuid(), 'rec_month' => now()->format('Y')],
        })->saveQuietly();

        return $record->fresh();
    }

    #[DataProvider('documents')]
    public function test_observation_editor_has_a_minimal_payload_without_optional_or_archived_relation_dependencies(string $class, string $routes, string $permission): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, $permission);
        $record = $this->document($class, $lab, $user);
        $number = $record->getAttribute(match ($class) {
            Invoice::class => 'inv_no', CreditNote::class => 'note_no', Receipt::class => 'rec_no',
        });
        if ($record instanceof Invoice) {
            $line = new InvoiceItem(['invoice_id' => $record->id, 'qty' => 1]);
            $line->lab_id = $lab->id;
            $line->saveQuietly();
        }
        $record->customer->delete();
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route($routes.'.edit', $record->id))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('record', function ($payload) use ($record, $number): bool {
                $data = collect($payload)->all();
                $data = isset($data['data']) ? collect($data['data'])->all() : $data;

                return $data === ['id' => $record->id, 'document_no' => $number, 'obs' => 'Before'];
            }));
    }

    #[DataProvider('documents')]
    public function test_http_correction_changes_only_observations_and_records_a_subject_audit(string $class, string $routes, string $permission): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, $permission);
        $record = $this->document($class, $lab, $user);
        $before = Arr::except($record->getAttributes(), ['obs', 'updated_at']);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->putJson(route($routes.'.update', $record->id), ['obs' => 'Corrected'])->assertRedirect();
        $this->assertSame('Corrected', $record->fresh()->obs);
        $this->assertSame($before, Arr::except($record->fresh()->getAttributes(), ['obs', 'updated_at']));
        $this->assertTrue(ISOActivityLog::withoutGlobalScopes()->where('subject_type', $record->getMorphClass())
            ->where('subject_id', $record->id)->where('event', 'updated')->exists());
        $this->putJson(route($routes.'.update', $record->id), ['obs' => null])->assertRedirect();
        $this->assertNull($record->fresh()->obs);
    }

    #[DataProvider('documents')]
    public function test_http_rejects_core_payload_even_when_empty_and_preserves_every_attribute(string $class, string $routes, string $permission): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, $permission);
        $record = $this->document($class, $lab, $user);
        $before = $record->getAttributes();
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        foreach (['customer_id', 'warehouse_id', 'total', 'tax', 'items', 'unique_hash', 'date', 'seq', 'status'] as $field) {
            $this->putJson(route($routes.'.update', $record->id), ['obs' => 'Forged', $field => null])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame($before, $record->fresh()->getAttributes());
        }
        $this->putJson(route($routes.'.update', $record->id), [])->assertUnprocessable()->assertJsonValidationErrors('obs');
        $this->putJson(route($routes.'.update', $record->id), ['obs' => str_repeat('x', 5001)])
            ->assertUnprocessable()->assertJsonValidationErrors('obs');
    }

    #[DataProvider('documents')]
    public function test_peer_documents_and_revoked_membership_cannot_be_corrected(string $class, string $routes, string $permission): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, $permission);
        $local = $this->document($class, $lab, $user);
        $peer = $this->document($class, VAPLab::factory()->create(), $user);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->putJson(route($routes.'.update', $peer->id), ['obs' => 'Forged'])->assertNotFound();
        $user->revokePermissionTo($permission);
        $this->putJson(route($routes.'.update', $local->id), ['obs' => 'Forged'])->assertForbidden();
        $user->givePermissionTo($permission);
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->putJson(route($routes.'.update', $local->id), ['obs' => 'Forged'])->assertForbidden();
        $this->assertSame('Before', $local->fresh()->obs);
        $this->assertSame('Before', $peer->fresh()->obs);
    }

    #[DataProvider('documents')]
    public function test_eventful_model_writes_cannot_change_issued_identity_or_core(string $class, string $routes, string $permission): void
    {
        $lab = VAPLab::factory()->create();
        $record = $this->document($class, $lab, $this->operator($lab, $permission));
        foreach (['date' => '2000-01-01', 'customer_id' => null, 'unique_hash' => 'replacement', 'seq' => 123456] as $field => $value) {
            try {
                $record->fresh()->forceFill([$field => $value])->save();
                $this->fail('Issued core was changed.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }
        $this->assertTrue($record->update(['obs' => 'Allowed']));
        $this->assertSame('Allowed', $record->fresh()->obs);
    }

    public function test_signed_lines_cannot_be_repriced_deleted_or_added_and_invoice_line_observations_remain_editable(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'edit_invoices');
        $invoice = $this->document(Invoice::class, $lab, $user);
        $note = $this->document(CreditNote::class, $lab, $user);
        $receipt = $this->document(Receipt::class, $lab, $user);
        foreach ([[InvoiceItem::class, ['invoice_id' => $invoice->id, 'qty' => 1, 'total' => 10], 'total'],
            [CreditNoteItem::class, ['note_id' => $note->id, 'qty' => 1, 'total' => 10], 'total'],
            [InvoiceReceipt::class, ['invoice_id' => $invoice->id, 'receipt_id' => $receipt->id, 'paid_amount' => 10], 'paid_amount']] as [$class, $attributes, $amount]) {
            $line = new $class($attributes);
            $line->lab_id = $lab->id;
            $line->saveQuietly();
            foreach (['update', 'delete', 'force_delete', 'archive_update', 'create'] as $operation) {
                try {
                    match ($operation) {
                        'update' => $line->fresh()->update([$amount => 999]),
                        'delete' => $line->fresh()->delete(),
                        'force_delete' => $line->fresh()->forceDelete(),
                        'archive_update' => $line->fresh()->forceFill(['deleted_at' => now()])->save(),
                        'create' => $class::create($attributes),
                    };
                    $this->fail('Issued lines were mutated.');
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey(match ($operation) {
                        'update' => $amount, 'archive_update' => 'deleted_at', default => 'items',
                    }, $exception->errors());
                }
            }
            $this->assertTrue($line->fresh()->update(['obs' => 'Allowed line correction']));
            if ($line instanceof InvoiceItem) {
                $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
                $this->putJson(route('invoiceitems.update', $line->id), ['obs' => 'HTTP line correction'])->assertRedirect();
                $this->assertSame('HTTP line correction', $line->fresh()->obs);
                $this->putJson(route('invoiceitems.update', $line->id), ['obs' => 'Forged', 'total' => 100])
                    ->assertUnprocessable()->assertJsonValidationErrors('total');
                request()->attributes->remove('proposal_laboratory_id');
            }
            $line->forceFill(['deleted_at' => now()])->saveQuietly();
            try {
                $class::withTrashed()->findOrFail($line->id)->restore();
                $this->fail('A historical issued line was restored through an ordinary write.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('deleted_at', $exception->errors());
                $this->assertNotNull($class::withTrashed()->findOrFail($line->id)->deleted_at);
            }
        }
    }

    public function test_transactional_writer_permissions_are_narrow_and_cleaned_up_after_exceptions(): void
    {
        $lab = VAPLab::factory()->create();
        $invoice = $this->document(Invoice::class, $lab, $this->operator($lab, 'edit_invoices'));
        $assembly = app(FinancialDocumentAssembly::class);
        try {
            $assembly->withLines($invoice, fn () => null);
            $this->fail('An existing invoice reopened line assembly.');
        } catch (LogicException) {
            $this->assertFalse($assembly->allowsLines($invoice));
        }
        DB::transaction(function () use ($invoice, $assembly): void {
            $this->mock(DocumentSignature::class)->shouldReceive('sign')->andReturn('test-issued-signature');
            $category = InvoiceCategory::firstOrCreate(['code' => 'FT'], ['name' => 'Factura']);
            $newInvoice = $invoice->replicate(['inv_no', 'seq', 'unique_hash']);
            $newInvoice->type_id = $category->id;
            $newInvoice->save();
            $invoice = $newInvoice;
            try {
                $assembly->withLines($invoice, function () use ($assembly, $invoice): void {
                    $this->assertTrue($assembly->allowsLines($invoice));
                    throw new LogicException('Simulated failure');
                });
            } catch (LogicException) {
                $this->assertFalse($assembly->allowsLines($invoice));
            }
            try {
                $assembly->withInvoiceState($invoice, ['amount_due'], function () use ($assembly, $invoice): void {
                    $this->assertTrue($assembly->allowsInvoiceState($invoice, 'amount_due'));
                    $this->assertFalse($assembly->allowsInvoiceState($invoice, 'total'));
                    throw new LogicException('Simulated failure');
                });
            } catch (LogicException) {
                $this->assertFalse($assembly->allowsInvoiceState($invoice, 'amount_due'));
            }
            $this->expectException(LogicException::class);
            $assembly->withInvoiceState($invoice, ['total'], fn () => null);
        });
    }

    public function test_line_assembly_creation_context_expires_on_real_root_commit_and_rollback(): void
    {
        config(['database.connections.financial_integrity_boundary' => config('database.connections.pgsql')]);
        $connection = DB::connection('financial_integrity_boundary');
        $transactions = new DatabaseTransactionsManager;
        $connection->setTransactionManager($transactions);
        $this->assertSame('lims_unleashed_test', $connection->getDatabaseName());
        $this->assertSame(0, $connection->transactionLevel());
        $assembly = new FinancialDocumentAssembly($transactions);
        try {
            foreach ([[1, 'commit', 0], [1, 'rollback', 0], [2, 'rollback', 0], [3, 'rollback', 1]] as [$depth, $boundary, $remaining]) {
                $document = new Invoice;
                $document->setConnection('financial_integrity_boundary');
                $document->id = 123;
                $document->wasRecentlyCreated = true;
                while ($connection->transactionLevel() < $depth) {
                    $connection->beginTransaction();
                }
                $assembly->rememberCreation($document);
                $assembly->withLines($document, function () use ($assembly, $document): void {
                    $this->assertTrue($assembly->allowsLines($document));
                });
                if ($depth > 1) {
                    $connection->commit();
                }
                $boundary === 'commit' ? $connection->commit() : $connection->rollBack($remaining);
                $connection->beginTransaction();
                try {
                    $assembly->withLines($document, fn () => null);
                    $this->fail('A retained creation context reopened issued assembly.');
                } catch (LogicException) {
                    $this->assertFalse($assembly->allowsLines($document));
                } finally {
                    $connection->rollBack();
                }
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            }
            $document = new Invoice;
            $document->setConnection('financial_integrity_boundary');
            $document->id = 124;
            $document->wasRecentlyCreated = true;
            $assembly->rememberCreation($document);
            $connection->beginTransaction();
            try {
                $assembly->withLines($document, fn () => null);
                $this->fail('Nontransactional creation reopened issued assembly.');
            } catch (LogicException) {
                $this->assertFalse($assembly->allowsLines($document));
            } finally {
                $connection->rollBack();
            }
        } finally {
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            DB::purge('financial_integrity_boundary');
        }
    }

    public function test_cash_invoice_and_credit_note_can_initialize_sign_and_assemble_lines_without_reopening_existing_documents(): void
    {
        DB::transaction(function (): void {
            $this->mock(DocumentSignature::class)->shouldReceive('sign')->andReturn('test-issued-signature');
            $lab = VAPLab::factory()->create();
            $user = $this->operator($lab, 'edit_invoices');
            $template = $this->document(Invoice::class, $lab, $user);
            $category = InvoiceCategory::firstOrCreate(['code' => 'FR'], ['name' => 'Factura-Recibo']);
            $invoice = $template->replicate(['inv_no', 'seq', 'unique_hash']);
            $invoice->type_id = $category->id;
            $invoice->save();
            $this->assertTrue($invoice->fresh()->status);
            $this->assertSame('test-issued-signature', $invoice->fresh()->unique_hash);
            $assembly = app(FinancialDocumentAssembly::class);
            $assembly->withLines($invoice, function () use ($invoice): void {
                InvoiceItem::create(['invoice_id' => $invoice->id, 'qty' => 1, 'total' => 10]);
            });
            $this->assertSame(1, $invoice->items()->count());
            $this->assertFalse($assembly->allowsLines($invoice));

            $note = $this->document(CreditNote::class, $lab, $user)->replicate(['note_no', 'seq', 'unique_hash']);
            $note->invoice_id = $invoice->id;
            $note->customer_id = $invoice->customer_id;
            $note->warehouse_id = $invoice->warehouse_id;
            $note->total = '2.00';
            $note->save();
            $this->assertSame('test-issued-signature', $note->fresh()->unique_hash);
            $this->assertSame('8.00', $invoice->fresh()->amount_due);
            $assembly->withLines($note, function () use ($note): void {
                CreditNoteItem::create(['note_id' => $note->id, 'qty' => 1, 'total' => 2]);
            });
            $this->assertSame(1, $note->items()->count());
            $this->assertFalse($assembly->allowsInvoiceState($invoice, 'amount_due'));
        });
    }

    public function test_save_veto_audit_veto_and_lifecycle_mutation_roll_back_observation_correction(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'edit_invoices');
        $invoice = $this->document(Invoice::class, $lab, $user);
        foreach (['save', 'audit', 'core', 'observation', 'audit_core', 'audit_observation'] as $failure) {
            $event = match ($failure) {
                'save' => 'eloquent.updating: '.Invoice::class,
                'audit', 'audit_core', 'audit_observation' => 'eloquent.creating: '.ISOActivityLog::class,
                default => 'eloquent.updated: '.Invoice::class,
            };
            $listeners = Event::getRawListeners()[$event] ?? [];
            Event::listen($event, function (Model $model) use ($failure, $invoice): ?bool {
                if (in_array($failure, ['audit_core', 'audit_observation'], true)
                    && $model->description !== 'Corrigiu as observações de um documento financeiro emitido.') {
                    return null;
                }
                if (in_array($failure, ['save', 'audit'], true)) {
                    return false;
                }
                DB::table('invoices')->where('id', $invoice->id)->update(in_array($failure, ['core', 'audit_core'], true) ? ['total' => 999] : ['obs' => 'Altered by listener']);

                return null;
            });
            try {
                app(UpdateFinancialDocumentObservation::class)->execute($user->id, $lab->id, Invoice::class, $invoice->id, 'Corrected');
                $this->fail('A failed correction committed.');
            } catch (LogicException) {
                $this->assertSame('Before', $invoice->fresh()->obs);
                $this->assertSame('10.00', $invoice->fresh()->total);
                $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('subject_id', $invoice->id)->where('event', 'updated')->count());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }

    }

    public function test_access_revoked_during_explicit_audit_rolls_back_correction_and_audit(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'edit_invoices');
        $invoice = $this->document(Invoice::class, $lab, $user);
        foreach (['membership', 'permission', 'account'] as $revocation) {
            $event = 'eloquent.creating: '.ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            Event::listen($event, function (Model $audit) use ($user, $revocation): void {
                if ($audit->description !== 'Corrigiu as observações de um documento financeiro emitido.') {
                    return;
                }
                match ($revocation) {
                    'membership' => DB::table('lab_user')->where('user_id', $user->id)->delete(),
                    'permission' => DB::table('model_has_permissions')->where('model_id', $user->id)->delete(),
                    'account' => DB::table('users')->where('id', $user->id)->update(['is_active' => false]),
                };
            });
            try {
                app(UpdateFinancialDocumentObservation::class)->execute($user->id, $lab->id, Invoice::class, $invoice->id, 'Corrected');
                $this->fail('A revoked operator committed a correction.');
            } catch (AuthorizationException) {
                $this->assertSame('Before', $invoice->fresh()->obs);
                $this->assertSame(0, ISOActivityLog::withoutGlobalScopes()->where('subject_id', $invoice->id)->where('event', 'updated')->count());
                $this->assertTrue($user->fresh()->is_active);
                $this->assertTrue($user->fresh()->can('edit_invoices'));
                $this->assertTrue(DB::table('lab_user')->where('user_id', $user->id)->exists());
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }
}
