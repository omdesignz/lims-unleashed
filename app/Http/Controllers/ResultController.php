<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ResultRequest;
use App\Http\Resources\ResultResource;
use App\Jobs\ApproveAnalysisResults;
use App\Jobs\ApproveCounterAnalysisResults;
use App\Jobs\ApproveIndividualResult;
use App\Jobs\InsertAnalysisResults;
use App\Jobs\InsertCounterAnalysisResults;
use App\Jobs\InsertIndividualResult;
use App\Jobs\VerifyAnalysisResults;
use App\Jobs\VerifyCounterAnalysisResults;
use App\Jobs\VerifyIndividualResult;
use Illuminate\Support\Facades\DB;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Support\DuplicateSubmissionGuard;
use App\Support\EquipmentMetrologyGate;
use App\Support\PersonnelQualificationGate;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ResultController extends Controller
{

    public function getDefaultResultsData()
    {
        abort_if( !auth()->user()->can('view_results'), 403, '');

        // dd(Sample::with('analysis.profile.parameters.pivot.protocol', 'collection.collection.product', 'results')->findOrFail(request()->sample_id));

        if(request()->action == 'analyze') {
            $sample = Sample::with(
                'analysis.profile.parameters.formula', 
                'analysis.profile.parameters.pivot.protocol',
                'analysis.profile.parameters.pivot.nwp',
                'analysis.profile.parameters.pivot.standard',
                'analysis.profile.parameters.pivot.category',
                'analysis.profile.parameters.pivot.formula',
                'analysis.profile.parameters.pivot.unit',
                'collection.collection.product', 
                'results'
                )->findOrFail(request()->sample_id);

            return collect($sample->analysis->profile->parameters)->map(function($item) use ($sample) {
                return [
                    'sample_id' => request()->sample_id,
                    'code_id' => [
                        'value' => $sample->cl_id,
                        'label' => $sample->collection->code
                    ],
                    'code_label' => $sample->collection->code,
                    'product_id' => [
                        'value' => $sample->collection->collection->product_id,
                        'label' => $sample->collection->collection->product->name
                    ],
                    'parameter_id' => [
                        'value' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'result_is_qualitative' => $item->pivot->result_is_qualitative,
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
                        'label' => $item->pivot->category->name,
                    ],
                    'category_label' => $item->pivot->category->name,
                    'nwp_id' => [
                        'value' => $item->pivot->nwp_id,
                        'label' => $item->pivot->nwp->code,
                    ],
                    'nwp_label' => $item->pivot->nwp->code,
                    'unit_id' => [
                        'value' => $item->pivot->unit_id,
                        'label' => $item->pivot->unit->code,
                    ],
                    'unit_label' => $item->pivot->unit->code,
                    'protocol_id' => [
                        'value' => $item->pivot->protocol_id,
                        'label' => $item->pivot->protocol?->code,
                    ],
                    'protocol_label' => $item->pivot->protocol?->code,
                    'standard_id' => [
                        'value' => $item->pivot->standard_id,
                        'label' => $item->pivot->standard?->code,
                    ],
                    'standard_label' => $item->pivot->standard?->code,
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
                    'extra_data' => null,
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


        if(request()->action == 'verify') {
            $results = Result::with(
                'parameter.profiles',
                'sample.collection.collection.recollection', 
                'sample.analysis.profile', 
                'sample.collection.collection.product', 
                'category'
                )->where('sample_id', '=', request()->sample_id)->get();

            return collect($results)->map(function($item) {
                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product->name
                    ],
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter->name,
                        'result_is_qualitative' => $item->result_is_qualitative,
                        'decimal_places' => $item->parameter->decimal_places,
                        'requires_calculation' => $item->parameter->requires_calculation,
                        'formula_expression' => $item->parameter->formula_expression,
                        'formula_id' => $item->parameter->formula_id,
                        'calculation_parameters' => $item->parameter->calculation_parameters,
                        'result_type' => $item->parameter->result_type,
                        'active' => $item->parameter->active,
                        'code' => $item->parameter->code,
                    ],
                    'formula' => $item->parameter?->formula,

                    // Added    
                    'decimal_places' => $item->parameter->decimal_places,
                    'requires_calculation' => $item->parameter->requires_calculation,
                    'formula_expression' => $item->parameter->formula_expression,
                    'formula_id' => $item->parameter->formula_id,
                    'calculation_parameters' => $item->parameter->calculation_parameters,
                    'result_type' => $item->parameter->result_type,
                    'active' => $item->parameter->active,
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
                    'extra_data' => null,
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


        if(request()->action == 'approve') {
            $results = Result::with(
                'parameter.profiles',
                'sample.collection.collection.recollection', 
                'sample.analysis.profile', 
                'sample.collection.collection.product', 
                'category'
                )->where('sample_id', '=', request()->sample_id)->get();

            return collect($results)->map(function($item) {
                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product->name
                    ],
                    'product_label' => $item->product_label,
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter->name,
                        'result_is_qualitative' => $item->result_is_qualitative,
                        'decimal_places' => $item->decimal_places,
                        'requires_calculation' => $item->requires_calculation ?? false,
                        'formula_expression' => $item->formula_expression,
                        'formula_id' => $item->formula_id,
                        'calculation_parameters' => $item->calculation_parameters,
                        'result_type' => $item->result_type,
                        'active' => $item->parameter->active,
                        'code' => $item->parameter->code,
                    ],
                    'formula' => $item->formula,

                    // Added    
                    'decimal_places' => $item->decimal_places,
                    'requires_calculation' => $item->requires_calculation ?? false,
                    'formula_expression' => $item->formula_expression,
                    'formula_id' => $item->formula_id,
                    'calculation_parameters' => $item->parameter->calculation_parameters,
                    'result_type' => $item->result_type,
                    'active' => $item->parameter->active,
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
                    'extra_data' => null,
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
        abort_if( !auth()->user()->can('view_results'), 403, '');

        if(request()->action == 'analyze') {
            $sample = Sample::with(
                'counteranalysis.profile.parameters.formula',
                'counteranalysis.profile.parameters.pivot.protocol',
                'counteranalysis.profile.parameters.pivot.nwp',
                'counteranalysis.profile.parameters.pivot.standard',
                'counteranalysis.profile.parameters.pivot.category',
                'counteranalysis.profile.parameters.pivot.formula',
                'counteranalysis.profile.parameters.pivot.unit',
                'collection.collection.product', 
                'results'
                )->findOrFail(request()->sample_id);

            return collect($sample->counteranalysis->profile->parameters)->map(function($item) use ($sample) {
                return [
                    'sample_id' => request()->sample_id, 
                    'code_id' => [
                        'value' => $sample->cl_id,
                        'label' => $sample->collection->code
                    ],
                    'code_label' => $sample->collection->code,
                    'product_id' => [
                        'value' => $sample->collection->collection->product_id,
                        'label' => $sample->collection->collection->product->name
                    ],
                    'parameter_id' => [
                        'value' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'result_is_qualitative' => $item->pivot->result_is_qualitative,
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
                        'label' => $item->pivot->category->name,
                    ],
                    'category_label' => $item->pivot->category->name,
                    'nwp_id' => [
                        'value' => $item->pivot->nwp_id,
                        'label' => $item->pivot->nwp->code,
                    ],
                    'nwp_label' => $item->pivot->nwp->code,
                    'unit_id' => [
                        'value' => $item->pivot->unit_id,
                        'label' => $item->pivot->unit->code,
                    ],
                    'unit_label' => $item->pivot->unit->code,
                    'protocol_id' => [
                        'value' => $item->pivot->protocol_id,
                        'label' => $item->pivot->protocol->code,
                    ],
                    'protocol_label' => $item->pivot->protocol->code,
                    'standard_id' => [
                        'value' => $item->pivot->standard_id,
                        'label' => $item->pivot->standard->code,
                    ],
                    'standard_label' => $item->pivot->standard->code,
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
                    'extra_data' => null,
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


        if(request()->action == 'verify') {
            $results = Result::with(
                'parameter.profiles',
                'sample.collection.collection.recollection', 
                'sample.counteranalysis.profile', 
                'sample.collection.collection.product', 
                'category'
                )->where('sample_id', '=', request()->sample_id)->get();

            return collect($results)->map(function($item) {
                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product->name
                    ],
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter?->name,
                        'result_is_qualitative' => $item->result_is_qualitative,
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
                    'extra_data' => null,
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


        if(request()->action == 'approve') {
            $results = Result::with(
                'parameter.profiles',
                'sample.collection.collection.recollection', 
                'sample.counteranalysis.profile', 
                'sample.collection.collection.product', 
                'category'
                )->where('sample_id', '=', request()->sample_id)->get();

            return collect($results)->map(function($item) {
                return [
                    'result_id' => $item->id,
                    'sample_id' => $item->sample_id,
                    'code_id' => [
                        'value' => $item->code_id,
                        'label' => $item->code_label
                    ],
                    'code_label' => $item->code_label,
                    'product_id' => [
                        'value' => $item->product_id,
                        'label' => $item->product->name
                    ],
                    'product_label' => $item->product_label,
                    'parameter_id' => [
                        'value' => $item->parameter_id,
                        'label' => $item->parameter_label,
                        'name' => $item->parameter?->name,
                        'result_is_qualitative' => $item->result_is_qualitative,
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
                    'extra_data' => null,
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
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        // Get any required data

        // Load form

        return Inertia::render('Analysis/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ResultRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        $validated = $request->validated();
        $qualificationGate = app(PersonnelQualificationGate::class);
        $results = $this->prepareResultsForWorkflow(collect($validated['results'] ?? [])->values()->all(), (string) $request->action);
        app(EquipmentMetrologyGate::class)->ensureResultsReady($results);
        $signature = $request->input('signature');
        $departmentId = Sample::query()
            ->whereKey($validated['sample_id'])
            ->with('analysis')
            ->first()?->analysis?->department_id;

        // Persiste data to DB
        if ($request->action == 'analyze') {

            abort_if( !auth()->user()->can('insert_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'insert_results', $departmentId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-analyze', [
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('analysis.index', ['category' => 'insert']);
            }

            dispatch( new InsertAnalysisResults(
                $results,
                Sample::with('analysis.profile.parameters', 'collection.collection')->find($validated['sample_id'])->analysis()->first()->id,
                User::find(auth()->user()->id),
            ));
            
            return to_route('analysis.index', ['category' => 'insert'])->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_insert'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ]
            ]);
            
        };


        if ($request->action == 'verify') {

            abort_if( !auth()->user()->can('verify_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'verify_results', $departmentId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-verify', [
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('analysis.index', ['category' => 'verify']);
            }

            dispatch( new VerifyAnalysisResults(
                $results,
                Sample::with('analysis.profile.parameters', 'collection.collection')->find($validated['sample_id'])->analysis()->first()->id,
                User::find(auth()->user()->id),
                $signature,
            ));

            return to_route('analysis.index', ['category' => 'verify'])->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_verify'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ]
            ]);
            
        };


        if ($request->action == 'approve') {

            abort_if( !auth()->user()->can('approve_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'approve_results', $departmentId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-approve', [
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('analysis.index', ['category' => 'approve']);
            }

            dispatch( new ApproveAnalysisResults(
                $results,
                Sample::with('analysis.profile.parameters', 'collection.collection')->find($validated['sample_id'])->analysis()->first()->id,
                User::find(auth()->user()->id),
                $signature,
            ));

            return to_route('analysis.index', ['category' => 'approve'])->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_approve'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ]
            ]);
            
        };


    }


    /**
     * Store a newly created resource in storage.
     *
     */
    public function storeCounterAnalysisResults(ResultRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        $validated = $request->validated();
        $qualificationGate = app(PersonnelQualificationGate::class);
        $results = $this->prepareResultsForWorkflow(collect($validated['results'] ?? [])->values()->all(), (string) $request->action);
        app(EquipmentMetrologyGate::class)->ensureResultsReady($results);
        $signature = $request->input('signature');
        $departmentId = Sample::query()
            ->whereKey($validated['sample_id'])
            ->with('counteranalysis')
            ->first()?->counteranalysis?->department_id;

        // Persiste data to DB
        if ($request->action == 'analyze') {

            abort_if( !auth()->user()->can('insert_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'insert_results', $departmentId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-results-analyze', [
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('counteranalysis.index');
            }

            dispatch( new InsertCounterAnalysisResults(
                $results,
                Sample::with('counteranalysis.profile.parameters', 'collection.collection')->find($validated['sample_id'])->counteranalysis()->first()->id,
                User::find(auth()->user()->id),
            ));
            
            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_insert'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ]
            ]);
            
        };


        if ($request->action == 'verify') {

            abort_if( !auth()->user()->can('verify_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'verify_results', $departmentId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-results-verify', [
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('counteranalysis.index');
            }

            dispatch( new VerifyCounterAnalysisResults(
                $results,
                Sample::with('counteranalysis.profile.parameters', 'collection.collection')->find($validated['sample_id'])->counteranalysis()->first()->id,
                User::find(auth()->user()->id),
                $signature,
            ));

            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_verify'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ]
            ]);
            
        };


        if ($request->action == 'approve') {

            abort_if( !auth()->user()->can('approve_results'), 403, '');
            $qualificationGate->ensure(auth()->user(), 'approve_results', $departmentId);

            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-results-approve', [
                'sample_id' => $validated['sample_id'],
                'action' => $request->action,
                'results' => $results,
            ], 60)) {
                return $this->duplicateRedirectResponse('counteranalysis.index');
            }

            dispatch( new ApproveCounterAnalysisResults(
                $results,
                Sample::with('counteranalysis.profile.parameters', 'collection.collection')->find($validated['sample_id'])->counteranalysis()->first()->id,
                User::find(auth()->user()->id),
                $signature,
            ));

            return to_route('counteranalysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification_approve'),
                    'message' => trans('gestlab.toasts.notification_results'),
                ]
            ]);
            
        };


    }


    public function getCode() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("samples")
                ->select('samples.*')
                ->where('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }


    public function getInsert() {
        abort_if( !auth()->user()->can('insert_results'), 403, '');

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
        $action = $request->input('action', 'analyze');
        
        // Validate individual result
        $validator = Validator::make($validated, [
            'sample_id' => 'required|exists:samples,id',
            'parameter_id.value' => 'required',
            'inserted_value' => 'required_if:action,analyze',
            'verified_value' => 'required_if:action,verify',
            'approved_value' => 'required_if:action,approve',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get analysis ID from sample
        $sample = Sample::with('analysis')->findOrFail($validated['sample_id']);
        $analysisId = $sample->analysis->id;
        $preparedResult = $this->prepareIndividualResultForWorkflow($validated, $action);

        app(EquipmentMetrologyGate::class)->ensureResultsReady([$preparedResult]);

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'analysis-results-individual', [
            'sample_id' => $validated['sample_id'],
            'action' => $action,
            'parameter_id' => data_get($validated, 'parameter_id.value'),
            'result_id' => data_get($validated, 'result_id'),
            'inserted_value' => data_get($validated, 'inserted_value'),
            'verified_value' => data_get($validated, 'verified_value'),
            'approved_value' => data_get($validated, 'approved_value'),
        ], 60)) {
            return response()->json([
                'success' => false,
                'message' => 'Uma submissão idêntica do resultado já está a ser processada.',
            ], 429);
        }
        
        // Dispatch appropriate job based on action
        if ($action === 'analyze') {
            abort_if(!auth()->user()->can('insert_results'), 403);
            app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'insert_results', $sample->analysis?->department_id);
            
            dispatch(new InsertIndividualResult(
                $preparedResult,
                $analysisId,
                auth()->user()
            ));
            
            $message = 'Resultado inserido individualmente com sucesso';
        } elseif ($action === 'verify') {
            abort_if(!auth()->user()->can('verify_results'), 403);
            app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'verify_results', $sample->analysis?->department_id);
            
            dispatch(new VerifyIndividualResult(
                $preparedResult,
                $analysisId,
                auth()->user(),
                $request->input('signature'),
            ));
            
            $message = 'Resultado verificado individualmente com sucesso';
        } elseif ($action === 'approve') {
            abort_if(!auth()->user()->can('approve_results'), 403);
            app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'approve_results', $sample->analysis?->department_id);
            
            dispatch(new ApproveIndividualResult(
                $preparedResult,
                $analysisId,
                auth()->user(),
                $request->input('signature'),
            ));
            
            $message = 'Resultado aprovado individualmente com sucesso';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Ação inválida'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $validated
        ]);
    }

    private function prepareResultsForWorkflow(array $results, string $action): array
    {
        $valueKey = match ($action) {
            'verify' => 'verified_value',
            'approve' => 'approved_value',
            default => 'inserted_value',
        };

        return collect($results)->map(function (array $result) use ($action, $valueKey) {
            $value = data_get($result, $valueKey);
            $min = data_get($result, 'min_ref_value');
            $max = data_get($result, 'max_ref_value');
            $withinLimits = null;

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

            return $result;
        })->all();
    }

    private function prepareIndividualResultForWorkflow(array $result, string $action): array
    {
        return $this->prepareResultsForWorkflow([$result], $action)[0];
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

}
