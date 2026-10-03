<?php

namespace App\Http\Requests;

use App\Services\InventoryCatalogueRead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryOperationalReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $type = $this->routeIs('vap-inventory.reports.export') ? $this->input('report_type') : $this->reportType();
        $operation = $type === 'stock_movement' ? 'view_itransactions' : 'view_inventory';

        return (bool) $this->user()?->can($operation)
            && app(InventoryCatalogueRead::class)->allowedTypes($this->user()) !== [];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $filters = [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', Rule::in(['critical', 'low'])],
            'view' => ['nullable', Rule::in(['summary', 'detailed'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
        if ($this->routeIs('vap-inventory.reports.export')) {
            $rules = [
                'report_type' => ['required', Rule::in(['stock_movement', 'consumption', 'inventory_value', 'low_stock'])],
                'format' => ['required', Rule::in(['pdf', 'csv', 'excel'])],
                'filters' => ['nullable', 'array'],
            ];
            foreach ($filters as $key => $validation) {
                $rules['filters.'.$key] = $key === 'date_to'
                    ? ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters.date_from'] : $validation;
            }

            return $rules;
        }

        return [
            ...$filters,
            'sort_by' => ['nullable', Rule::in(match ($this->reportType()) {
                'stock_movement' => ['created_at', 'qty', 'item_id', 'warehouse_id', 'type_id', 'user_id'],
                'consumption' => ['date', 'quantity_used', 'reagent_id', 'warehouse_id', 'user_id'],
                default => ['qty_available', 'item_id', 'warehouse_id'],
            })],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    private function reportType(): string
    {
        return match ($this->route()->getName()) {
            'vap-inventory.reports.stock-movement' => 'stock_movement',
            'vap-inventory.reports.consumption' => 'consumption',
            'vap-inventory.reagents.consumption.index' => 'consumption',
            'vap-inventory.reports.inventory-value' => 'inventory_value',
            default => 'dashboard',
        };
    }
}
