<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
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
            'phone_code' => $this->phone_code,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('countries.edit', $this->id),
                'delete_path' => route('countries.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('countries.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
