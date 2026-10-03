<?php

namespace Tests\Feature;

use App\Actions\CreateReceipt;
use App\Actions\RecordInvoicePayment;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReceipt;
use App\Models\ISOActivityLog;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use App\Support\DocumentSignature;
use App\Support\ExportHubQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FinancialLaboratoryOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    private function operator(VAPLab $lab, array $permissions = ['view_invoices', 'edit_invoices', 'view_receipts', 'add_receipts', 'view_credit_notes']): User
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function invoice(VAPLab $lab, User $user, string $due = '100.00', ?Warehouse $site = null): Invoice
    {
        $site ??= Warehouse::create(['name' => 'Finance '.Str::uuid(), 'customer_id' => Customer::create(['name' => 'Shared finance customer'])->id]);
        $invoice = new Invoice([
            'inv_no' => 'FINANCE-'.Str::uuid(), 'invoice_month' => now()->format('Y'),
            'user_id' => $user->id, 'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
            'total' => $due, 'amount_due' => $due, 'status_code' => Invoice::STATUS_CODE_NORMAL,
        ]);
        $invoice->lab_id = $lab->id;
        $invoice->saveQuietly();

        return $invoice;
    }

    private function receipt(VAPLab $lab, User $user, Invoice $invoice): Receipt
    {
        $receipt = new Receipt(['user_id' => $user->id, 'customer_id' => $invoice->customer_id,
            'warehouse_id' => $invoice->warehouse_id, 'rec_no' => 'FINANCE-'.Str::uuid(),
            'rec_month' => now()->format('Y'), 'date' => now()->toDateString()]);
        $receipt->lab_id = $lab->id;
        $receipt->saveQuietly();

        return $receipt;
    }

    public function test_staff_registers_and_lookups_only_show_the_active_lab_even_for_a_shared_customer(): void
    {
        $one = VAPLab::factory()->create();
        $two = VAPLab::factory()->create();
        $user = $this->operator($one);
        DB::table('lab_user')->insert(['lab_id' => $two->id, 'user_id' => $user->id]);
        $local = $this->invoice($one, $user);
        $peer = $this->invoice($two, $user, '999.00', $local->warehouse);
        $this->receipt($one, $user, $local);
        $this->receipt($two, $user, $peer);
        foreach ([[$one, $local], [$two, $peer]] as [$lab, $invoice]) {
            $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
            $this->get(route('invoices.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('record.data', 1)->where('record.data.0.id', $invoice->id));
            $this->getJson(route('invoices.getInvoice', ['q' => 'FINANCE-']))->assertOk()
                ->assertJsonCount(1)->assertJsonPath('0.id', $invoice->id)
                ->assertJsonMissingPath('0.unique_hash')->assertJsonMissingPath('0.extra_data');
            $this->getJson(route('receipts.getReceipt'))->assertOk()->assertJsonCount(1);
        }
        $this->assertSame($local->customer_id, $peer->customer_id);
    }

    public function test_direct_membership_and_module_permission_are_both_required(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, []);
        $this->actingAs($user)->getJson(route('invoices.getInvoice', ['q' => 'FINANCE']))->assertForbidden();
        $user->givePermissionTo(Permission::findOrCreate('view_invoices', 'web'));
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        $this->getJson(route('invoices.getInvoice', ['q' => 'FINANCE']))->assertForbidden();
    }

    public function test_peer_invoice_read_pdf_edit_and_payment_are_rejected(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('invoices.show', $peer->id))->assertNotFound();
        $this->get(route('invoices.edit', $peer->id))->assertNotFound();
        $this->get(route('invoices.getPDF', ['id' => $peer->id]))->assertNotFound();
        $this->postJson(route('invoices.changeStatusToPaid'), ['id' => $peer->id,
            'payment_method' => ['value' => $payment->id, 'label' => 'Forged']])->assertNotFound();
        $this->assertSame('100.00', $peer->fresh()->amount_due);
        $this->assertSame(0, Receipt::count());
    }

    public function test_financial_exports_only_include_the_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $local = $this->invoice($lab, $user);
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $receipt = $this->receipt($lab, $user, $local);
        $this->receipt($peer->lab, $user, $peer);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $query = app(ExportHubQuery::class);
        request()->attributes->set('sample_laboratory_id', $lab->id);
        $this->assertSame([$local->id], $query->invoices([])->pluck('invoices.id')->all());
        $this->assertSame([$receipt->id], $query->receipts([])->pluck('receipts.id')->all());
    }

    public function test_partial_then_full_payment_uses_exact_current_balances_and_signs_completed_allocations(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user, '10.31');
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        $receipt = app(CreateReceipt::class)->execute($user->id, $lab->id,
            ['customer_id' => $invoice->customer_id, 'warehouse_id' => $invoice->warehouse_id],
            [['invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'paid_amount' => '3.10',
                'pending_amount' => '-999', 'invoice_pending_amount' => '123456']]);
        $this->assertSame('7.21', $invoice->fresh()->amount_due);
        $this->assertNull($invoice->fresh()->paid_date);
        $line = $receipt->items()->firstOrFail();
        $this->assertSame('10.31', $line->invoice_pending_amount);
        $this->assertSame('7.21', $line->pending_amount);
        $this->assertSame($lab->id, $line->lab_id);
        $payload = $receipt->date.';'.$receipt->created_at->toDateTimeLocalString().';'.$receipt->rec_no.';3.1;';
        $this->assertSame(app(DocumentSignature::class)->sign($payload), $receipt->unique_hash);
        $final = app(RecordInvoicePayment::class)->execute($user->id, $lab->id, $invoice->id, $payment->id);
        $this->assertSame('0.00', $invoice->fresh()->amount_due);
        $this->assertTrue($invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_date);
        $this->assertSame('7.21', $final->items()->firstOrFail()->paid_amount);
        $this->assertSame($payment->name, $invoice->fresh()->payment_method);
    }

    public function test_mark_paid_is_post_only_and_does_not_subtract_twice_or_duplicate_a_receipt(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        $payload = ['id' => $invoice->id, 'payment_method' => ['value' => $payment->id, 'label' => 'Forged']];
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->getJson(route('invoices.changeStatusToPaid', $payload))->assertStatus(405);
        $this->postJson(route('invoices.changeStatusToPaid'), $payload)->assertRedirect();
        $this->assertSame('0.00', $invoice->fresh()->amount_due);
        $this->assertSame($payment->name, $invoice->fresh()->payment_method);
        $this->assertSame(1, Receipt::count());
        $this->postJson(route('invoices.changeStatusToPaid'), $payload)->assertUnprocessable();
        $this->assertSame(1, Receipt::count());
    }

    public function test_invalid_cross_lab_or_stale_payments_leave_no_partial_receipt_or_balance_change(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user, '0.30');
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        foreach ([['id' => $invoice->id, 'amount' => '0.31'], ['id' => $invoice->id, 'amount' => '0.001'],
            ['id' => $invoice->id, 'amount' => '-1'], ['id' => $invoice->id, 'amount' => '1e1'],
            ['id' => $peer->id, 'amount' => '0.10']] as $case) {
            try {
                app(CreateReceipt::class)->execute($user->id, $lab->id,
                    ['customer_id' => $invoice->customer_id, 'warehouse_id' => $invoice->warehouse_id],
                    [['invoice_id' => $case['id'], 'payment_id' => $payment->id, 'paid_amount' => $case['amount']]]);
                $this->fail('Payment must be rejected.');
            } catch (ValidationException|ModelNotFoundException|HttpException $exception) {
                $this->assertSame(0, Receipt::count());
                $this->assertSame('0.30', $invoice->fresh()->amount_due);
                $this->assertSame(0, InvoiceReceipt::count());
            }
        }
    }

    public function test_payment_requires_fresh_permission_and_membership(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        DB::table('lab_user')->where('user_id', $user->id)->delete();
        try {
            app(RecordInvoicePayment::class)->execute($user->id, $lab->id, $invoice->id, $payment->id);
            $this->fail('Revoked membership must be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame(0, Receipt::count());
            $this->assertSame('100.00', $invoice->fresh()->amount_due);
        }
    }

    public function test_database_rejects_cross_lab_payment_allocation_even_without_model_events(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user);
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $receipt = $this->receipt($lab, $user, $invoice);
        $this->expectException(QueryException::class);
        DB::transaction(fn () => DB::table('invoice_receipt')->insert([
            'lab_id' => $lab->id, 'receipt_id' => $receipt->id, 'invoice_id' => $peer->id,
            'paid_amount' => '1.00', 'pending_amount' => '99.00', 'invoice_pending_amount' => '100.00',
        ]));
    }

    public function test_financial_owner_cannot_be_reassigned_and_lines_inherit_parent_ownership(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user);
        $line = $invoice->items()->create(['item_description' => 'Local service']);
        $this->assertSame($lab->id, $line->lab_id);
        $invoice->lab_id = VAPLab::factory()->create()->id;
        $this->expectException(LogicException::class);
        $invoice->save();
    }

    public function test_rollback_preserves_receipt_and_balance_when_invoice_update_is_vetoed(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        $event = 'eloquent.updating: '.Invoice::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (): bool => false);
        try {
            app(RecordInvoicePayment::class)->execute($user->id, $lab->id, $invoice->id, $payment->id);
            $this->fail('Veto must fail the payment.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(0, Receipt::count());
            $this->assertSame(0, InvoiceReceipt::count());
            $this->assertSame('100.00', $invoice->fresh()->amount_due);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public function test_schema_rollback_refuses_retained_financial_data(): void
    {
        $lab = VAPLab::factory()->create();
        $this->invoice($lab, $this->operator($lab));
        $migration = require database_path('migrations/2026_10_01_180907_enforce_laboratory_ownership_of_financial_documents.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_credit_note_cannot_reference_an_invoice_of_another_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $note = new CreditNote(['invoice_id' => $peer->id, 'customer_id' => $peer->customer_id,
            'warehouse_id' => $peer->warehouse_id, 'note_month' => now()->format('Y'), 'user_id' => $user->id]);
        $note->lab_id = $lab->id;
        $this->expectException(ValidationException::class);
        $note->save();
    }

    public function test_payment_audit_veto_rolls_back_receipt_allocations_and_balances(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab);
        $invoice = $this->invoice($lab, $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        $event = 'eloquent.saving: '.ISOActivityLog::class;
        $listeners = Event::getRawListeners()[$event] ?? [];
        Event::listen($event, fn (): bool => false);
        try {
            app(RecordInvoicePayment::class)->execute($user->id, $lab->id, $invoice->id, $payment->id);
            $this->fail('Audit veto must reject the payment.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(0, Receipt::count());
            $this->assertSame(0, InvoiceReceipt::count());
            $this->assertSame('100.00', $invoice->fresh()->amount_due);
        } finally {
            Event::forget($event);
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }

    public function test_financial_activity_details_and_exports_require_source_permission_in_the_active_lab(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['view_invoices', 'view_activity_log', 'export_activity_log']);
        $local = $this->invoice($lab, $user);
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $localActivity = activity()->causedBy($user)->performedOn($local)->withProperties(['amount' => '100.00'])->log('local financial audit');
        $peerActivity = activity()->causedBy($user)->performedOn($peer)->withProperties(['amount' => '999.00'])->log('peer financial audit');
        $receipt = $this->receipt($lab, $user, $local);
        $receiptActivity = activity()->causedBy($user)->performedOn($receipt)->log('receipt without source permission');
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->getJson(route('systemactivity.show', $localActivity->id))->assertOk();
        $this->getJson(route('systemactivity.show', $peerActivity->id))->assertNotFound();
        $this->getJson(route('systemactivity.show', $receiptActivity->id))->assertNotFound();
        request()->attributes->set('proposal_laboratory_id', $lab->id);
        try {
            $ids = app(ExportHubQuery::class)->activityLog([])->pluck('activity_log.id');
            $this->assertContains($localActivity->id, $ids);
            $this->assertNotContains($peerActivity->id, $ids);
            $this->assertNotContains($receiptActivity->id, $ids);
            $this->assertSame([$localActivity->id], ISOActivityLog::query()
                ->whereKey([$localActivity->id, $peerActivity->id, $receiptActivity->id])->pluck('id')->all());
        } finally {
            request()->attributes->remove('proposal_laboratory_id');
        }
        $user->revokePermissionTo('view_invoices');
        $this->getJson(route('systemactivity.show', $localActivity->id))->assertNotFound();
    }

    public function test_receipt_creation_permission_does_not_require_receipt_read_permission_to_persist_checked_audit(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['add_receipts']);
        $invoice = $this->invoice($lab, $user);
        $payment = PaymentCategory::create(['name' => 'Bank '.Str::uuid()]);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id])->postJson(route('receipts.store'), [
            'customer_id' => ['value' => $invoice->customer_id],
            'warehouse_id' => ['value' => $invoice->warehouse_id],
            'formatted_items' => [['invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'paid_amount' => '25.00']],
        ])->assertRedirect();
        $this->assertSame('75.00', $invoice->fresh()->amount_due);
        $this->assertSame(1, Receipt::count());
        $this->assertSame(1, ISOActivityLog::withoutGlobalScopes()->where('event', 'issued')->count());
    }

    public function test_export_cards_do_not_reveal_peer_counts_or_update_times(): void
    {
        $lab = VAPLab::factory()->create();
        $user = $this->operator($lab, ['export_invoices', 'export_receipts', 'export_credit_notes']);
        $local = $this->invoice($lab, $user);
        $peer = $this->invoice(VAPLab::factory()->create(), $user);
        $this->receipt($peer->lab, $user, $peer);
        $this->actingAs($user)->withSession(['active_lab_id' => $lab->id]);
        $this->get(route('exports.index', ['dataset' => 'invoices']))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('selectedCount', 1)
            ->where('datasets', function (Collection $datasets) use ($local): bool {
                $cards = collect($datasets)->keyBy('key');

                return $cards['invoices']['count'] === 1
                    && $cards['invoices']['updated_at'] === $local->getRawOriginal('updated_at')
                    && $cards['receipts']['count'] === 0
                    && $cards['receipts']['updated_at'] === null
                    && $cards['credit_notes']['count'] === 0;
            }));
    }

    public function test_financial_migration_replays_and_excludes_concurrent_writers_before_rollback_check(): void
    {
        $this->assertSame('lims_unleashed_test', DB::connection()->getDatabaseName());
        $schema = 'finance_migration_'.bin2hex(random_bytes(8));
        $name = 'finance_migration';
        $writerName = 'finance_migration_writer';
        $originalDefault = DB::getDefaultConnection();
        $originalConfig = config('database.connections.'.$name);
        $originalWriterConfig = config('database.connections.'.$writerName);
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
            foreach (['invoices', 'credit_notes', 'receipts'] as $tableName) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->id();
                    $table->date('date')->nullable();
                    if ($tableName !== 'invoices') {
                        $table->foreignId('invoice_id')->nullable()->constrained('invoices');
                    }
                });
            }
            foreach (['invoice_items' => ['invoice_id' => 'invoices'],
                'credit_note_items' => ['note_id' => 'credit_notes'],
                'invoice_receipt' => ['invoice_id' => 'invoices', 'receipt_id' => 'receipts']] as $tableName => $parents) {
                Schema::create($tableName, function (Blueprint $table) use ($parents): void {
                    $table->id();
                    foreach ($parents as $column => $parent) {
                        $table->foreignId($column)->nullable()->constrained($parent);
                    }
                });
            }
            $migration = require database_path('migrations/2026_10_01_180907_enforce_laboratory_ownership_of_financial_documents.php');
            $migration->up();
            $writer->statement("SET lock_timeout = '100ms'");
            $blocked = 0;
            DB::listen(function (QueryExecuted $query) use ($name, $writer, &$probe, &$blocked): void {
                if (! $probe || $query->connectionName !== $name || ! str_starts_with($query->sql, 'LOCK TABLE')) {
                    return;
                }
                $probe = false;
                $this->assertGreaterThan(0, $query->connection->transactionLevel());
                try {
                    $writer->table('invoices')->insert(['lab_id' => 1]);
                    $this->fail('Rollback must exclude writers before checking retained evidence.');
                } catch (QueryException $exception) {
                    $this->assertSame('55P03', $exception->errorInfo[0]);
                    $blocked++;
                }
            });
            $probe = true;
            $migration->down();
            $this->assertSame(1, $blocked);
            $this->assertFalse(Schema::hasColumn('invoices', 'lab_id'));
            $migration->up();
            DB::table('invoices')->insert(['lab_id' => 1]);
            try {
                $migration->down();
                $this->fail('Retained financial evidence must reject rollback.');
            } catch (\RuntimeException) {
                $this->assertSame(1, DB::table('invoices')->count());
                $this->assertSame(1, DB::table('invoices')->value('lab_id'));
                $this->assertTrue(Schema::hasColumn('invoice_receipt', 'lab_id'));
            }
        } finally {
            $probe = false;
            DB::setDefaultConnection($originalDefault);
            $connection->statement('DROP SCHEMA "'.$schema.'" CASCADE');
            DB::purge($name);
            DB::purge($writerName);
            config(['database.connections.'.$name => $originalConfig]);
            config(['database.connections.'.$writerName => $originalWriterConfig]);
        }
    }
}
