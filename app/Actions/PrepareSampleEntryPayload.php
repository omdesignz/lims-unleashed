<?php

namespace App\Actions;

use App\Models\CustomerRequest;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\VAPSampleEntry;
use App\Settings\GeneralSettings;
use App\Support\SampleEntryValidation;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PrepareSampleEntryPayload
{
    public const SYSTEM_FIELDS = [
        'linked_lab_code_id', 'linked_sample_ids', 'linked_collection_type',
        'resolved_profile_ids', 'resolved_profiles', 'required_parameters',
        'required_parameter_count', 'matrix_description', 'quality_control_path',
        'batch_sample',
    ];

    public const EVIDENCE_FIELDS = ['qc_release', 'qc_release_history', 'archived_by'];

    public const BATCH_FIELDS = [
        'manual_batch', 'manual_batch_registered_at', 'manual_batch_registered_by_id',
        'imported_from_spreadsheet', 'imported_at', 'imported_by_id',
    ];

    public function __construct(
        private readonly GeneralSettings $settings,
        private readonly SampleEntryValidation $validation,
    ) {}

    private function ensureExecutionIsAuthorized(array $validated, ?Proposal $proposal): void
    {
        if ($proposal) {
            $lineageErrors = collect([
                'proposal_id' => ! $proposal->isAccepted()
                    ? 'A proposta seleccionada ainda não foi aceite pelo cliente.'
                    : null,
                'customer_id' => (int) $proposal->customer_id !== (int) ($validated['customer_id'] ?? 0)
                    ? 'O cliente da amostra deve ser o mesmo da proposta aceite.'
                    : null,
                'warehouse_id' => (int) $proposal->warehouse_id !== (int) ($validated['warehouse_id'] ?? 0)
                    ? 'O local da amostra deve ser o mesmo da proposta aceite.'
                    : null,
                'department_id' => (int) $proposal->department_id !== (int) ($validated['department_id'] ?? 0)
                    ? 'O departamento da amostra deve corresponder ao âmbito da proposta aceite.'
                    : null,
            ])->filter()->all();

            if ($lineageErrors !== []) {
                throw ValidationException::withMessages($lineageErrors);
            }
        }

        $workIsStarting = ($validated['status'] ?? 'POR_INICIAR') !== 'POR_INICIAR'
            || ! empty($validated['analysis_start_date'])
            || ! empty($validated['analysis_end_date']);

        if (! $workIsStarting) {
            return;
        }

        $operationMode = $this->settings->app_operation_mode ?? 'client_only';
        $requestOrigin = data_get($validated, 'client_submitted_info.request_origin', 'client');

        if ($requestOrigin === 'internal' && in_array($operationMode, ['internal_only', 'hybrid'], true)) {
            return;
        }

        if (! $proposal || ! $proposal->isAccepted()) {
            throw ValidationException::withMessages([
                'proposal_id' => 'É necessário associar uma proposta aceite antes de colocar a amostra em análise.',
            ]);
        }
    }

    public function execute(
        array $validated,
        ?CustomerRequest $portalRequest,
        ?VAPSampleEntry $sampleEntry = null,
        ?Proposal $proposal = null
    ): array {
        unset($validated['portal_request_id']);

        foreach (['received_at', 'collected_at', 'analysis_start_date', 'analysis_end_date'] as $field) {
            if (filled($validated[$field] ?? null)) {
                $validated[$field] = Carbon::parse($validated[$field])->setTimezone(config('app.timezone'))->toDateTimeString();
            }
        }

        $submittedInfo = Arr::except($validated['client_submitted_info'] ?? [], array_merge(self::SYSTEM_FIELDS, self::EVIDENCE_FIELDS));
        $validated['client_submitted_info'] = array_replace($sampleEntry?->client_submitted_info ?? [], $submittedInfo);

        if ($portalRequest && ! $sampleEntry?->collection_product_id) {
            $validated['customer_request_id'] = $portalRequest->id;
            $validated['client_submitted_info'] = collect($validated['client_submitted_info'] ?? [])
                ->merge([
                    'request_origin' => data_get($portalRequest->extra_data, 'request_origin', 'client'),
                    'request_reference' => $portalRequest->reference,
                    'request_title' => $portalRequest->title,
                    'request_description' => $portalRequest->description,
                    'preferred_date' => optional($portalRequest->preferred_date)?->format('Y-m-d'),
                    'details' => $portalRequest->extra_data,
                ])
                ->all();
            if (blank($validated['requested_services'] ?? null)) {
                $validated['requested_services'] = collect($portalRequest->extra_data['requested_profiles'] ?? [])
                    ->filter()
                    ->values()
                    ->all();
            }

            $portalDetails = collect($portalRequest->extra_data ?? []);
            $batchIndex = data_get($validated['client_submitted_info'], 'batch_sample_index');
            $sourceDetails = $portalDetails->all();

            if ($batchIndex !== null) {
                $batchSample = collect($portalDetails->get('samples', []))
                    ->first(fn (mixed $row, int $position): bool => is_array($row) && (int) ($row['batch_index'] ?? $position) === (int) $batchIndex);

                if (! $batchSample) {
                    throw ValidationException::withMessages(['client_submitted_info.batch_sample_index' => 'A linha seleccionada já não existe no pedido do cliente.']);
                }

                $validated['client_submitted_info']['batch_sample_index'] = (int) $batchIndex;
                $validated['client_submitted_info']['batch_sample'] = $batchSample;
                $sourceDetails = array_replace($sourceDetails, Arr::where($batchSample, fn (mixed $value): bool => $value !== null && $value !== ''));
            }

            $sourceMetadata = Arr::only($sourceDetails, [
                'product_id', 'matrix_id', 'packaging_id', 'quantity', 'lot', 'product_name', 'matrix', 'packaging',
            ]);
            $sourceMetadata = Arr::where($sourceMetadata, fn (mixed $value): bool => $value !== null && $value !== '');

            if (is_int($sourceMetadata['quantity'] ?? null) || is_float($sourceMetadata['quantity'] ?? null)) {
                $sourceMetadata['quantity'] = (string) $sourceMetadata['quantity'];
            }

            if (! empty($sourceDetails['requested_profiles'])) {
                $sourceMetadata['requested_profile_ids'] = $sourceDetails['requested_profiles'];
            }

            $validated['client_submitted_info'] = array_replace($validated['client_submitted_info'], $sourceMetadata);
            $effectiveInput = array_replace($sampleEntry?->getAttributes() ?? [], $validated);
            $effectiveInput['client_submitted_info'] = Arr::except($validated['client_submitted_info'],
                array_merge(self::SYSTEM_FIELDS, self::EVIDENCE_FIELDS, self::BATCH_FIELDS));
            $this->validation->validate($effectiveInput, (int) ($validated['lab_id'] ?? $sampleEntry?->lab_id), $sampleEntry);
        } elseif ($sampleEntry && ! array_key_exists('customer_request_id', $validated)) {
            $validated['customer_request_id'] = $sampleEntry->customer_request_id;
        }

        $validated['client_submitted_info'] = collect($validated['client_submitted_info'] ?? [])
            ->merge([
                'request_origin' => data_get($validated, 'client_submitted_info.request_origin', 'client'),
                'collection_type' => data_get($validated, 'client_submitted_info.collection_type', 'direct'),
            ])
            ->all();

        $validated['client_submitted_info'] = $this->normalizeInternalQualityControlPayload(
            $validated['client_submitted_info'],
            $validated['sample_type'] ?? $sampleEntry?->sample_type
        );

        if (! $sampleEntry?->collection_product_id) {
            $validated['client_submitted_info'] = $this->attachAnalyticalScopeSnapshot(
                $validated['client_submitted_info'],
                $validated['department_id'] ?? $sampleEntry?->department_id
            );
        } else {
            $validated['client_submitted_info'] = array_replace(
                $validated['client_submitted_info'],
                Arr::only($sampleEntry->client_submitted_info ?? [], array_merge(self::SYSTEM_FIELDS, [
                    'product_id', 'matrix_id', 'requested_profile_ids', 'collection_type', 'request_origin', 'analysis_discipline', 'batch_sample_index',
                ]))
            );
        }

        $this->ensureExecutionIsAuthorized(array_replace($sampleEntry?->getAttributes() ?? [], $validated), $proposal);

        $receivedAt = isset($validated['received_at'])
            ? Carbon::parse($validated['received_at'])
            : ($sampleEntry?->received_at ?? now());
        $retentionDays = (int) ($validated['retention_period_days']
            ?? $sampleEntry?->retention_period_days
            ?? VAPSampleEntry::defaultRetentionPeriodFor($validated['sample_type'] ?? $sampleEntry?->sample_type));

        $retentionChanged = ! $sampleEntry
            || (array_key_exists('received_at', $validated) && $receivedAt->toDateString() !== $sampleEntry->received_at?->toDateString())
            || (array_key_exists('retention_period_days', $validated) && $retentionDays !== (int) $sampleEntry->retention_period_days);
        $validated['retention_period_days'] = $retentionDays;
        $validated['retention_due_at'] = $validated['retention_due_at']
            ?? (! $retentionChanged ? $sampleEntry?->retention_due_at?->toDateString() : null)
            ?? $receivedAt->copy()->addDays($retentionDays)->toDateString();
        $validated['discard_scheduled_at'] = $validated['discard_scheduled_at']
            ?? (! $retentionChanged ? $sampleEntry?->discard_scheduled_at?->toDateString() : null)
            ?? $validated['retention_due_at'];
        $validated['retention_status'] = $sampleEntry?->retention_status === 'discarded'
            ? 'discarded'
            : $this->resolveRetentionStatus($validated['retention_due_at']);

        return $validated;
    }

    private function normalizeInternalQualityControlPayload(array $clientSubmittedInfo, ?string $sampleType): array
    {
        $requestOrigin = data_get($clientSubmittedInfo, 'request_origin', 'client');
        $normalizedSampleType = strtoupper((string) $sampleType);

        if ($requestOrigin !== 'internal' || ! in_array($normalizedSampleType, ['MATERIA_PRIMA', 'RAW_MATERIAL'], true)) {
            return $clientSubmittedInfo;
        }

        $discipline = data_get($clientSubmittedInfo, 'analysis_discipline', 'chemistry');
        $purpose = data_get($clientSubmittedInfo, 'quality_control_purpose', 'raw_material_release');
        $decision = data_get($clientSubmittedInfo, 'qc_decision', 'hold_until_release');

        return collect($clientSubmittedInfo)
            ->merge([
                'request_origin' => 'internal',
                'material_category' => data_get($clientSubmittedInfo, 'material_category', 'raw_material'),
                'quality_control_purpose' => $purpose,
                'analysis_discipline' => $discipline,
                'qc_decision' => $decision,
                'quality_control_path' => [
                    'name' => 'Controlo interno de matéria-prima',
                    'procedure_type' => 'internal_quality_control',
                    'sample_family' => 'raw_material',
                    'discipline' => $discipline,
                    'purpose' => $purpose,
                    'decision_gate' => $decision,
                    'requires_proposal' => false,
                    'follows_normal_analysis_flow' => true,
                    'retention_period_days' => VAPSampleEntry::defaultRetentionPeriodFor($normalizedSampleType),
                    'steps' => [
                        'sample_entry',
                        'collection_product',
                        'lab_code',
                        'analysis',
                        'result_insertion',
                        'verification',
                        'approval',
                        'report_or_certificate',
                    ],
                ],
            ])
            ->all();
    }

    private function attachAnalyticalScopeSnapshot(array $clientSubmittedInfo, ?int $departmentId): array
    {
        $productId = data_get($clientSubmittedInfo, 'product_id');

        if (! $productId) {
            return $clientSubmittedInfo;
        }

        $product = Product::query()
            ->with([
                'matrix:id,description',
                'matrix.profiles' => function ($query) use ($departmentId) {
                    $query->with([
                        'type:id,name,department_id',
                        'parameters:id,name,code,requires_calculation,result_is_qualitative,result_type,active,decimal_places,formula_expression,formula_id,calculation_parameters,optimal_analysis_time',
                        'parameters.formula:id,name,code,expression,formula_expression,variables,decimal_places,output_unit',
                        'parameters.pivot.unit:id,code',
                        'parameters.pivot.protocol:id,code',
                        'parameters.pivot.standard:id,code',
                        'parameters.pivot.nwp:id,code',
                        'parameters.pivot.category:id,name',
                    ]);

                    if ($departmentId) {
                        $query->whereHas('type', function ($typeQuery) use ($departmentId) {
                            $typeQuery->where('department_id', $departmentId);
                        });
                    }
                },
            ])
            ->find($productId);

        if (! $product) {
            return $clientSubmittedInfo;
        }

        $availableProfiles = $product->matrix?->profiles ?? collect();
        $requestedProfileIds = collect(data_get($clientSubmittedInfo, 'requested_profile_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $resolvedProfiles = $requestedProfileIds->isNotEmpty()
            ? $availableProfiles->whereIn('id', $requestedProfileIds)->values()
            : $availableProfiles->values();

        $resolvedParameters = $resolvedProfiles
            ->flatMap(fn (Profile $profile) => $profile->parameters->map(function ($parameter) use ($profile) {
                return [
                    'id' => $parameter->id,
                    'name' => $parameter->name,
                    'code' => $parameter->code,
                    'profile_id' => $profile->id,
                    'profile' => $profile->name,
                    'requires_calculation' => $parameter->requires_calculation,
                    'result_is_qualitative' => $parameter->result_is_qualitative,
                    'result_type' => $parameter->result_type,
                    'active' => $parameter->active,
                    'decimal_places' => $parameter->decimal_places,
                    'formula_expression' => $parameter->formula_expression,
                    'formula_id' => $parameter->formula_id,
                    'formula' => $parameter->formula?->only(['id', 'name', 'code', 'expression', 'formula_expression', 'variables', 'decimal_places', 'output_unit']),
                    'calculation_parameters' => $parameter->calculation_parameters,
                    'optimal_analysis_time' => $parameter->optimal_analysis_time,
                    'unit_id' => $parameter->pivot?->unit_id,
                    'unit_label' => $parameter->pivot?->unit_label ?? $parameter->pivot?->unit?->code,
                    'unit_code' => $parameter->pivot?->unit?->code,
                    'protocol_id' => $parameter->pivot?->protocol_id,
                    'protocol_label' => $parameter->pivot?->protocol_label ?? $parameter->pivot?->protocol?->code,
                    'standard_id' => $parameter->pivot?->standard_id,
                    'standard_label' => $parameter->pivot?->standard_label ?? $parameter->pivot?->standard?->code,
                    'nwp_id' => $parameter->pivot?->nwp_id,
                    'nwp_label' => $parameter->pivot?->nwp_label ?? $parameter->pivot?->nwp?->code,
                    'category_id' => $parameter->pivot?->category_id,
                    'category_label' => $parameter->pivot?->category_label ?? $parameter->pivot?->category?->name,
                    'min_ref_value' => $parameter->pivot?->min_ref_value,
                    'max_ref_value' => $parameter->pivot?->max_ref_value,
                    'ref_val_origin' => $parameter->pivot?->ref_val_origin,
                    'accredited' => (bool) $parameter->pivot?->accredited,
                    'subcontractor' => $parameter->pivot?->subcontractor,
                    'uncertainty_coverage_factor' => $parameter->pivot?->uncertainty_coverage_factor,
                    'dilutions' => $parameter->pivot?->dilutions,
                ];
            }))
            ->groupBy('id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'id' => $first['id'],
                    'name' => $first['name'],
                    'code' => $first['code'],
                    'profiles' => $items->pluck('profile')->unique()->values()->all(),
                    'profile_ids' => $items->pluck('profile_id')->unique()->values()->all(),
                    'requires_calculation' => $first['requires_calculation'],
                    'result_is_qualitative' => $first['result_is_qualitative'],
                    'result_type' => $first['result_type'],
                    'active' => $first['active'],
                    'decimal_places' => $first['decimal_places'],
                    'formula_expression' => $first['formula_expression'],
                    'formula_id' => $first['formula_id'],
                    'formula' => $first['formula'],
                    'calculation_parameters' => $first['calculation_parameters'],
                    'optimal_analysis_time' => $first['optimal_analysis_time'],
                    'profile_definitions' => $items->map(fn (array $item): array => [
                        'profile_id' => $item['profile_id'],
                        'unit_id' => $item['unit_id'],
                        'unit_label' => $item['unit_label'],
                        'unit_code' => $item['unit_code'],
                        'protocol_id' => $item['protocol_id'],
                        'protocol_label' => $item['protocol_label'],
                        'standard_id' => $item['standard_id'],
                        'standard_label' => $item['standard_label'],
                        'nwp_id' => $item['nwp_id'],
                        'nwp_label' => $item['nwp_label'],
                        'category_id' => $item['category_id'],
                        'category_label' => $item['category_label'],
                        'min_ref_value' => $item['min_ref_value'],
                        'max_ref_value' => $item['max_ref_value'],
                        'ref_val_origin' => $item['ref_val_origin'],
                        'accredited' => $item['accredited'],
                        'subcontractor' => $item['subcontractor'],
                        'uncertainty_coverage_factor' => $item['uncertainty_coverage_factor'],
                        'dilutions' => $item['dilutions'],
                    ])->values()->all(),
                ];
            })
            ->sortBy('name')
            ->values();

        return collect($clientSubmittedInfo)
            ->merge([
                'matrix_id' => $product->matrix_id,
                'matrix_description' => $product->matrix?->description,
                'resolved_profile_ids' => $resolvedProfiles->pluck('id')->values()->all(),
                'resolved_profiles' => $resolvedProfiles->map(fn (Profile $profile) => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'analysis_type' => $profile->type?->name,
                    'department_id' => $profile->type?->department_id,
                    'parameter_count' => $profile->parameters->unique('id')->count(),
                ])->values()->all(),
                'required_parameter_count' => $resolvedParameters->count(),
                'required_parameters' => $resolvedParameters->all(),
            ])
            ->all();
    }

    private function resolveRetentionStatus(?string $retentionDueAt): string
    {
        if (! $retentionDueAt) {
            return 'active';
        }

        $dueDate = Carbon::parse($retentionDueAt);

        if ($dueDate->isPast()) {
            return 'overdue';
        }

        if ($dueDate->lte(now()->addDays(7))) {
            return 'due_soon';
        }

        return 'active';
    }
}
