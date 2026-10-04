<?php

namespace App\Http\Requests;

use App\Support\MaintenanceTaskFilters;
use App\Support\MaintenanceTaskQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExportMaintenanceTasksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('export_maintenance_tasks') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...MaintenanceTaskFilters::rules($this->all()),
            'type' => ['required', 'in:tasks,calendar'],
            'format' => ['required', 'in:pdf,csv,excel'],
            'task_id' => ['nullable', 'integer', 'min:1'],
            'range' => ['nullable', 'in:filtered,all,overdue,upcoming,executed'],
            'report_type' => ['nullable', 'in:overdue,upcoming,executed,schedule'],
            'fields' => ['prohibited'],
            'archived' => ['prohibited'],
            'filters' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('vap-maintenance.report.generate')) {
            $this->merge(['type' => $this->input('report_type') === 'schedule' ? 'calendar' : 'tasks']);
        }
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $data = $this->validated();
        $filters = array_intersect_key($data, array_flip([...array_keys(MaintenanceTaskFilters::rules($data)), 'task_id']));
        $range = $data['range'] ?? 'filtered';
        if ($range !== 'filtered') {
            $filters = array_intersect_key($filters, ['task_id' => true]);
            if ($range !== 'all') {
                $filters['status'] = $range;
            }
        }
        if (in_array($data['report_type'] ?? null, ['overdue', 'upcoming', 'executed'], true)) {
            $filters['status'] = $data['report_type'];
        }

        return $data['type'] === 'calendar' ? app(MaintenanceTaskQuery::class)->calendarFilters($filters) : $filters;
    }
}
