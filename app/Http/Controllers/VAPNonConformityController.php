<?php

namespace App\Http\Controllers;

use App\Actions\ArchiveNonConformity;
use App\Actions\SaveNonConformity;
use App\Actions\TransitionNonConformity;
use App\Exports\NonConformitiesExport;
use App\Exports\NonConformityDetailsExport;
use App\Http\Requests\NonConformityFilterRequest;
use App\Http\Requests\SaveNonConformityRequest;
use App\Http\Requests\TransitionNonConformityRequest;
use App\Http\Resources\NonConformityResource;
use App\Models\Department;
use App\Models\VAPLab;
use App\Models\VAPNonConformity;
use App\Services\SampleLaboratoryAccess;
use App\Support\NonConformityLifecycleReport;
use App\Support\NonConformityQuery;
use App\Support\PdfResponse;
use App\Support\QualityModuleNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VAPNonConformityController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $access, private readonly NonConformityQuery $query) {}

    /**
     * Display a listing of the resource.
     */
    public function index(NonConformityFilterRequest $request): \Inertia\Response
    {
        $labId = $this->access->activeLabId();
        $filters = $request->validated();
        $query = $this->query->query($labId, $filters);

        return Inertia::render('VAPNonConformities/Index', [
            'nonConformities' => (clone $query)->with(['lab:id,name', 'department:id,name'])
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'can' => $this->abilities(),
            'stats' => $this->query->stats($query),
            'charts' => $this->query->charts($query),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $labId = $this->access->activeLabId();

        return Inertia::render('VAPNonConformities/Create', [
            'labs' => VAPLab::query()->whereKey($labId)->get(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
            'defaultNcNumber' => (new VAPNonConformity)->generateNcNumber($labId),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveNonConformityRequest $request, SaveNonConformity $save): RedirectResponse
    {
        $nonConformity = $save->execute($request->user()->id, $this->access->activeLabId(), $request->validated());
        $nonConformity->load(['assignedToUser', 'reportedByUser']);
        app(QualityModuleNotifier::class)->notifyNonConformityCreated($nonConformity);

        return to_route('vap_non_conformities.index')->with('success', 'Não conformidade criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VAPNonConformity $nonConformity)
    {
        $this->assertOwned($nonConformity);
        $nonConformity->load(['lab', 'department', 'actions' => fn ($query) => $query->withTrashed()->orderBy('id'), 'reportedByUser', 'assignedToUser', 'media']);

        return Inertia::render('VAPNonConformities/Show', [
            'can' => $this->abilities(),
            'nonConformity' => NonConformityResource::make($nonConformity)->resolve(),
            'labs' => VAPLab::query()->whereKey($nonConformity->lab_id)->get(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VAPNonConformity $nonConformity)
    {
        $this->assertOwned($nonConformity);
        $nonConformity->load(['lab', 'department', 'actions', 'media']);

        return Inertia::render('VAPNonConformities/Edit', [
            'nonConformity' => NonConformityResource::make($nonConformity)->resolve(),
            'labs' => VAPLab::query()->whereKey($nonConformity->lab_id)->get(['id', 'name']),
            'departments' => Department::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveNonConformityRequest $request, VAPNonConformity $nonConformity, SaveNonConformity $save): RedirectResponse
    {
        $before = $nonConformity->only(['status', 'severity']);
        $nonConformity = $save->execute($request->user()->id, $this->access->activeLabId(), $request->validated(), $nonConformity->id);
        $nonConformity->load(['assignedToUser', 'reportedByUser']);
        app(QualityModuleNotifier::class)->notifyNonConformityUpdated($nonConformity, $before);

        return to_route('vap_non_conformities.show', $nonConformity)->with('success', 'Não conformidade actualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, VAPNonConformity $nonConformity, ArchiveNonConformity $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->access->activeLabId(), $nonConformity->id);

        return to_route('vap_non_conformities.index')->with('success', 'Não conformidade arquivada. Histórico e anexos preservados.');
    }

    public function restore(Request $request, VAPNonConformity $nonConformity, ArchiveNonConformity $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->access->activeLabId(), $nonConformity->id, true);

        return to_route('vap_non_conformities.show', $nonConformity)->with('success', 'Não conformidade restaurada.');
    }

    private function abilities(): array
    {
        return collect(['create' => 'add', 'edit' => 'edit', 'archive' => 'delete', 'restore' => 'restore'])
            ->map(fn ($ability) => ! session()->has('impersonate') && auth()->user()->can($ability.'_occurrences'))
            ->merge(collect(['resolve', 'verify', 'close', 'reopen'])->mapWithKeys(fn ($ability) => [$ability => ! session()->has('impersonate') && auth()->user()->can($ability.'_non_conformities')]))->all();
    }

    public function transition(TransitionNonConformityRequest $request, VAPNonConformity $nonConformity, string $transition, TransitionNonConformity $action): RedirectResponse
    {
        $action->execute($request->user()->id, $this->access->activeLabId(), $nonConformity->id, $transition, $request->validated());

        return to_route('vap_non_conformities.show', $nonConformity)->with('success', 'Etapa registada. Histórico preservado.');
    }

    /**
     * Export non-conformities to Excel.
     */
    public function exportExcel(NonConformityFilterRequest $request): Response
    {
        $labId = $this->access->activeLabId();
        $filters = $request->validated();

        $fileName = 'non_conformities_'.now()->format('Y_m_d_His').'.xlsx';

        return $this->privateReport(Excel::download(new NonConformitiesExport($labId, $filters), $fileName));
    }

    /**
     * Export non-conformity details to Excel.
     */
    public function exportDetailsExcel(VAPNonConformity $nonConformity): Response
    {
        $this->assertOwned($nonConformity);
        $fileName = 'nc_details_'.$nonConformity->id.'_'.now()->format('Y_m_d_His').'.xlsx';

        return $this->privateReport(Excel::download(new NonConformityDetailsExport($nonConformity), $fileName));
    }

    /**
     * Export non-conformities to PDF.
     */
    public function exportPdf(NonConformityFilterRequest $request): Response
    {
        $labId = $this->access->activeLabId();
        $filters = $request->validated();

        $nonConformities = $this->query->exportRows($labId, $filters);

        $pdf = PDF::loadView('exports.non-conformities.pdf', [
            'nonConformities' => $nonConformities,
            'filters' => $filters,
            'labName' => VAPLab::findOrFail($labId)->name,
            'title' => 'Relatório de Não Conformidades',
            'exportDate' => now()->format('d/m/Y H:i:s'),
        ], [], ['format' => 'A4-L']);

        $fileName = 'non_conformities_'.now()->format('Y_m_d_His').'.pdf';

        return $this->privateReport(PdfResponse::download($pdf, $fileName));
    }

    /**
     * Export non-conformity details to PDF.
     */
    public function exportDetailsPdf(VAPNonConformity $nonConformity): Response
    {
        $this->assertOwned($nonConformity);
        $nonConformity->load(['lab', 'department', 'actions' => fn ($query) => $query->withTrashed()->where('lab_id', $nonConformity->lab_id)->orderBy('id'), 'reportedByUser', 'assignedToUser']);

        $pdf = PDF::loadView('exports.non-conformities.details-pdf', [
            'nonConformity' => $nonConformity,
            'lifecycleSummary' => NonConformityLifecycleReport::summary($nonConformity),
            'lifecycleHistory' => NonConformityLifecycleReport::history($nonConformity),
            'title' => 'Detalhes da Não Conformidade '.$nonConformity->nc_number,
            'exportDate' => now()->format('d/m/Y H:i:s'),
        ]);

        $fileName = 'nc_details_'.$nonConformity->id.'_'.now()->format('Y_m_d_His').'.pdf';

        return $this->privateReport(PdfResponse::download($pdf, $fileName));
    }

    private function privateReport(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function showAttachment(Request $request, VAPNonConformity $nonConformity, Media $media): StreamedResponse
    {
        $this->assertOwned($nonConformity);
        abort_unless($media->model_type === $nonConformity->getMorphClass()
            && (int) $media->model_id === (int) $nonConformity->id
            && $media->collection_name === 'attachments', 404);

        return $media->toResponse($request);
    }

    private function assertOwned(VAPNonConformity $nonConformity): void
    {
        abort_unless((int) $nonConformity->lab_id === $this->access->activeLabId(), 404);
    }
}
