<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'email' => $this->email,
            'invoicing_email' => $this->invoicing_email,
            'primary_phone' => $this->primary_phone,
            'alternative_phone' => $this->alternative_phone, 
            'nif' => $this->nif,
            'address' => $this->address,
            'municipality' => $this->municipality,
            'province' => $this->province,
            'code' => $this->code,
            'name' => $this->name,
            'focal_point' => $this->focal_point,
            'focal_point_email' => $this->focal_point_email,
            'focal_point_contact' => $this->focal_point_contact,
            'description' => $this->description,
            'customer_id' => $this->customer_id,
            'customer' => CustomerResource::make($this->customer)?->name ?? null,
            'customer_category' => CustomerResource::make($this->customer)?->category?->name ?? null,
            'has_password' => ! empty($this->password),
            'status' => $this->deleted_at ? 'inactive' : 'active',
            'is_primary' => (bool) ($this->customer && $this->customer->main_warehouse_id === $this->id),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('warehouses.edit', $this->id),
                'show_path' => route('warehouses.show', $this->id),
                'delete_path' => route('warehouses.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('warehouses.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
