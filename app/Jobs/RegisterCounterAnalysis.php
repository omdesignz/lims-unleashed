<?php

namespace App\Jobs;

use App\Models\CounterAnalysis;
use App\Models\LabCode;
use App\Models\Sample;
use App\Models\Result;
use App\Models\User;
use App\Support\LaboratoryWorkflowNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RegisterCounterAnalysis implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $result_id;
    public $user_id;
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($result_id, $user_id)
    {
        $this->result_id = $result_id;
        $this->user_id = $user_id;
    }

    /**
     * Execute the job.
     */
    public function handle(LaboratoryWorkflowNotifier $workflowNotifier): void
    {
        DB::transaction(function () use ($workflowNotifier): void {
            $result = Result::query()
                ->with('code', 'sample.analysis')
                ->lockForUpdate()
                ->findOrFail($this->result_id);

            if ($result->counter_analysis()->exists()) {
                if (! $result->requested_counter_analysis) {
                    $result->update([
                        'requested_counter_analysis' => true,
                    ]);
                }

                return;
            }

            $code = LabCode::create([
                'code' => '',
                'codeable_type' => 'counteranalysis',
                'cl_month' => now()->format('y/m'),
                'collection_id' => $result->code->collection_id,
            ]);

            $sample = Sample::create([
                'code' => '',
                'sample_month' => now()->format('y/m'),
                'cl_id' => $code->id,
            ]);

            $counterAnalysis = CounterAnalysis::create([
                'department_id' => $result->sample->analysis->department_id,
                'user_id' => $this->user_id,
                'analysis_id' => $result->sample->analysis->id,
                'sample_id' => $sample->id,
                'profile_id' => $result->sample->analysis->profile_id,
                'col_date' => $result->sample->analysis->col_date,
                'entry_date' => now()->format('Y-m-d'),
                'type_id' => $result->sample->analysis->type_id,
                'parameter_id' => $result->parameter_id,
                'result_id' => $this->result_id,
                'cl_id' => $code->id,
                'extra_data' => [
                    'source_sample_id' => $result->sample_id,
                ],
            ]);

            $counterAnalysis->codeable()->save($code);

            $result->update([
                'requested_counter_analysis' => true,
            ]);

            $sender = User::query()->find($this->user_id);

            if ($sender) {
                $workflowNotifier->notifyCounterAnalysisRequested($result->fresh(['sample.collection.collection.warehouse']), $sender);
            }
        });
    }

    public function uniqueId(): string
    {
        return 'counter-analysis-result:' . $this->result_id;
    }
}
