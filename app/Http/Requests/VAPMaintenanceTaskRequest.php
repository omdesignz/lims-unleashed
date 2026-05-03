<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VAPMaintenanceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $requiresSupplier = (bool) $this->boolean('executed_by_supplier');
        $marksExecuted = (bool) $this->boolean('is_executed');

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:maintenance_categories,id'],
            'equipment_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:i_items,id'],
            'due_date' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'maintenance_task_no' => ['nullable', 'string', 'max:255'],
            'periodicity' => ['nullable', 'integer', 'min:1'],
            'periodicity_unit' => ['nullable', Rule::in(['hours', 'days', 'weeks', 'months', 'years'])],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'executed_by_supplier' => ['nullable', 'boolean'],
            'supplier_id' => [$requiresSupplier ? 'required' : 'nullable', 'exists:i_suppliers,id'],
            'obs' => ['nullable', 'string', 'max:5000'],
            'is_planned' => ['nullable', 'boolean'],
            'is_executed' => ['nullable', 'boolean'],
            'acceptance_criteria' => ['nullable', 'string', 'max:255'],
            'result' => [$marksExecuted ? 'required' : 'nullable', 'string'],
            'range' => ['nullable', 'string', 'max:255'],
            'calibration_points' => ['nullable', 'string'],
            'calibration_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'calibration_certificate_no' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'executed_by_supplier' => $this->boolean('executed_by_supplier'),
            'is_planned' => $this->boolean('is_planned'),
            'is_executed' => $this->boolean('is_executed'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome da tarefa',
            'category_id' => 'categoria',
            'equipment_id' => 'equipamento',
            'due_date' => 'data prevista',
            'supplier_id' => 'fornecedor',
            'periodicity' => 'periodicidade',
            'periodicity_unit' => 'unidade da periodicidade',
            'result' => 'resultado da manutenção',
        ];
    }
}
