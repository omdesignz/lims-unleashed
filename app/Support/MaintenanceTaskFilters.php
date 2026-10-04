<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class MaintenanceTaskFilters
{
    /** @param array<string, mixed> $input
     * @return array<string, array<mixed>>
     */
    public static function rules(array $input): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'equipment_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['overdue', 'executed', 'planned', 'upcoming', 'due_soon', 'scheduled'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when(filled($input['date_from'] ?? null), ['after_or_equal:date_from'])],
            'cost_min' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'cost_max' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', Rule::when(filled($input['cost_min'] ?? null), ['gte:cost_min'])],
            'sort_by' => ['nullable', Rule::in(['due_date', 'created_at', 'name', 'cost'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
