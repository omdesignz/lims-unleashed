<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LabCode;
use App\Models\Parameter;
use App\Models\Profile;
use App\Models\CollectionProduct;
use App\Http\Resources\LabCodeResource;
use App\Models\Matrix;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class LabCodeController extends Controller
{
    public function getCode() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("lab_codes")
                ->select('lab_codes.*')
                ->where('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getCodeParameters() {
        $data = [];

        if(request()->has('code_id') && !request()->boolean('use_matrix_price')){

            $parameterIDs = Matrix::parameters(LabCode::with('collection.product')->findOrFail(request()->code_id)?->collection?->product?->matrix_id);

            $data = collect(Parameter::whereIn('id', $parameterIDs)->get())->map(function($item) {
                return [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $item->exemption_id ?? null,
                    'exemption_code' => $item->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $item->id,
                        'label' => $item->name,
                        'price'=> $item->price,
                        'tax_id'=> $item->tax_id,
                        'charge_tax'=> $item->charge_tax,
                        'tax_percentage'=> $item->tax_percentage,
                        'exemption_id'=> $item->exemption_id,
                        'exemption_code'=> $item->exemption_code,
                        'withhold_tax'=> $item->withhold_tax,
                    ],
                    'item_description' => $item->code,
                    'invoice_id' => null,
                    'itemable_id' => null,
                    'itemable_type' => null,
                    'qty' => 1,
                    'unit_price' => $item->price,
                    'tax_id' => $item->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $item->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $item->charge_tax,
                    'withhold_tax' => $item->withhold_tax,

                ];
            });
        }

        if(request()->has('code_id') && request()->boolean('use_matrix_price')){

            $matrix = Matrix::findOrFail(LabCode::findOrFail(request()->code_id)?->collection?->product?->matrix_id);

            $data = collect([

                [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $matrix->exemption_id ?? null,
                    'exemption_code' => $matrix->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $matrix->id,
                        'label' => $matrix->description,
                        'price'=> $matrix->fixed_price,
                        'tax_id'=> $matrix->tax_id,
                        'charge_tax'=> $matrix->charge_tax,
                        'tax_percentage'=> $matrix->tax_percentage,
                        'exemption_id'=> $matrix->exemption_id,
                        'exemption_code'=> $matrix->exemption_code,
                        'withhold_tax'=> $matrix->withhold_tax,
                    ],
                    'item_description' => $matrix->code,
                    'invoice_id' => null,
                    'itemable_id' => null,
                    'itemable_type' => null,
                    'qty' => 1,
                    'unit_price' => $matrix->price,
                    'tax_id' => $matrix->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $matrix->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $matrix->charge_tax,
                    'withhold_tax' => $matrix->withhold_tax,
    
                ]
                
            ]);
        }

        return response()->json($data);
    }


    public function getCodeProducts() {
        $data = [];

        if(request()->has('code_id') && !request()->boolean('use_matrix_price')){

            $productID = LabCode::with('collection.product.matrix')->findOrFail(request()->code_id)?->collection?->product_id;

            $product = Product::findOrfail($productID);

            $data = collect([
                [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $product->exemption_id ?? null,
                    'exemption_code' => $product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $product->id,
                        'label' => $product->name,
                        'price'=> $product?->matrix?->fixed_price,
                        'tax_id'=> $product->tax_id,
                        'charge_tax'=> $product->charge_tax,
                        'tax_percentage'=> $product->tax_percentage,
                        'exemption_id'=> $product->exemption_id,
                        'exemption_code'=> $product->exemption_code,
                        'withhold_tax'=> $product->withhold_tax,
                    ],
                    'item_description' => $product->name,
                    'invoice_id' => null,
                    'itemable_id' => null,
                    'itemable_type' => null,
                    'qty' => 1,
                    'unit_price' => $product?->matrix?->fixed_price,
                    'tax_id' => $product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $product->charge_tax,
                    'withhold_tax' => $product->withhold_tax,

                ]
            ]);
        }

        if(request()->has('code_id') && request()->boolean('use_matrix_price')){

            $productID = LabCode::with('collection.product.matrix')->findOrFail(request()->code_id)?->collection?->product_id;

            $product = Product::findOrfail($productID);

            $data = collect([

                [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $product->exemption_id ?? null,
                    'exemption_code' => $product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $product->id,
                        'label' => $product->name,
                        'price'=> $product?->matrix?->price,
                        'tax_id'=> $product->tax_id,
                        'charge_tax'=> $product->charge_tax,
                        'tax_percentage'=> $product->tax_percentage,
                        'exemption_id'=> $product->exemption_id,
                        'exemption_code'=> $product->exemption_code,
                        'withhold_tax'=> $product->withhold_tax,
                    ],
                    'item_description' => $product->name,
                    'invoice_id' => null,
                    'itemable_id' => null,
                    'itemable_type' => null,
                    'qty' => 1,
                    'unit_price' => $product?->matrix?->price,
                    'tax_id' => $product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $product->charge_tax,
                    'withhold_tax' => $product->withhold_tax,
    
                ]
                
            ]);
        }

        return response()->json($data);
    }

    public function getWarehouseUninvoicedProducts() {
        $data = [];

        if(request()->has('warehouse_id') && !request()->boolean('use_matrix_price')){

            $data = collect(CollectionProduct::with('product.matrix')->where('warehouse_id', request()->warehouse_id)->where('invoiced', false)->get())->map(function($item) {
                return [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $item->product->exemption_id ?? null,
                    'exemption_code' => $item->product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $item->product->id,
                        'label' => $item->product->name,
                        'price'=> $item->product?->matrix?->fixed_price,
                        'tax_id'=> $item->product->tax_id,
                        'charge_tax'=> $item->product->charge_tax,
                        'tax_percentage'=> $item->product->tax_percentage,
                        'exemption_id'=> $item->product->exemption_id,
                        'exemption_code'=> $item->product->exemption_code,
                        'withhold_tax'=> $item->product->withhold_tax,
                    ],
                    'item_description' => $item->product->name,
                    'invoice_id' => null,
                    'itemable_id' => $item->id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $item->product?->matrix?->fixed_price,
                    'tax_id' => $item->product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $item->product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $item->product->charge_tax,
                    'withhold_tax' => $item->product->withhold_tax,
                ];
            })->toArray();

        }

        if(request()->has('warehouse_id') && request()->boolean('use_matrix_price')){

            $data = collect(CollectionProduct::with('product.matrix')->where('warehouse_id', request()->warehouse_id)->where('invoiced', false)->get())->map(function($item) {
                return [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $item->product->exemption_id ?? null,
                    'exemption_code' => $item->product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $item->product->id,
                        'label' => $item->product->name,
                        'price'=> $item->product?->matrix?->price,
                        'tax_id'=> $item->product->tax_id,
                        'charge_tax'=> $item->product->charge_tax,
                        'tax_percentage'=> $item->product->tax_percentage,
                        'exemption_id'=> $item->product->exemption_id,
                        'exemption_code'=> $item->product->exemption_code,
                        'withhold_tax'=> $item->product->withhold_tax,
                    ],
                    'item_description' => $item->product->name,
                    'invoice_id' => null,
                    'itemable_id' => $item->id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $item->product?->matrix?->price,
                    'tax_id' => $item->product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $item->product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $item->product->charge_tax,
                    'withhold_tax' => $item->product->withhold_tax,
                ];
            })->toArray();

        }

        return response()->json($data);
    }

    public function getSampleStatus()
    {
        $code = LabCode::with('results', 'latest_approved_result', 'collection', 'analysis.profile.parameters', 'analysis.department', 'analysis.results')->where('collection_id', request()->id)->first();

        $analysis = [];

        $analysis = collect($code->analysis)->map(function($item) use ($code) {
            return [
                'id' => $item->id ?? null,
                'profile' => $item->profile->name ?? null,
                'parameter_count' => $item->profile->parameters()->count() ?? 0,
                'total_params' => $item->profile->parameters()->count() ?? 0,
                'completed_params' => $item?->results->whereNotNull('approved_date')->count() ?? 0,
                'progress' => $item?->results->whereNotNull('approved_date')->count() > 0 ? ($item?->results->count() / $item?->results->whereNotNull('approved_date')->count() * 100) : 0,
                'status' => is_null($item->init_date) ? 'pending' : (!is_null($item->init_date) && is_null($item->end_date) ? 'in_progress' : 'completed'),
                'updated_at' => $item->updated_at,
                'department' => $item->department->name,
            ];
        })->toArray();

        return response()->json($analysis);
    }
}
