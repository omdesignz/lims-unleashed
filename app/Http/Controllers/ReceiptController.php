<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ReceiptRequest;
use App\Http\Resources\DiscountCategoryResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\ReceiptResource;
use App\Models\DiscountCategory;
use Illuminate\Support\Facades\DB;
use App\Models\Receipt;
use App\Models\InvoiceReceipt;
use App\Models\PaymentCategory;
use App\Settings\GeneralSettings;
use Inertia\Inertia;
use NumberToWords\NumberToWords;
use PDF;

class ReceiptController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_receipts'), 403, '');

        return Inertia::render('Receipts/Index', [
            'record' => ReceiptResource::collection(
                Receipt::query()
                            ->with('warehouse', 'customer')
                            ->withCount('activities as revision_count')
                            ->withMax('activities as last_revision_at', 'created_at')
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('rec_no', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter === 'trashed'){
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
                    'name' => trans('gestlab.general.labels.receipts.date'),
                    'value' => 'date'
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.rec_no'),
                    'value' => 'rec_no'
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.customer_id'),
                    'value' => 'customer'
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.warehouse_id'),
                    'value' => 'warehouse'
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.total'),
                    'value' => 'total'
                ],
            ],
            'model' => Receipt::MENU_NAME,
            'abilities' => method_exists(Receipt::class, 'getAbilities') ? collect(Receipt::ABILITIES)->map(function($item){
                return $item . '_' . Receipt::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Receipt::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_receipts'), 403, '');

        return Inertia::render('Receipts/Create', [
            'payment_categories' => collect(PaymentCategory::all())->map(function($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ReceiptRequest $request)
    {
        abort_if( !auth()->user()->can('add_receipts'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first());


        DB::transaction(function () use ($request): void {
            $receipt = Receipt::create($request->safe()->except(['items']));

            $receipt->items()->createMany(collect($request->items)->toArray());
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
        abort_if( !auth()->user()->can('edit_receipts'), 403, '');

        // Find the record
        $record = Receipt::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice_category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Receipts/Edit', [

            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'internal_ref' => $record->internal_ref,
                'inv_no' => $record->inv_no,
                'obs' => $record->obs,
                'status' => $record->status,
                'use_matrix_price' => $record->use_matrix_price,
                'is_original' => $record->is_original,
                'exported_saft' => $record->exported_saft,
                'type_id' => [
                    'value' => $record->type_id,
                    'label' => $record->invoice_category->code,
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
                'items' => collect($record->items)->map(function($item) {
                    return [
                        'id' => $item->id ?? null,
                        'invoice_id' => $item->invoice_id ?? null,
                        'unit_id' => [
                            'value' => $item->unit_id,
                            'label' => $item->unit->code,
                        ],
                        'exemption_id' => $item->exemption_id ?? null,
                        'exemption_code' => $item->exemption_code ?? null,
                        'discount_id' => $item->discount_id,
                        'item_id' => [
                            'value' => $item->item_id,
                            'label' => $item->item_description,
                            'price'=> $item->unit_price + $item->discount_amount,
                            'tax_id'=> $item->tax_id,
                            'charge_tax'=> $item->charge_tax,
                            'tax_percentage'=> $item->tax_percentage,
                            'exemption_id'=> $item->exemption_id,
                            'exemption_code'=> $item->exemption_code,
                        ],
                        'item_description' => $item->item_description,
                        'itemable_id' => [
                            'value' => $item->itemable_id ?? '',
                            'label' => $item->itemable?->code?->code ?? '',
                        ],
                        'itemable_type' => $item->itemable_type,
                        'qty' => $item->qty ?? 1,
                        'unit_price' => $item->unit_price,
                        'tax_id' => $item->tax_id,
                        'total' => $item->total,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_amount' => $item->discount_id == 1 ? $item->discount_percentage : $item->discount_amount,
                        'tax_percentage' => $item->tax_percentage,
                        'tax_amount' => $item->tax_amount,
                        'obs' => $item->obs,
                        'charge_tax' => $item->charge_tax,
                    ];
                })
            ],
            'discount_categories' => collect(DiscountCategory::all())->map(function($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            })
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ReceiptRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_receipts'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Receipt::findOrFail($id), function($record) use($request) {

                $record->update($request->validated());
    
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
        abort_if( !auth()->user()->can('delete_receipts'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Receipt::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_receipts'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Receipt::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInvoice() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("invoices")
                ->select('invoices.*')
                ->where('inv_no','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF() {
        abort_if( !auth()->user()->can('view_receipts'), 403, '');

        $ntw = new NumberToWords();
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');
        $model = Receipt::with('items.invoice', 'user', 'customer', 'warehouse')->find(request()->id);
        //dd($model);
        
        $pdf = PDF::loadView('PDFs.receipt', [
            'model' => $model,
            'nTrans' => $nTrans,
            'settings' => app(GeneralSettings::class),
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
}
