<?php

namespace App\Http\Requests;

use App\Services\InventoryCatalogueAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCatalogueReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return app(InventoryCatalogueAccess::class)->any($this->user(), 'view')
            && (! $this->routeIs('vap-inventory.reports.low-stock') || $this->user()->can('view_inventory'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reportRules = match ($this->route()->getName()) {
            'vap-inventory.items.calibration.schedule' => [
                'status' => ['nullable', Rule::in(['overdue', 'due_soon', 'upcoming'])],
                'type_id' => ['nullable', 'integer', 'min:1'],
                'sort_by' => ['nullable', Rule::in(['next_calibration_date', 'last_calibration_date', 'name'])],
            ],
            'vap-inventory.items.reagents.expiry' => [
                'status' => ['nullable', Rule::in(['expired', 'expiring_soon', 'good'])],
                'warehouse_id' => ['nullable', 'integer', 'min:1'],
                'sort_by' => ['nullable', Rule::in(['expiry_date', 'name', 'current_stock'])],
            ],
            'vap-inventory.reports.low-stock' => [
                'warehouse_id' => ['nullable', 'integer', 'min:1'],
                'severity' => ['nullable', Rule::in(['critical', 'low'])],
                'sort_by' => ['nullable', Rule::in(['severity', 'current_stock', 'reorder_point', 'item_name'])],
            ],
            default => [],
        };

        return [
            'category_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
            ...$reportRules,
        ];
    }
}
