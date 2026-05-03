<?php

namespace App\Jobs;

use App\Events\AnalysisResultsVerified;
use App\Models\Result;
use App\Models\User;
use App\Models\Analysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyIndividualResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $resultData;
    public $analysis_id;
    public $user;
    public $signature;

    /**
     * Create a new job instance.
     */
    public function __construct($resultData, $analysis_id, $user, $signature = null)
    {
        $this->resultData = $resultData;
        $this->analysis_id = $analysis_id;
        $this->user = $user;
        $this->signature = $signature;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $analysis = Analysis::with('sample.collection.collection')->find($this->analysis_id);
        
        // Find and update the result
        $result = Result::with('code')->find($this->resultData['result_id']);
        
        if ($result) {
            $result->update($this->resultData);
            
            // Log result verification
            $u = User::find($this->user->id);
            
            activity()
                ->by($u)
                ->performedOn($result)
                ->log('Verificou individualmente o resultado ' .$result->verified_value . ' no parâmetro: ' . $result->parameter_label . ' da CL: ' . $result->code_label);

            if ($this->signature) {
                $result->addMediaFromBase64($this->signature)
                    ->usingFileName('verification-signature-' . $result->id . '.png')
                    ->toMediaCollection('verification_signature');
            } elseif ($u?->getFirstMedia('signature')) {
                $result->copyMedia($u->getFirstMedia('signature'))
                    ->toMediaCollection('verification_signature');
            }
        }
        
        // Notify User
        broadcast(new AnalysisResultsVerified($this->user, $analysis->sample->collection));
    }
}
