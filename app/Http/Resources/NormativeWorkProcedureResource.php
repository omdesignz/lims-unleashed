<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NormativeWorkProcedureResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('nwps.edit', $this->id),
                'delete_path' => route('nwps.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('nwps.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
