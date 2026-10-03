<?php

namespace App\Services;

use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProposalPdfDocument
{
    public const RELATIONS = ['customer', 'warehouse', 'department', 'user', 'template', 'complianceAgreement', 'items.standard', 'items.unit'];

    public function __construct(
        private readonly ReportStudioPdfBuilder $builder,
        private readonly ReportStudioPdfRenderer $renderer,
        private readonly GeneralSettings $settings
    ) {}

    /** @return array{content: string, renderer: string, filename: string, fingerprint: string} */
    public function render(VAPProposal $proposal): array
    {
        $proposal->load(self::RELATIONS);
        $fingerprint = $this->fingerprint($proposal);
        $content = $proposal->template?->content
            ? VAPProposalTemplate::parseContent($proposal->template->content, $proposal, $this->settings)
            : '<p>Sem conteúdo configurado para esta proposta.</p>';
        $payload = $this->builder->buildProposalPayload($proposal, $content, $this->settings);
        $filename = str($proposal->proposal_number)->slug('-')->prepend('Proposta-')->append('.pdf')->value();

        return [...$this->renderer->renderDocument('proposal', $payload, $filename), 'filename' => $filename, 'fingerprint' => $fingerprint];
    }

    public function assertUnchanged(VAPProposal $proposal, string $fingerprint): void
    {
        $proposal->load(self::RELATIONS);
        abort_unless($fingerprint === $this->fingerprint($proposal), 409,
            'A proposta mudou durante a geração. Actualize a página e tente novamente.');
    }

    public function fingerprint(VAPProposal $proposal): string
    {
        return hash('sha256', json_encode($this->snapshot($proposal), JSON_THROW_ON_ERROR));
    }

    /** @return array{attributes: array<string, mixed>, relations: array<string, mixed>} */
    private function snapshot(Model $model): array
    {
        $relations = [];
        foreach ($model->getRelations() as $name => $related) {
            $relations[$name] = match (true) {
                $related instanceof Model => $this->snapshot($related),
                $related instanceof Collection => $related->sortBy(fn (Model $item): string => (string) $item->getKey())
                    ->map(fn (Model $item): array => $this->snapshot($item))->values()->all(),
                default => $related,
            };
        }

        return ['attributes' => $model->getRawOriginal(), 'relations' => $relations];
    }
}
