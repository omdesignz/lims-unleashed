<?php

namespace App\Http\Controllers;

use App\Exports\CollectionParametersSheetExport;
use Illuminate\Http\Request;
use App\Http\Requests\ProgrammedCollectionRequest;
use App\Http\Resources\CollectionProductResource;
use App\Http\Resources\ParameterResource;
use App\Http\Resources\ProgrammedCollectionResource;
use App\Jobs\PlaceProductsInAnalysis;
use App\Jobs\ProcessProgrammedCollectionProducts;
use App\Models\Analysis;
use Illuminate\Support\Facades\DB;
use App\Models\ProgrammedCollection;
use App\Models\AnalysisCategory;
use App\Models\CollectionProduct;
use App\Models\CollectionReason;
use App\Models\Customer;
use App\Models\Parameter;
use App\Models\ParameterProgrammedCollection;
use App\Models\User;
use App\Support\DuplicateSubmissionGuard;
use App\Support\SpreadsheetDownloadResponder;
use App\Settings\GeneralSettings;
use Inertia\Inertia;
use NumberToWords\NumberToWords;
use PDF;
use App\Models\LabCode;
use App\Models\Sample;
use Maatwebsite\Excel\Facades\Excel;

class ProgrammedCollectionController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_programmed_collections'), 403, '');

        return Inertia::render('ProgrammedCollections/Index', [
            'record' => CollectionProductResource::collection(
                CollectionProduct::query()->{ucfirst(request()->category ?? 'Pending') }()->with('product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'collection')
                            ->whereRelation('collection', 'collectionable_type', 'programmed')
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('lot', 'like', "%{$search}%")
                                ->orWhereRelation('code', 'code', 'like', "%{$search}%")
                                ->orWhereRelation('product', 'name', 'like', "%{$search}%")
                                ->orWhereRelation('customer', 'name', 'like', "%{$search}%")
                                ->orWhereRelation('warehouse', 'address', 'like', "%{$search}%");;
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter = 'trashed'){
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
                    'name' => trans('gestlab.general.labels.programmed_collections.cl'),
                    'value' => 'cl'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.product'),
                    'value' => 'product'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.collection_date'),
                    'value' => 'collection_date'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.customer_id'),
                    'value' => 'customer'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.warehouse_id'),
                    'value' => 'warehouse'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.lot'),
                    'value' => 'lot'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.bl'),
                    'value' => 'bl'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.qty'),
                    'value' => 'qty'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.comercial_brand'),
                    'value' => 'comercial_brand'
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.result_id'),
                    'value' => 'result'
                ],
            ],
            'model' => ProgrammedCollection::MENU_NAME,
            'abilities' => method_exists(ProgrammedCollection::class, 'getAbilities') ? collect(ProgrammedCollection::ABILITIES)->map(function($item){
                return $item . '_' . ProgrammedCollection::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . ProgrammedCollection::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed', 'category'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_programmed_collections'), 403, '');

        return Inertia::render('ProgrammedCollections/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ProgrammedCollectionRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if( !auth()->user()->can('add_programmed_collections'), 403, '');

        $validated = $request->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'programmed-collection-store', $validated, 60)) {
            return back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'Já existe uma submissão idêntica de colheita programada em processamento.',
                ],
            ]);
        }

        DB::transaction(function () use ($validated): void {

            dispatch( new ProcessProgrammedCollectionProducts(
                $validated['customer_id'],
                $validated['warehouse_id'],
                $validated['products'],
                $validated['collection_date'] ?? null,
                $validated['collection_location'] ?? null,
                $validated['collaborations'] ?? [],
                $validated['collectionreasons'] ?? [],
                User::find(auth()->user()->id),
                Customer::find($validated['customer_id']),
                $validated['vehicle_reference'] ?? null,
            ));

        });

        

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
        abort_if( !auth()->user()->can('edit_programmed_collections'), 403, '');

        // Find the record
        $record = CollectionProduct::with('product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'vehicle', 'packaging', 'invoice')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ProgrammedCollections/Edit', [

            'record' => [
                'id' => $record->id,
                'code' => $record->code->code,
                'collection_id' => $record->collection_id,
                'product_id' => [
                    'value' => $record?->product_id,
                    'label' => $record?->product?->name
                ],
                'temperature_id' => [
                    'value' => $record?->temperature_id,
                    'label' => $record?->temperature?->name
                ],
                'vehicle_id' => [
                    'value' => $record?->vehicle_id,
                    'label' => $record?->vehicle?->number_plate
                ],
                'customer_id' => [
                    'value' => $record?->customer_id,
                    'label' => $record?->customer?->name
                ],
                'warehouse_id' => [
                    'value' => $record?->warehouse_id,
                    'label' => $record?->warehouse?->address
                ],
                'owner_id' => [
                    'value' => $record?->owner_id,
                    'label' => $record?->owner?->name
                ],
                'result_id' => [
                    'value' => $record?->result_id,
                    'label' => $record?->end_result?->name
                ],
                'pack_id' => [
                    'value' => $record?->pack_id,
                    'label' => $record?->packaging?->name
                ],
                'invoice_id' => [
                    'value' => $record?->invoice_id,
                    'label' => $record?->invoice?->inv_no
                ],
                'comercial_brand' => $record->comercial_brand,
                'du_no' => $record->du_no,
                'term_no' => $record->term_no,
                'lot' => $record->lot,
                'bl' => $record->bl,
                'temperature_value' => $record->temperature_value,
                'container_no' => $record->container_no,
                'qty' => $record->qty,
                'collected_qty' => $record->collected_qty,
                'origin' => $record->origin,
                'location' => $record->location,
                'invoiced' => $record->invoiced,
                'recollection' => $record->recollection,
                'processed' => $record->processed,
                'status' => $record->status,
                'obs' => $record->obs,
                'expiry_date' => $record->expiry_date,
                'collection_date' => $record->collection_date,
                'production_date' => $record->production_date,
                'collaborations' => collect($record->collection->collaborations)->map(function($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['name']
                    ];
                })->toArray(),
                'collectionreasons' => collect($record->collection->reasons)->map(function($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['name']
                    ];
                })->toArray(),
            ],
        ]);
    }


     /**
     * Place an existing resource product in analysis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function PlaceProductsInAnalysis(Request $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if( !auth()->user()->can('add_analysis'), 403, '');

        $validated = $request->validate([
            'collection_product_id' => ['required', 'exists:collection_product,id'],
        ]);

        $collectionProduct = CollectionProduct::with('code.samples', 'collection')->findOrFail($validated['collection_product_id']);

        if($collectionProduct->code->samples->count() > 0){
            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' =>  trans('gestlab.toasts.notification_sample_already_placed_in_analysis'),
                ]
            ]);

        }else {
            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'programmed-collection-place-analysis', $validated, 60)) {
                return redirect()->back()->with([
                    'toast' => [
                        'title' => trans('gestlab.toasts.notification'),
                        'message' => 'Este produto já está a ser colocado em análise.',
                    ],
                ]);
            }

            DB::transaction(function () use ($collectionProduct): void {

                dispatch( new PlaceProductsInAnalysis(
                    $collectionProduct->collection->collectionable_id,
                    $collectionProduct->id,
                    User::find(auth()->user()->id),
                    Customer::find($collectionProduct->collection->customer_id)
                ));
    
            });

            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.notification_sample_placed_in_analysis'),
                ]
            ]);
        }
        
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ProgrammedCollectionRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_programmed_collections'), 403, '');

        DB::transaction(function () use ($request, $id): void {



            tap(CollectionProduct::findOrFail($id), function($record) use($request) {

                $record->update($request->validated());

                $record->collection->collectionable->update([
                    'col_date' => $request->collection_date
                ]);

                Analysis::whereIn('id', $record->code->analysis->pluck('id')->toArray())->update([
                    'col_date' => $request->collection_date
                ]);

                $record->collection->collaborations()->sync(collect($request->collaborations)->map(function($item) {
                    return data_get($item, 'collaboration_id', data_get($item, 'value'));
                })->toArray() );

                $record->collection->reasons()->sync(collect($request->collectionreasons)->map(function($item) {
                    return data_get($item, 'reason_id', data_get($item, 'value'));
                })->toArray() );
    
            });

        });

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
        abort_if( !auth()->user()->can('delete_programmed_collections'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (CollectionProduct::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_programmed_collections'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (CollectionProduct::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getProfile() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("profiles")
                ->select('profiles.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF() {
        abort_if( !auth()->user()->can('view_programmed_collections'), 403, '');

        $ntw = new NumberToWords();
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');

        $app_name = app(GeneralSettings::class)->app_name;
        $app_validation_number = app(GeneralSettings::class)->app_agt_validation_number;
        $model = ProgrammedCollection::with('items', 'user', 'customer', 'warehouse')->find(request()->id);
        //dd($model);
        
        $pdf = PDF::loadView('PDFs.receipt', [
            'model' => $model,
            'app_name' => $app_name,
            'app_validation_number' => $app_validation_number,
            'nTrans' => $nTrans
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 25,
            'margin_footer' => 25,
            'title'=> 'Recibo Nº ' . $model->rec_no,
            'author'=> $model->user->name,
            'watermark'            => 'PAGO',
            'show_watermark'       => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'showBarcodeNumbers' => FALSE
        ]);

        if (request()->q) {
                 activity()->log('baixou o Recibo Nº ' . $model->rec_no);

                return $pdf->download($model->rec_no . '.pdf');
        }  if (!request()->q ) {
                activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Recibo Nº ' . $model->rec_no );
                return  $pdf->stream($model->rec_no . '.pdf');
        }
        
    }


     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getCollectionTermPDF()
    {
        abort_if( !auth()->user()->can('view_programmed_collections'), 403, '');
        
       $model = CollectionProduct::with('collection.reasons', 'product', 'packaging', 'customer', 'warehouse')->findOrFail(request()->id);
       $paramIDs = collect();

       foreach($model->product->matrix->profiles as $profile) {
           foreach($profile->parameters as $param) {
               $paramIDs->push([
                   'id' => $param->id,
                   'description' => $param->description,
                   'code' => $param->name,
                   'dilutions' => $param->pivot->dilutions
               ]);
           }
       }

    //    dd($paramIDs);
       
       $pdf = PDF::loadView('PDFs.collection_term', [
           'model' => $model,
           'reasons' => CollectionReason::all(),
       ], [], [
           'margin_left' => 15,
           'margin_right' => 15,
           'margin_top' => 10,
           'margin_bottom' => 25,
           'margin_header' => 10,
           'margin_footer' => 10,
           'title'=> 'Termo de Colheita de Amostras ' . $model->code->description,
           'author'=> auth()->user()->name,
           'watermark'            => '',
           'show_watermark'       => false,
           'display_mode' => 'fullpage',
           'watermark_text_alpha' => 0.1,
           'format' => 'A4-L',
           'showBarcodeNumbers' => FALSE
       ]);

       if (request()->q) {
                activity()->log('baixou a Termo de Colheita de Amostras da colheita ' . $model->product->description);

               return $pdf->download($model->code->description . '.pdf');
       }  if (!request()->q ) {
               activity()
               ->causedBy(auth()->user()->id)
               ->log('visualizou a Termo de Colheita de Amostras da colheita ' . $model->product->description );
               return  $pdf->stream($model->code->description . '.pdf');
       }
    }

    public function getMultipleParametersToAnalyzePDF()
    {
        abort_if( !auth()->user()->can('view_programmed_collections'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);

        $models = collect(Sample::query()->with('collection.collection', 'analysis.profile.parameters', 'analysis.department')->whereHas('analysis', function($q) {
            $q->whereHas('profile', function($q)  {
                $q->whereHas('parameters', function($q)  {
                    $q->whereIn('parameter_id', request()->recordIds);
                });
            });
        })->get());

        return view('PDFs.multiple_sample_analysis', compact('models'));
        
    }

    public function exportParametersToAnalyzeSheet(Request $request)
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $validated = $request->validate([
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['integer'],
        ]);

        $records = CollectionProduct::query()
            ->with([
                'collection.collectionable',
                'customer',
                'warehouse',
                'product.matrix.profiles.parameters',
                'code',
                'quality_certificate',
            ])
            ->whereRelation('collection', 'collectionable_type', 'programmed')
            ->whereIn('id', $validated['recordIds'])
            ->get();

        abort_if($records->isEmpty(), 404, '');

        return SpreadsheetDownloadResponder::download(
            new CollectionParametersSheetExport($records),
            'programmed-collection-parameters.xlsx'
        );
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getParametersToAnalyzePDF()
    {
        abort_if( !auth()->user()->can('view_programmed_collections'), 403, '');
        
       $model = CollectionProduct::findOrFail(request()->id);
       $paramIDs = collect();

       foreach($model->product->matrix->profiles as $profile) {
           foreach($profile->parameters as $param) {
               $paramIDs->push([
                   'id' => $param->id,
                   'description' => $param->description,
                   'code' => $param->name,
                   'dilutions' => $param->pivot->dilutions
               ]);
           }
       }

    //    dd($paramIDs);
       
       $pdf = PDF::loadView('PDFs.parameters_to_analyze', [
           'model' => $model,
           'parameters' => $paramIDs
       ], [], [
           'margin_left' => 15,
           'margin_right' => 15,
           'margin_top' => 10,
           'margin_bottom' => 25,
           'margin_header' => 10,
           'margin_footer' => 10,
           'title'=> 'Folha de Trabalho ' . $model->code->description,
           'author'=> auth()->user()->full_name,
           'watermark'            => '',
           'show_watermark'       => false,
           'display_mode' => 'fullpage',
           'watermark_text_alpha' => 0.1,
           'format' => 'A3-L',
           'showBarcodeNumbers' => FALSE
       ]);

       if (request()->q) {
                activity()->log('baixou a Folha de Trabalho da colheita ' . $model->product->description);

               return $pdf->download($model->code->description . '.pdf');
       }  if (!request()->q ) {
               activity()
               ->causedBy(auth()->user()->id)
               ->log('visualizou a Folha de Trabalho da colheita ' . $model->product->description );
               return  $pdf->stream($model->code->description . '.pdf');
       }
    }


    /**
     * Issue Contract Guide.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function IssueContractGuide($id)
    {
        abort_if( !auth()->user()->can('add_contract_guides'), 403, '');

        if(ContractGuide::whereNull('deleted_at')->whereCollectionId(CollectionProduct::find($id)->collection_id)->count() > 0) {
            
            $guide_id = ContractGuide::whereCollectionId(CollectionProduct::find($id)->collection_id)->first()->id;
            return redirect('/cguides/pdf/' . $guide_id);

        } else {
            $countries = Countries::all()->pluck('translations.por.common');
            $product_ids = CollectionProduct::find($id)->collection->products->pluck('id')->toArray();
            $products = new Collect;

            $data = CollectionProduct::with('collection.customer', 'collection.warehouse', 'product.matrix', 'code')
                                        ->whereIn('id', $product_ids)
                                        ->get();

            foreach ($data as $prod) {
                $products->push([
                    'product_id' => $prod->product_id,
                    'description' => $prod->product->description,
                    'lot' => $prod->lot,
                    'bl' => $prod->bl,
                    'collection_id' => $prod->id,
                    'country_origin' => '',
                    'manufacturer' => ''
                ]);
            }

            $pdata = [
                'customer_id' => $data->first()->collection->customer_id,
                'customer' => $data->first()->collection->customer->company,
                'warehouse_id' => $data->first()->collection->warehouse_id,
                'warehouse' => $data->first()->collection->warehouse->suburb,
                'col_location' => $data->first()->collection->collectionable->col_location,
                'collection_id' => $data->first()->collection_id,
                'products' => $products,
            ];
            
            return view('cguide.create', compact('countries', 'pdata'));
        }
    }


    /**
     * Issue Quote.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function IssueQuote($id)
    {
        abort_if( !auth()->user()->can('add_quotes'), 403, '');

        if(InvoiceItem::where('colpro_id', $id)->count() > 0){
            noty('Impossível emitir proforma. A colheita em questão já foi facturada.', 'info');
            return back();
        }

        if(CollectionProduct::whereNull('deleted_at')->find($id)->collection->collectionable->quoted) {
            $quote_id = CollectionProduct::find($id)->collection->collectionable->quote_id;

            return redirect('/quotes/pdf/' . $quote_id);

        } else {
            $product_ids = CollectionProduct::find($id)->collection->products->pluck('id')->toArray();
            $products = new Collect;
            $types = InvoiceType::all();
            $tax = config('gestlab.agt.iva');
            $apply_discount = auth()->user()->can('apply_discounts');

            $data = CollectionProduct::with('collection.customer', 'collection.warehouse', 'product.matrix', 'code')
                                        ->whereIn('id', $product_ids)
                                        ->where('invoiced', false)
                                        ->get();

            foreach ($data as $prod) {
                $products->push([
                    'product_id' => $prod->product_id,
                    'description' => $prod->product->description . ' - ' . $prod->code->description,
                    'qty' => 1,
                    'charge_tax' => ($prod->product->charge_tax ? true : false),
                    'tax' => ($prod->product->charge_tax ? config('gestlab.agt.iva') : 0),
                    'tax_amount' => 0,
                    'price' => $prod->product->matrix->price,
                    'fixed_price' => $prod->product->matrix->fixed_price,
                    'colpro_id' => $prod->id,
                    'discountAmount' => 0.00,
                    'discount_percentage' => 0.00,
                    'reason_for_not_charging_tax' => '',
                    'tax_name' => '',
                    'collection_id' => $prod->id,
                ]);
            }

            $pdata = [
                'customer_id' => $data->first()->collection->customer_id,
                'customer' => $data->first()->collection->customer->company,
                'warehouse_id' => $data->first()->collection->warehouse_id,
                'warehouse' => $data->first()->collection->warehouse->suburb,
                'collection_id' => $data->first()->collection_id,
                'products' => $products,
                'lot' => $data->first()->lot,
                'bl' => $data->first()->bl,
                'add_product_button' => false
            ];
            
            return view('quote.create', compact('pdata','types','tax','apply_discount'));
        }
        
    }


    public function getUncollectedProducts(Request $request) {
        // Get Customer Collections

        if(Customer::whereNull('deleted_at')->find($request->id)) {
            $products = new Collect;

            $collectionIDs = Collection::with('products')
                                ->whereNull('deleted_at')
                                ->where('collectionable_type', 'Programmed')
                                ->whereCustomerId($request->id)
                                ->pluck('id')
                                ->toArray();
                
            $colpro = CollectionProduct::query()
                            ->whereIn('collection_id', $collectionIDs)
                            ->where('processed', 0)
                            ->get();  
                            
            foreach ($colpro as $prod) {
                $products->push([
                    'id' => $prod->id,
                    'product' => $prod->product->description . ' - ' . $prod->code->description,
                    'lot' => $prod->lot,
                    'bl' => $prod->bl,
                    'comercial_brand' => $prod->comercial_brand,
                    'qty' => $prod->qty,
                    'col_date' => $prod->col_date,
                    
                ]);
            }                 

            return response()->json($products);
        }
       
    }


    public function consult(QCollectionConsultRequest $request) {
        abort_if( !auth()->user()->can('view_programmed_collections'), 403, '');

        if(Customer::whereNull('deleted_at')->find($request->customer_id)) {
            $collectionIDs = explode(',', $request->products);

            if(count($collectionIDs) > 0) {
                
                $colpro = CollectionProduct::query()
                                ->with('collection.customer', 'collection.warehouse','product.matrix', 'code', 'packaging')
                                ->whereIn('id', $collectionIDs)
                                ->where('processed', 0)
                                ->get();  

            }else{

                $collectionIDs = Collection::with('products')
                                ->whereNull('deleted_at')
                                ->where('collectionable_type', 'Programmed')
                                ->whereCustomerId($request->customer_id)
                                ->pluck('id')
                                ->toArray();
                
                $colpro = CollectionProduct::query()
                                ->with('collection.customer', 'collection.warehouse','product.matrix', 'code', 'packaging')
                                ->whereIn('collection_id', $collectionIDs)
                                ->where('processed', 0)
                                ->get();  
            }
                                            
            $pdf = PDF2::loadView('qcollection.pdf', [
                'model' => $colpro,
                'trans_type' => $request->trans_type
            ], [], [
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 10,
                'margin_bottom' => 25,
                'margin_header' => 10,
                'margin_footer' => 10,
                'title'=> 'Consulta de Colheita',
                'author'=> \Auth::user()->full_name,
                'watermark'            => '',
                'show_watermark'       => false,
                'display_mode' => 'fullpage',
                'watermark_text_alpha' => 0.1,
                'format' => 'A4-L',
                'showBarcodeNumbers' => FALSE
            ]);
            return $pdf->stream('consulta_colheita.pdf');
        }else{
                
            $colpro = CollectionProduct::query()
                            ->with('collection.customer', 'collection.warehouse','product.matrix','code', 'packaging')
                            ->whereHas('collection', function($q){
                                $q->whereNull('deleted_at');
                                $q->where('collectionable_type', 'Programmed');
                            })
                            ->where('processed', 0)
                            ->get();
            
            $pdf = PDF2::loadView('qcollection.pdf', [
                'model' => $colpro,
                'trans_type' => $request->trans_type
            ], [], [
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 10,
                'margin_bottom' => 25,
                'margin_header' => 10,
                'margin_footer' => 10,
                'title'=> 'Consulta de Colheita',
                'author'=> \Auth::user()->full_name,
                'watermark'            => '',
                'show_watermark'       => false,
                'display_mode' => 'fullpage',
                'watermark_text_alpha' => 0.1,
                'format' => 'A4-L',
                'showBarcodeNumbers' => FALSE
            ]);
            return $pdf->stream('consulta_colheita.pdf');
        }
       
    }

    public function getCollectionLabels() {
        $model = LabCode::with('collection', 'samples.analysis.department')->where('collection_id', request()->id)->first();
        
        return view('PDFs.sample_labels', compact('model'));
    }
}
