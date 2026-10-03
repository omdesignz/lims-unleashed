<?php

namespace App\Services;

use App\Enums\InventoryCategoryType;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class InventoryCatalogueAccess
{
    public function permission(string $ability, InventoryCategoryType $type): string
    {
        return $ability.'_'.($type === InventoryCategoryType::EQUIPMENT ? 'iequipments' : 'iitems');
    }

    public function type(mixed $value): InventoryCategoryType
    {
        $type = is_string($value) ? InventoryCategoryType::tryFrom($value) : null;
        abort_unless($type, 404, 'A classificação do item não está definida.');

        return $type;
    }

    public function itemType(InventoryItem $item): InventoryCategoryType
    {
        return $this->type(ItemCategory::withTrashed()->whereKey($item->category_id)->toBase()->value('inventory_type'));
    }

    public function authorize(?User $user, InventoryItem $item, string $ability): void
    {
        abort_unless($user?->can($this->permission($ability, $this->itemType($item))), 403);
    }

    public function any(?User $user, string $ability): bool
    {
        return $this->allowedTypes($user, $ability) !== [];
    }

    /** @return list<string> */
    public function allowedTypes(?User $user, string $ability): array
    {
        return array_values(array_map(fn (InventoryCategoryType $type): string => $type->value,
            array_filter(InventoryCategoryType::cases(), fn (InventoryCategoryType $type): bool => (bool) $user?->can($this->permission($ability, $type)))));
    }

    /** @return Builder<ItemCategory> */
    public function categories(?User $user, string $ability): Builder
    {
        return ItemCategory::query()->whereIn('inventory_type', $this->allowedTypes($user, $ability));
    }

    /** @param Builder<InventoryItem> $query @return Builder<InventoryItem> */
    public function constrain(Builder $query, ?User $user, string $ability): Builder
    {
        $types = $this->allowedTypes($user, $ability);

        return $query->whereHas('category', fn (Builder $category): Builder => $category->withTrashed()->whereIn('inventory_type', $types));
    }
}
