<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class LabNetworkStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'code' => $this->code,
            'lab_name' => $this->lab_name, 'warehouse_name' => $this->warehouse_name,
            'lot' => $this->lot, 'expiry_date' => $this->expiry_date, 'unit' => $this->unit,
            'physical_quantity' => $this->physical_quantity, 'available_quantity' => $this->available_quantity,
            'reserved_quantity' => $this->reserved_quantity, 'blocked_quantity' => $this->blocked_quantity,
            'outgoing_quantity' => $this->outgoing_quantity, 'availability_state' => $this->availability_state,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toIso8601String() : null,
        ];
    }
}
