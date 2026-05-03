<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReagentConsumptionRequest extends FormRequest
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
                'date' => 'required|date',
                'reagent_id' => 'required|exists:inventory,id',
                'reagent_name' => 'required|string',
                'quantity_used' => 'required|numeric|min:0.01',
                'user_id' => 'nullable|exists:users,id',
                'used_by' => 'nullable|string',
                'used_at' => 'required|date',
                'remarks' => 'nullable|string',
            ];
        } else {
            $rules = [
                'date' => 'required|date',
                'reagent_id' => 'required|exists:inventory,id',
                'reagent_name' => 'required|string',
                'quantity_used' => 'required|numeric|min:0.01',
                'user_id' => 'nullable|exists:users,id',
                'used_by' => 'nullable|string',
                'used_at' => 'required|date',
                'remarks' => 'nullable|string',
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
            'reagent_id' => trans('gestlab.general.labels.reagent_consumption.reagent_id'),
            'reagent_name' => trans('gestlab.general.labels.reagent_consumption.reagent_name'),
            'quantity_used' => trans('gestlab.general.labels.reagent_consumption.quantity_used'),
            'user_id' => trans('gestlab.general.labels.reagent_consumption.user_id'),
            'used_by' => trans('gestlab.general.labels.reagent_consumption.used_by'),
            'used_at' => trans('gestlab.general.labels.reagent_consumption.used_at'),
            'remarks' => trans('gestlab.general.labels.reagent_consumption.remarks'),
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
            'date' => now()->format('Y-m-d'),
            'reagent_id' => !is_null(request()->reagent_id) ? request()->reagent_id['value'] : null,
            'user_id' => !is_null(request()->user_id) ? request()->user_id['value'] : null,
        ]);
    }
}
