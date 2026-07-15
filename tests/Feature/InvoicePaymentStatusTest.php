<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioDefaultTemplates;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoicePaymentStatusTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        return Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->firstOrFail();
    }

    public function test_invoice_register_filters_paid_and_unpaid_records(): void
    {
        Invoice::query()->update([
            'status_code' => Invoice::STATUS_CODE_NORMAL,
            'status' => false,
            'amount_due' => 100,
        ]);

        $paidInvoice = Invoice::query()->firstOrFail();
        $unpaidInvoice = Invoice::query()->whereKeyNot($paidInvoice->getKey())->first();

        if (! $unpaidInvoice) {
            $unpaidInvoice = $paidInvoice->replicate();
            $unpaidInvoice->inv_no = 'FT TEST/UNPAID';
            $unpaidInvoice->saveQuietly();
        }

        $paidInvoice->forceFill([
            'status' => true,
            'amount_due' => 0,
            'paid_date' => now(),
        ])->saveQuietly();

        $unpaidInvoice->forceFill([
            'status' => false,
            'amount_due' => 100,
            'paid_date' => null,
        ])->saveQuietly();

        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->get(route('invoices.index', ['filter' => 'paid']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Index')
                ->where('query.filter', 'paid')
                ->has('record.data', 1)
                ->where('record.data.0.id', $paidInvoice->getKey())
                ->where('record.data.0.payment_status', 'paid')
                ->where('record.data.0.payment_status_label', 'Paga')
            );

        $this->actingAs($user)
            ->get(route('invoices.index', ['filter' => 'unpaid']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Index')
                ->where('query.filter', 'unpaid')
                ->where('record.data', function ($rows) use ($paidInvoice): bool {
                    $invoices = collect($rows);

                    return $invoices->isNotEmpty()
                        && $invoices->every(fn (array $invoice): bool => $invoice['payment_status'] === 'unpaid')
                        && ! $invoices->contains('id', $paidInvoice->getKey());
                })
            );
    }

    public function test_invoice_pdf_payload_always_displays_payment_status(): void
    {
        $studio = new ReportStudioTemplate([
            'name' => 'Custom invoice without payment placeholders',
            'studio_type' => 'invoice',
            'renderer' => 'chrome',
            'status' => 'active',
            'layout_schema' => [
                'body_html' => '<section class="custom-invoice">Factura {document_number}</section>',
                'canvas_blocks' => [],
            ],
            'export_settings' => ['paper_size' => 'A4', 'orientation' => 'P'],
        ]);

        $invoice = new Invoice([
            'inv_no' => 'FT 07/2026/0012',
            'date' => Carbon::parse('2026-07-01'),
            'due_date' => Carbon::parse('2026-07-31'),
            'paid_date' => Carbon::parse('2026-07-14'),
            'payment_method' => 'Transferência bancária',
            'status_code' => Invoice::STATUS_CODE_NORMAL,
            'status' => true,
            'amount_due' => 0,
            'sub_total' => 1000,
            'tax' => 140,
            'total' => 1140,
        ]);
        $invoice->setRelation('items', collect());
        $invoice->setRelation('customer', null);
        $invoice->setRelation('warehouse', null);
        $invoice->setRelation('user', null);

        $builder = app(ReportStudioPdfBuilder::class);
        $settings = app(GeneralSettings::class);
        $paidBody = (string) data_get(
            $builder->buildInvoicePayload($invoice, $settings, $studio),
            'data.bodyHtml'
        );

        $this->assertStringContainsString('invoice-payment-status', $paidBody);
        $this->assertStringContainsString('PAGA', $paidBody);
        $this->assertStringContainsString('Liquidada em 14/07/2026', $paidBody);
        $this->assertStringContainsString('Transferência bancária', $paidBody);
        $this->assertSame(1, substr_count($paidBody, 'invoice-payment-status studio-avoid-break'));

        $invoice->forceFill([
            'status' => false,
            'amount_due' => 850,
            'paid_date' => null,
            'payment_method' => null,
        ]);

        $unpaidBody = (string) data_get(
            $builder->buildInvoicePayload($invoice, $settings, $studio),
            'data.bodyHtml'
        );

        $this->assertStringContainsString('invoice-payment-status', $unpaidBody);
        $this->assertStringContainsString('POR PAGAR', $unpaidBody);
        $this->assertStringContainsString('Valor pendente: AOA 850,00', $unpaidBody);
        $this->assertSame(1, substr_count($unpaidBody, 'invoice-payment-status studio-avoid-break'));

        $variables = collect(data_get(
            ReportStudioDefaultTemplates::make('invoice')->layout_schema,
            'variable_catalog'
        ))->pluck('value');

        $this->assertContains('{payment_status}', $variables);
        $this->assertContains('{payment_status_badge}', $variables);
        $this->assertContains('{amount_due}', $variables);
    }
}
