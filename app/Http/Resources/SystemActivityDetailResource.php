<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\StaffAccountHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemActivityDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [...$this->resource->attributesToArray(),
            'causer' => $this->identity($this->resource->causer),
            'subject' => $this->identity($this->resource->subject),
            'is_retained' => in_array($this->log_name, StaffAccountHistory::RETAINED_LOGS, true),
        ];
    }

    /** @return array<string,mixed>|null */
    private function identity(?Model $model): ?array
    {
        if ($model instanceof User) {
            return $model->only(['id', 'name']);
        }

        return $model?->toArray();
    }
}
