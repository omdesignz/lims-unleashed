<?php

namespace App\Http\Requests;

use App\Models\MaintenanceTask;
use App\Services\SampleLaboratoryAccess;
use App\Support\MaintenanceTaskValidation;
use Illuminate\Foundation\Http\FormRequest;

class VAPMaintenanceTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_if($this->session()->has('impersonate'), 403);
        $task = $this->route('task');
        if ($task instanceof MaintenanceTask) {
            abort_unless(MaintenanceTask::forLaboratory(app(SampleLaboratoryAccess::class)->activeLabId())->whereKey($task->id)->exists(), 404);
        }

        return $this->user()?->can($this->isMethod('post') ? 'add_maintenance_tasks' : 'edit_maintenance_tasks') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $task = $this->route('task');

        return MaintenanceTaskValidation::rules(app(SampleLaboratoryAccess::class)->activeLabId(), $task instanceof MaintenanceTask ? $task : null);
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        $task = $this->route('task');

        return MaintenanceTaskValidation::mergeCurrentState($this->all(), $task instanceof MaintenanceTask ? $task : null);
    }

    /** @return array<string, mixed> */
    public function submittedData(): array
    {
        return array_intersect_key($this->validated(), $this->all());
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return MaintenanceTaskValidation::messages();
    }

    /** @return array<string, string> */
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
