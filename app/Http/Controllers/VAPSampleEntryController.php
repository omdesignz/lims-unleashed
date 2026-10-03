<?php

// app/Http/Controllers/SampleEntryController.php

namespace App\Http\Controllers;

use App\Actions\PrepareSampleEntryPayload;
use App\Actions\UpdateSampleEntry;
use App\Exports\VAPSampleEntriesTemplateExport;
use App\Http\Requests\VAP\StoreSampleEntryRequest;
use App\Http\Requests\VAP\UpdateInternalQualityControlDecisionRequest;
use App\Http\Resources\SampleIntakeResource;
use App\Imports\VAPSampleEntriesImport;
use App\Models\Analysis;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\PackagingCategory;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\QualityCertificate;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\SampleLaboratoryAccess;
use App\Settings\GeneralSettings;
use App\Support\LaboratoryWorkflowNotifier;
use App\Support\NotificationTemplateService;
use App\Support\PdfResponse;
use App\Support\PersonnelQualificationGate;
use App\Support\SampleEntryCollectionFlowService;
use App\Support\SampleEntryValidation;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VAPSampleEntryController extends Controller
{
    /** The discard picker lists the longest-retained eligible samples first. */
    private const DISCARD_PICKER_LIMIT = 200;

    public function __construct(
        private readonly NotificationTemplateService $notificationTemplates,
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly PrepareSampleEntryPayload $preparePayload,
        private readonly SampleEntryValidation $sampleEntryValidation,
    ) {}

    private function buildInternalQualityControlPath(): array
    {
        $microbiologyDepartment = $this->findDepartmentByKeywords(['microbiologia', 'microbiology', 'micro']);
        $chemistryDepartment = $this->findDepartmentByKeywords(['quimica', 'química', 'chemistry', 'físico', 'fisico']);

        return [
            'title' => 'Controlo interno de matéria-prima',
            'description' => 'Recepção interna de matéria-prima para microbiologia e química, com integração automática no fluxo normal de análise.',
            'requires_proposal' => false,
            'sample_type' => 'MATERIA_PRIMA',
            'request_origin' => 'internal',
            'retention_period_days' => VAPSampleEntry::defaultRetentionPeriodFor('MATERIA_PRIMA'),
            'presets' => [
                'microbiology' => [
                    'label' => 'Matéria-prima - Microbiologia',
                    'discipline' => 'microbiology',
                    'department_id' => $microbiologyDepartment?->id,
                    'requested_services' => 'Controlo microbiológico interno de matéria-prima',
                ],
                'chemistry' => [
                    'label' => 'Matéria-prima - Química',
                    'discipline' => 'chemistry',
                    'department_id' => $chemistryDepartment?->id,
                    'requested_services' => 'Controlo físico-químico interno de matéria-prima',
                ],
            ],
            'workflow' => [
                'Registar como origem interna e tipo MATERIA_PRIMA.',
                'Seleccionar produto, matriz e perfis analíticos de microbiologia/química.',
                'Validar recepção, condicionamento, lote, fornecedor e decisão de libertação.',
                'Gerar automaticamente o código laboratorial, as amostras internas e as análises no fluxo normal.',
                'Inserir resultados, verificar, aprovar e emitir certificado/relatório quando aplicável.',
            ],
        ];
    }

    private function findDepartmentByKeywords(array $keywords): ?Department
    {
        return Department::query()
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'like', '%'.$keyword.'%')
                        ->orWhere('code', 'like', '%'.$keyword.'%');
                }
            })
            ->orderBy('name')
            ->first();
    }

    /**
     * Display the sample entry interface
     */
    public function index(Request $request)
    {
        $proposalPrefill = $request->integer('proposal_id')
            ? Proposal::query()
                ->accepted()
                ->with(['customer:id,name', 'warehouse:id,address', 'department:id,name'])
                ->find($request->integer('proposal_id'))
            : null;

        $discardable = fn () => $this->laboratoryAccess->samples()->discardable()
            ->whereDoesntHave('discards')
            ->where(fn (Builder $query) => $query->whereNull('retention_status')->orWhere('retention_status', '!=', 'discarded'))
            ->with(['customer', 'lab', 'department']);
        $discardableSamples = $discardable()->orderBy('received_at')->orderBy('id')->limit(self::DISCARD_PICKER_LIMIT)->get();
        if ($request->integer('discard') && ! $discardableSamples->contains('id', $request->integer('discard'))) {
            $discardableSamples->push(...$discardable()->whereKey($request->integer('discard'))->get());
        }
        $discardableSamples = $discardableSamples
            ->map(function ($sample) {
                return [
                    'id' => $sample->id,
                    'name' => $sample->name,
                    'code' => $sample->code,
                    'status' => $sample->status,
                    'sample_type' => $sample->sample_type,
                    'received_at' => $sample->received_at,
                    'customer' => $sample->customer ? $sample->customer->only(['id', 'name']) : null,
                    'lab_id' => $sample->lab_id,
                    'department_id' => $sample->department_id,
                ];
            });

        $recentDiscards = $this->laboratoryAccess->discards()->with(['sample' => fn (BelongsTo $query) => $query->withTrashed(), 'discardedBy'])
            ->recent(7)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($discard) {
                return [
                    'id' => $discard->id,
                    'sample_id' => $discard->sample_id,
                    'sample' => $discard->sample ? $discard->sample->only(['id', 'name', 'code']) : null,
                    'discard_method' => $discard->discard_method,
                    'qty' => $discard->qty,
                    'discarded_at' => $discard->discarded_at,
                    'discarded_by' => $discard->discardedBy ? $discard->discardedBy->only(['id', 'name']) : null,
                ];
            });

        $stats = [
            'total_samples' => $this->laboratoryAccess->samples()->count(),
            'pending_analysis' => $this->laboratoryAccess->samples()->pending()->count(),
            'in_progress' => $this->laboratoryAccess->samples()->inProgress()->count(),
            'completed_analysis' => $this->laboratoryAccess->samples()->completed()->count(),
            'total_discarded' => $this->laboratoryAccess->discards()->count(),
            'discarded_this_month' => $this->laboratoryAccess->discards()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'today_samples' => $this->laboratoryAccess->samples()->whereDate('created_at', today())->count(),
            'week_samples' => $this->laboratoryAccess->samples()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'internal_qc_samples' => $this->laboratoryAccess->samples()->internalRawMaterialQualityControl()->count(),
            'raw_material_samples' => $this->laboratoryAccess->samples()
                ->whereIn('sample_type', ['MATERIA_PRIMA', 'RAW_MATERIAL'])
                ->count(),
        ];

        $editingSample = $request->integer('edit')
            ? $this->laboratoryAccess->samples()->with(['customer', 'lab', 'department', 'warehouse'])->findOrFail($request->integer('edit'))
            : null;

        return Inertia::render('VAPSamples/Index', [
            'title' => 'Gestão de Amostras',
            'stats' => $stats,
            'internalQualityControlPath' => $this->buildInternalQualityControlPath(),
            'entryWorkflowDefaults' => [
                'collection_type' => $request->string('collection_type')->value() === 'programmed' ? 'programmed' : 'direct',
                'open_form' => (bool) $proposalPrefill && $request->boolean('start', true),
                'proposal' => $proposalPrefill ? [
                    'id' => $proposalPrefill->id,
                    'proposal_no' => $proposalPrefill->proposal_no,
                    'customer_id' => $proposalPrefill->customer_id,
                    'customer' => $proposalPrefill->customer?->name,
                    'warehouse_id' => $proposalPrefill->warehouse_id,
                    'warehouse' => $proposalPrefill->warehouse?->address,
                    'department_id' => $proposalPrefill->department_id,
                    'department' => $proposalPrefill->department?->name,
                    'service_location' => $proposalPrefill->service_location,
                ] : null,
            ],
            'editingSample' => $editingSample ? SampleIntakeResource::make($editingSample)->resolve($request) : null,
            'discardPickerLimit' => self::DISCARD_PICKER_LIMIT,
            'discardableSamples' => $discardableSamples,
            'recentDiscards' => $recentDiscards,
            'customers' => Customer::select('id', 'name', 'code')->orderBy('name')->get(),
            'acceptedProposals' => Proposal::query()
                ->accepted()
                ->with(['customer:id,name', 'warehouse:id,address'])
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->map(fn (Proposal $proposal) => [
                    'id' => $proposal->id,
                    'proposal_no' => $proposal->proposal_no,
                    'customer_id' => $proposal->customer_id,
                    'customer' => $proposal->customer?->name,
                    'warehouse_id' => $proposal->warehouse_id,
                    'warehouse' => $proposal->warehouse?->address,
                    'department_id' => $proposal->department_id,
                    'status' => $proposal->status instanceof \BackedEnum
                        ? $proposal->status->value
                        : (string) $proposal->status,
                    'total' => $proposal->total,
                    'updated_at' => optional($proposal->updated_at)?->toIso8601String(),
                ]),
            'portalAnalysisRequests' => $this->pendingPortalAnalysisRequests(),
            'products' => Product::query()
                ->with([
                    'matrix:id,description',
                    'matrix.profiles:id,name,category_id',
                    'matrix.profiles.type:id,name,department_id',
                    'matrix.profiles.parameters:id,name,code',
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'matrix_id'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'matrix_id' => $product->matrix_id,
                    'matrix' => $product->matrix?->description,
                    'profiles' => $product->matrix?->profiles
                        ? $product->matrix->profiles->map(fn (Profile $profile) => [
                            'id' => $profile->id,
                            'name' => $profile->name,
                            'category_id' => $profile->category_id,
                            'analysis_type' => $profile->type?->name,
                            'department_id' => $profile->type?->department_id,
                            'parameter_count' => $profile->parameters->unique('id')->count(),
                            'parameters' => $profile->parameters
                                ->unique('id')
                                ->sortBy('name')
                                ->values()
                                ->map(fn ($parameter) => [
                                    'id' => $parameter->id,
                                    'name' => $parameter->name,
                                    'code' => $parameter->code,
                                ]),
                        ])->values()
                        : [],
                ]),
            'profiles' => Profile::query()
                ->with('type:id,name,department_id')
                ->orderBy('name')
                ->get(['id', 'name', 'category_id'])
                ->map(fn (Profile $profile) => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'category_id' => $profile->category_id,
                    'analysis_type' => $profile->type?->name,
                    'department_id' => $profile->type?->department_id,
                ]),
            'matrixes' => Matrix::query()->orderBy('description')->get(['id', 'description']),
            'labs' => VAPLab::whereKey($this->laboratoryAccess->activeLabId())->get(['id', 'name', 'code']),
            'departments' => Department::select('id', 'name', 'code')->orderBy('name')->get(),
            'warehouses' => Warehouse::select('id', 'name', 'address', 'code')->orderBy('name')->get(),
            'packagingCategories' => PackagingCategory::select('id', 'name', 'description')->orderBy('name')->get(),
        ]);
    }

    public function show(VAPSampleEntry $sampleEntry)
    {
        $sampleEntry->load([
            'customer',
            'lab',
            'department',
            'warehouse',
            'packaging',
            'receivedBy',
            'proposal.customer',
            'collectionProduct.product',
            'collectionProduct.code',
            'collectionProduct.quality_certificate',
            'customerRequest',
            'discards.discardedBy',
        ]);

        $linkedSampleIds = $this->linkedSampleIdsFor($sampleEntry);
        $analyses = $this->buildLinkedAnalysisSummaries($linkedSampleIds);

        $qualityCertificate = $sampleEntry->collectionProduct?->quality_certificate;
        $qualityControlRelease = $this->buildQualityControlReleaseGate($sampleEntry, $analyses);
        $analysisQueueCategory = $this->resolveSampleEntryAnalysisQueueCategory($analyses);
        $workflowLinks = [
            'collection_workflow_url' => $this->collectionProductWorkflowUrl($sampleEntry),
            'analysis_queue_url' => route('analysis.index', ['category' => $analysisQueueCategory]),
            'counter_analysis_url' => route('counteranalysis.index'),
            'quality_certificate_show_url' => $qualityCertificate ? route('qualitycertificates.show', $qualityCertificate) : null,
            'quality_certificate_pdf_url' => $qualityCertificate ? route('qualitycertificates.getPDF', ['id' => $qualityCertificate->id]) : null,
        ];

        return Inertia::render('VAPSamples/Show', [
            'sample' => [
                'id' => $sampleEntry->id,
                'name' => $sampleEntry->name,
                'code' => $sampleEntry->code,
                'sample_type' => $sampleEntry->sample_type,
                'status' => $sampleEntry->status,
                'received_at' => optional($sampleEntry->received_at)?->toIso8601String(),
                'analysis_start_date' => optional($sampleEntry->analysis_start_date)?->toIso8601String(),
                'analysis_end_date' => optional($sampleEntry->analysis_end_date)?->toIso8601String(),
                'requested_services' => $sampleEntry->requested_services,
                'obs' => $sampleEntry->obs,
                'retention_period_days' => $sampleEntry->retention_period_days,
                'retention_due_at' => optional($sampleEntry->retention_due_at)?->toDateString(),
                'discard_scheduled_at' => optional($sampleEntry->discard_scheduled_at)?->toDateString(),
                'retention_status' => $sampleEntry->retention_status,
                'client_submitted_info' => $sampleEntry->client_submitted_info ?? [],
                'customer' => $sampleEntry->customer?->only(['id', 'name', 'code']),
                'lab' => $sampleEntry->lab?->only(['id', 'name', 'code']),
                'department' => $sampleEntry->department?->only(['id', 'name', 'code']),
                'warehouse' => $sampleEntry->warehouse?->only(['id', 'name', 'code', 'address']),
                'packaging' => $sampleEntry->packaging?->only(['id', 'name', 'description']),
                'received_by' => $sampleEntry->receivedBy?->only(['id', 'name']),
                'proposal' => $sampleEntry->proposal ? [
                    'id' => $sampleEntry->proposal->id,
                    'proposal_no' => $sampleEntry->proposal->proposal_no,
                    'status' => $sampleEntry->proposal->status,
                    'customer' => $sampleEntry->proposal->customer?->name,
                ] : null,
                'portal_request' => $sampleEntry->customerRequest ? [
                    'id' => $sampleEntry->customerRequest->id,
                    'reference' => $sampleEntry->customerRequest->reference,
                    'title' => $sampleEntry->customerRequest->title,
                    'status' => $sampleEntry->customerRequest->status,
                ] : null,
                'collection_product' => $sampleEntry->collectionProduct ? [
                    'id' => $sampleEntry->collectionProduct->id,
                    'code' => $sampleEntry->collectionProduct->code?->code,
                    'workflow_url' => $workflowLinks['collection_workflow_url'],
                    'product' => $sampleEntry->collectionProduct->product?->name,
                ] : null,
                'quality_certificate' => $qualityCertificate ? [
                    'id' => $qualityCertificate->id,
                    'code' => $qualityCertificate->code,
                    'show_url' => $workflowLinks['quality_certificate_show_url'],
                    'pdf_url' => $workflowLinks['quality_certificate_pdf_url'],
                ] : null,
                'workflow_links' => $workflowLinks,
                'discards' => $sampleEntry->discards->map(fn ($discard) => [
                    'id' => $discard->id,
                    'discard_method' => $discard->discard_method,
                    'qty' => $discard->qty,
                    'discarded_at' => optional($discard->discarded_at)?->toIso8601String(),
                    'discarded_by' => $discard->discardedBy?->name,
                ])->values(),
            ],
            'analyses' => $analyses,
            'linkedSampleIds' => $linkedSampleIds,
            'workflowSummary' => [
                'collection_product_id' => $sampleEntry->collection_product_id,
                'linked_lab_code_id' => data_get($sampleEntry->client_submitted_info, 'linked_lab_code_id'),
                'linked_lab_code' => $sampleEntry->collectionProduct?->code?->code,
                'linked_sample_count' => $linkedSampleIds->count(),
                'analysis_count' => $analyses->count(),
                'counter_analysis_count' => $analyses->filter(fn ($analysis) => (bool) $analysis['counter_analysis_id'])->count(),
                'quality_certificate_ready' => (bool) $qualityCertificate,
                'quality_control_release' => $qualityControlRelease,
                'analysis_queue_category' => $analysisQueueCategory,
                'next_action' => $this->buildSampleEntryNextAction($analysisQueueCategory, $qualityCertificate, $analyses),
                'links' => $workflowLinks,
            ],
        ]);
    }

    /**
     * @return Collection<int, int>
     */
    private function linkedSampleIdsFor(VAPSampleEntry $sampleEntry): Collection
    {
        return $sampleEntry->collectionProduct?->samples()->pluck('samples.id') ?? collect();
    }

    /**
     * @param  Collection<int, int>  $linkedSampleIds
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLinkedAnalysisSummaries(Collection $linkedSampleIds): Collection
    {
        return Analysis::query()
            ->with(['profile:id,name', 'department:id,name', 'type:id,name', 'sample.results.counter_analysis', 'sample.results.parameter:id,code,name'])
            ->when(
                $linkedSampleIds->isNotEmpty(),
                fn ($query) => $query->whereIn('sample_id', $linkedSampleIds->all()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('id')
            ->get()
            ->map(function (Analysis $analysis) {
                $results = collect($analysis->sample?->results ?? []);
                $result = $results->sortByDesc('id')->first();
                $totalResults = $results->count();
                $insertedCount = $results->whereNotNull('inserted_date')->count();
                $verifiedCount = $results->whereNotNull('verified_date')->count();
                $approvedCount = $results->whereNotNull('approved_date')->count();
                $counterAnalysisItems = $results
                    ->filter(fn ($item) => (bool) $item->requested_counter_analysis || $item->counter_analysis)
                    ->map(fn ($item) => [
                        'result_id' => $item->id,
                        'parameter' => $item->parameter?->code ?? $item->parameter?->name,
                        'status' => $item->counter_analysis ? 'opened' : 'requested',
                        'counter_analysis_id' => $item->counter_analysis?->id,
                        'counter_analysis_url' => $item->counter_analysis ? route('counteranalysis.edit', $item->counter_analysis->id) : null,
                    ])
                    ->values();
                $counterAnalysis = $counterAnalysisItems->firstWhere('counter_analysis_id');

                return [
                    'id' => $analysis->id,
                    'sample_id' => $analysis->sample_id,
                    'profile' => $analysis->profile?->name,
                    'department' => $analysis->department?->name,
                    'type' => $analysis->type?->name,
                    'status' => $analysis->end_date ? 'Arquivada' : 'Activa',
                    'entry_date' => optional($analysis->entry_date)?->format('Y-m-d'),
                    'analysis_url' => route('analysis.edit', $analysis),
                    'result_id' => $result?->id,
                    'workflow_stage' => $this->resolveAnalysisResultStage($totalResults, $insertedCount, $verifiedCount, $approvedCount),
                    'results_summary' => [
                        'total' => $totalResults,
                        'inserted' => $insertedCount,
                        'verified' => $verifiedCount,
                        'approved' => $approvedCount,
                        'pending_insertion' => max($totalResults - $insertedCount, 0),
                        'pending_verification' => max($insertedCount - $verifiedCount, 0),
                        'pending_approval' => max($verifiedCount - $approvedCount, 0),
                        'with_uncertainty' => $results->whereNotNull('uncertainty_value')->count(),
                        'without_uncertainty' => $results->whereNull('uncertainty_value')->count(),
                        'counter_analysis_requested' => $counterAnalysisItems->count(),
                    ],
                    'counter_analysis_items' => $counterAnalysisItems,
                    'counter_analysis_id' => data_get($counterAnalysis, 'counter_analysis_id'),
                    'counter_analysis_url' => data_get($counterAnalysis, 'counter_analysis_url'),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $analyses
     */
    private function resolveSampleEntryAnalysisQueueCategory(Collection $analyses): string
    {
        if ($analyses->isEmpty()) {
            return 'insert';
        }

        $stages = $analyses->pluck('workflow_stage');

        if ($stages->contains(fn ($stage) => in_array($stage, ['pending_results', 'insertion'], true))) {
            return 'insert';
        }

        if ($stages->contains('verification')) {
            return 'verify';
        }

        if ($stages->contains('approval')) {
            return 'approve';
        }

        return 'archived';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $analyses
     * @return array<string, mixed>
     */
    private function buildSampleEntryNextAction(string $analysisQueueCategory, ?QualityCertificate $qualityCertificate, Collection $analyses): array
    {
        if (! $qualityCertificate && $analyses->isNotEmpty() && $analysisQueueCategory === 'archived') {
            return [
                'label' => 'Preparar certificado',
                'description' => 'Resultados aprovados. Verifique o boletim analítico e emita o certificado quando aplicável.',
                'url' => route('qualitycertificates.index'),
                'type' => 'certificate',
            ];
        }

        if ($qualityCertificate) {
            return [
                'label' => 'Abrir certificado emitido',
                'description' => 'O certificado já foi emitido para esta entrada.',
                'url' => route('qualitycertificates.show', $qualityCertificate),
                'type' => 'certificate_ready',
            ];
        }

        return [
            'label' => match ($analysisQueueCategory) {
                'verify' => 'Verificar resultados',
                'approve' => 'Validar resultados',
                'archived' => 'Rever análises concluídas',
                default => 'Inserir resultados',
            },
            'description' => 'Abrir a fila operacional correspondente ao estado actual desta entrada.',
            'url' => route('analysis.index', ['category' => $analysisQueueCategory]),
            'type' => 'analysis',
        ];
    }

    private function resolveAnalysisResultStage(int $totalResults, int $insertedCount, int $verifiedCount, int $approvedCount): string
    {
        if ($totalResults === 0) {
            return 'pending_results';
        }

        if ($insertedCount < $totalResults) {
            return 'insertion';
        }

        if ($verifiedCount < $totalResults) {
            return 'verification';
        }

        if ($approvedCount < $totalResults) {
            return 'approval';
        }

        return 'approved';
    }

    private function isInternalQualityControlSample(VAPSampleEntry $sampleEntry): bool
    {
        $clientInfo = $sampleEntry->client_submitted_info ?? [];

        return data_get($clientInfo, 'request_origin') === 'internal'
            && in_array(strtoupper((string) $sampleEntry->sample_type), ['MATERIA_PRIMA', 'RAW_MATERIAL'], true);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $analyses
     * @return array<string, mixed>
     */
    private function buildQualityControlReleaseGate(VAPSampleEntry $sampleEntry, Collection $analyses): array
    {
        $clientInfo = $sampleEntry->client_submitted_info ?? [];

        if (! $this->isInternalQualityControlSample($sampleEntry)) {
            return [
                'applies' => false,
                'status' => 'not_applicable',
                'label' => 'Não aplicável',
                'message' => 'Esta entrada não é um procedimento interno de CQ de matéria-prima.',
                'current_decision' => null,
                'history' => [],
            ];
        }

        $totalResults = $analyses->sum(fn (array $analysis) => (int) data_get($analysis, 'results_summary.total', 0));
        $approvedResults = $analyses->sum(fn (array $analysis) => (int) data_get($analysis, 'results_summary.approved', 0));
        $insertedResults = $analyses->sum(fn (array $analysis) => (int) data_get($analysis, 'results_summary.inserted', 0));
        $verifiedResults = $analyses->sum(fn (array $analysis) => (int) data_get($analysis, 'results_summary.verified', 0));
        $counterAnalysisCount = $analyses->sum(fn (array $analysis) => (int) data_get($analysis, 'results_summary.counter_analysis_requested', 0));
        $uncertaintyCount = $analyses->sum(fn (array $analysis) => (int) data_get($analysis, 'results_summary.with_uncertainty', 0));
        $decision = data_get($clientInfo, 'qc_decision', 'hold_until_release');
        $currentDecision = data_get($clientInfo, 'qc_release');
        $decisionHistory = collect(data_get($clientInfo, 'qc_release_history', []))
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->all();

        if ($analyses->isEmpty() || $totalResults === 0) {
            $status = 'pending_results';
            $label = 'Aguardar resultados';
            $message = 'A matéria-prima continua retida até existirem resultados ligados às análises criadas.';
        } elseif ($approvedResults < $totalResults) {
            $status = 'awaiting_approval';
            $label = 'Aguardar verificação/aprovação';
            $message = 'A libertação só deve avançar quando todos os resultados estiverem verificados e aprovados.';
        } elseif ($decision === 'investigate_before_release' || $counterAnalysisCount > 0) {
            $status = 'requires_review';
            $label = 'Revisão técnica necessária';
            $message = 'Existem indicações de investigação ou contra-análise antes da decisão final de libertação.';
        } else {
            $status = 'ready_for_release';
            $label = 'Pronto para decisão de libertação';
            $message = $decision === 'release_if_compliant'
                ? 'Todos os resultados estão aprovados; a matéria-prima pode ser liberada se estiver conforme.'
                : 'Todos os resultados estão aprovados; a equipa pode registar a decisão final de libertação.';
        }

        return [
            'applies' => true,
            'status' => $status,
            'label' => $label,
            'message' => $message,
            'decision' => $decision,
            'can_release' => $status === 'ready_for_release',
            'requires_full_approval' => true,
            'current_decision' => $currentDecision,
            'history' => $decisionHistory,
            'totals' => [
                'analyses' => $analyses->count(),
                'results' => $totalResults,
                'inserted' => $insertedResults,
                'verified' => $verifiedResults,
                'approved' => $approvedResults,
                'with_uncertainty' => $uncertaintyCount,
                'counter_analysis_requested' => $counterAnalysisCount,
            ],
        ];
    }

    /**
     * Store a newly created sample entry
     */
    public function store(
        StoreSampleEntryRequest $request,
        SampleEntryCollectionFlowService $sampleEntryCollectionFlowService,
        LaboratoryWorkflowNotifier $workflowNotifier
    ) {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $sample = $this->createSampleEntryFromPayload(
                $validated,
                $sampleEntryCollectionFlowService,
                $workflowNotifier
            );

            DB::commit();

            return redirect()->back()->with([
                'message' => 'A amostra foi registada com sucesso.',
                'type' => 'success',
                'sample_id' => $sample->id,
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Failed to create sample entry.', [
                'exception' => $exception,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with([
                'message' => 'Não foi possível registar a amostra. Tente novamente.',
                'type' => 'error',
            ]);
        }
    }

    public function bulkStore(
        Request $request,
        SampleEntryCollectionFlowService $sampleEntryCollectionFlowService,
        LaboratoryWorkflowNotifier $workflowNotifier
    ): RedirectResponse {
        $validated = $request->validate([
            'samples' => ['required', 'array', 'min:1', 'max:50'],
            'samples.*' => ['required', 'array'],
        ]);

        $createdSamples = collect();
        $payloads = collect($validated['samples'])->values()->map(function (array $samplePayload, int $index): array {
            $payload = $this->normalizeManualBatchSamplePayload($samplePayload);
            try {
                $payload = $this->validateImportedSampleEntryPayload($payload, $index + 1);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    'samples' => Str::replaceFirst('Linha', 'Amostra', $exception->errors()['file'][0] ?? 'A fila manual contém uma amostra inválida.'),
                ]);
            }

            $payload['client_submitted_info'] = array_replace($payload['client_submitted_info'] ?? [], [
                'manual_batch' => true,
                'manual_batch_registered_at' => now()->toIso8601String(),
                'manual_batch_registered_by_id' => auth()->id(),
            ]);

            return $payload;
        });

        $this->ensureUniqueBatchCodes($payloads, 'samples');
        DB::beginTransaction();

        try {
            foreach ($payloads as $payload) {
                $createdSamples->push($this->createSampleEntryFromPayload(
                    $payload,
                    $sampleEntryCollectionFlowService,
                    $workflowNotifier
                ));
            }

            DB::commit();

            return redirect()->back()->with([
                'message' => $createdSamples->count() === 1
                    ? 'A amostra da fila manual foi registada com sucesso.'
                    : $createdSamples->count().' amostras da fila manual foram registadas com sucesso.',
                'type' => 'success',
                'sample_id' => $createdSamples->first()?->id,
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Failed to create manual sample entry batch.', [
                'exception' => $exception,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with([
                'message' => 'Não foi possível registar a fila manual de amostras. Tente novamente.',
                'type' => 'error',
            ]);
        }
    }

    /**
     * Update the specified sample entry
     */
    public function update(
        StoreSampleEntryRequest $request,
        VAPSampleEntry $sampleEntry,
        UpdateSampleEntry $updateSampleEntry,
        LaboratoryWorkflowNotifier $workflowNotifier
    ) {
        $validated = $request->validated();
        $result = $updateSampleEntry->execute($this->laboratoryAccess->activeLabId(), $sampleEntry->id, $request->user()->id, $validated);
        $sampleEntry = $result['sample'];
        $previousStatus = $result['previous_status'];
        $this->sendSampleTrackingNotifications(
            $sampleEntry,
            $previousStatus !== $sampleEntry->status ? 'status_updated' : 'updated',
            ['previous_status' => $previousStatus]
        );
        $workflowNotifier->notifySampleCollectionLinked($sampleEntry, auth()->user());

        return redirect()->back()->with([
            'message' => 'A amostra foi actualizada com sucesso.',
            'type' => 'success',
        ]);
    }

    public function downloadImportTemplate()
    {
        return Excel::download(
            new VAPSampleEntriesTemplateExport($this->laboratoryAccess->activeLabId()),
            'sample-entry-import-template-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function import(
        Request $request,
        SampleEntryCollectionFlowService $sampleEntryCollectionFlowService,
        LaboratoryWorkflowNotifier $workflowNotifier
    ): RedirectResponse {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $import = new VAPSampleEntriesImport;
        Excel::import($import, $validated['file']);

        if ($import->rows()->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'O ficheiro não contém linhas para importar.',
            ]);
        }

        $createdSamples = collect();
        $linkedSamples = 0;
        $payloads = $import->rows()->map(function (array $row, int $index): array {
            $payload = $this->sampleEntryPayloadFromImportRow($row, $index + 2);
            $payload = $this->validateImportedSampleEntryPayload($payload, $index + 2);

            $payload['client_submitted_info'] = array_replace($payload['client_submitted_info'] ?? [], [
                'imported_from_spreadsheet' => true,
                'imported_at' => now()->toIso8601String(),
                'imported_by_id' => auth()->id(),
            ]);

            return $payload;
        });

        $this->ensureUniqueBatchCodes($payloads, 'file');
        DB::beginTransaction();

        try {
            foreach ($payloads as $payload) {
                $operator = app(LaboratoryWorkflowMutationAccess::class)->operator(
                    (int) auth()->id(), $this->laboratoryAccess->activeLabId(), 'add_samples'
                );
                app(PersonnelQualificationGate::class)->ensure(
                    $operator,
                    'sample_intake_validation',
                    $payload['department_id'] ?? null,
                    $this->laboratoryAccess->activeLabId()
                );

                $portalRequest = $this->resolvePortalRequest($payload);
                $proposal = $this->resolveProposal($payload['proposal_id'] ?? null);
                $payload = $this->preparePayload->execute($payload, $portalRequest, proposal: $proposal);

                $sample = VAPSampleEntry::query()->create(array_merge($payload, [
                    'received_by_id' => $operator->id,
                    'received_by_label' => $operator->name,
                    'sample_year' => date('Y'),
                ]));

                $collectionProduct = $sampleEntryCollectionFlowService->sync($sample->fresh());
                $this->markPortalRequestAsValidated($portalRequest, $sample);
                $sample = $sample->fresh(['collectionProduct.code', 'warehouse', 'receivedBy']);
                $this->sendSampleTrackingNotifications($sample, 'created');
                $workflowNotifier->notifySampleCollectionLinked($sample, auth()->user());

                $createdSamples->push($sample);
                $linkedSamples += $collectionProduct ? 1 : 0;
            }

            DB::commit();
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Failed to import sample entries.', [
                'exception' => $exception,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with([
                'message' => 'Não foi possível importar as amostras. Verifique o ficheiro e tente novamente.',
                'type' => 'error',
            ]);
        }

        return redirect()->back()->with([
            'message' => sprintf(
                '%d amostra(s) importada(s); %d ligada(s) automaticamente ao fluxo de colheita/análise.',
                $createdSamples->count(),
                $linkedSamples
            ),
            'type' => 'success',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createSampleEntryFromPayload(
        array $payload,
        SampleEntryCollectionFlowService $sampleEntryCollectionFlowService,
        LaboratoryWorkflowNotifier $workflowNotifier
    ): VAPSampleEntry {
        $operator = app(LaboratoryWorkflowMutationAccess::class)->operator(
            (int) auth()->id(), $this->laboratoryAccess->activeLabId(), 'add_samples'
        );
        app(PersonnelQualificationGate::class)->ensure($operator, 'sample_intake_validation', $payload['department_id'] ?? null, $this->laboratoryAccess->activeLabId());

        $portalRequest = $this->resolvePortalRequest($payload);
        $proposal = $this->resolveProposal($payload['proposal_id'] ?? null);
        $payload = $this->preparePayload->execute($payload, $portalRequest, proposal: $proposal);

        $sample = VAPSampleEntry::query()->create(array_merge($payload, [
            'received_by_id' => $operator->id,
            'received_by_label' => $operator->name,
            'sample_year' => date('Y'),
        ]));

        $sampleEntryCollectionFlowService->sync($sample->fresh());
        $this->markPortalRequestAsValidated($portalRequest, $sample);
        $sample = $sample->fresh(['collectionProduct.code', 'warehouse', 'receivedBy']);
        $this->sendSampleTrackingNotifications($sample, 'created');
        $workflowNotifier->notifySampleCollectionLinked($sample, auth()->user());

        return $sample;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeManualBatchSamplePayload(array $payload): array
    {
        $payload['code'] = filled($payload['code'] ?? null) ? $payload['code'] : null;
        $payload['collection_product_id'] = $payload['collection_product_id'] ?? null;
        $payload['status'] = $payload['status'] ?? 'POR_INICIAR';
        $payload['received_at'] = $payload['received_at'] ?? now()->toDateTimeString();

        return $payload;
    }

    public function destroy(VAPSampleEntry $sampleEntry): RedirectResponse
    {
        abort_if(! auth()->user()->can('delete_samples'), 403, '');

        $clientInfo = $sampleEntry->client_submitted_info ?? [];
        $clientInfo['archived_by'] = [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'archived_at' => now()->toIso8601String(),
            'reason' => request()->string('reason')->trim()->toString() ?: 'manual_archive',
        ];

        $sampleEntry->forceFill([
            'client_submitted_info' => $clientInfo,
        ])->save();

        $sampleEntry->delete();

        return redirect()->route('vap_samples.index')->with([
            'message' => 'A entrada de amostra foi arquivada com rastreabilidade.',
            'type' => 'success',
        ]);
    }

    public function updateInternalQualityControlDecision(
        UpdateInternalQualityControlDecisionRequest $request,
        VAPSampleEntry $sampleEntry
    ) {
        if (! $this->isInternalQualityControlSample($sampleEntry)) {
            throw ValidationException::withMessages([
                'decision' => 'A decisão de CQ interno só pode ser registada em amostras internas de matéria-prima.',
            ]);
        }

        $validated = $request->validated();
        $analyses = $this->buildLinkedAnalysisSummaries($this->linkedSampleIdsFor($sampleEntry));
        $releaseGate = $this->buildQualityControlReleaseGate($sampleEntry, $analyses);

        if ($validated['decision'] === 'released' && ! ($releaseGate['can_release'] ?? false)) {
            throw ValidationException::withMessages([
                'decision' => 'A libertação só pode ser registada quando todos os resultados estiverem aprovados e sem revisão técnica pendente.',
            ]);
        }

        $decisionRecord = [
            'decision' => $validated['decision'],
            'label' => $this->finalQualityControlDecisionLabel($validated['decision']),
            'notes' => $validated['notes'] ?? null,
            'decided_at' => now()->toIso8601String(),
            'decided_by_id' => auth()->id(),
            'decided_by_name' => auth()->user()?->name,
            'gate_status_at_decision' => $releaseGate['status'] ?? null,
            'gate_totals_at_decision' => $releaseGate['totals'] ?? [],
        ];

        $clientInfo = $sampleEntry->client_submitted_info ?? [];
        $history = collect(data_get($clientInfo, 'qc_release_history', []))
            ->filter(fn ($item) => is_array($item))
            ->push($decisionRecord)
            ->slice(-20)
            ->values()
            ->all();

        $clientInfo['qc_release'] = $decisionRecord;
        $clientInfo['qc_release_history'] = $history;

        $sampleEntry->forceFill([
            'client_submitted_info' => $clientInfo,
        ])->save();

        $this->sendSampleTrackingNotifications(
            $sampleEntry->fresh(['collectionProduct.code', 'warehouse', 'receivedBy']),
            'quality_control_decision',
            ['decision_label' => $decisionRecord['label']]
        );

        return redirect()->back()->with([
            'message' => 'A decisão de controlo interno foi registada com rastreabilidade.',
            'type' => 'success',
        ]);
    }

    private function finalQualityControlDecisionLabel(string $decision): string
    {
        return match ($decision) {
            'released' => 'Liberada para uso',
            'rejected' => 'Rejeitada',
            'quarantined' => 'Em quarentena',
            'investigation_required' => 'Investigação requerida',
            'trend_recorded' => 'Registada para tendência',
            default => 'Decisão registada',
        };
    }

    private function collectionProductWorkflowUrl(VAPSampleEntry $sampleEntry): ?string
    {
        if (! $sampleEntry->collection_product_id) {
            return null;
        }

        $collectionType = data_get($sampleEntry->client_submitted_info, 'linked_collection_type', 'direct');

        return $collectionType === 'programmed'
            ? route('programmedcollections.show', $sampleEntry->collection_product_id)
            : route('directcollections.show', $sampleEntry->collection_product_id);
    }

    /**
     * Generate sample entry PDF
     */
    public function generatePdf(VAPSampleEntry $sampleEntry)
    {
        $sampleEntry->load(['customer', 'lab', 'department', 'warehouse', 'packaging', 'receivedBy']);

        $pdf = PDF::loadView('PDFs.sample-entry', [
            'sample' => $sampleEntry,
            'settings' => app(GeneralSettings::class),
            'date' => now()->format('d/m/Y'),
            'time' => now()->format('H:i:s'),
        ]);

        $filename = "sample-entry-{$sampleEntry->code}-".now()->format('Ymd-His').'.pdf';

        return PdfResponse::download($pdf, $filename);
    }

    /**
     * Get sample statistics
     */
    public function stats()
    {
        $stats = [
            'total_samples' => $this->laboratoryAccess->samples()->count(),
            'pending_analysis' => $this->laboratoryAccess->samples()->pending()->count(),
            'completed_analysis' => $this->laboratoryAccess->samples()->completed()->count(),
            'total_discarded' => $this->laboratoryAccess->discards()->count(),
            'discarded_this_month' => $this->laboratoryAccess->discards()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'by_sample_type' => $this->laboratoryAccess->samples()->select('sample_type', DB::raw('count(*) as total'))
                ->groupBy('sample_type')
                ->get(),
            'by_status' => $this->laboratoryAccess->samples()->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * @return array<string, string>
     */
    private function sampleReportFilters(Request $request): array
    {
        return [
            'date_from' => $request->string('date_from')->value() ?: $request->string('start_date')->value(),
            'date_to' => $request->string('date_to')->value() ?: $request->string('end_date')->value(),
            'status' => $request->string('status')->value(),
            'sample_type' => $request->string('sample_type')->value(),
            'sample_scope' => $request->string('sample_scope')->value(),
            'qc_release' => $request->string('qc_release')->value(),
            'customer_id' => $request->string('customer_id')->value(),
            'lab_id' => $request->string('lab_id')->value(),
            'department_id' => $request->string('department_id')->value(),
            'discard_method' => $request->string('discard_method')->value(),
        ];
    }

    /**
     * @param  Builder<VAPSampleEntry>  $query
     * @return Builder<VAPSampleEntry>
     */
    private function applySampleReportFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['date_from'], function (Builder $query, string $dateFrom) {
                $query->whereDate('received_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'], function (Builder $query, string $dateTo) {
                $query->whereDate('received_at', '<=', $dateTo);
            })
            ->when($filters['status'], function (Builder $query, string $status) {
                $query->where('status', $status);
            })
            ->when($filters['sample_type'], function (Builder $query, string $sampleType) {
                $query->where('sample_type', $sampleType);
            })
            ->when($filters['sample_scope'], function (Builder $query, string $sampleScope) {
                $this->applySampleScopeFilter($query, $sampleScope);
            })
            ->when($filters['qc_release'], function (Builder $query, string $qcRelease) {
                $this->applyQualityControlReleaseFilter($query, $qcRelease);
            })
            ->when($filters['customer_id'], function (Builder $query, string $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($filters['lab_id'], function (Builder $query, string $labId) {
                $query->where('lab_id', $labId);
            })
            ->when($filters['department_id'], function (Builder $query, string $departmentId) {
                $query->where('department_id', $departmentId);
            });
    }

    /**
     * @param  Builder<VAPSampleDiscard>  $query
     * @return Builder<VAPSampleDiscard>
     */
    private function applyDiscardReportFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['date_from'], function (Builder $query, string $dateFrom) {
                $query->whereDate('discarded_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'], function (Builder $query, string $dateTo) {
                $query->whereDate('discarded_at', '<=', $dateTo);
            })
            ->when($filters['discard_method'], function (Builder $query, string $discardMethod) {
                $query->where('discard_method', $discardMethod);
            })
            ->when($filters['lab_id'], function (Builder $query, string $labId) {
                $query->where(function (Builder $nested) use ($labId) {
                    $nested->where('lab_id', $labId)
                        ->orWhereHas('sample', function (Builder $sampleQuery) use ($labId) {
                            $sampleQuery->where('lab_id', $labId);
                        });
                });
            })
            ->when($filters['department_id'], function (Builder $query, string $departmentId) {
                $query->where(function (Builder $nested) use ($departmentId) {
                    $nested->where('department_id', $departmentId)
                        ->orWhereHas('sample', function (Builder $sampleQuery) use ($departmentId) {
                            $sampleQuery->where('department_id', $departmentId);
                        });
                });
            })
            ->when($filters['customer_id'], function (Builder $query, string $customerId) {
                $query->whereHas('sample', function (Builder $sampleQuery) use ($customerId) {
                    $sampleQuery->where('customer_id', $customerId);
                });
            })
            ->when($filters['sample_type'], function (Builder $query, string $sampleType) {
                $query->whereHas('sample', function (Builder $sampleQuery) use ($sampleType) {
                    $sampleQuery->where('sample_type', $sampleType);
                });
            })
            ->when($filters['status'], function (Builder $query, string $status) {
                $query->whereHas('sample', function (Builder $sampleQuery) use ($status) {
                    $sampleQuery->where('status', $status);
                });
            })
            ->when($filters['sample_scope'], function (Builder $query, string $sampleScope) {
                $query->whereHas('sample', function (Builder $sampleQuery) use ($sampleScope) {
                    $this->applySampleScopeFilter($sampleQuery, $sampleScope);
                });
            })
            ->when($filters['qc_release'], function (Builder $query, string $qcRelease) {
                $query->whereHas('sample', function (Builder $sampleQuery) use ($qcRelease) {
                    $this->applyQualityControlReleaseFilter($sampleQuery, $qcRelease);
                });
            });
    }

    /**
     * @param  Builder<VAPSampleEntry>  $query
     * @return Builder<VAPSampleEntry>
     */
    private function applySampleScopeFilter(Builder $query, string $sampleScope): Builder
    {
        return match ($sampleScope) {
            'internal_qc' => $query->internalRawMaterialQualityControl(),
            'internal' => $query->where('client_submitted_info->request_origin', 'internal'),
            'client' => $query->where(function (Builder $nested) {
                $nested->where('client_submitted_info->request_origin', 'client')
                    ->orWhereNull('client_submitted_info->request_origin');
            }),
            default => $query,
        };
    }

    /**
     * @param  Builder<VAPSampleEntry>  $query
     * @return Builder<VAPSampleEntry>
     */
    private function applyQualityControlReleaseFilter(Builder $query, string $qcRelease): Builder
    {
        return match ($qcRelease) {
            'pending' => $query->where(function (Builder $nested) {
                $nested->whereNull('client_submitted_info->qc_release->decision')
                    ->orWhere('client_submitted_info->qc_release->decision', '');
            }),
            default => $query->where('client_submitted_info->qc_release->decision', $qcRelease),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleQualityControlPayload(VAPSampleEntry $sample): array
    {
        $clientInfo = $sample->client_submitted_info ?? [];
        $qualityControlPath = data_get($clientInfo, 'quality_control_path', []);
        $finalDecision = data_get($clientInfo, 'qc_release.decision');
        $isInternalQualityControl = data_get($clientInfo, 'request_origin') === 'internal'
            && in_array(strtoupper((string) $sample->sample_type), ['MATERIA_PRIMA', 'RAW_MATERIAL'], true);

        return [
            'is_internal_qc' => $isInternalQualityControl,
            'path_name' => data_get($qualityControlPath, 'name'),
            'discipline' => data_get($clientInfo, 'analysis_discipline'),
            'purpose' => data_get($clientInfo, 'quality_control_purpose'),
            'decision' => data_get($clientInfo, 'qc_decision'),
            'final_decision' => $finalDecision,
            'final_decision_label' => $finalDecision ? $this->finalQualityControlDecisionLabel((string) $finalDecision) : null,
            'final_decision_notes' => data_get($clientInfo, 'qc_release.notes'),
            'final_decision_at' => data_get($clientInfo, 'qc_release.decided_at'),
            'final_decision_by' => data_get($clientInfo, 'qc_release.decided_by_name'),
            'material_category' => data_get($clientInfo, 'material_category'),
            'lot' => data_get($clientInfo, 'lot'),
            'batch' => data_get($clientInfo, 'batch'),
            'supplier_name' => data_get($clientInfo, 'supplier_name'),
            'follows_normal_analysis_flow' => (bool) data_get($qualityControlPath, 'follows_normal_analysis_flow', false),
        ];
    }

    /**
     * @param  Builder<VAPSampleEntry>  $sampleQuery
     * @return array<string, mixed>
     */
    private function buildInternalQualityControlReport(Builder $sampleQuery): array
    {
        $baseQuery = (clone $sampleQuery)->internalRawMaterialQualityControl();

        return [
            'total' => (clone $baseQuery)->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'EN_PROGRESO')->count(),
            'completed' => (clone $baseQuery)->where('status', 'COMPLETADO')->count(),
            'waiting_release' => (clone $baseQuery)->where('client_submitted_info->qc_decision', 'hold_until_release')->count(),
            'pending_release_decision' => (clone $baseQuery)->whereNull('client_submitted_info->qc_release->decision')->count(),
            'released' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'released')->count(),
            'quarantined' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'quarantined')->count(),
            'rejected' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'rejected')->count(),
            'investigation_required' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'investigation_required')->count(),
            'linked_to_normal_flow' => (clone $baseQuery)->whereNotNull('collection_product_id')->count(),
            'by_discipline' => [
                'microbiology' => (clone $baseQuery)->where('client_submitted_info->analysis_discipline', 'microbiology')->count(),
                'chemistry' => (clone $baseQuery)->where('client_submitted_info->analysis_discipline', 'chemistry')->count(),
                'microbiology_and_chemistry' => (clone $baseQuery)->where('client_submitted_info->analysis_discipline', 'microbiology_and_chemistry')->count(),
            ],
            'by_release_decision' => [
                'pending' => (clone $baseQuery)->whereNull('client_submitted_info->qc_release->decision')->count(),
                'released' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'released')->count(),
                'quarantined' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'quarantined')->count(),
                'rejected' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'rejected')->count(),
                'investigation_required' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'investigation_required')->count(),
                'trend_recorded' => (clone $baseQuery)->where('client_submitted_info->qc_release->decision', 'trend_recorded')->count(),
            ],
            'samples' => (clone $baseQuery)
                ->latest('received_at')
                ->limit(6)
                ->get()
                ->map(function (VAPSampleEntry $sample) {
                    return [
                        'id' => $sample->id,
                        'name' => $sample->name,
                        'code' => $sample->code,
                        'status' => $sample->status,
                        'sample_type' => $sample->sample_type,
                        'received_at' => optional($sample->received_at)?->toIso8601String(),
                        'customer' => $sample->customer?->only(['id', 'name']),
                        'department' => $sample->department?->only(['id', 'name']),
                        'collection_product_id' => $sample->collection_product_id,
                        'quality_control' => $this->sampleQualityControlPayload($sample),
                    ];
                })
                ->values(),
        ];
    }

    public function export(Request $request)
    {
        $filters = $this->sampleReportFilters($request);
        $samples = $this->applySampleReportFilters(
            $this->laboratoryAccess->samples()->with(['customer', 'lab', 'department', 'warehouse', 'packaging']),
            $filters
        )
            ->latest('received_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="samples-'.now()->format('Ymd-His').'.csv"',
        ];

        $callback = function () use ($samples) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'ID',
                'Code',
                'Name',
                'Sample Type',
                'Customer',
                'Laboratory',
                'Department',
                'Warehouse',
                'Status',
                'Received Date',
                'Analysis Start',
                'Analysis End',
                'Request Origin',
                'QC Discipline',
                'QC Purpose',
                'QC Decision',
                'QC Final Decision',
                'QC Final Decision At',
                'QC Final Decision By',
                'QC Final Decision Notes',
                'Material Category',
                'Lot',
                'Batch',
                'Supplier',
                'Created At',
            ]);

            // CSV data
            foreach ($samples as $sample) {
                fputcsv($file, [
                    $sample->id,
                    $sample->code,
                    $sample->name,
                    $sample->sample_type,
                    $sample->customer->name ?? 'N/A',
                    $sample->lab->name ?? 'N/A',
                    $sample->department->name ?? 'N/A',
                    $sample->warehouse->name ?? 'N/A',
                    $sample->status,
                    $sample->received_at ? $sample->received_at->format('Y-m-d H:i:s') : 'N/A',
                    $sample->analysis_start_date ? $sample->analysis_start_date->format('Y-m-d H:i:s') : 'N/A',
                    $sample->analysis_end_date ? $sample->analysis_end_date->format('Y-m-d H:i:s') : 'N/A',
                    data_get($sample->client_submitted_info, 'request_origin', 'client'),
                    data_get($sample->client_submitted_info, 'analysis_discipline', 'N/A'),
                    data_get($sample->client_submitted_info, 'quality_control_purpose', 'N/A'),
                    data_get($sample->client_submitted_info, 'qc_decision', 'N/A'),
                    ($finalDecision = data_get($sample->client_submitted_info, 'qc_release.decision'))
                        ? $this->finalQualityControlDecisionLabel((string) $finalDecision)
                        : 'N/A',
                    data_get($sample->client_submitted_info, 'qc_release.decided_at', 'N/A'),
                    data_get($sample->client_submitted_info, 'qc_release.decided_by_name', 'N/A'),
                    data_get($sample->client_submitted_info, 'qc_release.notes', 'N/A'),
                    data_get($sample->client_submitted_info, 'material_category', 'N/A'),
                    data_get($sample->client_submitted_info, 'lot', 'N/A'),
                    data_get($sample->client_submitted_info, 'batch', 'N/A'),
                    data_get($sample->client_submitted_info, 'supplier_name', 'N/A'),
                    $sample->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display consolidated VAP sample reports.
     */
    public function reports(Request $request)
    {
        $filters = $this->sampleReportFilters($request);

        $sampleQuery = $this->applySampleReportFilters(
            $this->laboratoryAccess->samples()
                ->with(['customer:id,name', 'lab:id,name', 'department:id,name', 'warehouse:id,name']),
            $filters
        );

        $discardQuery = $this->applyDiscardReportFilters(
            $this->laboratoryAccess->discards()
                ->with([
                    'sample' => fn (BelongsTo $query) => $query->withTrashed()->select('id', 'name', 'code', 'status', 'sample_type', 'customer_id', 'lab_id', 'department_id', 'client_submitted_info'),
                    'sample.customer:id,name',
                    'sample.lab:id,name',
                    'sample.department:id,name',
                    'discardedBy:id,name',
                    'lab:id,name',
                    'department:id,name',
                ]),
            $filters
        );

        $totalSamples = (clone $sampleQuery)->count();
        $completedSamples = (clone $sampleQuery)->where('status', 'COMPLETADO')->count();
        $pendingSamples = (clone $sampleQuery)->where('status', 'POR_INICIAR')->count();
        $inProgressSamples = (clone $sampleQuery)->where('status', 'EN_PROGRESO')->count();
        $totalDiscards = (clone $discardQuery)->count();

        $averageTurnaroundHours = round(
            (float) ((clone $sampleQuery)
                ->whereNotNull('received_at')
                ->whereNotNull('analysis_end_date')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (analysis_end_date - received_at)) / 3600.0) as avg_hours')
                ->value('avg_hours') ?? 0),
            1
        );
        $internalQualityControl = $this->buildInternalQualityControlReport(clone $sampleQuery);

        $statusBreakdown = (clone $sampleQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->status,
                'total' => (int) $row->total,
            ]);

        $sampleTypeBreakdown = (clone $sampleQuery)
            ->select('sample_type', DB::raw('count(*) as total'))
            ->groupBy('sample_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->sample_type ?: 'N/A',
                'total' => (int) $row->total,
            ]);

        $discardMethodBreakdown = (clone $discardQuery)
            ->select('discard_method', DB::raw('count(*) as total'))
            ->groupBy('discard_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->discard_method ?: 'N/A',
                'total' => (int) $row->total,
            ]);

        $sampleTimeline = (clone $sampleQuery)
            ->selectRaw('DATE(received_at) as report_date, COUNT(*) as total')
            ->whereNotNull('received_at')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->report_date,
                'total' => (int) $row->total,
            ]);

        $samples = (clone $sampleQuery)
            ->latest('received_at')
            ->paginate(10, ['*'], 'samples_page')
            ->withQueryString()
            ->through(function (VAPSampleEntry $sample) {
                return [
                    'id' => $sample->id,
                    'name' => $sample->name,
                    'code' => $sample->code,
                    'sample_type' => $sample->sample_type,
                    'status' => $sample->status,
                    'received_at' => optional($sample->received_at)?->toIso8601String(),
                    'analysis_start_date' => optional($sample->analysis_start_date)?->toIso8601String(),
                    'analysis_end_date' => optional($sample->analysis_end_date)?->toIso8601String(),
                    'customer' => $sample->customer?->only(['id', 'name']),
                    'lab' => $sample->lab?->only(['id', 'name']),
                    'department' => $sample->department?->only(['id', 'name']),
                    'warehouse' => $sample->warehouse?->only(['id', 'name']),
                    'collection_product_id' => $sample->collection_product_id,
                    'quality_control' => $this->sampleQualityControlPayload($sample),
                ];
            });

        $discards = (clone $discardQuery)
            ->latest('discarded_at')
            ->paginate(10, ['*'], 'discards_page')
            ->withQueryString()
            ->through(function (VAPSampleDiscard $discard) {
                return [
                    'id' => $discard->id,
                    'discard_method' => $discard->discard_method,
                    'qty' => $discard->qty,
                    'discarded_at' => optional($discard->discarded_at)?->toIso8601String(),
                    'discarded_by' => $discard->discardedBy?->only(['id', 'name']),
                    'lab' => $discard->lab?->only(['id', 'name']) ?? $discard->sample?->lab?->only(['id', 'name']),
                    'department' => $discard->department?->only(['id', 'name']) ?? $discard->sample?->department?->only(['id', 'name']),
                    'sample' => $discard->sample ? [
                        'id' => $discard->sample->id,
                        'name' => $discard->sample->name,
                        'code' => $discard->sample->code,
                        'status' => $discard->sample->status,
                        'sample_type' => $discard->sample->sample_type,
                        'customer' => $discard->sample->customer?->only(['id', 'name']),
                    ] : null,
                ];
            });

        return Inertia::render('VAPSamples/Reports', [
            'title' => 'Relatórios de Amostras',
            'filters' => $filters,
            'summary' => [
                'total_samples' => $totalSamples,
                'completed_samples' => $completedSamples,
                'pending_samples' => $pendingSamples,
                'in_progress_samples' => $inProgressSamples,
                'total_discards' => $totalDiscards,
                'discard_rate' => $totalSamples > 0 ? round(($totalDiscards / $totalSamples) * 100, 1) : 0,
                'avg_turnaround_hours' => $averageTurnaroundHours,
                'internal_qc_samples' => $internalQualityControl['total'],
            ],
            'internalQualityControl' => $internalQualityControl,
            'samples' => $samples,
            'discards' => $discards,
            'statusBreakdown' => $statusBreakdown,
            'sampleTypeBreakdown' => $sampleTypeBreakdown,
            'discardMethodBreakdown' => $discardMethodBreakdown,
            'sampleTimeline' => $sampleTimeline,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'labs' => VAPLab::whereKey($this->laboratoryAccess->activeLabId())->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'discardMethods' => $this->laboratoryAccess->discards()
                ->select('discard_method')
                ->distinct()
                ->orderBy('discard_method')
                ->pluck('discard_method')
                ->filter()
                ->values(),
            'sampleTypes' => $this->laboratoryAccess->samples()
                ->select('sample_type')
                ->distinct()
                ->orderBy('sample_type')
                ->pluck('sample_type')
                ->filter()
                ->values(),
            'generatedAt' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function pendingPortalAnalysisRequests()
    {
        $profiles = Profile::query()->pluck('name', 'id');

        return CustomerRequest::query()
            ->with(['customer:id,name', 'warehouse:id,address'])
            ->where('request_type', 'analysis_request')
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'in_progress'])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status')->where('answered', false);
                    });
            })
            ->latest('submitted_at')
            ->limit(100)
            ->get()
            ->map(function (CustomerRequest $request) use ($profiles) {
                $details = collect($request->extra_data ?? []);
                $sampleRows = collect($details->get('samples', []))
                    ->map(function ($sample, int $index) {
                        return [
                            'batch_index' => $sample['batch_index'] ?? $index,
                            'sample_name' => $sample['sample_name'] ?? null,
                            'product_name' => $sample['product_name'] ?? null,
                            'matrix' => $sample['matrix'] ?? null,
                            'lot' => $sample['lot'] ?? null,
                            'packaging' => $sample['packaging'] ?? null,
                            'quantity' => $sample['quantity'] ?? null,
                            'notes' => $sample['notes'] ?? null,
                        ];
                    })
                    ->values();
                $validatedBatchIndexes = collect($details->get('validated_batch_indexes', []))
                    ->filter(fn ($index) => is_numeric($index))
                    ->map(fn ($index) => (int) $index)
                    ->values();
                $remainingSampleRows = $sampleRows
                    ->reject(fn (array $sample) => $validatedBatchIndexes->contains((int) ($sample['batch_index'] ?? -1)))
                    ->values();
                $requestedProfileNames = collect($details->get('requested_profiles', []))
                    ->map(fn ($profileId) => $profiles->get($profileId, 'Perfil #'.$profileId))
                    ->values()
                    ->all();

                return [
                    'id' => $request->id,
                    'reference' => $request->reference,
                    'title' => $request->title,
                    'status' => $request->portal_status,
                    'description' => $request->description,
                    'customer_id' => $request->customer_id,
                    'customer' => $request->customer?->name,
                    'warehouse_id' => $request->warehouse_id,
                    'warehouse' => $request->warehouse?->address,
                    'preferred_date' => optional($request->preferred_date)?->format('Y-m-d'),
                    'details' => $details,
                    'product_id' => data_get($details, 'product_id'),
                    'matrix_id' => data_get($details, 'matrix_id'),
                    'packaging_id' => data_get($details, 'packaging_id'),
                    'sample_rows' => $sampleRows,
                    'remaining_sample_rows' => $remainingSampleRows,
                    'remaining_sample_count' => $remainingSampleRows->count(),
                    'next_sample_row' => $remainingSampleRows->first(),
                    'requested_profile_names' => $requestedProfileNames,
                    'submitted_at' => optional($request->submitted_at)?->toIso8601String(),
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function sampleEntryPayloadFromImportRow(array $row, int $rowNumber): array
    {
        $requestOrigin = $this->normalizedImportChoice(
            $this->importString($row, ['request_origin', 'origem_do_trabalho', 'origem_do_pedido']),
            [
                'cliente' => 'client',
                'client' => 'client',
                'externo' => 'client',
                'interna' => 'internal',
                'interno' => 'internal',
                'internal' => 'internal',
            ],
            'client'
        );
        $collectionType = $this->normalizedImportChoice(
            $this->importString($row, ['collection_type', 'fluxo_de_colheita', 'tipo_de_colheita']),
            [
                'directa', 'direta' => 'direct',
                'directa' => 'direct',
                'direct' => 'direct',
                'imediata' => 'direct',
                'programada' => 'programmed',
                'programmed' => 'programmed',
                'planeada' => 'programmed',
            ],
            'direct'
        );
        $productId = $this->importEntityId(Product::class, $row, $rowNumber, ['product_id', 'produto_id'], [
            'product_name' => 'name',
            'produto' => 'name',
            'nome_do_produto' => 'name',
        ], false);
        $product = $productId ? Product::query()->find($productId) : null;
        $matrixId = $this->importEntityId(Matrix::class, $row, $rowNumber, ['matrix_id', 'matriz_id'], [
            'matrix_name' => 'description',
            'matrix' => 'description',
            'matriz' => 'description',
        ], false) ?: $product?->matrix_id;
        $profileIds = $this->importProfileIds($row, $rowNumber);
        $sampleType = strtoupper($this->importString($row, ['sample_type', 'tipo_de_amostra']) ?: 'ROTINA');

        return [
            'name' => $this->importString($row, ['name', 'sample_name', 'nome', 'nome_da_amostra', 'amostra']) ?: throw ValidationException::withMessages([
                'file' => "Linha {$rowNumber}: o nome da amostra é obrigatório.",
            ]),
            'code' => $this->importString($row, ['code', 'sample_code', 'codigo', 'codigo_da_amostra']),
            'sample_type' => $sampleType,
            'proposal_id' => $this->importInteger($row, ['proposal_id']),
            'collection_product_id' => null,
            'portal_request_id' => $this->importInteger($row, ['portal_request_id']),
            'customer_request_id' => $this->importInteger($row, ['customer_request_id']),
            'customer_id' => $this->importEntityId(Customer::class, $row, $rowNumber, ['customer_id', 'cliente_id'], [
                'customer_code' => 'code',
                'customer_name' => 'name',
                'codigo_do_cliente' => 'code',
                'cliente' => 'name',
            ]),
            'lab_id' => $this->importEntityId(VAPLab::class, $row, $rowNumber, ['lab_id', 'laboratorio_id'], [
                'lab_code' => 'code',
                'lab_name' => 'name',
                'laboratorio' => 'name',
                'codigo_do_laboratorio' => 'code',
            ]),
            'department_id' => $this->importEntityId(Department::class, $row, $rowNumber, ['department_id', 'departamento_id'], [
                'department_code' => 'code',
                'department_name' => 'name',
                'departamento' => 'name',
                'codigo_do_departamento' => 'code',
            ]),
            'warehouse_id' => $this->importEntityId(Warehouse::class, $row, $rowNumber, ['warehouse_id', 'armazem_id'], [
                'warehouse_code' => 'code',
                'warehouse_name' => 'name',
                'warehouse_address' => 'address',
                'armazem' => ['name', 'address'],
                'codigo_do_armazem' => 'code',
                'endereco_do_armazem' => 'address',
                'morada_do_armazem' => 'address',
            ]),
            'packaging_id' => $this->importEntityId(PackagingCategory::class, $row, $rowNumber, ['packaging_id', 'embalagem_id'], [
                'packaging_code' => 'code',
                'packaging_name' => 'name',
                'embalagem' => 'name',
                'codigo_da_embalagem' => 'code',
            ], false),
            'received_at' => $this->importString($row, ['received_at', 'recebido_em', 'data_de_recebimento']) ?: now()->toDateTimeString(),
            'requested_services' => $this->importString($row, ['requested_services', 'servicos_solicitados']),
            'obs' => $this->importString($row, ['obs', 'notes', 'observacoes']),
            'status' => $this->importString($row, ['status', 'estado']) ?: 'POR_INICIAR',
            'analysis_start_date' => $this->importString($row, ['analysis_start_date']),
            'analysis_end_date' => $this->importString($row, ['analysis_end_date']),
            'collected_by_lab' => $this->importBoolean($row, ['collected_by_lab']),
            'collected_at' => $this->importString($row, ['collected_at', 'colhido_em', 'data_de_colheita']),
            'client_submitted_info' => [
                'request_origin' => $requestOrigin,
                'collection_type' => $collectionType,
                'collection_location' => $this->importString($row, ['collection_location', 'local_de_colheita']),
                'vehicle_reference' => $this->importString($row, ['vehicle_reference', 'referencia_de_viatura', 'equipa_de_colheita']),
                'product_id' => $productId,
                'product_name' => $product?->name ?: $this->importString($row, ['product_name', 'produto', 'nome_do_produto']),
                'matrix_id' => $matrixId,
                'requested_profile_ids' => $profileIds,
                'conditioning_status' => $this->normalizedImportChoice(
                    $this->importString($row, ['conditioning_status', 'condicao_de_aceitacao', 'estado_de_aceitacao']),
                    [
                        'aceite' => 'accepted',
                        'aceito' => 'accepted',
                        'accepted' => 'accepted',
                        'aceite_com_restricoes' => 'restricted',
                        'restrito' => 'restricted',
                        'restricted' => 'restricted',
                        'rejeitado' => 'rejected',
                        'rejected' => 'rejected',
                    ],
                    null
                ),
                'quality_control_purpose' => $this->importString($row, ['quality_control_purpose', 'objetivo_do_controlo']) ?: ($requestOrigin === 'internal' ? 'raw_material_release' : null),
                'analysis_discipline' => $this->importString($row, ['analysis_discipline', 'disciplina']),
                'material_category' => $this->importString($row, ['material_category', 'categoria_do_material']) ?: ($requestOrigin === 'internal' ? 'raw_material' : null),
                'qc_decision' => $this->importString($row, ['qc_decision', 'decisao_esperada']) ?: ($requestOrigin === 'internal' ? 'hold_until_release' : null),
                'lot' => $this->importString($row, ['lot', 'lote']),
                'batch' => $this->importString($row, ['batch']),
                'origin' => $this->importString($row, ['origin', 'origem']),
                'location' => $this->importString($row, ['location', 'local']),
                'quantity' => $this->importString($row, ['quantity', 'quantidade_recebida']),
                'collected_qty' => $this->importString($row, ['collected_qty', 'quantidade_colhida']),
                'production_date' => $this->importString($row, ['production_date', 'data_de_producao']),
                'expiry_date' => $this->importString($row, ['expiry_date', 'data_de_validade']),
                'temperature_value' => $this->importString($row, ['temperature_value', 'temperatura']),
                'container_no' => $this->importString($row, ['container_no', 'contentor']),
                'du_no' => $this->importString($row, ['du_no']),
                'term_no' => $this->importString($row, ['term_no', 'termo']),
                'bl' => $this->importString($row, ['bl']),
                'sampling_plan_ref' => $this->importString($row, ['sampling_plan_ref', 'plano_de_amostragem']),
                'supplier_name' => $this->importString($row, ['supplier_name', 'fornecedor']),
                'packaging_condition' => $this->importString($row, ['packaging_condition', 'estado_da_embalagem']),
                'temperature_condition' => $this->importString($row, ['temperature_condition', 'condicao_termica']),
                'integrity_observations' => $this->importString($row, ['integrity_observations', 'observacoes_de_integridade']),
                'chain_of_custody_notes' => $this->importString($row, ['chain_of_custody_notes', 'notas_de_custodia']),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateImportedSampleEntryPayload(array $payload, int $rowNumber): array
    {
        try {
            return $this->sampleEntryValidation->validate($payload, $this->laboratoryAccess->activeLabId());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'file' => "Linha {$rowNumber}: ".collect($exception->errors())->flatten()->first(),
            ]);
        }
    }

    /** @param Collection<int, array<string, mixed>> $payloads */
    private function ensureUniqueBatchCodes(Collection $payloads, string $errorField): void
    {
        $duplicate = $payloads->pluck('code')->filter(fn (mixed $code): bool => filled($code))->duplicatesStrict()->first();

        if ($duplicate !== null) {
            throw ValidationException::withMessages([$errorField => 'O código '.$duplicate.' está repetido na mesma fila de entrada.']);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function importString(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->importRowValue($row, $key);

            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importRowValue(array $row, string $key): mixed
    {
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }

        $normalizedKey = $this->normalizeImportKey($key);

        foreach ($row as $rowKey => $value) {
            if ($this->normalizeImportKey((string) $rowKey) === $normalizedKey) {
                return $value;
            }
        }

        return null;
    }

    private function normalizeImportKey(string $key): string
    {
        return Str::of($key)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    /**
     * @param  array<string, string>  $map
     */
    private function normalizedImportChoice(?string $value, array $map, ?string $default): ?string
    {
        if (! filled($value)) {
            return $default;
        }

        return $map[$this->normalizeImportKey($value)] ?? $value;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function importInteger(array $row, array $keys): ?int
    {
        $value = $this->importString($row, $keys);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function importBoolean(array $row, array $keys): bool
    {
        $value = strtolower((string) ($this->importString($row, $keys) ?? ''));

        return in_array($value, ['1', 'true', 'yes', 'sim', 's'], true);
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $idKeys
     * @param  array<string, string|array<int, string>>  $lookupColumns
     */
    private function importEntityId(
        string $modelClass,
        array $row,
        int $rowNumber,
        array $idKeys,
        array $lookupColumns,
        bool $required = true
    ): ?int {
        $id = $this->importInteger($row, $idKeys);

        if ($id) {
            return $id;
        }

        foreach ($lookupColumns as $key => $columns) {
            $value = $this->importString($row, [$key]);

            if (! $value) {
                continue;
            }

            $model = $modelClass::query()
                ->where(function ($query) use ($columns, $value) {
                    foreach ((array) $columns as $column) {
                        $query->orWhere($column, $value);
                    }
                })
                ->first();

            if ($model) {
                return (int) $model->getKey();
            }
        }

        if ($required) {
            throw ValidationException::withMessages([
                'file' => "Linha {$rowNumber}: não foi possível identificar ".str(class_basename($modelClass))->headline()->lower().'.',
            ]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, int>
     */
    private function importProfileIds(array $row, int $rowNumber): array
    {
        $raw = $this->importString($row, ['requested_profile_ids', 'profile_ids', 'profiles', 'perfis_analiticos', 'perfis_solicitados']);

        if (! $raw) {
            return [];
        }

        return collect(preg_split('/[,;|]/', $raw) ?: [])
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->map(function (string $value) use ($rowNumber): int {
                if (is_numeric($value)) {
                    return (int) $value;
                }

                $profile = Profile::query()->where('name', $value)->first();

                if (! $profile) {
                    throw ValidationException::withMessages([
                        'file' => "Linha {$rowNumber}: o perfil '{$value}' não existe.",
                    ]);
                }

                return (int) $profile->id;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function resolvePortalRequest(array $validated): ?CustomerRequest
    {
        $portalRequestId = $validated['portal_request_id'] ?? $validated['customer_request_id'] ?? null;

        if (! $portalRequestId) {
            return null;
        }

        $field = array_key_exists('portal_request_id', $validated) ? 'portal_request_id' : 'customer_request_id';
        $portalRequest = CustomerRequest::query()
            ->where('customer_id', $validated['customer_id'] ?? 0)
            ->where('warehouse_id', $validated['warehouse_id'] ?? 0)
            ->lockForUpdate()
            ->find($portalRequestId);

        if (! $portalRequest || ! in_array($portalRequest->portal_status, ['pending', 'in_progress'], true)) {
            throw ValidationException::withMessages([$field => 'Seleccione um pedido activo do cliente e local indicados.']);
        }

        $batchIndex = data_get($validated, 'client_submitted_info.batch_sample_index');
        $usedIndexes = collect(data_get($portalRequest->extra_data, 'validated_batch_indexes', []))
            ->filter(fn (mixed $index): bool => filter_var($index, FILTER_VALIDATE_INT) !== false)
            ->map(fn (mixed $index): int => (int) $index);

        if ($batchIndex !== null && $usedIndexes->contains((int) $batchIndex)) {
            throw ValidationException::withMessages([
                'client_submitted_info.batch_sample_index' => 'Esta linha do pedido do cliente já foi registada como amostra.',
            ]);
        }

        return $portalRequest;
    }

    private function resolveProposal(?int $proposalId): ?Proposal
    {
        if (! $proposalId) {
            return null;
        }

        return Proposal::query()->findOrFail($proposalId);
    }

    private function markPortalRequestAsValidated(?CustomerRequest $portalRequest, VAPSampleEntry $sample): void
    {
        if (! $portalRequest) {
            return;
        }

        $portalRequest->forceFill([
            'status' => 'in_progress',
            'answered' => false,
            'extra_data' => collect($portalRequest->extra_data ?? [])
                ->merge([
                    'validated_sample_entry_id' => $sample->id,
                    'validated_sample_code' => $sample->code,
                    'validated_by_id' => auth()->id(),
                    'validated_by_name' => auth()->user()->name,
                    'validated_at' => now()->toIso8601String(),
                ])
                ->merge([
                    'validated_sample_entry_ids' => collect($portalRequest->extra_data['validated_sample_entry_ids'] ?? [])
                        ->push($sample->id)
                        ->unique()
                        ->values()
                        ->all(),
                ])
                ->when(
                    data_get($sample->client_submitted_info, 'batch_sample_index') !== null,
                    function ($details) use ($sample) {
                        return $details->merge([
                            'validated_batch_indexes' => collect($details->get('validated_batch_indexes', []))
                                ->push((int) data_get($sample->client_submitted_info, 'batch_sample_index'))
                                ->unique()
                                ->values()
                                ->all(),
                        ]);
                    }
                )
                ->all(),
        ])->save();
    }

    private function sendSampleTrackingNotifications(VAPSampleEntry $sample, string $action, array $context = []): void
    {
        $sender = auth()->user();

        if (! $sender instanceof User) {
            return;
        }

        $templateKey = match ($action) {
            'created' => 'lab.sample.created',
            'status_updated' => 'lab.sample.status_updated',
            'quality_control_decision' => 'lab.sample.quality_decision',
            default => null,
        };

        if (! $templateKey) {
            return;
        }

        $recipients = collect([
            $sample->warehouse,
            $sample->receivedBy,
            $sender,
        ])->filter()->unique(fn ($recipient) => get_class($recipient).':'.$recipient->getKey());

        $analysisId = $sample->collectionProduct?->code?->analysis()?->value('analysis.id');

        $this->notificationTemplates->notify($recipients, $templateKey, [
            'sample_entry_id' => $sample->id,
            'sample_id' => $sample->id,
            'sample_code' => $sample->code ?: $sample->name,
            'conditioning_status' => data_get($sample->client_submitted_info, 'conditioning_status'),
            'required_parameter_count' => (int) data_get($sample->client_submitted_info, 'required_parameter_count', 0),
            'collection_product_id' => $sample->collection_product_id,
            'collection_url' => $this->collectionProductWorkflowUrl($sample),
            'analysis_url' => $analysisId ? route('analysis.edit', $analysisId) : null,
            'previous_status' => $context['previous_status'] ?? 'estado anterior',
            'status' => $sample->status,
            'decision_label' => $context['decision_label'] ?? 'decisão registada',
            'document_url' => route('vap_samples.show', $sample),
        ]);
    }
}
