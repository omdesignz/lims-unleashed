<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceTaskPeriodicityUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MaintenanceTaskRequest extends FormRequest
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
                'name' => 'required|min:1',
                'description' => 'required|min:1',
                'equipment_id' => 'required|exists:i_items,id',
                'category_id' => 'required|exists:maintenance_categories,id',
                'supplier_id' => 'nullable|exists:i_suppliers,id',
                'due_date' => 'required|date',
                'previous_date' => 'nullable|date',
                'next_date' => 'nullable|date',
                'is_executed' => 'boolean',
                'is_planned' => 'boolean',
                'executed_by_supplier' => 'boolean',
                'cost' => 'numeric',
                'range' => 'nullable',
                'calibration_points' => 'nullable',
                'calibration_status' => 'nullable',
                'calibration_certificate_no' => 'nullable',
                'periodicity' => 'nullable|numeric',
                'periodicity_unit' => [
                    'required',
                    new Enum(MaintenanceTaskPeriodicityUnit::class)
                ],
                'result' => 'nullable|string',
                'acceptance_criteria' => 'nullable|string',
                'maintenance_task_year' => 'required'
            ];
        } else {
            $rules = [
                'name' => 'required|min:1',
                'description' => 'required|min:1',
                'equipment_id' => 'required|exists:i_items,id',
                'category_id' => 'required|exists:maintenance_categories,id',
                'supplier_id' => 'nullable|exists:i_suppliers,id',
                'due_date' => 'required|date',
                'previous_date' => 'nullable|date',
                'next_date' => 'nullable|date',
                'is_executed' => 'boolean',
                'is_planned' => 'boolean',
                'executed_by_supplier' => 'boolean',
                'cost' => 'numeric',
                'range' => 'nullable',
                'calibration_points' => 'nullable',
                'calibration_status' => 'nullable',
                'calibration_certificate_no' => 'nullable',
                'periodicity' => 'nullable|numeric',
                'periodicity_unit' => [
                    'required',
                    new Enum(MaintenanceTaskPeriodicityUnit::class)
                ],
                'result' => 'nullable|string',
                'acceptance_criteria' => 'nullable|string',

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
            'name' => trans('gestlab.general.labels.maintenance_tasks.name'),
            'description' => trans('gestlab.general.labels.maintenance_tasks.description'),
            'equipment_id' => trans('gestlab.general.labels.maintenance_tasks.equipment_id'),
            'category_id' => trans('gestlab.general.labels.maintenance_tasks.category_id'),
            'supplier_id' => trans('gestlab.general.labels.maintenance_tasks.supplier_id'),
            'is_executed' => trans('gestlab.general.labels.maintenance_tasks.is_executed'),
            'is_planned' => trans('gestlab.general.labels.maintenance_tasks.is_planned'),
            'executed_by_supplier' => trans('gestlab.general.labels.maintenance_tasks.executed_by_supplier'),
            'due_date' => trans('gestlab.general.labels.maintenance_tasks.due_date'),
            'previous_date' => trans('gestlab.general.labels.maintenance_tasks.previous_date'),
            'next_date' => trans('gestlab.general.labels.maintenance_tasks.next_date'),
            'is_executed' => trans('gestlab.general.labels.maintenance_tasks.is_executed'),
            'cost' => trans('gestlab.general.labels.maintenance_tasks.cost'),
            'range' => trans('gestlab.general.labels.maintenance_tasks.range'),
            'calibration_points' => trans('gestlab.general.labels.maintenance_tasks.calibration_points'),
            'calibration_status' => trans('gestlab.general.labels.maintenance_tasks.calibration_status'),
            'calibration_certificate_no' => trans('gestlab.general.labels.maintenance_tasks.calibration_certificate_no'),
            'periodicity' => trans('gestlab.general.labels.maintenance_tasks.periodicity'),
            'periodicity_unit' => trans('gestlab.general.labels.maintenance_tasks.periodicity_unit'),
            'result' => trans('gestlab.general.labels.maintenance_tasks.result'),
            'acceptance_criteria' => trans('gestlab.general.labels.maintenance_tasks.acceptance_criteria'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
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
            'periodicity_unit' => !is_null(request()->periodicity_unit) ? request()->periodicity_unit['value'] : null,
            'equipment_id' => !is_null(request()->equipment_id) ? request()->equipment_id['value'] : null,
            'category_id' => !is_null(request()->category_id) ? request()->category_id['value'] : null,
            'supplier_id' => !is_null(request()->supplier_id) ? request()->supplier_id['value'] : null,
            'is_executed' => request()->boolean('is_executed'),
            'is_planned' => request()->boolean('is_planned'),
            'executed_by_supplier' => request()->boolean('executed_by_supplier'),
            'maintenance_task_year' => now()->format('Y'),
        ]);
    }
}
