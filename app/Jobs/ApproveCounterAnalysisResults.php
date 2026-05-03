<?php

namespace App\Jobs;

use App\Events\AnalysisResultsValidated;
use App\Events\CounterAnalysisResultsApproved;
use App\Models\CounterAnalysis;
use App\Models\CollectionProduct;
use App\Models\QualityCertificate;
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

class ApproveCounterAnalysisResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $results;
    public $analysis_id;
    public $user;
    public $signature;

    /**
     * Create a new job instance.
     */
    public function __construct($results, $analysis_id, $user, $signature = null)
    {
        $this->results = $results;
        $this->analysis_id = $analysis_id;
        $this->user = $user;
        $this->signature = $signature;
    }

    /**
     * Execute the job.
     */
    public function handle(LaboratoryWorkflowNotifier $workflowNotifier): void
    {
        $analysis = CounterAnalysis::with('sample.collection.collection')->find($this->analysis_id);

        // Aprove The Results
        $lastResult = null;
        foreach ($this->results as $result) {
            
            $obj = Result::with('code')->find($result['result_id']);
            $lastResult = $obj;
                            
            $obj->update($result);

            // Log result aproval
            $u = User::find($this->user->id);

            activity()
                ->by($u)
                ->performedOn($obj)
                ->log('Validou o resultado ' .$obj->approved_value . ' no parâmetro: ' . $obj->parameter_label . ' da CA: ' . $obj->code->code);

            if ($this->signature) {
                $obj->addMediaFromBase64($this->signature)
                    ->usingFileName('approval-signature-' . $obj->id . '.png')
                    ->toMediaCollection('approval_signature');
            } elseif ($u?->getFirstMedia('signature')) {
                $obj->copyMedia($u->getFirstMedia('signature'))
                    ->toMediaCollection('approval_signature');
            }
        }

        // Update Status of Analysis
        CounterAnalysis::find($this->analysis_id)->update([
            'end_date' => now(),
            'status' => true
        ]);

        Result::where('sample_id', $analysis->analysis->sample_id)->update([
            'requested_counter_analysis' => false
        ]);

        // Notify User
        broadcast(new CounterAnalysisResultsApproved($this->user,$analysis->sample->collection));

        if ($lastResult) {
            $workflowNotifier->notifyResultsApproved($lastResult->fresh(['sample.collection.collection.warehouse', 'inserted_by', 'verified_by', 'approved_by']), User::find($this->user->id));
        }
    }
}
