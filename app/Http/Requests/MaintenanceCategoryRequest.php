<?php

namespace App\Http\Requests;

use App\Models\MaintenanceCategory;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaintenanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');
        if ($category instanceof MaintenanceCategory) {
            abort_unless($category->lab_id === app(SampleLaboratoryAccess::class)->activeLabId(), 404);
        }

        return ! $this->session()->has('impersonate')
            && $this->user()->can(($this->isMethod('post') ? 'add' : 'edit').'_maintenance_categories');
    }

    public function rules(): array
    {
        return self::categoryRules($this->route('category')?->id);
    }

    /** @return array<string, array<int, mixed>> */
    public static function categoryRules(?int $categoryId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', Rule::unique('maintenance_categories', 'code')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'lab_id' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
