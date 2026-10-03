<?php

namespace App\Http\Controllers;

use App\Actions\ValidateQualityCertificate;
use App\Http\Requests\QualityCertificateRequest;
use App\Http\Resources\QualityCertificateResource;
use App\Models\QualityCertificate;
use App\Models\QualityCertificateRevision;
use App\Models\VAPProposal;
use App\Models\VAPSampleEntry;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class QualityCertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_quality_certificates'), 403, '');

        $filters = request()->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'in:pending,validated,archived'],
        ]);
        $state = $filters['state'] ?? null;
        $search = $filters['search'] ?? null;

        return Inertia::render('QualityCertificates/Index', [
            'record' => QualityCertificateResource::collection(
                QualityCertificate::query()
                    ->with('lab_code', 'customer', 'warehouse', 'product', 'validated_by_user')
                    ->when($search, function ($query, $search) {
                        $query->where(fn ($query) => $query->whereLike('code', "%{$search}%")
                            ->orWhereRelation('lab_code', 'code', 'ilike', "%{$search}%")
                            ->orWhereRelation('warehouse', 'name', 'ilike', "%{$search}%")
                            ->orWhereRelation('warehouse', 'address', 'ilike', "%{$search}%")
                            ->orWhereRelation('product', 'name', 'ilike', "%{$search}%")
                            ->orWhereRelation('customer', 'name', 'ilike', "%{$search}%"));
                    })
                    ->when($state === 'pending', fn ($query) => $query->whereNull('validated_at'))
                    ->when($state === 'validated', fn ($query) => $query->whereNotNull('validated_at'))
                    ->when($state === 'archived', fn ($query) => $query->onlyTrashed())
                    ->orderByRaw('validated_at IS NOT NULL')
                    ->latest()
                    ->paginate(25)
                    ->withQueryString()
            ),
            'counts' => [
                'all' => QualityCertificate::query()->count(),
                'pending' => QualityCertificate::query()->whereNull('validated_at')->count(),
                'validated' => QualityCertificate::query()->whereNotNull('validated_at')->count(),
                'archived' => QualityCertificate::onlyTrashed()->count(),
            ],
            'filters' => ['search' => $search, 'state' => $state],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id, ValidateQualityCertificate $validation)
    {
        abort_if(! auth()->user()->can('view_quality_certificates'), 403, '');

        $labId = (int) request()->attributes->get('proposal_laboratory_id');
        $certificate = QualityCertificate::query()
            ->with(['currentRevision' => function ($query) {
                $query->select(['id', 'quality_certificate_id', 'version', 'revision_number']);
            }, 'customer', 'warehouse', 'product', 'lab_code', 'user', 'validated_by_user', 'validated_on_behalf_of_user'])
            ->findOrFail($id);
        $entry = VAPSampleEntry::query()
            ->where('lab_id', $labId)
            ->where('collection_product_id', $certificate->collection_id)
            ->first(['id', 'code', 'proposal_id', 'collection_product_id']);
        $proposal = $entry?->proposal_id ? VAPProposal::query()->find($entry->proposal_id) : null;

        return Inertia::render('QualityCertificates/Show', [
            'record' => QualityCertificateResource::make($certificate),
            'release' => [
                ...$validation->readiness($labId, $certificate),
                'revision' => $certificate->currentRevision?->version,
                'sample' => $entry ? ['code' => $entry->code, 'url' => route('vap_samples.show', $entry->id)] : null,
                'proposal' => $proposal ? ['code' => $proposal->proposal_number, 'url' => route('vap-proposals.show', $proposal->id)] : null,
            ],
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
     * Show the form for editing the specified resource. Identity comes from the
     * accession and stays fixed; only the observation is edited, and only until the
     * certificate is validated.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_quality_certificates'), 403, '');

        $record = QualityCertificate::query()->with('customer', 'warehouse', 'product', 'lab_code')->findOrFail($id);

        if ($record->validated_at !== null) {
            return to_route('qualitycertificates.show', $record->id)->with('toast', [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Este boletim já foi validado. Correcções seguem o fluxo de revisão ISO.',
            ]);
        }

        return Inertia::render('QualityCertificates/Edit', [
            'record' => QualityCertificateResource::make($record),
        ]);
    }

    /**
     * Update the observation of a certificate that has not been validated yet.
     */
    public function update(QualityCertificateRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_quality_certificates'), 403, '');

        DB::transaction(function () use ($request, $id): void {
            $record = QualityCertificate::query()->lockForUpdate()->findOrFail($id);

            if ($record->validated_at !== null) {
                throw ValidationException::withMessages(['obs' => 'Este boletim já foi validado. Correcções seguem o fluxo de revisão ISO.']);
            }

            $record->update(['obs' => $request->validated('obs')]);
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
        abort_if(! auth()->user()->can('delete_quality_certificates'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // A validated certificate is an issued document: it is never archived.
        DB::transaction(function (): void {
            $records = QualityCertificate::query()->lockForUpdate()->findOrFail(request('recordIds'));

            if ($records->contains(fn (QualityCertificate $record): bool => $record->validated_at !== null)) {
                throw ValidationException::withMessages(['recordIds' => 'Um boletim validado não pode ser arquivado.']);
            }

            $records->each->delete();
        });

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
        DB::transaction(function (): void {
            QualityCertificate::onlyTrashed()->lockForUpdate()->findOrFail(request('recordIds'))->each->restore();
        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getQualityCertificate(): JsonResponse
    {
        abort_if(! auth()->user()->can('view_quality_certificates'), 403, '');

        $search = request()->string('q')->trim()->toString();

        $data = QualityCertificate::query()
            ->select(['id', 'code', 'obs', 'created_at'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('code', "%{$search}%")
                        ->orWhereLike('obs', "%{$search}%");
                });
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (QualityCertificate $certificate): array => [
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

    public function getApprove($id, ValidateQualityCertificate $validation)
    {
        abort_if(! auth()->user()->can('validate_quality_certificates'), 403, '');

        $labId = (int) request()->attributes->get('proposal_laboratory_id');
        $certificate = QualityCertificate::query()->with('customer', 'warehouse', 'product', 'lab_code')->findOrFail($id);

        return Inertia::modal('QualityCertificates/validation-modal', [
            'record' => QualityCertificateResource::make($certificate),
            'title' => 'Validação do Boletim de Resultados',
            'action' => 'approve',
            'url' => route('qualitycertificates.approve', $id),
            'release' => $validation->readiness($labId, $certificate),
            'results' => $validation->resultsForRelease($labId, $certificate),
        ], route('qualitycertificates.show', $id));
    }

    public function approve($id, ValidateQualityCertificate $validate)
    {
        abort_if(! auth()->user()->can('validate_quality_certificates'), 403, '');

        $validated = request()->validate([
            'signature' => ['required', 'string', 'max:2000000'],
            'approve_on_behalf_of' => ['nullable', 'boolean'],
            'signed_by_user_id' => ['nullable', 'required_if_accepted:approve_on_behalf_of', 'integer'],
        ], [
            'signed_by_user_id.required_if_accepted' => 'Indique o validador em nome de quem assina.',
        ]);
        $onBehalfOfId = request()->boolean('approve_on_behalf_of') ? (int) $validated['signed_by_user_id'] : null;

        $validate->execute((int) request()->attributes->get('proposal_laboratory_id'), (int) auth()->id(), (int) $id, $validated['signature'], $onBehalfOfId);

        return to_route('qualitycertificates.show', $id)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Boletim validado e assinado.',
            ],
        ]);
    }
}
