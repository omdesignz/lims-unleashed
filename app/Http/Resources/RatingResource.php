<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'rateable_type' => $this->rateable_type, 'rateable_id' => $this->rateable_id,
            'channel' => $this->channel, 'criteria' => $this->criteria, 'review' => $this->review, 'created_at' => $this->created_at,
        ];
    }
}
