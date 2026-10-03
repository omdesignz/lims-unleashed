<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lab_id' => $this->lab_id,
            'rec_no' => $this->rec_no,
            'user_id' => $this->whenLoaded('user', fn () => UserIdentityResource::make($this->user)->resolve($request)),
            'user' => $this->whenLoaded('user', fn () => $this->user->name),
            'customer_id' => CustomerResource::make($this->whenLoaded('customer')),
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'description' => $this->description,
            'file_path' => $this->file_path,
            'date' => $this->date,
            'obs' => $this->obs,
            'unique_hash' => $this->unique_hash,
            'total' => $this->items->sum('paid_amount'),
            'is_original' => $this->is_original,
            'exported_saft' => $this->exported_saft,
            'extra_data' => $this->extra_data,
            'revision_count' => $this->revision_count,
            'last_revision_at' => $this->last_revision_at,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('receipts.edit', $this->id),
                'pdf_path' => route('receipts.getPDF', ['id' => $this->id]),
                'delete_path' => route('receipts.destroy'),
                'restore_path' => route('receipts.restore'),
            ],
        ];
    }
}
