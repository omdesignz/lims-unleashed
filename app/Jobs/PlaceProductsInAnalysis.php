<?php

namespace App\Jobs;

use App\Events\CollectionProcessed;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\LabCode;
use App\Models\Profile;
use App\Models\ProgrammedCollection;
use App\Models\Sample;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PlaceProductsInAnalysis implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $programmed_collection_id;
    public $collection_product_id;
    public $user;
    public $customer;
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($programmed_collection_id, $collection_product_id, $user, $customer)
    {
        $this->programmed_collection_id = $programmed_collection_id;
        $this->collection_product_id = $collection_product_id;
        $this->user = $user;
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update Placed Analysis Value   
        $obj = ProgrammedCollection::with('collection.products')->find($this->programmed_collection_id);
        $obj->placed_analysis = true;
        $obj->status = true;
        $obj->entry_date = now()->format('Y-m-d');
        $obj->save();
        
        
        $colpro = CollectionProduct::find($this->collection_product_id);
        $colpro->processed = true;
        $colpro->save();

        foreach($colpro->product->matrix->profiles as $d) {

            $sample = Sample::create([
                'description' => '',
                'sample_month' => now()->format('y/m'),
                'cl_id' => $colpro->code->id,
            ]);
            
            $analysis = Analysis::create([
                'department_id' => Profile::findOrFail($d->id)->type->department_id,
                'sample_id' => $sample->id,
                'profile_id' => $d->id,
                'col_date' => $obj->col_date,
                'entry_date' => now()->format('Y-m-d'),
                'type_id' => $d->category_id,
                'cl_id' => $colpro->code->id,
                'product_id' => $colpro->product_id ?? null,
            ]);

            $analysis->codeable()->save(LabCode::findOrFail($colpro->code->id));

            // Log analysis creation
            activity()
            ->by($this->user)
            ->performedOn($colpro)
            ->log('colocou em analise a colheita programada CL ' . $colpro->code->description);
        }

        // Notify User
        broadcast(new CollectionProcessed($this->user,$this->customer));
    }

    public function uniqueId(): string
    {
        return 'place-programmed-collection-product:' . $this->collection_product_id;
    }
}
