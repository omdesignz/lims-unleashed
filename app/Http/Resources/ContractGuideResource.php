<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractGuideResource extends JsonResource
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
            'user_id' => UserResource::make($this->whenLoaded('user')),
            'user' => UserResource::make($this->whenLoaded('user'))?->name ?? null,
            'customer_id' => CustomerResource::make($this->whenLoaded('customer')),
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => WarehouseResource::make($this->whenLoaded('warehouse')),
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'guide_no' => $this->guide_no,
            'collection_id' => $this->collection_id,
            'nif' => $this->nif,
            'contact' => $this->contact,
            'email' => $this->email,
            'du_no' => $this->du_no,
            'bl' => $this->bl,
            'date' => $this->date,
            'file_path' => $this->file_path,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('contractguides.edit', $this->id),
                'pdf_path' => route('contractguides.getPDF', ['id' => $this->id]),
                'delete_path' => route('contractguides.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('contractguides.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
