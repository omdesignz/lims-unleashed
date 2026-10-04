<?php

namespace App\Http\Requests;

use App\Models\VAPNonConformity;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNonConformityRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->boolean('actions_present') && ! $this->has('actions')) {
            $this->merge(['actions' => []]);
        }
    }

    public function authorize(): bool
    {
        $record = $this->route('nonConformity');
        if ($record instanceof VAPNonConformity) {
            abort_unless((int) $record->lab_id === app(SampleLaboratoryAccess::class)->activeLabId(), 404);
        }

        return ! $this->session()->has('impersonate')
            && $this->user()->can(($record ? 'edit' : 'add').'_occurrences');
    }

    public function rules(): array
    {
        return self::authoringRules(app(SampleLaboratoryAccess::class)->activeLabId(), $this->route('nonConformity')?->id, $this->route('nonConformity')?->status);
    }

    /** @return array<string, array<int, mixed>> */
    public static function authoringRules(int $labId, ?int $recordId = null, ?string $status = null): array
    {
        $rules = [
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'nc_number' => ['required', 'string', 'max:255', Rule::unique('v_non_conformities', 'nc_number')->where('lab_id', $labId)->ignore($recordId)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in($status === 'resolved' ? ['resolved'] : ['opened', 'in_progress'])],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'category' => ['required', Rule::in(['quality', 'safety', 'environmental', 'regulatory', 'other'])],
            'sample_id' => ['nullable', 'string', 'max:255'],
            'test_method' => ['nullable', 'string', 'max:255'],
            'equipment_id' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'reported_by' => ['required', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'assigned_to_id' => ['bail', 'nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) use ($labId, $recordId): void {
                if ($recordId !== null && VAPNonConformity::query()->whereKey($recordId)->where('lab_id', $labId)
                    ->where('assigned_to_id', $value)->exists()) {
                    return;
                }
                if (! app(LaboratoryWorkflowOwnership::class)->eligibleUsers($labId)->whereKey($value)->exists()) {
                    $fail('O responsável deve ser um membro activo e verificado deste laboratório.');
                }
            }],
            'reported_at' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'occurrence_area' => ['nullable', 'string', 'max:255'],
            'root_cause' => ['nullable', 'string', 'max:20000'],
            'corrective_actions' => ['nullable', 'string', 'max:20000'],
            'preventive_actions' => ['nullable', 'string', 'max:20000'],
            'comments' => ['nullable', 'string', 'max:20000'],
            'attachment_files' => ['nullable', 'array', 'max:10'],
            'attachment_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt', 'max:10240'],
            'actions' => ['nullable', 'array', 'max:100'],
            'actions.*' => ['array:id,correction,corrective_action,due_at'],
            'actions.*.id' => [$recordId ? 'nullable' : 'prohibited', 'integer', 'distinct'],
            'actions.*.correction' => ['nullable', 'string', 'max:20000'],
            'actions.*.corrective_action' => ['nullable', 'string', 'max:20000'],
            'actions.*.due_at' => ['nullable', 'date'],
        ];

        if ($status === 'closed') {
            $rules = array_fill_keys(array_filter(array_keys($rules), fn ($key) => ! str_contains($key, '.')), ['missing']);
            $rules['comments'] = ['present', 'nullable', 'string', 'max:20000'];
        }

        return $rules;
    }
}
