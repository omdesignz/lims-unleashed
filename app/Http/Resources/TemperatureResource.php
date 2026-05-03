<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemperatureResource extends JsonResource
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
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('temperatures.edit', $this->id),
                'delete_path' => route('temperatures.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('temperatures.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
