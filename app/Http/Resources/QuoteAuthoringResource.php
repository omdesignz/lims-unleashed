<?php

namespace App\Http\Resources;

use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class QuoteAuthoringResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...Arr::only($this->resource->attributesToArray(), ['id', 'date', 'due_date', 'internal_ref', 'description', 'quote_no', 'obs', 'status', 'use_matrix_price', 'is_service', 'is_original', 'converted_to_invoice', 'exported_saft', 'invoice_id', 'total']),
            'customer_id' => ['value' => $this->customer_id, 'label' => $this->customer?->name],
            'warehouse_id' => ['value' => $this->warehouse_id, 'label' => $this->warehouse?->address],
            'items' => $this->items->map(function (QuoteItem $item): array {
                $metadata = $item->extra_data;

                return [
                    'id' => $item->id,
                    'catalog_type' => $metadata?->get('catalog_type'),
                    'agreed_unit_price' => $metadata?->get('agreed_unit_price') ?? number_format((float) $item->unit_price + (float) $item->discount_amount, 2, '.', ''),
                    'discount_mode' => $metadata?->get('discount_mode') ?? ((float) $item->discount_percentage > 0 ? 'percentage' : 'fixed'),
                    'discount_value' => $metadata?->get('discount_value') ?? ((float) $item->discount_percentage > 0 ? $item->discount_percentage : $item->discount_amount),
                    'unit_id' => ['value' => $item->unit_id, 'label' => $item->unit?->code],
                    'item_id' => ['value' => $item->item_id, 'label' => $item->item_description,
                        'catalog_type' => $metadata?->get('catalog_type'), 'price' => $metadata?->get('agreed_unit_price') ?? number_format((float) $item->unit_price + (float) $item->discount_amount, 2, '.', ''),
                        'charge_tax' => $item->charge_tax, 'tax_percentage' => $item->tax_percentage],
                    'item_description' => $item->item_description,
                    'itemable_id' => $item->itemable_id === null ? null : ['value' => $item->itemable_id, 'label' => $item->itemable?->code?->code],
                    'itemable_type' => $item->itemable_type,
                    'qty' => $item->qty, 'obs' => $item->obs,
                ];
            })->all(),
        ];
    }
}
