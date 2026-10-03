<?php

namespace App\Http\Resources;

use App\Models\VAPSampleEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VAPSampleEntry */
class SampleIntakeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'sample_type' => $this->sample_type,
            'status' => $this->status,
            'collection_product_id' => $this->collection_product_id,
            'received_at' => $this->received_at?->toISOString(),
            'collected_at' => $this->collected_at?->toISOString(),
            'collected_by_lab' => $this->collected_by_lab,
            'analysis_start_date' => $this->analysis_start_date?->toISOString(),
            'analysis_end_date' => $this->analysis_end_date?->toISOString(),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer?->only(['id', 'name'])),
            'customer_id' => $this->customer_id,
            'lab_id' => $this->lab_id,
            'department_id' => $this->department_id,
            'packaging_id' => $this->packaging_id,
            'warehouse_id' => $this->warehouse_id,
            'proposal_id' => $this->proposal_id,
            'customer_request_id' => $this->customer_request_id,
            'requested_services' => $this->requested_services,
            'client_submitted_info' => $this->client_submitted_info,
            'obs' => $this->obs,
            'retention_period_days' => $this->retention_period_days,
            'retention_due_at' => $this->retention_due_at?->toDateString(),
            'discard_scheduled_at' => $this->discard_scheduled_at?->toDateString(),
            'retention_status' => $this->retention_status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
