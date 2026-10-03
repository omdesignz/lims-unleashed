<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionAccessionResource extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $record = $this->resource;
        $data = [
            'id' => $record->id,
            'code' => $record->code?->code,
            'collection_id' => $record->collection_id,
            'collection_location' => $record->collection?->collectionable?->collection_location,
            'vehicle_reference' => $record->collection?->collectionable?->vehicle_reference,
            'sample_entry_url' => route('vap_samples.show', $record->sampleEntry->id),
        ];

        foreach ([
            'product_id' => [$record->product, 'name'],
            'customer_id' => [$record->customer, 'name'],
            'warehouse_id' => [$record->warehouse, 'address'],
            'temperature_id' => [$record->temperature, 'name'],
            'vehicle_id' => [$record->vehicle, 'number_plate'],
            'owner_id' => [$record->owner, 'name'],
            'result_id' => [$record->end_result, 'name'],
            'pack_id' => [$record->packaging, 'name'],
        ] as $field => [$related, $label]) {
            $data[$field] = $record->{$field} === null ? null : [
                'value' => $record->{$field},
                'label' => $related?->{$label} ?? (string) $record->{$field},
            ];
        }

        foreach (['comercial_brand', 'du_no', 'term_no', 'origin', 'location', 'lot', 'bl', 'temperature_value', 'container_no', 'qty', 'collected_qty', 'recollection', 'collected_by_lab', 'obs', 'sample_status', 'sampling_plan_ref', 'customer_submitted_info', 'expiry_date', 'production_date', 'collection_date'] as $field) {
            $data[$field] = $record->{$field};
        }

        foreach (['collaborations' => 'collaborations', 'collectionreasons' => 'reasons'] as $field => $relation) {
            $data[$field] = $record->collection->{$relation}->map(fn ($item): array => [
                'value' => $item->id, 'label' => $item->name,
            ])->all();
        }

        return $data;
    }
}
