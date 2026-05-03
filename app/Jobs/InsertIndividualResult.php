<?php

namespace App\Jobs;

use App\Events\AnalysisResultsInserted;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Result;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InsertIndividualResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $resultData;
    public $analysis_id;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct($resultData, $analysis_id, $user)
    {
        $this->resultData = $resultData;
        $this->analysis_id = $analysis_id;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add insertion method to result data
        $this->resultData['insertion_method'] = 'individual';
        $this->resultData['calculated_at'] = now();
        
        // Check if result already exists
        $existingResult = Result::where('sample_id', $this->resultData['sample_id'])
            ->where('parameter_id', $this->resultData['parameter_id']['value'])
            ->first();
        
        if ($existingResult) {
            // Update existing result
            $existingResult->update($this->resultData);
            $res = $existingResult;
        } else {
            // Create new result
            $res = Result::create($this->resultData);
        }
        
        // Log activity
        $u = User::find($this->user->id);
        
        activity()
            ->causedBy($u)
            ->performedOn($res)
            ->log('Inseriu individualmente o resultado ' . $res->inserted_value . ' no parâmetro: ' . $res->parameter_label . ' da CL: ' . $res->code_label);

        // Update analysis start date if not set
        $analysis = Analysis::with('sample.collection')->findOrFail($this->analysis_id);
        
        if (is_null($analysis->init_date)) {
            $analysis->update([
                'init_date' => now(),
            ]);
        }
        
        // Update collection product analysis start date if not set
        $cp = CollectionProduct::find($analysis->sample->collection->collection_id);
        
        if(is_null($cp->analysis_start_date)) {
            $cp->update([
                'analysis_start_date' => now()->format('Y-m-d'),
            ]);
        }
        
        // Associate with analysis
        $analysis->result()->save($res);
        
        // Notify user
        broadcast(new AnalysisResultsInserted($this->user, $analysis->sample->collection));    
    }
}