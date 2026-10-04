<?php

namespace Tests\Feature;

use App\Models\CollectionProduct;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReceipt;
use App\Models\LabCode;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Support\ReportStudioPdfBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortalDocumentBoundaryTest extends TestCase
{
    use DatabaseTransactions;

    private function site(?Customer $customer = null): Warehouse
    {
        $customer ??= Customer::create(['name' => 'Portal boundary '.Str::uuid()]);

        return Warehouse::create(['name' => 'Site '.Str::uuid(), 'customer_id' => $customer->id,
            'email' => Str::uuid().'@example.test', 'email_verified_at' => now()]);
    }

    private function certificate(Warehouse $site): QualityCertificate
    {
        $invoice = new Invoice(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'inv_no' => 'INV-'.Str::uuid(), 'invoice_month' => now()->format('m/Y'),
            'date' => now()->toDateString(), 'status' => true, 'user_id' => User::factory()->create()->id]);
        $invoice->lab_id = VAPLab::factory()->create()->id;
        $invoice->saveQuietly();
        $collection = new CollectionProduct(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'invoice_id' => $invoice->id]);
        $collection->saveQuietly();
        VAPSampleEntry::factory()->createQuietly(['lab_id' => $invoice->lab_id,
            'customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'collection_product_id' => $collection->id]);
        $code = new LabCode(['collection_id' => $collection->id, 'code' => 'MATCH-'.Str::uuid(),
            'cl_month' => now()->format('Y'), 'codeable_type' => 'analysis']);
        $code->saveQuietly();
        $certificate = new QualityCertificate(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'user_id' => $invoice->user_id, 'code' => 'CERT-'.Str::uuid(), 'collection_id' => $collection->id,
            'cl_id' => $code->id, 'validated_at' => now()]);
        $certificate->saveQuietly();

        return $certificate;
    }

    /** @return array<string, array{class-string<Model>, string}> */
    public static function endpoints(): array
    {
        return ClientPortalSmokeTest::portalDocumentEndpoints();
    }

    #[DataProvider('endpoints')]
    public function test_same_site_but_different_customer_is_not_visible_or_downloadable(string $model, string $download): void
    {
        $site = $this->site();
        $foreign = $this->site();
        $record = $model === QualityCertificate::class ? $this->certificate($site) : new $model;
        $record->forceFill(['customer_id' => $foreign->customer_id, 'warehouse_id' => $site->id]);
        if (in_array($model, [Invoice::class, Receipt::class, CreditNote::class, Quote::class], true)) {
            $record->lab_id = VAPLab::factory()->create()->id;
        }
        $month = match ($model) {
            Invoice::class => 'invoice_month',
            CreditNote::class => 'note_month',
            Quote::class => 'quote_month',
            default => null,
        };
        if ($month) {
            $record->setAttribute($month, now()->format('m/Y'));
        }
        $record->saveQuietly();
        $this->actingAs($site, 'portal')->get(route($download, ['id' => $record->id]))->assertNotFound();
        $list = Str::beforeLast($download, '.');
        foreach ([[], ['filter' => 'trashed']] as $query) {
            $this->get(route($list, $query))->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
        }
    }

    public function test_search_by_lab_code_cannot_escape_customer_site_or_release_predicates(): void
    {
        $site = $this->site();
        $visible = $this->certificate($site);
        $foreign = $this->certificate($this->site());
        $sibling = $this->certificate($this->site($site->customer));
        $unvalidated = $this->certificate($site);
        $unvalidated->forceFill(['validated_at' => null])->saveQuietly();
        $unpaid = $this->certificate($site);
        $unpaid->collection->invoice->forceFill(['status' => false])->saveQuietly();
        $this->actingAs($site, 'portal');
        foreach ([$foreign, $sibling, $unvalidated, $unpaid] as $hidden) {
            $this->get(route('portal.qualitycertificates', ['search' => $hidden->lab_code->code]))
                ->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
        }
        $this->get(route('portal.qualitycertificates', ['search' => $visible->lab_code->code]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 1)->where('record.data.0.id', $visible->id));
    }

    /** @return array<string, array{string}> */
    public static function invalidCertificateSources(): array
    {
        return collect(['unvalidated', 'unpaid', 'missing_invoice', 'archived_invoice', 'foreign_invoice',
            'foreign_collection', 'foreign_code', 'foreign_entry', 'archived_entry', 'conflicting_entry', 'foreign_result'])
            ->mapWithKeys(fn (string $case): array => [$case => [$case]])->all();
    }

    #[DataProvider('invalidCertificateSources')]
    public function test_certificate_download_rejects_unreleased_or_incoherent_sources_before_rendering(string $case): void
    {
        $site = $this->site();
        $certificate = $this->certificate($site);
        $foreign = $this->certificate($this->site());
        $collection = $certificate->collection;
        $invoice = $collection->invoice;
        $entry = $collection->sampleEntry;
        match ($case) {
            'unvalidated' => $certificate->forceFill(['validated_at' => null])->saveQuietly(),
            'unpaid' => $invoice->forceFill(['status' => false])->saveQuietly(),
            'missing_invoice' => $collection->forceFill(['invoice_id' => null])->saveQuietly(),
            'archived_invoice' => $invoice->deleteQuietly(),
            'foreign_invoice' => $collection->forceFill(['invoice_id' => $foreign->collection->invoice_id])->saveQuietly(),
            'foreign_collection' => $this->moveToForeignCollection($certificate, $foreign),
            'foreign_code' => $certificate->forceFill(['cl_id' => $foreign->cl_id])->saveQuietly(),
            'foreign_entry' => $entry->forceFill(['customer_id' => $foreign->customer_id])->saveQuietly(),
            'archived_entry' => $entry->deleteQuietly(),
            'conflicting_entry' => $entry->forceFill(['lab_id' => $foreign->collection->sampleEntry->lab_id])->saveQuietly(),
            'foreign_result' => $this->foreignResult($certificate, $foreign),
        };
        $this->mock(ReportStudioPdfBuilder::class, fn ($mock) => $mock->shouldNotReceive('buildAnalysisReportPayload'));
        $response = $this->actingAs($site, 'portal')
            ->get(route('portal.qualitycertificates.getQualityCertificatePDF', ['id' => $certificate->id]));
        $response->assertStatus($case === 'foreign_result' ? 403 : 404);
        if ($case !== 'foreign_result') {
            $this->get(route('portal.qualitycertificates', ['search' => $certificate->code]))
                ->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
        }
    }

    /**
     * Points the certificate at another site's collection. A collection holds one live
     * certificate at most, so the foreign certificate is archived first.
     */
    private function moveToForeignCollection(QualityCertificate $certificate, QualityCertificate $foreign): bool
    {
        $foreign->deleteQuietly();

        return $certificate->forceFill(['collection_id' => $foreign->collection_id])->saveQuietly();
    }

    private function foreignResult(QualityCertificate $certificate, QualityCertificate $foreign): int
    {
        $sample = new Sample(['cl_id' => $foreign->cl_id, 'code' => 'FOREIGN', 'sample_month' => now()->format('Y')]);
        $sample->saveQuietly();

        return DB::table('results')->insertGetId(['sample_id' => $sample->id, 'code_id' => $certificate->cl_id,
            'collection_id' => $certificate->collection_id, 'inserted_value' => 'Private result']);
    }

    /** @return array<string, array{class-string<Model>, string, string}> */
    public static function linkedDocuments(): array
    {
        $cases = [];
        foreach ([CreditNote::class => 'creditnotes', Quote::class => 'quotes', Receipt::class => 'receipts'] as $model => $route) {
            foreach (['site', 'customer', 'lab'] as $mismatch) {
                $cases[$route.' '.$mismatch] = [$model, $route, $mismatch];
            }
        }

        return $cases;
    }

    #[DataProvider('linkedDocuments')]
    public function test_linked_invoice_must_match_document_customer_site_and_lab(string $model, string $route, string $mismatch): void
    {
        $site = $this->site();
        $invoice = $this->certificate($site)->collection->invoice;
        $record = new $model(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'invoice_id' => $invoice->id]);
        $record->lab_id = $invoice->lab_id;
        if ($model !== Receipt::class) {
            $record->setAttribute($model === Quote::class ? 'quote_month' : 'note_month', now()->format('m/Y'));
        }
        $record->saveQuietly();
        $this->actingAs($site, 'portal')->get(route('portal.'.$route))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('record.data', 1));
        $foreign = $this->site();
        if ($mismatch === 'lab') {
            $this->expectException(QueryException::class);
        }
        $invoice->forceFill(match ($mismatch) {
            'site' => ['warehouse_id' => $foreign->id],
            'customer' => ['customer_id' => $foreign->customer_id],
            'lab' => ['lab_id' => VAPLab::factory()->create()->id],
        })->saveQuietly();
        $download = match ($model) {
            CreditNote::class => 'getCreditNotePDF', Quote::class => 'getQuotePDF', Receipt::class => 'getReceiptPDF',
        };
        $this->get(route('portal.'.$route.'.'.$download, ['id' => $record->id]))->assertNotFound();
        $this->get(route('portal.'.$route))->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
    }

    public function test_receipt_allocation_cannot_expose_foreign_invoice_or_foreign_line_ownership(): void
    {
        $site = $this->site();
        $invoice = $this->certificate($site)->collection->invoice;
        $foreign = $this->certificate($this->site())->collection->invoice;
        $foreign->forceFill(['lab_id' => $invoice->lab_id])->saveQuietly();
        $receipt = new Receipt(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'rec_no' => 'PORTAL-RECEIPT', 'date' => now()->toDateString()]);
        $receipt->lab_id = $invoice->lab_id;
        $receipt->saveQuietly();
        $allocation = new InvoiceReceipt(['receipt_id' => $receipt->id, 'invoice_id' => $invoice->id, 'paid_amount' => '125.25']);
        $allocation->lab_id = $receipt->lab_id;
        $allocation->saveQuietly();
        $this->actingAs($site, 'portal')->get(route('portal.receipts'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('record.data', 1)->where('record.data.0.total', '125.25'));
        $allocation->forceFill(['invoice_id' => $foreign->id])->saveQuietly();
        $this->get(route('portal.receipts.getReceiptPDF', ['id' => $receipt->id]))->assertNotFound();
        $this->get(route('portal.receipts'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('record.data', 0));
        $this->expectException(QueryException::class);
        $allocation->forceFill(['invoice_id' => $invoice->id, 'lab_id' => VAPLab::factory()->create()->id])->saveQuietly();
    }

    public function test_dashboard_uses_the_same_document_release_and_customer_boundaries(): void
    {
        $site = $this->site();
        $released = $this->certificate($site);
        $hidden = $this->certificate($site);
        $hidden->forceFill(['validated_at' => null])->saveQuietly();
        $invoice = $hidden->collection->invoice;
        $invoice->forceFill(['customer_id' => $this->site()->customer_id, 'status' => false, 'amount_due' => 500])->saveQuietly();
        $this->actingAs($site, 'portal')->get(route('portal.home'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.qualitycertificates', 1)
                ->where('stats.invoices', 1)->where('stats.overdue', 'AOA 0.00'));
    }

    public function test_owned_archived_invoice_references_remain_available_in_retained_documents(): void
    {
        $site = $this->site();
        $invoice = $this->certificate($site)->collection->invoice;
        $note = new CreditNote(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'invoice_id' => $invoice->id, 'note_no' => 'PORTAL-NOTE', 'note_month' => now()->format('m/Y')]);
        $note->lab_id = $invoice->lab_id;
        $note->saveQuietly();
        $receipt = new Receipt(['customer_id' => $site->customer_id, 'warehouse_id' => $site->id,
            'rec_no' => 'PORTAL-RECEIPT', 'date' => now()->toDateString()]);
        $receipt->lab_id = $invoice->lab_id;
        $receipt->saveQuietly();
        $allocation = new InvoiceReceipt(['receipt_id' => $receipt->id, 'invoice_id' => $invoice->id, 'paid_amount' => '125.25']);
        $allocation->lab_id = $receipt->lab_id;
        $allocation->saveQuietly();
        $invoice->deleteQuietly();
        $this->actingAs($site, 'portal')->get(route('portal.creditnotes'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('record.data.0.invoice_id', ['inv_no' => $invoice->inv_no]));
        $this->mock(ReportStudioPdfBuilder::class, function ($mock) use ($invoice): void {
            $mock->shouldReceive('buildReceiptPayload')->once()->andReturnUsing(function (Receipt $record, $settings) use ($invoice): array {
                $this->assertSame($invoice->inv_no, $record->items->first()->invoice->inv_no);
                $this->assertTrue($record->items->first()->invoice->trashed());

                return (new ReportStudioPdfBuilder)->buildReceiptPayload($record, $settings);
            });
            $mock->shouldReceive('buildCreditNotePayload')->once()->andReturnUsing(function (CreditNote $record, $settings) use ($invoice): array {
                $this->assertSame($invoice->inv_no, $record->invoice->inv_no);
                $this->assertTrue($record->invoice->trashed());

                return (new ReportStudioPdfBuilder)->buildCreditNotePayload($record, $settings);
            });
        });
        $this->get(route('portal.receipts.getReceiptPDF', ['id' => $receipt->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('portal.creditnotes.getCreditNotePDF', ['id' => $note->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('portal.invoices.getInvoicePDF', ['id' => $invoice->id]))->assertNotFound();
    }
}
