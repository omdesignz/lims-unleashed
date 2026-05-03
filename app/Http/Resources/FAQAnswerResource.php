<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FAQAnswerResource extends JsonResource
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
            'faq_id' => $this->faq_id,
            'faq' => FAQResource::make($this->whenLoaded('faq'))?->description ?? null,
            'description' => $this->description,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('faqanswers.edit', $this->id),
                'delete_path' => route('faqanswers.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('faqanswers.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
