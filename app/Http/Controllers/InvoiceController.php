<?php

namespace App\Http\Controllers;

use App\Actions\RecordInvoicePayment;
use App\Actions\SetBillingDocumentsArchived;
use App\Actions\UpdateFinancialDocumentObservation;
use App\Http\Requests\FinancialDocumentObservationRequest;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\RecordInvoicePaymentRequest;
use App\Http\Requests\SetBillingDocumentsArchivedRequest;
use App\Http\Resources\FinancialObservationResource;
use App\Http\Resources\InvoiceResource;
use App\Models\CollectionProduct;
use App\Models\DiscountCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabCode;
use App\Services\FinancialDocumentAssembly;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        abort_if(! auth()->user()->can('view_invoices'), 403, '');

        $filter = $request->string('filter')->toString();

        return Inertia::render('Invoices/Index', [
            'record' => InvoiceResource::collection(
                Invoice::query()
                    ->with('warehouse', 'customer')
                    ->withCount('activities as revision_count')
                    ->withMax('activities as last_revision_at', 'created_at')
                    ->when($request->input('search'), function ($query, $search) {
                        $query->where('inv_no', 'like', "%{$search}%");
                    })
                    ->when($filter === 'trashed', fn ($query) => $query->withTrashed())
                    ->paymentStatus($filter)
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
                [
                    'name' => trans('gestlab.general.labels.invoices.payment_status'),
                    'value' => 'payment_status_label',
                ],
            ],
            'model' => Invoice::MENU_NAME,
            'abilities' => method_exists(Invoice::class, 'getAbilities') ? collect(Invoice::ABILITIES)->map(function ($item) {
                return $item.'_'.Invoice::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Invoice::MENU_NAME;
            }),
            'query' => $request->only(['search', 'filter']),
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

                abort_unless(app(FinancialDocumentAssembly::class)->withLines($invoice, fn (): bool => $obj->save()), 409);

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

    public function changeStatusToPaid(RecordInvoicePaymentRequest $request, RecordInvoicePayment $recordPayment)
    {
        $recordPayment->execute((int) $request->user()->id,
            (int) $request->attributes->get('proposal_laboratory_id'),
            (int) $request->validated('id'), (int) $request->validated('payment_method.value'));

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
    public function edit(int $id): Response
    {
        abort_unless(auth()->user()->can('edit_invoices'), 403);

        return Inertia::render('Invoices/Edit', [
            'record' => FinancialObservationResource::make(Invoice::query()->findOrFail($id)),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinancialDocumentObservationRequest $request, int $id, UpdateFinancialDocumentObservation $correctObservation): RedirectResponse
    {
        $correctObservation->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), Invoice::class, $id, $request->validated('obs'));

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
    public function destroy(SetBillingDocumentsArchivedRequest $request, SetBillingDocumentsArchived $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'),
            Invoice::class, $request->validated('recordIds'), true);

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
    public function restore(SetBillingDocumentsArchivedRequest $request, SetBillingDocumentsArchived $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'),
            Invoice::class, $request->validated('recordIds'), false);

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

            $data = Invoice::query()
                ->select(['id', 'inv_no', 'amount_due', 'customer_id', 'warehouse_id'])
                ->where('inv_no', 'LIKE', "%$search%")
                ->orderBy('inv_no')->limit(50)
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
            activity()->performedOn($model)->log('baixou o Factura Nº '.$model->inv_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        if (! request()->q) {
            activity()
                ->performedOn($model)
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
