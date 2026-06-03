<?php

namespace Tests\Feature;

use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\InventoryBatch;
use App\Models\InventoryNeed;
use App\Models\InventoryOrder;
use App\Models\Invoice;
use App\Models\ProficiencyTest;
use App\Models\Proposal;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\ReportStudioTemplate;
use App\Models\Role;
use App\Models\User;
use App\Models\VAPLabel;
use App\Models\VAPNonConformity;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocumentGenerationSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function verifiedAdmin(): User
    {
        $admin = Role::query()
            ->where('name', 'admin')
            ->firstOrFail()
            ->users()
            ->whereNotNull('email_verified_at')
            ->first();

        $this->assertNotNull($admin, 'Expected at least one verified admin user for document generation smoke testing.');

        return $admin;
    }

    public function test_verified_admin_document_endpoints_do_not_return_server_errors(): void
    {
        $user = $this->verifiedAdmin();
        $checks = [];

        $this->appendLegacyPdfCheck($checks, 'invoice', 'invoices.getPDF', Invoice::query()->first());
        $this->appendLegacyPdfCheck($checks, 'quote', 'quotes.getPDF', Quote::query()->first());
        $this->appendLegacyPdfCheck($checks, 'receipt', 'receipts.getPDF', Receipt::query()->first());
        $this->appendLegacyPdfCheck($checks, 'credit note', 'creditnotes.getPDF', CreditNote::query()->first());
        $this->appendLegacyPdfCheck($checks, 'contract guide', 'contractguides.getPDF', ContractGuide::query()->first());
        $this->appendLegacyPdfCheck($checks, 'import certificate', 'importcertificates.getPDF', ImportCertificate::query()->first());
        $this->appendLegacyPdfCheck($checks, 'export certificate', 'exportcertificates.getPDF', ExportCertificate::query()->first());
        $this->appendLegacyPdfCheck($checks, 'quality certificate', 'qualitycertificates.getPDF', QualityCertificate::query()->first());
        $this->appendLegacyPdfCheck($checks, 'legacy proposal', 'proposals.getPDF', Proposal::query()->first());

        $this->appendModelRouteCheck($checks, 'VAP proposal PDF', 'vap-proposals.download.pdf', VAPProposal::query()->first());
        $this->appendModelRouteCheck($checks, 'VAP proposal template PDF', 'vap-proposals.templates.pdf', VAPProposalTemplate::query()->first());
        $this->appendModelRouteCheck($checks, 'report studio preview', 'report-studios.preview-pdf', ReportStudioTemplate::query()->first());
        $this->appendModelRouteCheck($checks, 'label preview PDF', 'vap_labels.preview-pdf', VAPLabel::query()->first());
        $this->appendModelRouteCheck($checks, 'inventory order PDF', 'vap-inventory.orders.export-pdf', InventoryOrder::query()->first());
        $this->appendModelRouteCheck($checks, 'inventory need PDF', 'vap-inventory.needs.pdf', InventoryNeed::query()->first());
        $this->appendModelRouteCheck($checks, 'sample entry PDF', 'vap_samples.samples.pdf', VAPSampleEntry::query()->first());
        $this->appendModelRouteCheck($checks, 'sample discard PDF', 'vap_samples.discards.pdf', VAPSampleDiscard::query()->first());
        $this->appendModelRouteCheck($checks, 'proficiency test results template', 'proficiency_tests.results.template', ProficiencyTest::query()->first(), false);
        $this->appendModelRouteCheck($checks, 'non conformity detail PDF', 'vap_non_conformities.export.details.pdf', VAPNonConformity::query()->first());

        $batch = InventoryBatch::query()->first();
        if ($batch) {
            $checks[] = [
                'label' => 'batch labels PDF',
                'url' => route('printBatchLabels', ['ids' => $batch->id]),
                'expects_pdf' => true,
            ];
        }

        $checks[] = [
            'label' => 'non conformity list PDF',
            'url' => route('vap_non_conformities.export.pdf'),
            'expects_pdf' => true,
        ];

        $failures = [];
        $this->assertNotEmpty($checks, 'Expected at least one document endpoint to smoke test.');

        foreach ($checks as $check) {
            $response = $this->actingAs($user)->get($check['url']);

            if ($response->getStatusCode() >= 500) {
                $failures[] = sprintf(
                    'Expected [%s] document endpoint [%s] not to return a server error, got HTTP %d.',
                    $check['label'],
                    $check['url'],
                    $response->getStatusCode()
                );

                continue;
            }

            if (($check['expects_pdf'] ?? true) && $response->isSuccessful()) {
                $content = $response->getContent();
                $contentType = (string) $response->headers->get('content-type');

                if (! str_contains($contentType, 'application/pdf') && ! str_starts_with($content, '%PDF')) {
                    $failures[] = sprintf(
                        'Expected [%s] document endpoint [%s] to return PDF content, got content type [%s].',
                        $check['label'],
                        $check['url'],
                        $contentType
                    );
                }
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_missing_commercial_document_pdfs_return_not_found_instead_of_server_errors(): void
    {
        $user = $this->verifiedAdmin();
        $missingId = 999999999;

        foreach ([
            'invoices.getPDF',
            'quotes.getPDF',
            'receipts.getPDF',
            'creditnotes.getPDF',
            'contractguides.getPDF',
            'importcertificates.getPDF',
            'exportcertificates.getPDF',
            'qualitycertificates.getPDF',
        ] as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName, ['id' => $missingId]))
                ->assertNotFound();
        }
    }

    public function test_credit_note_invoice_data_endpoint_returns_safe_json_when_invoice_is_missing(): void
    {
        $user = $this->verifiedAdmin();

        $this->actingAs($user)
            ->getJson(route('creditnotes.getInvoiceData'))
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($user)
            ->getJson(route('creditnotes.getInvoiceData', ['id' => 999999999]))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_missing_collection_lifecycle_document_endpoints_do_not_return_server_errors(): void
    {
        $user = $this->verifiedAdmin();
        $missingId = 999999999;

        foreach ([
            'directcollections.getParametersToAnalyzePDF',
            'directcollections.getCollectionTermPDF',
            'directcollections.getCollectionLabels',
            'programmedcollections.getParametersToAnalyzePDF',
            'programmedcollections.getCollectionTermPDF',
            'programmedcollections.getCollectionLabels',
        ] as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName, ['id' => $missingId]))
                ->assertNotFound();
        }

        foreach ([
            'directcollections.exportParametersToAnalyzeSheet',
            'programmedcollections.exportParametersToAnalyzeSheet',
        ] as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName, ['recordIds' => [$missingId]]))
                ->assertNotFound();
        }

        foreach ([
            'directcollections.getMultipleParametersToAnalyzePDF',
            'programmedcollections.getMultipleParametersToAnalyzePDF',
        ] as $routeName) {
            $response = $this->actingAs($user)
                ->get(route($routeName, ['recordIds' => [$missingId]]));

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                sprintf('Expected [%s] not to return a server error.', $routeName)
            );
        }
    }

    /**
     * @param  array<int, array{label: string, url: string, expects_pdf: bool}>  $checks
     */
    private function appendLegacyPdfCheck(array &$checks, string $label, string $routeName, ?object $record): void
    {
        if (! $record) {
            return;
        }

        $checks[] = [
            'label' => $label,
            'url' => route($routeName, ['id' => $record->id]),
            'expects_pdf' => true,
        ];
    }

    /**
     * @param  array<int, array{label: string, url: string, expects_pdf: bool}>  $checks
     */
    private function appendModelRouteCheck(array &$checks, string $label, string $routeName, ?object $record, bool $expectsPdf = true): void
    {
        if (! $record) {
            return;
        }

        $checks[] = [
            'label' => $label,
            'url' => route($routeName, $record),
            'expects_pdf' => $expectsPdf,
        ];
    }
}
