<?php

namespace App\Http\Requests;

use App\Models\Quote;
use App\Services\QuoteAuthoringData;
use App\Services\TradeCertificateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->isMethod('post') ? 'add_quotes' : 'edit_quotes') ?? false;
    }

    public function rules(): array
    {
        return app(QuoteAuthoringData::class)->rules($this->billedSource());
    }

    protected function prepareForValidation(): void
    {
        $this->replace(app(QuoteAuthoringData::class)->normalize($this->all()));
    }

    private function billedSource(): bool
    {
        if ($this->isMethod('post')) {
            return false;
        }
        $record = Quote::query()->findOrFail($this->route('quote'));

        return $record->invoice_id !== null || (bool) $record->converted_to_invoice;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->billedSource()) {
                app(TradeCertificateData::class)->rejectLockedFields($validator, $this->all());
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => trans('gestlab.general.labels.quotes.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.quotes.warehouse_id'),
            'internal_ref' => trans('gestlab.general.labels.quotes.internal_ref'),
            'obs' => trans('gestlab.general.labels.quotes.obs'),
            'items' => trans('gestlab.general.labels.quotes.items'),
            'items.*.quote_id' => trans('gestlab.general.labels.quotes.quote_id'),
            'items.*.unit_id' => trans('gestlab.general.labels.quotes.unit_id'),
            'items.*.exemption_id' => trans('gestlab.general.labels.quotes.exemption_id'),
            'items.*.discount_id' => trans('gestlab.general.labels.quotes.discount_id'),
            'items.*.item_id' => trans('gestlab.general.labels.quotes.item_id'),
            'items.*.qty' => trans('gestlab.general.labels.quotes.qty'),
            'items.*.unit_price' => trans('gestlab.general.labels.quotes.unit_price'),
            'items.*.total' => trans('gestlab.general.labels.quotes.total'),
            'items.*.discount_percentage' => trans('gestlab.general.labels.quotes.discount_percentage'),
            'items.*.tax_percentage' => trans('gestlab.general.labels.quotes.tax_percentage'),
            'items.*.charge_tax' => trans('gestlab.general.labels.quotes.charge_tax'),
        ];
    }
}
