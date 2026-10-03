<?php

namespace App\Services;

use App\Models\Matrix;
use App\Models\PaidService;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\TaxExemption;
use App\Models\TaxType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuoteAuthoringData
{
    public const CATALOGS = ['parameter' => Parameter::class, 'matrix' => Matrix::class, 'product' => Product::class, 'paid_service' => PaidService::class];

    /** @return list<string> */
    public function lineInputs(): array
    {
        return ['catalog_type', 'item_id', 'unit_id', 'collection_product_id', 'qty', 'agreed_unit_price', 'discount_mode', 'discount_value', 'obs'];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        foreach (['customer_id', 'warehouse_id'] as $field) {
            if (isset($input[$field]) && is_array($input[$field]) && array_key_exists('value', $input[$field])) {
                $input[$field] = $input[$field]['value'];
            }
        }
        if (is_array($input['items'] ?? null)) {
            foreach ($input['items'] as &$item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach (['item_id', 'unit_id', 'collection_product_id'] as $field) {
                    if (isset($item[$field]) && is_array($item[$field]) && array_key_exists('value', $item[$field])) {
                        $item[$field] = $item[$field]['value'];
                    }
                }
            }
            unset($item);
        }

        return $input;
    }

    /** @return array<string, mixed> */
    public function rules(bool $observationsOnly = false): array
    {
        $rules = ['obs' => [$observationsOnly ? 'present' : 'nullable', 'nullable', 'string', 'max:5000']];
        foreach (['id', 'lab_id', 'user_id', 'quote_no', 'quote_month', 'seq', 'unique_hash', 'invoice_id', 'converted_to_invoice', 'status',
            'is_original', 'exported_saft', 'deleted_at', 'file_path', 'extra_data', 'total', 'sub_total', 'discount', 'tax', 'formatted_items'] as $field) {
            $rules[$field] = ['missing'];
        }
        if ($observationsOnly) {
            return $rules;
        }
        $decimal = ['required', 'numeric', 'min:0', 'regex:/^\d{1,8}(\.\d{1,2})?$/'];

        return $rules + [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->whereNull('deleted_at')],
            'date' => ['sometimes', 'required', 'date_format:Y-m-d'], 'due_date' => ['nullable', 'date_format:Y-m-d'],
            'internal_ref' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:255'],
            'use_matrix_price' => ['required', 'boolean'], 'is_service' => ['required', 'boolean'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:100'], 'items.*' => ['required', 'array:'.implode(',', $this->lineInputs())],
            'items.*.catalog_type' => ['required', Rule::in(array_keys(self::CATALOGS))], 'items.*.item_id' => ['required', 'integer', 'min:1'],
            'items.*.unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->whereNull('deleted_at')],
            'items.*.collection_product_id' => ['nullable', 'integer', 'min:1'],
            'items.*.qty' => [...$decimal, 'gt:0'], 'items.*.agreed_unit_price' => $decimal,
            'items.*.discount_mode' => ['required', Rule::in(['percentage', 'fixed'])], 'items.*.discount_value' => $decimal,
            'items.*.obs' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validate(array $input, bool $observationsOnly = false): array
    {
        $input = $this->normalize($input);
        $validator = Validator::make($input, $this->rules($observationsOnly));
        if ($observationsOnly) {
            $validator->after(fn (\Illuminate\Validation\Validator $validator) => app(TradeCertificateData::class)->rejectLockedFields($validator, $input));
        }

        return $validator->validate();
    }

    /** @param array<string, mixed> $input
     * @return array{line: array<string, mixed>, discount_total: int}
     */
    public function calculate(array $input, int $index): array
    {
        $class = self::CATALOGS[$input['catalog_type']];
        $catalog = $class::query()->sharedLock()->find($input['item_id']);
        if (! $catalog) {
            throw ValidationException::withMessages(['items.'.$index.'.item_id' => 'O artigo seleccionado já não está disponível.']);
        }
        $price = $this->scaled((string) $input['agreed_unit_price']);
        $value = $this->scaled((string) $input['discount_value']);
        $quantity = $this->scaled((string) $input['qty']);
        if ($input['discount_mode'] === 'percentage' && $value > 10000) {
            throw ValidationException::withMessages(['items.'.$index.'.discount_value' => 'O desconto percentual não pode exceder 100%.']);
        }
        $discount = $input['discount_mode'] === 'percentage' ? intdiv($price * $value + 5000, 10000) : $value;
        if ($discount > $price) {
            throw ValidationException::withMessages(['items.'.$index.'.discount_value' => 'O desconto não pode exceder o preço unitário acordado.']);
        }
        $subtotal = $this->multiply($price - $discount, $quantity, 100);
        $discountTotal = $this->multiply($discount, $quantity, 100);
        $rate = (bool) $catalog->charge_tax ? $this->scaled((string) ($catalog->tax_percentage ?? 0)) : 0;
        if ($rate > 10000) {
            throw ValidationException::withMessages(['items.'.$index.'.item_id' => 'A taxa configurada para o artigo não é suportada.']);
        }
        $tax = $this->multiply($subtotal, $rate, 10000);
        foreach (['tax_id' => TaxType::class, 'exemption_id' => TaxExemption::class] as $field => $model) {
            if ($catalog->$field !== null && ! $model::query()->whereKey($catalog->$field)->sharedLock()->first()) {
                throw ValidationException::withMessages(['items.'.$index.'.item_id' => 'A classificação fiscal do artigo já não está disponível.']);
            }
        }

        return ['line' => [...Arr::only($input, ['item_id', 'unit_id', 'qty', 'obs']),
            'item_description' => $catalog instanceof Matrix ? $catalog->description : $catalog->name,
            'unit_price' => $this->decimal($price - $discount), 'total' => $this->decimal($subtotal),
            'discount_id' => null, 'discount_percentage' => $input['discount_mode'] === 'percentage' ? $this->decimal($value) : '0.00',
            'discount_amount' => $this->decimal($discount), 'tax_amount' => $this->decimal($tax), 'tax_percentage' => $this->decimal($rate),
            'charge_tax' => (bool) $catalog->charge_tax, 'tax_id' => $catalog->tax_id,
            'exemption_id' => $catalog->exemption_id, 'exemption_code' => $catalog->exemption_code,
            'itemable_id' => $input['collection_product_id'] ?? null, 'itemable_type' => isset($input['collection_product_id']) ? 'collectionproduct' : null,
            'extra_data' => ['catalog_type' => $input['catalog_type'], 'agreed_unit_price' => $this->decimal($price),
                'discount_mode' => $input['discount_mode'], 'discount_value' => $this->decimal($value)]], 'discount_total' => $discountTotal];
    }

    public function scaled(string $value): int
    {
        if (! preg_match('/^\d{1,8}(\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages(['items' => 'O valor decimal excede a precisão suportada.']);
        }
        [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return (int) $integer * 100 + (int) str_pad($fraction, 2, '0');
    }

    public function decimal(int $value): string
    {
        if ($value < 0 || $value > 9999999999) {
            throw ValidationException::withMessages(['items' => 'O total excede a precisão suportada.']);
        }

        return intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
    }

    private function multiply(int $left, int $right, int $divisor): int
    {
        $half = intdiv($divisor, 2);
        if ($right > 0 && $left > intdiv(9999999999 * $divisor - $half, $right)) {
            throw ValidationException::withMessages(['items' => 'O total excede a precisão suportada.']);
        }

        return intdiv($left * $right + $half, $divisor);
    }
}
