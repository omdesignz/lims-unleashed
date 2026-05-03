<?php

namespace App\Jobs;

use App\Events\AnalysisResultsVerified;
use App\Models\Result;
use App\Models\User;
use App\Models\Analysis;
use App\Support\LaboratoryWorkflowNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyAnalysisResults implements ShouldQueue
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
        $analysis = Analysis::with('sample.collection.collection')->find($this->analysis_id);

        // Verify The Results
        $lastResult = null;
        foreach ($this->results as $result) {
            
            $obj = Result::with('code')->find($result['result_id']);
            $lastResult = $obj;

            $obj->update($result);

            // Log result verification
            $u = User::find($this->user->id);

            activity()
                ->by($u)
                ->performedOn($obj)
                ->log('Verificou o resultado ' .$obj->verified_value . ' no parâmetro: ' . $obj->parameter_label . ' da CL: ' . $obj->code_label);

            if ($this->signature) {
                $obj->addMediaFromBase64($this->signature)
                    ->usingFileName('verification-signature-' . $obj->id . '.png')
                    ->toMediaCollection('verification_signature');
            } elseif ($u?->getFirstMedia('signature')) {
                $obj->copyMedia($u->getFirstMedia('signature'))
                    ->toMediaCollection('verification_signature');
            }
        }

        // Notify User
        broadcast(new AnalysisResultsVerified($this->user, $analysis->sample->collection));

        if ($lastResult) {
            $workflowNotifier->notifyResultsVerified($lastResult->fresh(['sample.collection.collection.warehouse']), User::find($this->user->id));
        }
    }
}
