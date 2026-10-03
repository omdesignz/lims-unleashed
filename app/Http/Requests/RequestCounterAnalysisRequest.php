<?php

namespace App\Http\Requests;

use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestCounterAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('add_counter_analysis') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $ownership = app(LaboratoryWorkflowOwnership::class);

        return [
            'result_id' => ['required', 'integer', Rule::exists('results', 'id')
                ->where(fn (Builder $query): Builder => $query->whereIn('results.id',
                    $ownership->resultsForLaboratory($labId)->whereIn('results.sample_id',
                        $ownership->analysesForLaboratory($labId)->select('analysis.sample_id'))->select('results.id')))],
        ];
    }
}
