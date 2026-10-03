<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLab;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioDefaultTemplates;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoicePaymentStatusTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_invoice_register_filters_paid_and_unpaid_records(): void
    {
        $user = $this->verifiedAdmin();
        $numberPrefix = 'PAYMENT-'.Str::upper(Str::random(8));
        $lab = VAPLab::factory()->create();
        DB::table('lab_user')->insert(['lab_id' => $lab->id, 'user_id' => $user->id]);
        $invoiceAttributes = [
            'user_id' => $user->id,
            'invoice_month' => now()->format('m/Y'),
            'date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
            'status_code' => Invoice::STATUS_CODE_NORMAL,
        ];
        $paidInvoice = new Invoice($invoiceAttributes + [
            'inv_no' => $numberPrefix.'-PAID',
            'status' => true,
            'amount_due' => 0,
            'paid_date' => now(),
        ]);
        $paidInvoice->lab_id = $lab->id;
        $paidInvoice->saveQuietly();
        $unpaidInvoice = new Invoice($invoiceAttributes + [
            'inv_no' => $numberPrefix.'-UNPAID',
            'status' => false,
            'amount_due' => 100,
        ]);
        $unpaidInvoice->lab_id = $lab->id;
        $unpaidInvoice->saveQuietly();

        $this->actingAs($user)
            ->get(route('invoices.index', ['filter' => 'paid', 'search' => $numberPrefix]))
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
            ->get(route('invoices.index', ['filter' => 'unpaid', 'search' => $numberPrefix]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Index')
                ->where('query.filter', 'unpaid')
                ->where('record.data', function ($rows) use ($paidInvoice, $unpaidInvoice): bool {
                    $invoices = collect($rows);

                    return $invoices->count() === 1
                        && $invoices->every(fn (array $invoice): bool => $invoice['payment_status'] === 'unpaid')
                        && ! $invoices->contains('id', $paidInvoice->getKey())
                        && $invoices->contains('id', $unpaidInvoice->getKey());
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
