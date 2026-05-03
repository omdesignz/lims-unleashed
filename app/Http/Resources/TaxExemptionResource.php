<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxExemptionResource extends JsonResource
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
            'reason' => $this->reason,
            'law' => $this->law,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('taxexemptions.edit', $this->id),
                'delete_path' => route('taxexemptions.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('taxexemptions.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
