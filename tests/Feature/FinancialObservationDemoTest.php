<?php

namespace Tests\Feature;

use App\Actions\CreateFinancialObservationDemo;
use App\Models\CreditNote;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class FinancialObservationDemoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fresh_narrow_editor_can_correct_all_six_fictional_documents_without_changing_issued_content(): void
    {
        $existing = User::factory()->create();
        $before = $existing->fresh()->getRawOriginal();
        $settingsRevision = app(GeneralSettings::class)->revision();
        Notification::fake();
        $demo = app(CreateFinancialObservationDemo::class)->execute();
        $staff = User::findOrFail($demo['staff']['id']);
        $this->assertTrue(Hash::check($demo['staff']['password'], $staff->password));
        $this->assertCount(0, $staff->roles);
        $this->assertCount(12, $staff->permissions);
        $this->assertFalse($staff->can('add_invoices'));
        $this->assertFalse($staff->can('edit_users'));
        $this->assertSame([$demo['lab_id']], DB::table('lab_user')->where('user_id', $staff->id)->pluck('lab_id')->all());
        $this->actingAs($staff)->withSession(['active_lab_id' => $demo['lab_id']]);

        foreach (['invoice' => [Invoice::class, 'invoices'], 'credit_note' => [CreditNote::class, 'creditnotes'],
            'receipt' => [Receipt::class, 'receipts'], 'quote' => [Quote::class, 'quotes'],
            'import_certificate' => [ImportCertificate::class, 'importcertificates'],
            'export_certificate' => [ExportCertificate::class, 'exportcertificates']] as $kind => [$class, $routes]) {
            $document = $class::findOrFail($demo['documents'][$kind]);
            $original = $document->getRawOriginal();
            $this->assertSame($demo['lab_id'], $document->lab_id);
            $this->assertStringContainsString('DEMO-OBS-', $document->inv_no ?? $document->note_no ?? $document->rec_no ?? $document->quote_no ?? $document->cert_no);
            $this->assertNull($document->unique_hash);
            $this->get(route($routes.'.edit', $document->id))->assertOk()->assertInertia(fn (Assert $page) => $page->has('record'));
            $this->putJson(route($routes.'.update', $document->id), ['obs' => 'DEMONSTRAÇÃO — observação corrigida. Não enviar.'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame(Arr::except($original, ['obs', 'updated_at']), Arr::except($document->fresh()->getRawOriginal(), ['obs', 'updated_at']));
            $this->assertSame('DEMONSTRAÇÃO — observação corrigida. Não enviar.', $document->fresh()->obs);
        }
        $this->assertSame($before, $existing->fresh()->getRawOriginal());
        $this->assertSame($settingsRevision, app(GeneralSettings::class)->revision());
        Notification::assertNothingSent();
    }

    public function test_repeated_setup_creates_new_credentials_and_never_reuses_an_existing_account(): void
    {
        $first = app(CreateFinancialObservationDemo::class)->execute();
        $original = User::findOrFail($first['staff']['id'])->getRawOriginal();
        $second = app(CreateFinancialObservationDemo::class)->execute();
        $this->assertNotSame($first['staff']['id'], $second['staff']['id']);
        $this->assertNotSame($first['lab_id'], $second['lab_id']);
        $this->assertNotSame($first['staff']['password'], $second['staff']['password']);
        $this->assertSame($original, User::findOrFail($first['staff']['id'])->getRawOriginal());
    }

    public function test_production_is_rejected_before_fixture_writes(): void
    {
        $before = [User::count(), VAPLab::count(), Invoice::count()];
        $this->app->instance('env', 'production');
        try {
            app(CreateFinancialObservationDemo::class)->execute();
            $this->fail('Production fixtures were allowed.');
        } catch (LogicException) {
            $this->assertSame($before, [User::count(), VAPLab::count(), Invoice::count()]);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_outer_transaction_rollback_removes_the_entire_fixture_set(): void
    {
        $before = [User::count(), VAPLab::count(), Invoice::count(), ImportCertificate::count()];
        try {
            DB::transaction(function (): void {
                app(CreateFinancialObservationDemo::class)->execute();
                throw new RuntimeException('Discard demonstration');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Discard demonstration', $exception->getMessage());
        }
        $this->assertSame($before, [User::count(), VAPLab::count(), Invoice::count(), ImportCertificate::count()]);
    }
}
