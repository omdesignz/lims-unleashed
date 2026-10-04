<?php

namespace App\Http\Resources;

use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortalDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fields = match ($this->resource::class) {
            Invoice::class => ['inv_no', 'date', 'total', 'amount_due', 'description', 'internal_ref'],
            Receipt::class => ['rec_no', 'date', 'description', 'obs'],
            CreditNote::class => ['note_no', 'date', 'total', 'reason', 'description', 'obs', 'internal_ref'],
            Quote::class => ['quote_no', 'date', 'due_date', 'total', 'description', 'obs', 'internal_ref', 'converted_to_invoice'],
            ContractGuide::class => ['guide_no', 'date', 'contact', 'du_no', 'bl'],
            QualityCertificate::class => ['code', 'validated_at', 'created_at'],
        };
        $data = ['id' => $this->id, ...$this->resource->only($fields)];
        if ($this->resource instanceof Receipt) {
            $data += ['total' => $this->portal_total ?? 0,
                'customer' => $this->customer?->name, 'warehouse' => $this->warehouse?->address];
        } elseif ($this->resource instanceof CreditNote) {
            $data['invoice_id'] = $this->invoice ? ['inv_no' => $this->invoice->inv_no] : null;
        } elseif ($this->resource instanceof Quote) {
            $data['converted_to_invoice'] = (bool) ($this->converted_to_invoice || $this->invoice_id);
        } elseif ($this->resource instanceof QualityCertificate) {
            $data += ['lab_code' => $this->lab_code?->code, 'product' => $this->product?->name];
        }

        return $data;
    }
}
