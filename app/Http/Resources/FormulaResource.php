<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormulaResource extends JsonResource
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
            'description' => $this->description,
            'code' => $this->code,
            'variables' => $this->variables,
            'variables_count' => count($this->variables ?? []),
            'category' => $this->category,
            'decimal_places' => $this->decimal_places,
            'is_active' => $this->is_active,
            'output_unit' => $this->output_unit,
            'expression' => $this->expression,
            'formula_expression' => $this->formula_expression,
            'created_at' => $this->created_at,
            'created_by' => $this->createdBy?->only(['id', 'name']),
            'parameters_count' => $this->whenCounted('parameters'),
            'parameters' => ParameterResource::collection($this->whenLoaded('parameters')),
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('formulas.edit', $this->id),
                'delete_path' => route('formulas.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('formulas.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
