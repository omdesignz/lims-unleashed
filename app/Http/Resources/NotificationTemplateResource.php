<?php

namespace App\Http\Resources;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return collect($this->resource)->only([
            'key', 'name', 'category', 'description', 'variables', 'is_overridden',
            ...NotificationTemplate::EDITABLE_FIELDS,
        ])->all();
    }
}
