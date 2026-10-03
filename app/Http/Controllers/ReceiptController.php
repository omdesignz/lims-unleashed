<?php

namespace App\Http\Controllers;

use App\Actions\CreateReceipt;
use App\Actions\SetBillingDocumentsArchived;
use App\Actions\UpdateFinancialDocumentObservation;
use App\Http\Requests\FinancialDocumentObservationRequest;
use App\Http\Requests\ReceiptRequest;
use App\Http\Requests\SetBillingDocumentsArchivedRequest;
use App\Http\Resources\FinancialObservationResource;
use App\Http\Resources\ReceiptResource;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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
    public function store(ReceiptRequest $request, CreateReceipt $createReceipt)
    {
        abort_if(! auth()->user()->can('add_receipts'), 403, '');

        $createReceipt->execute((int) $request->user()->id,
            (int) $request->attributes->get('proposal_laboratory_id'),
            $request->safe()->except(['items']), $request->validated('items'));

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
    public function edit(int $id): Response
    {
        abort_unless(auth()->user()->can('edit_receipts'), 403);

        return Inertia::render('Receipts/Edit', [
            'record' => FinancialObservationResource::make(Receipt::query()->findOrFail($id)),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinancialDocumentObservationRequest $request, int $id, UpdateFinancialDocumentObservation $correctObservation): RedirectResponse
    {
        $correctObservation->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), Receipt::class, $id, $request->validated('obs'));

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
            Receipt::class, $request->validated('recordIds'), true);

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
            Receipt::class, $request->validated('recordIds'), false);

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
            activity()->performedOn($model)->log('baixou o Recibo Nº '.$model->rec_no);

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
                ->log('visualizou o Recibo Nº '.$model->rec_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }
}
