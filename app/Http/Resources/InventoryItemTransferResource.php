<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemTransferResource extends JsonResource
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
            'qty' => $this->qty,
            'sent_date' => $this->sent_date,
            'received_date' => $this->received_date,
            'obs' => $this->obs,
            'item_id' => $this->item_id,
            'item' => InventoryItemResource::make($this->item)?->name ?? null,
            'source_id' => $this->source_id,
            'source' => InventoryItemWarehouseResource::make($this->from)?->name ?? null,
            'destination_id' => $this->destination_id,
            'destination' => InventoryItemWarehouseResource::make($this->to)?->name ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('itransfers.edit', $this->id),
                'delete_path' => route('itransfers.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('itransfers.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
