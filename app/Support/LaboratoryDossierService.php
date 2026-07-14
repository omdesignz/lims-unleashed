<?php

namespace App\Support;

use App\Models\Analysis;
use App\Models\QualityCertificate;
use App\Models\User;
use App\Models\VAPProposal;
use App\Models\VAPSampleEntry;
use Illuminate\Support\Collection;

class LaboratoryDossierService
{
    /**
     * @return array<int, string>
     */
    public function relations(): array
    {
        return [
            'customer:id,name,code',
            'warehouse:id,name,address',
            'department:id,name',
            'complianceAgreement',
            'sampleEntries:id,name,code,status,proposal_id,collection_product_id,customer_id,department_id,received_at,updated_at',
            'sampleEntries.collectionProduct:id,customer_id,warehouse_id,product_id,status,obs',
            'sampleEntries.collectionProduct.product:id,name',
            'sampleEntries.collectionProduct.code:id,collection_id,code',
            'sampleEntries.collectionProduct.code.samples:id,cl_id,code',
            'sampleEntries.collectionProduct.code.samples.analysis:id,sample_id,profile_id,department_id,end_date',
            'sampleEntries.collectionProduct.code.samples.analysis.profile:id,name',
            'sampleEntries.collectionProduct.code.samples.analysis.department:id,name',
            'sampleEntries.collectionProduct.code.samples.results:id,sample_id,inserted_date,verified_date,approved_date',
            'sampleEntries.collectionProduct.quality_certificate:id,collection_id,code,validated_at,status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function stageOptions(): array
    {
        return [
            'awaiting_samples' => 'Aguardar amostra',
            'intake' => 'Completar recepção',
            'results' => 'Inserir resultados',
            'verification' => 'Verificar resultados',
            'approval' => 'Aprovar resultados',
            'report_generation' => 'Gerar boletim',
            'report_validation' => 'Validar boletim',
            'issued' => 'Emitido',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(VAPProposal $proposal, ?User $user = null): array
    {
        $proposal->loadMissing($this->relations());

        $sampleEntries = $proposal->sampleEntries->values();
        $analyses = $this->analysesFor($sampleEntries);
        $analysisRows = $analyses->map(fn (Analysis $analysis): array => $this->summarizeAnalysis($analysis));
        $reports = $sampleEntries
            ->pluck('collectionProduct.quality_certificate')
            ->filter()
            ->unique('id')
            ->values();

        $stage = $this->resolveStage($sampleEntries, $analysisRows, $reports);
        $counts = [
            'samples' => $sampleEntries->count(),
            'accessioned_samples' => $sampleEntries->whereNotNull('collection_product_id')->count(),
            'analyses' => $analysisRows->count(),
            'results' => $analysisRows->sum('results.total'),
            'inserted_results' => $analysisRows->sum('results.inserted'),
            'verified_results' => $analysisRows->sum('results.verified'),
            'approved_results' => $analysisRows->sum('results.approved'),
            'reports' => $reports->count(),
            'validated_reports' => $reports->whereNotNull('validated_at')->count(),
        ];

        return [
            'id' => $proposal->id,
            'proposal_number' => $proposal->proposal_number,
            'customer' => $proposal->customer?->name,
            'customer_code' => $proposal->customer?->code,
            'service_location' => $proposal->service_location,
            'department' => $proposal->department?->name,
            'accepted_at' => optional($proposal->complianceAgreement?->acknowledged_at ?? $proposal->updated_at)?->toIso8601String(),
            'updated_at' => optional($proposal->updated_at)?->toIso8601String(),
            'stage' => $this->stagePresentation($stage),
            'steps' => $this->stepsFor($stage),
            'counts' => $counts,
            'blockers' => $this->blockersFor($stage, $sampleEntries, $analysisRows, $reports),
            'primary_action' => $this->primaryActionFor($proposal, $stage, $sampleEntries, $analysisRows, $reports, $user),
            'samples' => $sampleEntries->map(fn (VAPSampleEntry $sampleEntry): array => [
                'id' => $sampleEntry->id,
                'code' => $sampleEntry->code,
                'name' => $sampleEntry->name,
                'status' => $sampleEntry->status,
                'received_at' => optional($sampleEntry->received_at)?->toIso8601String(),
                'collection_product_id' => $sampleEntry->collection_product_id,
                'lab_code' => $sampleEntry->collectionProduct?->code?->code,
                'product' => $sampleEntry->collectionProduct?->product?->name,
                'show_url' => route('vap_samples.show', $sampleEntry),
            ])->values(),
            'reports' => $reports->map(fn (QualityCertificate $report): array => [
                'id' => $report->id,
                'code' => $report->code,
                'validated_at' => optional($report->validated_at)?->toIso8601String(),
                'show_url' => route('qualitycertificates.show', $report),
                'pdf_url' => route('qualitycertificates.getPDF', ['id' => $report->id]),
            ])->values(),
            'links' => [
                'proposal_url' => route('vap-proposals.show', $proposal),
                'add_sample_url' => route('vap_samples.index', ['proposal_id' => $proposal->id, 'start' => 1]),
            ],
        ];
    }

    /**
     * @param  Collection<int, VAPSampleEntry>  $sampleEntries
     * @return Collection<int, Analysis>
     */
    private function analysesFor(Collection $sampleEntries): Collection
    {
        return $sampleEntries
            ->flatMap(fn (VAPSampleEntry $sampleEntry): Collection => collect($sampleEntry->collectionProduct?->code?->samples)
                ->map(function ($laboratorySample): ?Analysis {
                    $analysis = $laboratorySample->analysis;

                    if ($analysis) {
                        $analysis->setRelation('sample', $laboratorySample);
                    }

                    return $analysis;
                })
                ->filter())
            ->unique('id')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeAnalysis(Analysis $analysis): array
    {
        $results = collect($analysis->sample?->results);
        $total = $results->count();
        $inserted = $results->whereNotNull('inserted_date')->count();
        $verified = $results->whereNotNull('verified_date')->count();
        $approved = $results->whereNotNull('approved_date')->count();

        return [
            'id' => $analysis->id,
            'profile' => $analysis->profile?->name,
            'department' => $analysis->department?->name,
            'stage' => $this->analysisStage($total, $inserted, $verified, $approved),
            'url' => route('analysis.edit', $analysis),
            'results' => compact('total', 'inserted', 'verified', 'approved'),
        ];
    }

    /**
     * @param  Collection<int, VAPSampleEntry>  $sampleEntries
     * @param  Collection<int, array<string, mixed>>  $analyses
     * @param  Collection<int, QualityCertificate>  $reports
     */
    private function resolveStage(Collection $sampleEntries, Collection $analyses, Collection $reports): string
    {
        if ($sampleEntries->isEmpty()) {
            return 'awaiting_samples';
        }

        $hasIncompleteAccession = $sampleEntries->contains(function (VAPSampleEntry $sampleEntry): bool {
            if (! $sampleEntry->collection_product_id) {
                return true;
            }

            return collect($sampleEntry->collectionProduct?->code?->samples)
                ->pluck('analysis')
                ->filter()
                ->isEmpty();
        });

        if ($hasIncompleteAccession || $analyses->isEmpty()) {
            return 'intake';
        }

        if ($analyses->contains(fn (array $analysis): bool => in_array($analysis['stage'], ['pending_results', 'results'], true))) {
            return 'results';
        }

        if ($analyses->contains(fn (array $analysis): bool => $analysis['stage'] === 'verification')) {
            return 'verification';
        }

        if ($analyses->contains(fn (array $analysis): bool => $analysis['stage'] === 'approval')) {
            return 'approval';
        }

        $accessionedSamples = $sampleEntries->whereNotNull('collection_product_id')->count();

        if ($reports->count() < $accessionedSamples) {
            return 'report_generation';
        }

        if ($reports->contains(fn (QualityCertificate $report): bool => blank($report->validated_at))) {
            return 'report_validation';
        }

        return 'issued';
    }

    private function analysisStage(int $total, int $inserted, int $verified, int $approved): string
    {
        if ($total === 0) {
            return 'pending_results';
        }

        if ($inserted < $total) {
            return 'results';
        }

        if ($verified < $total) {
            return 'verification';
        }

        if ($approved < $total) {
            return 'approval';
        }

        return 'approved';
    }

    /**
     * @return array<string, string>
     */
    private function stagePresentation(string $stage): array
    {
        $presentations = [
            'awaiting_samples' => ['key' => $stage, 'label' => 'Aguardar amostra', 'description' => 'O âmbito comercial está aceite; falta receber ou programar a primeira amostra.', 'tone' => 'hold'],
            'intake' => ['key' => $stage, 'label' => 'Completar recepção', 'description' => 'Há amostras sem registo LIMS completo ou sem âmbito analítico.', 'tone' => 'hold'],
            'results' => ['key' => $stage, 'label' => 'Inserir resultados', 'description' => 'A execução está aberta e existem determinações por registar.', 'tone' => 'instrument'],
            'verification' => ['key' => $stage, 'label' => 'Verificar resultados', 'description' => 'Os resultados inseridos aguardam revisão técnica independente.', 'tone' => 'review'],
            'approval' => ['key' => $stage, 'label' => 'Aprovar resultados', 'description' => 'A verificação terminou; falta a aprovação final do resultado.', 'tone' => 'review'],
            'report_generation' => ['key' => $stage, 'label' => 'Gerar boletim', 'description' => 'Todos os resultados estão aprovados e o boletim pode ser preparado.', 'tone' => 'release'],
            'report_validation' => ['key' => $stage, 'label' => 'Validar boletim', 'description' => 'O boletim existe e aguarda assinatura/libertação autorizada.', 'tone' => 'release'],
            'issued' => ['key' => $stage, 'label' => 'Emitido', 'description' => 'O boletim foi validado e está pronto para distribuição ao cliente.', 'tone' => 'complete'],
        ];

        return $presentations[$stage];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function stepsFor(string $stage): array
    {
        $steps = [
            ['key' => 'accepted', 'label' => 'Aceite'],
            ['key' => 'sample', 'label' => 'Recepção'],
            ['key' => 'accession', 'label' => 'Registo LIMS'],
            ['key' => 'execution', 'label' => 'Execução'],
            ['key' => 'review', 'label' => 'Revisão técnica'],
            ['key' => 'report', 'label' => 'Boletim'],
        ];
        $currentIndex = match ($stage) {
            'awaiting_samples' => 1,
            'intake' => 2,
            'results' => 3,
            'verification', 'approval' => 4,
            'report_generation', 'report_validation' => 5,
            'issued' => 6,
        };

        return collect($steps)->map(function (array $step, int $index) use ($currentIndex): array {
            $step['status'] = $currentIndex === 6 || $index < $currentIndex
                ? 'complete'
                : ($index === $currentIndex ? 'current' : 'pending');

            return $step;
        })->all();
    }

    /**
     * @param  Collection<int, VAPSampleEntry>  $sampleEntries
     * @param  Collection<int, array<string, mixed>>  $analyses
     * @param  Collection<int, QualityCertificate>  $reports
     * @return array<int, string>
     */
    private function blockersFor(string $stage, Collection $sampleEntries, Collection $analyses, Collection $reports): array
    {
        return match ($stage) {
            'awaiting_samples' => ['Nenhuma amostra foi associada ao âmbito aceite.'],
            'intake' => $sampleEntries->whereNull('collection_product_id')->map(
                fn (VAPSampleEntry $sampleEntry): string => ($sampleEntry->code ?: $sampleEntry->name).' precisa de produto e perfis analíticos.'
            )->values()->all() ?: ['O registo laboratorial ainda não gerou análises.'],
            'results' => ['Existem análises sem resultados completos.'],
            'verification' => ['Existem resultados inseridos sem verificação independente.'],
            'approval' => ['Existem resultados verificados sem aprovação final.'],
            'report_generation' => ['Falta gerar '.max($sampleEntries->whereNotNull('collection_product_id')->count() - $reports->count(), 1).' boletim(ns).'],
            'report_validation' => ['Existem boletins sem validação e assinatura autorizada.'],
            default => [],
        };
    }

    /**
     * @param  Collection<int, VAPSampleEntry>  $sampleEntries
     * @param  Collection<int, array<string, mixed>>  $analyses
     * @param  Collection<int, QualityCertificate>  $reports
     * @return array<string, string>
     */
    private function primaryActionFor(
        VAPProposal $proposal,
        string $stage,
        Collection $sampleEntries,
        Collection $analyses,
        Collection $reports,
        ?User $user
    ): array {
        if ($stage === 'awaiting_samples') {
            return ['label' => 'Registar primeira amostra', 'description' => 'Abrir a recepção com cliente, local e proposta já preenchidos.', 'url' => route('vap_samples.index', ['proposal_id' => $proposal->id, 'start' => 1]), 'method' => 'get'];
        }

        if ($stage === 'intake') {
            $sampleEntry = $sampleEntries->first(fn (VAPSampleEntry $entry): bool => ! $entry->collection_product_id) ?? $sampleEntries->first();

            return ['label' => 'Completar recepção', 'description' => 'Definir produto, perfis e cadeia de custódia para gerar as análises.', 'url' => route('vap_samples.show', $sampleEntry), 'method' => 'get'];
        }

        if (in_array($stage, ['results', 'verification', 'approval'], true)) {
            $targetStages = match ($stage) {
                'results' => ['pending_results', 'results'],
                'verification' => ['verification'],
                default => ['approval'],
            };
            $analysis = $analyses->first(fn (array $analysis): bool => in_array($analysis['stage'], $targetStages, true)) ?? $analyses->first();

            return ['label' => $this->stagePresentation($stage)['label'], 'description' => 'Abrir a análise exacta que está a bloquear este dossier.', 'url' => $analysis['url'], 'method' => 'get'];
        }

        if ($stage === 'report_generation') {
            if (! $user?->can('view_quality_certificates') && ! $user?->can('approve_results')) {
                return [
                    'label' => 'Aguardar emissão autorizada',
                    'description' => 'Os resultados estão aprovados. Um responsável documental deve gerar o boletim.',
                    'url' => route('laboratory-workflow.index'),
                    'method' => 'get',
                    'disabled' => true,
                ];
            }

            return ['label' => 'Gerar boletim', 'description' => 'Criar os boletins em falta a partir dos resultados aprovados.', 'url' => route('laboratory-workflow.reports.store', $proposal), 'method' => 'post'];
        }

        $report = $reports->first(fn (QualityCertificate $certificate): bool => blank($certificate->validated_at)) ?? $reports->first();

        if ($stage === 'report_validation') {
            return ['label' => 'Validar boletim', 'description' => 'Rever, assinar e libertar o documento ao cliente.', 'url' => route('qualitycertificates.show', $report), 'method' => 'get'];
        }

        return ['label' => 'Abrir boletim emitido', 'description' => 'Consultar o documento final e a respectiva rastreabilidade.', 'url' => route('qualitycertificates.show', $report), 'method' => 'get'];
    }
}
