<?php

namespace App\Http\Controllers;

use App\Models\CollectionProduct;
use App\Models\ISOActivityLog;
use App\Models\QualityCertificate;
use App\Models\QualityCertificateRevision;
use App\Models\Result;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PDF;

class ISORevisionController extends Controller
{
    /**
     * Display ISO 17025 revision management page
     */
    public function index(QualityCertificate $certificate)
    {
        $revisions = $certificate->revisions()
            ->with(['createdBy', 'approvedBy'])
            ->orderBy('revision_number', 'desc')
            ->paginate(20);

        $activityLogs = ISOActivityLog::where('subject_type', QualityCertificate::class)
            ->where('subject_id', $certificate->id)
            ->orWhere(function ($query) use ($certificate) {
                $query->where('subject_type', QualityCertificateRevision::class)
                    ->whereHas('subject', function ($q) use ($certificate) {
                        $q->where('subject_id', $certificate->id);
                    });
            })
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('QualityCertificates/ISORevisions/Index', [
            'certificate' => $certificate->load([
                'customer',
                'product',
                'warehouse',
                'currentRevision',
                'collection',
            ]),
            'revisions' => $revisions,
            'activityLogs' => $activityLogs,
            'complianceStats' => $this->getComplianceStats($certificate),
            'approvers' => User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['quality-manager', 'lab-manager', 'director', 'admin']);
            })->select('id', 'name', 'email')->get(),
        ]);
    }

    /**
     * Show form to create new revision
     */
    public function create(QualityCertificate $certificate)
    {
        return Inertia::render('QualityCertificates/ISORevisions/Create', [
            'certificate' => $certificate->load(['customer', 'product']),
            'updatableFields' => $this->getUpdatableFields(),
            'approvers' => $this->getApprovers(),
        ]);
    }

    /**
     * Store a new revision
     */
    public function store(Request $request, QualityCertificate $certificate)
    {
        $request->validate([
            'change_type' => 'required|in:UPDATED,CORRECTED,REISSUED,WITHDRAWN',
            'change_reason' => 'required|string|min:10|max:1000',
            'iso_section' => 'required|string',
            'risk_assessment' => 'required|in:LOW,MEDIUM,HIGH,CRITICAL',
            'approved_by_id' => 'nullable|exists:users,id',
            'fields' => 'required|array',
        ]);

        // Use ISO-compliant update method
        $certificate->updateWithIsoCompliance(
            $request->fields,
            $request->change_reason,
            [
                'approved_by_id' => $request->approved_by_id,
                'iso_section' => $request->iso_section,
                'change_category' => $request->change_type,
                'risk_assessment' => $request->risk_assessment,
            ]
        );

        return redirect()
            ->route('qualitycertificates.iso-revisions.index', $certificate)
            ->with('success', 'Revisão criada com sucesso em conformidade com a ISO 17025.');
    }

    /**
     * Display specific revision
     */
    public function show(QualityCertificate $certificate, QualityCertificateRevision $revision)
    {
        $relatedLogs = ISOActivityLog::whereIn('id', $revision->activity_log_ids ?? [])
            ->with('causer')
            ->get();

        return Inertia::render('QualityCertificates/ISORevisions/Show', [
            'certificate' => $certificate,
            'revision' => $revision->load(['createdBy', 'approvedBy']),
            'snapshot' => $revision->snapshot_data,
            'relatedActivityLogs' => $relatedLogs,
            'differences' => $this->calculateRevisionDiff($certificate, $revision),
            'approvers' => $this->getApprovers(),
        ]);
    }

    /**
     * Compare two revisions
     */
    // public function compare(Request $request, QualityCertificate $certificate)
    // {
    //     $request->validate([
    //         'revision_a' => 'required|exists:quality_certificate_revisions,id',
    //         'revision_b' => 'required|exists:quality_certificate_revisions,id',
    //     ]);

    //     $revisionA = QualityCertificateRevision::with(['createdBy', 'approvedBy'])
    //         ->findOrFail($request->revision_a);

    //     $revisionB = QualityCertificateRevision::with(['createdBy', 'approvedBy'])
    //         ->findOrFail($request->revision_b);

    //     return Inertia::render('QualityCertificates/ISORevisions/Compare', [
    //         'certificate' => $certificate,
    //         'revisionA' => $revisionA,
    //         'revisionB' => $revisionB,
    //         'differences' => $this->compareRevisions($revisionA, $revisionB),
    //     ]);
    // }

    /**
     * Compare two revisions (using query parameters)
     */
    public function compare(Request $request, QualityCertificate $certificate)
    {
        $request->validate([
            'revision_a' => 'required|exists:quality_certificate_revisions,id',
            'revision_b' => 'required|exists:quality_certificate_revisions,id',
        ]);

        $revisionA = QualityCertificateRevision::with(['createdBy', 'approvedBy'])
            ->findOrFail($request->revision_a);

        $revisionB = QualityCertificateRevision::with(['createdBy', 'approvedBy'])
            ->findOrFail($request->revision_b);

        return Inertia::render('QualityCertificates/ISORevisions/Compare', [
            'certificate' => $certificate,
            'revisionA' => $revisionA,
            'revisionB' => $revisionB,
            'differences' => $this->compareRevisions($revisionA, $revisionB),
        ]);
    }

    /**
     * Compare two revisions (using route parameters)
     */
    public function compareTwo(Request $request, QualityCertificate $certificate, QualityCertificateRevision $revision_a, QualityCertificateRevision $revision_b)
    {

        // Load relationships for both revisions
        $revision_a->load(['createdBy', 'approvedBy']);
        $revision_b->load(['createdBy', 'approvedBy']);

        // Calculate the differences
        $differences = $this->compareRevisions($revision_a, $revision_b);

        // Prepare the data array
        $data = [
            'certificate' => $certificate,
            'revisionA' => $revision_a,
            'revisionB' => $revision_b,
            'differences' => $differences,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('QualityCertificates/ISORevisions/Compare', $data);
        // return Inertia::render('QualityCertificates/ISORevisions/Compare', [
        //     'certificate' => $certificate,
        //     'revisionA' => $revision_a->load(['createdBy', 'approvedBy']),
        //     'revisionB' => $revision_b->load(['createdBy', 'approvedBy']),
        //     'differences' => $this->compareRevisions($revision_a, $revision_b),
        // ]);
    }

    /**
     * Restore to a previous revision
     */
    public function restore(Request $request, QualityCertificate $certificate, QualityCertificateRevision $revision)
    {
        $request->validate([
            'restore_reason' => 'required|string|min:20|max:1000',
            'approval_data' => 'required|array',
            'approval_data.approved_by_id' => 'required|exists:users,id',
            'approval_data.iso_section' => 'required|string',
            'approval_data.change_category' => 'required|string|in:CORRECTION,REISSUE,EMERGENCY,REGULATORY',
            'confirmed' => 'required|accepted',
        ]);

        $this->authorize('restore', [$certificate, $revision]);

        DB::transaction(function () use ($certificate, $revision, $request) {
            $snapshotData = $revision->snapshot_data;
            $updates = [];

            if ($request->input('approval_data.restore_scope', 'FULL') === 'FULL') {
                $updates = $snapshotData['certificate'] ?? [];
                unset($updates['id'], $updates['code'], $updates['created_at'], $updates['updated_at']);
            } else {
                $selectedFields = $request->input('approval_data.selected_fields', []);
                $certificateSnapshot = $snapshotData['certificate'] ?? [];

                foreach ($selectedFields as $field) {
                    if (isset($certificateSnapshot[$field])) {
                        $updates[$field] = $certificateSnapshot[$field];
                    }
                }
            }

            $certificate->updateWithIsoCompliance(
                $updates,
                $request->restore_reason,
                [
                    'approved_by_id' => $request->input('approval_data.approved_by_id'),
                    'iso_section' => $request->input('approval_data.iso_section'),
                    'change_category' => $request->input('approval_data.change_category'),
                    'risk_assessment' => $request->input('approval_data.risk_assessment', 'MEDIUM'),
                    'restore_scope' => $request->input('approval_data.restore_scope', 'FULL'),
                    'restored_from_revision' => $revision->id,
                ]
            );
        });

        return redirect()
            ->route('qualitycertificates.iso-revisions.index', $certificate)
            ->with('success', 'Certificado restaurado para a revisão '.$revision->version.'.');
    }

    /**
     * Display full audit trail
     */
    public function auditTrail(QualityCertificate $certificate)
    {
        $logs = ISOActivityLog::where(function ($query) use ($certificate) {
            $query->where('subject_type', QualityCertificate::class)
                ->where('subject_id', $certificate->id);
        })
            ->orWhere(function ($query) use ($certificate) {
                $query->where('subject_type', CollectionProduct::class)
                    ->where('subject_id', $certificate->collection_id);
            })
            ->orWhere(function ($query) use ($certificate) {
                $query->where('subject_type', Result::class)
                    ->whereIn('subject_id', function ($subquery) use ($certificate) {
                        $subquery->select('id')
                            ->from('results')
                            ->where('collection_id', $certificate->collection_id);
                    });
            })
            ->orWhere(function ($query) use ($certificate) {
                $query->where('subject_type', QualityCertificateRevision::class)
                    ->whereIn('subject_id', function ($subquery) use ($certificate) {
                        $subquery->select('id')
                            ->from('quality_certificate_revisions')
                            ->where('quality_certificate_id', $certificate->id);
                    });
            })
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('QualityCertificates/ISORevisions/AuditTrail', [
            'certificate' => $certificate,
            'logs' => $logs,
            'filters' => request()->only(['causer_id', 'date_from', 'date_to', 'change_type']),
        ]);
    }

    /**
     * Export revision history as PDF
     */
    public function export(QualityCertificate $certificate)
    {
        $revisions = $certificate->revisions()
            ->with(['createdBy', 'approvedBy'])
            ->orderBy('revision_number', 'desc')
            ->get();

        $pdf = PDF::loadView('exports.revision-history', [
            'certificate' => $certificate,
            'revisions' => $revisions,
            'exportDate' => Carbon::now(),
        ]);

        return $pdf->download("historico-revisoes-{$certificate->code}.pdf");
    }

    /**
     * Export comparison as PDF
     */
    public function exportComparison(QualityCertificate $certificate, $revisionA, $revisionB)
    {
        $revisionA = QualityCertificateRevision::findOrFail($revisionA);
        $revisionB = QualityCertificateRevision::findOrFail($revisionB);

        $pdf = PDF::loadView('exports.revision-comparison', [
            'certificate' => $certificate,
            'revisionA' => $revisionA,
            'revisionB' => $revisionB,
            'differences' => $this->compareRevisions($revisionA, $revisionB),
        ]);

        return $pdf->download("comparacao-{$certificate->code}-{$revisionA->version}-{$revisionB->version}.pdf");
    }

    /**
     * Get revision snapshot (JSON API)
     */
    public function snapshot(QualityCertificate $certificate, QualityCertificateRevision $revision)
    {
        return response()->json([
            'revision' => $revision->load(['createdBy', 'approvedBy']),
            'snapshot' => $revision->snapshot_data,
            'certificate' => $certificate->only(['id', 'code', 'customer_id', 'product_id']),
        ]);
    }

    /**
     * Get approvers list (JSON API)
     */
    public function approvers(QualityCertificate $certificate)
    {
        return response()->json([
            'approvers' => User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['quality-manager', 'lab-manager', 'director']);
            })->select('id', 'name', 'email', 'role')->get(),
        ]);
    }

    /**
     * Helper methods
     */
    private function getComplianceStats(QualityCertificate $certificate): array
    {
        $revisions = $certificate->revisions()->count();
        $logs = ISOActivityLog::where('subject_type', QualityCertificate::class)
            ->where('subject_id', $certificate->id)
            ->count();

        $compliantLogs = ISOActivityLog::where('subject_type', QualityCertificate::class)
            ->where('subject_id', $certificate->id)
            ->whereJsonContains('properties->change_reason', '*')
            ->count();

        return [
            'revision_count' => $revisions,
            'activity_count' => $logs,
            'compliance_rate' => $logs > 0 ? round(($compliantLogs / $logs) * 100, 2) : 100,
            'last_audit' => $certificate->updated_at,
        ];
    }

    private function getUpdatableFields(): array
    {
        return [
            ['name' => 'status', 'label' => 'Estado do certificado'],
            ['name' => 'obs', 'label' => 'Observações'],
            ['name' => 'validated_by', 'label' => 'Validado por'],
            ['name' => 'extra_data', 'label' => 'Dados adicionais'],
        ];
    }

    private function getApprovers()
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['quality-manager', 'lab-manager', 'director', 'admin']);
        })->select('id', 'name', 'email')->get();
    }

    private function calculateRevisionDiff($certificate, $revision): array
    {
        // Implementation
        return [];
    }

    // In App\Http\Controllers\IsoRevisionController.php

    /**
     * Compare two revisions and return detailed differences
     */
    private function compareRevisions(
        QualityCertificateRevision $revisionA,
        QualityCertificateRevision $revisionB
    ): array {
        $snapshotA = $revisionA->snapshot_data ?? [];
        $snapshotB = $revisionB->snapshot_data ?? [];

        $differences = [];

        // 1. Compare Certificate Data
        $certificateDiff = $this->compareCertificateData(
            $snapshotA['certificate'] ?? [],
            $snapshotB['certificate'] ?? []
        );

        if (! empty($certificateDiff)) {
            $differences[] = [
                'category' => 'certificate',
                'label' => 'Dados do certificado',
                'items' => $certificateDiff,
                'count' => count($certificateDiff),
            ];
        }

        // 2. Compare Related Data
        $relatedDiff = $this->compareRelatedData(
            $snapshotA['relations'] ?? [],
            $snapshotB['relations'] ?? []
        );

        if (! empty($relatedDiff)) {
            $differences[] = [
                'category' => 'related',
                'label' => 'Dados relacionados',
                'items' => $relatedDiff,
                'count' => count($relatedDiff),
            ];
        }

        // 3. Compare ISO Compliance Metadata
        $isoDiff = $this->compareIsoMetadata(
            $revisionA->compliance_metadata ?? [],
            $revisionB->compliance_metadata ?? []
        );

        if (! empty($isoDiff)) {
            $differences[] = [
                'category' => 'iso',
                'label' => 'Conformidade ISO',
                'items' => $isoDiff,
                'count' => count($isoDiff),
            ];
        }

        // 4. Compare Revision Metadata
        $metadataDiff = $this->compareRevisionMetadata($revisionA, $revisionB);

        if (! empty($metadataDiff)) {
            $differences[] = [
                'category' => 'metadata',
                'label' => 'Metadados da revisão',
                'items' => $metadataDiff,
                'count' => count($metadataDiff),
            ];
        }

        return $differences;
    }

    /**
     * Compare certificate data between two snapshots
     */
    private function compareCertificateData(array $certificateA, array $certificateB): array
    {
        $differences = [];

        $allKeys = array_unique(array_merge(
            array_keys($certificateA),
            array_keys($certificateB)
        ));

        // Define important fields with labels
        $fieldLabels = [
            'status' => 'Estado do certificado',
            'obs' => 'Observações',
            'validated_by' => 'Validado por',
            'validated_at' => 'Data de validação',
            'file_path' => 'Caminho do ficheiro',
            'code' => 'Código do certificado',
            'validated_on_behalf_of' => 'Validado em nome de',
            'extra_data' => 'Dados adicionais',
            'deleted_at' => 'Eliminado em',
            'created_at' => 'Criado em',
            'updated_at' => 'Actualizado em',
        ];

        foreach ($allKeys as $field) {
            $valueA = $certificateA[$field] ?? null;
            $valueB = $certificateB[$field] ?? null;

            // Skip certain fields from comparison
            if (in_array($field, ['id', 'updated_at'])) {
                continue;
            }

            // Format values for comparison
            $formattedValueA = $this->formatValueForComparison($valueA);
            $formattedValueB = $this->formatValueForComparison($valueB);

            if ($formattedValueA !== $formattedValueB) {
                $changeType = $this->determineChangeType($valueA, $valueB);
                $impact = $this->determineFieldImpact($field);

                $differences[] = [
                    'field' => $field,
                    'label' => $fieldLabels[$field] ?? $this->formatFieldName($field),
                    'valueA' => $formattedValueA,
                    'valueB' => $formattedValueB,
                    'change_type' => $changeType,
                    'impact' => $impact,
                    'category' => 'certificate',
                    'description' => $this->getFieldDescription($field),
                    'notes' => $this->getChangeNotes($field, $valueA, $valueB),
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare related data (collection, results, etc.)
     */
    private function compareRelatedData(array $relationsA, array $relationsB): array
    {
        $differences = [];

        // Compare collection product
        $collectionDiff = $this->compareCollectionProduct(
            $relationsA['collection_product'] ?? null,
            $relationsB['collection_product'] ?? null
        );

        if (! empty($collectionDiff)) {
            $differences = array_merge($differences, $collectionDiff);
        }

        // Compare test results
        $resultsDiff = $this->compareTestResults(
            $relationsA['results'] ?? [],
            $relationsB['results'] ?? []
        );

        if (! empty($resultsDiff)) {
            $differences = array_merge($differences, $resultsDiff);
        }

        // Compare customer data
        $customerDiff = $this->compareCustomerData(
            $relationsA['customer'] ?? null,
            $relationsB['customer'] ?? null
        );

        if (! empty($customerDiff)) {
            $differences = array_merge($differences, $customerDiff);
        }

        // Compare product data
        $productDiff = $this->compareProductData(
            $relationsA['product'] ?? null,
            $relationsB['product'] ?? null
        );

        if (! empty($productDiff)) {
            $differences = array_merge($differences, $productDiff);
        }

        return $differences;
    }

    /**
     * Compare collection product data
     */
    private function compareCollectionProduct(?array $collectionA, ?array $collectionB): array
    {
        $differences = [];

        // If one exists but not the other
        if ($collectionA && ! $collectionB) {
            $differences[] = [
                'field' => 'collection_product',
                'label' => 'Produto da recolha',
                'valueA' => 'Presente',
                'valueB' => 'Em falta',
                'change_type' => 'REMOVED',
                'impact' => 'HIGH',
                'category' => 'related',
                'description' => 'Os dados do produto da recolha foram removidos',
                'notes' => 'Esta é uma alteração significativa que afecta a rastreabilidade',
            ];

            return $differences;
        }

        if (! $collectionA && $collectionB) {
            $differences[] = [
                'field' => 'collection_product',
                'label' => 'Produto da recolha',
                'valueA' => 'Em falta',
                'valueB' => 'Presente',
                'change_type' => 'ADDED',
                'impact' => 'HIGH',
                'category' => 'related',
                'description' => 'Foram adicionados dados do produto da recolha',
                'notes' => 'Esta é uma alteração significativa que afecta a rastreabilidade',
            ];

            return $differences;
        }

        if (! $collectionA && ! $collectionB) {
            return $differences;
        }

        // Compare specific fields
        $importantFields = [
            'comercial_brand' => 'Marca comercial',
            'lot' => 'Número do lote',
            'bl' => 'Número do BL',
            'qty' => 'Quantidade',
            'collection_date' => 'Data de recolha',
            'expiry_date' => 'Data de validade',
            'sample_status' => 'Estado da amostra',
            'status' => 'Estado',
        ];

        foreach ($importantFields as $field => $label) {
            $valueA = $collectionA[$field] ?? null;
            $valueB = $collectionB[$field] ?? null;

            $formattedValueA = $this->formatValueForComparison($valueA);
            $formattedValueB = $this->formatValueForComparison($valueB);

            if ($formattedValueA !== $formattedValueB) {
                $differences[] = [
                    'field' => "collection.{$field}",
                    'label' => "Recolha: {$label}",
                    'valueA' => $formattedValueA,
                    'valueB' => $formattedValueB,
                    'change_type' => $this->determineChangeType($valueA, $valueB),
                    'impact' => $field === 'lot' || $field === 'bl' ? 'HIGH' : 'MEDIUM',
                    'category' => 'related',
                    'description' => "O campo {$label} da recolha foi alterado",
                    'notes' => $this->getCollectionChangeNotes($field, $valueA, $valueB),
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare test results
     */
    private function compareTestResults(array $resultsA, array $resultsB): array
    {
        $differences = [];

        // Group results by parameter for easier comparison
        $resultsByParamA = collect($resultsA)->groupBy('parameter_id')->toArray();
        $resultsByParamB = collect($resultsB)->groupBy('parameter_id')->toArray();

        $allParams = array_unique(array_merge(
            array_keys($resultsByParamA),
            array_keys($resultsByParamB)
        ));

        foreach ($allParams as $paramId) {
            $resultsAForParam = $resultsByParamA[$paramId] ?? [];
            $resultsBForParam = $resultsByParamB[$paramId] ?? [];

            // Check if results were added or removed for this parameter
            if (empty($resultsAForParam) && ! empty($resultsBForParam)) {
                $addedResult = $resultsBForParam[0];
                $differences[] = [
                    'field' => "result.{$paramId}",
                    'label' => 'Resultado de ensaio: '.($addedResult['parameter_label'] ?? "Parâmetro {$paramId}"),
                    'valueA' => 'Não ensaiado',
                    'valueB' => $this->formatResultValue($addedResult),
                    'change_type' => 'ADDED',
                    'impact' => 'MEDIUM',
                    'category' => 'related',
                    'description' => 'Foi adicionado um novo resultado de ensaio',
                    'notes' => $addedResult['approval_notes'] ?? $addedResult['verification_notes'] ?? null,
                ];

                continue;
            }

            if (! empty($resultsAForParam) && empty($resultsBForParam)) {
                $removedResult = $resultsAForParam[0];
                $differences[] = [
                    'field' => "result.{$paramId}",
                    'label' => 'Resultado de ensaio: '.($removedResult['parameter_label'] ?? "Parâmetro {$paramId}"),
                    'valueA' => $this->formatResultValue($removedResult),
                    'valueB' => 'Não ensaiado',
                    'change_type' => 'REMOVED',
                    'impact' => 'HIGH',
                    'category' => 'related',
                    'description' => 'O resultado de ensaio foi removido',
                    'notes' => 'Esta alteração afecta a integridade dos dados do ensaio',
                ];

                continue;
            }

            // Compare values if both exist
            $resultA = $resultsAForParam[0] ?? null;
            $resultB = $resultsBForParam[0] ?? null;

            if ($resultA && $resultB) {
                $valueA = $resultA['approved_value'] ?? $resultA['verified_value'] ?? $resultA['inserted_value'] ?? null;
                $valueB = $resultB['approved_value'] ?? $resultB['verified_value'] ?? $resultB['inserted_value'] ?? null;

                if ($valueA !== $valueB) {
                    $differences[] = [
                        'field' => "result.{$paramId}.value",
                        'label' => 'Resultado de ensaio: '.($resultA['parameter_label'] ?? "Parâmetro {$paramId}"),
                        'valueA' => $this->formatResultValue($resultA),
                        'valueB' => $this->formatResultValue($resultB),
                        'change_type' => 'MODIFIED',
                        'impact' => $this->determineResultImpact($resultA, $resultB),
                        'category' => 'related',
                        'description' => 'O valor do resultado de ensaio foi alterado',
                        'notes' => $this->getResultChangeNotes($resultA, $resultB),
                    ];
                }

                // Compare other important result fields
                $resultFields = [
                    'status' => 'Estado do resultado',
                    'verification_notes' => 'Notas de verificação',
                    'approval_notes' => 'Notas de aprovação',
                    'insertion_notes' => 'Notas de inserção',
                ];

                foreach ($resultFields as $field => $label) {
                    $fieldValueA = $resultA[$field] ?? null;
                    $fieldValueB = $resultB[$field] ?? null;

                    if ($fieldValueA !== $fieldValueB) {
                        $differences[] = [
                            'field' => "result.{$paramId}.{$field}",
                            'label' => "Resultado de ensaio - {$label}: ".($resultA['parameter_label'] ?? "Parâmetro {$paramId}"),
                            'valueA' => $this->formatValueForComparison($fieldValueA),
                            'valueB' => $this->formatValueForComparison($fieldValueB),
                            'change_type' => $this->determineChangeType($fieldValueA, $fieldValueB),
                            'impact' => 'LOW',
                            'category' => 'related',
                            'description' => "O campo {$label} do resultado de ensaio foi alterado",
                            'notes' => null,
                        ];
                    }
                }
            }
        }

        return $differences;
    }

    /**
     * Compare ISO compliance metadata
     */
    private function compareIsoMetadata(array $metadataA, array $metadataB): array
    {
        $differences = [];

        $allKeys = array_unique(array_merge(
            array_keys($metadataA),
            array_keys($metadataB)
        ));

        $metadataLabels = [
            'iso_section' => 'Referência da secção ISO',
            'change_category' => 'Categoria da alteração',
            'risk_assessment' => 'Avaliação de risco',
            'review_required' => 'Revisão necessária',
            'approval_workflow' => 'Fluxo de aprovação',
        ];

        foreach ($allKeys as $key) {
            $valueA = $metadataA[$key] ?? null;
            $valueB = $metadataB[$key] ?? null;

            $formattedValueA = $this->formatValueForComparison($valueA);
            $formattedValueB = $this->formatValueForComparison($valueB);

            if ($formattedValueA !== $formattedValueB) {
                $differences[] = [
                    'field' => "iso.{$key}",
                    'label' => $metadataLabels[$key] ?? $this->formatFieldName($key),
                    'valueA' => $formattedValueA,
                    'valueB' => $formattedValueB,
                    'change_type' => $this->determineChangeType($valueA, $valueB),
                    'impact' => $key === 'risk_assessment' ? 'MEDIUM' : 'LOW',
                    'category' => 'iso',
                    'description' => 'Os metadados de conformidade ISO foram alterados',
                    'notes' => $this->getIsoMetadataNotes($key, $valueA, $valueB),
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare revision metadata
     */
    private function compareRevisionMetadata(QualityCertificateRevision $revisionA, QualityCertificateRevision $revisionB): array
    {
        $differences = [];

        $fields = [
            'change_type' => 'Tipo de alteração',
            'change_reason' => 'Motivo da alteração',
            'version' => 'Versão',
            'revision_number' => 'Número da revisão',
            'effective_date' => 'Data de entrada em vigor',
            'created_by_id' => 'Criada por',
            'approved_by_id' => 'Aprovada por',
        ];

        foreach ($fields as $field => $label) {
            $valueA = $revisionA->$field;
            $valueB = $revisionB->$field;

            $formattedValueA = $this->formatValueForComparison($valueA);
            $formattedValueB = $this->formatValueForComparison($valueB);

            if ($formattedValueA !== $formattedValueB) {
                $impact = $field === 'change_reason' ? 'MEDIUM' : 'LOW';

                // Special handling for user IDs
                if (in_array($field, ['created_by_id', 'approved_by_id'])) {
                    $formattedValueA = $revisionA->{$field === 'created_by_id' ? 'createdBy' : 'approvedBy'}->name ?? "ID do utilizador: {$valueA}";
                    $formattedValueB = $revisionB->{$field === 'created_by_id' ? 'createdBy' : 'approvedBy'}->name ?? "ID do utilizador: {$valueB}";
                    $impact = 'LOW';
                }

                $differences[] = [
                    'field' => "revision.{$field}",
                    'label' => $label,
                    'valueA' => $formattedValueA,
                    'valueB' => $formattedValueB,
                    'change_type' => $this->determineChangeType($valueA, $valueB),
                    'impact' => $impact,
                    'category' => 'metadata',
                    'description' => 'Os metadados da revisão foram alterados',
                    'notes' => $field === 'change_reason' ? 'O motivo da alteração indica o objectivo da revisão' : null,
                ];
            }
        }

        return $differences;
    }

    /**
     * Helper methods
     */
    private function formatValueForComparison($value): string
    {
        if ($value === null) {
            return '[nulo]';
        }

        if ($value === '') {
            return '[vazio]';
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_array($value)) {
            return empty($value) ? '[]' : '[matriz]';
        }

        if (is_object($value)) {
            return '[objecto]';
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    private function formatFieldName(string $field): string
    {
        return str_replace('_', ' ', ucwords($field, '_'));
    }

    private function determineChangeType($oldValue, $newValue): string
    {
        if ($oldValue === null && $newValue !== null) {
            return 'ADDED';
        }

        if ($oldValue !== null && $newValue === null) {
            return 'REMOVED';
        }

        if ($oldValue !== $newValue) {
            return 'MODIFIED';
        }

        return 'UNCHANGED';
    }

    private function determineFieldImpact(string $field): string
    {
        $highImpactFields = [
            'status', 'validated_by', 'validated_at', 'obs',
            'lot', 'bl', 'approved_value', 'verified_value',
        ];

        $mediumImpactFields = [
            'code', 'comercial_brand', 'qty', 'collection_date',
            'expiry_date', 'sample_status', 'change_reason',
        ];

        if (in_array($field, $highImpactFields)) {
            return 'HIGH';
        }

        if (in_array($field, $mediumImpactFields)) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function determineResultImpact(array $resultA, array $resultB): string
    {
        $valueA = $resultA['approved_value'] ?? $resultA['verified_value'] ?? $resultA['inserted_value'] ?? null;
        $valueB = $resultB['approved_value'] ?? $resultB['verified_value'] ?? $resultB['inserted_value'] ?? null;

        // Check if the change crosses a threshold (if reference values exist)
        $minRef = $resultA['min_ref_value'] ?? $resultB['min_ref_value'] ?? null;
        $maxRef = $resultA['max_ref_value'] ?? $resultB['max_ref_value'] ?? null;

        if ($minRef && $maxRef && is_numeric($valueA) && is_numeric($valueB)) {
            $wasWithin = ($valueA >= $minRef && $valueA <= $maxRef);
            $isWithin = ($valueB >= $minRef && $valueB <= $maxRef);

            if ($wasWithin !== $isWithin) {
                return 'HIGH'; // Result moved in/out of acceptable range
            }
        }

        return 'MEDIUM';
    }

    private function formatResultValue(array $result): string
    {
        $value = $result['approved_value'] ?? $result['verified_value'] ?? $result['inserted_value'] ?? null;
        $unit = $result['unit_label'] ?? null;

        if ($value === null) {
            return 'Sem valor';
        }

        if ($unit) {
            return "{$value} {$unit}";
        }

        return (string) $value;
    }

    private function getFieldDescription(string $field): ?string
    {
        $descriptions = [
            'status' => 'Estado geral do certificado',
            'obs' => 'Observações e notas',
            'validated_by' => 'Pessoa que validou o certificado',
            'validated_at' => 'Data e hora da validação',
            'lot' => 'Número do lote do produto',
            'bl' => 'Número do conhecimento de embarque',
            'approved_value' => 'Valor aprovado do resultado do ensaio',
            'change_reason' => 'Motivo da revisão',
            'iso_section' => 'Referência da secção ISO 17025',
            'risk_assessment' => 'Nível de risco da alteração',
        ];

        return $descriptions[$field] ?? null;
    }

    private function getChangeNotes($field, $oldValue, $newValue): ?string
    {
        if ($field === 'status') {
            $statusLabels = [
                0 => 'Rascunho',
                1 => 'Validado',
                2 => 'Retirado',
                3 => 'Reemitido',
            ];

            $oldLabel = $statusLabels[$oldValue] ?? $oldValue;
            $newLabel = $statusLabels[$newValue] ?? $newValue;

            return "O estado foi alterado de '{$oldLabel}' para '{$newLabel}'";
        }

        if ($field === 'validated_at') {
            return 'A data e hora da validação foram actualizadas';
        }

        return null;
    }

    private function getCollectionChangeNotes($field, $oldValue, $newValue): ?string
    {
        if (in_array($field, ['lot', 'bl'])) {
            return 'O identificador de rastreabilidade foi alterado';
        }

        if (in_array($field, ['collection_date', 'expiry_date'])) {
            return 'O campo de data foi actualizado';
        }

        return null;
    }

    private function getResultChangeNotes(array $resultA, array $resultB): ?string
    {
        $notesA = $resultA['approval_notes'] ?? $resultA['verification_notes'] ?? null;
        $notesB = $resultB['approval_notes'] ?? $resultB['verification_notes'] ?? null;

        if ($notesA && $notesB && $notesA !== $notesB) {
            return 'As notas foram actualizadas na revisão mais recente';
        }

        return null;
    }

    private function getIsoMetadataNotes($key, $oldValue, $newValue): ?string
    {
        if ($key === 'risk_assessment') {
            $riskLevels = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
            $oldIndex = array_search($oldValue, $riskLevels);
            $newIndex = array_search($newValue, $riskLevels);

            if ($oldIndex !== false && $newIndex !== false) {
                if ($newIndex > $oldIndex) {
                    return 'O nível de risco aumentou';
                } elseif ($newIndex < $oldIndex) {
                    return 'O nível de risco diminuiu';
                }
            }
        }

        return null;
    }

    private function compareCustomerData(?array $customerA, ?array $customerB): array
    {
        // Implementation similar to collection product comparison
        return [];
    }

    private function compareProductData(?array $productA, ?array $productB): array
    {
        // Implementation similar to collection product comparison
        return [];
    }
}
