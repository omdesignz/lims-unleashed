<?php

namespace App\Http\Controllers;

use App\Exports\CollectionParametersSheetExport;
use App\Http\Requests\DirectCollectionRequest;
use App\Http\Resources\CollectionProductResource;
use App\Jobs\ProcessDirectCollectionProducts;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\CollectionReason;
use App\Models\Customer;
use App\Models\DirectCollection;
use App\Models\LabCode;
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
use Spatie\QueryBuilder\QueryBuilder;

class DirectCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

        $category = $this->normalizeCollectionCategory(request()->query('category'));
        $scope = ucfirst($category);

        $records = QueryBuilder::for(CollectionProduct::query()->{$scope}()->whereRelation('collection', 'collectionable_type', 'direct'))
            ->with('owner', 'product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'collection', 'quality_certificate', 'sampleEntry')
            ->allowedFilters(CollectionProduct::getAllowedFilters())
            ->allowedSorts(CollectionProduct::getAllowedSorts())
            ->paginate(request()->query('per_page', 10));

        return Inertia::render('DirectCollections/Index', [
            'stats' => CollectionProduct::count() > 0 ? [
                [
                    'name' => trans('gestlab.stats.collections.total_number_collections'),
                    'value' => CollectionProduct::count(),
                    'unit' => null,
                ],
                [
                    'name' => trans('gestlab.stats.collections.total_number_analysed_collections'),
                    'value' => CollectionProduct::whereStatus(1)->count(),
                    'unit' => null,
                ],
                [
                    'name' => trans('gestlab.stats.collections.total_number_pending_collections'),
                    'value' => CollectionProduct::whereStatus(0)->count(),
                    'unit' => null,
                ],
                [
                    'name' => trans('gestlab.stats.collections.execution_percentage'),
                    'value' => CollectionProduct::whereStatus(0)->count() > 0 ? number_format((CollectionProduct::whereStatus(1)->count() / CollectionProduct::whereStatus(0)->count()) * 100, 2) : 0,
                    'unit' => '%',
                ],
            ] : [
                [
                    'name' => trans('gestlab.stats.collections.total_number_collections'),
                    'value' => 0,
                    'unit' => null,
                ],
                [
                    'name' => trans('gestlab.stats.collections.total_number_analysed_collections'),
                    'value' => 0,
                    'unit' => null,
                ],
                [
                    'name' => trans('gestlab.stats.collections.total_number_pending_collections'),
                    'value' => 0,
                    'unit' => null,
                ],
                [
                    'name' => trans('gestlab.stats.collections.execution_percentage'),
                    'value' => 0,
                    'unit' => '%',
                ],
            ],
            'record' => CollectionProductResource::collection($records),
            'initialFilters' => request()->query('filter', ['code.code' => '', 'customer.name' => '', 'product.name' => '', 'bl' => '', 'lot' => '', 'comercial_brand' => '', 'collection_date' => '', 'qty' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => false,
            'entrypoint' => [
                'label' => 'A entrada canónica é Sample Entry',
                'description' => 'Use a receção de amostra para iniciar novos fluxos. A colheita direta fica como etapa operacional ligada ao código da amostra.',
                'create_sample_url' => route('vap_samples.index', ['collection_type' => 'direct']),
            ],
            'trashedFilter' => true,
            'trashedOptions' => CollectionProduct::getTrashedOptions(),
            'fields' => CollectionProduct::getColumns(),
            'model' => DirectCollection::MENU_NAME,
            'abilities' => method_exists(DirectCollection::class, 'getAbilities') ? collect(DirectCollection::ABILITIES)->map(function ($item) {
                return $item.'_'.DirectCollection::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.DirectCollection::MENU_NAME;
            }),
            'query' => array_merge(request()->only(['search', 'trashed']), ['category' => $category]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_direct_collections'), 403, '');

        return Inertia::render('DirectCollections/Create', [
            'entrypoint' => [
                'label' => 'Use Sample Entry para novos fluxos',
                'description' => 'Esta página permanece disponível para operações legadas ou correções manuais. Para novos processos, comece pela receção da amostra para manter produto, matriz, lab code, análises e evidências ligados.',
                'create_sample_url' => route('vap_samples.index', ['collection_type' => 'direct']),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DirectCollectionRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if(! auth()->user()->can('add_direct_collections'), 403, '');

        $validated = $request->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'direct-collection-store', $validated, 60)) {
            return back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'Já existe uma submissão idêntica de colheita directa em processamento.',
                ],
            ]);
        }

        DB::transaction(function () use ($validated): void {

            dispatch(new ProcessDirectCollectionProducts(
                $validated['customer_id'],
                $validated['warehouse_id'],
                $validated['products'],
                $validated['collection_date'] ?? null,
                $validated['collaborations'] ?? [],
                $validated['collectionreasons'] ?? [],
                User::find(auth()->user()->id),
                Customer::find($validated['customer_id'])
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
        // dd(CollectionProduct::with('samples.analysis')->findOrFail($id));

        return Inertia::render('DirectCollections/Show', [
            'record' => CollectionProductResource::make(
                CollectionProduct::query()
                    ->with('product', 'code.results', 'code.samples', 'code.completed_analysis', 'code.pending_analysis', 'code.in_progress_analysis', 'code.latest_inserted_result', 'code.latest_verified_result', 'code.latest_approved_result', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'collection', 'quality_certificate', 'sampleEntry', 'samples.analysis.department')
                    ->whereRelation('collection', 'collectionable_type', 'direct')
                    ->findOrFail($id)
            ),
            'collectionPresentation' => [
                'type' => 'direct',
                'title' => 'Colheita direta',
                'description' => 'Etapa operacional ligada à Sample Entry e ao lab code.',
                'index_url' => route('directcollections.index'),
                'edit_url' => route('directcollections.edit', ['collection' => $id]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_direct_collections'), 403, '');

        // Find the record
        $record = CollectionProduct::with('product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'vehicle', 'packaging', 'invoice', 'collection.collaborations', 'collection.reasons')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('DirectCollections/Edit', [

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
                'origin' => $record->origin,
                'location' => $record->location,
                'lot' => $record->lot,
                'bl' => $record->bl,
                'temperature_value' => $record->temperature_value,
                'container_no' => $record->container_no,
                'qty' => $record->qty,
                'collected_qty' => $record->collected_qty,
                'invoiced' => $record->invoiced,
                'recollection' => $record->recollection,
                'processed' => $record->processed,
                'status' => $record->status,
                'obs' => $record->obs,
                'expiry_date' => $record->expiry_date,
                'production_date' => $record->production_date,
                'collection_date' => $record->collection_date,
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
     * Update the specified resource in storage.
     */
    public function update(DirectCollectionRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_direct_collections'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(CollectionProduct::with('code.analysis', 'collection.collectionable', 'collection.collaborations', 'collection.reasons')->findOrFail($id), function ($record) use ($request) {

                $record->update($request->validated());

                $record->collection?->collectionable?->update([
                    'col_date' => $request->collection_date,
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
        abort_if(! auth()->user()->can('delete_direct_collections'), 403, '');

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
        abort_if(! auth()->user()->can('restore_direct_collections'), 403, '');

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
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

        $ntw = new NumberToWords;
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');

        $app_name = app(GeneralSettings::class)->app_name;
        $app_validation_number = app(GeneralSettings::class)->app_agt_validation_number;
        $model = DirectCollection::with('items', 'user', 'customer', 'warehouse')->findOrFail(request()->integer('id'));
        $receiptNumber = $model->rec_no ?: 'direct-collection-'.$model->id;
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
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

        $model = $this->documentCollectionProduct(request()->integer('id'), 'direct');
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
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

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
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

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
            ->whereRelation('collection', 'collectionable_type', 'direct')
            ->whereIn('id', $validated['recordIds'])
            ->get();

        abort_if($records->isEmpty(), 404, '');

        return SpreadsheetDownloadResponder::download(
            new CollectionParametersSheetExport($records),
            'direct-collection-parameters.xlsx'
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function getParametersToAnalyzePDF2()
    {
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

        $model = $this->documentCollectionProduct(request()->integer('id'), 'direct');
        $this->abortIfCollectionDocumentDataIsIncomplete($model);

        $paramIDs = collect();

        foreach ($model->product->matrix->profiles as $profile) {
            foreach ($profile->parameters as $param) {
                $paramIDs->push([
                    'id' => $param->id,
                    'description' => $param->description,
                    'code' => $param->name,
                    'dilutions' => $param->pivot->dilutions,
                    'new_dilutions' => collect(json_decode($param->pivot->extra_data ?? '[]', true))->map(function ($item) {
                        return collect($item)->values();
                    })->values(),
                ]);
            }
        }

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

    public function getCollectionLabels()
    {
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

        $model = LabCode::with('collection.product', 'samples.analysis.department')->where('collection_id', request()->integer('id'))->firstOrFail();

        return view('PDFs.sample_labels', compact('model'));
    }

    public function getParametersToAnalyzePDF()
    {
        abort_if(! auth()->user()->can('view_direct_collections'), 403, '');

        $model = $this->documentCollectionProduct(request()->integer('id'), 'direct');
        $this->abortIfCollectionDocumentDataIsIncomplete($model);

        $paramIDs = collect();

        foreach ($model->product->matrix->profiles as $profile) {
            foreach ($profile->parameters as $param) {
                // Convert new_dilutions to array and handle null/empty cases
                $newDilutions = json_decode($param->pivot->extra_data ?? '[]', true);

                // Ensure it's always an array
                if (empty($newDilutions)) {
                    $newDilutionsArray = [];
                } else {
                    $newDilutionsArray = collect($newDilutions)
                        ->map(function ($item) {
                            // Ensure each item is an array
                            return is_array($item) ? array_values($item) : [];
                        })
                        ->values()
                        ->toArray(); // Convert Collection to array
                }

                $paramIDs->push([
                    'id' => $param->id,
                    'description' => $param->description,
                    'code' => $param->name,
                    'dilutions' => $param->pivot->dilutions,
                    'new_dilutions' => $newDilutionsArray,
                ]);
            }
        }

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
