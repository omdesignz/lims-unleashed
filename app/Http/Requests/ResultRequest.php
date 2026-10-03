<?php

namespace App\Http\Requests;

use App\Models\Result;
use App\Models\Sample;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryResultStageIntegrity;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ResultRequest extends FormRequest
{
    private const RESULT_DISPLAY_FORMAT_STANDARD = 'standard';

    private const RESULT_DISPLAY_FORMAT_SCIENTIFIC = 'scientific';

    private const QUALITATIVE_RESULT_OPTIONS = ['Presença', 'Ausência'];

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
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();

        if ($this->isMethod('post')) {

            $rules = [
                'action' => ['required', Rule::in(['analyze', 'verify', 'approve'])],
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
                'results.*.equipment_id' => ['nullable', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
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
                'results.*.result_is_qualitative' => 'nullable|boolean',
                'results.*.result_options' => 'nullable|array',
                'results.*.result_options.*' => 'string',
                'results.*.calculation_metadata' => 'nullable|array',
                'results.*.extra_data' => 'nullable|array',
                'results.*.display_format' => 'nullable|in:standard,scientific',
                'results.*.calculated_at' => 'date',
                'signature' => 'nullable|string',
            ];
        } else {
            $rules = [
                'action' => ['required', Rule::in(['analyze', 'verify', 'approve'])],
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
                'results.*.equipment_id' => ['nullable', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
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
                'results.*.result_is_qualitative' => 'nullable|boolean',
                'results.*.result_options' => 'nullable|array',
                'results.*.result_options.*' => 'string',
                'results.*.calculation_metadata' => 'nullable|array',
                'results.*.extra_data' => 'nullable|array',
                'results.*.display_format' => 'nullable|in:standard,scientific',
                'results.*.calculated_at' => 'date',
                'signature' => 'nullable|string',

            ];
        }

        $rules['sample_id'] = ['required', 'integer', Rule::exists('samples', 'id')
            ->where(fn (Builder $query): Builder => $query->whereIn('samples.id',
                $this->samplesForRequest()->select('samples.id')))];
        $rules['results.*.result_id'] = [$this->input('action') === 'analyze' ? 'nullable' : 'required', 'integer', 'exists:results,id'];
        if ($this->routeIs('results.store.individual')) {
            $rules['results'] = ['required', 'array', 'size:1'];
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
     */
    protected function prepareForValidation(): void
    {
        $sample = $this->input('sample_id');
        $sampleId = is_array($sample) ? data_get($sample, 'value') : $sample;
        $rows = $this->input('results', []);
        if ($this->routeIs('results.store.individual') && ! $this->has('results')) {
            $rows = [array_replace($this->except(['action', 'signature', 'sample_id']), ['sample_id' => $sampleId])];
        }
        if (is_array($rows)) {
            $rows = array_map(function (mixed $row): mixed {
                if (! is_array($row)) {
                    return $row;
                }
                $row['result_is_qualitative'] = $this->resultIsQualitativePayload($row);
                $row['result_options'] = $this->resultOptionsFromPayload($row);
                $row['extra_data'] = $this->prepareResultExtraData($row);
                $row['display_format'] = $this->displayFormatFromResult($row);
                $row['equipment_id'] = data_get($row, 'equipment_id.value',
                    data_get($row, 'equipment_id', data_get($row, 'extra_data.equipment.equipment_id')));
                foreach (['parameter', 'product', 'protocol', 'unit', 'standard', 'code', 'nwp', 'type'] as $reference) {
                    $value = $row[$reference.'_id'] ?? null;
                    if (is_array($value)) {
                        $row[$reference === 'type' ? 'category_label' : $reference.'_label'] = data_get($value, 'label');
                        $row[$reference.'_id'] = data_get($value, 'value');
                    }
                }

                return $row;
            }, array_values($rows));
        }
        $this->merge(['sample_id' => $sampleId, 'results' => $rows]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $action = (string) $this->input('action');
            $results = collect($this->input('results', []));
            $sampleId = $this->input('sample_id');
            $workflowRelation = $this->routeIs('results.storeCounterAnalysisResults')
                ? 'counteranalysis'
                : 'analysis';
            $sample = $sampleId
                ? $this->samplesForRequest()
                    ->with([
                        $workflowRelation,
                        'collection.collection.sampleEntry',
                        'results:id,sample_id,parameter_id,profile_id,code_id,resultable_type,resultable_id,inserted_value,inserted_date,verified_value,verified_date',
                    ])
                    ->find($sampleId)
                : null;
            $qualitativeParameterIds = collect();

            if ($sample) {
                if (! $sample->{$workflowRelation}) {
                    $validator->errors()->add(
                        'sample_id',
                        $workflowRelation === 'counteranalysis'
                            ? 'A amostra seleccionada ainda não tem uma contra-análise associada.'
                            : 'A amostra seleccionada ainda não tem uma análise associada.'
                    );

                    return;
                }

                $collectionProduct = $sample->collection?->collection;
                if (! $collectionProduct) {
                    $validator->errors()->add('sample_id', 'A amostra não tem uma entrada analítica válida.');

                    return;
                }

                $expectedParameters = app(IssuedAnalyticalScope::class)
                    ->parametersFor($sample->{$workflowRelation}, $collectionProduct);
                $expectedParameterIds = $expectedParameters
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();
                $qualitativeParameterIds = $expectedParameters
                    ->filter(fn ($parameter): bool => $this->parameterIsQualitative($parameter))
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->flip();
                $submittedParameterIds = $results
                    ->map(fn (array $result) => (int) data_get($result, 'parameter_id'))
                    ->filter()
                    ->values();

                $expectedLineage = [
                    'sample_id' => (int) $sample->id,
                    'code_id' => (int) $sample->cl_id,
                    'collection_id' => (int) $sample->collection?->collection_id,
                    'product_id' => (int) $sample->{$workflowRelation}?->product_id,
                    'profile_id' => (int) $sample->{$workflowRelation}?->profile_id,
                ];

                $results->each(function (array $result, int $index) use ($validator, $expectedLineage): void {
                    foreach ($expectedLineage as $field => $expectedValue) {
                        if ($expectedValue > 0 && (int) data_get($result, $field) !== $expectedValue) {
                            $validator->errors()->add(
                                "results.$index.$field",
                                'A linhagem do resultado não corresponde à amostra seleccionada.'
                            );
                        }
                    }
                });

                if ($submittedParameterIds->diff($expectedParameterIds)->isNotEmpty()) {
                    $validator->errors()->add(
                        'results',
                        'A submissão contém parâmetros fora do perfil analítico atribuído à amostra.'
                    );
                }

                if (
                    in_array($action, ['analyze', 'verify', 'approve'], true)
                    && ! $this->routeIs('results.store.individual')
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
                    $root = $sample->{$workflowRelation};
                    $existingResultIds = $sample->results
                        ->where('profile_id', $root->profile_id)
                        ->where('code_id', $root->cl_id)
                        ->where('resultable_type', $root->getMorphClass())
                        ->where('resultable_id', $root->id)
                        ->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
                    $submittedResultIds = $results
                        ->map(fn (array $result) => (int) data_get($result, 'result_id'))
                        ->filter()
                        ->values();

                    if ($submittedResultIds->diff($existingResultIds)->isNotEmpty()) {
                        $validator->errors()->add(
                            'results',
                            'A submissão inclui resultados que não pertencem à amostra seleccionada.'
                        );
                    }
                }
                $results->each(function (array $row, int $index) use ($validator, $action, $sample): void {
                    $result = $action === 'analyze'
                        ? $sample->results->firstWhere('parameter_id', (int) data_get($row, 'parameter_id'))
                        : $sample->results->firstWhere('id', (int) data_get($row, 'result_id'));
                    try {
                        app(LaboratoryResultStageIntegrity::class)->ensure($result ?? new Result, $action, $row, $index);
                    } catch (ValidationException $exception) {
                        foreach ($exception->errors() as $field => $messages) {
                            foreach ($messages as $message) {
                                $validator->errors()->add($field, $message);
                            }
                        }
                    }
                });
            }

            if (in_array($action, ['verify', 'approve'], true) && blank($this->input('signature')) && blank(optional($this->user())->signature_url)) {
                $validator->errors()->add('signature', 'É necessária uma assinatura eletrónica para verificar ou aprovar resultados.');
            }

            $valueKey = match ($action) {
                'verify' => 'verified_value',
                'approve' => 'approved_value',
                default => 'inserted_value',
            };

            $results->each(function (array $result, int $index) use ($validator, $action, $valueKey, $qualitativeParameterIds): void {
                $value = data_get($result, $valueKey);
                $min = data_get($result, 'min_ref_value');
                $max = data_get($result, 'max_ref_value');
                $parameterId = data_get($result, 'parameter_id.value', data_get($result, 'parameter_id'));
                $parameterId = is_numeric($parameterId) ? (int) $parameterId : 0;
                $isQualitative = $qualitativeParameterIds->has($parameterId);

                if (blank($value)) {
                    $validator->errors()->add("results.$index.$valueKey", 'É obrigatório indicar um resultado não vazio.');

                    return;
                }

                if ($isQualitative) {
                    if (! in_array((string) $value, self::QUALITATIVE_RESULT_OPTIONS, true)) {
                        $validator->errors()->add(
                            "results.$index.$valueKey",
                            'Os resultados qualitativos devem ser Presença ou Ausência.'
                        );
                    }

                    if (blank(data_get($result, 'unit_id'))) {
                        $validator->errors()->add("results.$index.unit_id", 'A unidade de medição é obrigatória.');
                    }

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

    /** @return EloquentBuilder<Sample> */
    private function samplesForRequest(): EloquentBuilder
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $ownership = app(LaboratoryWorkflowOwnership::class);
        $workflowSamples = $this->routeIs('results.storeCounterAnalysisResults')
            ? $ownership->counterAnalysesForLaboratory($labId)->select('counter_analysis.sample_id')
            : $ownership->analysesForLaboratory($labId)->select('analysis.sample_id');

        return $ownership->samplesForLaboratory($labId)->whereIn('samples.id', $workflowSamples);
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
        if ($this->resultIsQualitativePayload($result)) {
            return self::RESULT_DISPLAY_FORMAT_STANDARD;
        }

        $displayFormat = data_get($result, 'display_format', data_get($result, 'extra_data.display_format'));

        return $displayFormat === self::RESULT_DISPLAY_FORMAT_SCIENTIFIC
            ? self::RESULT_DISPLAY_FORMAT_SCIENTIFIC
            : self::RESULT_DISPLAY_FORMAT_STANDARD;
    }

    private function resultIsQualitativePayload(array $result): bool
    {
        return $this->truthy(data_get($result, 'result_is_qualitative'))
            || $this->truthy(data_get($result, 'parameter_id.result_is_qualitative'))
            || data_get($result, 'result_type') === 'qualitative'
            || data_get($result, 'parameter_id.result_type') === 'qualitative';
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<int, string>
     */
    private function resultOptionsFromPayload(array $result): array
    {
        return $this->resultIsQualitativePayload($result)
            ? self::QUALITATIVE_RESULT_OPTIONS
            : [];
    }

    private function parameterIsQualitative(mixed $parameter): bool
    {
        return (bool) data_get($parameter, 'result_is_qualitative')
            || data_get($parameter, 'result_type') === 'qualitative';
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
