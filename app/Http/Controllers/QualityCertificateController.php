<?php

namespace App\Http\Controllers;

use App\Http\Requests\QualityCertificateRequest;
use App\Http\Resources\QualityCertificateResource;
use App\Models\QualityCertificate;
use App\Models\QualityCertificateRevision;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QualityCertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_quality_certificates'), 403, '');

        return Inertia::render('QualityCertificates/Index', [
            'record' => QualityCertificateResource::collection(
                QualityCertificate::query()
                    ->with('lab_code', 'customer', 'warehouse', 'invoice', 'user')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('code', 'like', "%{$search}%")
                            ->orWhereRelation('lab_code', 'code', 'like', "%{$search}%")
                            ->orWhereRelation('warehouse', 'name', 'like', "%{$search}%")
                            ->orWhereRelation('warehouse', 'address', 'like', "%{$search}%")
                            ->orWhereRelation('product', 'name', 'like', "%{$search}%")
                            ->orWhereRelation('customer', 'name', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.quality_certificates.cl_id'),
                    'value' => 'lab_code',
                ],
                // [
                //     'name' => trans('gestlab.general.labels.quality_certificates.code'),
                //     'value' => 'code'
                // ],
                [
                    'name' => trans('gestlab.general.labels.quality_certificates.product_id'),
                    'value' => 'product',
                ],
                [
                    'name' => trans('gestlab.general.labels.quality_certificates.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.quality_certificates.warehouse_id'),
                    'value' => 'warehouse',
                ],
            ],
            'model' => QualityCertificate::MENU_NAME,
            'abilities' => method_exists(QualityCertificate::class, 'getAbilities') ? collect(QualityCertificate::ABILITIES)->map(function ($item) {
                return $item.'_'.QualityCertificate::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.QualityCertificate::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_quality_certificates'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('QualityCertificates/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QualityCertificateRequest $request)
    {
        abort_if(! auth()->user()->can('add_quality_certificates'), 403, '');

        // Persiste data to DB
        QualityCertificate::create($request->validated());

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
        return Inertia::render('QualityCertificates/Show', [
            'record' => QualityCertificateResource::make(
                QualityCertificate::query()
                    ->with(['currentRevision' => function ($query) {
                        $query->select(['id', 'quality_certificate_id', 'version', 'revision_number']);
                    }, 'customer', 'warehouse', 'user', 'validated_by_user'])
                                //  ->with('customer', 'warehouse', 'user', 'validated_by_user')
                    ->findOrFail($id)
            ),
        ]);
    }

    // View a specific revision (historical view)
    public function showRevision(QualityCertificate $certificate, QualityCertificateRevision $revision)
    {
        return Inertia::render('QualityCertificates/Revisions/Show', [
            'certificate' => $certificate,
            'revision' => $revision,
            'snapshot' => $revision->snapshot_data, // Historical data
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_quality_certificates'), 403, '');

        // Find the record
        $record = QualityCertificate::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('QualityCertificates/Edit', [
            'record' => QualityCertificateResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QualityCertificateRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_quality_certificates'), 403, '');

        // Find the record
        $record = QualityCertificate::findOrFail($id);

        $record->update($request->validated());

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
        abort_if(! auth()->user()->can('delete_quality_certificates'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (QualityCertificate::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_quality_certificates'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (QualityCertificate::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getQualityCertificate(): JsonResponse
    {
        $search = request()->string('q')->trim()->toString();

        $data = DB::table('quality_certificates')
            ->select(['id', 'code', 'obs', 'created_at'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('obs', 'LIKE', "%{$search}%");
                });
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn ($certificate): array => [
                'id' => $certificate->id,
                'value' => $certificate->id,
                'label' => $certificate->code ?: ('Certificado #'.$certificate->id),
                'code' => $certificate->code,
                'obs' => $certificate->obs,
                'created_at' => $certificate->created_at,
            ]);

        return response()->json($data);
    }

    public function getPDF()
    {
        abort_if(! auth()->user()->can('view_quality_certificates') || ! auth()->user()->can('validate_quality_certificates'), 403, '');

        $model = QualityCertificate::with('collection', 'lab_code', 'user', 'customer', 'warehouse')->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildAnalysisReportPayload($model, app(GeneralSettings::class));
        $filename = $model->code.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('analysis', $payload, $filename);

        if (request()->q) {
            activity()->log('baixou o Boletim Analítico Nº '.$model->code);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        if (! request()->q) {
            activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Boletim Analítico Nº '.$model->code);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }
    }

    public function getApprove($id)
    {
        abort_if(! auth()->user()->can('validate_quality_certificates'), 403, '');

        return Inertia::modal('QualityCertificates/validation-modal', [
            'record' => QualityCertificateResource::make(
                QualityCertificate::with('customer', 'warehouse')
                    ->findOrFail($id)
            ),
            'title' => 'Validação do Boletim de Resultados',
            'action' => 'approve',
            'url' => route('qualitycertificates.approve', $id),
        ], route('qualitycertificates.show', $id));
    }

    public function approve($id)
    {
        abort_if(! auth()->user()->can('validate_quality_certificates'), 403, '');

        $validated = request()->validate([
            'signature' => ['required', 'string'],
        ]);

        $certificate = QualityCertificate::with('customer', 'warehouse')
            ->findOrFail($id);

        $certificate->update([
            'validated_by_id' => auth()->user()->id,
            'validated_by' => auth()->user()->name,
            'validated_at' => now(),
        ]);

        $certificate->addMediaFromBase64($validated['signature'])
            ->usingFileName(auth()->user()->id.'_signature.png')
            ->toMediaCollection('validation_signature');

        activity()
            ->by(auth()->user())
            ->performedOn($certificate)
            ->log('Validou o Boletim de Resultados Nº '.$certificate->certificate_no);

        return to_route('qualitycertificates.show', $id)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }
}
