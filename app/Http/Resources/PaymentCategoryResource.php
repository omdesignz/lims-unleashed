<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentCategoryResource extends JsonResource
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
                'edit_path' => route('paymentcategories.edit', $this->id),
                'delete_path' => route('paymentcategories.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('paymentcategories.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
