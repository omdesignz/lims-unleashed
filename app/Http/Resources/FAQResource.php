<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FAQResource extends JsonResource
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
            'category_id' => $this->category_id,
            'category' => FAQCategoryResource::make($this->whenLoaded('category'))?->name ?? null,
            'answers' => FAQAnswerResource::collection($this->whenLoaded('answers')),
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('faqs.edit', $this->id),
                'delete_path' => route('faqs.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('faqs.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
