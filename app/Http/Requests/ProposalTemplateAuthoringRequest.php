<?php

namespace App\Http\Requests;

use App\Models\VAPProposalTemplate;
use App\Support\ProposalTemplateValidation;
use Illuminate\Foundation\Http\FormRequest;

class ProposalTemplateAuthoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($this->isPreview()) {
            return (bool) ($user?->can('add_proposal_templates') || $user?->can('edit_proposal_templates'));
        }

        return (bool) $user?->can($this->isMethod('post') ? 'add_proposal_templates' : 'edit_proposal_templates');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $template = $this->route('proposalTemplate');

        return app(ProposalTemplateValidation::class)->rules($this->all(), $template instanceof VAPProposalTemplate ? $template : null, $this->isPreview());
    }

    /** @return array<\Closure> */
    public function after(): array
    {
        return [app(ProposalTemplateValidation::class)->after(['_token', '_method'])];
    }

    /** @return array<string, mixed> */
    public function templatePayload(): array
    {
        return app(ProposalTemplateValidation::class)->normalize($this->validated());
    }

    private function isPreview(): bool
    {
        return $this->routeIs('vap-proposals.templates.preview-draft-pdf');
    }
}
