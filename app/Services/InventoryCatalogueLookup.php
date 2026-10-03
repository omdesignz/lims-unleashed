<?php

namespace App\Services;

use App\Enums\InventoryCategoryType;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCatalogueLookup
{
    public function __construct(private readonly InventoryCatalogueAccess $access) {}

    /** @return Collection<int,InventoryItem> */
    public function search(int $labId, User $user, string $search, ?InventoryCategoryType $type = null, bool $reagentsOnly = false): Collection
    {
        abort_unless($type ? $user->can($this->access->permission('view', $type)) : $this->access->any($user, 'view'), 403);
        $search = trim($search);
        if ($search === '') {
            return new Collection;
        }
        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

        return $this->access->constrain(InventoryItem::forLaboratory($labId), $user, 'view')
            ->select(['id', 'name', 'code', 'internal_code', 'category_id', 'unit_id', 'is_reagent'])
            ->with(['category' => fn (BelongsTo $category) => $category->withTrashed()->select(['id', 'inventory_type', 'name'])])
            ->when($type, fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->withTrashed()->where('inventory_type', $type->value)))
            ->when($reagentsOnly, fn (Builder $query) => $query->reagents())
            ->where(fn (Builder $query) => $query->whereLike('name', $pattern)->orWhereLike('code', $pattern)->orWhereLike('internal_code', $pattern))
            ->orderBy('name')->orderBy('id')->limit(25)->get();
    }
}
