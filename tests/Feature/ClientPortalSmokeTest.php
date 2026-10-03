<?php

namespace Tests\Feature;

use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientPortalSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function portalWarehouse(?Customer $customer = null): Warehouse
    {
        $customer ??= Customer::query()->create(['name' => 'Portal smoke customer '.Str::uuid()]);

        return Warehouse::query()->create([
            'name' => 'Portal smoke site '.Str::uuid(),
            'customer_id' => $customer->id,
            'email' => 'portal.smoke.'.Str::uuid().'@lims-unleashed.test',
            'email_verified_at' => now(),
        ]);
    }

    public function test_guest_can_open_portal_login(): void
    {
        $this->get(route('portal.login'))->assertOk();
    }

    public function test_portal_customer_can_open_core_portal_pages_without_server_errors(): void
    {
        $warehouse = $this->portalWarehouse();

        $routes = [
            'portal.home',
            'portal.services',
            'portal.profile',
            'portal.security',
            'portal.faqs',
            'portal.requests.index',
            'portal.collections',
            'portal.invoices',
            'portal.receipts',
            'portal.contractguides',
            'portal.creditnotes',
            'portal.quotes',
            'portal.qualitycertificates',
        ];

        $failures = [];

        foreach ($routes as $route) {
            $response = $this->actingAs($warehouse, 'portal')->get(route($route));

            if (! $response->isSuccessful() && ! $response->isRedirection()) {
                $failures[] = sprintf(
                    'Expected portal route [%s] to load or redirect successfully, got HTTP %d.',
                    $route,
                    $response->getStatusCode()
                );
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    /** @param class-string<Model> $modelClass */
    #[DataProvider('portalDocumentEndpoints')]
    public function test_portal_customer_document_downloads_are_guarded_and_scoped_to_their_warehouse(string $modelClass, string $routeName): void
    {
        $warehouse = $this->portalWarehouse();
        $siblingWarehouse = $this->portalWarehouse($warehouse->customer);
        $otherWarehouse = $this->portalWarehouse();
        $document = $this->portalDocument($modelClass, $warehouse);
        $siblingDocument = $this->portalDocument($modelClass, $siblingWarehouse);
        $otherDocument = $this->portalDocument($modelClass, $otherWarehouse);
        $url = route($routeName, ['id' => $document->id]);

        $this->get($url)->assertRedirect(route('portal.login'));

        $response = $this->actingAs($warehouse, 'portal')->get($url);
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());

        $this->actingAs($warehouse, 'portal')
            ->get(route($routeName, ['id' => $siblingDocument->id]))
            ->assertNotFound();
        $this->actingAs($warehouse, 'portal')
            ->get(route($routeName, ['id' => $otherDocument->id]))
            ->assertNotFound();

        $document->delete();
        $this->actingAs($warehouse, 'portal')->get($url)->assertNotFound();
    }

    /** @return array<string, array{class-string<Model>, string}> */
    public static function portalDocumentEndpoints(): array
    {
        return [
            'invoice' => [Invoice::class, 'portal.invoices.getInvoicePDF'],
            'receipt' => [Receipt::class, 'portal.receipts.getReceiptPDF'],
            'contract guide' => [ContractGuide::class, 'portal.contractguides.getContractGuidePDF'],
            'credit note' => [CreditNote::class, 'portal.creditnotes.getCreditNotePDF'],
            'quote' => [Quote::class, 'portal.quotes.getQuotePDF'],
            'quality certificate' => [QualityCertificate::class, 'portal.qualitycertificates.getQualityCertificatePDF'],
        ];
    }

    /** @param class-string<Model> $modelClass */
    private function portalDocument(string $modelClass, Warehouse $warehouse): Model
    {
        $number = 'PORTAL-'.Str::random(8);
        $attributes = match ($modelClass) {
            Invoice::class => ['inv_no' => $number, 'invoice_month' => now()->format('m/Y'), 'date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString()],
            Receipt::class => ['rec_no' => $number, 'rec_month' => now()->format('m/Y'), 'date' => now()->toDateString()],
            ContractGuide::class => ['guide_no' => $number, 'guide_month' => now()->format('m/Y'), 'date' => now()->toDateString()],
            CreditNote::class => ['note_no' => $number, 'note_month' => now()->format('m/Y'), 'date' => now()->toDateString()],
            Quote::class => ['quote_no' => $number, 'quote_month' => now()->format('m/Y'), 'date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString()],
            QualityCertificate::class => ['code' => $number],
        };

        $document = new $modelClass([
            ...$attributes,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $warehouse->customer_id,
            'user_id' => User::factory()->create()->id,
        ]);
        if (in_array($modelClass, [Invoice::class, Receipt::class, CreditNote::class, Quote::class], true)) {
            $document->lab_id = VAPLab::factory()->create()->id;
        }
        $document->saveQuietly();

        return $document;
    }
}
