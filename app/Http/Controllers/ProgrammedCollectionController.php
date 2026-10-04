<?php

namespace App\Http\Controllers;

use App\Actions\SetCollectionAccessionArchived;
use App\Actions\UpdateCollectionAccession;
use App\Exports\CollectionParametersSheetExport;
use App\Http\Requests\ProgrammedCollectionRequest;
use App\Http\Resources\CollectionAccessionResource;
use App\Http\Resources\CollectionProductResource;
use App\Jobs\PlaceProductsInAnalysis;
use App\Models\CollectionProduct;
use App\Models\CollectionReason;
use App\Models\ProgrammedCollection;
use App\Models\Sample;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use App\Support\DuplicateSubmissionGuard;
use App\Support\SpreadsheetDownloadResponder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PDF;

class ProgrammedCollectionController extends Controller
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly SampleLaboratoryAccess $laboratory,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): InertiaResponse
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $category = $this->normalizeCollectionCategory(request()->query('category'));
        $scope = ucfirst($category);

        return Inertia::render('ProgrammedCollections/Index', [
            'record' => CollectionProductResource::collection(
                $this->ownedCollectionProducts(request()->input('filter') === 'trashed')->{$scope}()->with(['owner', 'product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'collection.collectionable', 'quality_certificate', 'code.samples', 'code.completed_analysis', 'code.pending_analysis', 'code.in_progress_analysis', 'sampleEntry' => fn (HasOne $entry): HasOne => $entry->withTrashed()])
                    ->when(request()->input('search'), function (Builder $query, string $search): void {
                        $query->where(function (Builder $matches) use ($search): void {
                            $matches->where('lot', 'like', "%{$search}%")
                                ->orWhereRelation('code', 'code', 'like', "%{$search}%")
                                ->orWhereRelation('product', 'name', 'like', "%{$search}%")
                                ->orWhereRelation('customer', 'name', 'like', "%{$search}%")
                                ->orWhereRelation('warehouse', 'address', 'like', "%{$search}%");
                        });
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
                'label' => 'A entrada canónica é a entrada de amostra',
                'description' => 'Use a recepção de amostra para iniciar novos fluxos. A colheita programada fica como planeamento ligado ao código da amostra.',
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
            'query' => array_merge(request()->only(['search', 'filter', 'trashed']), ['category' => $category]),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): InertiaResponse
    {
        abort_unless(auth()->user()->can('view_programmed_collections'), 403);

        return Inertia::render('DirectCollections/Show', [
            'record' => CollectionProductResource::make(
                $this->ownedCollectionProducts()
                    ->with('product', 'code.results', 'code.samples', 'code.completed_analysis', 'code.pending_analysis', 'code.in_progress_analysis', 'code.latest_inserted_result', 'code.latest_verified_result', 'code.latest_approved_result', 'end_result', 'customer', 'warehouse', 'temperature', 'packaging', 'vehicle', 'owner', 'collection.collectionable', 'quality_certificate', 'sampleEntry', 'samples.analysis.department')
                    ->findOrFail($id)
            ),
            'collectionPresentation' => [
                'type' => 'programmed',
                'title' => 'Colheita programada',
                'description' => 'Planeamento de colheita ligado à entrada de amostra, ao código laboratorial e ao fluxo analítico.',
                'index_url' => route('programmedcollections.index'),
                'edit_url' => route('programmedcollections.edit', ['collection' => $id]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): InertiaResponse
    {
        abort_unless(auth()->user()->can('edit_programmed_collections'), 403);

        $record = $this->ownedCollectionProducts()
            ->with('product', 'code', 'end_result', 'customer', 'warehouse', 'temperature', 'vehicle', 'packaging', 'owner', 'sampleEntry', 'collection.collectionable', 'collection.collaborations', 'collection.reasons')
            ->findOrFail($id);

        return Inertia::render('ProgrammedCollections/Edit', [
            'record' => CollectionAccessionResource::make($record),
            'ownerOptions' => $this->ownership->eligibleUsers($this->laboratory->activeLabId())
                ->orderBy('name')->get(['id', 'name'])->map(fn ($user): array => [
                    'value' => $user->id, 'label' => $user->name,
                ]),
        ]);
    }

    /**
     * Place an existing resource product in analysis.
     *
     * @return Response
     */
    public function placeProductsInAnalysis(
        Request $request,
        CollectionProduct $collectionProduct,
        DuplicateSubmissionGuard $duplicateSubmissionGuard,
        SampleLaboratoryAccess $laboratoryAccess,
        LaboratoryWorkflowOwnership $ownership,
    ) {
        abort_if(! auth()->user()->can('add_analysis'), 403, '');

        $labId = $laboratoryAccess->activeLabId();
        $collectionProduct = $ownership->collectionAccessionsForLaboratory($labId, 'programmed')->findOrFail($collectionProduct->id);
        $collectionProduct->load('code.samples', 'collection');
        abort_unless($collectionProduct->collection?->collectionable_type === 'programmed', 404);

        $submissionIdentity = [
            'collection_product_id' => $collectionProduct->id,
        ];

        if ($collectionProduct->code?->samples->isNotEmpty()) {
            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.notification_sample_already_placed_in_analysis'),
                ],
            ]);

        } else {
            if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'programmed-collection-place-analysis', $submissionIdentity, 60)) {
                return redirect()->back()->with([
                    'toast' => [
                        'title' => trans('gestlab.toasts.notification'),
                        'message' => 'Este produto já está a ser colocado em análise.',
                    ],
                ]);
            }

            DB::transaction(function () use ($collectionProduct, $request, $labId): void {

                dispatch(new PlaceProductsInAnalysis(
                    $collectionProduct->collection->collectionable_id,
                    $collectionProduct->id,
                    (int) $request->user()->id,
                    $labId,
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
    public function update(ProgrammedCollectionRequest $request, int $id, UpdateCollectionAccession $updateAccession): RedirectResponse
    {
        $updateAccession->execute($this->laboratory->activeLabId(), $id, (int) $request->user()->id, 'programmed', $request->validated());

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
    public function destroy(Request $request, SetCollectionAccessionArchived $archive): RedirectResponse
    {
        $validated = $request->validate([
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $archive->execute($this->laboratory->activeLabId(), (int) $request->user()->id, 'programmed', array_map('intval', $validated['recordIds']), archived: true);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'A colheita e a entrada de amostra foram arquivadas. Os dados analíticos foram preservados.',
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore(Request $request, SetCollectionAccessionArchived $archive): RedirectResponse
    {
        $validated = $request->validate([
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $archive->execute($this->laboratory->activeLabId(), (int) $request->user()->id, 'programmed', array_map('intval', $validated['recordIds']), archived: false);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'A colheita e a entrada de amostra foram restauradas.',
            ],
        ]);
    }

    public function getCollectionTermPDF()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $model = $this->documentCollectionProduct(request()->integer('id'));
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
            'format' => 'A4',
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

        $models = $this->ownership->samplesForLaboratory($this->laboratory->activeLabId())
            ->whereHas('collection', fn (Builder $code): Builder => $code->whereIn('collection_id', $this->ownedCollectionProducts()->select('collection_product.id')))
            ->with('collection.collection', 'analysis.profile.parameters', 'analysis.department')->whereHas('analysis', function ($q) use ($recordIds) {
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

    public function exportParametersToAnalyzeSheet(
        Request $request,
        LaboratoryWorkflowOwnership $ownership,
        SampleLaboratoryAccess $laboratory
    ) {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $validated = $request->validate([
            'recordIds' => ['required', 'array', 'min:1'],
            'recordIds.*' => ['integer'],
        ]);

        $recordIds = array_values(array_unique($validated['recordIds']));
        $records = $ownership->collectionAccessionsForLaboratory($laboratory->activeLabId(), 'programmed')
            ->with([
                'collection.collectionable',
                'customer',
                'warehouse',
                'product.matrix.profiles.parameters',
                'code',
                'quality_certificate',
            ])
            ->whereIn('collection_product.id', $recordIds)
            ->get();

        abort_if($records->count() !== count($recordIds), 404, '');

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

        $model = $this->documentCollectionProduct(request()->integer('id'));
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
    public function getCollectionLabels()
    {
        abort_if(! auth()->user()->can('view_programmed_collections'), 403, '');

        $collectionProduct = $this->ownedCollectionProducts()->findOrFail(request()->integer('id'));
        $model = $this->ownership->labCodesForLaboratory($this->laboratory->activeLabId())
            ->with('collection.product', 'samples.analysis.department')
            ->where('codeable_type', 'analysis')->where('collection_id', $collectionProduct->id)->firstOrFail();

        return view('PDFs.sample_labels', compact('model'));
    }

    /** @return Builder<CollectionProduct> */
    private function ownedCollectionProducts(bool $includeArchivedEntries = false): Builder
    {
        return $this->ownership->collectionAccessionsForLaboratory($this->laboratory->activeLabId(), 'programmed', $includeArchivedEntries);
    }

    private function normalizeCollectionCategory(mixed $category): string
    {
        return in_array($category, ['pending', 'archived'], true) ? $category : 'pending';
    }

    private function documentCollectionProduct(int $id): CollectionProduct
    {
        return $this->ownedCollectionProducts()
            ->with([
                'collection.reasons',
                'collection.customer',
                'collection.warehouse',
                'product.matrix.profiles.parameters',
                'packaging',
                'code.samples.analysis.department',
            ])
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
