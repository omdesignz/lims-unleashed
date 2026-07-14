<?php

namespace App\Http\Controllers;

use App\Models\VAPProposal;
use App\Support\AnalysisReportService;
use App\Support\LaboratoryDossierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LaboratoryWorkflowController extends Controller
{
    public function index(Request $request, LaboratoryDossierService $dossierService): Response
    {
        $this->authorizeWorkflow($request);

        $search = $request->string('search')->trim()->toString();
        $requestedStage = $request->string('stage')->toString();
        $stage = array_key_exists($requestedStage, $dossierService->stageOptions()) ? $requestedStage : '';

        $dossiers = VAPProposal::query()
            ->accepted()
            ->with($dossierService->relations())
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('proposal_no', 'like', "%{$search}%")
                        ->orWhere('service_location', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at')
            ->limit(250)
            ->get()
            ->map(fn (VAPProposal $proposal): array => $dossierService->summarize($proposal, $request->user()));

        $stats = [
            'total' => $dossiers->count(),
            'awaiting_samples' => $dossiers->where('stage.key', 'awaiting_samples')->count(),
            'in_execution' => $dossiers->whereIn('stage.key', ['intake', 'results', 'verification', 'approval'])->count(),
            'awaiting_release' => $dossiers->whereIn('stage.key', ['report_generation', 'report_validation'])->count(),
            'issued' => $dossiers->where('stage.key', 'issued')->count(),
        ];

        if ($stage !== '') {
            $dossiers = $dossiers->where('stage.key', $stage)->values();
        }

        return Inertia::render('LaboratoryWorkflow/Index', [
            'dossiers' => $dossiers->values(),
            'stats' => $stats,
            'filters' => ['search' => $search, 'stage' => $stage],
            'stageOptions' => collect($dossierService->stageOptions())->map(
                fn (string $label, string $value): array => compact('value', 'label')
            )->values(),
        ]);
    }

    public function storeReports(
        Request $request,
        VAPProposal $proposal,
        AnalysisReportService $analysisReportService
    ): RedirectResponse {
        $this->authorizeWorkflow($request);
        abort_unless(
            $request->user()?->can('view_quality_certificates')
            || $request->user()?->can('approve_results'),
            403
        );
        abort_unless($proposal->status === 'ACCEPTED', 409, 'A proposta ainda não foi aceite.');

        $reports = $analysisReportService->ensureForProposal($proposal, (int) $request->user()->id);

        if ($reports->isEmpty()) {
            return back()->with([
                'message' => 'O boletim só pode ser gerado quando todos os resultados estiverem aprovados.',
                'type' => 'warning',
            ]);
        }

        if ($reports->count() === 1) {
            return to_route('qualitycertificates.show', $reports->first())->with([
                'message' => 'Boletim gerado. Reveja e valide o documento para concluir a emissão.',
                'type' => 'success',
            ]);
        }

        return to_route('laboratory-workflow.index')->with([
            'message' => $reports->count().' boletins foram gerados e aguardam validação.',
            'type' => 'success',
        ]);
    }

    private function authorizeWorkflow(Request $request): void
    {
        abort_unless(
            $request->user()?->can('view_proposals')
            || $request->user()?->can('view_samples')
            || $request->user()?->can('view_analysis')
            || $request->user()?->can('view_quality_certificates'),
            403
        );
    }
}
