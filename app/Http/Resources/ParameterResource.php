<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterResource extends JsonResource
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
            'price' => $this->price,
            'tax_percentage' => $this->tax_percentage,
            'charge_tax' => $this->charge_tax,
            'active' => $this->active,
            'exemption_id' => TaxExemptionResource::make($this->exemption),
            'exemption' => TaxExemptionResource::make($this->exemption)?->code ?? null,
            'tax_id' => TaxTypeResource::make($this->tax_category),
            'tax' => TaxTypeResource::make($this->tax_category)?->name ?? null,
            'optimal_analysis_time' => $this->optimal_analysis_time,
            'result_is_qualitative' => $this->result_is_qualitative,
            'formula_id' => $this->formula_id,
            'formula' => FormulaResource::make($this->formula) ?? null,
            'decimal_places' => $this->decimal_places,
            'result_type' => $this->result_type,
            'pivot' => $this->pivot ?? null,
            'requires_calculation' => $this->requires_calculation,
            'calculation_parameters' => $this->calculation_parameters,
            'formula_expression' => $this->formula_expression,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('parameters.edit', $this->id),
                'delete_path' => route('parameters.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('parameters.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
