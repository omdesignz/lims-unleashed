<?php

namespace App\Actions;

use App\Models\CounterAnalysis;
use App\Models\LabCode;
use App\Models\Sample;
use App\Models\User;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use App\Support\LaboratoryWorkflowNotifier;
use Illuminate\Support\Facades\DB;

class RequestLaboratoryCounterAnalysis
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryWorkflowMutationAccess $access,
        private readonly LaboratoryWorkflowNotifier $notifier,
    ) {}

    public function execute(int $labId, int $resultId, int $userId): CounterAnalysis
    {
        return DB::transaction(function () use ($labId, $resultId, $userId): CounterAnalysis {
            $operator = $this->access->operator($userId, $labId, 'add_counter_analysis');
            $result = $this->ownership->resultsForLaboratory($labId)
                ->with('sample.collection')->lockForUpdate()->findOrFail($resultId);
            $analysis = $this->ownership->analysesForLaboratory($labId)
                ->where('sample_id', $result->sample_id)->lockForUpdate()->firstOrFail();
            $existing = $result->counter_analysis()->withTrashed()->first();

            if ($existing) {
                $this->ownership->counterAnalysesForLaboratory($labId)->withTrashed()->findOrFail($existing->id);

                if (! $existing->trashed() && ! $existing->end_date && ! $existing->status) {
                    $result->update(['requested_counter_analysis' => true]);
                }

                return $existing;
            }

            $code = LabCode::query()->create([
                'code' => '',
                'codeable_type' => 'counteranalysis',
                'cl_month' => now()->format('y/m'),
                'collection_id' => $result->sample->collection->collection_id,
            ]);
            $sample = Sample::query()->create([
                'code' => '', 'sample_month' => now()->format('y/m'), 'cl_id' => $code->id,
            ]);
            $counterAnalysis = CounterAnalysis::query()->create([
                'department_id' => $analysis->department_id,
                'user_id' => $operator->id,
                'analysis_id' => $analysis->id,
                'sample_id' => $sample->id,
                'profile_id' => $analysis->profile_id,
                'col_date' => $analysis->col_date,
                'entry_date' => now()->format('Y-m-d'),
                'type_id' => $analysis->type_id,
                'parameter_id' => $result->parameter_id,
                'result_id' => $result->id,
                'cl_id' => $code->id,
                'extra_data' => ['source_sample_id' => $result->sample_id],
            ]);
            $counterAnalysis->codeable()->save($code);
            $result->update(['requested_counter_analysis' => true]);

            DB::afterCommit(function () use ($resultId, $userId, $labId): void {
                $result = $this->ownership->resultsForLaboratory($labId)->find($resultId);
                $operator = User::query()->find($userId);

                if ($result && $operator) {
                    $this->notifier->notifyCounterAnalysisRequested($result, $operator);
                }
            });

            return $counterAnalysis;
        });
    }
}
