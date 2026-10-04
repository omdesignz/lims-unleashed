<?php

namespace App\Support;

use App\Models\QualityCertificate;
use App\Settings\GeneralSettings;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * The content of a test report, structured after ISO/IEC 17025:2017 clause 7.8.
 *
 * The report states only what the laboratory recorded: a value that was not
 * recorded is left out, never filled with a general phrase. Each block maps to a
 * requirement of the standard:
 *
 * - customer (7.8.2.1 e), item and its condition (g), sampling (k, 7.8.5),
 *   dates of receipt, performance and issue (h, i, j);
 * - results with units (m), method (f), measurement uncertainty (7.8.3.1 c),
 *   limits and a statement of conformity with its decision rule (7.8.6);
 * - the statements that travel with the results (l, 7.8.2.2), who authorised
 *   the report (o), the amendment it carries (7.8.8) and a marked end (d).
 */
class TestReportContent
{
    public const TITLE = 'Relatório de Ensaio';

    /** Marks information the customer supplied (7.8.2.2). */
    private const CUSTOMER_FLAG = 'c';

    /**
     * The rule the report applies when it compares a result with its limits.
     * It describes the comparison this class performs, nothing more.
     */
    public const SIMPLE_ACCEPTANCE_RULE = 'aceitação simples: o resultado é comparado directamente com os limites indicados, sem alargar ou reduzir esses limites pela incerteza de medição';

    /**
     * @return array<string, string> template placeholders and their HTML or text
     */
    public function forCertificate(QualityCertificate $certificate, GeneralSettings $settings): array
    {
        return $this->placeholders($this->reportFromCertificate($certificate), $settings);
    }

    /**
     * A representative report for the template preview, clearly fictional.
     *
     * @return array<string, string>
     */
    public function forPreview(GeneralSettings $settings): array
    {
        $today = now();

        return $this->placeholders([
            'code' => 'BA-2026-001',
            'revision' => 0,
            'issued_at' => $today->format('d/m/Y'),
            'authorised' => true,
            'unapproved_results' => 0,
            'amendment' => null,
            'customer' => [
                ['label' => 'Cliente', 'value' => 'Cliente laboratorial de referência'],
                ['label' => 'NIF', 'value' => '5000000000'],
                ['label' => 'Morada', 'value' => 'Rua de exemplo, n.º 10, Luanda'],
                ['label' => 'Contacto', 'value' => 'Responsável da qualidade · 900 000 000'],
            ],
            'item' => [
                ['label' => 'Código laboratorial', 'value' => 'LAB-2026-042'],
                ['label' => 'Entrada de amostra', 'value' => 'SE-2026-001'],
                ['label' => 'Designação', 'value' => 'Farinha de trigo tipo 65'],
                ['label' => 'Matriz', 'value' => 'Cereais e derivados'],
                ['label' => 'Lote', 'value' => 'LT-26-041', 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Origem', 'value' => 'Recepção interna', 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Quantidade recebida', 'value' => '2 kg'],
                ['label' => 'Embalagem', 'value' => 'Saco selado'],
                ['label' => 'Estado à recepção', 'value' => 'Embalagem íntegra, selada e identificada; temperatura ambiente.', 'wide' => true],
            ],
            'sampling' => [
                'by_laboratory' => true,
                'rows' => [
                    ['label' => 'Amostragem', 'value' => 'Realizada pelo laboratório'],
                    ['label' => 'Data de colheita', 'value' => $today->copy()->subDays(3)->format('d/m/Y')],
                    ['label' => 'Local de colheita', 'value' => 'Armazém de matéria-prima'],
                    ['label' => 'Plano de amostragem', 'value' => 'PA-MP-01'],
                ],
            ],
            'dates' => [
                ['label' => 'Recepção da amostra', 'value' => $today->copy()->subDays(2)->format('d/m/Y')],
                ['label' => 'Início dos ensaios', 'value' => $today->copy()->subDays(2)->format('d/m/Y')],
                ['label' => 'Fim dos ensaios', 'value' => $today->copy()->subDay()->format('d/m/Y')],
                ['label' => 'Emissão do relatório', 'value' => $today->format('d/m/Y')],
            ],
            'results' => [
                ['group' => 'Físico-química', 'parameter' => 'Humidade', 'method' => 'ISO 712', 'value' => '13,2', 'unit' => '%', 'uncertainty' => '0,4', 'minimum' => null, 'maximum' => '14,5', 'origin' => 'Especificação do cliente'],
                ['group' => 'Físico-química', 'parameter' => 'Cinzas', 'method' => 'ISO 2171', 'value' => '0,62', 'unit' => '%', 'uncertainty' => '0,03', 'minimum' => null, 'maximum' => '0,65', 'origin' => 'Especificação do cliente'],
                ['group' => 'Microbiologia', 'parameter' => 'Bolores e leveduras', 'method' => 'ISO 21527-2', 'value' => '1,2 × 10^2', 'unit' => 'UFC/g', 'uncertainty' => null, 'minimum' => null, 'maximum' => null, 'origin' => null],
                ['group' => 'Microbiologia', 'parameter' => 'Salmonella spp.', 'method' => 'ISO 6579-1', 'value' => 'Não detectada', 'unit' => 'em 25 g', 'uncertainty' => null, 'minimum' => null, 'maximum' => null, 'origin' => null],
            ],
            'decision_rule' => null,
            'observations' => null,
            'authoriser' => ['name' => 'Direcção técnica', 'role' => 'Responsável pela autorização do relatório', 'date' => $today->format('d/m/Y'), 'signature' => null, 'caption' => null],
        ], $settings);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, string>
     */
    private function placeholders(array $report, GeneralSettings $settings): array
    {
        $results = collect($report['results']);
        $assessments = $results->map(fn (array $result): ?bool => $this->conforms($result));
        $hasCustomerInformation = collect($report['item'])->contains(fn (array $row): bool => filled($row['value'] ?? null) && ($row['flag'] ?? null) === self::CUSTOMER_FLAG);
        $section = 0;

        $notices = '';
        if (! $report['authorised'] || $report['unapproved_results'] > 0) {
            $notices .= ControlledDocument::notice('Documento não autorizado.', $report['unapproved_results'] > 0
                ? 'Contém resultados ainda não aprovados e não foi revisto nem autorizado para emissão. Não é válido como relatório de ensaio.'
                : 'Ainda não foi revisto nem autorizado para emissão. Não é válido como relatório de ensaio.');
        }
        if (is_array($report['amendment'])) {
            $notices .= ControlledDocument::notice(
                'Alteração ao '.self::TITLE.' n.º '.$report['code'].'.',
                trim('Esta revisão '.$report['revision'].', de '.$report['amendment']['date'].', substitui a versão anterior deste relatório. '
                    .(filled($report['amendment']['reason']) ? 'Motivo da alteração: '.$report['amendment']['reason'] : ''))
            );
        }

        $customer = ControlledDocument::section(++$section, 'Cliente', ControlledDocument::keyValueGrid($report['customer']));
        $item = ControlledDocument::section(++$section, 'Item ensaiado', ControlledDocument::keyValueGrid($report['item']));
        $samplingGrid = ControlledDocument::keyValueGrid($report['sampling']['rows']);
        $sampling = $samplingGrid === '' ? '' : ControlledDocument::section(++$section, 'Amostragem', $samplingGrid);
        $dates = ControlledDocument::section(++$section, 'Datas', ControlledDocument::keyValueGrid($report['dates']));
        $resultsTable = $this->resultsTable($results, $assessments);
        $resultsSection = ControlledDocument::section(++$section, 'Resultados', $resultsTable.$this->resultNotes($results, $assessments));
        $conformity = $this->conformityStatement($assessments, $results, $report['decision_rule']);
        $conformitySection = $conformity === '' ? '' : ControlledDocument::section(++$section, 'Declaração de conformidade', '<p class="doc-text">'.$conformity.'</p>');
        $observations = filled($report['observations'])
            ? ControlledDocument::section(++$section, 'Observações', '<p class="doc-text">'.nl2br(e((string) $report['observations']), false).'</p>')
            : '';
        $statements = $this->statements($report['sampling']['by_laboratory'], $hasCustomerInformation);
        $statementsSection = ControlledDocument::section(++$section, 'Declarações', ControlledDocument::statements($statements), keepTogether: true);
        $authorisation = ControlledDocument::section(++$section, 'Autorização', $report['authorised']
            ? ControlledDocument::authorisation([$report['authoriser']])
            : '<p class="doc-text">Relatório por autorizar.</p>', keepTogether: true);
        $end = ControlledDocument::endMark('relatório de ensaio');

        return [
            '{report_title}' => self::TITLE,
            '{document_revision}' => (string) $report['revision'],
            '{issue_date}' => (string) $report['issued_at'],
            '{report_notices}' => $notices,
            '{customer_block}' => $customer,
            '{item_block}' => $item,
            '{sampling_block}' => $sampling,
            '{dates_block}' => $dates,
            '{results_block}' => $resultsSection,
            '{results_table}' => $resultsTable,
            '{conformity_block}' => $conformitySection,
            '{observations_block}' => $observations,
            '{statements_block}' => $statementsSection,
            '{authorisation_block}' => $authorisation,
            '{end_of_report}' => $end,
            '{uncertainty_statement}' => $results->contains(fn (array $result): bool => filled($result['uncertainty']))
                ? 'A incerteza indicada é a incerteza de medição associada a cada resultado, expressa na unidade do resultado.'
                : '',
            '{decision_rule}' => $conformity === '' ? '' : 'Regra de decisão: '.($report['decision_rule'] ?: self::SIMPLE_ACCEPTANCE_RULE).'.',
            '{conclusion}' => strip_tags($conformity),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportFromCertificate(QualityCertificate $certificate): array
    {
        $product = $certificate->collection;
        $entry = $product?->sampleEntry;
        $site = $certificate->warehouse;
        $clientInformation = (array) ($entry?->client_submitted_info ?? data_get($product?->extra_data, 'submitted_payload', []));
        $results = collect($certificate->results ?? [])
            ->sortBy(fn ($result): string => mb_strtolower(($result->category_label ?: '').'|'.($result->parameter_label ?: $result->parameter?->name ?: $result->id)))
            ->values();
        $revision = $certificate->relationLoaded('currentRevision') ? $certificate->getRelation('currentRevision') : $certificate->currentRevision()->first();
        $authorisedAt = $certificate->validated_at ? Carbon::parse($certificate->validated_at) : null;
        $issuedAt = $revision?->effective_date ?? $authorisedAt ?? $certificate->created_at ?? now();
        $collectedByLaboratory = $entry?->collected_by_lab ?? $product?->collected_by_lab;
        $performed = $this->performancePeriod($product, $entry, $results);
        $authoriserName = $certificate->validated_by ?: $certificate->validated_by_user?->name;
        $onBehalfOf = $certificate->validated_on_behalf_of ?: $certificate->validated_on_behalf_of_user?->name;

        return [
            'code' => (string) $certificate->code,
            'revision' => (int) ($revision?->revision_number ?? 0),
            'issued_at' => $this->date($issuedAt),
            'authorised' => $authorisedAt !== null && filled($authoriserName),
            'unapproved_results' => $results->filter(fn ($result): bool => blank($result->approved_date))->count(),
            'amendment' => $revision ? ['date' => $this->date($revision->effective_date), 'reason' => (string) $revision->change_reason] : null,
            'customer' => [
                ['label' => 'Cliente', 'value' => $certificate->customer?->name ?: $site?->name],
                ['label' => 'NIF', 'value' => $site?->nif],
                ['label' => 'Instalação', 'value' => $site?->name !== $certificate->customer?->name ? $site?->name : null],
                ['label' => 'Morada', 'value' => implode(', ', array_filter([$site?->address, $site?->municipality, $site?->province]))],
                ['label' => 'Contacto', 'value' => implode(' · ', array_filter([$site?->focal_point, $site?->focal_point_contact ?: $site?->primary_phone, $site?->focal_point_email ?: $site?->email]))],
            ],
            'item' => [
                ['label' => 'Código laboratorial', 'value' => $certificate->lab_code?->code ?: $product?->code?->code],
                ['label' => 'Entrada de amostra', 'value' => $entry?->code],
                ['label' => 'Designação', 'value' => $product?->product?->name ?: $certificate->product?->name ?: $entry?->name ?: data_get($clientInformation, 'product_name')],
                ['label' => 'Matriz', 'value' => $product?->product?->matrix?->description ?: data_get($clientInformation, 'matrix_description', data_get($clientInformation, 'matrix'))],
                ['label' => 'Tipo de amostra', 'value' => $entry?->sample_type ?: data_get($clientInformation, 'sample_type')],
                ['label' => 'Marca comercial', 'value' => $product?->comercial_brand, 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Lote', 'value' => $product?->lot ?: data_get($clientInformation, 'lot'), 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Origem', 'value' => $product?->origin ?: data_get($clientInformation, 'origin'), 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Fornecedor', 'value' => data_get($clientInformation, 'supplier_name'), 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Data de produção', 'value' => $this->date($product?->production_date ?: data_get($clientInformation, 'production_date')), 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Data de validade', 'value' => $this->date($product?->expiry_date ?: data_get($clientInformation, 'expiry_date')), 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Contentor', 'value' => $product?->container_no, 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Documento único (DU)', 'value' => $product?->du_no, 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Conhecimento de embarque', 'value' => $product?->bl, 'flag' => self::CUSTOMER_FLAG],
                ['label' => 'Quantidade recebida', 'value' => $product?->qty ?: data_get($clientInformation, 'quantity')],
                ['label' => 'Embalagem', 'value' => $product?->packaging?->name ?: $entry?->packaging?->name],
                ['label' => 'Estado à recepção', 'value' => $this->conditionOnReceipt($product, $clientInformation), 'wide' => true],
            ],
            'sampling' => [
                'by_laboratory' => $collectedByLaboratory,
                'rows' => [
                    ['label' => 'Amostragem', 'value' => match ($collectedByLaboratory) {
                        true => 'Realizada pelo laboratório',
                        false => 'Realizada pelo cliente',
                        default => null,
                    }],
                    ['label' => 'Data de colheita', 'value' => $this->date($product?->collection_date ?: $entry?->collected_at)],
                    ['label' => 'Local de colheita', 'value' => $product?->location ?: data_get($clientInformation, 'location', data_get($clientInformation, 'collection_location'))],
                    ['label' => 'Plano de amostragem', 'value' => $product?->sampling_plan_ref ?: data_get($clientInformation, 'sampling_plan_ref')],
                ],
            ],
            'dates' => [
                ['label' => 'Recepção da amostra', 'value' => $this->date($entry?->received_at)],
                ['label' => 'Início dos ensaios', 'value' => $performed['start']],
                ['label' => 'Fim dos ensaios', 'value' => $performed['end']],
                ['label' => 'Emissão do relatório', 'value' => $this->date($issuedAt)],
            ],
            'results' => $results->map(fn ($result): array => [
                'group' => $result->category_label,
                'parameter' => $result->parameter_label ?: $result->parameter?->name ?: 'Parâmetro',
                'method' => $this->method($result),
                'value' => $this->resultValue($result),
                'unit' => $result->unit_label ?: $result->unit?->name,
                'uncertainty' => $result->uncertainty_value ?: data_get($result->calculation_metadata, 'uncertainty'),
                'minimum' => $result->min_ref_value,
                'maximum' => $result->max_ref_value,
                'origin' => $result->ref_val_origin,
            ])->all(),
            'decision_rule' => data_get($clientInformation, 'decision_rule'),
            'observations' => $certificate->obs,
            'authoriser' => [
                'name' => $authoriserName,
                'role' => 'Responsável pela autorização do relatório',
                'caption' => filled($onBehalfOf) && $onBehalfOf !== $authoriserName ? 'Em nome de '.$onBehalfOf : null,
                'date' => $authorisedAt ? 'Autorizado em '.$this->date($authorisedAt) : null,
                'signature' => $this->signatureImage($certificate),
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $results
     * @param  Collection<int, ?bool>  $assessments
     */
    private function resultsTable(Collection $results, Collection $assessments): string
    {
        if ($results->isEmpty()) {
            return '<p class="doc-text">Sem resultados registados.</p>';
        }

        $showUncertainty = $results->contains(fn (array $result): bool => filled($result['uncertainty']));
        $showLimits = $results->contains(fn (array $result): bool => filled($result['minimum']) || filled($result['maximum']));
        $showAssessment = $assessments->contains(fn (?bool $assessment): bool => $assessment !== null);
        $grouped = $results->pluck('group')->filter()->unique()->count() > 1;
        $columns = 4 + (int) $showUncertainty + (int) $showLimits + (int) $showAssessment;

        $head = '<th style="width:26%;">Parâmetro</th><th>Método</th><th class="doc-num">Resultado</th><th>Unidade</th>'
            .($showUncertainty ? '<th class="doc-num">Incerteza</th>' : '')
            .($showLimits ? '<th class="doc-num">Limites</th>' : '')
            .($showAssessment ? '<th>Apreciação</th>' : '');

        $body = '';
        $currentGroup = null;

        foreach ($results as $index => $result) {
            if ($grouped && $result['group'] !== $currentGroup) {
                $currentGroup = $result['group'];
                $body .= '<tr class="doc-results-group"><td colspan="'.$columns.'">'.e($currentGroup ?: 'Outros ensaios').'</td></tr>';
            }

            $assessment = $assessments[$index];
            $body .= '<tr>'
                .'<td>'.e((string) $result['parameter']).'</td>'
                .'<td>'.e((string) ($result['method'] ?: ControlledDocument::NOT_RECORDED)).'</td>'
                .'<td class="doc-num doc-result-value">'.$this->valueHtml($result['value']).'</td>'
                .'<td>'.e((string) ($result['unit'] ?: ControlledDocument::NOT_RECORDED)).'</td>'
                .($showUncertainty ? '<td class="doc-num">'.e($this->uncertainty($result['uncertainty'])).'</td>' : '')
                .($showLimits ? '<td class="doc-num">'.e($this->limits($result)).'</td>' : '')
                .($showAssessment ? '<td'.($assessment === false ? ' class="doc-nonconforming"' : '').'>'.e(match ($assessment) {
                    true => 'Conforme',
                    false => 'Não conforme',
                    default => ControlledDocument::NOT_RECORDED,
                }).'</td>' : '')
                .'</tr>';
        }

        return '<table class="doc-results doc-plain"><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table>';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $results
     * @param  Collection<int, ?bool>  $assessments
     */
    private function resultNotes(Collection $results, Collection $assessments): string
    {
        $notes = [];

        if ($results->contains(fn (array $result): bool => filled($result['uncertainty']))) {
            $notes[] = 'Incerteza: incerteza de medição associada ao resultado, na unidade do resultado.';
        }

        $origins = $results->pluck('origin')->filter()->unique()->values();
        if ($results->contains(fn (array $result): bool => filled($result['minimum']) || filled($result['maximum']))) {
            $notes[] = 'Limites: valores especificados para o parâmetro'.($origins->isNotEmpty() ? ' ('.$origins->implode('; ').')' : '').'.';
        }

        if ($assessments->contains(fn (?bool $assessment): bool => $assessment !== null) && $assessments->contains(null)) {
            $notes[] = ControlledDocument::NOT_RECORDED.' na apreciação: resultado sem limite especificado ou não comparável numericamente.';
        }

        return $notes === [] ? '' : '<p class="doc-notes">'.implode('<br>', array_map('e', $notes)).'</p>';
    }

    /**
     * The statement of conformity (7.8.6.2): which results it covers, against
     * what, and the decision rule applied. Empty when no result has a limit.
     *
     * @param  Collection<int, ?bool>  $assessments
     * @param  Collection<int, array<string, mixed>>  $results
     */
    private function conformityStatement(Collection $assessments, Collection $results, ?string $decisionRule): string
    {
        $assessed = $assessments->filter(fn (?bool $assessment): bool => $assessment !== null);

        if ($assessed->isEmpty()) {
            return '';
        }

        $failed = $assessed->filter(fn (bool $assessment): bool => ! $assessment)->keys()
            ->map(fn (int $index): string => (string) $results[$index]['parameter']);
        $scope = $assessed->count() === $results->count()
            ? 'Todos os parâmetros ensaiados foram comparados'
            : $assessed->count().' de '.$results->count().' parâmetros ensaiados foram comparados';
        $verdict = $failed->isEmpty()
            ? 'Os resultados apreciados cumprem os limites indicados.'
            : 'Não cumprem os limites indicados: '.e($failed->implode(', ')).'. Os restantes resultados apreciados cumprem.';
        $rule = e(rtrim($decisionRule ?: self::SIMPLE_ACCEPTANCE_RULE, '. '));

        return $scope.' com os limites indicados na tabela de resultados. '.$verdict.' Regra de decisão aplicada: '.$rule.'.';
    }

    /**
     * @return array<int, string>
     */
    private function statements(?bool $sampledByLaboratory, bool $hasCustomerInformation): array
    {
        return array_values(array_filter([
            $sampledByLaboratory === false
                ? 'Os resultados referem-se exclusivamente ao item ensaiado, tal como recebido pelo laboratório. A amostragem não foi da responsabilidade do laboratório.'
                : 'Os resultados referem-se exclusivamente ao item ensaiado.',
            $hasCustomerInformation
                ? 'A informação assinalada com ('.self::CUSTOMER_FLAG.') foi fornecida pelo cliente. O laboratório não é responsável por essa informação.'
                : null,
            'Este relatório só pode ser reproduzido na íntegra. A reprodução parcial requer autorização escrita do laboratório.',
        ]));
    }

    /**
     * Whether a result meets its limits: null when it has none or cannot be
     * compared as a number.
     *
     * @param  array<string, mixed>  $result
     */
    private function conforms(array $result): ?bool
    {
        $minimum = $this->number($result['minimum'] ?? null);
        $maximum = $this->number($result['maximum'] ?? null);

        if ($minimum === null && $maximum === null) {
            return null;
        }

        $raw = trim((string) ($result['value'] ?? ''));

        // "< 10" states an upper bound only: it can meet a maximum, never prove a minimum.
        if (preg_match('/^<\s*(.+)$/u', $raw, $matches) === 1) {
            $bound = $this->number($matches[1]);

            return $bound !== null && $minimum === null && $maximum !== null && $bound <= $maximum ? true : null;
        }

        $value = $this->number($raw);

        if ($value === null) {
            return null;
        }

        return ($minimum === null || $value >= $minimum) && ($maximum === null || $value <= $maximum);
    }

    /** A result as markup: powers of ten are set as superscripts. */
    private function valueHtml(mixed $value): string
    {
        $text = e((string) (filled($value) ? $value : ControlledDocument::NOT_RECORDED));

        return (string) preg_replace('/10\^(-?\d+)/', '10<sup>$1</sup>', $text);
    }

    private function number(mixed $value): ?float
    {
        $text = str_replace([' ', "\u{00A0}"], '', trim((string) $value));

        if ($text === '') {
            return null;
        }

        if (str_contains($text, ',') && ! str_contains($text, '.')) {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function limits(array $result): string
    {
        $minimum = filled($result['minimum']) ? (string) $result['minimum'] : null;
        $maximum = filled($result['maximum']) ? (string) $result['maximum'] : null;

        return match (true) {
            $minimum !== null && $maximum !== null => $minimum.' – '.$maximum,
            $maximum !== null => '≤ '.$maximum,
            $minimum !== null => '≥ '.$minimum,
            default => ControlledDocument::NOT_RECORDED,
        };
    }

    private function uncertainty(mixed $value): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return ControlledDocument::NOT_RECORDED;
        }

        return $this->number($text) !== null ? '± '.$text : $text;
    }

    private function method(mixed $result): ?string
    {
        $methods = array_filter([
            $result->standard_label ?: $result->standard?->code ?: $result->standard?->description,
            $result->protocol_label ?: $result->protocol?->code ?: $result->protocol?->description,
            $result->nwp_label,
        ]);

        return $methods === [] ? null : implode(' / ', array_unique($methods));
    }

    private function resultValue(mixed $result): ?string
    {
        $value = $result->approved_value ?: $result->verified_value ?: $result->inserted_value;

        if (blank($value)) {
            return null;
        }

        if (data_get($result->extra_data, 'display_format') !== 'scientific' || ! is_numeric((string) $value)) {
            return (string) $value;
        }

        $precision = min(max((int) ($result->parameter?->decimal_places ?? 2), 0), 8);
        [$mantissa, $exponent] = explode('E', sprintf('%.'.$precision.'E', (float) $value));

        return $mantissa.' × 10^'.((int) $exponent);
    }

    /**
     * @param  array<string, mixed>  $clientInformation
     */
    private function conditionOnReceipt(mixed $product, array $clientInformation): ?string
    {
        $temperature = data_get($clientInformation, 'temperature_condition') ?: $product?->temperature_value;
        $parts = array_filter([
            data_get($clientInformation, 'packaging_condition'),
            filled($temperature) ? 'Temperatura: '.$temperature : null,
            data_get($clientInformation, 'integrity_observations'),
        ]);

        return $parts === [] ? null : implode('. ', array_map(fn (string $part): string => rtrim($part, '. '), $parts)).'.';
    }

    /**
     * @param  Collection<int, mixed>  $results
     * @return array{start: ?string, end: ?string}
     */
    private function performancePeriod(mixed $product, mixed $entry, Collection $results): array
    {
        $start = $entry?->analysis_start_date ?: $product?->analysis_start_date ?: $results->pluck('inserted_date')->filter()->min();
        $end = $entry?->analysis_end_date ?: $product?->analysis_end_date ?: $results->pluck('approved_date')->filter()->max();

        return ['start' => $this->date($start), 'end' => $this->date($end)];
    }

    private function date(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function signatureImage(QualityCertificate $certificate): ?string
    {
        try {
            $path = (string) $certificate->validation_signature_url;
        } catch (Throwable) {
            return null;
        }

        return $path !== '' && is_file($path) ? ControlledDocument::imageDataUri($path) : null;
    }
}
