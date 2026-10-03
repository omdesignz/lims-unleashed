<?php

namespace App\Support;

use App\Actions\PrepareSampleEntryPayload;
use App\Models\CustomerRequest;
use App\Models\Product;
use App\Models\Profile;
use App\Models\VAPSampleEntry;
use Closure;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SampleEntryValidation
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(array $input, int $labId, ?VAPSampleEntry $sampleEntry = null): array
    {
        $validator = ValidatorFacade::make($input, $this->rules($input, $labId, $sampleEntry), [], $this->attributes());

        foreach ($this->after($input, $sampleEntry) as $callback) {
            $validator->after($callback);
        }

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<mixed>>
     */
    public function rules(array $input, int $labId, ?VAPSampleEntry $sampleEntry = null): array
    {
        $customerId = $this->identifier($input['customer_id'] ?? null);
        $warehouseId = $this->identifier($input['warehouse_id'] ?? null);
        $portalRequest = Rule::exists('customer_requests', 'id')->where('customer_id', $customerId)
            ->where('warehouse_id', $warehouseId)->whereNull('deleted_at');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'bail',
                'sometimes',
                $sampleEntry?->code ? 'required' : 'nullable',
                'string',
                'max:255',
                Rule::unique('sample_entries', 'code')->ignore($sampleEntry?->id),
            ],
            'sample_type' => ['required', 'string', 'max:255'],
            'proposal_id' => ['bail', 'nullable', 'integer', Rule::exists('proposals', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'collection_product_id' => $sampleEntry?->collection_product_id
                ? ['bail', 'sometimes', 'required', 'integer', Rule::in([$sampleEntry->collection_product_id])]
                : ['prohibited'],
            'portal_request_id' => ['bail', 'nullable', 'integer', $portalRequest],
            'customer_request_id' => ['bail', 'nullable', 'integer', $portalRequest],
            'customer_id' => ['bail', 'required', 'integer', 'exists:customers,id'],
            'lab_id' => ['bail', 'required', 'integer', Rule::in([$labId])],
            'department_id' => ['bail', 'required', 'integer', 'exists:departments,id'],
            'warehouse_id' => ['bail', 'required', 'integer', Rule::exists('warehouses', 'id')->where('customer_id', $customerId)->whereNull('deleted_at')],
            'packaging_id' => ['bail', 'nullable', 'integer', 'exists:packaging_categories,id'],
            'received_at' => ['nullable', 'date'],
            'requested_services' => ['nullable'],
            'obs' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['POR_INICIAR', 'EN_PROGRESO', 'COMPLETADO', 'CANCELADO', 'EN_PAUSA'])],
            'analysis_start_date' => ['nullable', 'date'],
            'analysis_end_date' => ['nullable', 'date', 'after_or_equal:analysis_start_date'],
            'collected_by_lab' => ['sometimes', 'boolean'],
            'collected_at' => ['nullable', 'date'],
            'client_submitted_info' => ['nullable', 'array'],
            'client_submitted_info.request_origin' => ['nullable', Rule::in(['client', 'internal'])],
            'client_submitted_info.collection_type' => ['nullable', Rule::in(['direct', 'programmed'])],
            'client_submitted_info.collection_location' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.vehicle_reference' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.product_id' => ['bail', 'nullable', 'integer', 'exists:products,id'],
            'client_submitted_info.product_name' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.matrix' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.packaging' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.packaging_id' => ['bail', 'nullable', 'integer', 'exists:packaging_categories,id'],
            'client_submitted_info.batch_sample_index' => ['bail', 'nullable', 'integer', 'min:0', 'max:2147483647'],
            'client_submitted_info.matrix_id' => ['bail', 'nullable', 'integer', 'exists:matrixes,id'],
            'client_submitted_info.requested_profile_ids' => ['nullable', 'array'],
            'client_submitted_info.requested_profile_ids.*' => ['bail', 'integer', 'exists:profiles,id'],
            'client_submitted_info.conditioning_status' => ['nullable', Rule::in(['accepted', 'restricted', 'rejected'])],
            'client_submitted_info.quality_control_purpose' => ['nullable', Rule::in(['raw_material_release', 'supplier_qualification', 'process_validation', 'stability_follow_up', 'investigation', 'other'])],
            'client_submitted_info.analysis_discipline' => ['nullable', Rule::in(['microbiology', 'chemistry', 'microbiology_and_chemistry'])],
            'client_submitted_info.material_category' => ['nullable', Rule::in(['raw_material', 'ingredient', 'packaging_material', 'intermediate', 'finished_product', 'environmental_control', 'other'])],
            'client_submitted_info.qc_decision' => ['nullable', Rule::in(['hold_until_release', 'release_if_compliant', 'investigate_before_release', 'trend_only'])],
            'client_submitted_info.lot' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.batch' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.origin' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.location' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.quantity' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.collected_qty' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.production_date' => ['nullable', 'date'],
            'client_submitted_info.expiry_date' => ['nullable', 'date', 'after_or_equal:client_submitted_info.production_date'],
            'client_submitted_info.temperature_value' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.container_no' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.du_no' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.term_no' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.bl' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.sampling_plan_ref' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.supplier_name' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.packaging_condition' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.temperature_condition' => ['nullable', 'string', 'max:255'],
            'client_submitted_info.integrity_observations' => ['nullable', 'string', 'max:2000'],
            'client_submitted_info.customer_submitted_info' => ['nullable', 'string', 'max:2000'],
            'client_submitted_info.chain_of_custody_notes' => ['nullable', 'string', 'max:2000'],
            'retention_period_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'retention_due_at' => ['nullable', 'date'],
            'discard_scheduled_at' => ['nullable', 'date', 'after_or_equal:retention_due_at'],
        ];

        if ($sampleEntry?->code) {
            $rules['code'][] = Rule::in([$sampleEntry->code]);
        }

        if ($sampleEntry?->collection_product_id) {
            foreach (['customer_id', 'warehouse_id', 'department_id', 'sample_type'] as $field) {
                $rules[$field][] = Rule::in([$sampleEntry->{$field}]);
            }
        }

        if ($sampleEntry?->collection_product_id) {
            $rules['client_submitted_info.batch_sample_index'][] = Rule::in([data_get($sampleEntry->client_submitted_info, 'batch_sample_index')]);
        }

        foreach (array_merge(PrepareSampleEntryPayload::SYSTEM_FIELDS, PrepareSampleEntryPayload::EVIDENCE_FIELDS, PrepareSampleEntryPayload::BATCH_FIELDS) as $field) {
            if ($field === 'batch_sample' && ! $sampleEntry) {
                $rules['client_submitted_info.'.$field] = ['exclude'];

                continue;
            }

            $rules['client_submitted_info.'.$field] = [
                'sometimes',
                function (string $attribute, mixed $value, Closure $fail) use ($sampleEntry, $field): void {
                    if (! $sampleEntry || $value !== data_get($sampleEntry->client_submitted_info, $field)) {
                        $fail('As ligações e o âmbito emitidos da amostra são geridos pelo sistema.');
                    }
                },
            ];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nome da amostra',
            'code' => 'código da amostra',
            'sample_type' => 'tipo da amostra',
            'customer_id' => 'cliente',
            'lab_id' => 'laboratório',
            'department_id' => 'departamento',
            'warehouse_id' => 'local do cliente',
            'received_at' => 'data de recepção',
            'collected_at' => 'data de colheita',
            'client_submitted_info.product_id' => 'produto',
            'client_submitted_info.matrix_id' => 'matriz',
            'client_submitted_info.packaging_id' => 'embalagem',
            'client_submitted_info.requested_profile_ids' => 'perfis analíticos',
            'client_submitted_info.batch_sample_index' => 'linha do pedido do cliente',
            'client_submitted_info.lot' => 'lote',
            'client_submitted_info.quantity' => 'quantidade recebida',
            'client_submitted_info.collected_qty' => 'quantidade colhida',
            'client_submitted_info.customer_submitted_info' => 'informação fornecida pelo cliente',
            'client_submitted_info.integrity_observations' => 'observações de integridade',
            'client_submitted_info.chain_of_custody_notes' => 'cadeia de custódia e condicionamento',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<Closure(Validator): void>
     */
    public function after(array $input, ?VAPSampleEntry $sampleEntry = null): array
    {
        return [
            function (Validator $validator) use ($input, $sampleEntry): void {
                if ($validator->errors()->isNotEmpty() || $sampleEntry?->collection_product_id) {
                    return;
                }

                $this->validatePortalSelection($validator, $input);

                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $productId = (int) data_get($input, 'client_submitted_info.product_id', 0);

                if (! $productId) {
                    return;
                }

                $product = Product::query()
                    ->with(['matrix.profiles.type'])
                    ->find($productId);

                if (! $product) {
                    return;
                }

                $selectedMatrixId = data_get($input, 'client_submitted_info.matrix_id');

                if ($selectedMatrixId && (int) $selectedMatrixId !== (int) $product->matrix_id) {
                    $validator->errors()->add(
                        'client_submitted_info.matrix_id',
                        'A matriz seleccionada não corresponde ao produto escolhido.'
                    );
                }

                $requestedProfileIds = collect(data_get($input, 'client_submitted_info.requested_profile_ids', []))
                    ->filter()
                    ->map(fn (mixed $id) => (int) $id)
                    ->values();

                $allowedProfiles = $product->matrix?->profiles ?? collect();
                $allowedProfileIds = $allowedProfiles->pluck('id')->map(fn (mixed $id) => (int) $id)->values();

                if ($requestedProfileIds->isNotEmpty()) {
                    $invalidProfileIds = $requestedProfileIds->diff($allowedProfileIds);

                    if ($invalidProfileIds->isNotEmpty()) {
                        $validator->errors()->add(
                            'client_submitted_info.requested_profile_ids',
                            'Os perfis seleccionados devem pertencer à matriz do produto escolhido.'
                        );
                    }
                }

                $departmentId = (int) ($input['department_id'] ?? 0);

                if (! $departmentId) {
                    return;
                }

                $profilesForDepartmentCheck = $requestedProfileIds->isNotEmpty()
                    ? Profile::query()->with('type:id,department_id')->whereIn('id', $requestedProfileIds)->get()
                    : $allowedProfiles->filter(
                        fn (Profile $profile) => (int) $profile->type?->department_id === $departmentId
                    )->values();

                if ($profilesForDepartmentCheck->isEmpty()) {
                    $validator->errors()->add(
                        'client_submitted_info.requested_profile_ids',
                        'O produto seleccionado não possui perfis analíticos compatíveis para o departamento informado.'
                    );

                    return;
                }

                if (
                    $requestedProfileIds->isNotEmpty()
                    && $profilesForDepartmentCheck->contains(
                        fn (Profile $profile) => (int) $profile->type?->department_id !== $departmentId
                    )
                ) {
                    $validator->errors()->add(
                        'client_submitted_info.requested_profile_ids',
                        'Os perfis analíticos devem pertencer ao departamento seleccionado.'
                    );
                }
            },
        ];
    }

    private function identifier(mixed $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : 0;
    }

    /** @param array<string, mixed> $input */
    private function validatePortalSelection(Validator $validator, array $input): void
    {
        $portalId = $input['portal_request_id'] ?? null;
        $customerRequestId = $input['customer_request_id'] ?? null;

        if ($portalId && $customerRequestId && (int) $portalId !== (int) $customerRequestId) {
            $validator->errors()->add('customer_request_id', 'As referências do pedido do cliente devem identificar o mesmo pedido.');

            return;
        }

        $index = data_get($input, 'client_submitted_info.batch_sample_index');

        if ($index === null) {
            return;
        }

        $request = CustomerRequest::query()->find($portalId ?? $customerRequestId);
        $hasRow = collect(data_get($request?->extra_data, 'samples', []))
            ->contains(fn (mixed $row, int $position): bool => is_array($row) && (int) ($row['batch_index'] ?? $position) === (int) $index);

        if (! $hasRow) {
            $validator->errors()->add('client_submitted_info.batch_sample_index', 'Seleccione uma linha válida do pedido do cliente associado.');
        }
    }
}
