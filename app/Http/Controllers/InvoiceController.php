<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\CollectionProduct;
use App\Models\DiscountCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceipt;
use App\Models\LabCode;
use App\Models\Receipt;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_invoices'), 403, '');

        return Inertia::render('Invoices/Index', [
            'record' => InvoiceResource::collection(
                Invoice::query()
                    ->with('warehouse', 'customer')
                    ->withCount('activities as revision_count')
                    ->withMax('activities as last_revision_at', 'created_at')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('inv_no', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.invoices.date'),
                    'value' => 'date',
                ],
                [
                    'name' => trans('gestlab.general.labels.invoices.inv_no'),
                    'value' => 'inv_no',
                ],
                [
                    'name' => trans('gestlab.general.labels.invoices.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.invoices.warehouse_id'),
                    'value' => 'warehouse',
                ],
                [
                    'name' => trans('gestlab.general.labels.invoices.total'),
                    'value' => 'total',
                ],
            ],
            'model' => Invoice::MENU_NAME,
            'abilities' => method_exists(Invoice::class, 'getAbilities') ? collect(Invoice::ABILITIES)->map(function ($item) {
                return $item.'_'.Invoice::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Invoice::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_invoices'), 403, '');

        return Inertia::render('Invoices/Create', [
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
    public function store(InvoiceRequest $request)
    {
        abort_if(! auth()->user()->can('add_invoices'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first());

        DB::transaction(function () use ($request): void {
            $invoice = Invoice::create($request->safe()->except(['items']));

            foreach (collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new InvoiceItem;

                $obj->invoice_id = $invoice->id;
                $obj->item_id = $item['item_id'];
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
                $obj->itemable_id = $item['itemable_id'];
                $obj->itemable_type = $item['itemable_type'];
                $obj->obs = $item['obs'];

                $obj->save();

                if (! is_null($obj->itemable_id) && $obj->itemable_type === 'collectionproduct') {
                    CollectionProduct::query()->whereKey($obj->itemable_id)->update([
                        'invoice_id' => $invoice->id,
                        'invoiced' => true,
                    ]);
                }

            }

            if ($request->boolean('assign_lab_code') && ! is_null($request->labcode_id)) {

                $labCode = LabCode::query()->find($request->labcode_id);

                if ($labCode?->collection_id) {
                    CollectionProduct::query()->whereKey($labCode->collection_id)->update([
                        'invoice_id' => $invoice->id,
                        'invoiced' => true,
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

    public function changeStatusToPaid(Request $request)
    {
        // dd($request->payment_method['label'] ?? null);
        DB::transaction(function () use ($request): void {

            $record = tap(Invoice::findOrFail($request->id), function ($record) use ($request) {

                $amount_due = $record->amount_due;

                $record->update([
                    'status' => true,
                    'paid_date' => now(),
                    'payment_method' => $request->payment_method ? $request->payment_method['label'] : null,
                    'amount_due' => 0,

                ]);

                $receipt = new Receipt;

                $receipt->rec_no = '';
                $receipt->customer_id = $record->customer_id;
                $receipt->warehouse_id = $record->warehouse_id;
                $receipt->invoice_id = $record->id;
                $receipt->payment_type = $request->payment_method['value'] ?? null;
                $receipt->rec_month = now()->format('Y');
                $receipt->description = '';
                $receipt->date = now()->format('Y-m-d');
                $receipt->obs = '';
                $receipt->user_id = auth()->user()->id;
                $receipt->save();

                // Add Invoice to Receipt
                $obj = new InvoiceReceipt;

                $obj->receipt_id = $receipt->id;
                $obj->invoice_id = $record->id;
                $obj->paid_amount = $amount_due;
                $obj->invoice_pending_amount = $amount_due;
                $obj->user_id = auth()->user()->id;
                $obj->payment_id = $request->payment_method['value'] ?? null;
                $obj->pending_amount = 0;

                $obj->save();

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
     * Display the specified resource.
     */
    public function show($id)
    {
        $record = Invoice::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice_category')->findOrFail($id);

        return Inertia::render('Invoices/Show', [
            'record' => InvoiceResource::make($record),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_invoices'), 403, '');

        // Find the record
        $record = Invoice::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice_category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Invoices/Edit', [

            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'internal_ref' => $record->internal_ref,
                'inv_no' => $record->inv_no,
                'obs' => $record->obs,
                'status' => $record->status,
                'use_matrix_price' => $record->use_matrix_price,
                'is_service' => $record->is_service,
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
                'items' => collect($record->items)->map(function ($item) {
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
    public function update(InvoiceRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_invoices'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Invoice::findOrFail($id), function ($record) use ($request) {

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
        abort_if(! auth()->user()->can('delete_invoices'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Invoice::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_invoices'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Invoice::withTrashed()->findOrFail(request('recordIds')) as $record) {
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

            $data = DB::table('invoices')
                ->select('invoices.*')
                ->where('inv_no', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_invoices'), 403, '');

        $model = Invoice::with('items.exemption', 'items.unit', 'items.itemable', 'customer', 'warehouse', 'invoice_category', 'user')->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildInvoicePayload(
            $model,
            app(GeneralSettings::class)
        );
        $filename = $model->inv_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('invoice', $payload, $filename);

        if (request()->q) {
            activity()->log('baixou o Factura Nº '.$model->inv_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Factura Nº '.$model->inv_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }
}
