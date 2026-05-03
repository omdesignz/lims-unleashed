<?php

namespace App\Jobs;

use App\Events\CounterAnalysisResultsInserted;
use App\Models\CounterAnalysis;
use App\Models\Result;
use App\Models\User;
use App\Support\LaboratoryWorkflowNotifier;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InsertCounterAnalysisResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $results;
    public $analysis_id;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct($results, $analysis_id, $user)
    {
        $this->results = $results;
        $this->analysis_id = $analysis_id;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(LaboratoryWorkflowNotifier $workflowNotifier): void
    {
        // Insert The Results
        $lastResult = null;
        foreach ($this->results as $result) {

            $res = Result::create($result);
            $lastResult = $res;

            $u = User::find($this->user->id);
            
            activity()
                ->causedBy($u)
                ->performedOn($res)
                ->log('Inseriu o resultado ' . $res->inserted_value . ' no parâmetro: ' . $res->parameter_label . ' da CA: ' . $res->code_label);


            $analysis = tap(CounterAnalysis::with('sample.collection')->findOrFail($this->analysis_id), function($analysis) {

                $analysis->update([
                    'init_date' => now(),
                ]);
    
            });
    
            $analysis->result()->save($res);
    
            // Notify User
            broadcast(new CounterAnalysisResultsInserted($this->user, $analysis->sample->collection));    
        }

        if ($lastResult) {
            $workflowNotifier->notifyResultsInserted($lastResult->fresh(['sample.collection.collection.warehouse']), User::find($this->user->id));
        }

    }
}
