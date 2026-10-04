<?php

namespace Tests\Feature;

use App\Models\CollectionProduct;
use App\Models\Customer;
use App\Models\LabCode;
use App\Models\QualityCertificate;
use App\Models\QualityCertificateRevision;
use App\Models\Result;
use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioDefaultTemplates;
use App\Support\ReportStudioPdfBuilder;
use App\Support\TestReportContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The test report against ISO/IEC 17025:2017 clause 7.8: what it must carry,
 * and that it states only what was recorded.
 */
class TestReportContentTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @param  array<string, mixed>  $certificateAttributes
     * @param  array<string, mixed>  $collectionAttributes
     * @param  array<string, mixed>  $entryAttributes
     */
    private function certificate(array $certificateAttributes = [], array $collectionAttributes = [], array $entryAttributes = []): QualityCertificate
    {
        $customer = Customer::create(['name' => 'Cliente de ensaio '.Str::uuid()]);
        $site = Warehouse::create(['name' => 'Instalação '.Str::uuid(), 'customer_id' => $customer->id, 'email' => Str::uuid().'@example.test',
            'address' => 'Rua do Ensaio, 12', 'municipality' => 'Luanda', 'nif' => '5000000001']);
        $collection = new CollectionProduct(['customer_id' => $customer->id, 'warehouse_id' => $site->id, ...$collectionAttributes]);
        $collection->saveQuietly();
        VAPSampleEntry::factory()->createQuietly(['customer_id' => $customer->id, 'warehouse_id' => $site->id,
            'collection_product_id' => $collection->id, 'received_at' => '2026-09-28 09:00:00',
            // The intake record and the collected product carry the same sampling responsibility.
            'collected_by_lab' => $collectionAttributes['collected_by_lab'] ?? false, ...$entryAttributes]);
        $code = new LabCode(['collection_id' => $collection->id, 'code' => 'LAB-'.Str::uuid(), 'cl_month' => now()->format('Y'), 'codeable_type' => 'analysis']);
        $code->saveQuietly();
        $certificate = new QualityCertificate(['customer_id' => $customer->id, 'warehouse_id' => $site->id, 'user_id' => User::factory()->create()->id,
            'code' => 'BA-'.Str::uuid(), 'collection_id' => $collection->id, 'cl_id' => $code->id, ...$certificateAttributes]);
        $certificate->saveQuietly();

        return $certificate->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function analysisResult(QualityCertificate $certificate, array $attributes): Result
    {
        $result = new Result;
        $result->forceFill(['code_id' => $certificate->cl_id, 'approved_date' => '2026-09-30 16:00:00', 'inserted_date' => '2026-09-29 10:00:00', ...$attributes]);
        $result->saveQuietly();

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function content(QualityCertificate $certificate): array
    {
        return app(TestReportContent::class)->forCertificate($certificate->fresh(), app(GeneralSettings::class));
    }

    public function test_an_authorised_report_carries_every_common_element_of_clause_7_8_2(): void
    {
        $certificate = $this->certificate(
            ['validated_at' => '2026-10-01 11:00:00', 'validated_by' => 'Ana Técnica', 'obs' => 'Amostra homogeneizada antes do ensaio.'],
            ['lot' => 'LT-77', 'origin' => 'Namibe', 'collected_by_lab' => true, 'collection_date' => '2026-09-27', 'location' => 'Tanque 3', 'sampling_plan_ref' => 'PA-01'],
        );
        $this->analysisResult($certificate, ['parameter_label' => 'pH', 'approved_value' => '7.1', 'unit_label' => 'pH', 'standard_label' => 'ISO 10523',
            'uncertainty_value' => '0.2', 'min_ref_value' => '6.5', 'max_ref_value' => '8.5', 'ref_val_origin' => 'Decreto 261/11']);

        $content = $this->content($certificate);
        $body = implode("\n", $content);

        $this->assertSame('Relatório de Ensaio', $content['{report_title}']);
        $this->assertSame('0', $content['{document_revision}']);
        $this->assertSame('01/10/2026', $content['{issue_date}']);
        $this->assertSame('', $content['{report_notices}']);

        // e) customer; g) item; k) sampling; h, i, j) dates.
        $this->assertStringContainsString('Rua do Ensaio, 12, Luanda', $content['{customer_block}']);
        $this->assertStringContainsString('5000000001', $content['{customer_block}']);
        $this->assertStringContainsString('LT-77', $content['{item_block}']);
        $this->assertStringContainsString('Realizada pelo laboratório', $content['{sampling_block}']);
        $this->assertStringContainsString('27/09/2026', $content['{sampling_block}']);
        $this->assertStringContainsString('Tanque 3', $content['{sampling_block}']);
        $this->assertStringContainsString('PA-01', $content['{sampling_block}']);
        foreach (['Recepção da amostra', '28/09/2026', 'Início dos ensaios', '29/09/2026', 'Fim dos ensaios', '30/09/2026', 'Emissão do relatório', '01/10/2026'] as $date) {
            $this->assertStringContainsString($date, $content['{dates_block}']);
        }

        // f, m) method and result with unit; 7.8.3.1 c) uncertainty; limits and their origin.
        foreach (['ISO 10523', '>7.1</td>', '± 0.2', '6.5 – 8.5', 'Conforme', 'Decreto 261/11'] as $cell) {
            $this->assertStringContainsString($cell, $content['{results_block}']);
        }

        // 7.8.6.2: which results, against what, and the decision rule.
        $this->assertStringContainsString('Todos os parâmetros ensaiados foram comparados', $content['{conformity_block}']);
        $this->assertStringContainsString('cumprem os limites indicados', $content['{conformity_block}']);
        $this->assertStringContainsString('Regra de decisão aplicada: aceitação simples', $content['{conformity_block}']);

        // l) results relate only to the item; the reproduction notice; customer-supplied data (7.8.2.2).
        $this->assertStringContainsString('Os resultados referem-se exclusivamente ao item ensaiado.', $content['{statements_block}']);
        $this->assertStringContainsString('só pode ser reproduzido na íntegra', $content['{statements_block}']);
        $this->assertStringContainsString('(c) foi fornecida pelo cliente', $content['{statements_block}']);
        $this->assertStringContainsString('<span class="doc-flag">(c)</span>', $content['{item_block}']);

        // o) who authorised it, and d) a marked end.
        $this->assertStringContainsString('Ana Técnica', $content['{authorisation_block}']);
        $this->assertStringContainsString('Autorizado em 01/10/2026', $content['{authorisation_block}']);
        $this->assertStringContainsString('Fim do relatório de ensaio', $content['{end_of_report}']);
        $this->assertStringContainsString('Amostra homogeneizada antes do ensaio.', $content['{observations_block}']);

        // Nothing unrecorded is printed as a placeholder value.
        foreach (['N/D', 'N/A', 'Sem lote', 'Não informado', 'Validação pendente'] as $filler) {
            $this->assertStringNotContainsString($filler, $body);
        }
    }

    public function test_sections_are_numbered_in_order_without_gaps_when_some_are_empty(): void
    {
        $certificate = $this->certificate(['validated_at' => now(), 'validated_by' => 'Ana Técnica']);
        $this->analysisResult($certificate, ['parameter_label' => 'Turvação', 'approved_value' => '0.8', 'unit_label' => 'NTU']);

        $content = $this->content($certificate);
        $body = $content['{customer_block}'].$content['{item_block}'].$content['{sampling_block}'].$content['{dates_block}'].$content['{results_block}']
            .$content['{conformity_block}'].$content['{observations_block}'].$content['{statements_block}'].$content['{authorisation_block}'];
        preg_match_all('/<span class="doc-section-number">(\d+)\.<\/span>/', $body, $matches);

        // No limits and no observations were recorded: those sections are left out and the numbering closes up.
        $this->assertSame('', $content['{conformity_block}']);
        $this->assertSame('', $content['{observations_block}']);
        $this->assertSame(range(1, count($matches[1])), array_map('intval', $matches[1]));
        $this->assertStringNotContainsString('Limites', $content['{results_block}']);
        $this->assertStringNotContainsString('Incerteza', $content['{results_block}']);
        $this->assertStringNotContainsString('Apreciação', $content['{results_block}']);
    }

    public function test_an_unauthorised_report_says_so_and_names_nobody(): void
    {
        $certificate = $this->certificate();
        $this->analysisResult($certificate, ['parameter_label' => 'pH', 'inserted_value' => '7.1', 'approved_date' => null]);

        $content = $this->content($certificate);

        $this->assertStringContainsString('Documento não autorizado.', $content['{report_notices}']);
        $this->assertStringContainsString('Contém resultados ainda não aprovados', $content['{report_notices}']);
        $this->assertStringContainsString('Não é válido como relatório de ensaio.', $content['{report_notices}']);
        $this->assertStringContainsString('Relatório por autorizar.', $content['{authorisation_block}']);
        $this->assertStringNotContainsString('doc-auth-sign', $content['{authorisation_block}']);
    }

    public function test_sampling_by_the_customer_limits_the_results_to_the_item_as_received(): void
    {
        $certificate = $this->certificate(['validated_at' => now(), 'validated_by' => 'Ana Técnica'], ['collected_by_lab' => false]);
        $this->analysisResult($certificate, ['parameter_label' => 'pH', 'approved_value' => '7.1']);

        $content = $this->content($certificate);

        $this->assertStringContainsString('Realizada pelo cliente', $content['{sampling_block}']);
        $this->assertStringContainsString('tal como recebido pelo laboratório', $content['{statements_block}']);
        $this->assertStringContainsString('A amostragem não foi da responsabilidade do laboratório.', $content['{statements_block}']);
    }

    public function test_an_amended_report_identifies_the_change_and_what_it_replaces(): void
    {
        $certificate = $this->certificate(['validated_at' => '2026-10-01 11:00:00', 'validated_by' => 'Ana Técnica']);
        $this->analysisResult($certificate, ['parameter_label' => 'pH', 'approved_value' => '7.1']);
        QualityCertificateRevision::withoutEvents(fn () => QualityCertificateRevision::query()->create([
            'quality_certificate_id' => $certificate->id, 'revision_number' => 2, 'version' => '1.1', 'is_current' => true,
            'change_reason' => 'Correcção da unidade do pH', 'change_type' => 'CORRECTION', 'effective_date' => '2026-10-03 09:30:00',
            'created_by_id' => $certificate->user_id, 'snapshot_data' => [],
        ]));

        $content = $this->content($certificate);

        $this->assertSame('2', $content['{document_revision}']);
        $this->assertSame('03/10/2026', $content['{issue_date}']);
        $this->assertStringContainsString('Alteração ao Relatório de Ensaio n.º '.$certificate->code, $content['{report_notices}']);
        $this->assertStringContainsString('substitui a versão anterior deste relatório', $content['{report_notices}']);
        $this->assertStringContainsString('Motivo da alteração: Correcção da unidade do pH', $content['{report_notices}']);
    }

    /**
     * @return array<string, array{string, ?string, ?string, ?string}>
     */
    public static function assessments(): array
    {
        return [
            'inside both limits' => ['7,2', '6,5', '8,5', 'Conforme'],
            'on the upper limit' => ['8.5', null, '8.5', 'Conforme'],
            'above the maximum' => ['9.1', '6.5', '8.5', 'Não conforme'],
            'below the minimum' => ['5', '6.5', null, 'Não conforme'],
            'less-than within the maximum' => ['< 1', null, '10', 'Conforme'],
            'less-than cannot prove a minimum' => ['< 1', '0.5', null, null],
            'less-than above the maximum is not decided' => ['< 20', null, '10', null],
            'qualitative result' => ['Ausente', null, '10', null],
            'no limits' => ['7.2', null, null, null],
        ];
    }

    #[DataProvider('assessments')]
    public function test_a_result_is_assessed_only_when_it_can_be_compared_with_its_limits(string $value, ?string $minimum, ?string $maximum, ?string $expected): void
    {
        $certificate = $this->certificate(['validated_at' => now(), 'validated_by' => 'Ana Técnica']);
        $this->analysisResult($certificate, ['parameter_label' => 'Parâmetro A', 'approved_value' => $value, 'min_ref_value' => $minimum, 'max_ref_value' => $maximum]);

        $content = $this->content($certificate);

        if ($expected === null) {
            $this->assertSame('', $content['{conformity_block}']);
            $this->assertStringNotContainsString('<td>Conforme</td>', $content['{results_block}']);
            $this->assertStringNotContainsString('Não conforme', $content['{results_block}']);

            return;
        }

        $this->assertStringContainsString('>'.$expected.'</td>', $content['{results_block}']);
        $this->assertStringContainsString(
            $expected === 'Conforme' ? 'Os resultados apreciados cumprem os limites indicados.' : 'Não cumprem os limites indicados: Parâmetro A.',
            $content['{conformity_block}']
        );
    }

    public function test_recorded_text_is_escaped_and_a_customer_decision_rule_replaces_the_default(): void
    {
        $certificate = $this->certificate(
            ['validated_at' => now(), 'validated_by' => '<b>Ana</b>'],
            ['lot' => '<script>alert(1)</script>'],
            ['client_submitted_info' => ['decision_rule' => 'banda de guarda igual à incerteza expandida']],
        );
        $this->analysisResult($certificate, ['parameter_label' => 'Chumbo <Pb>', 'approved_value' => '0.004', 'max_ref_value' => '0.01']);

        $content = $this->content($certificate);
        $body = implode("\n", $content);

        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringNotContainsString('<b>Ana</b>', $body);
        $this->assertStringContainsString('Chumbo &lt;Pb&gt;', $content['{results_block}']);
        $this->assertStringContainsString('banda de guarda igual à incerteza expandida', $content['{conformity_block}']);
        $this->assertStringNotContainsString('aceitação simples', $content['{conformity_block}']);
    }

    public function test_the_generated_report_resolves_every_placeholder_and_keeps_document_control_on_each_page(): void
    {
        $certificate = $this->certificate(['validated_at' => '2026-10-01 11:00:00', 'validated_by' => 'Ana Técnica']);
        $this->analysisResult($certificate, ['parameter_label' => 'pH', 'approved_value' => '7.1', 'max_ref_value' => '8.5']);

        $payload = app(ReportStudioPdfBuilder::class)->buildAnalysisReportPayload(
            $certificate->fresh(),
            app(GeneralSettings::class),
            ReportStudioDefaultTemplates::make('analysis')
        );
        $data = $payload['data'];

        foreach (['firstPageHeader', 'defaultHeader', 'footerHtml', 'bodyHtml'] as $surface) {
            $this->assertDoesNotMatchRegularExpression('/\{\{?[a-z_]+\}?\}/', preg_replace('/\{PAGENO\}|\{nbpg\}/', '', (string) $data[$surface]), $surface);
        }

        // a) title, b) laboratory, d) unique identification on the first page and on every other page.
        $this->assertStringContainsString('Relatório de Ensaio', $data['firstPageHeader']);
        $this->assertStringContainsString($certificate->code, $data['firstPageHeader']);
        $this->assertStringContainsString('class="doc-lab-name"', $data['firstPageHeader']);
        $this->assertStringContainsString($certificate->code, $data['defaultHeader']);
        $this->assertStringContainsString($certificate->code, $data['footerHtml']);
        $this->assertStringContainsString('Página {PAGENO} de {nbpg}', $data['footerHtml']);
        $this->assertStringContainsString('só pode ser reproduzido na íntegra', $data['footerHtml']);
        $this->assertStringContainsString('Fim do relatório de ensaio', $data['bodyHtml']);
        $this->assertSame('#ffffff', data_get(ReportStudioDefaultTemplates::make('analysis')->layout_schema, 'page_background_color'));
    }
}
