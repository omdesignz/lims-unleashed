<?php

namespace Tests\Feature;

use App\Actions\CreatePortalDocumentsDemo;
use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\User;
use App\Models\Warehouse;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class PortalDocumentsDemoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_isolated_demo_supports_real_portal_login_lists_and_six_pdf_downloads_without_signing_or_notifications(): void
    {
        $existing = User::factory()->create();
        $before = $existing->fresh()->getRawOriginal();
        $settingsRevision = app(GeneralSettings::class)->revision();
        Notification::fake();
        $demo = app(CreatePortalDocumentsDemo::class)->execute();
        $this->assertSame($before, $existing->fresh()->getRawOriginal());
        $this->assertSame($settingsRevision, app(GeneralSettings::class)->revision());
        foreach (['invoice' => Invoice::class, 'quote' => Quote::class, 'credit_note' => CreditNote::class, 'receipt' => Receipt::class] as $key => $model) {
            $this->assertNull($model::findOrFail($demo['documents'][$key])->unique_hash);
        }
        $this->post(route('portal.login.store'), ['email' => $demo['portal']['email'], 'password' => $demo['portal']['password']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs(Warehouse::findOrFail($demo['portal']['id']), 'portal');
        foreach ([
            'invoice' => ['invoices', 'getInvoicePDF'], 'quote' => ['quotes', 'getQuotePDF'],
            'credit_note' => ['creditnotes', 'getCreditNotePDF'], 'receipt' => ['receipts', 'getReceiptPDF'],
            'contract_guide' => ['contractguides', 'getContractGuidePDF'],
            'quality_certificate' => ['qualitycertificates', 'getQualityCertificatePDF'],
        ] as $key => [$list, $download]) {
            $this->get(route('portal.'.$list))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('record.data', 1)->where('record.data.0.id', $demo['documents'][$key]));
            $pdf = $this->get(route('portal.'.$list.'.'.$download, ['id' => $demo['documents'][$key]]));
            $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', (string) $pdf->getContent());
        }
        $this->get(route('portal.invoices.getInvoicePDF', ['id' => $demo['hidden']['invoice']]))->assertNotFound();
        $this->get(route('portal.qualitycertificates.getQualityCertificatePDF', ['id' => $demo['hidden']['quality_certificate']]))->assertNotFound();
        Notification::assertNothingSent();
    }

    public function test_repeated_setup_is_fresh_and_does_not_reset_existing_credentials(): void
    {
        $first = app(CreatePortalDocumentsDemo::class)->execute();
        $before = Warehouse::findOrFail($first['portal']['id'])->getRawOriginal();
        $second = app(CreatePortalDocumentsDemo::class)->execute();
        $this->assertNotSame($first['marker'], $second['marker']);
        $this->assertNotSame($first['portal']['password'], $second['portal']['password']);
        $this->assertNotSame($first['lab_id'], $second['lab_id']);
        $this->assertSame($before, Warehouse::findOrFail($first['portal']['id'])->getRawOriginal());
    }

    public function test_contract_guide_escapes_item_text_and_renders_missing_optional_references(): void
    {
        $demo = app(CreatePortalDocumentsDemo::class)->execute();
        $guide = ContractGuide::findOrFail($demo['documents']['contract_guide']);
        $item = $guide->items()->sole();
        $item->forceFill(['product_id' => null, 'country_id' => null, 'manufacturer' => '<b>Supplier</b>', 'brand' => null, 'lot' => null])->saveQuietly();
        $html = view('PDFs.contractguide', ['model' => $guide->load('items.product', 'items.country'),
            'settings' => app(GeneralSettings::class)])->render();
        // A reference that was not recorded is marked as such, never filled with a phrase.
        $this->assertStringContainsString('<td>—</td>', $html);
        $this->assertStringNotContainsString('informado', mb_strtolower($html));
        $this->assertStringContainsString('&lt;b&gt;Supplier&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Supplier</b>', $html);
    }

    public function test_contract_guide_uses_real_identity_and_separate_reference_fields_without_inventing_status(): void
    {
        $demo = app(CreatePortalDocumentsDemo::class)->execute();
        $guide = ContractGuide::findOrFail($demo['documents']['contract_guide']);
        $guide->forceFill(['bl' => 'BL-123', 'ref_no' => 'REF-456', 'nif' => '0', 'obs' => '<b>Evidence</b>'])->saveQuietly();
        $guide->load('customer', 'warehouse', 'items.product', 'items.country');

        $html = view('PDFs.contractguide', ['model' => $guide, 'settings' => app(GeneralSettings::class)])->render();

        $this->assertStringContainsString(e($guide->customer->name), $html);
        $this->assertStringContainsString($guide->warehouse->name, $html);
        $this->assertStringContainsString('BL-123', $html);
        $this->assertStringContainsString('REF-456', $html);
        $this->assertStringContainsString('&lt;b&gt;Evidence&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>Evidence</b>', $html);
        $this->assertStringNotContainsString('Registo válido até', $html);
        $this->assertStringNotContainsString('Documentação Completa', $html);
        $this->assertStringNotContainsString('sistema validado', $html);
        // Products come before the terms, in one flow; the page breaks where the content does.
        $this->assertStringNotContainsString('<pagebreak', $html);
        $this->assertLessThan(strpos($html, 'Termos e condições'), strpos($html, 'Produtos para análise'));
        $this->assertStringNotContainsString('105mm', $html);
        // The number and the page of the total are on every page, with the letterhead on the first.
        $this->assertStringContainsString('class="doc-control-title">Guia de Contratação</td>', $html);
        $this->assertStringContainsString($guide->guide_no.' · Rev. 0', $html);
        $this->assertStringContainsString('Página {PAGENO} de {nbpg}', $html);
        $this->assertStringContainsString('footer: doc-footer;', $html);
        $this->assertStringContainsString('header: doc-letterhead;', $html);
    }

    public function test_demo_is_forbidden_in_production_before_any_records_are_created(): void
    {
        $before = User::count();
        $this->app->instance('env', 'production');
        try {
            app(CreatePortalDocumentsDemo::class)->execute();
            $this->fail('Production demo setup was permitted.');
        } catch (LogicException) {
            $this->assertSame($before, User::count());
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_fixture_writes_participate_in_outer_transaction_rollback(): void
    {
        $before = [User::count(), Warehouse::count(), Invoice::count()];
        try {
            DB::transaction(function (): void {
                app(CreatePortalDocumentsDemo::class)->execute();
                throw new RuntimeException('Discard fictional fixture');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Discard fictional fixture', $exception->getMessage());
        }

        $this->assertSame($before, [User::count(), Warehouse::count(), Invoice::count()]);
    }
}
