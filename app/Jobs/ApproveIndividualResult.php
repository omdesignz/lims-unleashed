<?php

namespace App\Jobs;

use App\Events\AnalysisResultsApproved;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Result;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApproveIndividualResult implements ShouldQueue
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
            
            // Log result approval
            $u = User::find($this->user->id);
            
            activity()
                ->by($u)
                ->performedOn($result)
                ->log('Aprovou individualmente o resultado ' .$result->approved_value . ' no parâmetro: ' . $result->parameter_label . ' da CL: ' . $result->code->code);

            if ($this->signature) {
                $result->addMediaFromBase64($this->signature)
                    ->usingFileName('approval-signature-' . $result->id . '.png')
                    ->toMediaCollection('approval_signature');
            } elseif ($u?->getFirstMedia('signature')) {
                $result->copyMedia($u->getFirstMedia('signature'))
                    ->toMediaCollection('approval_signature');
            }
        }
        
        // Check if all results are approved
        $allResultsApproved = !Result::where('sample_id', $analysis->sample_id)
            ->whereNull('approved_date')
            ->exists();
        
        if ($allResultsApproved) {
            // Update Status of Analysis
            $analysis->update([
                'end_date' => now(),
                'status' => true
            ]);
            
            // Update collection product status
            $cp = CollectionProduct::find($analysis->sample->collection->collection_id);
            $cp->update([
                'status' => true,
                'processed' => true,
                'analysis_end_date' => now()->format('Y-m-d'),
            ]);
        }
        
        // Notify User
        broadcast(new AnalysisResultsApproved($this->user, $analysis->sample->collection));
    }
}
