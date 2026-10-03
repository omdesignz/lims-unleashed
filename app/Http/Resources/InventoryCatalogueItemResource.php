<?php

namespace App\Http\Resources;

use App\Services\InventoryCatalogueAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryCatalogueItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->category?->inventory_type;
        $access = app(InventoryCatalogueAccess::class);
        $canMutate = ! $request->session()->has('impersonate');

        return $this->resource->only(['id', 'lab_id', 'name', 'code', 'internal_code', 'barcode',
            'category_id', 'type_id', 'unit_id', 'status_id', 'supplier_id', 'reorder_qty',
            'inventory_sum_qty_available', 'is_reagent', 'is_expired', 'days_to_expiry', 'metrology_status']) + [
                'category' => $this->whenLoaded('category', fn (): array => ['id' => $this->category->id,
                    'name' => $this->category->name, 'inventory_type' => $type?->value]),
                'type' => $this->whenLoaded('type', fn (): array => ['id' => $this->type->id, 'name' => $this->type->name]),
                'status' => $this->whenLoaded('status', fn (): array => ['id' => $this->status->id, 'name' => $this->status->name]),
                'is_archived' => $this->resource->trashed(),
                'can_edit' => $canMutate && ! $this->resource->trashed() && $type !== null && (bool) $request->user()?->can($access->permission('edit', $type)),
                'can_delete' => $canMutate && ! $this->resource->trashed() && $type !== null && (bool) $request->user()?->can($access->permission('delete', $type)),
                'can_restore' => $canMutate && $this->resource->trashed() && $type !== null && (bool) $request->user()?->can($access->permission('restore', $type)),
            ];
    }
}
