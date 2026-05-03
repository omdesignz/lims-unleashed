<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRequestResource extends JsonResource
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
            'reference' => $this->reference,
            'title' => $this->title,
            'request_type' => $this->request_type,
            'status' => $this->portal_status,
            'priority' => $this->priority,
            'preferred_date' => $this->preferred_date?->format('Y-m-d'),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'response_time' => $this->resolved_at && $this->submitted_at
                ? $this->submitted_at->diffInHours($this->resolved_at)
                : null,
            'category_id' => $this->category_id,
            'category' => CustomerRequestCategoryResource::make($this->whenLoaded('category'))?->name ?? null,
            'customer_id' => $this->customer_id,
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'contact' => $this->contact,
            'email' => $this->email,
            'description' => $this->description,
            'extra_data' => $this->extra_data,
            'created_at' => $this->created_at?->toIso8601String(),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('customerrequests.edit', $this->id),
                'delete_path' => route('customerrequests.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('customerrequests.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
