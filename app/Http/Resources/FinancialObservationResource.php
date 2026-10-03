<?php

namespace App\Http\Resources;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialObservationResource extends JsonResource
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
            'document_no' => match (true) {
                $this->resource instanceof Invoice => $this->inv_no,
                $this->resource instanceof CreditNote => $this->note_no,
                $this->resource instanceof Receipt => $this->rec_no,
                $this->resource instanceof Quote => $this->quote_no,
            },
            'obs' => $this->obs,
            ...($this->resource instanceof Quote ? ['invoice_id' => $this->invoice_id, 'converted_to_invoice' => (bool) $this->converted_to_invoice] : []),
        ];
    }
}
