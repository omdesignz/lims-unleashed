<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractGuideRequest;
use App\Http\Resources\ContractGuideResource;
use App\Models\ContractGuide;
use App\Models\ContractGuideItem;
use App\Settings\GeneralSettings;
use App\Support\PdfResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use NumberToWords\NumberToWords;
use PDF;

class ContractGuideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_contract_guides'), 403, '');

        return Inertia::render('ContractGuides/Index', [
            'record' => ContractGuideResource::collection(
                ContractGuide::query()
                    ->with('warehouse', 'customer')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('guide_no', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.contract_guides.date'),
                    'value' => 'date',
                ],
                [
                    'name' => trans('gestlab.general.labels.contract_guides.guide_no'),
                    'value' => 'guide_no',
                ],
                [
                    'name' => trans('gestlab.general.labels.contract_guides.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.contract_guides.warehouse_id'),
                    'value' => 'warehouse',
                ],
            ],
            'model' => ContractGuide::MENU_NAME,
            'abilities' => method_exists(ContractGuide::class, 'getAbilities') ? collect(ContractGuide::ABILITIES)->map(function ($item) {
                return $item.'_'.ContractGuide::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ContractGuide::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_contract_guides'), 403, '');

        return Inertia::render('ContractGuides/Create', [

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContractGuideRequest $request)
    {
        abort_if(! auth()->user()->can('add_contract_guides'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first());

        DB::transaction(function () use ($request): void {
            $guide = ContractGuide::create($request->safe()->except(['items']));

            foreach (collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new ContractGuideItem;

                $obj->guide_id = $guide->id;
                $obj->product_id = $item['product_id'];
                $obj->country_id = $item['country_id'];
                $obj->bl = $item['bl'];
                $obj->lot = $item['lot'];
                $obj->manufacturer = $item['manufacturer'];
                $obj->origin = $item['origin'];
                $obj->brand = $item['brand'];
                $obj->obs = $item['obs'];
                $obj->du_no = $item['du_no'];
                $obj->collection_id = $item['collection_id'];
                $obj->date = $item['date'];

                $obj->save();

            }
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_contract_guides'), 403, '');

        // Find the record
        $record = ContractGuide::with('items', 'customer', 'warehouse', 'user')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ContractGuides/Edit', [

            'record' => [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'guide_no' => $record->guide_no,
                'ref_no' => $record->ref_no,
                'entry_point' => $record->entry_point,
                'collection_point' => $record->collection_point,
                'du_no' => $record->du_no,
                'nif' => $record->nif,
                'obs' => $record->obs,
                'contact' => $record->contact,
                'email' => $record->email,
                'bl' => $record->bl,
                'date' => $record->date,
                'collection_id' => [
                    'value' => $record->collection_id,
                    'label' => $record->collection?->description,
                ],
                'customer_id' => [
                    'value' => $record->customer_id,
                    'label' => $record->customer->name,
                ],
                'user_id' => [
                    'value' => $record->user_id,
                    'label' => $record->user->name,
                ],
                'warehouse_id' => [
                    'value' => $record?->warehouse?->id,
                    'label' => $record?->warehouse?->address,
                ],
                'items' => collect($record->items)->map(function ($item) {
                    return [
                        'id' => $item->id ?? null,
                        'guide_id' => $item->guide_id ?? null,
                        'product_id' => [
                            'value' => $item->product_id,
                            'label' => $item->product->name,
                        ],
                        'country_id' => [
                            'value' => $item->country_id,
                            'label' => $item->country->name,
                        ],
                        'bl' => $item->bl ?? null,
                        'lot' => $item->lot ?? null,
                        'manufacturer' => $item->manufacturer,
                        'origin' => $item->origin,
                        'brand' => $item->brand,
                        'obs' => $item->obs,
                        'du_no' => $item->du_no,
                        'date' => $item->date,
                        'collection_id' => $item->collection_id ?? null,

                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContractGuideRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_contract_guides'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(ContractGuide::findOrFail($id), function ($record) use ($request) {

                $record->update($request->validated());

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
        abort_if(! auth()->user()->can('delete_contract_guides'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (ContractGuide::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_contract_guides'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (ContractGuide::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getInvoice()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('contract_guides')
                ->select('contract_guides.*')
                ->where('guide_no', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getContractGuide(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = ContractGuide::query()
            ->select(['id', 'guide_no', 'date'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('guide_no', 'LIKE', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (ContractGuide $guide): array => [
                'id' => $guide->id,
                'value' => $guide->id,
                'label' => $guide->guide_no,
                'guide_no' => $guide->guide_no,
                'date' => $guide->date,
            ]);

        return response()->json($data);
    }

    public function getPDF()
    {

        abort_if(! auth()->user()->can('view_contract_guides'), 403, '');

        $ntw = new NumberToWords;
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');

        $app_name = app(GeneralSettings::class)->app_name;
        $app_validation_number = app(GeneralSettings::class)->app_agt_validation_number;
        // $model = ContractGuide::with('items.exemption', 'items.unit', 'items.itemable', 'customer', 'warehouse', 'invoice_category', 'user')->find(request()->id);
        $model = ContractGuide::with('items.product', 'items.country', 'warehouse', 'customer', 'user')->findOrFail(request()->integer('id'));
        // dd($model);

        $pdf = PDF::loadView('PDFs.contractguide', [
            'model' => $model,
            'settings' => app(GeneralSettings::class),
            'nTrans' => $nTrans,
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 10,
            'margin_footer' => 10,
            'title' => 'Factura Nº '.$model->guide_no,
            'author' => $model->user->name,
            'watermark' => 'PAGO',
            'show_watermark' => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'showBarcodeNumbers' => false,
        ]);

        if (request()->q) {
            activity()->log('baixou o Factura Nº '.$model->guide_no);

            return PdfResponse::download($pdf, $model->guide_no.'.pdf');
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Factura Nº '.$model->guide_no);

            return PdfResponse::inline($pdf, $model->guide_no.'.pdf');
        }
    }
}
