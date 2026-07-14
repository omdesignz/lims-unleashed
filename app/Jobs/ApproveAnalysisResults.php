<?php

namespace App\Jobs;

use App\Events\AnalysisResultsApproved;
use App\Events\AnalysisResultsValidated;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\User;
use App\Support\LaboratoryWorkflowNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApproveAnalysisResults implements ShouldQueue
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
        $analysis = Analysis::with('sample.collection.collection')->findOrFail($this->analysis_id);
        $user = User::findOrFail($this->user->id);

        $lastResult = null;
        foreach ($this->results as $result) {
            $obj = Result::with('code')
                ->whereBelongsTo($analysis->sample)
                ->findOrFail($result['result_id']);
            $lastResult = $obj;

            $obj->update($result);

            activity()
                ->by($user)
                ->performedOn($obj)
                ->log('Validou o resultado '.$obj->approved_value.' no parâmetro: '.$obj->parameter_label.' da CL: '.$obj->code->code);

            if ($this->signature) {
                $obj->addMediaFromBase64($this->signature)
                    ->usingFileName('approval-signature-'.$obj->id.'.png')
                    ->toMediaCollection('approval_signature');
            } elseif (($signatureMedia = $user->getFirstMedia('signature')) && is_file($signatureMedia->getPath())) {
                $obj->copyMedia($signatureMedia->getPath())
                    ->toMediaCollection('approval_signature');
            }
        }

        $analysis->update([
            'end_date' => now(),
            'status' => true,
        ]);

        $collectionProduct = CollectionProduct::with(['code.analysis', 'sampleEntry'])
            ->findOrFail($analysis->sample->collection->collection_id);
        $analyses = $collectionProduct->code?->analysis ?? collect();

        if ($analyses->isNotEmpty() && $analyses->every(fn (Analysis $item): bool => filled($item->end_date))) {
            $collectionProduct->update([
                'status' => true,
                'processed' => true,
                'analysis_end_date' => now()->format('Y-m-d'),
                'sample_status' => 'Concluída',
            ]);

            $collectionProduct->sampleEntry?->forceFill([
                'analysis_end_date' => now(),
                'status' => 'COMPLETADO',
            ])->save();

            $result = Result::with('sample.analysis.profile', 'counter_analysis', 'sample.collection')
                ->findOrFail($this->results[0]['result_id']);

            if (! QualityCertificate::whereCollectionId($collectionProduct->id)->exists()) {
                event(new AnalysisResultsValidated($result, $user->id));
            }
        }

        broadcast(new AnalysisResultsApproved($this->user, $analysis->sample->collection));

        if ($lastResult) {
            $workflowNotifier->notifyResultsApproved($lastResult->fresh(['sample.collection.collection.warehouse', 'inserted_by', 'verified_by', 'approved_by']), $user);
        }
    }
}
