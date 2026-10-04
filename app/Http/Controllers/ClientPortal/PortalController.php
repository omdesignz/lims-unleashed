<?php

namespace App\Http\Controllers\ClientPortal;

use App\Actions\SubmitPortalServiceRequest;
use App\Exports\CollectionParametersSheetExport;
use App\Http\Requests\Portal\StorePortalCustomerRequest;
use App\Http\Resources\CollectionProductResource;
use App\Http\Resources\CustomerRequestCategoryResource;
use App\Http\Resources\CustomerRequestResource;
use App\Http\Resources\FAQResource;
use App\Http\Resources\PortalDocumentResource;
use App\Http\Resources\WarehouseResource;
use App\Models\CollectionProduct;
use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\CustomerRequest;
use App\Models\CustomerRequestCategory;
use App\Models\FAQ;
use App\Models\Invoice;
use App\Models\Matrix;
use App\Models\PackagingCategory;
use App\Models\Passkey;
use App\Models\PortalServiceInvitation;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Quote;
use App\Models\Receipt;
use App\Models\User;
use App\Services\PortalDocumentAccess;
use App\Services\SharedDocumentDeliveryAccess;
use App\Settings\GeneralSettings;
use App\Support\PdfResponse;
use App\Support\ReportStudioPdfBuilder;
use App\Support\ReportStudioPdfRenderer;
use App\Support\SpreadsheetDownloadResponder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;
use NumberToWords\NumberToWords;
use PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalController extends Controller
{
    public function __construct(
        private readonly PortalDocumentAccess $documents,
        private readonly SharedDocumentDeliveryAccess $documentIntegrity,
    ) {}

    private function portalWarehouse()
    {
        return auth()->guard('portal')->user();
    }

    private function serviceCatalog(): array
    {
        return [
            [
                'type' => 'complaint',
                'title' => 'Registar reclamação',
                'description' => 'Registe uma reclamação formal para tratamento e rastreabilidade.',
                'icon' => 'shield',
            ],
            [
                'type' => 'analysis_request',
                'title' => 'Solicitar análise',
                'description' => 'Peça novas análises laboratoriais com os perfis e datas pretendidas.',
                'icon' => 'beaker',
            ],
            [
                'type' => 'collection_request',
                'title' => 'Solicitar colheita',
                'description' => 'Agende uma colheita no seu local com instruções operacionais.',
                'icon' => 'truck',
            ],
            [
                'type' => 'certificate_support',
                'title' => 'Apoio a certificados',
                'description' => 'Peça revisão, reemissão ou esclarecimentos sobre certificados.',
                'icon' => 'certificate',
            ],
            [
                'type' => 'document_request',
                'title' => 'Pedir documentos',
                'description' => 'Solicite cópias, guias, comprovativos e outros documentos.',
                'icon' => 'document',
            ],
            [
                'type' => 'billing_support',
                'title' => 'Apoio de facturação',
                'description' => 'Abra pedidos sobre facturas, recibos, pagamentos e notas de crédito.',
                'icon' => 'currency',
            ],
            [
                'type' => 'general_support',
                'title' => 'Suporte geral',
                'description' => 'Envie qualquer outro pedido operacional ou comercial.',
                'icon' => 'support',
            ],
        ];
    }

    private function portalChartWindow(): SupportCollection
    {
        return collect(range(6, 0))
            ->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));
    }

    private function buildPortalRequestTrendChart(int $warehouseId): array
    {
        $chartWindow = $this->portalChartWindow();
        $months = $chartWindow->map(fn (Carbon $month) => $month->format('Y-m'))->all();
        $categories = $chartWindow->map(fn (Carbon $month) => $month->translatedFormat('M Y'))->all();

        $requests = CustomerRequest::query()
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month_key, request_type, COUNT(*) as aggregate")
            ->where('warehouse_id', $warehouseId)
            ->where('customer_id', $this->portalWarehouse()->customer_id)
            ->whereDate('created_at', '>=', $chartWindow->first()->copy()->startOfMonth()->toDateString())
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM'), request_type")
            ->get()
            ->groupBy('request_type');

        $seriesFor = function (string $requestType) use ($requests, $months): array {
            $lookup = collect($requests->get($requestType, []))
                ->mapWithKeys(fn ($row) => [$row->month_key => (int) $row->aggregate]);

            return array_map(
                fn (string $month) => (int) $lookup->get($month, 0),
                $months
            );
        };

        $supportLookup = $requests
            ->except(['analysis_request', 'collection_request'])
            ->flatten(1)
            ->groupBy('month_key')
            ->map(fn ($rows) => (int) $rows->sum('aggregate'));

        return [
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Análises',
                    'data' => $seriesFor('analysis_request'),
                ],
                [
                    'name' => 'Colheitas',
                    'data' => $seriesFor('collection_request'),
                ],
                [
                    'name' => 'Outros pedidos',
                    'data' => array_map(
                        fn (string $month) => (int) $supportLookup->get($month, 0),
                        $months
                    ),
                ],
            ],
        ];
    }

    private function buildPortalStatusChart(int $warehouseId): array
    {
        $requests = CustomerRequest::query()
            ->where('warehouse_id', $warehouseId)
            ->where('customer_id', $this->portalWarehouse()->customer_id)
            ->get(['status', 'answered']);

        $statusCounts = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($requests as $request) {
            $status = $request->status;

            if (! $status) {
                $status = $request->answered ? 'completed' : 'pending';
            }

            if (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status]++;
            }
        }

        return [
            'labels' => [
                'Pendente',
                'Em tratamento',
                'Concluída',
                'Cancelada',
            ],
            'series' => [
                $statusCounts['pending'],
                $statusCounts['in_progress'],
                $statusCounts['completed'],
                $statusCounts['cancelled'],
            ],
        ];
    }

    private function buildPortalAssetChart(array $stats): array
    {
        return [
            'labels' => [
                'Certificados',
                'Colheitas',
                'Facturas',
                'Recibos',
                'Guias',
                'Notas crédito',
            ],
            'series' => [
                (int) ($stats['qualitycertificates'] ?? 0),
                (int) ($stats['collections'] ?? 0),
                (int) ($stats['invoices'] ?? 0),
                (int) ($stats['receipts'] ?? 0),
                (int) ($stats['contractguides'] ?? 0),
                (int) ($stats['creditnotes'] ?? 0),
            ],
        ];
    }

    private function portalRequestBaseQuery(): Builder
    {
        return CustomerRequest::query()
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->where('customer_id', $this->portalWarehouse()->customer_id)
            ->with('category', 'customer', 'warehouse', 'lab:id,name')
            ->when(request()->input('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->when(request()->input('status_filter'), function ($query, $status) {
                if ($status === 'completed') {
                    $query->where(function ($nested) {
                        $nested->where('status', 'completed')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('status')->where('answered', true);
                            });
                    });

                    return;
                }

                if ($status === 'pending') {
                    $query->where(function ($nested) {
                        $nested->where('status', 'pending')
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('status')->where('answered', false);
                            });
                    });

                    return;
                }

                $query->where('status', $status);
            })
            ->when(request()->input('request_type'), function ($query, $requestType) {
                $query->where('request_type', $requestType);
            })
            ->latest();
    }

    private function portalRequestCategories()
    {
        return CustomerRequestCategoryResource::collection(
            CustomerRequestCategory::query()->orderBy('name')->get()
        );
    }

    private function portalCollectionBaseQuery(): Builder
    {
        return CollectionProduct::query()
            ->with([
                'product',
                'code.samples',
                'code.completed_analysis',
                'code.pending_analysis',
                'code.in_progress_analysis',
                'customer',
                'warehouse',
                'temperature',
                'packaging',
                'vehicle',
                'collection.collectionable',
                'quality_certificate',
            ])
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->when(request()->input('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('lot', 'like', "%{$search}%")
                        ->orWhere('comercial_brand', 'like', "%{$search}%")
                        ->orWhereRelation('code', 'code', 'like', "%{$search}%")
                        ->orWhereRelation('product', 'name', 'like', "%{$search}%");
                });
            })
            ->when(request()->input('status_filter'), function ($query, $status) {
                if ($status === 'certificate_ready') {
                    $query->whereHas('quality_certificate');

                    return;
                }

                if ($status === 'analysis_pending') {
                    $query->whereDoesntHave('quality_certificate')
                        ->where(function ($nested) {
                            $nested->whereRelation('collection.collectionable', 'placed_analysis', true)
                                ->orWhereHas('code.pending_analysis');
                        });

                    return;
                }

                if ($status === 'in_progress') {
                    $query->whereHas('code.in_progress_analysis');
                }
            })
            ->when(request()->input('type_filter'), function ($query, $type) {
                $query->whereRelation('collection', 'collectionable_type', $type);
            })
            ->when(request()->input('date_filter'), function ($query, $dateFilter) {
                $startDate = match ($dateFilter) {
                    'last_week' => now()->subWeek(),
                    'last_month' => now()->subMonth(),
                    'last_quarter' => now()->subMonths(3),
                    default => null,
                };

                if ($startDate) {
                    $query->whereDate('collection_date', '>=', $startDate->toDateString());
                }
            })
            ->latest('collection_date')
            ->latest('id');
    }

    private function resolvePortalCategoryId(string $requestType, ?int $categoryId = null): ?int
    {
        if ($categoryId) {
            return $categoryId;
        }

        $mapping = [
            'analysis_request' => ['análise', 'analise', 'ensaio'],
            'complaint' => ['reclamação', 'reclamacao', 'complaint'],
            'collection_request' => ['colheita', 'coleta'],
            'certificate_support' => ['certificado'],
            'document_request' => ['documento', 'guia', 'comprovativo'],
            'billing_support' => ['fatura', 'factura', 'pagamento', 'recibo'],
            'general_support' => ['suporte', 'geral'],
        ];

        $terms = $mapping[$requestType] ?? [];

        if ($terms === []) {
            return CustomerRequestCategory::query()->value('id');
        }

        $category = CustomerRequestCategory::query()
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('name', 'like', '%'.$term.'%');
                }
            })
            ->orderBy('name')
            ->first();

        return $category?->id ?? CustomerRequestCategory::query()->value('id');
    }

    public function dashboard()
    {
        $warehouse = $this->portalWarehouse();
        $recentRequests = $this->portalRequestBaseQuery()->limit(5)->get();
        $stats = [
            'overdue' => 'AOA '.number_format($this->documents->query(Invoice::class, $warehouse)->unpaid($warehouse->id)->sum('amount_due'), 2),
            'qualitycertificates' => $this->documents->releasedCertificates($warehouse)->count(),
            'contractguides' => $this->documents->query(ContractGuide::class, $warehouse)->count(),
            'collections' => CollectionProduct::where('warehouse_id', $warehouse->id)->count(),
            'invoices' => $this->documents->query(Invoice::class, $warehouse)->count(),
            'creditnotes' => $this->documents->query(CreditNote::class, $warehouse)->count(),
            'receipts' => $this->documents->query(Receipt::class, $warehouse)->count(),
            'open_requests' => $this->portalRequestBaseQuery()->whereIn('status', ['pending', 'in_progress'])->count(),
            'analysis_requests' => $this->portalRequestBaseQuery()->where('request_type', 'analysis_request')->count(),
            'collection_requests' => $this->portalRequestBaseQuery()->where('request_type', 'collection_request')->count(),
        ];

        return Inertia::render('ClientPortal/Dashboard', [
            'auth' => [
                'user' => WarehouseResource::make($warehouse),
            ],
            'stats' => $stats,
            'charts' => [
                'request_trend' => $this->buildPortalRequestTrendChart($warehouse->id),
                'request_status' => $this->buildPortalStatusChart($warehouse->id),
                'asset_visibility' => $this->buildPortalAssetChart($stats),
            ],
            'services' => $this->serviceCatalog(),
            'recentRequests' => CustomerRequestResource::collection($recentRequests),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function faqs()
    {

        return Inertia::render('ClientPortal/FAQs/Index', [
            'record' => FAQResource::collection(
                FAQ::query()
                    ->with('category', 'answers')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('description', 'like', "%{$search}%");
                    })
                    ->when(request()->input('category'), function ($query, $category) {
                        $query->where('category_id', $category);
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => 'Pergunta',
                    'value' => 'description',
                ],
                [
                    'name' => 'Categoria',
                    'value' => 'category',
                ],
            ],
            'query' => request()->only(['search', 'category', 'filter']),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function requests()
    {
        $warehouse = $this->portalWarehouse();
        $presetType = request()->string('request_type')->toString() ?: request()->string('service')->toString();
        $requests = $this->portalRequestBaseQuery()
            ->paginate(6)
            ->withQueryString();

        return Inertia::render('ClientPortal/Requests/Index', [
            'invitations' => PortalServiceInvitation::query()->availableTo($warehouse)
                ->with('lab:id,name')->orderBy('expires_at')->get()->map(fn ($invitation): array => [
                    'token' => $invitation->token, 'lab_name' => $invitation->lab->name,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ]),
            'warehouse' => WarehouseResource::make($warehouse),
            'request_categories' => $this->portalRequestCategories(),
            'service_catalog' => $this->serviceCatalog(),
            'analysis_profiles' => Profile::query()
                ->select('id', 'name', 'category_id')
                ->orderBy('name')
                ->limit(100)
                ->get()
                ->map(fn ($profile) => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'category_id' => $profile->category_id,
                ]),
            'products' => Product::query()
                ->with('matrix:id,description')
                ->orderBy('name')
                ->get(['id', 'name', 'matrix_id'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'matrix_id' => $product->matrix_id,
                    'matrix' => $product->matrix?->description,
                ]),
            'matrixes' => Matrix::query()->orderBy('description')->get(['id', 'description']),
            'packaging_categories' => PackagingCategory::query()->orderBy('name')->get(['id', 'name', 'description']),
            'record' => CustomerRequestResource::collection($requests),
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => 'Título',
                    'value' => 'title',
                ],
                [
                    'name' => 'Tipo',
                    'value' => 'request_type',
                ],
            ],
            'query' => request()->only(['search', 'status_filter', 'request_type', 'page']),
            'prefill' => [
                'request_type' => $presetType ?: null,
                'open_form' => request()->boolean('new'),
                'title' => request()->input('title'),
            ],
        ]);
    }

    public function services()
    {
        return Inertia::render('ClientPortal/Services/Index', [
            'services' => $this->serviceCatalog(),
            'warehouse' => WarehouseResource::make($this->portalWarehouse()),
        ]);
    }

    public function profile()
    {
        return Inertia::render('ClientPortal/Profile', [
            'warehouse' => WarehouseResource::make($this->portalWarehouse()),
            'requestStats' => [
                'total' => $this->portalRequestBaseQuery()->count(),
                'open' => $this->portalRequestBaseQuery()->whereIn('status', ['pending', 'in_progress'])->count(),
                'completed' => $this->portalRequestBaseQuery()->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function security()
    {
        $warehouse = $this->portalWarehouse();
        $passkeys = $warehouse->passkeys()
            ->latest()
            ->get(['id', 'name', 'last_used_at', 'created_at'])
            ->map(fn (Passkey $passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'last_used_at' => optional($passkey->last_used_at)?->toIso8601String(),
                'created_at' => optional($passkey->created_at)?->toIso8601String(),
            ]);

        return Inertia::render('ClientPortal/Security', [
            'warehouse' => WarehouseResource::make($warehouse),
            'sessions' => $this->portalSessions(),
            'passkeys' => $passkeys,
            'security' => [
                'email_verified' => (bool) $warehouse?->email_verified_at,
                'two_factor_enabled' => (bool) $warehouse?->two_factor_secret,
                'two_factor_confirmed' => (bool) $warehouse?->two_factor_confirmed_at,
                'has_password' => ! empty($warehouse?->password),
                'passkey_count' => $passkeys->count(),
                'last_login_at' => $warehouse?->last_login_at,
                'last_activity_at' => $warehouse?->last_activity_at,
            ],
        ]);
    }

    private function portalSessions(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $warehouse = $this->portalWarehouse();

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $warehouse->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) {
                $agent = new Agent;
                $agent->setUserAgent($session->user_agent);

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'is_current_device' => $session->id === request()->session()->getId(),
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'agent' => [
                        'is_desktop' => $agent->isDesktop(),
                        'platform' => $agent->platform() ?: 'Desconhecido',
                        'browser' => $agent->browser() ?: 'Desconhecido',
                    ],
                ];
            })
            ->all();
    }

    /**
     * Display a listing of the resource.
     */
    public function invoices()
    {

        return Inertia::render('ClientPortal/Invoices/Index', [
            'record' => PortalDocumentResource::collection(
                $this->documents->query(Invoice::class, $this->portalWarehouse())
                    ->with('warehouse', 'customer')
                    ->where('warehouse_id', auth()->guard('portal')->user()->id)
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('inv_no', 'like', "%{$search}%");
                    })
                    ->orderByDesc('id')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Emissão',
                    'value' => 'date',
                ],
                [
                    'name' => 'Referência',
                    'value' => 'inv_no',
                ],
                [
                    'name' => 'Total',
                    'value' => 'total',
                ],
            ],
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    public function receipts()
    {

        return Inertia::render('ClientPortal/Receipts/Index', [
            'record' => PortalDocumentResource::collection(
                $this->documents->query(Receipt::class, $this->portalWarehouse())
                    ->with('warehouse', 'customer')
                    ->where('warehouse_id', auth()->guard('portal')->user()->id)
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('rec_no', 'like', "%{$search}%");
                    })
                    ->orderByDesc('id')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Emissão',
                    'value' => 'date',
                ],
                [
                    'name' => 'Referência',
                    'value' => 'rec_no',
                ],
                [
                    'name' => 'Cliente',
                    'value' => 'customer',
                ],
                [
                    'name' => 'Armazém',
                    'value' => 'warehouse',
                ],
                [
                    'name' => 'Total',
                    'value' => 'total',
                ],
            ],
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    public function collections()
    {
        $baseQuery = $this->portalCollectionBaseQuery();
        $summaryRecords = (clone $baseQuery)->get();

        return Inertia::render('ClientPortal/Collections/Index', [
            'record' => CollectionProductResource::collection(
                $baseQuery
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Código',
                    'value' => 'cl',
                ],
                [
                    'name' => 'Produto',
                    'value' => 'product',
                ],
                [
                    'name' => 'Colheita',
                    'value' => 'collection_date',
                ],
                [
                    'name' => 'Cliente',
                    'value' => 'customer',
                ],
                [
                    'name' => 'Armazém',
                    'value' => 'warehouse',
                ],
                [
                    'name' => 'Lote',
                    'value' => 'lot',
                ],
                [
                    'name' => 'BL',
                    'value' => 'bl',
                ],
                [
                    'name' => 'QTD',
                    'value' => 'qty',
                ],
                [
                    'name' => 'Marca Comercial',
                    'value' => 'comercial_brand',
                ],
                [
                    'name' => 'Acompanhamento',
                    'value' => 'tracking.label',
                ],
            ],
            'summary' => [
                'total' => $summaryRecords->count(),
                'recent' => $summaryRecords->filter(fn ($record) => optional($record->collection_date ?? $record->created_at)?->gte(now()->subDays(30)))->count(),
                'certificate_ready' => $summaryRecords->filter(fn ($record) => (bool) $record->quality_certificate)->count(),
                'analysis_pending' => $summaryRecords->filter(fn ($record) => ! $record->quality_certificate && (($record->collection?->collectionable?->placed_analysis ?? false) || $record->code?->pending_analysis?->count() > 0))->count(),
                'in_progress' => $summaryRecords->filter(fn ($record) => $record->code?->in_progress_analysis?->count() > 0)->count(),
            ],
            'query' => request()->only(['search', 'status_filter', 'type_filter', 'date_filter', 'page']),
        ]);
    }

    public function exportCollections(Request $request)
    {
        $validated = $request->validate([
            'recordIds' => ['nullable', 'array'],
            'recordIds.*' => ['integer'],
        ]);

        $query = $this->portalCollectionBaseQuery();

        if (! empty($validated['recordIds'])) {
            $query->whereIn('id', $validated['recordIds']);
        }

        $records = $query->get();

        abort_if($records->isEmpty(), 404, '');

        return SpreadsheetDownloadResponder::download(
            new CollectionParametersSheetExport($records),
            'portal-collections-parameters.xlsx'
        );
    }

    public function contractguides()
    {

        return Inertia::render('ClientPortal/ContractGuides/Index', [
            'record' => PortalDocumentResource::collection(
                $this->documents->query(ContractGuide::class, $this->portalWarehouse())
                    ->with('warehouse', 'customer')
                    ->where('warehouse_id', auth()->guard('portal')->user()->id)
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('guide_no', 'like', "%{$search}%");
                    })
                    ->orderByDesc('id')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Emissão',
                    'value' => 'date',
                ],
                [
                    'name' => 'Referência',
                    'value' => 'guide_no',
                ],
                [
                    'name' => 'Cliente',
                    'value' => 'customer',
                ],
                [
                    'name' => 'Armazém',
                    'value' => 'warehouse',
                ],
            ],
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    public function creditnotes()
    {

        return Inertia::render('ClientPortal/CreditNotes/Index', [
            'record' => PortalDocumentResource::collection(
                $this->documents->query(CreditNote::class, $this->portalWarehouse())
                    ->with(['warehouse', 'customer', 'invoice' => fn (BelongsTo $invoice): BelongsTo => $invoice->withTrashed()])
                    ->where('warehouse_id', auth()->guard('portal')->user()->id)
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('note_no', 'like', "%{$search}%");
                    })
                    ->orderByDesc('id')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Emissão',
                    'value' => 'date',
                ],
                [
                    'name' => 'Referência',
                    'value' => 'note_no',
                ],
                [
                    'name' => 'Cliente',
                    'value' => 'customer',
                ],
                [
                    'name' => 'Armazém',
                    'value' => 'warehouse',
                ],
                [
                    'name' => 'Total',
                    'value' => 'total',
                ],
            ],
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    public function quotes()
    {

        return Inertia::render('ClientPortal/Quotes/Index', [
            'record' => PortalDocumentResource::collection(
                $this->documents->query(Quote::class, $this->portalWarehouse())
                    ->with('warehouse', 'customer')
                    ->where('warehouse_id', auth()->guard('portal')->user()->id)
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('quote_no', 'like', "%{$search}%");
                    })
                    ->orderByDesc('id')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => 'Emissão',
                    'value' => 'date',
                ],
                [
                    'name' => 'Referência',
                    'value' => 'quote_no',
                ],
                [
                    'name' => 'Cliente',
                    'value' => 'customer',
                ],
                [
                    'name' => 'Armazém',
                    'value' => 'warehouse',
                ],
                [
                    'name' => 'Total',
                    'value' => 'total',
                ],
            ],
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    public function qualitycertificates()
    {

        return Inertia::render('ClientPortal/QualityCertificates/Index', [
            'record' => PortalDocumentResource::collection(
                $this->documents->releasedCertificates($this->portalWarehouse())
                    ->with('lab_code', 'product')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(fn (Builder $match): Builder => $match
                            ->where('code', 'like', "%{$search}%")
                            ->orWhereRelation('lab_code', 'code', 'like', "%{$search}%"));
                    })
                    ->orderByDesc('id')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => 'Código de Laboratório',
                    'value' => 'lab_code',
                ],
                [
                    'name' => 'Nº Documento',
                    'value' => 'code',
                ],
                [
                    'name' => 'Cliente',
                    'value' => 'customer',
                ],
                [
                    'name' => 'Armazém',
                    'value' => 'warehouse',
                ],
            ],
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ClientPortal/Invoices/Create', [
            'discount_categories' => collect(DiscountCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storerequest(StorePortalCustomerRequest $request, SubmitPortalServiceRequest $submit)
    {
        $validated = $request->validated();
        $validated['details'] = $this->normalizePortalRequestDetails($validated['request_type'], $validated['details'] ?? []);
        $validated['category_id'] = $this->resolvePortalCategoryId($validated['request_type'], $validated['category_id'] ?? null);
        $submit->execute((int) $this->portalWarehouse()->id, $validated);

        return back()->with('toast', ['title' => 'Notificação', 'message' => 'Pedido enviado com êxito.']);
    }

    private function normalizePortalRequestDetails(string $requestType, array $details): array
    {
        if ($requestType !== 'analysis_request') {
            return $details;
        }

        $samples = collect($details['samples'] ?? [])
            ->map(function ($sample, int $index) {
                return [
                    'batch_index' => $index,
                    'sample_name' => trim((string) ($sample['sample_name'] ?? '')),
                    'product_name' => trim((string) ($sample['product_name'] ?? '')),
                    'product_id' => isset($sample['product_id']) ? (int) $sample['product_id'] : null,
                    'matrix' => trim((string) ($sample['matrix'] ?? '')),
                    'matrix_id' => isset($sample['matrix_id']) ? (int) $sample['matrix_id'] : null,
                    'lot' => trim((string) ($sample['lot'] ?? '')),
                    'packaging' => trim((string) ($sample['packaging'] ?? '')),
                    'packaging_id' => isset($sample['packaging_id']) ? (int) $sample['packaging_id'] : null,
                    'quantity' => $sample['quantity'] ?? null,
                    'notes' => trim((string) ($sample['notes'] ?? '')),
                ];
            })
            ->filter(function (array $sample) {
                return filled($sample['sample_name'])
                    || filled($sample['product_name'])
                    || filled($sample['matrix'])
                    || filled($sample['lot']);
            })
            ->values();

        $details['samples'] = $samples->all();
        $details['sample_count'] = $samples->count();
        $details['requested_profiles'] = collect($details['requested_profiles'] ?? [])->filter()->map(fn ($id) => (int) $id)->values()->all();
        $details['product_id'] = isset($details['product_id']) ? (int) $details['product_id'] : null;
        $details['matrix_id'] = isset($details['matrix_id']) ? (int) $details['matrix_id'] : null;
        $details['packaging_id'] = isset($details['packaging_id']) ? (int) $details['packaging_id'] : null;

        return $details;
    }

    public function exportRequests(): StreamedResponse
    {
        $filename = 'portal-requests-'.now()->format('Y-m-d').'.csv';
        $requests = $this->portalRequestBaseQuery()->get();

        return response()->streamDownload(function () use ($requests): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Title', 'Type', 'Status', 'Priority', 'Preferred Date', 'Submitted At']);

            foreach ($requests as $request) {
                fputcsv($handle, [
                    $request->reference,
                    $request->title,
                    $request->request_type,
                    $request->portal_status,
                    $request->priority,
                    optional($request->preferred_date)->format('Y-m-d'),
                    optional($request->submitted_at ?? $request->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
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
    public function edit($id)
    {
        // Find the record
        $record = Invoice::with('items.itemable.code', 'customer', 'warehouse', 'user', 'invoice_category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ClientPortal/Invoices/Edit', [

            'record' => [
                'id' => $record->id,
                'date' => $record->date,
                'internal_ref' => $record->internal_ref,
                'inv_no' => $record->inv_no,
                'obs' => $record->obs,
                'status' => $record->status,
                'use_matrix_price' => $record->use_matrix_price,
                'is_original' => $record->is_original,
                'exported_saft' => $record->exported_saft,
                'type_id' => [
                    'value' => $record->type_id,
                    'label' => $record->invoice_category->code,
                ],
                'customer_id' => [
                    'value' => $record->customer_id,
                    'label' => $record->customer->name,
                ],
                'user_id' => [
                    'value' => $record->user_id,
                    'label' => $record->user->name,
                ],
                'warehouse_id' => [
                    'value' => $record?->warehouse?->id,
                    'label' => $record?->warehouse?->address,
                ],
                'items' => collect($record->items)->map(function ($item) {
                    return [
                        'id' => $item->id ?? null,
                        'invoice_id' => $item->invoice_id ?? null,
                        'unit_id' => [
                            'value' => $item->unit_id,
                            'label' => $item->unit->code,
                        ],
                        'exemption_id' => $item->exemption_id ?? null,
                        'exemption_code' => $item->exemption_code ?? null,
                        'discount_id' => $item->discount_id,
                        'item_id' => [
                            'value' => $item->item_id,
                            'label' => $item->item_description,
                            'price' => $item->unit_price + $item->discount_amount,
                            'tax_id' => $item->tax_id,
                            'charge_tax' => $item->charge_tax,
                            'tax_percentage' => $item->tax_percentage,
                            'exemption_id' => $item->exemption_id,
                            'exemption_code' => $item->exemption_code,
                        ],
                        'item_description' => $item->item_description,
                        'itemable_id' => [
                            'value' => $item->itemable_id ?? '',
                            'label' => $item->itemable?->code?->code ?? '',
                        ],
                        'itemable_type' => $item->itemable_type,
                        'qty' => $item->qty ?? 1,
                        'unit_price' => $item->unit_price,
                        'tax_id' => $item->tax_id,
                        'total' => $item->total,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_amount' => $item->discount_id == 1 ? $item->discount_percentage : $item->discount_amount,
                        'tax_percentage' => $item->tax_percentage,
                        'tax_amount' => $item->tax_amount,
                        'obs' => $item->obs,
                        'charge_tax' => $item->charge_tax,
                    ];
                }),
            ],
            'discount_categories' => collect(DiscountCategory::all())->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function markAsDone(int $id)
    {
        DB::transaction(function () use ($id): void {
            $recipient = $this->portalWarehouse();
            $record = CustomerRequest::query()->where('warehouse_id', $recipient->id)
                ->where('customer_id', $recipient->customer_id)->lockForUpdate()->findOrFail($id);
            if ($record->status !== 'completed') {
                abort_unless($record->update(['answered' => true, 'status' => 'completed', 'resolved_at' => now()]), 409);
            }
        }, 3);

        return back()->with('toast', ['title' => 'Notificação', 'message' => 'Registo actualizado com êxito']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyrequest(int $id)
    {
        DB::transaction(function () use ($id): void {
            $recipient = $this->portalWarehouse();
            $record = CustomerRequest::query()->where('warehouse_id', $recipient->id)
                ->where('customer_id', $recipient->customer_id)->lockForUpdate()->findOrFail($id);
            abort_unless($record->update(['status' => 'cancelled']), 409);
            abort_unless($record->delete(), 409);
        }, 3);

        return back()->with('toast', ['title' => 'Notificação', 'message' => 'Registo removido com êxito']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Invoice::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => '',
                'message' => 'Registo removido com êxito',
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore()
    {
        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Invoice::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => '',
                'message' => 'Registo restaurado com êxito',
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

    public function getInvoicePDF()
    {
        $model = $this->documents->query(Invoice::class, $this->portalWarehouse())
            ->with('items.exemption', 'items.unit', 'items.itemable', 'customer', 'warehouse', 'invoice_category', 'user')
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildInvoicePayload(
            $model,
            app(GeneralSettings::class)
        );

        $filename = $model->inv_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('invoice', $payload, $filename);

        if (request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('baixou o Factura Nº '.$model->inv_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, true);
        }  if (! request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('visualizou o Factura Nº '.$model->inv_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, false);
        }

    }

    public function getQuotePDF()
    {

        $model = $this->documents->query(Quote::class, $this->portalWarehouse())
            ->with('items', 'user', 'customer', 'warehouse')
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildQuotePayload(
            $model,
            app(GeneralSettings::class)
        );

        $filename = $model->quote_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('quote', $payload, $filename);

        if (request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('baixou o Proforma Nº '.$model->quote_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, true);
        }  if (! request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('visualizou o Proforma Nº '.$model->quote_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, false);
        }

    }

    public function getCreditNotePDF()
    {
        $model = $this->documents->query(CreditNote::class, $this->portalWarehouse())
            ->with(['items', 'user', 'customer', 'warehouse', 'invoice' => fn (BelongsTo $invoice): BelongsTo => $invoice->withTrashed()])
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildCreditNotePayload(
            $model,
            app(GeneralSettings::class)
        );

        $filename = $model->note_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('credit_note', $payload, $filename);

        if (request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('baixou o Nota de Crédito Nº '.$model->note_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, true);
        }  if (! request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('visualizou o Nota de Crédito Nº '.$model->note_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, false);
        }

    }

    public function getReceiptPDF()
    {
        $model = $this->documents->query(Receipt::class, $this->portalWarehouse())
            ->with(['user', 'customer', 'warehouse', 'items.invoice' => fn (BelongsTo $invoice): BelongsTo => $invoice->withTrashed()])
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->findOrFail(request()->integer('id'));
        $payload = app(ReportStudioPdfBuilder::class)->buildReceiptPayload(
            $model,
            app(GeneralSettings::class)
        );

        $filename = $model->rec_no.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('receipt', $payload, $filename);

        if (request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('baixou o Recibo Nº '.$model->rec_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, true);
        }  if (! request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('visualizou o Recibo Nº '.$model->rec_no);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, false);
        }

    }

    public function getContractGuidePDF()
    {

        $ntw = new NumberToWords;
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');

        $app_name = app(GeneralSettings::class)->app_name;
        $app_validation_number = app(GeneralSettings::class)->app_agt_validation_number;
        // $model = ContractGuide::with('items.exemption', 'items.unit', 'items.itemable', 'customer', 'warehouse', 'invoice_category', 'user')->find(request()->id);
        $model = $this->documents->query(ContractGuide::class, $this->portalWarehouse())
            ->with('items.product', 'items.country', 'warehouse', 'customer', 'user')
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->findOrFail(request()->integer('id'));
        // dd($model);

        $pdf = PDF::loadView('PDFs.contractguide', [
            'model' => $model,
            'settings' => app(GeneralSettings::class),
            'nTrans' => $nTrans,
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 10,
            'margin_footer' => 10,
            'title' => 'Guia de contratação Nº '.$model->guide_no,
            'author' => $model->user?->name,
            'watermark' => 'PAGO',
            'show_watermark' => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'showBarcodeNumbers' => false,
        ]);

        if (request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('baixou a Guia de contratação Nº '.$model->guide_no);

            return PdfResponse::download($pdf, $model->guide_no.'.pdf');
        }  if (! request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('visualizou a Guia de contratação Nº '.$model->guide_no);

            return PdfResponse::inline($pdf, $model->guide_no.'.pdf');
        }

    }

    public function getQualityCertificatePDF()
    {
        $model = $this->documents->releasedCertificates($this->portalWarehouse())
            ->with('collection', 'lab_code', 'user', 'customer', 'warehouse')
            ->where('warehouse_id', $this->portalWarehouse()->id)
            ->findOrFail(request()->integer('id'));
        $this->documentIntegrity->assertCertificateIntegrity($model);
        $payload = app(ReportStudioPdfBuilder::class)->buildAnalysisReportPayload($model, app(GeneralSettings::class));

        $filename = $model->code.'.pdf';
        $renderedPdf = app(ReportStudioPdfRenderer::class)->renderDocument('analysis', $payload, $filename);

        if (request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('baixou o Boletim Analítico Nº '.$model->code);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, true);
        }  if (! request()->q) {
            activity()
                ->by(auth()->guard('portal')->user())
                ->log('visualizou o Boletim Analítico Nº '.$model->code);

            return $this->reportStudioPdfResponse($renderedPdf, $filename, false);
        }

    }

    /**
     * @param  array{content: string, renderer: string}  $renderedPdf
     */
    private function reportStudioPdfResponse(array $renderedPdf, string $filename, bool $download)
    {
        return response($renderedPdf['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'X-Report-Studio-Renderer' => $renderedPdf['renderer'],
        ]);
    }
}
