<?php

namespace App\Http\Requests;

use App\Models\Sample;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class ResultRequest extends FormRequest
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
        if ($this->isMethod('post')) {

            $rules = [
                'action' => 'nullable',
                'sample_id' => 'required|exists:samples,id',
                'results' => 'required|array|min:1',
                'results.*.result_id' => 'nullable|exists:results,id',
                'results.*.parameter_id' => 'required|exists:parameters,id',
                'results.*.parameter_label' => 'nullable',
                'results.*.product_id' => 'required|exists:products,id',
                'results.*.product_label' => 'nullable',
                'results.*.profile_id' => 'required|exists:profiles,id',
                'results.*.profile_label' => 'nullable',
                'results.*.protocol_id' => 'required|exists:protocols,id',
                'results.*.protocol_label' => 'nullable',
                'results.*.unit_id' => 'required|exists:units,id',
                'results.*.unit_label' => 'nullable',
                'results.*.standard_id' => 'required|exists:standards,id',
                'results.*.standard_label' => 'nullable',
                'results.*.nwp_id' => 'required|exists:nwps,id',
                'results.*.nwp_label' => 'nullable',
                'results.*.code_id' => 'required|exists:lab_codes,id',
                'results.*.code_label' => 'nullable',
                'results.*.sample_id' => 'required|exists:samples,id',
                'results.*.matrix_id' => 'required|exists:matrixes,id',
                'results.*.collection_id' => 'required|exists:collection_product,id',
                'results.*.equipment_id' => 'nullable|exists:i_items,id',
                'results.*.inserted_by_id' => 'nullable|exists:users,id',
                'results.*.verified_by_id' => 'nullable|exists:users,id',
                'results.*.approved_by_id' => 'nullable|exists:users,id',
                'results.*.inserted_by' => 'nullable',
                'results.*.verified_by' => 'nullable',
                'results.*.verification_status' => 'nullable',
                'results.*.approved_by' => 'nullable',
                'results.*.inserted_date' => 'nullable',
                'results.*.verified_date' => 'nullable',
                'results.*.approved_date' => 'nullable',
                'results.*.inserted_value' => 'nullable',
                'results.*.insertion_notes' => 'nullable',
                'results.*.verified_value' => 'nullable',
                'results.*.verification_notes' => 'nullable',
                'results.*.approved_value' => 'nullable',
                'results.*.approval_notes' => 'nullable',
                'results.*.uncertainty_value' => 'nullable',
                'results.*.count' => 'boolean',
                'results.*.status' => 'boolean',
                'results.*.min_ref_value' => 'nullable',
                'results.*.max_ref_value' => 'nullable',
                'results.*.ref_val_origin' => 'nullable',
                'results.*.requested_counter_analysis' => 'boolean',
                'results.*.type_id' => 'required|exists:result_categories,id',
                'results.*.category_label' => 'nullable',
                'results.*.sumC' => 'nullable',
                'results.*.volume' => 'nullable',
                'results.*.n1' => 'nullable',
                'results.*.n2' => 'nullable',
                'results.*.dilution' => 'nullable',
                'results.*.d1' => 'nullable',
                'results.*.d2' => 'nullable',
                'results.*.cfu1' => 'nullable',
                'results.*.cfu2' => 'nullable',
                'results.*.is_calculated' => 'boolean',
                'results.*.is_override' => 'boolean',
                'results.*.calculation_metadata' => 'nullable|array',
                'results.*.extra_data' => 'nullable|array',
                'results.*.display_format' => 'nullable|in:standard,scientific',
                'results.*.calculated_at' => 'date',
                'signature' => 'nullable|string',
            ];
        } else {
            $rules = [
                'action' => 'nullable',
                'sample_id' => 'required|exists:samples,id',
                'results' => 'required|array|min:1',
                'results.*.result_id' => 'required|exists:results,id',
                'results.*.parameter_id' => 'required|exists:parameters,id',
                'results.*.parameter_label' => 'nullable',
                'results.*.product_id' => 'required|exists:products,id',
                'results.*.product_label' => 'nullable',
                'results.*.profile_id' => 'required|exists:profiles,id',
                'results.*.profile_label' => 'nullable',
                'results.*.protocol_id' => 'required|exists:protocols,id',
                'results.*.protocol_label' => 'nullable',
                'results.*.unit_id' => 'required|exists:units,id',
                'results.*.unit_label' => 'nullable',
                'results.*.standard_id' => 'required|exists:standards,id',
                'results.*.standard_label' => 'nullable',
                'results.*.nwp_id' => 'required|exists:nwps,id',
                'results.*.nwp_label' => 'nullable',
                'results.*.code_id' => 'required|exists:lab_codes,id',
                'results.*.code_label' => 'nullable',
                'results.*.sample_id' => 'required|exists:samples,id',
                'results.*.matrix_id' => 'required|exists:matrixes,id',
                'results.*.collection_id' => 'required|exists:collection_product,id',
                'results.*.equipment_id' => 'nullable|exists:i_items,id',
                'results.*.inserted_by_id' => 'nullable|exists:users,id',
                'results.*.verified_by_id' => 'nullable|exists:users,id',
                'results.*.approved_by_id' => 'nullable|exists:users,id',
                'results.*.inserted_by' => 'nullable',
                'results.*.verified_by' => 'nullable',
                'results.*.verification_status' => 'nullable',
                'results.*.approved_by' => 'nullable',
                'results.*.inserted_date' => 'nullable',
                'results.*.verified_date' => 'nullable',
                'results.*.approved_date' => 'nullable',
                'results.*.inserted_value' => 'nullable',
                'results.*.insertion_notes' => 'nullable',
                'results.*.verified_value' => 'nullable',
                'results.*.verification_notes' => 'nullable',
                'results.*.approved_value' => 'nullable',
                'results.*.approval_notes' => 'nullable',
                'results.*.uncertainty_value' => 'nullable',
                'results.*.count' => 'boolean',
                'results.*.status' => 'boolean',
                'results.*.min_ref_value' => 'nullable',
                'results.*.max_ref_value' => 'nullable',
                'results.*.ref_val_origin' => 'nullable',
                'results.*.requested_counter_analysis' => 'boolean',
                'results.*.type_id' => 'required|exists:result_categories,id',
                'results.*.category_label' => 'nullable',
                'results.*.sumC' => 'nullable',
                'results.*.volume' => 'nullable',
                'results.*.n1' => 'nullable',
                'results.*.n2' => 'nullable',
                'results.*.dilution' => 'nullable',
                'results.*.d1' => 'nullable',
                'results.*.d2' => 'nullable',
                'results.*.cfu1' => 'nullable',
                'results.*.cfu2' => 'nullable',
                'results.*.is_calculated' => 'boolean',
                'results.*.is_override' => 'boolean',
                'results.*.calculation_metadata' => 'nullable|array',
                'results.*.extra_data' => 'nullable|array',
                'results.*.display_format' => 'nullable|in:standard,scientific',
                'results.*.calculated_at' => 'date',
                'signature' => 'nullable|string',

            ];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'sample_id' => trans('gestlab.general.labels.results.sample_id'),
            'results' => trans('gestlab.general.labels.results.results'),
            'results.*.result_id' => trans('gestlab.general.labels.results.result_id'),
            'results.*.parameter_id' => trans('gestlab.general.labels.results.parameter_id'),
            'results.*.product_id' => trans('gestlab.general.labels.results.product_id'),
            'results.*.profile_id' => trans('gestlab.general.labels.results.profile_id'),
            'results.*.protocol_id' => trans('gestlab.general.labels.results.protocol_id'),
            'results.*.unit_id' => trans('gestlab.general.labels.results.unit_id'),
            'results.*.standard_id' => trans('gestlab.general.labels.results.standard_id'),
            'results.*.nwp_id' => trans('gestlab.general.labels.results.nwp_id'),
            'results.*.code_id' => trans('gestlab.general.labels.results.code_id'),
            'results.*.sample_id' => trans('gestlab.general.labels.results.sample_id'),
            'results.*.matrix_id' => trans('gestlab.general.labels.results.matrix_id'),
            'results.*.collection_id' => trans('gestlab.general.labels.results.collection_id'),
            'results.*.equipment_id' => 'equipamento',
            'results.*.inserted_by' => trans('gestlab.general.labels.results.inserted_by'),
            'results.*.verified_by' => trans('gestlab.general.labels.results.verified_by'),
            'results.*.approved_by' => trans('gestlab.general.labels.results.approved_by'),
            'results.*.inserted_date' => trans('gestlab.general.labels.results.inserted_date'),
            'results.*.verified_date' => trans('gestlab.general.labels.results.verified_date'),
            'results.*.approved_date' => trans('gestlab.general.labels.results.approved_date'),
            'results.*.count' => trans('gestlab.general.labels.results.count'),
            'results.*.status' => trans('gestlab.general.labels.results.status'),
            'results.*.min_ref_value' => trans('gestlab.general.labels.results.min_ref_value'),
            'results.*.max_ref_value' => trans('gestlab.general.labels.results.max_ref_value'),
            'results.*.ref_val_origin' => trans('gestlab.general.labels.results.ref_val_origin'),
            'results.*.requested_counter_analysis' => trans('gestlab.general.labels.results.requested_counter_analysis'),
            'results.*.type_id' => trans('gestlab.general.labels.results.type_id'),
            'results.*.sumC' => trans('gestlab.general.labels.results.sumC'),
            'results.*.volume' => trans('gestlab.general.labels.results.volume'),
            'results.*.n1' => trans('gestlab.general.labels.results.n1'),
            'results.*.n2' => trans('gestlab.general.labels.results.n2'),
            'results.*.dilution' => trans('gestlab.general.labels.results.dilution'),
            'results.*.is_calculated' => trans('gestlab.general.labels.results.is_calculated'),
            'results.*.is_override' => trans('gestlab.general.labels.results.is_override'),
            'results.*.calculation_metadata' => trans('gestlab.general.labels.results.calculation_metadata'),
            'results.*.calculated_at' => trans('gestlab.general.labels.results.calculated_at'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     * @return void
     */
    public function prepareForValidation()
    {
        // dd(request()->all());

        if (request()->action == 'analyze') {
            // code...

            $this->merge([
                'sample_id' => ! is_null(request()->sample_id) ? request()->sample_id['value'] : null,
                'results' => is_null(request()->results) ? [] : collect(request()->results)->map(function ($item) {
                    return [
                        'approved_by' => $item['approved_by'],
                        'approved_by_id' => $item['approved_by_id'],
                        'verified_by_id' => $item['verified_by_id'],
                        'approved_date' => $item['approved_date'],
                        'verified_date' => $item['verified_date'],
                        'approved_value' => $item['approved_value'],
                        'approval_notes' => $item['approval_notes'],
                        'collection_id' => $item['collection_id'],
                        'count' => $item['count'],
                        'inserted_by' => $item['inserted_by'],
                        'inserted_by_id' => $item['inserted_by_id'],
                        'inserted_date' => now(),
                        'inserted_value' => $item['inserted_value'],
                        'insertion_notes' => $item['insertion_notes'],
                        'verified_value' => $item['verified_value'],
                        'verification_notes' => $item['verification_notes'],
                        'matrix_id' => $item['matrix_id'],
                        'max_ref_value' => $item['max_ref_value'],
                        'min_ref_value' => $item['min_ref_value'],
                        'parameter_id' => $item['parameter_id']['value'],
                        'parameter_label' => $item['parameter_id']['label'],
                        'product_id' => $item['product_id']['value'],
                        'product_label' => $item['product_id']['label'],
                        'protocol_id' => $item['protocol_id']['value'],
                        'protocol_label' => $item['protocol_id']['label'],
                        'profile_id' => $item['profile_id'],
                        'unit_id' => $item['unit_id']['value'],
                        'unit_label' => $item['unit_id']['label'],
                        'standard_id' => $item['standard_id']['value'],
                        'standard_label' => $item['standard_id']['label'],
                        'code_id' => $item['code_id']['value'],
                        'code_label' => $item['code_id']['label'],
                        'nwp_id' => $item['nwp_id']['value'],
                        'nwp_label' => $item['nwp_id']['label'],
                        'requested_counter_analysis' => $item['requested_counter_analysis'],
                        'sample_id' => $item['sample_id'],
                        'status' => $item['status'],
                        'type_id' => $item['type_id']['value'],
                        'category_label' => $item['type_id']['label'],
                        'uncertainty_value' => $item['uncertainty_value'] ?? null,
                        'sumC' => $item['sumC'],
                        'volume' => $item['volume'],
                        'n1' => $item['n1'] ?? 0,
                        'n2' => $item['n2'] ?? 0,
                        'dilution' => $item['dilution'],
                        'd1' => $item['d1'] ?? 0,
                        'd2' => $item['d2'] ?? 0,
                        'cfu1' => $item['cfu1'] ?? 0,
                        'cfu2' => $item['cfu2'] ?? 0,
                        'is_calculated' => $item['is_calculated'] ?? false,
                        'is_override' => $item['is_override'] ?? false,
                        'calculation_metadata' => $item['calculation_metadata'] ?? null,
                        'extra_data' => $this->prepareResultExtraData($item),
                        'display_format' => $this->displayFormatFromResult($item),
                    ];
                })->toArray(),
            ]);
        }

        if (request()->action == 'verify') {
            // code...

            $this->merge([
                'sample_id' => ! is_null(request()->sample_id) ? request()->sample_id['value'] : null,
                'results' => is_null(request()->results) ? [] : collect(request()->results)->map(function ($item) {
                    return [
                        'approved_by' => $item['approved_by'],
                        'result_id' => $item['result_id'],
                        'approved_by_id' => $item['approved_by_id'],
                        'verified_by_id' => $item['verified_by_id'],
                        'approved_date' => $item['approved_date'],
                        'verified_date' => now(),
                        'approved_value' => $item['approved_value'],
                        'approval_notes' => $item['approval_notes'] ?? null,
                        'collection_id' => $item['collection_id'],
                        'count' => $item['count'],
                        'inserted_by' => $item['inserted_by'],
                        'inserted_by_id' => $item['inserted_by_id'],
                        'inserted_date' => $item['inserted_date'],
                        'inserted_value' => $item['inserted_value'],
                        'insertion_notes' => $item['insertion_notes'],
                        'verified_by' => $item['verified_by'],
                        'verified_value' => $item['verified_value'],
                        'verification_status' => $item['verification_status'],
                        'verification_notes' => $item['verification_notes'] ?? null,
                        'matrix_id' => $item['matrix_id'],
                        'max_ref_value' => $item['max_ref_value'],
                        'min_ref_value' => $item['min_ref_value'],
                        'parameter_id' => $item['parameter_id']['value'],
                        'parameter_label' => $item['parameter_id']['label'],
                        'product_id' => $item['product_id']['value'],
                        'product_label' => $item['product_id']['label'],
                        'protocol_id' => $item['protocol_id']['value'],
                        'protocol_label' => $item['protocol_id']['label'],
                        'profile_id' => $item['profile_id'],
                        'unit_id' => $item['unit_id']['value'],
                        'unit_label' => $item['unit_id']['label'],
                        'standard_id' => $item['standard_id']['value'],
                        'standard_label' => $item['standard_id']['label'],
                        'code_id' => $item['code_id']['value'],
                        'code_label' => $item['code_id']['label'],
                        'nwp_id' => $item['nwp_id']['value'],
                        'nwp_label' => $item['nwp_id']['label'],
                        'requested_counter_analysis' => $item['requested_counter_analysis'],
                        'sample_id' => $item['sample_id'],
                        'status' => $item['status'],
                        'type_id' => $item['type_id']['value'],
                        'category_label' => $item['type_id']['label'],
                        'uncertainty_value' => $item['uncertainty_value'] ?? null,
                        'sumC' => $item['sumC'],
                        'volume' => $item['volume'],
                        'n1' => $item['n1'],
                        'n2' => $item['n2'],
                        'dilution' => $item['dilution'],
                        'd1' => $item['d1'],
                        'd2' => $item['d2'],
                        'cfu1' => $item['cfu1'],
                        'cfu2' => $item['cfu2'],
                        'is_calculated' => $item['is_calculated'] ?? false,
                        'is_override' => $item['is_override'] ?? false,
                        'calculation_metadata' => $item['calculation_metadata'] ?? null,
                        'extra_data' => $this->prepareResultExtraData($item),
                        'display_format' => $this->displayFormatFromResult($item),
                    ];
                })->toArray(),
            ]);
        }

        if (request()->action == 'approve') {
            // code...

            $this->merge([
                'sample_id' => ! is_null(request()->sample_id) ? request()->sample_id['value'] : null,
                'results' => is_null(request()->results) ? [] : collect(request()->results)->map(function ($item) {
                    return [
                        'approved_by' => $item['approved_by'],
                        'approved_by_id' => $item['approved_by_id'],
                        'verified_by_id' => $item['verified_by_id'],
                        'approved_date' => now(),
                        'verified_date' => $item['verified_date'],
                        'approved_value' => $item['approved_value'],
                        'collection_id' => $item['collection_id'],
                        'count' => $item['count'],
                        'result_id' => $item['result_id'],
                        'inserted_by' => $item['inserted_by'],
                        'inserted_by_id' => $item['inserted_by_id'],
                        'inserted_date' => $item['inserted_date'],
                        'inserted_value' => $item['inserted_value'],
                        'verified_by' => $item['verified_by'],
                        'verified_value' => $item['verified_value'],
                        'matrix_id' => $item['matrix_id'],
                        'max_ref_value' => $item['max_ref_value'],
                        'min_ref_value' => $item['min_ref_value'],
                        'parameter_id' => $item['parameter_id']['value'],
                        'parameter_label' => $item['parameter_id']['label'],
                        'product_id' => $item['product_id']['value'],
                        'product_label' => $item['product_id']['label'],
                        'protocol_id' => $item['protocol_id']['value'],
                        'protocol_label' => $item['protocol_id']['label'],
                        'profile_id' => $item['profile_id'],
                        'unit_id' => $item['unit_id']['value'],
                        'unit_label' => $item['unit_id']['label'],
                        'standard_id' => $item['standard_id']['value'],
                        'standard_label' => $item['standard_id']['label'],
                        'code_id' => $item['code_id']['value'],
                        'code_label' => $item['code_id']['label'],
                        'nwp_id' => $item['nwp_id']['value'],
                        'nwp_label' => $item['nwp_id']['label'],
                        'requested_counter_analysis' => $item['requested_counter_analysis'],
                        'sample_id' => $item['sample_id'],
                        'status' => $item['status'],
                        'type_id' => $item['type_id']['value'],
                        'category_label' => $item['type_id']['label'],
                        'uncertainty_value' => $item['uncertainty_value'] ?? null,
                        'sumC' => $item['sumC'],
                        'volume' => $item['volume'],
                        'n1' => $item['n1'],
                        'n2' => $item['n2'],
                        'dilution' => $item['dilution'],
                        'd1' => $item['d1'],
                        'd2' => $item['d2'],
                        'cfu1' => $item['cfu1'],
                        'cfu2' => $item['cfu2'],
                        'is_calculated' => $item['is_calculated'] ?? false,
                        'is_override' => $item['is_override'] ?? false,
                        'calculation_metadata' => $item['calculation_metadata'] ?? null,
                        'extra_data' => $this->prepareResultExtraData($item),
                        'display_format' => $this->displayFormatFromResult($item),
                    ];
                })->toArray(),
            ]);
        }

    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $action = (string) $this->input('action');
            $results = collect($this->input('results', []));
            $sampleId = $this->input('sample_id');
            $workflowRelation = $this->routeIs('results.storeCounterAnalysisResults')
                ? 'counteranalysis'
                : 'analysis';
            $sample = $sampleId
                ? Sample::query()
                    ->with([
                        "{$workflowRelation}.profile.parameters:id",
                        'results:id,sample_id,parameter_id',
                    ])
                    ->find($sampleId)
                : null;

            if ($sample) {
                if (! $sample->{$workflowRelation}) {
                    $validator->errors()->add(
                        'sample_id',
                        $workflowRelation === 'counteranalysis'
                            ? 'A amostra selecionada ainda não tem uma contra-análise associada.'
                            : 'A amostra selecionada ainda não tem uma análise associada.'
                    );

                    return;
                }

                $expectedParameterIds = collect($sample->{$workflowRelation}?->profile?->parameters ?? [])
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();
                $submittedParameterIds = $results
                    ->map(fn (array $result) => (int) data_get($result, 'parameter_id'))
                    ->filter()
                    ->values();

                if ($submittedParameterIds->diff($expectedParameterIds)->isNotEmpty()) {
                    $validator->errors()->add(
                        'results',
                        'A submissão contém parâmetros fora do perfil analítico atribuído à amostra.'
                    );
                }

                if (
                    in_array($action, ['analyze', 'verify', 'approve'], true)
                    && $expectedParameterIds->isNotEmpty()
                    && $expectedParameterIds->diff($submittedParameterIds)->isNotEmpty()
                ) {
                    $validator->errors()->add(
                        'results',
                        $action === 'analyze'
                            ? 'Todos os parâmetros previstos para a análise devem permanecer no lote de resultados.'
                            : 'Não é permitido verificar ou aprovar uma amostra com parâmetros previstos em falta no lote submetido.'
                    );
                }

                if (in_array($action, ['verify', 'approve'], true)) {
                    $existingResultIds = $sample->results->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
                    $submittedResultIds = $results
                        ->map(fn (array $result) => (int) data_get($result, 'result_id'))
                        ->filter()
                        ->values();

                    if ($submittedResultIds->diff($existingResultIds)->isNotEmpty()) {
                        $validator->errors()->add(
                            'results',
                            'A submissão inclui resultados que não pertencem à amostra selecionada.'
                        );
                    }
                }
            }

            if (in_array($action, ['verify', 'approve'], true) && blank($this->input('signature')) && blank(optional($this->user())->signature_url)) {
                $validator->errors()->add('signature', 'É necessária uma assinatura eletrónica para verificar ou aprovar resultados.');
            }

            $valueKey = match ($action) {
                'verify' => 'verified_value',
                'approve' => 'approved_value',
                default => 'inserted_value',
            };

            $results->each(function (array $result, int $index) use ($validator, $action, $valueKey): void {
                $value = data_get($result, $valueKey);
                $min = data_get($result, 'min_ref_value');
                $max = data_get($result, 'max_ref_value');

                if (blank($value)) {
                    return;
                }

                if (($min !== null || $max !== null) && ! is_numeric($value)) {
                    $validator->errors()->add("results.$index.$valueKey", 'Os resultados com limites de referência devem ser numéricos.');
                }

                if (in_array($action, ['verify', 'approve'], true) && is_numeric($value) && blank(data_get($result, 'uncertainty_value'))) {
                    $validator->errors()->add("results.$index.uncertainty_value", 'A incerteza de medição é obrigatória para resultados numéricos verificados ou aprovados.');
                }

                if (blank(data_get($result, 'unit_id'))) {
                    $validator->errors()->add("results.$index.unit_id", 'A unidade de medição é obrigatória.');
                }
            });
        });
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function prepareResultExtraData(array $result): array
    {
        $extraData = data_get($result, 'extra_data', []);

        if ($extraData instanceof Collection) {
            $extraData = $extraData->toArray();
        }

        if (! is_array($extraData)) {
            $extraData = [];
        }

        $extraData['display_format'] = $this->displayFormatFromResult($result);

        return $extraData;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function displayFormatFromResult(array $result): string
    {
        $displayFormat = data_get($result, 'display_format', data_get($result, 'extra_data.display_format'));

        return $displayFormat === 'scientific' ? 'scientific' : 'standard';
    }
}
