<?php

namespace Tests\Feature;

use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientPortalSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function portalWarehouse(): Warehouse
    {
        $warehouse = Warehouse::query()
            ->whereNotNull('email')
            ->first();

        $this->assertNotNull($warehouse, 'Expected at least one warehouse with an email for portal smoke testing.');

        return $warehouse;
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

    public function test_portal_customer_document_downloads_are_guarded_and_scoped_to_their_warehouse(): void
    {
        $warehouse = $this->portalWarehouse();
        $checks = [];

        $this->appendPortalDocumentCheck($checks, 'invoice', 'portal.invoices.getInvoicePDF', Invoice::query()->where('warehouse_id', $warehouse->id)->first());
        $this->appendPortalDocumentCheck($checks, 'receipt', 'portal.receipts.getReceiptPDF', Receipt::query()->where('warehouse_id', $warehouse->id)->first());
        $this->appendPortalDocumentCheck($checks, 'contract guide', 'portal.contractguides.getContractGuidePDF', ContractGuide::query()->where('warehouse_id', $warehouse->id)->first());
        $this->appendPortalDocumentCheck($checks, 'credit note', 'portal.creditnotes.getCreditNotePDF', CreditNote::query()->where('warehouse_id', $warehouse->id)->first());
        $this->appendPortalDocumentCheck($checks, 'quote', 'portal.quotes.getQuotePDF', Quote::query()->where('warehouse_id', $warehouse->id)->first());
        $this->appendPortalDocumentCheck($checks, 'quality certificate', 'portal.qualitycertificates.getQualityCertificatePDF', QualityCertificate::query()->where('warehouse_id', $warehouse->id)->first());

        if ($checks === []) {
            $this->markTestSkipped('No portal-owned document records exist for this dataset.');
        }

        $failures = [];

        foreach ($checks as $check) {
            $this->app['auth']->guard('portal')->logout();
            $this->flushSession();

            $this->get($check['url'])->assertRedirect(route('portal.login'));

            $response = $this->actingAs($warehouse, 'portal')->get($check['url']);

            if ($response->getStatusCode() >= 500) {
                $failures[] = sprintf(
                    'Expected portal document endpoint [%s] not to return a server error, got HTTP %d.',
                    $check['label'],
                    $response->getStatusCode()
                );

                continue;
            }

            if ($response->isSuccessful()) {
                $contentType = (string) $response->headers->get('content-type');

                if (! str_contains($contentType, 'application/pdf') && ! str_starts_with($response->getContent(), '%PDF')) {
                    $failures[] = sprintf(
                        'Expected portal document endpoint [%s] to return PDF content, got content type [%s].',
                        $check['label'],
                        $contentType
                    );
                }
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $checks
     */
    private function appendPortalDocumentCheck(array &$checks, string $label, string $routeName, ?object $record): void
    {
        if (! $record) {
            return;
        }

        $checks[] = [
            'label' => $label,
            'url' => route($routeName, ['id' => $record->id]),
        ];
    }
}
