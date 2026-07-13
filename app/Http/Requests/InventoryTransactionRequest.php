<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InventoryTransactionRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'inventory_id' => ['required', 'exists:inventory,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'exists:users,id'],
            'warehouse_id' => ['required', 'exists:i_warehouses,id'],
            'item_id' => ['required', 'exists:i_items,id'],
            'type_id' => ['required', 'exists:itransaction_types,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'inventory_id' => trans('gestlab.general.labels.itransactions.inventory_id'),
            'qty' => trans('gestlab.general.labels.itransactions.qty'),
            'user_id' => trans('gestlab.general.labels.itransactions.user_id'),
            'warehouse_id' => trans('gestlab.general.labels.itransactions.warehouse_id'),
            'item_id' => trans('gestlab.general.labels.itransactions.item_id'),
            'type_id' => trans('gestlab.general.labels.itransactions.type_id'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    protected function prepareForValidation(): void
    {
        $inventoryId = data_get($this->input('inventory_id'), 'value', $this->input('inventory_id'));
        $typeId = data_get($this->input('type_id'), 'value', $this->input('type_id'));
        $inventory = Inventory::query()->find($inventoryId);

        $this->merge([
            'inventory_id' => $inventoryId,
            'user_id' => $this->user()?->id,
            'warehouse_id' => $inventory?->warehouse_id,
            'item_id' => $inventory?->item_id,
            'type_id' => $typeId,
        ]);
    }
}
