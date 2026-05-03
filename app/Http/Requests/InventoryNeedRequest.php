<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InventoryNeedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'exists:departments,id'],
            'lab_id' => ['nullable', 'exists:labs,id'],
            'needed_by_date' => ['nullable', 'date'],
            'justification' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:i_items,id'],
            'items.*.warehouse_id' => ['nullable', 'exists:i_warehouses,id'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
            'items.*.estimated_unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('lab_id') || ! $this->filled('department_id')) {
                    return;
                }

                $selectedLabBelongsToDepartment = \App\Models\VAPLab::query()
                    ->whereKey($this->integer('lab_id'))
                    ->where('department_id', $this->integer('department_id'))
                    ->exists();

                if (! $selectedLabBelongsToDepartment) {
                    $validator->errors()->add('lab_id', 'O laboratório seleccionado não pertence ao departamento informado.');
                }
            },
        ];
    }
}
