<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreditNoteRequest;
use App\Http\Resources\CreditNoteResource;
use App\Models\CollectionProduct;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\DiscountCategory;
use App\Models\Invoice;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_credit_notes'), 403, '');

        return Inertia::render('CreditNotes/Index', [
            'record' => CreditNoteResource::collection(
                CreditNote::query()
                    ->with('warehouse', 'customer')
                    ->withCount('activities as revision_count')
                    ->withMax('activities as last_revision_at', 'created_at')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('note_no', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.credit_notes.date'),
                    'value' => 'date',
                ],
                [
                    'name' => trans('gestlab.general.labels.credit_notes.note_no'),
                    'value' => 'note_no',
                ],
                [
                    'name' => trans('gestlab.general.labels.credit_notes.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.credit_notes.warehouse_id'),
                    'value' => 'warehouse',
                ],
                [
                    'name' => trans('gestlab.general.labels.credit_notes.total'),
                    'value' => 'total',
                ],
            ],
            'model' => CreditNote::MENU_NAME,
            'abilities' => method_exists(CreditNote::class, 'getAbilities') ? collect(CreditNote::ABILITIES)->map(function ($item) {
                return $item.'_'.CreditNote::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.CreditNote::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_credit_notes'), 403, '');

        return Inertia::render('CreditNotes/Create', [
            'reasons' => collect([
                [
                    'value' => CreditNote::REASON_RECTIFICATION,
                    'label' => 'Rectificação',
                ],
                [
                    'value' => CreditNote::REASON_CANCELATION,
                    'label' => 'Anulação',
                ],
            ]),
            'discount_categories' => collect(DiscountCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),

            'invoice_record' => Inertia::lazy(fn () => collect(Invoice::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice_category')->whereId(request()->id)->get())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'internal_ref' => $item->internal_ref,
                    'inv_no' => $item->inv_no,
                    'obs' => $item->obs,
                    'status' => $item->status,
                    'use_matrix_price' => $item->use_matrix_price,
                    'is_original' => $item->is_original,
                    'exported_saft' => $item->exported_saft,
                    'type_id' => [
                        'value' => $item->type_id,
                        'label' => $item->invoice_category->code,
                    ],
                    'customer_id' => [
                        'value' => $item->customer_id,
                        'label' => $item->customer->name,
                    ],
                    'user_id' => [
                        'value' => $item->user_id,
                        'label' => $item->user->name,
                    ],
                    'warehouse_id' => [
                        'value' => $item?->warehouse?->id,
                        'label' => $item?->warehouse?->address,
                    ],
                    'items' => collect($item->items)->map(function ($item) {
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
                                // 'label' => $item?->itemable?->code?->code ?? '',
                                'label' => '',
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
                ];
            })
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreditNoteRequest $request)
    {
        abort_if(! auth()->user()->can('add_credit_notes'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first());
        // dd(request()->all());

        DB::transaction(function () use ($request): void {
            $note = CreditNote::create($request->safe()->except(['items']));

            foreach (collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new CreditNoteItem;

                $obj->note_id = $note->id;
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
                $obj->obs = $item['obs'];

                $obj->save();

            }

            if ($note->reason === CreditNote::REASON_CANCELATION && ! is_null($note->invoice_id)) {

                // Find Invoice and Change status to "A - Anulado"
                $invoice = Invoice::find($note->invoice_id);

                if ($invoice) {
                    $invoice->update([
                        'status_code' => Invoice::STATUS_CODE_CANCELED,
                    ]);

                    // Find Invoice Items IDs
                    $IDs = $invoice->items->pluck('itemable_id')->toArray();

                    // Update Invoiced and Invoice_id fields from Collection Product
                    CollectionProduct::whereIn('id', $IDs)->update([
                        'invoiced' => false,
                        'invoice_id' => null,
                    ]);
                }
            }

            if ($note->reason === CreditNote::REASON_RECTIFICATION && ! is_null($note->invoice_id)) {

                // Find CNote IDs
                $IDs = $note->items->pluck('itemable_id')->toArray();

                // Update Invoiced and Invoice_id fields from Collection Product

                CollectionProduct::whereIn('id', $IDs)->update([
                    'invoiced' => true,
                    'invoice_id' => null,
                ]);
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
        abort_if(! auth()->user()->can('edit_credit_notes'), 403, '');

        // Find the record
        $record = CreditNote::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('CreditNotes/Edit', [

            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'internal_ref' => $record->internal_ref,
                'reason' => $record->reason,
                'note_no' => $record->note_no,
                'obs' => $record->obs,
                'status' => $record->status,
                'use_matrix_price' => $record->use_matrix_price,
                'is_service' => $record->is_service,
                'is_original' => $record->is_original,
                'exported_saft' => $record->exported_saft,
                'invoice_id' => [
                    'value' => $record->invoice_id,
                    'label' => $record->invoice->inv_no,
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
                        'invoice_id' => [
                            'value' => $item->invoice_id ?? null,
                            'label' => $item->invoice?->inv_no,
                        ],
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
    public function update(CreditNoteRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_credit_notes'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(CreditNote::findOrFail($id), function ($record) use ($request) {

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
        abort_if(! auth()->user()->can('delete_credit_notes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (CreditNote::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_credit_notes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (CreditNote::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getNote()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('credit_notes')
                ->select('credit_notes.*')
                ->where('inv_no', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getCreditNote(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = CreditNote::query()
            ->select(['id', 'note_no', 'date', 'total'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('note_no', 'LIKE', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (CreditNote $creditNote): array => [
                'id' => $creditNote->id,
                'value' => $creditNote->id,
                'label' => $creditNote->note_no,
                'note_no' => $creditNote->note_no,
                'date' => $creditNote->date,
                'total' => $creditNote->total,
            ]);

        return response()->json($data);
    }

    public function getInvoiceData()
    {
        $data = collect();

        if (request()->has('id')) {

            $data = collect(Invoice::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice_category')->whereId(request()->id)->get())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date,
                    'internal_ref' => $item->internal_ref,
                    'inv_no' => $item->inv_no,
                    'obs' => $item->obs,
                    'status' => $item->status,
                    'use_matrix_price' => $item->use_matrix_price,
                    'is_original' => $item->is_original,
                    'exported_saft' => $item->exported_saft,
                    'type_id' => [
                        'value' => $item->type_id,
                        'label' => $item->invoice_category->code,
                    ],
                    'customer_id' => [
                        'value' => $item->customer_id,
                        'label' => $item->customer->name,
                    ],
                    'user_id' => [
                        'value' => $item->user_id,
                        'label' => $item->user->name,
                    ],
                    'warehouse_id' => [
                        'value' => $item?->warehouse?->id,
                        'label' => $item?->warehouse?->address,
                    ],
                    'items' => collect($item->items)->map(function ($item) {
                        return [
                            'id' => $item->id ?? null,
                            'note_id' => '',
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
                                'label' => $item?->itemable?->code?->code ?? '',
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
                ];
            });

        }

        return response()->json($data->first() ?? []);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_credit_notes'), 403, '');

        $model = CreditNote::with('items', 'user', 'customer', 'warehouse', 'invoice')->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildCreditNotePayload(
            $model,
            app(GeneralSettings::class)
        );

        $filename = $model->note_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('credit_note', $payload, $filename);

        if (request()->q) {
            activity()->log('baixou o Nota de Crédito Nº '.$model->note_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Nota de Crédito Nº '.$model->note_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }
}
