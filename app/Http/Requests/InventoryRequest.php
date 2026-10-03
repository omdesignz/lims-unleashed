<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permission = $this->isMethod('post') ? 'add_inventory' : 'edit_inventory';
        abort_unless($this->user()?->can($permission), 403);
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        if (! $this->isMethod('post')) {
            $id = (string) $this->route('inventory');
            abort_unless(preg_match('/^[1-9][0-9]*$/', $id) && filter_var($id, FILTER_VALIDATE_INT) !== false, 404);
            Inventory::query()->whereHas('warehouse', fn ($query) => $query->where('lab_id', $labId))
                ->findOrFail($this->route('inventory'));
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $inventory = $this->isMethod('post') ? null : Inventory::query()->findOrFail($this->route('inventory'));

        return [
            'qty_available' => $this->isMethod('post')
                ? ['sometimes', 'numeric', 'decimal:0,4', 'min:0']
                : ['prohibited'],
            'min_stock_level' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'reorder_point' => ['required', 'numeric', 'decimal:0,4', 'min:0'],
            'warehouse_id' => ['required', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)->whereNull('deleted_at'),
                ...($inventory ? [Rule::in([$inventory->warehouse_id])] : [])],
            'item_id' => ['required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')->whereNotNull('unit_id'),
                ...($inventory ? [Rule::in([$inventory->item_id])] : [Rule::unique('inventory', 'item_id')
                    ->where('warehouse_id', $this->input('warehouse_id'))->whereNull('deleted_at')])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'qty_available' => trans('gestlab.general.labels.inventory.qty_available'),
            'min_stock_level' => trans('gestlab.general.labels.inventory.min_stock_level'),
            'reorder_point' => trans('gestlab.general.labels.inventory.reorder_point'),
            'warehouse_id' => trans('gestlab.general.labels.inventory.warehouse_id'),
            'item_id' => trans('gestlab.general.labels.inventory.item_id'),
            'name' => trans('gestlab.general.labels.inventory.name'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'warehouse_id' => data_get($this->input('warehouse_id'), 'value', $this->input('warehouse_id')),
            'item_id' => data_get($this->input('item_id'), 'value', $this->input('item_id')),
        ]);
    }
}
