<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Inertia\Inertia;

class ProductController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_products'), 403, '');

        return Inertia::render('Products/Index', [
            'record' => ProductResource::collection(
                Product::query()
                            ->with('matrix')
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function ($query, $filter) {
                                if ($filter === 'trashed') {
                                    $query->withTrashed();
                                }
                            })
                            ->latest()
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.products.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.matrix_id'),
                    'value' => 'matrix'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.charge_tax'),
                    'value' => 'charge_tax'
                ],
                [
                    'name' => trans('gestlab.general.labels.products.exemption_id'),
                    'value' => 'exemption'
                ],
            ],
            'model' => Product::MENU_NAME,
            'abilities' => method_exists(Product::class, 'getAbilities') ? collect(Product::ABILITIES)->map(function($item){
                return $item . '_' . Product::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Product::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_products'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Products/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ProductRequest $request)
    {
        abort_if( !auth()->user()->can('add_products'), 403, '');

        // Persiste data to DB
        Product::create($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);


    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_products'), 403, '');

        // Find the record
        $record = Product::with('matrix', 'exemption', 'tax_category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Products/Edit', [
            'record' => ProductResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ProductRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_products'), 403, '');

        // Find the record
        $record = Product::findOrFail($id);

        $record->update($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        abort_if( !auth()->user()->can('delete_products'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Product::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if( !auth()->user()->can('restore_products'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Product::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    // public function getProduct() {
    //     $data = [];

    //     if(request()->has('q')){
    //         $search = request()->q;
            
    //         $data = DB::table("products")
    //             ->select('products.*')
    //             ->where('name','LIKE',"%$search%")
    //             ->orWhere('description','LIKE',"%$search%")
    //             ->get();
    //     }

    //     return response()->json($data);
    // }

    public function getProduct() 
    {
        // Subquery: Calculate total price per matrix (sum of active parameters across all profiles)
        $matrixPrices = DB::table('matrix_profile')
            ->select('matrix_profile.matrix_id')
            ->selectRaw('SUM(parameters.price) as calculated_matrix_price')
            ->join('profiles', function($join) {
                $join->on('matrix_profile.profile_id', '=', 'profiles.id')
                    ->whereNull('profiles.deleted_at');
            })
            ->join('parameter_profile', function($join) {
                $join->on('profiles.id', '=', 'parameter_profile.profile_id')
                    ->whereNull('parameter_profile.deleted_at');
            })
            ->join('parameters', function($join) {
                $join->on('parameter_profile.parameter_id', '=', 'parameters.id')
                    ->whereNull('parameters.deleted_at')
                    ->where('parameters.active', 1);
            })
            ->whereNull('matrix_profile.deleted_at')
            ->groupBy('matrix_profile.matrix_id');

        // Main query: Explicitly select columns to avoid confusion
        $query = DB::table('products')
            ->select(
                'products.id',
                'products.name', 
                'products.description',
                'products.exemption_code',
                'products.withhold_tax',
                'products.charge_tax',
                'products.exemption_id',
                'products.tax_id',
                'products.matrix_id',
                'products.tax_percentage',
                'products.fixed_price',     // Product's own fixed price
                'products.price',           // Product's own price
                'products.created_at',
                'products.updated_at'
            )
            ->selectRaw('COALESCE(matrix_prices.calculated_matrix_price, 0.00) as matrix_parameters_price')
            ->leftJoinSub($matrixPrices, 'matrix_prices', function($join) {
                $join->on('products.matrix_id', '=', 'matrix_prices.matrix_id');
            })
            ->whereNull('products.deleted_at');

        if(request()->has('q')) {
            $search = request()->q;
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'LIKE', "%{$search}%")
                ->orWhere('products.description', 'LIKE', "%{$search}%");
            });
        }

        return response()->json($query->get());
    }


}
