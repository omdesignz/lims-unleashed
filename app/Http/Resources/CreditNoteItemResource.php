<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteItemResource extends JsonResource
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
            'note_id' => $this?->note_id,
            'note' => CreditNoteResource::make($this->whenLoaded('note')),
            'itemable_id' => $this->itemable_id,
            'itemable_type' => $this->itemable_type,
            'exemption_code' => $this->exemption_code,
            'unit_id' => UnitResource::make($this->whenLoaded('unit')),
            'unit' => UnitResource::make($this->whenLoaded('unit'))?->name ?? null,
            'exemption_id' => TaxExemptionResource::make($this->whenLoaded('exemption')),
            'exemption' => TaxExemptionResource::make($this->whenLoaded('exemption'))?->code ?? null,
            'item_id' => $this->item_id,
            'item_description' => $this->item_description,
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'total' => $this->total,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'tax_id' => $this->tax_id,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'obs' => $this->obs,
            'charge_tax' => $this->charge_tax,
            'extra_data' => $this->extra_data,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('invoiceitems.edit', $this->id),
                'delete_path' => route('invoiceitems.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('invoiceitems.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
