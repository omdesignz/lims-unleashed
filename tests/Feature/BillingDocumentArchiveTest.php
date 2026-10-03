<?php

namespace Tests\Feature;

use App\Actions\SetBillingDocumentsArchived;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\InvoiceReceipt;
use App\Models\ISOActivityLog;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BillingDocumentArchiveTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array<string, array{class-string<Model>, string, string}> */
    public static function documents(): array
    {
        return [
            'invoice' => [Invoice::class, 'invoices', 'invoices'],
            'credit note' => [CreditNote::class, 'creditnotes', 'credit_notes'],
            'receipt' => [Receipt::class, 'receipts', 'receipts'],
            'quote' => [Quote::class, 'quotes', 'quotes'],
            'import certificate' => [ImportCertificate::class, 'importcertificates', 'import_certificates'],
            'export certificate' => [ExportCertificate::class, 'exportcertificates', 'export_certificates'],
        ];
    }

    private function operator(VAPLab $lab, string $module): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach (['delete_', 'restore_'] as $prefix) {
            $user->givePermissionTo(Permission::findOrCreate($prefix.$module, 'web'));
        }

        return $user;
    }

    /** @param class-string<Model> $class */
    private function document(string $class, VAPLab $lab, User $user): Model
    {
        $site = Warehouse::create(['name' => 'Archive '.Str::uuid(), 'customer_id' => Customer::create(['name' => 'Shared customer'])->id]);
        $record = new $class;
        $record->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'date' => now()->toDateString(), ...match ($class) {
            Invoice::class => ['inv_no' => 'ARCHIVE-'.Str::uuid(), 'invoice_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'total' => '20.00', 'amount_due' => '12.00', 'unique_hash' => 'signature'],
            CreditNote::class => ['note_no' => 'ARCHIVE-'.Str::uuid(), 'note_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'reason' => 'Correction', 'total' => '8.00', 'unique_hash' => 'signature'],
            Receipt::class => ['rec_no' => 'ARCHIVE-'.Str::uuid(), 'rec_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'unique_hash' => 'signature'],
            Quote::class => ['quote_no' => 'ARCHIVE-'.Str::uuid(), 'quote_month' => now()->format('Y'), 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id, 'total' => '20.00'],
            ImportCertificate::class => ['cert_no' => 'ARCHIVE-'.Str::uuid(), 'importer_id' => $site->customer_id, 'importer_warehouse_id' => $site->id],
            ExportCertificate::class => ['cert_no' => 'ARCHIVE-'.Str::uuid(), 'exporter_id' => $site->customer_id, 'exporter_warehouse_id' => $site->id],
        }])->saveQuietly();

        return $record->fresh();
    }

    private function line(Model $record): Model
    {
        $line = $record->items()->make(['qty' => 1]);
        $line->lab_id = $record->lab_id;
        if ($line instanceof InvoiceReceipt) {
            $invoice = new Invoice;
            $invoice->forceFill(['lab_id' => $record->lab_id, 'user_id' => $record->user_id,
                'customer_id' => $record->customer_id, 'warehouse_id' => $record->warehouse_id,
                'inv_no' => 'ALLOCATION-'.Str::uuid(), 'invoice_month' => now()->format('Y'), 'amount_due' => '12.00'])->saveQuietly();
            $line->forceFill(['invoice_id' => $invoice->id, 'paid_amount' => '8.00', 'pending_amount' => '12.00']);
            unset($line->qty);
        }
        $line->saveQuietly();

        return $line->fresh();
    }

    private function auditCount(Model $record): int
    {
        return ISOActivityLog::withoutGlobalScopes()->where('subject_type', $record->getMorphClass())
            ->where('subject_id', $record->id)->where('properties->billing_archive', true)->count();
    }

    #[DataProvider('documents')]
    public function test_non_get_archive_restore_is_idempotent_and_preserves_issued_content_and_lines(string $class, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, $module);
        $record = $this->document($class, $lab, $user);
        $line = $this->line($record);
        $before = Arr::except($record->getAttributes(), ['deleted_at', 'updated_at']);
        $lineBefore = $line->getAttributes();
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);

        foreach (['destroy', 'restore'] as $route) {
            $this->getJson(route($routes.'.'.$route, ['recordIds' => [$record->id]]))->assertStatus(405);
        }
        $this->deleteJson(route($routes.'.destroy'), ['recordIds' => [$record->id]])->assertRedirect();
        $archived = $record->newQueryWithoutScopes()->findOrFail($record->id);
        $this->assertTrue($archived->trashed());
        $this->assertSame($before, Arr::except($archived->getAttributes(), ['deleted_at', 'updated_at']));
        $this->assertSame($lineBefore, $line->newQueryWithoutScopes()->findOrFail($line->id)->getAttributes());
        $this->assertSame(1, $this->auditCount($record));
        $this->deleteJson(route($routes.'.destroy'), ['recordIds' => [$record->id]])->assertRedirect();
        $this->assertSame($archived->getAttributes(), $record->newQueryWithoutScopes()->findOrFail($record->id)->getAttributes());
        $this->assertSame(1, $this->auditCount($record));

        $this->patchJson(route($routes.'.restore'), ['recordIds' => [$record->id]])->assertRedirect();
        $restored = $record->newQueryWithoutScopes()->findOrFail($record->id);
        $this->assertFalse($restored->trashed());
        $this->assertSame($before, Arr::except($restored->getAttributes(), ['deleted_at', 'updated_at']));
        $this->assertSame($lineBefore, $line->newQueryWithoutScopes()->findOrFail($line->id)->getAttributes());
        $this->assertSame(2, $this->auditCount($record));
        $this->patchJson(route($routes.'.restore'), ['recordIds' => [$record->id]])->assertRedirect();
        $this->assertSame($restored->getAttributes(), $record->newQueryWithoutScopes()->findOrFail($record->id)->getAttributes());
        $this->assertSame(2, $this->auditCount($record));
    }

    #[DataProvider('documents')]
    public function test_mixed_lab_and_missing_batches_never_partially_archive_or_restore(string $class, string $routes, string $module): void
    {
        $lab = VAPLab::factory()->create();
        $peerLab = VAPLab::factory()->create();
        $user = $this->operator($lab, $module);
        DB::table('lab_user')->insert(['lab_id' => $peerLab->id, 'user_id' => $user->id]);
        $local = $this->document($class, $lab, $user);
        $peer = $this->document($class, $peerLab, $user);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        foreach ([$peer->id, 2147483647] as $invalid) {
            $this->deleteJson(route($routes.'.destroy'), ['recordIds' => [$local->id, $invalid]])->assertNotFound();
            $this->assertFalse($local->newQueryWithoutScopes()->findOrFail($local->id)->trashed());
            $this->assertSame(0, $this->auditCount($local));
        }
        $local->deleteQuietly();
        $peer->deleteQuietly();
        $this->patchJson(route($routes.'.restore'), ['recordIds' => [$local->id, $peer->id]])->assertNotFound();
        $this->assertTrue($local->newQueryWithoutScopes()->findOrFail($local->id)->trashed());
        $this->assertTrue($peer->newQueryWithoutScopes()->findOrFail($peer->id)->trashed());
        $this->assertSame(0, $this->auditCount($local));
    }

    public function test_requests_are_bounded_and_permissions_and_membership_are_fresh(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        $invoice = $this->document(Invoice::class, $lab, $user);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        foreach ([[], [$invoice->id, $invoice->id], [0], ['oops'], ['key' => $invoice->id], range(1, 101)] as $ids) {
            $this->deleteJson(route('invoices.destroy'), ['recordIds' => $ids])->assertUnprocessable();
        }
        $user->revokePermissionTo('delete_invoices');
        $this->deleteJson(route('invoices.destroy'), ['recordIds' => [$invoice->id]])->assertForbidden();
        $user->givePermissionTo('delete_invoices');
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->deleteJson(route('invoices.destroy'), ['recordIds' => [$invoice->id]])->assertForbidden();
        $this->assertFalse($invoice->fresh()->trashed());
    }

    public function test_lifecycle_veto_or_child_mutation_rolls_back_the_complete_batch(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        $first = $this->document(Invoice::class, $lab, $user);
        $second = $this->document(Invoice::class, $lab, $user);
        $line = $this->line($second);
        foreach (['veto', 'child', 'earlier_core', 'state'] as $failure) {
            $event = ($failure === 'veto' ? 'eloquent.deleting: ' : 'eloquent.deleted: ').Invoice::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (Invoice $record) use ($failure, $first, $second, $line, &$called): ?bool {
                if ($record->id !== $second->id) {
                    return null;
                }
                $called = true;
                if ($failure === 'veto') {
                    return false;
                }
                match ($failure) {
                    'child' => DB::table($line->getTable())->where('id', $line->id)->update(['qty' => 99]),
                    'earlier_core' => DB::table('invoices')->where('id', $first->id)->update(['amount_due' => '999.00']),
                    'state' => DB::table('invoices')->where('id', $second->id)->update(['deleted_at' => null]),
                };

                return null;
            });
            try {
                $this->assertRejectedBatch($user, $lab, [$first, $second]);
                $this->assertTrue($called);
                $this->assertSame($line->getAttributes(), $line->fresh()->getAttributes());
                $this->assertSame('12.00', $first->fresh()->amount_due);
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    /** @param list<Model> $records */
    private function assertRejectedBatch(User $user, VAPLab $lab, array $records): void
    {
        try {
            app(SetBillingDocumentsArchived::class)->execute($user->id, $lab->id, Invoice::class, array_map(fn (Model $record): int => $record->id, $records), true);
            $this->fail('Unsafe archive must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        foreach ($records as $record) {
            $this->assertFalse($record->fresh()->trashed());
            $this->assertSame(0, $this->auditCount($record));
        }
    }

    public function test_audit_veto_and_post_audit_core_mutation_cannot_commit(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        $invoice = $this->document(Invoice::class, $lab, $user);
        foreach (['veto', 'core', 'removed', 'identity'] as $failure) {
            $event = ($failure === 'veto' ? 'eloquent.creating: ' : 'eloquent.created: ').ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (ISOActivityLog $audit) use ($invoice, $failure, &$called): ?bool {
                if (! $audit->properties->get('billing_archive')) {
                    return null;
                }
                $called = true;
                if ($failure === 'veto') {
                    return false;
                }
                if ($failure === 'core') {
                    DB::table('invoices')->where('id', $invoice->id)->update(['amount_due' => '999.00']);
                } elseif ($failure === 'removed') {
                    DB::table($audit->getTable())->where('id', $audit->id)->delete();
                } else {
                    DB::table($audit->getTable())->where('id', $audit->id)->update(['event' => 'unrelated', 'subject_id' => null]);
                }

                return null;
            });
            try {
                $this->assertRejectedBatch($user, $lab, [$invoice]);
                $this->assertTrue($called);
                $this->assertSame('12.00', $invoice->fresh()->amount_due);
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_authority_revoked_by_explicit_audit_prevents_commit(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        $invoice = $this->document(Invoice::class, $lab, $user);
        foreach (['membership', 'permission', 'account'] as $revocation) {
            $event = 'eloquent.created: '.ISOActivityLog::class;
            $listeners = Event::getRawListeners()[$event] ?? [];
            $called = false;
            Event::listen($event, function (ISOActivityLog $audit) use ($user, $revocation, &$called): void {
                if (! $audit->properties->get('billing_archive')) {
                    return;
                }
                $called = true;
                match ($revocation) {
                    'membership' => DB::table('lab_user')->where('user_id', $user->id)->delete(),
                    'permission' => DB::table('model_has_permissions')->where('model_id', $user->id)->delete(),
                    'account' => DB::table('users')->where('id', $user->id)->update(['is_active' => false]),
                };
            });
            try {
                try {
                    app(SetBillingDocumentsArchived::class)->execute($user->id, $lab->id, Invoice::class, [$invoice->id], true);
                    $this->fail('Revoked authority must prevent commit.');
                } catch (AuthorizationException) {
                    $this->assertTrue($called);
                    $this->assertFalse($invoice->fresh()->trashed());
                    $this->assertSame(0, $this->auditCount($invoice));
                    $this->assertDatabaseHas('lab_user', ['lab_id' => $lab->id, 'user_id' => $user->id]);
                    $this->assertTrue($user->fresh()->is_active);
                    $this->assertTrue($user->fresh()->can('delete_invoices'));
                }
            } finally {
                Event::forget($event);
                foreach ($listeners as $listener) {
                    Event::listen($event, $listener);
                }
            }
        }
    }

    public function test_restore_veto_rolls_back_all_rows_and_does_not_restore_historical_children(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        $first = $this->document(Invoice::class, $lab, $user);
        $second = $this->document(Invoice::class, $lab, $user);
        $line = $this->line($first);
        DB::table($line->getTable())->where('id', $line->id)->update(['deleted_at' => now()]);
        $first->deleteQuietly();
        $second->deleteQuietly();
        $event = 'eloquent.restoring: '.Invoice::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        $called = false;
        Event::listen($event, function (Invoice $record) use ($second, &$called): ?bool {
            if ($record->id === $second->id) {
                $called = true;

                return false;
            }

            return null;
        });
        try {
            try {
                app(SetBillingDocumentsArchived::class)->execute($user->id, $lab->id, Invoice::class, [$first->id, $second->id], false);
                $this->fail('Restore veto must roll back all rows.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
                $this->assertTrue($called);
                foreach ([$first, $second] as $record) {
                    $this->assertTrue($record->newQueryWithoutScopes()->findOrFail($record->id)->trashed());
                    $this->assertSame(0, $this->auditCount($record));
                }
            }
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
        $this->assertSame(2, app(SetBillingDocumentsArchived::class)->execute($user->id, $lab->id, Invoice::class, [$first->id, $second->id], false));
        $this->assertTrue($line->newQueryWithoutScopes()->findOrFail($line->id)->trashed());
    }

    public function test_source_restore_keeps_its_archived_invoice_and_archived_party_links(): void
    {
        foreach ([Quote::class, ImportCertificate::class, ExportCertificate::class] as $class) {
            $lab = VAPLab::factory()->create();
            $module = match ($class) {
                Quote::class => 'quotes', ImportCertificate::class => 'import_certificates', default => 'export_certificates'
            };
            $user = $this->operator($lab, $module);
            $source = $this->document($class, $lab, $user);
            [$customer, $site] = match ($class) {
                ImportCertificate::class => ['importer_id', 'importer_warehouse_id'],
                ExportCertificate::class => ['exporter_id', 'exporter_warehouse_id'],
                default => ['customer_id', 'warehouse_id'],
            };
            $invoice = new Invoice;
            $invoice->forceFill(['lab_id' => $lab->id, 'user_id' => $user->id, 'customer_id' => $source->$customer,
                'warehouse_id' => $source->$site, 'inv_no' => 'LINK-'.Str::uuid(), 'invoice_month' => now()->format('Y'), 'amount_due' => '12.00'])->saveQuietly();
            $source->forceFill(['invoice_id' => $invoice->id])->saveQuietly();
            $source->deleteQuietly();
            $invoice->deleteQuietly();
            Customer::findOrFail($source->$customer)->delete();
            Warehouse::findOrFail($source->$site)->delete();
            $this->assertSame(1, app(SetBillingDocumentsArchived::class)->execute($user->id, $lab->id, $class, [$source->id], false));
            $this->assertSame($invoice->id, $source->fresh()->invoice_id);
            $this->assertTrue($invoice->newQueryWithoutScopes()->findOrFail($invoice->id)->trashed());
            $this->assertSame('12.00', $invoice->newQueryWithoutScopes()->findOrFail($invoice->id)->amount_due);
        }
    }

    public function test_archive_does_not_reverse_settlements_or_cancel_an_issued_invoice(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        foreach (['receipts', 'credit_notes'] as $module) {
            foreach (['delete_', 'restore_'] as $prefix) {
                $user->givePermissionTo(Permission::findOrCreate($prefix.$module, 'web'));
            }
        }
        $invoice = $this->document(Invoice::class, $lab, $user);
        $invoice->forceFill(['amount_due' => '0.00', 'status' => true, 'paid_date' => now()->toDateString(), 'status_code' => Invoice::STATUS_CODE_NORMAL])->saveQuietly();
        $receipt = $this->document(Receipt::class, $lab, $user);
        $note = $this->document(CreditNote::class, $lab, $user);
        foreach ([$receipt, $note] as $document) {
            $document->forceFill(['invoice_id' => $invoice->id, 'customer_id' => $invoice->customer_id, 'warehouse_id' => $invoice->warehouse_id])->saveQuietly();
        }
        $allocation = new InvoiceReceipt;
        $allocation->forceFill(['lab_id' => $lab->id, 'invoice_id' => $invoice->id,
            'receipt_id' => $receipt->id, 'paid_amount' => '12.00', 'pending_amount' => '0.00'])->saveQuietly();
        $allocationBefore = $allocation->fresh()->getAttributes();
        $before = Arr::except($invoice->fresh()->getAttributes(), ['deleted_at', 'updated_at']);
        foreach ([$receipt, $note, $invoice] as $document) {
            foreach ([true, false] as $archived) {
                $this->assertSame(1, app(SetBillingDocumentsArchived::class)->execute($user->id, $lab->id, $document::class, [$document->id], $archived));
                $this->assertSame($before, Arr::except($invoice->newQueryWithoutScopes()->findOrFail($invoice->id)->getAttributes(), ['deleted_at', 'updated_at']));
                $this->assertSame($allocationBefore, $allocation->fresh()->getAttributes());
            }
        }
    }

    public function test_later_audit_cannot_remove_the_first_records_archive_evidence(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, 'invoices');
        $first = $this->document(Invoice::class, $lab, $user);
        $second = $this->document(Invoice::class, $lab, $user);
        $event = 'eloquent.created: '.ISOActivityLog::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        $called = false;
        Event::listen($event, function (ISOActivityLog $audit) use ($first, $second, &$called): void {
            if ($audit->properties->get('billing_archive') && (int) $audit->subject_id === $second->id) {
                $called = true;
                DB::table($audit->getTable())->where('subject_type', $first->getMorphClass())
                    ->where('subject_id', $first->id)->where('properties->billing_archive', true)->delete();
            }
        });
        try {
            $this->assertRejectedBatch($user, $lab, [$first, $second]);
            $this->assertTrue($called);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
