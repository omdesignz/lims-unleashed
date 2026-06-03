<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Models\CollectionProduct;
use App\Models\DiscountCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabCode;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_quotes'), 403, '');

        return Inertia::render('Quotes/Index', [
            'record' => QuoteResource::collection(
                Quote::query()
                    ->with('warehouse', 'customer')
                    ->withCount('activities as revision_count')
                    ->withMax('activities as last_revision_at', 'created_at')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('quote_no', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.quotes.date'),
                    'value' => 'date',
                ],
                [
                    'name' => trans('gestlab.general.labels.quotes.quote_no'),
                    'value' => 'quote_no',
                ],
                [
                    'name' => trans('gestlab.general.labels.quotes.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.quotes.warehouse_id'),
                    'value' => 'warehouse',
                ],
                [
                    'name' => trans('gestlab.general.labels.quotes.total'),
                    'value' => 'total',
                ],
            ],
            'model' => Quote::MENU_NAME,
            'abilities' => method_exists(Quote::class, 'getAbilities') ? collect(Quote::ABILITIES)->map(function ($item) {
                return $item.'_'.Quote::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Quote::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_quotes'), 403, '');

        return Inertia::render('Quotes/Create', [
            'discount_categories' => collect(DiscountCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuoteRequest $request)
    {
        abort_if(! auth()->user()->can('add_quotes'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first());

        DB::transaction(function () use ($request): void {
            $quote = Quote::create($request->safe()->except(['items']));

            foreach (collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new QuoteItem;

                $obj->quote_id = $quote->id;
                $obj->item_id = $item['item_id'];
                $obj->item_description = $item['item_description'];
                $obj->itemable_id = $item['itemable_id'];
                $obj->itemable_type = $item['itemable_type'];
                $obj->exemption_id = $item['exemption_id'];
                $obj->exemption_code = $item['exemption_code'];
                $obj->discount_id = $item['discount_id'];
                $obj->unit_id = $item['unit_id'];
                $obj->tax_id = $item['tax_id'];
                $obj->qty = $item['qty'];
                $obj->unit_price = $item['unit_price'];
                $obj->total = $item['total'];
                $obj->charge_tax = $item['charge_tax'];
                $obj->tax_amount = $item['tax_amount'];
                $obj->tax_percentage = $item['tax_percentage'];
                $obj->discount_amount = $item['discount_amount'];
                $obj->discount_percentage = $item['discount_percentage'];
                $obj->obs = $item['obs'];

                $obj->save();

                if (! is_null($obj->itemable_id) && $obj->itemable_type === 'collectionproduct') {
                    CollectionProduct::query()->whereKey($obj->itemable_id)->update([
                        'quote_id' => $quote->id,
                        'quoted' => true,
                    ]);
                }

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
        $record = Quote::with('items.itemable.code', 'customer', 'warehouse', 'user', 'discount_category')
            ->withCount('activities as revision_count')
            ->withMax('activities as last_revision_at', 'created_at')
            ->findOrFail($id);

        return Inertia::render('Quotes/Show', [
            'record' => QuoteResource::make($record),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_quotes'), 403, '');

        // Find the record
        $record = Quote::with('items.itemable.code', 'customer', 'warehouse', 'user', 'discount_category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Quotes/Edit', [

            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'due_date' => $record->due_date,
                'internal_ref' => $record->internal_ref,
                'quote_no' => $record->quote_no,
                'obs' => $record->obs,
                'status' => $record->status,
                'use_matrix_price' => $record->use_matrix_price,
                'is_service' => $record->is_service,
                'is_original' => $record->is_original,
                'converted_to_invoice' => $record->converted_to_invoice,
                'exported_saft' => $record->exported_saft,
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
                        'invoice_id' => $item->invoice_id ?? null,
                        'unit_id' => [
                            'value' => $item->unit_id,
                            'label' => $item?->unit?->code,
                        ],
                        'exemption_id' => $item->exemption_id ?? null,
                        'exemption_code' => $item->exemption_code ?? null,
                        'discount_id' => $item->discount_id,
                        'item_id' => [
                            'value' => $item->item_id,
                            'label' => $item->item_description,
                            'price' => $item->unit_price + $item->discount_amount,
                            'tax_id' => $item->tax_id,
                            'charge_tax' => $item->charge_tax,
                            'tax_percentage' => $item->tax_percentage,
                            'exemption_id' => $item->exemption_id,
                            'exemption_code' => $item->exemption_code,
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
                }),
            ],
            'discount_categories' => collect(DiscountCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuoteRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_quotes'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Quote::findOrFail($id), function ($record) use ($request) {

                $record->update($request->validated());

                QuoteItem::where('quote_id', $record->id)->forcedelete();

                foreach (collect($request->safe()->only(['items']))->first() as $item) {

                    $obj = new QuoteItem;

                    $obj->quote_id = $record->id;
                    $obj->item_id = $item['item_id'];
                    $obj->itemable_id = $item['itemable_id'];
                    $obj->itemable_type = $item['itemable_type'];
                    $obj->item_description = $item['item_description'];
                    $obj->exemption_id = $item['exemption_id'];
                    $obj->exemption_code = $item['exemption_code'];
                    $obj->discount_id = $item['discount_id'];
                    $obj->unit_id = $item['unit_id'];
                    $obj->tax_id = $item['tax_id'];
                    $obj->qty = $item['qty'];
                    $obj->unit_price = $item['unit_price'];
                    $obj->total = $item['total'];
                    $obj->charge_tax = $item['charge_tax'];
                    $obj->tax_amount = $item['tax_amount'];
                    $obj->tax_percentage = $item['tax_percentage'];
                    $obj->discount_amount = $item['discount_amount'];
                    $obj->discount_percentage = $item['discount_percentage'];
                    $obj->obs = $item['obs'];

                    $obj->save();

                    if ($record->itemable_id) {

                        CollectionProduct::findOrFail(LabCode::findOrFail($record->itemable_id)->collection_id)->quote_item()->save($record);

                    }

                }

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
        abort_if(! auth()->user()->can('delete_quotes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Quote::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_quotes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Quote::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function convertToInvoice()
    {
        abort_if(! auth()->user()->can('add_invoices'), 403, '');

        $quote = Quote::with('items')->findOrFail(request()->quote_id);

        DB::transaction(function () use ($quote): void {
            // $quote = Quote::with('items')->findOrFail(request()->quote_id);

            $invoice = Invoice::create([
                'type_id' => request()->type_id ? request()->type_id['value'] : 1,
                'user_id' => auth()->user()->id,
                'customer_id' => $quote->customer_id,
                'discount_type' => $quote->discount_type,
                'warehouse_id' => $quote->warehouse_id,
                'inv_no' => '',
                'invoice_month' => now()->format('Y'),
                'description' => $quote->description,
                'internal_ref' => $quote->internal_ref,
                'file_path' => '',
                'date' => now()->format('Y-m-d'),
                'paid_date' => null,
                'payment_method' => null,
                'discount' => $quote->discount,
                'tax' => $quote->tax,
                'sub_total' => $quote->sub_total,
                'total' => $quote->total,
                'amount_due' => $quote->total,
                'obs' => $quote->obs,
                'status_code' => '',
                'status' => false,
                'is_original' => true,
                'use_matrix_price' => $quote->use_matrix_price,
                'is_service' => $quote->is_service,
                'exported_saft' => false,
                'extra_data' => $quote->extra_data,
            ]);

            foreach (collect($quote->items) as $item) {

                $obj = new InvoiceItem;

                $obj->invoice_id = $invoice->id;
                $obj->item_id = $item['item_id'];
                $obj->item_description = $item['item_description'];
                $obj->exemption_id = $item['exemption_id'];
                $obj->exemption_code = $item['exemption_code'];
                $obj->itemable_id = $item['itemable_id'];
                $obj->itemable_type = $item['itemable_type'];
                $obj->discount_id = $item['discount_id'];
                $obj->unit_id = $item['unit_id'];
                $obj->tax_id = $item['tax_id'];
                $obj->qty = $item['qty'];
                $obj->unit_price = $item['unit_price'];
                $obj->total = $item['total'];
                $obj->charge_tax = $item['charge_tax'];
                $obj->tax_amount = $item['tax_amount'];
                $obj->tax_percentage = $item['tax_percentage'];
                $obj->discount_amount = $item['discount_amount'];
                $obj->discount_percentage = $item['discount_percentage'];
                $obj->obs = $item['obs'];

                $obj->save();

            }

            $quote->update([
                'converted_to_invoice' => true,
                'invoice_id' => $invoice->id,
            ]);

        });

        // return redirect()->back()->with([
        //     'toast' => [
        //         'title' => trans('gestlab.toasts.notification'),
        //         'message' => 'Registro armazenado com êxito'
        //     ]
        // ]);

        if ($quote->converted_to_invoice) {
            return redirect()->route('invoices.show', $quote->invoice_id)->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'Registro armazenado com êxito',
                ],
            ]);
        } else {
            return redirect()->route('quotes.show', $quote->id)->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'Registro Não Foi Convertido Para Fatura',
                ],
            ]);
        }

    }

    public function getInvoice()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('quotes')
                ->select('quotes.*')
                ->where('quote_no', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getQuote(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = Quote::query()
            ->select(['id', 'quote_no', 'date', 'total'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('quote_no', 'LIKE', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'value' => $quote->id,
                'label' => $quote->quote_no,
                'quote_no' => $quote->quote_no,
                'date' => $quote->date,
                'total' => $quote->total,
            ]);

        return response()->json($data);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_quotes'), 403, '');

        $model = Quote::with('items.itemable', 'user', 'customer', 'warehouse')->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildQuotePayload(
            $model,
            app(GeneralSettings::class)
        );
        $filename = $model->quote_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('quote', $payload, $filename);

        if (request()->q) {
            activity()->log('baixou o Proforma Nº '.$model->quote_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Proforma Nº '.$model->quote_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }

    public function getConvertToInvoiceModal()
    {
        abort_if(! auth()->user()->can('edit_quotes'), 403, '');

        return Inertia::modal('Quotes/convert-to-invoice-modal', [
            'record' => QuoteResource::make(
                Quote::with('customer', 'warehouse')
                    ->findOrFail(request()->integer('id'))
            ),
            'title' => 'Conversão de Proposta Para Factura',
            'action' => 'convert',
            'url' => route('quotes.convertToInvoice', request()->integer('id')),
        ], route('quotes.show', request()->integer('id')));
    }
}
