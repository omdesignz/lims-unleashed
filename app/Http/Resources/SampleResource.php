<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleResource extends JsonResource
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
            'cl_id' => $this->cl_id,
            'collection' => LabCodeResource::make($this->whenLoaded('collection'))?->code ?? null,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('samples.edit', $this->id),
                'delete_path' => route('samples.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('samples.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}