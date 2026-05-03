<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OccurrenceRequest extends FormRequest
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
    public function rules()
    {
        if ($this->isMethod('post')) {
            $rules = [
                'date_reported' => 'date',
                'issue_description' => 'required|string',
                'corrective_action' => 'nullable',
                'date_resolved' => 'nullable|date',
                'notification_date' => 'nullable|date',
                'client_process_open_notification_date' => 'nullable|date',
                'analysis' => 'nullable|string',
                'has_risk_correction_budget' => 'nullable|boolean',
                'reason_for_no_risk_correction_budget' => 'nullable|string',
                'has_non_conformity_terms' => 'nullable|boolean',
                'effect_corrective_actions' => 'nullable|string',
                'cause_corrective_actions' => 'nullable|string',
                'implementation_date' => 'nullable|date',
                'update_risk_matrix' => 'nullable|boolean',
                'client_process_close_notification_date' => 'nullable|date',
                'client_acceptance' => 'nullable|boolean',
                'client_acceptance_comments' => 'nullable|string',
                'date_closed' => 'nullable|date',
                'obs' => 'nullable|string',
                'was_effective' => 'nullable|boolean',
                'status_id' => 'nullable|exists:occurrence_statuses,id',
                'responsible_name' => 'nullable|string',
                'department_id' => 'nullable|integer',
                'user_id' => 'nullable|integer',
                'origin_id' => 'nullable|integer',
                'category_id' => 'nullable|integer',
                'occurrence_year' => 'required'
            ];
        } else {
            $rules = [
                'date_reported' => 'date',
                'issue_description' => 'required|string',
                'corrective_action' => 'nullable',
                'date_resolved' => 'nullable|date',
                'notification_date' => 'nullable|date',
                'client_process_open_notification_date' => 'nullable|date',
                'analysis' => 'nullable|string',
                'has_risk_correction_budget' => 'nullable|boolean',
                'reason_for_no_risk_correction_budget' => 'nullable|string',
                'has_non_conformity_terms' => 'nullable|boolean',
                'effect_corrective_actions' => 'nullable|string',
                'cause_corrective_actions' => 'nullable|string',
                'implementation_date' => 'nullable|date',
                'update_risk_matrix' => 'nullable|boolean',
                'client_process_close_notification_date' => 'nullable|date',
                'client_acceptance' => 'nullable|boolean',
                'client_acceptance_comments' => 'nullable|string',
                'date_closed' => 'nullable|date',
                'obs' => 'nullable|string',
                'was_effective' => 'nullable|boolean',
                'status_id' => 'nullable|exists:occurrence_statuses,id',
                'responsible_name' => 'nullable|string',
                'department_id' => 'nullable|integer',
                'user_id' => 'nullable|integer',
                'origin_id' => 'nullable|integer',
                'category_id' => 'nullable|integer',
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
            'date_reported' => trans('gestlab.general.labels.occurrences.date_reported'),
            'issue_description' => trans('gestlab.general.labels.occurrences.issue_description'),
            'corrective_action' => trans('gestlab.general.labels.occurrences.corrective_action'),
            'date_resolved' => trans('gestlab.general.labels.occurrences.date_resolved'),
            'notification_date' => trans('gestlab.general.labels.occurrences.notification_date'),
            'client_process_open_notification_date' => trans('gestlab.general.labels.occurrences.client_process_open_notification_date'),
            'analysis' => trans('gestlab.general.labels.occurrences.analysis'),
            'has_risk_correction_budget' => trans('gestlab.general.labels.occurrences.has_risk_correction_budget'),
            'reason_for_no_risk_correction_budget' => trans('gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget'),
            'has_non_conformity_terms' => trans('gestlab.general.labels.occurrences.has_non_conformity_terms'),
            'effect_corrective_actions' => trans('gestlab.general.labels.occurrences.effect_corrective_actions'),
            'cause_corrective_actions' => trans('gestlab.general.labels.occurrences.cause_corrective_actions'),
            'implementation_date' => trans('gestlab.general.labels.occurrences.implementation_date'),
            'update_risk_matrix' => trans('gestlab.general.labels.occurrences.update_risk_matrix'),
            'client_process_close_notification_date' => trans('gestlab.general.labels.occurrences.client_process_close_notification_date'),
            'client_acceptance' => trans('gestlab.general.labels.occurrences.client_acceptance'),
            'client_acceptance_comments' => trans('gestlab.general.labels.occurrences.client_acceptance_comments'),
            'date_closed' => trans('gestlab.general.labels.occurrences.date_closed'),
            'obs' => trans('gestlab.general.labels.occurrences.obs'),
            'was_effective' => trans('gestlab.general.labels.occurrences.was_effective'),
            'status_id' => trans('gestlab.general.labels.occurrences.status_id'),
            'responsible_name' => trans('gestlab.general.labels.occurrences.responsible_name'),
            'department_id' => trans('gestlab.general.labels.occurrences.department_id'),
            'user_id' => trans('gestlab.general.labels.occurrences.user_id'),
            'origin_id' => trans('gestlab.general.labels.occurrences.origin_id'),
            'category_id' => trans('gestlab.general.labels.occurrences.category_id'),
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
        $this->merge([
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
            'department_id' => !is_null(request()->department_id) ? request()->department_id['value'] : null,
            'user_id' => !is_null(request()->user_id) ? request()->user_id['value'] : null,
            'status_id' => !is_null(request()->status_id) ? request()->status_id['value'] : null,
            'origin_id' => !is_null(request()->origin_id) ? request()->origin_id['value'] : null,
            'occurrence_year' => now()->format('Y'),
        ]);
    }
}
