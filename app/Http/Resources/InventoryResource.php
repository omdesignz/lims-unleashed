<?php

namespace App\Http\Resources;

use App\Services\InventoryCatalogueAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = $this->item;
        $type = $item?->category?->inventory_type;
        $access = app(InventoryCatalogueAccess::class);

        return [
            'id' => $this->id,
            'qty_available' => $this->qty_available,
            'min_stock_level' => $this->min_stock_level,
            'reorder_point' => $this->reorder_point,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->warehouse?->name,
            'item_id' => $this->item_id,
            'item' => $item?->name,
            'category_id' => $item?->category_id,
            'category' => $item?->category?->name,
            'inventory_type' => $type?->value,
            'can_open_item' => $item !== null && ! $item->trashed() && $type !== null
                && (bool) $request->user()?->can($access->permission('view', $type)),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('inventory.edit', $this->id),
                'delete_path' => route('inventory.destroy'),
                'restore_path' => route('inventory.restore'),
            ],
        ];
    }
}
