<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposalComplianceAgreementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            $rules = [
                'proposal_id' => 'required|exists:proposals,id',
                'confidentiality' => 'required|boolean',
                'impartiality' => 'required|boolean',
                'nondisclosure' => 'required|boolean',
                'acknowledged_at' => 'required|boolean',
                'client_ip' => 'required|ip',
            ];
        } else {
            $rules = [
                'proposal_id' => 'required|exists:proposals,id',
                'confidentiality' => 'required|boolean',
                'impartiality' => 'required|boolean',
                'nondisclosure' => 'required|boolean',
                'acknowledged_at' => 'required|boolean',
                'client_ip' => 'required|ip',
            ];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'proposal_id' => trans('gestlab.general.labels.proposal_compliance_agreements.proposal_id'),
            'confidentiality' => trans('gestlab.general.labels.proposal_compliance_agreements.confidentiality'),
            'impartiality' => trans('gestlab.general.labels.proposal_compliance_agreements.impartiality'),
            'nondisclosure' => trans('gestlab.general.labels.proposal_compliance_agreements.nondisclosure'),
            'acknowledged_at' => trans('gestlab.general.labels.proposal_compliance_agreements.acknowledged_at'),
            'client_ip' => trans('gestlab.general.labels.proposal_compliance_agreements.client_ip'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        if ($this->isMethod('post')) {
            $this->merge([
                'client_ip' => request()->ip(),
            ]);
        }
    }
}
