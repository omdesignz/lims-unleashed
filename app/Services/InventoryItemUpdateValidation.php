<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class InventoryItemUpdateValidation
{
    public function __construct(private readonly InventoryItemCreationValidation $creation) {}

    /** @return array<string,array<mixed>> */
    public function rules(int $labId, InventoryItem $item): array
    {
        $rules = $this->creation->rules($labId);
        foreach (array_keys($rules) as $field) {
            if (str_starts_with($field, 'warehouses.')) {
                unset($rules[$field]);
            }
        }
        $rules['warehouses'] = ['prohibited'];
        foreach (['code', 'barcode', 'internal_code'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255', Rule::unique($item->getTable(), $field)->where('lab_id', $labId)->ignore($item)];
        }
        $hasStock = $item->inventory()->withTrashed()->exists();
        foreach (self::references() as $field => $class) {
            $current = $item->getRawOriginal($field);
            $rules[$field] = [$field === 'category_id' ? ($current === null ? 'nullable' : 'required') : ($field === 'unit_id' ? Rule::requiredIf($hasStock) : 'nullable'),
                'nullable', 'integer', Rule::exists((new $class)->getTable(), 'id')
                    ->where(fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                        ->whereNull('deleted_at')->orWhere('id', $current)))];
            if ($field === 'category_id' || ($field === 'unit_id' && $hasStock && $current !== null)) {
                $rules[$field][] = Rule::in([$current]);
            }
        }

        return $rules;
    }

    /** @return array<string,class-string<Model>> */
    public static function references(): array
    {
        return InventoryItemCreationValidation::references();
    }
}
