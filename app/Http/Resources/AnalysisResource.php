<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
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
            'cl' => LabCodeResource::make($this->whenLoaded('code'))?->code ?? null,
            'col_date' => $this->col_date,
            'entry_date' => $this->entry_date,
            'init_date' => $this->init_date,
            'status' => $this->status,
            'type_id' => $this->type_id,
            'type' => AnalysisCategoryResource::make($this->whenLoaded('type'))?->code ?? null,
            'profile_id' => $this->profile_id,
            'profile' => ProfileResource::make($this->whenLoaded('profile'))?->name ?? null,
            'product_id' => $this->product_id,
            'product' => ProductResource::make($this->whenLoaded('product'))?->name ?? null,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->whenLoaded('department'))?->name ?? null,
            'sample_id' => $this->sample_id,
            // 'sample' => SampleResource::make($this->whenLoaded('sample'))?->name,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('analysis.edit', $this->id),
                'delete_path' => route('analysis.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('analysis.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
