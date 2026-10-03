<?php

namespace App\Services;

use App\Enums\InventoryCategoryType;
use App\Models\ItemCategory;
use Illuminate\Validation\Rule;

class InventoryCategoryValidation
{
    /** @return array<string,array<mixed>> */
    public function rules(?ItemCategory $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('item_categories', 'name')->ignore($category?->id)],
            'code' => ['required', 'string', 'max:255', Rule::unique('item_categories', 'code')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', Rule::exists('item_categories', 'id')->whereNull('deleted_at'),
                Rule::notIn(array_filter([$category?->id]))],
            'inventory_type' => ['required', Rule::enum(InventoryCategoryType::class),
                ...($category?->typeIsLocked() ? [Rule::in([$category->inventory_type->value])] : [])],
        ];
    }
}
