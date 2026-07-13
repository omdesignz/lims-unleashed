<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ContractGuideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'collection_id' => 'nullable|exists:lab_codes,id',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'guide_no' => 'nullable',
            'ref_no' => 'nullable',
            'entry_point' => 'nullable',
            'collection_point' => 'nullable',
            'du_no' => 'nullable',
            'nif' => 'nullable',
            'contact' => 'nullable',
            'email' => 'nullable',
            'bl' => 'nullable',
            'obs' => 'nullable',
            'date' => 'nullable|date_format:Y-m-d',
            'extra_data' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:contract_guide_items,id',
            'items.*.guide_id' => 'nullable|exists:contract_guides,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.country_id' => 'nullable|exists:countries,id',
            'items.*.collection_id' => 'nullable|exists:lab_codes,id',
            'items.*.bl' => 'nullable',
            'items.*.lot' => 'nullable',
            'items.*.manufacturer' => 'required',
            'items.*.origin' => 'required',
            'items.*.brand' => 'required',
            'items.*.du_no' => 'nullable',
            'items.*.obs' => 'nullable',
            'items.*.date' => 'nullable|date_format:Y-m-d',
        ];

        if ($this->isMethod('post')) {
            $rules = array_merge($rules, [
                'user_id' => 'required|exists:users,id',
                'guide_month' => 'required',
            ]);
        } else {
            $rules = array_merge($rules, [
                'id' => 'required|exists:contract_guides,id',
            ]);
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => trans('gestlab.general.labels.contract_guides.user_id'),
            'customer_id' => trans('gestlab.general.labels.contract_guides.customer_id'),
            'warehouse_id' => trans('gestlab.general.labels.contract_guides.warehouse_id'),
            'guide_no' => trans('gestlab.general.labels.contract_guides.guide_no'),
            'bl' => trans('gestlab.general.labels.contract_guides.bl'),
            'lot' => trans('gestlab.general.labels.contract_guides.lot'),
            'ref_no' => trans('gestlab.general.labels.contract_guides.ref_no'),
            'entry_point' => trans('gestlab.general.labels.contract_guides.entry_point'),
            'collection_point' => trans('gestlab.general.labels.contract_guides.collection_point'),
            'du_no' => trans('gestlab.general.labels.contract_guides.du_no'),
            'nif' => trans('gestlab.general.labels.contract_guides.nif'),
            'contact' => trans('gestlab.general.labels.contract_guides.contact'),
            'email' => trans('gestlab.general.labels.contract_guides.email'),
            'date' => trans('gestlab.general.labels.contract_guides.date'),
            'collection_id' => trans('gestlab.general.labels.contract_guides.collection_id'),
            'obs' => trans('gestlab.general.labels.contract_guides.obs'),
            'items' => trans('gestlab.general.labels.contract_guides.products'),
            'items.*.guide_id' => trans('gestlab.general.labels.contract_guides.guide_id'),
            'items.*.product_id' => trans('gestlab.general.labels.contract_guides.product_id'),
            'items.*.country_id' => trans('gestlab.general.labels.contract_guides.country_id'),
            'items.*.collection_id' => trans('gestlab.general.labels.contract_guides.collection_id'),
            'items.*.bl' => trans('gestlab.general.labels.contract_guides.bl'),
            'items.*.lot' => trans('gestlab.general.labels.contract_guides.lot'),
            'items.*.manufacturer' => trans('gestlab.general.labels.contract_guides.manufacturer'),
            'items.*.origin' => trans('gestlab.general.labels.contract_guides.country_id'),
            'items.*.brand' => trans('gestlab.general.labels.contract_guides.brand'),
            'items.*.du_no' => trans('gestlab.general.labels.contract_guides.du_no'),
            'items.*.obs' => trans('gestlab.general.labels.contract_guides.obs'),
            'items.*.date' => trans('gestlab.general.labels.contract_guides.date'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.product_id.required' => 'É obrigatória a indicação de um valor para o campo produto',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))->map(function (array $item): array {
            $country = $item['country_id'] ?? null;

            return [
                'id' => $item['id'] ?? null,
                'guide_id' => $item['guide_id'] ?? null,
                'product_id' => $this->optionValue($item['product_id'] ?? null),
                'country_id' => $this->optionValue($country),
                'bl' => $item['bl'] ?? null,
                'lot' => $item['lot'] ?? null,
                'manufacturer' => $item['manufacturer'] ?? null,
                'origin' => $item['origin'] ?? (is_array($country) ? ($country['label'] ?? null) : null),
                'brand' => $item['brand'] ?? null,
                'obs' => $item['obs'] ?? null,
                'du_no' => $item['du_no'] ?? null,
                'date' => $item['date'] ?? null,
                'collection_id' => $this->optionValue($item['collection_id'] ?? null),
            ];
        })->all();

        $this->merge([
            'id' => $this->route('guide') ?? $this->input('id'),
            'user_id' => auth()->id(),
            'guide_month' => now()->format('Y'),
            'customer_id' => $this->optionValue($this->input('customer_id')),
            'warehouse_id' => $this->optionValue($this->input('warehouse_id')),
            'collection_id' => $this->optionValue($this->input('collection_id')),
            'date' => $this->input('date') ?: now()->format('Y-m-d'),
            'items' => $items,
        ]);
    }

    private function optionValue(mixed $option): mixed
    {
        if (is_array($option)) {
            return $option['value'] ?? null;
        }

        return filled($option) ? $option : null;
    }
}
