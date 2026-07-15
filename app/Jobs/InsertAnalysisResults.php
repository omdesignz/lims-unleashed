<?php

namespace App\Jobs;

use App\Events\AnalysisResultsInserted;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Result;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InsertAnalysisResults implements ShouldQueue
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
    public function handle(): void
    {
        // Insert The Results
        foreach ($this->results as $result) {

            $res = Result::create($result);

            $u = User::find($this->user->id);

            activity()
                ->causedBy($u)
                ->performedOn($res)
                ->log('Inseriu o resultado '.$res->inserted_value.' no parâmetro: '.$res->parameter_label.' da CL: '.$res->code_label);

            $analysis = tap(Analysis::with('sample.collection')->findOrFail($this->analysis_id), function ($analysis) {

                $analysis->update([
                    'init_date' => now(),
                ]);

            });

            $analysis->result()->save($res);

            // Find collection product
            $cp = CollectionProduct::find($analysis->sample->collection->collection_id);

            if (is_null($cp->analysis_start_date)) {
                $cp->update([
                    'analysis_start_date' => now()->format('Y-m-d'),
                ]);
            }

            $cp->update(['sample_status' => 'Em análise']);
            $cp->sampleEntry?->forceFill([
                'analysis_start_date' => $cp->sampleEntry->analysis_start_date ?? now(),
                'status' => 'EN_PROGRESO',
            ])->save();

            // Update CollectionProduct Analysis Start Date
            CollectionProduct::find($analysis->code->collection->collection_id)->update([
                'analysis_start_date' => now()->format('Y-m-d'),
            ]);

        }

        if (isset($analysis)) {
            broadcast(new AnalysisResultsInserted($this->user, $analysis->sample->collection));
        }

    }
}
