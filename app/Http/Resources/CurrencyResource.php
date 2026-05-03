<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
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
            'symbol' => $this->symbol,
            'thousand_separator' => $this->thousand_separator,
            'decimal_separator' => $this->decimal_separator,
            'swap_currency_symbol' => $this->swap_currency_symbol,
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('currencies.edit', $this->id),
                'delete_path' => route('currencies.destroy', [
                    'recordIds' => [$this->id]
                ]),
                'restore_path' => route('currencies.restore', [
                    'recordIds' => [$this->id]
                ]),
            ]
        ];
    }
}
