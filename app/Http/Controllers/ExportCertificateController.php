<?php

namespace App\Http\Controllers;

use App\Actions\IssueBillingSourceInvoice;
use App\Actions\SaveTradeCertificate;
use App\Actions\SetBillingDocumentsArchived;
use App\Http\Requests\ExportCertificateRequest;
use App\Http\Requests\IssueCertificateInvoiceRequest;
use App\Http\Requests\SetBillingDocumentsArchivedRequest;
use App\Http\Resources\ExportCertificateResource;
use App\Models\DiscountCategory;
use App\Models\ExportCertificate;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExportCertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_export_certificates'), 403, '');

        return Inertia::render('ExportCertificates/Index', [
            'record' => ExportCertificateResource::collection(
                ExportCertificate::query()
                    ->with('country_destination', 'country_origin', 'trans_type', 'exporter', 'exporter_warehouse', 'user')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('cert_no', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.export_certificates.date'),
                    'value' => 'date',
                ],
                [
                    'name' => trans('gestlab.general.labels.export_certificates.cert_no'),
                    'value' => 'cert_no',
                ],
                [
                    'name' => trans('gestlab.general.labels.export_certificates.exporter_id'),
                    'value' => 'exporter',
                ],
                [
                    'name' => trans('gestlab.general.labels.export_certificates.exporter_warehouse_id'),
                    'value' => 'exporter_warehouse',
                ],
                [
                    'name' => trans('gestlab.general.labels.export_certificates.country_origin_id'),
                    'value' => 'country_origin',
                ],
                [
                    'name' => trans('gestlab.general.labels.export_certificates.country_destination_id'),
                    'value' => 'country_destination',
                ],
                [
                    'name' => trans('gestlab.general.labels.export_certificates.authorized_personnel'),
                    'value' => 'authorized_personnel',
                ],
            ],
            'model' => ExportCertificate::MENU_NAME,
            'abilities' => method_exists(ExportCertificate::class, 'getAbilities') ? collect(ExportCertificate::ABILITIES)->map(function ($item) {
                return $item.'_'.ExportCertificate::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ExportCertificate::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_export_certificates'), 403, '');

        return Inertia::render('ExportCertificates/Create', [

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExportCertificateRequest $request, SaveTradeCertificate $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), 'export', $request->validated());

        return redirect()->back()->with(['toast' => ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.record_successfully_created')]]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        abort_if(! auth()->user()->can('view_export_certificates'), 403, '');

        // Find the record
        $record = ExportCertificate::with('items.product', 'country_destination', 'country_origin', 'trans_type', 'exporter', 'exporter_warehouse', 'user')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ExportCertificates/Show', [
            'record' => new ExportCertificateResource($record),
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_export_certificates'), 403, '');

        // Find the record
        $record = ExportCertificate::with('items.product', 'country_destination', 'country_origin', 'trans_type', 'exporter', 'exporter_warehouse', 'user')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ExportCertificates/Edit', [

            'record' => ExportCertificateResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExportCertificateRequest $request, int $id, SaveTradeCertificate $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'), 'export', $request->validated(), $id);

        return redirect()->back()->with(['toast' => ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.record_successfully_updated')]]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SetBillingDocumentsArchivedRequest $request, SetBillingDocumentsArchived $action): RedirectResponse
    {
        $action->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'),
            ExportCertificate::class, $request->validated('recordIds'), true);

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
            ExportCertificate::class, $request->validated('recordIds'), false);

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

    public function getExportCertificate(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = ExportCertificate::query()
            ->select(['id', 'cert_no', 'date'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('cert_no', 'LIKE', "%{$search}%");
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (ExportCertificate $certificate): array => [
                'id' => $certificate->id,
                'value' => $certificate->id,
                'label' => $certificate->cert_no,
                'cert_no' => $certificate->cert_no,
                'date' => $certificate->date,
            ]);

        return response()->json($data);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_export_certificates'), 403, '');

        // $ntw = new NumberToWords();
        // $nTrans = $ntw->getNumberTransformer('pt_BR');
        // $cTrans = $ntw->getCurrencyTransformer('pt_BR');

        $model = ExportCertificate::with('items.product', 'country_destination', 'country_origin', 'trans_type', 'exporter', 'exporter_warehouse', 'user')->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildExportCertificatePayload($model, app(GeneralSettings::class));
        $filename = $model->cert_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('export_certificate', $payload, $filename);

        if (request()->q) {
            activity()->performedOn($model)->log('baixou o Fitosanitário Para Exportação Nº '.$model->cert_no);

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
                ->log('visualizou o Fitosanitário Para Exportação Nº '.$model->cert_no);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }

    public function getIssueInvoiceModal()
    {
        abort_unless(auth()->user()->can('view_export_certificates') && auth()->user()->can('add_invoices'), 403);

        $certificateId = request()->integer('id');

        return Inertia::modal('ExportCertificates/issue-invoice-modal', [
            'record' => ExportCertificateResource::make(
                ExportCertificate::with('exporter', 'exporter_warehouse')
                    ->findOrFail($certificateId)
            ),
            'discount_categories' => collect(DiscountCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),
            'type' => 'export_certificate',
            'title' => 'Emissão de Factura para Fitosanitário Para Exportação',
            'action' => 'issue',
            'url' => route('exportcertificates.issueInvoice', $certificateId),
        ], route('exportcertificates.show', $certificateId));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function issueInvoice(IssueCertificateInvoiceRequest $request, IssueBillingSourceInvoice $issueInvoice)
    {
        $issueInvoice->execute($request->user()->id, (int) $request->attributes->get('proposal_laboratory_id'),
            'export_certificate', $request->integer('certificate_id'), $request->safe()->except(['items']),
            $request->validated('items'));

        return redirect()->back()->with([
            'toast' => ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.record_successfully_created')],
        ]);
    }
}
