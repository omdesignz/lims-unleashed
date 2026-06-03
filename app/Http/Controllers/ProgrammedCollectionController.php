<?php

namespace App\Http\Controllers;

use App\Exports\CollectionParametersSheetExport;
use App\Http\Requests\ProgrammedCollectionRequest;
use App\Http\Resources\CollectionProductResource;
use App\Jobs\PlaceProductsInAnalysis;
use App\Jobs\ProcessProgrammedCollectionProducts;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\CollectionReason;
use App\Models\Customer;
use App\Models\LabCode;
use App\Models\ProgrammedCollection;
use App\Models\Sample;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\DuplicateSubmissionGuard;
use App\Support\SpreadsheetDownloadResponder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use NumberToWords\NumberToWords;
use PDF;

class ProgrammedCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $category = $this->normalizeCollectionCategory(request()->query('category'));
        $scope = ucfirst($category);

        return Inertia::render('ProgrammedCollections/Index', [
            'record' => CollectionProductResource::collection(
                CollectionProduct::query()->{$scope}()->with('product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'collection', 'quality_certificate', 'sampleEntry')
                    ->whereRelation('collection', 'collectionable_type', 'programmed')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('lot', 'like', "%{$search}%")
                            ->orWhereRelation('code', 'code', 'like', "%{$search}%")
                            ->orWhereRelation('product', 'name', 'like', "%{$search}%")
                            ->orWhereRelation('customer', 'name', 'like', "%{$search}%")
                            ->orWhereRelation('warehouse', 'address', 'like', "%{$search}%");
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
            'entrypoint' => [
                'label' => 'A entrada canónica é Sample Entry',
                'description' => 'Use a receção de amostra para iniciar novos fluxos. A colheita programada fica como planeamento ligado ao código da amostra.',
                'create_sample_url' => route('vap_samples.index', ['collection_type' => 'programmed']),
            ],
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.cl'),
                    'value' => 'cl',
                ],
                [
                    'name' => trans('gestlab.general.labels.analysis.sample_entry'),
                    'value' => 'entry_lineage',
                ],
                [
                    'name' => trans('gestlab.general.labels.status'),
                    'value' => 'tracking_label',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.product'),
                    'value' => 'product',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.collection_date'),
                    'value' => 'collection_date',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.warehouse_id'),
                    'value' => 'warehouse',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.lot'),
                    'value' => 'lot',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.bl'),
                    'value' => 'bl',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.qty'),
                    'value' => 'qty',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.comercial_brand'),
                    'value' => 'comercial_brand',
                ],
                [
                    'name' => trans('gestlab.general.labels.programmed_collections.result_id'),
                    'value' => 'result',
                ],
            ],
            'model' => ProgrammedCollection::MENU_NAME,
            'abilities' => method_exists(ProgrammedCollection::class, 'getAbilities') ? collect(ProgrammedCollection::ABILITIES)->map(function ($item) {
                return $item.'_'.ProgrammedCollection::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ProgrammedCollection::MENU_NAME;
            }),
            'query' => array_merge(request()->only(['search', 'trashed']), ['category' => $category]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_programmed_collections'), 403, '');

        return Inertia::render('ProgrammedCollections/Create', [
            'entrypoint' => [
                'label' => 'Use Sample Entry para novos fluxos',
                'description' => 'Esta página permanece disponível para operações legadas ou correções manuais. Para novos processos programados, comece pela receção da amostra para manter produto, matriz, local, equipa, lab code e análises ligados.',
                'create_sample_url' => route('vap_samples.index', ['collection_type' => 'programmed']),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProgrammedCollectionRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if(! auth()->user()->can('add_programmed_collections'), 403, '');

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

            dispatch(new ProcessProgrammedCollectionProducts(
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
            ],
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Inertia::render('DirectCollections/Show', [
            'record' => CollectionProductResource::make(
                CollectionProduct::query()
                    ->with('product', 'code.results', 'code.samples', 'code.completed_analysis', 'code.pending_analysis', 'code.in_progress_analysis', 'code.latest_inserted_result', 'code.latest_verified_result', 'code.latest_approved_result', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'collection', 'quality_certificate', 'sampleEntry', 'samples.analysis.department')
                    ->whereRelation('collection', 'collectionable_type', 'programmed')
                    ->findOrFail($id)
            ),
            'collectionPresentation' => [
                'type' => 'programmed',
                'title' => 'Colheita programada',
                'description' => 'Planeamento de colheita ligado à Sample Entry, lab code e fluxo analítico.',
                'index_url' => route('programmedcollections.index'),
                'edit_url' => route('programmedcollections.edit', ['collection' => $id]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_programmed_collections'), 403, '');

        // Find the record
        $record = CollectionProduct::with('product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'vehicle', 'packaging', 'invoice', 'collection.collectionable', 'collection.collaborations', 'collection.reasons')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ProgrammedCollections/Edit', [

            'record' => [
                'id' => $record->id,
                'code' => $record->code?->code,
                'collection_id' => $record->collection_id,
                'product_id' => [
                    'value' => $record?->product_id,
                    'label' => $record?->product?->name,
                ],
                'temperature_id' => [
                    'value' => $record?->temperature_id,
                    'label' => $record?->temperature?->name,
                ],
                'vehicle_id' => [
                    'value' => $record?->vehicle_id,
                    'label' => $record?->vehicle?->number_plate,
                ],
                'customer_id' => [
                    'value' => $record?->customer_id,
                    'label' => $record?->customer?->name,
                ],
                'warehouse_id' => [
                    'value' => $record?->warehouse_id,
                    'label' => $record?->warehouse?->address,
                ],
                'owner_id' => [
                    'value' => $record?->owner_id,
                    'label' => $record?->owner?->name,
                ],
                'result_id' => [
                    'value' => $record?->result_id,
                    'label' => $record?->end_result?->name,
                ],
                'pack_id' => [
                    'value' => $record?->pack_id,
                    'label' => $record?->packaging?->name,
                ],
                'invoice_id' => [
                    'value' => $record?->invoice_id,
                    'label' => $record?->invoice?->inv_no,
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
                'collection_location' => $record->collection?->collectionable?->collection_location,
                'invoiced' => $record->invoiced,
                'recollection' => $record->recollection,
                'processed' => $record->processed,
                'status' => $record->status,
                'obs' => $record->obs,
                'expiry_date' => $record->expiry_date,
                'collection_date' => $record->collection_date,
                'production_date' => $record->production_date,
                'collaborations' => collect($record->collection?->collaborations)->map(function ($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['name'],
                    ];
                })->toArray(),
                'collectionreasons' => collect($record->collection?->reasons)->map(function ($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['name'],
                    ];
                })->toArray(),
            ],
        ]);
    }

    /**
     * Place an existing resource product in analysis.
     *
     * @return Response
     */
    public function PlaceProductsInAnalysis(Request $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if(! auth()->user()->can('add_analysis'), 403, '');

        $validated = $request->validate([
            'collection_product_id' => ['required', 'exists:collection_product,id'],
        ]);

        $collectionProduct = CollectionProduct::with('code.samples', 'collection')->findOrFail($validated['collection_product_id']);

        if ($collectionProduct->code->samples->count() > 0) {
            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.notification_sample_already_placed_in_analysis'),
                ],
            ]);

        } else {
            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'programmed-collection-place-analysis', $validated, 60)) {
                return redirect()->back()->with([
                    'toast' => [
                        'title' => trans('gestlab.toasts.notification'),
                        'message' => 'Este produto já está a ser colocado em análise.',
                    ],
                ]);
            }

            DB::transaction(function () use ($collectionProduct): void {

                dispatch(new PlaceProductsInAnalysis(
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
                ],
            ]);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProgrammedCollectionRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_programmed_collections'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(CollectionProduct::with('code.analysis', 'collection.collectionable', 'collection.collaborations', 'collection.reasons')->findOrFail($id), function ($record) use ($request) {

                $record->update($request->validated());

                $record->collection?->collectionable?->update([
                    'col_date' => $request->collection_date,
                    'collection_location' => $request->collection_location,
                ]);

                $analysisIds = $record->code?->analysis?->pluck('id')->toArray() ?? [];

                if ($analysisIds !== []) {
                    Analysis::whereIn('id', $analysisIds)->update([
                        'col_date' => $request->collection_date,
                    ]);
                }

                $record->collection?->collaborations()->sync(collect($request->collaborations)->map(function ($item) {
                    return data_get($item, 'collaboration_id', data_get($item, 'value'));
                })->toArray());

                $record->collection?->reasons()->sync(collect($request->collectionreasons)->map(function ($item) {
                    return data_get($item, 'reason_id', data_get($item, 'value'));
                })->toArray());

            });

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        abort_if(! auth()->user()->can('delete_programmed_collections'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (CollectionProduct::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_programmed_collections'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (CollectionProduct::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getProfile()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('profiles')
                ->select('profiles.*')
                ->where('name', 'LIKE', "%$search%")
                ->orWhere('code', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $ntw = new NumberToWords;
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');

        $app_name = app(GeneralSettings::class)->app_name;
        $app_validation_number = app(GeneralSettings::class)->app_agt_validation_number;
        $model = ProgrammedCollection::with('items', 'user', 'customer', 'warehouse')->findOrFail(request()->integer('id'));
        $receiptNumber = $model->rec_no ?: 'programmed-collection-'.$model->id;
        // dd($model);

        $pdf = PDF::loadView('PDFs.receipt', [
            'model' => $model,
            'app_name' => $app_name,
            'app_validation_number' => $app_validation_number,
            'nTrans' => $nTrans,
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 25,
            'margin_footer' => 25,
            'title' => 'Recibo Nº '.$receiptNumber,
            'author' => $model->user?->name ?? auth()->user()?->name,
            'watermark' => 'PAGO',
            'show_watermark' => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'showBarcodeNumbers' => false,
        ]);

        if (request()->q) {
            activity()->log('baixou o Recibo Nº '.$receiptNumber);

            return $pdf->download($receiptNumber.'.pdf');
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Recibo Nº '.$receiptNumber);

            return $pdf->stream($receiptNumber.'.pdf');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function getCollectionTermPDF()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $model = $this->documentCollectionProduct(request()->integer('id'), 'programmed');
        $this->abortIfCollectionDocumentDataIsIncomplete($model);

        $paramIDs = collect();

        foreach ($model->product->matrix->profiles as $profile) {
            foreach ($profile->parameters as $param) {
                $paramIDs->push([
                    'id' => $param->id,
                    'description' => $param->description,
                    'code' => $param->name,
                    'dilutions' => $param->pivot->dilutions,
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
            'title' => 'Termo de Colheita de Amostras '.$model->code->description,
            'author' => auth()->user()->name,
            'watermark' => '',
            'show_watermark' => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'format' => 'A4-L',
            'showBarcodeNumbers' => false,
        ]);

        if (request()->q) {
            activity()->log('baixou a Termo de Colheita de Amostras da colheita '.$model->product->description);

            return $pdf->download($model->code->description.'.pdf');
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou a Termo de Colheita de Amostras da colheita '.$model->product->description);

            return $pdf->stream($model->code->description.'.pdf');
        }
    }

    public function getMultipleParametersToAnalyzePDF()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $validated = request()->validate([
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['integer'],
        ]);

        $recordIds = collect($validated['recordIds'])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $models = Sample::query()->with('collection.collection', 'analysis.profile.parameters', 'analysis.department')->whereHas('analysis', function ($q) use ($recordIds) {
            $q->whereHas('profile', function ($q) use ($recordIds) {
                $q->whereHas('parameters', function ($q) use ($recordIds) {
                    $q->whereIn('parameter_id', $recordIds);
                });
            });
        })->get()->map(function (Sample $item) use ($recordIds) {
            $parameters = $item->analysis?->profile?->parameters
                ->filter(fn ($parameter): bool => $recordIds->contains((int) $parameter->id))
                ->map(function ($param) {
                    $extraData = json_decode($param->pivot->extra_data ?? '[]', true);
                    $param->extra_data = is_array($extraData) ? $extraData : [];

                    return $param;
                })
                ->values() ?? collect();

            if ($parameters->isEmpty()) {
                return null;
            }

            return [
                'code' => $item->collection?->code ?? $item->code ?? 'N/A',
                'department' => $item->analysis?->department?->name ?? 'N/A',
                'parameters' => $parameters,
            ];
        })->filter()->values();

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
     * @return Response
     */
    public function getParametersToAnalyzePDF()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $model = $this->documentCollectionProduct(request()->integer('id'), 'programmed');
        $this->abortIfCollectionDocumentDataIsIncomplete($model);

        $paramIDs = collect();

        foreach ($model->product->matrix->profiles as $profile) {
            foreach ($profile->parameters as $param) {
                $paramIDs->push([
                    'id' => $param->id,
                    'description' => $param->description,
                    'code' => $param->name,
                    'dilutions' => $param->pivot->dilutions,
                ]);
            }
        }

        //    dd($paramIDs);

        $pdf = PDF::loadView('PDFs.parameters_to_analyze', [
            'model' => $model,
            'parameters' => $paramIDs,
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
            'title' => 'Folha de Trabalho '.$model->code->description,
            'author' => auth()->user()->full_name,
            'watermark' => '',
            'show_watermark' => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'format' => 'A3-L',
            'showBarcodeNumbers' => false,
        ]);

        if (request()->q) {
            activity()->log('baixou a Folha de Trabalho da colheita '.$model->product->description);

            return $pdf->download($model->code->description.'.pdf');
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou a Folha de Trabalho da colheita '.$model->product->description);

            return $pdf->stream($model->code->description.'.pdf');
        }
    }

    /**
     * Issue Contract Guide.
     *
     * @param  Request  $request
     * @return Response
     */
    public function IssueContractGuide($id)
    {
        abort_if(! auth()->user()->can('add_contract_guides'), 403, '');

        if (ContractGuide::whereNull('deleted_at')->whereCollectionId(CollectionProduct::find($id)->collection_id)->count() > 0) {

            $guide_id = ContractGuide::whereCollectionId(CollectionProduct::find($id)->collection_id)->first()->id;

            return redirect('/cguides/pdf/'.$guide_id);

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
                    'manufacturer' => '',
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
     * @param  Request  $request
     * @return Response
     */
    public function IssueQuote($id)
    {
        abort_if(! auth()->user()->can('add_quotes'), 403, '');

        if (InvoiceItem::where('colpro_id', $id)->count() > 0) {
            noty('Impossível emitir proforma. A colheita em questão já foi facturada.', 'info');

            return back();
        }

        if (CollectionProduct::whereNull('deleted_at')->find($id)->collection->collectionable->quoted) {
            $quote_id = CollectionProduct::find($id)->collection->collectionable->quote_id;

            return redirect('/quotes/pdf/'.$quote_id);

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
                    'description' => $prod->product->description.' - '.$prod->code->description,
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
                'add_product_button' => false,
            ];

            return view('quote.create', compact('pdata', 'types', 'tax', 'apply_discount'));
        }

    }

    public function getUncollectedProducts(Request $request)
    {
        // Get Customer Collections

        if (Customer::whereNull('deleted_at')->find($request->id)) {
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
                    'product' => $prod->product->description.' - '.$prod->code->description,
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

    public function consult(QCollectionConsultRequest $request)
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        if (Customer::whereNull('deleted_at')->find($request->customer_id)) {
            $collectionIDs = explode(',', $request->products);

            if (count($collectionIDs) > 0) {

                $colpro = CollectionProduct::query()
                    ->with('collection.customer', 'collection.warehouse', 'product.matrix', 'code', 'packaging')
                    ->whereIn('id', $collectionIDs)
                    ->where('processed', 0)
                    ->get();

            } else {

                $collectionIDs = Collection::with('products')
                    ->whereNull('deleted_at')
                    ->where('collectionable_type', 'Programmed')
                    ->whereCustomerId($request->customer_id)
                    ->pluck('id')
                    ->toArray();

                $colpro = CollectionProduct::query()
                    ->with('collection.customer', 'collection.warehouse', 'product.matrix', 'code', 'packaging')
                    ->whereIn('collection_id', $collectionIDs)
                    ->where('processed', 0)
                    ->get();
            }

            $pdf = PDF2::loadView('qcollection.pdf', [
                'model' => $colpro,
                'trans_type' => $request->trans_type,
            ], [], [
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 10,
                'margin_bottom' => 25,
                'margin_header' => 10,
                'margin_footer' => 10,
                'title' => 'Consulta de Colheita',
                'author' => \Auth::user()->full_name,
                'watermark' => '',
                'show_watermark' => false,
                'display_mode' => 'fullpage',
                'watermark_text_alpha' => 0.1,
                'format' => 'A4-L',
                'showBarcodeNumbers' => false,
            ]);

            return $pdf->stream('consulta_colheita.pdf');
        } else {

            $colpro = CollectionProduct::query()
                ->with('collection.customer', 'collection.warehouse', 'product.matrix', 'code', 'packaging')
                ->whereHas('collection', function ($q) {
                    $q->whereNull('deleted_at');
                    $q->where('collectionable_type', 'Programmed');
                })
                ->where('processed', 0)
                ->get();

            $pdf = PDF2::loadView('qcollection.pdf', [
                'model' => $colpro,
                'trans_type' => $request->trans_type,
            ], [], [
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 10,
                'margin_bottom' => 25,
                'margin_header' => 10,
                'margin_footer' => 10,
                'title' => 'Consulta de Colheita',
                'author' => \Auth::user()->full_name,
                'watermark' => '',
                'show_watermark' => false,
                'display_mode' => 'fullpage',
                'watermark_text_alpha' => 0.1,
                'format' => 'A4-L',
                'showBarcodeNumbers' => false,
            ]);

            return $pdf->stream('consulta_colheita.pdf');
        }

    }

    public function getCollectionLabels()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $model = LabCode::with('collection.product', 'samples.analysis.department')->where('collection_id', request()->integer('id'))->firstOrFail();

        return view('PDFs.sample_labels', compact('model'));
    }

    private function normalizeCollectionCategory(mixed $category): string
    {
        return in_array($category, ['pending', 'archived'], true) ? $category : 'pending';
    }

    private function documentCollectionProduct(int $id, string $collectionType): CollectionProduct
    {
        return CollectionProduct::query()
            ->with([
                'collection.reasons',
                'collection.customer',
                'collection.warehouse',
                'product.matrix.profiles.parameters',
                'packaging',
                'code.samples.analysis.department',
            ])
            ->whereRelation('collection', 'collectionable_type', $collectionType)
            ->findOrFail($id);
    }

    private function abortIfCollectionDocumentDataIsIncomplete(CollectionProduct $model): void
    {
        abort_if(
            ! $model->collection
            || ! $model->collection->customer
            || ! $model->collection->warehouse
            || ! $model->product
            || ! $model->product->matrix
            || ! $model->packaging
            || ! $model->code,
            422,
            'A colheita não tem dados suficientes para gerar este documento.'
        );
    }
}
