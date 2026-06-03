<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\DiscountCategory;
use App\Models\Invoice;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_receipts'), 403, '');

        return Inertia::render('Receipts/Index', [
            'record' => ReceiptResource::collection(
                Receipt::query()
                    ->with('warehouse', 'customer')
                    ->withCount('activities as revision_count')
                    ->withMax('activities as last_revision_at', 'created_at')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('rec_no', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.receipts.date'),
                    'value' => 'date',
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.rec_no'),
                    'value' => 'rec_no',
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.warehouse_id'),
                    'value' => 'warehouse',
                ],
                [
                    'name' => trans('gestlab.general.labels.receipts.total'),
                    'value' => 'total',
                ],
            ],
            'model' => Receipt::MENU_NAME,
            'abilities' => method_exists(Receipt::class, 'getAbilities') ? collect(Receipt::ABILITIES)->map(function ($item) {
                return $item.'_'.Receipt::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Receipt::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_receipts'), 403, '');

        return Inertia::render('Receipts/Create', [
            'payment_categories' => collect(PaymentCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->name,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReceiptRequest $request)
    {
        abort_if(! auth()->user()->can('add_receipts'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first());

        DB::transaction(function () use ($request): void {
            $receipt = Receipt::create($request->safe()->except(['items']));

            $receipt->items()->createMany(collect($request->items)->toArray());
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
        abort_if(! auth()->user()->can('edit_receipts'), 403, '');

        // Find the record
        $record = Receipt::with('items.invoice', 'items.payment_category', 'customer', 'warehouse', 'user', 'type')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Receipts/Edit', [

            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'internal_ref' => $record->description,
                'inv_no' => $record->rec_no,
                'obs' => $record->obs,
                'status' => $record->exported_saft ? 'exported' : 'draft',
                'use_matrix_price' => false,
                'is_original' => $record->is_original,
                'exported_saft' => $record->exported_saft,
                'type_id' => [
                    'value' => $record->payment_type,
                    'label' => $record->type?->name,
                ],
                'customer_id' => [
                    'value' => $record->customer_id,
                    'label' => $record->customer?->name,
                ],
                'user_id' => [
                    'value' => $record->user_id,
                    'label' => $record->user?->name,
                ],
                'warehouse_id' => [
                    'value' => $record?->warehouse?->id,
                    'label' => $record?->warehouse?->address,
                ],
                'items' => collect($record->items)->map(function ($item) {
                    return [
                        'id' => $item->id ?? null,
                        'invoice_id' => [
                            'value' => $item->invoice_id,
                            'label' => $item->invoice?->inv_no,
                            'amount_due' => $item->invoice_pending_amount,
                        ],
                        'unit_id' => [
                            'value' => null,
                            'label' => null,
                        ],
                        'exemption_id' => null,
                        'exemption_code' => null,
                        'discount_id' => null,
                        'payment_id' => [
                            'value' => $item->payment_id,
                            'label' => $item->payment_category?->name,
                        ],
                        'paid_amount' => $item->paid_amount,
                        'pending_amount' => $item->pending_amount,
                        'invoice_pending_amount' => $item->invoice_pending_amount,
                        'item_id' => [
                            'value' => $item->invoice_id,
                            'label' => $item->invoice?->inv_no,
                            'price' => $item->paid_amount,
                            'tax_id' => null,
                            'charge_tax' => false,
                            'tax_percentage' => 0,
                            'exemption_id' => null,
                            'exemption_code' => null,
                        ],
                        'item_description' => $item->invoice?->inv_no ?? 'Receipt payment',
                        'itemable_id' => [
                            'value' => $item->invoice_id ?? '',
                            'label' => $item->invoice?->inv_no ?? '',
                        ],
                        'itemable_type' => Invoice::class,
                        'qty' => $item->qty ?? 1,
                        'unit_price' => $item->paid_amount,
                        'tax_id' => null,
                        'total' => $item->paid_amount,
                        'discount_percentage' => 0,
                        'discount_amount' => 0,
                        'tax_percentage' => 0,
                        'tax_amount' => 0,
                        'obs' => $item->obs,
                        'charge_tax' => false,
                    ];
                }),
                'total' => $record->items->sum('paid_amount'),
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
    public function update(ReceiptRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_receipts'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Receipt::findOrFail($id), function ($record) use ($request) {

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
        abort_if(! auth()->user()->can('delete_receipts'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Receipt::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_receipts'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Receipt::withTrashed()->findOrFail(request('recordIds')) as $record) {
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

    public function getReceipt(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = Receipt::query()
            ->select(['id', 'rec_no', 'date'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('rec_no', 'LIKE', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Receipt $receipt): array => [
                'id' => $receipt->id,
                'value' => $receipt->id,
                'label' => $receipt->rec_no,
                'rec_no' => $receipt->rec_no,
                'date' => $receipt->date,
            ]);

        return response()->json($data);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_receipts'), 403, '');

        $model = Receipt::with('items.invoice', 'user', 'customer', 'warehouse')->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildReceiptPayload(
            $model,
            app(GeneralSettings::class)
        );
        $filename = $model->rec_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('receipt', $payload, $filename);

        if (request()->q) {
            activity()->log('baixou o Recibo Nº '.$model->rec_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Recibo Nº '.$model->rec_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }
}
