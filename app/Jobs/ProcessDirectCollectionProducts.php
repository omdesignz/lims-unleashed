<?php

namespace App\Jobs;

use App\Events\CollectionProcessed;
use App\Models\Analysis;
use App\Models\ColCollab;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\ColReason;
use App\Models\Department;
use App\Models\DirectCollection;
use App\Models\LabCode;
use App\Models\Profile;
use App\Models\Sample;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDirectCollectionProducts implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customer_id;
    public $warehouse_id;
    public $products;
    public $col_date;
    public $collaborations;
    public $collectionreasons;
    public $user;
    public $customer;
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($customer_id, $warehouse_id, $products, $col_date, $collaborations, $collectionreasons, $user, $customer)
    {
        $this->customer_id = $customer_id;
        $this->warehouse_id = $warehouse_id;
        $this->products = $products;
        $this->col_date = $col_date;
        $this->collaborations = $collaborations;
        $this->collectionreasons = $collectionreasons;
        $this->user = $user;
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
          
        $obj = new DirectCollection();
    
        $obj->description = '';      
        $obj->col_date = $this->col_date;

        $obj->save();  
        
        $subject = DirectCollection::findOrFail($obj->id);

        $col = new Collection([
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
        ]);

        $col = $subject->collection()->save($col);

        foreach($this->products as $product) {
           
            $obj = new CollectionProduct;

            $obj->collection_id = $col->id;
            $obj->customer_id = $this->customer_id;
            $obj->warehouse_id = $this->warehouse_id;
            $obj->du_no = $product['du_no'];
            $obj->origin = $product['origin'];
            $obj->location = $product['location'];
            $obj->term_no = $product['term_no'];
            $obj->container_no = $product['container_no'];
            $obj->product_id = $product['product_id'];
            $obj->comercial_brand = $product['comercial_brand'];
            $obj->result_id = $product['result_id'];
            $obj->pack_id = $product['pack_id'];
            $obj->owner_id = $product['owner_id'];
            $obj->temperature_id = $product['temperature_id'];
            $obj->invoice_id = $product['invoice_id'];
            $obj->vehicle_id = $product['vehicle_id'];
            $obj->obs = $product['obs'];
            $obj->qty = $product['qty'];
            $obj->lot = $product['lot'];
            $obj->bl = $product['bl'];
            $obj->temperature_value = $product['temperature_value'];
            $obj->status = $product['status'];
            $obj->expiry_date = $product['expiry_date'];
            $obj->production_date = $product['production_date'];
            $obj->collection_date = $product['collection_date'];
            $obj->recollection = $product['recollection'];
            $obj->processed = $product['processed'];
            $obj->collected_by_lab = $product['collected_by_lab'] ?? false;   
            $obj->invoiced = $product['invoiced'];   
            $obj->collected_qty = $product['collected_qty'];

            $obj->save();

            $colpro = CollectionProduct::find($obj->id);
        


            //Create Lab Code for each Product

            $code = LabCode::Create([
                'code' => '',
                'codeable_type' => 'analysis',
                'cl_month' => Carbon::now()->format('y/m'),
                'collection_id' => $obj['id']
            ]);

            // Log product creation
            activity()
                ->by($this->user)
                ->performedOn($colpro)
                ->log('cadastrou a colheita directa com a CL ' . $code->description);

            // Initiate Analysis
            foreach($colpro->product->matrix->profiles as $d) {

                $sample = Sample::create([
                    'code' => '',
                    'sample_month' => Carbon::now()->format('y/m'),
                    'cl_id' => $code->id,
                ]);
                

                // $s = Sample::findOrFail($sample->id);
                
                $analysis = Analysis::create([
                    'department_id' => Profile::findOrFail($d->id)->type->department_id,
                    'sample_id' => $sample->id,
                    'profile_id' => $d->id,
                    'col_date' => $this->col_date,
                    'entry_date' => Carbon::now()->format('Y-m-d'),
                    'type_id' => $d->category_id,
                    'cl_id' => $code->id,
                    'product_id' => $product['product_id'] ?? null,
                ]);

                $analysis->codeable()->save($code);

                // Log analysis creation
                activity()
                ->by($this->user)
                ->performedOn($colpro)
                ->log('Colocou em analise a CL ' . $code->description);
            }
        }

        if(count($this->collaborations)) {
            foreach($this->collaborations as $collab) {
                $obj = new ColCollab();

                $obj->collection_id = $col->id;
                $obj->collaboration_id = $collab['collaboration_id'];

                $obj->save();
            }
        }

        if(count($this->collectionreasons)) {
            foreach($this->collectionreasons as $reason) {
                $obj = new ColReason();

                $obj->collection_id = $col->id;
                $obj->reason_id = $reason['reason_id'];

                $obj->save();
            }
        }

        // Notify User
        broadcast(new CollectionProcessed($this->user, $this->customer));
    }

    public function uniqueId(): string
    {
        return 'direct-collection:' . md5(json_encode([
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'col_date' => $this->col_date,
            'products' => $this->products,
        ]));
    }
}
