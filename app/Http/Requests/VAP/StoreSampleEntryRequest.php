<?php

namespace App\Http\Requests\VAP;

use App\Models\Product;
use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSampleEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $sampleEntry = $this->route('sampleEntry');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('sample_entries', 'code')->ignore($sampleEntry?->id),
            ],
            'sample_type' => ['required', 'string', 'max:255'],
            'proposal_id' => ['nullable', 'exists:proposals,id'],
            'collection_product_id' => ['nullable', 'exists:collection_product,id'],
            'portal_request_id' => ['nullable', 'exists:customer_requests,id'],
            'customer_request_id' => ['nullable', 'exists:customer_requests,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'lab_id' => ['required', 'exists:labs,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'packaging_id' => ['nullable', 'exists:packaging_categories,id'],
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
            'client_submitted_info.product_id' => ['nullable', 'exists:products,id'],
            'client_submitted_info.matrix_id' => ['nullable', 'exists:matrixes,id'],
            'client_submitted_info.requested_profile_ids' => ['nullable', 'array'],
            'client_submitted_info.requested_profile_ids.*' => ['integer', 'exists:profiles,id'],
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
            'client_submitted_info.chain_of_custody_notes' => ['nullable', 'string', 'max:2000'],
            'retention_period_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'retention_due_at' => ['nullable', 'date'],
            'discard_scheduled_at' => ['nullable', 'date', 'after_or_equal:retention_due_at'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $productId = $this->integer('client_submitted_info.product_id');

                if (! $productId) {
                    return;
                }

                $product = Product::query()
                    ->with(['matrix.profiles.type'])
                    ->find($productId);

                if (! $product) {
                    return;
                }

                $selectedMatrixId = $this->input('client_submitted_info.matrix_id');

                if ($selectedMatrixId && (int) $selectedMatrixId !== (int) $product->matrix_id) {
                    $validator->errors()->add(
                        'client_submitted_info.matrix_id',
                        'A matriz selecionada não corresponde ao produto escolhido.'
                    );
                }

                $requestedProfileIds = collect($this->input('client_submitted_info.requested_profile_ids', []))
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
                            'Os perfis selecionados devem pertencer à matriz do produto escolhido.'
                        );
                    }
                }

                $departmentId = $this->integer('department_id');

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
                        'O produto selecionado não possui perfis analíticos compatíveis para o departamento informado.'
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
                        'Os perfis analíticos devem pertencer ao departamento selecionado.'
                    );
                }
            },
        ];
    }
}
