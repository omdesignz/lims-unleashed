<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResultRequest;
use App\Jobs\ApproveAnalysisResults;
use App\Jobs\ApproveCounterAnalysisResults;
use App\Jobs\ApproveIndividualResult;
use App\Jobs\InsertAnalysisResults;
use App\Jobs\InsertCounterAnalysisResults;
use App\Jobs\InsertIndividualResult;
use App\Jobs\VerifyAnalysisResults;
use App\Jobs\VerifyCounterAnalysisResults;
use App\Jobs\VerifyIndividualResult;
use App\Models\Analysis;
use App\Models\CounterAnalysis;
use App\Models\Result;
use App\Models\Sample;
use App\Services\IssuedAnalyticalScope;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use App\Support\DuplicateSubmissionGuard;
use App\Support\EquipmentMetrologyGate;
use App\Support\PersonnelQualificationGate;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultController extends Controller
{
    public function __construct(private readonly IssuedAnalyticalScope $issuedScope) {}

    private const RESULT_DISPLAY_FORMAT_STANDARD = 'standard';

    private const RESULT_DISPLAY_FORMAT_SCIENTIFIC = 'scientific';

    private const QUALITATIVE_RESULT_OPTIONS = ['Presença', 'Ausência'];

    /**
     * @return array<int, string>
     */
    private function qualitativeResultOptions(bool $isQualitative): array
    {
        return $isQualitative ? self::QUALITATIVE_RESULT_OPTIONS : [];
    }

    private function parameterIsQualitative(mixed $parameter): bool
    {
        return (bool) data_get($parameter, 'result_is_qualitative')
            || data_get($parameter, 'result_type') === 'qualitative';
    }

    /**
     * @return array<int, int>
     */
    private function qualitativeParameterIds(mixed $parameters): array
    {
        return collect($parameters)
            ->filter(fn ($parameter): bool => $this->parameterIsQualitative($parameter))
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $qualitativeParameterIds
     */
    private function resultPayloadIsQualitative(array $result, array $qualitativeParameterIds = []): bool
    {
        $parameterId = data_get($result, 'parameter_id.value', data_get($result, 'parameter_id'));
        $parameterId = is_numeric($parameterId) ? (int) $parameterId : 0;

        return in_array($parameterId, $qualitativeParameterIds, true)
            || (bool) data_get($result, 'result_is_qualitative')
            || (bool) data_get($result, 'parameter_id.result_is_qualitative')
            || data_get($result, 'result_type') === 'qualitative'
            || data_get($result, 'parameter_id.result_type') === 'qualitative';
    }

    private function displayFormatForPayload(array $result, bool $isQualitative): string
    {
        if ($isQualitative) {
            return self::RESULT_DISPLAY_FORMAT_STANDARD;
        }

        $displayFormat = data_get($result, 'display_format', data_get($result, 'extra_data.display_format'));

        return $displayFormat === self::RESULT_DISPLAY_FORMAT_SCIENTIFIC
            ? self::RESULT_DISPLAY_FORMAT_SCIENTIFIC
            : self::RESULT_DISPLAY_FORMAT_STANDARD;
    }

    /**
     * @return array{display_format: string}
     */
    private function defaultResultExtraData(): array
    {
        return [
            'display_format' => self::RESULT_DISPLAY_FORMAT_STANDARD,
        ];
    }

    private function resultDisplayFormat(Result $result): string
    {
        if ($this->parameterIsQualitative($result->parameter)) {
            return self::RESULT_DISPLAY_FORMAT_STANDARD;
        }

        $displayFormat = data_get($result->extra_data, 'display_format');

        return $displayFormat === self::RESULT_DISPLAY_FORMAT_SCIENTIFIC
            ? self::RESULT_DISPLAY_FORMAT_SCIENTIFIC
            : self::RESULT_DISPLAY_FORMAT_STANDARD;
    }

    /**
     * @return array<string, mixed>
     */
    private function resultExtraData(Result $result): array
    {
        return collect($result->extra_data)
            ->merge([
                'display_format' => $this->resultDisplayFormat($result),
            ])
            ->all();
    }

    /** @return Collection<int, Result> */
    private function issuedResultsFor(Analysis|CounterAnalysis $root): Collection
    {
        $root->loadMissing('sample.collection.collection.sampleEntry');
        $product = $root->sample?->collection?->collection;
        abort_unless($product, 404);
        $issuedParameters = $this->issuedScope->parametersFor($root, $product)->keyBy('id');

        return Result::with('product')
            ->where('sample_id', $root->sample_id)
            ->where('code_id', $root->cl_id)
            ->where('profile_id', $root->profile_id)
            ->where('resultable_type', $root->getMorphClass())
            ->where('resultable_id', $root->id)
            ->get()
            ->each(function (Result $result) use ($issuedParameters): void {
                $parameter = $issuedParameters->get($result->parameter_id);
                if (! $parameter) {
                    throw ValidationException::withMessages(['results' => 'O resultado não pertence ao âmbito analítico emitido.']);
                }

                $result->setRelation('parameter', $parameter);
            });
    }

    public function getDefaultResultsData()
    {
        abort_if(! auth()->user()->can('view_results'), 403, '');

        $action = request()->input('action');
        $sampleId = request()->input('sample_id');

        if (! in_array($action, ['analyze', 'verify', 'approve'], true)) {
            return response()->json([
                'message' => 'A etapa do fluxo de resultados é inválida.',
            ], 422);
        }

        if (blank($sampleId)) {
            return response()->json([
                'message' => 'A amostra é obrigatória para carregar os resultados.',
            ], 422);
        }

        $analysis = app(LaboratoryWorkflowOwnership::class)
            ->analysesForLaboratory(app(SampleLaboratoryAccess::class)->activeLabId())
            ->where('sample_id', $sampleId)->firstOrFail();

        if ($action == 'analyze') {
            $sample = Sample::with(
                'analysis.profile',
                'collection.collection.product',
                'collection.collection.sampleEntry',
                'results'
            )->findOrFail($sampleId);

            if (! $sample->analysis?->profile || ! $sample->collection?->collection?->product) {
                return response()->json([
                    'message' => 'A amostra ainda não tem análise, perfil ou produto suficientes para lançar resultados.',
                ], 422);
            }

            return $this->issuedScope->parametersFor($sample->analysis, $sample->collection->collection)->map(function ($item) use ($sample) {
                $isQualitative = $this->parameterIsQualitative($item);

                return [
                    'sample_id' => request()->sample_id,
                    'code_id' => [
                        'value' => $sample->cl_id,
                        'label' => $sample->collection->code,
                    ],
                    'code_label' => $sample->collection->code,
                    'product_id' => [
                        'value' => $sample->collection->collection->product_id,
                        'label' => $sample->collection->collection->product->name,
                    ],
                    'parameter_id' => [
                        'value' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'result_is_qualitative' => $isQualitative,
                        'result_options' => $this->qualitativeResultOptions($isQualitative),
                        'decimal_places' => $item->decimal_places,
                        'requires_calculation' => $item->requires_calculation,
                        'formula_expression' => $item->formula_expression,
                        'formula_id' => $item->formula_id,
                        'calculation_parameters' => $item->calculation_parameters,
                        'result_type' => $item->result_type,
                        'active' => $item->active,
                        'code' => $item->code,
                    ],
                    'formula' => $item->formula,
                    'result_is_qualitative' => $isQualitative,
                    'result_options' => $this->qualitativeResultOptions($isQualitative),
                    'display_format' => self::RESULT_DISPLAY_FORMAT_STANDARD,

                    // Added
                    'decimal_places' => $item->decimal_places,
                    'requires_calculation' => $item->requires_calculation,
                    'formula_expression' => $item->formula_expression,
                    'formula_id' => $item->formula_id,
                    'calculation_parameters' => $item->calculation_parameters,
                    'result_type' => $item->result_type,
                    'active' => $item->active,
                    // End Added

                    'parameter_label' => $item->name,
                    'profile_id' => $sample->analysis->profile_id,
                    'matrix_id' => $sample->collection->collection->product->matrix_id,
                    'collection_id' => $sample->collection->collection_id,
                    'inserted_by_id' => auth()->id(),
                    'verified_by_id' => null,
                    'approved_by_id' => null,
                    'type_id' => [
                        'value' => $item->pivot->category_id,
                        'label' => $item->pivot->category_label,
                    ],
                    'category_label' => $item->pivot->category_label,
                    'nwp_id' => [
                        'value' => $item->pivot->nwp_id,
                        'label' => $item->pivot->nwp_label,
                    ],
                    'nwp_label' => $item->pivot->nwp_label,
                    'unit_id' => [
                        'value' => $item->pivot->unit_id,
                        'label' => $item->pivot->unit_label,
                    ],
                    'unit_label' => $item->pivot->unit_label,
                    'protocol_id' => [
                        'value' => $item->pivot->protocol_id,
                        'label' => $item->pivot->protocol_label,
                    ],
                    'protocol_label' => $item->pivot->protocol_label,
                    'standard_id' => [
                        'value' => $item->pivot->standard_id,
                        'label' => $item->pivot->standard_label,
                    ],
                    'standard_label' => $item->pivot->standard_label,
                    'status' => false,
                    'count' => true,
                    'requested_counter_analysis' => false,
                    'inserted_by' => auth()->user()->name,
                    'verified_by' => null,
                    'approved_by' => null,
                    'inserted_value' => null,
                    'insertion_notes' => null,
                    'verified_value' => null,
                    'verification_notes' => null,
                    'verification_status' => null,
                    'approved_value' => null,
                    'approval_notes' => null,
                    'uncertainty_value' => null,
                    'uncertainty_value' => null,
                    'resultable_id' => null,
                    'resultable_type' => null,
                    'inserted_date' => null,
                    'verified_date' => null,
                    'approved_date' => null,
                    'extra_data' => $this->defaultResultExtraData(),
                    'min_ref_value' => $item->pivot->min_ref_value,
                    'max_ref_value' => $item->pivot->max_ref_value,
                    'ref_val_origin' => $item->pivot->ref_val_origin,
                    'sumC' => 0,
                    'volume' => 1,
                    'n1' => 0,
                    'n2' => 0,
                    'dilution' => 0,
                    'd1' => 0,
                    'd2' => 0,
                    'cfu1' => 0,
                    'cfu2' => 0,
                ];
            });
        }

        if ($action == 'verify') {
            $results = $this->issuedResultsFor($analysis);

            return collect($results)->map(function ($item) {
                $isQualitative = $this->parameterIsQualitative($item->parameter);

                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label,
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product?->name ?? $item->product_label,
                    ],
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter?->name,
                        'result_is_qualitative' => $isQualitative,
                        'result_options' => $this->qualitativeResultOptions($isQualitative),
                        'decimal_places' => $item->parameter?->decimal_places,
                        'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                        'formula_expression' => $item->parameter?->formula_expression,
                        'formula_id' => $item->parameter?->formula_id,
                        'calculation_parameters' => $item->parameter?->calculation_parameters,
                        'result_type' => $item->parameter?->result_type,
                        'active' => $item->parameter?->active ?? true,
                        'code' => $item->parameter?->code,
                    ],
                    'formula' => $item->parameter?->formula,
                    'result_is_qualitative' => $isQualitative,
                    'result_options' => $this->qualitativeResultOptions($isQualitative),
                    'display_format' => $this->resultDisplayFormat($item),

                    // Added
                    'decimal_places' => $item->parameter?->decimal_places,
                    'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                    'formula_expression' => $item->parameter?->formula_expression,
                    'formula_id' => $item->parameter?->formula_id,
                    'calculation_parameters' => $item->parameter?->calculation_parameters,
                    'result_type' => $item->parameter?->result_type,
                    'active' => $item->parameter?->active ?? true,
                    // End Added

                    'parameter_label' => $item->parameter_label,
                    'profile_id' => $item->profile_id,
                    'matrix_id' => $item->matrix_id,
                    'collection_id' => $item->collection_id,
                    'inserted_by_id' => $item->inserted_by_id,
                    'verified_by_id' => auth()->id(),
                    'approved_by_id' => null,
                    'type_id' => [
                        'value' => $item->type_id,
                        'label' => $item->category_label,
                    ],
                    'category_label' => $item->category_label,
                    'nwp_id' => [
                        'value' => $item->nwp_id,
                        'label' => $item->nwp_label,
                    ],
                    'nwp_label' => $item->nwp_label,
                    'unit_id' => [
                        'value' => $item->unit_id,
                        'label' => $item->unit_label,
                    ],
                    'unit_label' => $item->unit_label,
                    'protocol_id' => [
                        'value' => $item->protocol_id,
                        'label' => $item->protocol_label,
                    ],
                    'protocol_label' => $item->protocol_label,
                    'standard_id' => [
                        'value' => $item->standard_id,
                        'label' => $item->standard_label,
                    ],
                    'status' => $item->status,
                    'count' => $item->count,
                    'requested_counter_analysis' => $item->requested_counter_analysis,
                    'inserted_by' => $item->inserted_by,
                    'verified_by' => auth()->user()->name,
                    'approved_by' => null,
                    'inserted_value' => $item->inserted_value,
                    'insertion_notes' => $item->insertion_notes,
                    'verified_value' => $item->inserted_value,
                    'verification_notes' => $item->insertion_notes,
                    'verification_status' => null,
                    'approved_value' => null,
                    'approval_notes' => null,
                    'uncertainty_value' => $item->uncertainty_value ?? null,
                    'resultable_id' => null,
                    'resultable_type' => null,
                    'inserted_date' => $item->inserted_date,
                    'verified_date' => null,
                    'approved_date' => null,
                    'extra_data' => $this->resultExtraData($item),
                    'min_ref_value' => $item->min_ref_value,
                    'max_ref_value' => $item->max_ref_value,
                    'ref_val_origin' => $item->ref_val_origin,
                    'sumC' => $item->sumC,
                    'volume' => $item->volume,
                    'n1' => $item->n1,
                    'n2' => $item->n2,
                    'dilution' => $item->dilution,
                    'd1' => $item->d1,
                    'd2' => $item->d2,
                    'cfu1' => $item->cfu1,
                    'cfu2' => $item->cfu2,
                    'calculation_metadata' => $item->calculation_metadata,
                ];
            });
        }

        if ($action == 'approve') {
            $results = $this->issuedResultsFor($analysis);

            return collect($results)->map(function ($item) {
                $isQualitative = $this->parameterIsQualitative($item->parameter);

                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label,
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product?->name ?? $item->product_label,
                    ],
                    'product_label' => $item->product_label,
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter?->name,
                        'result_is_qualitative' => $isQualitative,
                        'result_options' => $this->qualitativeResultOptions($isQualitative),
                        'decimal_places' => $item->parameter?->decimal_places,
                        'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                        'formula_expression' => $item->parameter?->formula_expression,
                        'formula_id' => $item->parameter?->formula_id,
                        'calculation_parameters' => $item->parameter?->calculation_parameters,
                        'result_type' => $item->parameter?->result_type,
                        'active' => $item->parameter?->active ?? true,
                        'code' => $item->parameter?->code,
                    ],
                    'formula' => $item->parameter?->formula,
                    'result_is_qualitative' => $isQualitative,
                    'result_options' => $this->qualitativeResultOptions($isQualitative),
                    'display_format' => $this->resultDisplayFormat($item),

                    // Added
                    'decimal_places' => $item->parameter?->decimal_places,
                    'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                    'formula_expression' => $item->parameter?->formula_expression,
                    'formula_id' => $item->parameter?->formula_id,
                    'calculation_parameters' => $item->parameter?->calculation_parameters,
                    'result_type' => $item->parameter?->result_type,
                    'active' => $item->parameter?->active ?? true,
                    // End Added

                    'parameter_label' => $item->parameter_label,
                    'profile_id' => $item->profile_id,
                    'matrix_id' => $item->matrix_id,
                    'collection_id' => $item->collection_id,
                    'inserted_by_id' => $item->inserted_by_id,
                    'verified_by_id' => $item->verified_by_id,
                    'approved_by_id' => auth()->id(),
                    'type_id' => [
                        'value' => $item->type_id,
                        'label' => $item->category_label,
                    ],
                    'category_label' => $item->category_label,
                    'nwp_id' => [
                        'value' => $item->nwp_id,
                        'label' => $item->nwp_label,
                    ],
                    'nwp_label' => $item->nwp_label,
                    'unit_id' => [
                        'value' => $item->unit_id,
                        'label' => $item->unit_label,
                    ],
                    'protocol_id' => [
                        'value' => $item->protocol_id,
                        'label' => $item->protocol_label,
                    ],
                    'protocol_label' => $item->protocol_label,
                    'standard_id' => [
                        'value' => $item->standard_id,
                        'label' => $item->standard_label,
                    ],
                    'standard_label' => $item->standard_label,
                    'status' => $item->status,
                    'count' => $item->count,
                    'requested_counter_analysis' => $item->requested_counter_analysis,
                    'inserted_by' => $item->inserted_by,
                    'verified_by' => $item->verified_by,
                    'approved_by' => auth()->user()->name,
                    'inserted_value' => $item->inserted_value,
                    'insertion_notes' => $item->insertion_notes,
                    'verified_value' => $item->verified_value,
                    'verification_notes' => $item->verification_notes ?? null,
                    'verification_status' => $item->verification_status ?? null,
                    'approved_value' => $item->verified_value,
                    'approval_notes' => $item->verification_notes ?? null,
                    'uncertainty_value' => $item->uncertainty_value ?? null,
                    'resultable_id' => null,
                    'resultable_type' => null,
                    'inserted_date' => $item->inserted_date,
                    'verified_date' => $item->verified_date,
                    'approved_date' => null,
                    'extra_data' => $this->resultExtraData($item),
                    'min_ref_value' => $item->min_ref_value,
                    'max_ref_value' => $item->max_ref_value,
                    'ref_val_origin' => $item->ref_val_origin,
                    'sumC' => $item->sumC,
                    'volume' => $item->volume,
                    'n1' => $item->n1,
                    'n2' => $item->n2,
                    'dilution' => $item->dilution,
                    'd1' => $item->d1,
                    'd2' => $item->d2,
                    'cfu1' => $item->cfu1,
                    'cfu2' => $item->cfu2,
                    'calculation_metadata' => $item->calculation_metadata,
                ];
            });
        }

    }

    public function getCounterAnalysisDefaultResultsData()
    {
        abort_if(! auth()->user()->can('view_results'), 403, '');

        $action = request()->input('action');
        $sampleId = request()->input('sample_id');

        if (! in_array($action, ['analyze', 'verify', 'approve'], true)) {
            return response()->json([
                'message' => 'A etapa do fluxo de resultados da contra-análise é inválida.',
            ], 422);
        }

        if (blank($sampleId)) {
            return response()->json([
                'message' => 'A amostra é obrigatória para carregar os resultados da contra-análise.',
            ], 422);
        }

        $counterAnalysis = app(LaboratoryWorkflowOwnership::class)
            ->counterAnalysesForLaboratory(app(SampleLaboratoryAccess::class)->activeLabId())
            ->where('sample_id', $sampleId)->firstOrFail();

        if ($action == 'analyze') {
            $sample = Sample::with(
                'counteranalysis.profile',
                'collection.collection.product',
                'collection.collection.sampleEntry',
                'results'
            )->findOrFail($sampleId);

            if (! $sample->counteranalysis?->profile || ! $sample->collection?->collection?->product) {
                return response()->json([
                    'message' => 'A amostra ainda não tem contra-análise, perfil ou produto suficientes para lançar resultados.',
                ], 422);
            }

            return $this->issuedScope->parametersFor($sample->counteranalysis, $sample->collection->collection)->map(function ($item) use ($sample) {
                $isQualitative = $this->parameterIsQualitative($item);

                return [
                    'sample_id' => request()->sample_id,
                    'code_id' => [
                        'value' => $sample->cl_id,
                        'label' => $sample->collection->code,
                    ],
                    'code_label' => $sample->collection->code,
                    'product_id' => [
                        'value' => $sample->collection->collection->product_id,
                        'label' => $sample->collection->collection->product->name,
                    ],
                    'parameter_id' => [
                        'value' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'result_is_qualitative' => $isQualitative,
                        'result_options' => $this->qualitativeResultOptions($isQualitative),
                        'decimal_places' => $item->decimal_places,
                        'requires_calculation' => $item->requires_calculation,
                        'formula_expression' => $item->formula_expression,
                        'formula_id' => $item->formula_id,
                        'calculation_parameters' => $item->calculation_parameters,
                        'result_type' => $item->result_type,
                        'active' => $item->active,
                        'code' => $item->code,
                    ],
                    'formula' => $item->formula,
                    'result_is_qualitative' => $isQualitative,
                    'result_options' => $this->qualitativeResultOptions($isQualitative),
                    'display_format' => self::RESULT_DISPLAY_FORMAT_STANDARD,
                    'decimal_places' => $item->decimal_places,
                    'requires_calculation' => $item->requires_calculation,
                    'formula_expression' => $item->formula_expression,
                    'formula_id' => $item->formula_id,
                    'calculation_parameters' => $item->calculation_parameters,
                    'result_type' => $item->result_type,
                    'active' => $item->active,
                    'parameter_label' => $item->name,
                    'profile_id' => $sample->counteranalysis->profile_id,
                    'matrix_id' => $sample->collection->collection->product->matrix_id,
                    'collection_id' => $sample->collection->collection_id,
                    'inserted_by_id' => auth()->id(),
                    'verified_by_id' => null,
                    'approved_by_id' => null,
                    'type_id' => [
                        'value' => $item->pivot->category_id,
                        'label' => $item->pivot->category_label,
                    ],
                    'category_label' => $item->pivot->category_label,
                    'nwp_id' => [
                        'value' => $item->pivot->nwp_id,
                        'label' => $item->pivot->nwp_label,
                    ],
                    'nwp_label' => $item->pivot->nwp_label,
                    'unit_id' => [
                        'value' => $item->pivot->unit_id,
                        'label' => $item->pivot->unit_label,
                    ],
                    'unit_label' => $item->pivot->unit_label,
                    'protocol_id' => [
                        'value' => $item->pivot->protocol_id,
                        'label' => $item->pivot->protocol_label,
                    ],
                    'protocol_label' => $item->pivot->protocol_label,
                    'standard_id' => [
                        'value' => $item->pivot->standard_id,
                        'label' => $item->pivot->standard_label,
                    ],
                    'standard_label' => $item->pivot->standard_label,
                    'status' => false,
                    'count' => true,
                    'requested_counter_analysis' => false,
                    'inserted_by' => auth()->user()->name,
                    'verified_by' => null,
                    'approved_by' => null,
                    'inserted_value' => null,
                    'verified_value' => null,
                    'approved_value' => null,
                    'uncertainty_value' => null,
                    'resultable_id' => null,
                    'resultable_type' => null,
                    'inserted_date' => null,
                    'verified_date' => null,
                    'approved_date' => null,
                    'extra_data' => $this->defaultResultExtraData(),
                    'min_ref_value' => $item->pivot->min_ref_value,
                    'max_ref_value' => $item->pivot->max_ref_value,
                    'ref_val_origin' => $item->pivot->ref_val_origin,
                    'sumC' => 0,
                    'volume' => 0,
                    'n1' => 0,
                    'n2' => 0,
                    'dilution' => 0,
                    'd1' => 0,
                    'd2' => 0,
                    'cfu1' => 0,
                    'cfu2' => 0,
                    'calculation_metadata' => null,
                ];
            });
        }

        if ($action == 'verify') {
            $results = $this->issuedResultsFor($counterAnalysis);

            return collect($results)->map(function ($item) {
                $isQualitative = $this->parameterIsQualitative($item->parameter);

                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label,
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product?->name ?? $item->product_label,
                    ],
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter?->name,
                        'result_is_qualitative' => $isQualitative,
                        'result_options' => $this->qualitativeResultOptions($isQualitative),
                        'decimal_places' => $item->parameter?->decimal_places,
                        'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                        'formula_expression' => $item->parameter?->formula_expression,
                        'formula_id' => $item->parameter?->formula_id,
                        'calculation_parameters' => $item->parameter?->calculation_parameters,
                        'result_type' => $item->parameter?->result_type,
                        'active' => $item->parameter?->active,
                        'code' => $item->parameter?->code,
                    ],
                    'formula' => $item->parameter?->formula,
                    'result_is_qualitative' => $isQualitative,
                    'result_options' => $this->qualitativeResultOptions($isQualitative),
                    'display_format' => $this->resultDisplayFormat($item),
                    'decimal_places' => $item->parameter?->decimal_places,
                    'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                    'formula_expression' => $item->parameter?->formula_expression,
                    'formula_id' => $item->parameter?->formula_id,
                    'calculation_parameters' => $item->parameter?->calculation_parameters,
                    'result_type' => $item->parameter?->result_type,
                    'active' => $item->parameter?->active,
                    'parameter_label' => $item->parameter_label,
                    'profile_id' => $item->profile_id,
                    'matrix_id' => $item->matrix_id,
                    'collection_id' => $item->collection_id,
                    'inserted_by_id' => $item->inserted_by_id,
                    'verified_by_id' => auth()->id(),
                    'approved_by_id' => null,
                    'type_id' => [
                        'value' => $item->type_id,
                        'label' => $item->category_label,
                    ],
                    'category_label' => $item->category_label,
                    'nwp_id' => [
                        'value' => $item->nwp_id,
                        'label' => $item->nwp_label,
                    ],
                    'nwp_label' => $item->nwp_label,
                    'unit_id' => [
                        'value' => $item->unit_id,
                        'label' => $item->unit_label,
                    ],
                    'unit_label' => $item->unit_label,
                    'protocol_id' => [
                        'value' => $item->protocol_id,
                        'label' => $item->protocol_label,
                    ],
                    'protocol_label' => $item->protocol_label,
                    'standard_id' => [
                        'value' => $item->standard_id,
                        'label' => $item->standard_label,
                    ],
                    'status' => $item->status,
                    'count' => $item->count,
                    'requested_counter_analysis' => $item->requested_counter_analysis,
                    'inserted_by' => $item->inserted_by,
                    'verified_by' => auth()->user()->name,
                    'approved_by' => null,
                    'inserted_value' => $item->inserted_value,
                    'verified_value' => $item->inserted_value,
                    'approved_value' => null,
                    'uncertainty_value' => $item->uncertainty_value ?? null,
                    'resultable_id' => null,
                    'resultable_type' => null,
                    'inserted_date' => $item->inserted_date,
                    'verified_date' => null,
                    'approved_date' => null,
                    'extra_data' => $this->resultExtraData($item),
                    'min_ref_value' => $item->min_ref_value,
                    'max_ref_value' => $item->max_ref_value,
                    'ref_val_origin' => $item->ref_val_origin,
                    'sumC' => $item->sumC,
                    'volume' => $item->volume,
                    'n1' => $item->n1,
                    'n2' => $item->n2,
                    'dilution' => $item->dilution,
                    'd1' => $item->d1,
                    'd2' => $item->d2,
                    'cfu1' => $item->cfu1,
                    'cfu2' => $item->cfu2,
                    'calculation_metadata' => $item->calculation_metadata,
                ];
            });
        }

        if ($action == 'approve') {
            $results = $this->issuedResultsFor($counterAnalysis);

            return collect($results)->map(function ($item) {
                $isQualitative = $this->parameterIsQualitative($item->parameter);

                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label,
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product?->name ?? $item->product_label,
                    ],
                    'product_label' => $item->product_label,
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter?->name,
                        'result_is_qualitative' => $isQualitative,
                        'result_options' => $this->qualitativeResultOptions($isQualitative),
                        'decimal_places' => $item->parameter?->decimal_places,
                        'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                        'formula_expression' => $item->parameter?->formula_expression,
                        'formula_id' => $item->parameter?->formula_id,
                        'calculation_parameters' => $item->parameter?->calculation_parameters,
                        'result_type' => $item->parameter?->result_type,
                        'active' => $item->parameter?->active,
                        'code' => $item->parameter?->code,
                    ],
                    'formula' => $item->parameter?->formula,
                    'result_is_qualitative' => $isQualitative,
                    'result_options' => $this->qualitativeResultOptions($isQualitative),
                    'display_format' => $this->resultDisplayFormat($item),
                    'decimal_places' => $item->parameter?->decimal_places,
                    'requires_calculation' => $item->parameter?->requires_calculation ?? false,
                    'formula_expression' => $item->parameter?->formula_expression,
                    'formula_id' => $item->parameter?->formula_id,
                    'calculation_parameters' => $item->parameter?->calculation_parameters,
                    'result_type' => $item->parameter?->result_type,
                    'active' => $item->parameter?->active,
                    'parameter_label' => $item->parameter_label,
                    'profile_id' => $item->profile_id,
                    'matrix_id' => $item->matrix_id,
                    'collection_id' => $item->collection_id,
                    'inserted_by_id' => $item->inserted_by_id,
                    'verified_by_id' => $item->verified_by_id,
                    'approved_by_id' => auth()->id(),
                    'type_id' => [
                        'value' => $item->type_id,
                        'label' => $item->category_label,
                    ],
                    'category_label' => $item->category_label,
                    'nwp_id' => [
                        'value' => $item->nwp_id,
                        'label' => $item->nwp_label,
                    ],
                    'nwp_label' => $item->nwp_label,
                    'unit_id' => [
                        'value' => $item->unit_id,
                        'label' => $item->unit_label,
                    ],
                    'protocol_id' => [
                        'value' => $item->protocol_id,
                        'label' => $item->protocol_label,
                    ],
                    'protocol_label' => $item->protocol_label,
                    'standard_id' => [
                        'value' => $item->standard_id,
                        'label' => $item->standard_label,
                    ],
                    'standard_label' => $item->standard_label,
                    'status' => $item->status,
                    'count' => $item->count,
                    'requested_counter_analysis' => $item->requested_counter_analysis,
                    'inserted_by' => $item->inserted_by,
                    'verified_by' => $item->verified_by,
                    'approved_by' => auth()->user()->name,
                    'inserted_value' => $item->inserted_value,
                    'verified_value' => $item->verified_value,
                    'approved_value' => $item->verified_value,
                    'uncertainty_value' => $item->uncertainty_value ?? null,
                    'resultable_id' => null,
                    'resultable_type' => null,
                    'inserted_date' => $item->inserted_date,
                    'verified_date' => $item->verified_date,
                    'approved_date' => null,
                    'extra_data' => $this->resultExtraData($item),
                    'min_ref_value' => $item->min_ref_value,
                    'max_ref_value' => $item->max_ref_value,
                    'ref_val_origin' => $item->ref_val_origin,
                    'sumC' => $item->sumC,
                    'volume' => $item->volume,
                    'n1' => $item->n1,
                    'n2' => $item->n2,
                    'dilution' => $item->dilution,
                    'd1' => $item->d1,
                    'd2' => $item->d2,
                    'cfu1' => $item->cfu1,
                    'cfu2' => $item->cfu2,
                    'calculation_metadata' => $item->calculation_metadata,
                ];
            });
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResultRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        $validated = $request->validated();
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $action = (string) $request->action;
        $qualificationGate = app(PersonnelQualificationGate::class);
        $signature = $request->input('signature');
        $sample = app(LaboratoryWorkflowOwnership::class)->samplesForLaboratory($labId)
            ->with('analysis', 'collection.collection.sampleEntry')
            ->findOrFail($validated['sample_id']);
        $analysisId = $sample->analysis?->id;
        $departmentId = $sample->analysis?->department_id;

        if (! in_array($action, ['analyze', 'verify', 'approve'], true)) {
            return back()->withErrors([
                'action' => 'A etapa do fluxo de resultados é inválida.',
            ]);
        }

        if (! $analysisId) {
            return $this->missingWorkflowRedirect('analysis.index', [
                'category' => $this->analysisCategoryForAction($action),
            ], 'A amostra seleccionada ainda não tem uma análise associada.');
        }

        $collectionProduct = $sample->collection?->collection;
        abort_unless($collectionProduct, 404);

        $results = $this->prepareResultsForWorkflow(
            collect($validated['results'] ?? [])->values()->all(),
            $action,
            $this->qualitativeParameterIds($this->issuedScope->parametersFor($sample->analysis, $collectionProduct))
        );
        app(EquipmentMetrologyGate::class)->ensureResultsReady($results, $labId);

        // Persiste data to DB
        if ($action === 'analyze') {

            abort_if(! auth()->user()->can('insert_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'insert_results', $departmentId, $labId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-analyze', [
                'lab_id' => $labId,
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('analysis.index', ['category' => 'insert']);
            }

            dispatch(new InsertAnalysisResults(
                $results,
                $analysisId,
                auth()->user(),
                $labId,
            ));

            return to_route('analysis.index', ['category' => 'insert'])->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_insert'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ],
            ]);

        }

        if ($action === 'verify') {

            abort_if(! auth()->user()->can('verify_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'verify_results', $departmentId, $labId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-verify', [
                'lab_id' => $labId,
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('analysis.index', ['category' => 'verify']);
            }

            dispatch(new VerifyAnalysisResults(
                $results,
                $analysisId,
                auth()->user(),
                $labId,
                $signature,
            ));

            return to_route('analysis.index', ['category' => 'verify'])->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_verify'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ],
            ]);

        }

        if ($action === 'approve') {

            abort_if(! auth()->user()->can('approve_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'approve_results', $departmentId, $labId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-approve', [
                'lab_id' => $labId,
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('analysis.index', ['category' => 'approve']);
            }

            dispatch(new ApproveAnalysisResults(
                $results,
                $analysisId,
                auth()->user(),
                $labId,
                $signature,
            ));

            return to_route('analysis.index', ['category' => 'approve'])->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_approve'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ],
            ]);

        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeCounterAnalysisResults(ResultRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        $validated = $request->validated();
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $action = (string) $request->action;
        $qualificationGate = app(PersonnelQualificationGate::class);
        $signature = $request->input('signature');
        $sample = app(LaboratoryWorkflowOwnership::class)->samplesForLaboratory($labId)
            ->with('counteranalysis', 'collection.collection.sampleEntry')
            ->findOrFail($validated['sample_id']);
        $counterAnalysisId = $sample->counteranalysis?->id;
        $departmentId = $sample->counteranalysis?->department_id;

        if (! in_array($action, ['analyze', 'verify', 'approve'], true)) {
            return back()->withErrors([
                'action' => 'A etapa do fluxo de contra-análise é inválida.',
            ]);
        }

        if (! $counterAnalysisId) {
            return $this->missingWorkflowRedirect('counteranalysis.index', [], 'A amostra seleccionada ainda não tem uma contra-análise associada.');
        }

        $collectionProduct = $sample->collection?->collection;
        abort_unless($collectionProduct, 404);

        $results = $this->prepareResultsForWorkflow(
            collect($validated['results'] ?? [])->values()->all(),
            $action,
            $this->qualitativeParameterIds($this->issuedScope->parametersFor($sample->counteranalysis, $collectionProduct))
        );
        app(EquipmentMetrologyGate::class)->ensureResultsReady($results, $labId);

        // Persiste data to DB
        if ($action === 'analyze') {

            abort_if(! auth()->user()->can('insert_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'insert_results', $departmentId, $labId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-results-analyze', [
                'lab_id' => $labId,
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('counteranalysis.index');
            }

            dispatch(new InsertCounterAnalysisResults(
                $results,
                $counterAnalysisId,
                auth()->user(),
                $labId,
            ));

            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_insert'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ],
            ]);

        }

        if ($action === 'verify') {

            abort_if(! auth()->user()->can('verify_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'verify_results', $departmentId, $labId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-results-verify', [
                'lab_id' => $labId,
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('counteranalysis.index');
            }

            dispatch(new VerifyCounterAnalysisResults(
                $results,
                $counterAnalysisId,
                auth()->user(),
                $labId,
                $signature,
            ));

            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_verify'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ],
            ]);

        }

        if ($action === 'approve') {

            abort_if(! auth()->user()->can('approve_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'approve_results', $departmentId, $labId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-results-approve', [
                'lab_id' => $labId,
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('counteranalysis.index');
            }

            dispatch(new ApproveCounterAnalysisResults(
                $results,
                $counterAnalysisId,
                auth()->user(),
                $labId,
                $signature,
            ));

            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_approve'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ],
            ]);

        }

    }

    public function getCode()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('samples')
                ->select('samples.*')
                ->where('code', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getInsert()
    {
        abort_if(! auth()->user()->can('insert_results'), 403, '');

        return to_route('analysis.edit', [
            'category' => 'insert',
            'id' => request()->analysis_id,
        ]);
    }

    /**
     * Store individual result
     */
    public function storeIndividual(ResultRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        $validated = $request->validated();
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $action = $request->input('action', 'analyze');

        $resultData = $validated['results'][0];

        // Get analysis ID from sample
        $sample = app(LaboratoryWorkflowOwnership::class)->samplesForLaboratory($labId)
            ->with('analysis', 'collection.collection.sampleEntry')->findOrFail($validated['sample_id']);
        $analysisId = $sample->analysis?->id;

        if (! $analysisId) {
            return response()->json([
                'success' => false,
                'message' => 'A amostra seleccionada ainda não tem uma análise associada.',
            ], 422);
        }
        $collectionProduct = $sample->collection?->collection;
        abort_unless($collectionProduct, 404);
        $preparedResult = $this->prepareIndividualResultForWorkflow(
            $resultData,
            $action,
            $this->qualitativeParameterIds($this->issuedScope->parametersFor($sample->analysis, $collectionProduct))
        );

        app(EquipmentMetrologyGate::class)->ensureResultsReady([$preparedResult], $labId);

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-individual', [
            'lab_id' => $labId,
            'sample_id' => $validated['sample_id'],
            'action' => $action,
            'parameter_id' => data_get($resultData, 'parameter_id'),
            'result_id' => data_get($resultData, 'result_id'),
            'inserted_value' => data_get($resultData, 'inserted_value'),
            'verified_value' => data_get($resultData, 'verified_value'),
            'approved_value' => data_get($resultData, 'approved_value'),
        ], 60)) {
            return response()->json([
                'success' => false,
                'message' => 'Uma submissão idêntica do resultado já está a ser processada.',
            ], 429);
        }

        // Dispatch appropriate job based on action
        if ($action === 'analyze') {
            abort_if(! auth()->user()->can('insert_results'), 403);
            app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'insert_results', $sample->analysis?->department_id, $labId);

            dispatch(new InsertIndividualResult(
                $preparedResult,
                $analysisId,
                auth()->user(),
                $labId,
            ));

            $message = 'Resultado inserido individualmente com sucesso';
        } elseif ($action === 'verify') {
            abort_if(! auth()->user()->can('verify_results'), 403);
            app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'verify_results', $sample->analysis?->department_id, $labId);

            dispatch(new VerifyIndividualResult(
                $preparedResult,
                $analysisId,
                auth()->user(),
                $labId,
                $request->input('signature'),
            ));

            $message = 'Resultado verificado individualmente com sucesso';
        } elseif ($action === 'approve') {
            abort_if(! auth()->user()->can('approve_results'), 403);
            app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'approve_results', $sample->analysis?->department_id, $labId);

            dispatch(new ApproveIndividualResult(
                $preparedResult,
                $analysisId,
                auth()->user(),
                $labId,
                $request->input('signature'),
            ));

            $message = 'Resultado aprovado individualmente com sucesso';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Acção inválida',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $validated,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @param  array<int, int>  $qualitativeParameterIds
     * @return array<int, array<string, mixed>>
     */
    private function prepareResultsForWorkflow(array $results, string $action, array $qualitativeParameterIds = []): array
    {
        $valueKey = match ($action) {
            'verify' => 'verified_value',
            'approve' => 'approved_value',
            default => 'inserted_value',
        };

        return collect($results)->map(function (array $result) use ($action, $valueKey, $qualitativeParameterIds) {
            $value = data_get($result, $valueKey);
            $min = data_get($result, 'min_ref_value');
            $max = data_get($result, 'max_ref_value');
            $withinLimits = null;
            $isQualitative = $this->resultPayloadIsQualitative($result, $qualitativeParameterIds);

            if (is_numeric($value)) {
                $withinLimits = true;

                if ($min !== null && is_numeric($min) && (float) $value < (float) $min) {
                    $withinLimits = false;
                }

                if ($max !== null && is_numeric($max) && (float) $value > (float) $max) {
                    $withinLimits = false;
                }
            }

            $existingExtra = collect($result['extra_data'] ?? []);

            $result['extra_data'] = $existingExtra->merge([
                'display_format' => $this->displayFormatForPayload($result, $isQualitative),
                'specification_check' => [
                    'action' => $action,
                    'value' => $value,
                    'min_ref_value' => $min,
                    'max_ref_value' => $max,
                    'within_limits' => $withinLimits,
                    'unit_id' => data_get($result, 'unit_id'),
                    'unit_label' => data_get($result, 'unit_label'),
                    'checked_at' => now()->toIso8601String(),
                ],
                'equipment' => [
                    'equipment_id' => data_get($result, 'equipment_id'),
                ],
            ])->all();

            Arr::forget($result, [
                'display_format',
                'result_is_qualitative',
                'result_options',
                'parameter_id.result_options',
                'parameter_id.result_is_qualitative',
                'parameter_id.display_format',
            ]);

            return $result;
        })->all();
    }

    /**
     * @param  array<int, int>  $qualitativeParameterIds
     * @return array<string, mixed>
     */
    private function prepareIndividualResultForWorkflow(array $result, string $action, array $qualitativeParameterIds = []): array
    {
        return $this->prepareResultsForWorkflow([$result], $action, $qualitativeParameterIds)[0];
    }

    private function duplicateRedirectResponse(string $route, array $parameters = [])
    {
        return to_route($route, $parameters)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Uma submissão idêntica deste fluxo já está a ser processada.',
            ],
        ]);
    }

    private function analysisCategoryForAction(string $action): string
    {
        return match ($action) {
            'verify', 'approve' => $action,
            default => 'insert',
        };
    }

    private function missingWorkflowRedirect(string $route, array $parameters, string $message)
    {
        return to_route($route, $parameters)->withErrors([
            'sample_id' => $message,
        ]);
    }
}
