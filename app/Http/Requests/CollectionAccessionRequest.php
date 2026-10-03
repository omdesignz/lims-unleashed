<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class CollectionAccessionRequest extends FormRequest
{
    abstract protected function collectionType(): string;

    public function authorize(): bool
    {
        return $this->user()?->can('edit_'.$this->collectionType().'_collections') ?? false;
    }

    /** @return array<string, ValidationRule|array|string> */
    public function rules(): array
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $ownership = app(LaboratoryWorkflowOwnership::class);
        $record = $ownership->collectionAccessionsForLaboratory($labId, $this->collectionType())
            ->findOrFail($this->route('collection'));

        $rules = [
            'customer_id' => ['sometimes', 'required', 'integer', Rule::in([$record->customer_id])],
            'warehouse_id' => ['sometimes', 'required', 'integer', Rule::in([$record->warehouse_id])],
            'product_id' => ['sometimes', 'required', 'integer', Rule::in([$record->product_id])],
            'collection_id' => ['sometimes', 'required', 'integer', Rule::in([$record->collection_id])],
            'invoice_id' => ['sometimes', 'nullable', 'integer', Rule::in([$record->invoice_id])],
            'owner_id' => ['nullable', 'integer', Rule::in($ownership->eligibleUsers($labId)->pluck('id')->all())],
            'collaborations' => ['nullable', 'array'],
            'collaborations.*.collaboration_id' => ['required', 'integer', 'distinct', Rule::exists('collection_collaborations', 'id')->whereNull('deleted_at')],
            'collectionreasons' => ['nullable', 'array'],
            'collectionreasons.*.reason_id' => ['required', 'integer', 'distinct', Rule::exists('collection_reasons', 'id')->whereNull('deleted_at')],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'collected_qty' => ['nullable', 'numeric', 'min:0'],
            'recollection' => ['nullable', 'boolean'],
            'collected_by_lab' => ['nullable', 'boolean'],
            'obs' => ['nullable', 'string'],
            'expiry_date' => ['nullable', 'date_format:Y-m-d'],
            'production_date' => ['nullable', 'date_format:Y-m-d'],
            'collection_date' => ['nullable', 'date_format:Y-m-d'],
            'result_id' => ['nullable', 'integer', Rule::exists('collection_end_results', 'id')->whereNull('deleted_at')],
            'vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')->whereNull('deleted_at')],
            'temperature_id' => ['nullable', 'integer', Rule::exists('temperatures', 'id')->whereNull('deleted_at')],
            'pack_id' => ['nullable', 'integer', Rule::exists('packaging_categories', 'id')->whereNull('deleted_at')],
            'customer_submitted_info' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (['comercial_brand', 'origin', 'location', 'du_no', 'container_no', 'term_no', 'lot', 'bl', 'temperature_value', 'sample_status', 'sampling_plan_ref'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        if ($this->collectionType() === 'programmed') {
            $rules['vehicle_reference'] = ['nullable', 'string', 'max:255'];
            $rules['collection_location'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = [];

        foreach (['customer_id', 'warehouse_id', 'product_id', 'comercial_brand', 'result_id', 'pack_id', 'vehicle_id', 'collection_date', 'temperature_id', 'lot', 'origin', 'location', 'obs', 'bl', 'term_no', 'container_no', 'expiry_date', 'production_date', 'collected_qty', 'qty', 'temperature_value', 'collected_by_lab', 'owner_id'] as $field) {
            $attributes[$field] = trans('gestlab.general.labels.'.$this->collectionType().'_collections.'.$field);
        }

        foreach (['sample_status', 'sampling_plan_ref', 'customer_submitted_info'] as $field) {
            $attributes[$field] = trans('gestlab.general.labels.direct_collections.'.$field);
        }

        return $attributes;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'product_id.required' => 'É obrigatória a indicação de um valor para o campo produto.',
            'customer_id.in' => 'O cliente pertence à entrada de amostra e não pode ser alterado nesta etapa.',
            'warehouse_id.in' => 'A instalação pertence à entrada de amostra e não pode ser alterada nesta etapa.',
            'product_id.in' => 'O produto pertence à entrada de amostra e não pode ser alterado nesta etapa.',
            'collection_id.in' => 'A colheita não pode ser transferida para outro registo.',
            'invoice_id.in' => 'A ligação comercial não pode ser alterada nesta etapa.',
            'owner_id.in' => 'Escolha um responsável activo e verificado deste laboratório.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['customer_id', 'warehouse_id', 'product_id', 'temperature_id', 'vehicle_id', 'collection_id', 'pack_id', 'result_id', 'invoice_id', 'owner_id'] as $field) {
            if ($this->exists($field)) {
                $value = $this->input($field);
                $this->merge([$field => is_array($value) ? data_get($value, 'value') : $value]);
            }
        }

        foreach (['collaborations' => 'collaboration_id', 'collectionreasons' => 'reason_id'] as $field => $key) {
            $values = $this->input($field);

            if (is_array($values)) {
                $this->merge([$field => array_map(fn (mixed $value): array => [
                    $key => is_array($value) ? data_get($value, $key, data_get($value, 'value')) : $value,
                ], $values)]);
            }
        }
    }
}
