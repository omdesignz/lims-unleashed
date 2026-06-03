<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\InventoryNeed;
use App\Models\InventorySupplierAssessment;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Proposal;
use App\Models\QualityCertificate;
use App\Models\ReportStudioTemplate;
use App\Models\Standard;
use App\Models\User;
use App\Models\VAPNonConformity;
use App\Models\VAPSampleEntry;
use App\Settings\GeneralSettings;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request)
    {
        $chartWindow = $this->executiveChartWindow();
        $procurementQueue = InventoryNeed::query()
            ->with([
                'department:id,name',
                'lab:id,name',
                'requestedBy:id,name',
                'items.inventoryItem:id,name,supplier_id',
                'items.inventoryItem.supplier:id,name',
            ])
            ->withCount('items')
            ->where('status', 'approved')
            ->whereNull('inventory_order_id')
            ->orderBy('needed_by_date')
            ->limit(6)
            ->get()
            ->map(fn ($need) => $this->transformProcurementNeed($need));

        return Inertia::render('Dashboard', [
            'record' => User::query()
                ->when($request->input('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->when($request->input('filter'), function ($query, $filter) {
                    if ($filter === 'trashed') {
                        $query->withTrashed();
                    }
                })
                ->paginate(5)
                ->withQueryString()
                ->through(fn ($record) => [
                    'id' => $record->id,
                    'name' => $record->name,
                    'email' => $record->email,
                    'deleted' => (bool) $record->deleted_at,
                ]),
            'stats' => [
                'analysis' => Analysis::count(),
                'invoices' => Invoice::count(),
                'collections' => Collection::count(),
                'customers' => Customer::count(),
                'products' => Product::count(),
                'clients' => Customer::count(),
                'standards' => Standard::count(),
                'certificates' => QualityCertificate::count(),
                'profiles' => Profile::count(),
            ],
            'executive' => [
                'kpis' => [
                    [
                        'label' => 'Propostas aceites',
                        'value' => Proposal::query()->accepted()->count(),
                        'hint' => 'Trabalhos autorizados pelo cliente',
                    ],
                    [
                        'label' => 'Pedidos do portal abertos',
                        'value' => CustomerRequest::query()->whereIn('status', ['pending', 'in_progress'])->count(),
                        'hint' => 'Pedidos pendentes de resposta ou validação',
                    ],
                    [
                        'label' => 'Amostras ativas',
                        'value' => VAPSampleEntry::query()->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA'])->count(),
                        'hint' => 'Fluxo operacional ainda em curso',
                    ],
                    [
                        'label' => 'Recebível em aberto',
                        'value' => number_format((float) Invoice::query()->sum('amount_due'), 2, '.', ' '),
                        'hint' => 'Montante agregado de faturas por liquidar',
                    ],
                    [
                        'label' => 'Fornecedores de alto risco',
                        'value' => InventorySupplierAssessment::query()->whereIn('risk_level', ['high', 'critical'])->where('is_active', true)->count(),
                        'hint' => 'Avaliações que exigem seguimento reforçado',
                    ],
                    [
                        'label' => 'Revisões de fornecedor próximas',
                        'value' => InventorySupplierAssessment::query()->whereNotNull('next_review_at')->whereDate('next_review_at', '<=', now()->addDays(30))->where('is_active', true)->count(),
                        'hint' => 'Avaliações a rever nos próximos 30 dias',
                    ],
                    [
                        'label' => 'Necessidades à espera de compra',
                        'value' => InventoryNeed::query()->where('status', 'approved')->whereNull('inventory_order_id')->count(),
                        'hint' => 'Necessidades aprovadas sem pedido de compra associado',
                    ],
                    [
                        'label' => 'NCs de recepção abertas',
                        'value' => VAPNonConformity::query()->where('occurrence_area', 'procurement_receipt')->whereNotIn('status', ['closed', 'resolved'])->count(),
                        'hint' => 'Desvios formais registados no recebimento de encomendas',
                    ],
                ],
                'top_customers' => Customer::query()
                    ->withCount([
                        'warehouses',
                        'warehouses as portal_warehouses_count',
                    ])
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(['id', 'name', 'code'])
                    ->map(fn ($customer) => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'code' => $customer->code,
                        'warehouses_count' => $customer->warehouses_count,
                    ]),
                'supplier_watchlist' => InventorySupplierAssessment::query()
                    ->with(['supplier:id,name', 'department:id,name'])
                    ->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereIn('risk_level', ['high', 'critical'])
                            ->orWhereIn('status', ['conditional', 'suspended', 'rejected'])
                            ->orWhereDate('next_review_at', '<=', now()->addDays(30));
                    })
                    ->orderByRaw("case when risk_level = 'critical' then 0 when risk_level = 'high' then 1 else 2 end")
                    ->orderBy('next_review_at')
                    ->limit(6)
                    ->get()
                    ->map(fn ($assessment) => [
                        'id' => $assessment->id,
                        'supplier_name' => $assessment->supplier?->name,
                        'department_name' => $assessment->department?->name,
                        'status' => $assessment->status,
                        'risk_level' => $assessment->risk_level,
                        'total_score' => $assessment->total_score,
                        'next_review_at' => $assessment->next_review_at?->toDateString(),
                    ]),
                'procurement_queue' => $procurementQueue,
                'receiving_non_conformities' => VAPNonConformity::query()
                    ->with(['department:id,name'])
                    ->where('occurrence_area', 'procurement_receipt')
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->latest('reported_at')
                    ->limit(6)
                    ->get(['id', 'department_id', 'nc_number', 'title', 'status', 'severity', 'reported_at', 'batch_number'])
                    ->map(fn ($record) => [
                        'id' => $record->id,
                        'nc_number' => $record->nc_number,
                        'title' => $record->title,
                        'status' => $record->status,
                        'severity' => $record->severity,
                        'reported_at' => $record->reported_at?->toDateString(),
                        'batch_number' => $record->batch_number,
                        'department_name' => $record->department?->name,
                    ]),
                'charts' => [
                    'throughput' => $this->buildThroughputTrendChart($chartWindow),
                    'sample_status' => $this->buildStatusDistributionChart(
                        VAPSampleEntry::query()
                            ->select('status', DB::raw('count(*) as aggregate'))
                            ->groupBy('status')
                            ->pluck('aggregate', 'status')
                            ->all(),
                        [
                            'POR_INICIAR' => 'Por iniciar',
                            'EN_PROGRESO' => 'Em progresso',
                            'EN_PAUSA' => 'Em pausa',
                            'COMPLETADO' => 'Completado',
                            'CANCELADO' => 'Cancelado',
                        ]
                    ),
                    'portal_requests' => $this->buildStatusDistributionChart(
                        CustomerRequest::query()
                            ->selectRaw("COALESCE(NULLIF(status, ''), CASE WHEN answered = 1 THEN 'completed' ELSE 'pending' END) as state, count(*) as aggregate")
                            ->groupBy('state')
                            ->pluck('aggregate', 'state')
                            ->all(),
                        [
                            'pending' => 'Pendentes',
                            'in_progress' => 'Em tratamento',
                            'completed' => 'Concluídos',
                            'resolved' => 'Resolvidos',
                            'cancelled' => 'Cancelados',
                        ]
                    ),
                    'procurement_readiness' => $this->buildReadinessDistributionChart($procurementQueue),
                    'supplier_risk' => $this->buildStatusDistributionChart(
                        InventorySupplierAssessment::query()
                            ->where('is_active', true)
                            ->select('risk_level', DB::raw('count(*) as aggregate'))
                            ->groupBy('risk_level')
                            ->pluck('aggregate', 'risk_level')
                            ->all(),
                        [
                            'low' => 'Risco baixo',
                            'medium' => 'Risco médio',
                            'high' => 'Risco elevado',
                            'critical' => 'Risco crítico',
                        ]
                    ),
                    'receiving_nc_severity' => $this->buildStatusDistributionChart(
                        VAPNonConformity::query()
                            ->where('occurrence_area', 'procurement_receipt')
                            ->whereNotIn('status', ['closed', 'resolved'])
                            ->select('severity', DB::raw('count(*) as aggregate'))
                            ->groupBy('severity')
                            ->pluck('aggregate', 'severity')
                            ->all(),
                        [
                            'low' => 'Baixa',
                            'medium' => 'Média',
                            'high' => 'Alta',
                            'critical' => 'Crítica',
                        ]
                    ),
                ],
                'export_url' => route('dashboard.export'),
                'report_studios_url' => route('report-studios.index'),
                'report_templates' => [
                    'executive' => ReportStudioTemplate::query()->where('studio_type', 'executive')->count(),
                    'analysis' => ReportStudioTemplate::query()->where('studio_type', 'analysis')->count(),
                ],
            ],
            'query' => $request->only(['search', 'trashed']),
        ]);
    }

    public function export()
    {
        $payload = [
            'kpis' => [
                [
                    'label' => 'Propostas aceites',
                    'value' => Proposal::query()->accepted()->count(),
                    'hint' => 'Trabalhos autorizados pelo cliente',
                ],
                [
                    'label' => 'Pedidos do portal abertos',
                    'value' => CustomerRequest::query()->whereIn('status', ['pending', 'in_progress'])->count(),
                    'hint' => 'Pedidos pendentes de resposta ou validação',
                ],
                [
                    'label' => 'Amostras ativas',
                    'value' => VAPSampleEntry::query()->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA'])->count(),
                    'hint' => 'Fluxo operacional ainda em curso',
                ],
                [
                    'label' => 'Recebível em aberto',
                    'value' => number_format((float) Invoice::query()->sum('amount_due'), 2, '.', ' '),
                    'hint' => 'Montante agregado de faturas por liquidar',
                ],
                [
                    'label' => 'Fornecedores de alto risco',
                    'value' => InventorySupplierAssessment::query()->whereIn('risk_level', ['high', 'critical'])->where('is_active', true)->count(),
                    'hint' => 'Avaliações que exigem seguimento reforçado',
                ],
                [
                    'label' => 'Revisões de fornecedor próximas',
                    'value' => InventorySupplierAssessment::query()->whereNotNull('next_review_at')->whereDate('next_review_at', '<=', now()->addDays(30))->where('is_active', true)->count(),
                    'hint' => 'Avaliações a rever nos próximos 30 dias',
                ],
                [
                    'label' => 'Necessidades à espera de compra',
                    'value' => InventoryNeed::query()->where('status', 'approved')->whereNull('inventory_order_id')->count(),
                    'hint' => 'Necessidades aprovadas sem pedido de compra associado',
                ],
                [
                    'label' => 'NCs de recepção abertas',
                    'value' => VAPNonConformity::query()->where('occurrence_area', 'procurement_receipt')->whereNotIn('status', ['closed', 'resolved'])->count(),
                    'hint' => 'Desvios formais registados no recebimento de encomendas',
                ],
            ],
            'top_customers' => Customer::query()
                ->withCount(['warehouses'])
                ->latest('updated_at')
                ->limit(5)
                ->get(['id', 'name', 'code'])
                ->map(fn ($customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'code' => $customer->code,
                    'warehouses_count' => $customer->warehouses_count,
                ])
                ->all(),
            'supplier_watchlist' => InventorySupplierAssessment::query()
                ->with(['supplier:id,name', 'department:id,name'])
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereIn('risk_level', ['high', 'critical'])
                        ->orWhereIn('status', ['conditional', 'suspended', 'rejected'])
                        ->orWhereDate('next_review_at', '<=', now()->addDays(30));
                })
                ->orderByRaw("case when risk_level = 'critical' then 0 when risk_level = 'high' then 1 else 2 end")
                ->orderBy('next_review_at')
                ->limit(6)
                ->get()
                ->map(fn ($assessment) => [
                    'supplier_name' => $assessment->supplier?->name,
                    'department_name' => $assessment->department?->name,
                    'status' => $assessment->status,
                    'risk_level' => $assessment->risk_level,
                    'total_score' => $assessment->total_score,
                    'next_review_at' => $assessment->next_review_at?->toDateString(),
                ])
                ->all(),
        ];

        if (request()->string('format')->lower()->value() === 'pdf') {
            $studioPayload = app(ReportStudioPdfBuilder::class)->buildExecutiveReportPayload($payload, app(GeneralSettings::class));
            $filename = 'executive-dashboard-'.now()->format('Ymd-His').'.pdf';
            $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('executive', $studioPayload, $filename);

            return response($renderedPdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="executive-dashboard-'.now()->format('Ymd-His').'.csv"',
        ];

        $rows = [
            ['metric', 'value'],
            ['accepted_proposals', $payload['kpis'][0]['value']],
            ['open_portal_requests', $payload['kpis'][1]['value']],
            ['active_samples', $payload['kpis'][2]['value']],
            ['completed_samples', VAPSampleEntry::query()->where('status', 'COMPLETADO')->count()],
            ['open_amount_due', Invoice::query()->sum('amount_due')],
            ['issued_certificates', QualityCertificate::count()],
            ['high_risk_suppliers', $payload['kpis'][4]['value']],
            ['supplier_reviews_due', $payload['kpis'][5]['value']],
            ['needs_awaiting_purchase', $payload['kpis'][6]['value']],
            ['receiving_non_conformities_open', $payload['kpis'][7]['value']],
        ];

        return response()->stream(function () use ($rows) {
            $stream = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }

            fclose($stream);
        }, 200, $headers);
    }

    private function transformProcurementNeed(InventoryNeed $need): array
    {
        $supplierIds = $need->items
            ->pluck('inventoryItem.supplier_id')
            ->filter()
            ->unique()
            ->values();

        $assessments = InventorySupplierAssessment::query()
            ->whereIn('inventory_item_supplier_id', $supplierIds)
            ->orderByDesc('assessment_date')
            ->get()
            ->unique('inventory_item_supplier_id')
            ->keyBy('inventory_item_supplier_id');

        $missingSupplierCount = 0;
        $unassessedSupplierCount = 0;
        $blockedSupplierCount = 0;
        $conditionalSupplierCount = 0;

        foreach ($need->items as $item) {
            $supplierId = $item->inventoryItem?->supplier_id;

            if ($supplierId === null) {
                $missingSupplierCount++;

                continue;
            }

            $assessment = $assessments->get($supplierId);

            if ($assessment === null) {
                $unassessedSupplierCount++;

                continue;
            }

            if (in_array($assessment->status, ['rejected', 'suspended'], true) || ($assessment->risk_level === 'critical' && ! $assessment->approved_supplier)) {
                $blockedSupplierCount++;

                continue;
            }

            if ($assessment->status === 'conditional' || in_array($assessment->risk_level, ['high', 'critical'], true)) {
                $conditionalSupplierCount++;
            }
        }

        $readiness = 'ready';

        if ($blockedSupplierCount > 0) {
            $readiness = 'blocked';
        } elseif ($missingSupplierCount > 0 || $unassessedSupplierCount > 0) {
            $readiness = 'incomplete';
        } elseif ($conditionalSupplierCount > 0) {
            $readiness = 'attention';
        }

        return [
            'id' => $need->id,
            'reference' => $need->reference,
            'department_name' => $need->department?->name,
            'lab_name' => $need->lab?->name,
            'requested_by_name' => $need->requestedBy?->name,
            'needed_by_date' => $need->needed_by_date?->toDateString(),
            'items_count' => $need->items_count,
            'justification' => $need->justification,
            'supplier_readiness' => $readiness,
            'supplier_summary' => [
                'missing_supplier_count' => $missingSupplierCount,
                'unassessed_supplier_count' => $unassessedSupplierCount,
                'blocked_supplier_count' => $blockedSupplierCount,
                'conditional_supplier_count' => $conditionalSupplierCount,
                'supplier_count' => $supplierIds->count(),
            ],
        ];
    }

    private function executiveChartWindow(): SupportCollection
    {
        return collect(range(5, 0))
            ->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo))
            ->push(now()->startOfMonth());
    }

    private function buildThroughputTrendChart(SupportCollection $chartWindow): array
    {
        $categories = $chartWindow
            ->map(fn (Carbon $month) => $month->translatedFormat('M Y'))
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Propostas aceites',
                    'data' => $this->buildMonthlySeries(
                        Proposal::query()->accepted(),
                        $chartWindow
                    ),
                ],
                [
                    'name' => 'Amostras concluídas',
                    'data' => $this->buildMonthlySeries(
                        VAPSampleEntry::query()->where('status', 'COMPLETADO'),
                        $chartWindow
                    ),
                ],
                [
                    'name' => 'Certificados emitidos',
                    'data' => $this->buildMonthlySeries(
                        QualityCertificate::query(),
                        $chartWindow,
                        'validated_at'
                    ),
                ],
            ],
        ];
    }

    private function buildMonthlySeries($query, SupportCollection $chartWindow, string $dateColumn = 'created_at'): array
    {
        $firstMonth = $chartWindow->first()?->copy() ?? now()->startOfMonth()->subMonths(5);
        $lastMonth = $chartWindow->last()?->copy()->endOfMonth() ?? now()->endOfMonth();

        $aggregates = (clone $query)
            ->whereBetween($dateColumn, [$firstMonth, $lastMonth])
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as month_key, count(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        return $chartWindow
            ->map(fn (Carbon $month) => (int) ($aggregates[$month->format('Y-m')] ?? 0))
            ->values()
            ->all();
    }

    private function buildStatusDistributionChart(array $distribution, array $labels): array
    {
        $items = collect($labels)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'value' => (int) ($distribution[$key] ?? 0),
            ])
            ->values();

        return [
            'labels' => $items->pluck('label')->all(),
            'series' => $items->pluck('value')->all(),
            'total' => $items->sum('value'),
        ];
    }

    private function buildReadinessDistributionChart(SupportCollection $procurementQueue): array
    {
        $labels = [
            'ready' => 'Pronto para compra',
            'attention' => 'Compra com atenção',
            'incomplete' => 'Dados incompletos',
            'blocked' => 'Compra bloqueada',
        ];

        $distribution = $procurementQueue
            ->groupBy('supplier_readiness')
            ->map(fn (SupportCollection $records) => $records->count())
            ->all();

        return $this->buildStatusDistributionChart($distribution, $labels);
    }
}
