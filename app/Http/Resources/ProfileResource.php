<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'description' => $this->description,
            'price' => $this->price_based_on_parameters,
            'category_id' => AnalysisCategoryResource::make($this->type),
            'category' => AnalysisCategoryResource::make($this->type)?->name,
            'parameters' => ParameterResource::collection($this->whenLoaded('parameters')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('profiles.edit', $this->id),
                'show_path' => route('profiles.show', $this->id),
                'delete_path' => route('profiles.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('profiles.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
