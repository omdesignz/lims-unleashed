<?php

namespace App\Jobs;

use App\Events\CollectionProcessed;
use App\Models\ColCollab;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\ColReason;
use App\Models\LabCode;
use App\Models\ProgrammedCollection;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProgrammedCollectionProducts implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customer_id;
    public $warehouse_id;
    public $products;
    public $col_date;
    public $collection_location;
    public $collaborations;
    public $collectionreasons;
    public $user;
    public $customer;
    public $vehicle_reference;
    public int $uniqueFor = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($customer_id, $warehouse_id, $products, $col_date, $collection_location, $collaborations, $collectionreasons, $user, $customer, $vehicle_reference)
    {
        $this->customer_id = $customer_id;
        $this->warehouse_id = $warehouse_id;
        $this->products = $products;
        $this->col_date = $col_date;
        $this->collection_location = $collection_location;
        $this->collaborations = $collaborations;
        $this->collectionreasons = $collectionreasons;
        $this->user = $user;
        $this->customer = $customer;
        $this->vehicle_reference = $vehicle_reference;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $programmedCollection = new ProgrammedCollection();
        $programmedCollection->user_id = $this->user->id;
        $programmedCollection->col_date = $this->col_date;
        $programmedCollection->collection_location = $this->collection_location;
        $programmedCollection->vehicle_reference = $this->vehicle_reference;
        $programmedCollection->save();

        $collection = $programmedCollection->collection()->save(new Collection([
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
        ]));

        foreach($this->products as $product) {
            $collectionProduct = new CollectionProduct;

            $collectionProduct->collection_id = $collection->id;
            $collectionProduct->customer_id = $this->customer_id;
            $collectionProduct->warehouse_id = $this->warehouse_id;
            $collectionProduct->du_no = $product['du_no'];
            $collectionProduct->origin = $product['origin'];
            $collectionProduct->location = $product['location'];
            $collectionProduct->term_no = $product['term_no'];
            $collectionProduct->container_no = $product['container_no'];
            $collectionProduct->product_id = $product['product_id'];
            $collectionProduct->comercial_brand = $product['comercial_brand'];
            $collectionProduct->result_id = $product['result_id'];
            $collectionProduct->pack_id = $product['pack_id'];
            $collectionProduct->owner_id = $product['owner_id'];
            $collectionProduct->temperature_id = $product['temperature_id'];
            $collectionProduct->invoice_id = $product['invoice_id'];
            $collectionProduct->vehicle_id = $product['vehicle_id'];
            $collectionProduct->obs = $product['obs'];
            $collectionProduct->qty = $product['qty'];
            $collectionProduct->lot = $product['lot'];
            $collectionProduct->bl = $product['bl'];
            $collectionProduct->temperature_value = $product['temperature_value'];
            $collectionProduct->status = $product['status'];
            $collectionProduct->expiry_date = $product['expiry_date'];
            $collectionProduct->production_date = $product['production_date'];
            $collectionProduct->collection_date = $product['collection_date'];
            $collectionProduct->recollection = $product['recollection'];
            $collectionProduct->processed = $product['processed'];
            $collectionProduct->collected_by_lab = $product['collected_by_lab'] ?? false;
            $collectionProduct->invoiced = $product['invoiced'];
            $collectionProduct->collected_qty = $product['collected_qty'];
            $collectionProduct->save();

            $colpro = CollectionProduct::find($collectionProduct->id);
        
            //Create Lab Code for each Product

            $code = LabCode::Create([
                'code' => '',
                'codeable_type' => 'analysis',
                'cl_month' => Carbon::now()->format('y/m'),
                'collection_id' => $collectionProduct->id
            ]);

            // Log product creation
            activity()
                ->by($this->user)
                ->performedOn($colpro)
                ->log('cadastrou a colheita programada com a CL ' . $code->description);
        }

        if(count($this->collaborations)) {
            foreach($this->collaborations as $collab) {
                $obj = new ColCollab();

                $obj->collection_id = $collection->id;
                $obj->collaboration_id = $collab['collaboration_id'];

                $obj->save();
            }
        }

        if(count($this->collectionreasons)) {
            foreach($this->collectionreasons as $reason) {
                $obj = new ColReason();

                $obj->collection_id = $collection->id;
                $obj->reason_id = $reason['reason_id'];

                $obj->save();
            }
        }

        // Notify User
        broadcast(new CollectionProcessed($this->user,$this->customer));
    }

    public function uniqueId(): string
    {
        return 'programmed-collection:' . md5(json_encode([
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'col_date' => $this->col_date,
            'vehicle_reference' => $this->vehicle_reference,
            'products' => $this->products,
        ]));
    }
}
