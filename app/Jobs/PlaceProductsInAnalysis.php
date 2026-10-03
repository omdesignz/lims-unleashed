<?php

namespace App\Jobs;

use App\Actions\PlaceProgrammedCollectionProductInAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PlaceProductsInAnalysis implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $user_id = 0;

    public int $lab_id = 0;

    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $programmed_collection_id,
        public int $collection_product_id,
        int $user_id,
        int $lab_id,
    ) {
        $this->user_id = $user_id;
        $this->lab_id = $lab_id;
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(PlaceProgrammedCollectionProductInAnalysis $action): void
    {
        $action->execute($this->lab_id, $this->collection_product_id, $this->programmed_collection_id, $this->user_id);
    }

    public function uniqueId(): string
    {
        return 'place-programmed-collection-product:'.$this->lab_id.':'.$this->collection_product_id;
    }
}
