<?php

namespace App\Http\Controllers;

use App\Actions\IssueBillingSourceInvoice;
use App\Actions\SaveQuote;
use App\Actions\SetBillingDocumentsArchived;
use App\Http\Requests\ConvertQuoteToInvoiceRequest;
use App\Http\Requests\QuoteRequest;
use App\Http\Requests\SetBillingDocumentsArchivedRequest;
use App\Http\Resources\FinancialObservationResource;
use App\Http\Resources\QuoteAuthoringResource;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        return Inertia::render('Quotes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuoteRequest $request, SaveQuote $action): RedirectResponse
    {
        $record = $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), $request->validated());

        $response = $request->user()->fresh()->can('view_quotes') ? to_route('quotes.show', $record->id) : to_route('quotes.create');

        return $response->with('toast', ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.record_successfully_created')]);
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

        if ($record->invoice_id !== null || $record->converted_to_invoice) {
            return Inertia::render('Quotes/Edit', ['record' => FinancialObservationResource::make($record)->resolve(request())]);
        }

        return Inertia::render('Quotes/Edit', [
            'record' => QuoteAuthoringResource::make($record->load('items.unit'))->resolve(request()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuoteRequest $request, int $id, SaveQuote $action): RedirectResponse
    {
        $record = $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), $request->validated(), $id);

        return to_route('quotes.edit', $record->id)->with('toast', ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.record_successfully_updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SetBillingDocumentsArchivedRequest $request, SetBillingDocumentsArchived $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'),
            Quote::class, $request->validated('recordIds'), true);

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
            Quote::class, $request->validated('recordIds'), false);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function convertToInvoice(ConvertQuoteToInvoiceRequest $request, IssueBillingSourceInvoice $issueInvoice)
    {
        $invoice = $issueInvoice->execute($request->user()->id,
            (int) $request->attributes->get('proposal_laboratory_id'), 'quote',
            $request->integer('quote_id'), $request->validated());

        return redirect()->route('invoices.show', $invoice->id)->with([
            'toast' => ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.record_successfully_created')],
        ]);
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
            activity()->performedOn($model)->log('baixou o Proforma Nº '.$model->quote_no);

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
        abort_unless(auth()->user()->can('view_quotes') && auth()->user()->can('add_invoices'), 403);

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
