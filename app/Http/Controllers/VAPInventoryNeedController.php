<?php

namespace App\Http\Controllers;

use App\Actions\ApproveInventoryNeed;
use App\Actions\ConvertInventoryNeedToOrder;
use App\Actions\RejectInventoryNeed;
use App\Http\Requests\ApproveInventoryNeedRequest;
use App\Http\Requests\ConvertInventoryNeedToOrderRequest;
use App\Http\Requests\InventoryNeedRequest;
use App\Http\Requests\RejectInventoryNeedRequest;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\InventorySupplierAssessment;
use App\Models\VAPLab;
use App\Services\SampleLaboratoryAccess;
use App\Support\InventoryNeedWorkflowNotifier;
use App\Support\PdfResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PDF;

class VAPInventoryNeedController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(Request $request)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $query = InventoryNeed::forLaboratory($labId)
            ->with([
                'department:id,name',
                'lab:id,name',
                'requestedBy:id,name',
                'approvedBy:id,name',
                'inventoryOrder:id,reference,status',
            ])
            ->withCount('items')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('reference', 'like', "%{$search}%")
                    ->orWhere('justification', 'like', "%{$search}%")
                    ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('lab', fn ($labQuery) => $labQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $needs = $query->paginate(12)->withQueryString()
            ->through(fn (InventoryNeed $need) => $this->transformNeedIndexRecord($need));
        $procurementQueue = InventoryNeed::forLaboratory($labId)
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
            ->limit(8)
            ->get()
            ->map(fn (InventoryNeed $need) => $this->transformProcurementQueueRecord($need));

        $readinessCounts = [
            'ready' => $procurementQueue->where('supplier_readiness', 'ready')->count(),
            'attention' => $procurementQueue->where('supplier_readiness', 'attention')->count(),
            'incomplete' => $procurementQueue->where('supplier_readiness', 'incomplete')->count(),
            'blocked' => $procurementQueue->where('supplier_readiness', 'blocked')->count(),
        ];

        return Inertia::render('VAPInventory/Needs/Index', [
            'needs' => $needs,
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'department_id']),
            'stats' => [
                'total' => InventoryNeed::forLaboratory($labId)->count(),
                'submitted' => InventoryNeed::forLaboratory($labId)->where('status', 'submitted')->count(),
                'approved' => InventoryNeed::forLaboratory($labId)->where('status', 'approved')->count(),
                'ordered' => InventoryNeed::forLaboratory($labId)->whereIn('status', ['ordered', 'partially_fulfilled', 'fulfilled'])->count(),
                'awaiting_order' => InventoryNeed::forLaboratory($labId)->where('status', 'approved')->whereNull('inventory_order_id')->count(),
                'overdue_procurement' => InventoryNeed::forLaboratory($labId)
                    ->where('status', 'approved')
                    ->whereNull('inventory_order_id')
                    ->whereDate('needed_by_date', '<', now()->toDateString())
                    ->count(),
                'by_status' => InventoryNeed::forLaboratory($labId)
                    ->toBase()
                    ->selectRaw('status, count(*) as aggregate')
                    ->groupBy('status')
                    ->pluck('aggregate', 'status')
                    ->map(fn ($count): int => (int) $count),
            ],
            'procurementQueue' => $procurementQueue,
            'charts' => [
                'status_overview' => [
                    'labels' => ['Submetidas', 'Aprovadas', 'Em aquisição', 'Aguardam pedido'],
                    'series' => [
                        InventoryNeed::forLaboratory($labId)->where('status', 'submitted')->count(),
                        InventoryNeed::forLaboratory($labId)->where('status', 'approved')->count(),
                        InventoryNeed::forLaboratory($labId)->whereIn('status', ['ordered', 'partially_fulfilled', 'fulfilled'])->count(),
                        InventoryNeed::forLaboratory($labId)->where('status', 'approved')->whereNull('inventory_order_id')->count(),
                    ],
                ],
                'queue_readiness' => [
                    'labels' => ['Prontas', 'Atenção', 'Incompletas', 'Bloqueadas'],
                    'series' => [
                        $readinessCounts['ready'],
                        $readinessCounts['attention'],
                        $readinessCounts['incomplete'],
                        $readinessCounts['blocked'],
                    ],
                ],
                'procurement_pressure' => [
                    'labels' => ['Fila procurement', 'Em atraso', 'Urgentes', 'Planeadas'],
                    'series' => [
                        $procurementQueue->count(),
                        $procurementQueue->filter(fn (array $need) => $this->urgencyLabel($need['needed_by_date'] ?? null) === 'Em atraso')->count(),
                        $procurementQueue->filter(fn (array $need) => $this->urgencyLabel($need['needed_by_date'] ?? null) === 'Urgente')->count(),
                        $procurementQueue->filter(fn (array $need) => $this->urgencyLabel($need['needed_by_date'] ?? null) === 'Planeado')->count(),
                    ],
                ],
            ],
        ]);
    }

    private function appendSupplierReadiness(InventoryNeed $need): InventoryNeed
    {
        $supplierIds = $need->items
            ->pluck('inventoryItem.supplier_id')
            ->filter()
            ->unique()
            ->values();

        $assessments = InventorySupplierAssessment::query()
            ->where('lab_id', $need->lab_id)
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

        $need->setAttribute('supplier_readiness', $readiness);
        $need->setAttribute('supplier_summary', [
            'missing_supplier_count' => $missingSupplierCount,
            'unassessed_supplier_count' => $unassessedSupplierCount,
            'blocked_supplier_count' => $blockedSupplierCount,
            'conditional_supplier_count' => $conditionalSupplierCount,
            'supplier_count' => $supplierIds->count(),
        ]);

        return $need;
    }

    private function transformNeedIndexRecord(InventoryNeed $need): array
    {
        return [
            'id' => $need->id,
            'reference' => $need->reference,
            'status' => $need->status,
            'justification' => $need->justification,
            'needed_by_date' => $need->needed_by_date?->toDateString(),
            'items_count' => $need->items_count,
            'department' => $need->department ? [
                'id' => $need->department->id,
                'name' => $need->department->name,
            ] : null,
            'lab' => $need->lab ? [
                'id' => $need->lab->id,
                'name' => $need->lab->name,
            ] : null,
            'requested_by' => $need->requestedBy ? [
                'id' => $need->requestedBy->id,
                'name' => $need->requestedBy->name,
            ] : null,
            'approved_by' => $need->approvedBy ? [
                'id' => $need->approvedBy->id,
                'name' => $need->approvedBy->name,
            ] : null,
            'inventory_order' => $need->inventoryOrder ? [
                'id' => $need->inventoryOrder->id,
                'reference' => $need->inventoryOrder->reference,
                'status' => $need->inventoryOrder->status,
            ] : null,
        ];
    }

    private function transformProcurementQueueRecord(InventoryNeed $need): array
    {
        $need = $this->appendSupplierReadiness($need);

        return [
            'id' => $need->id,
            'reference' => $need->reference,
            'justification' => $need->justification,
            'needed_by_date' => $need->needed_by_date?->toDateString(),
            'items_count' => $need->items_count,
            'department' => $need->department ? [
                'id' => $need->department->id,
                'name' => $need->department->name,
            ] : null,
            'lab' => $need->lab ? [
                'id' => $need->lab->id,
                'name' => $need->lab->name,
            ] : null,
            'requested_by' => $need->requestedBy ? [
                'id' => $need->requestedBy->id,
                'name' => $need->requestedBy->name,
            ] : null,
            'supplier_readiness' => $need->getAttribute('supplier_readiness'),
            'supplier_summary' => $need->getAttribute('supplier_summary'),
        ];
    }

    private function urgencyLabel(?string $neededByDate): string
    {
        if ($neededByDate === null) {
            return 'Sem prazo';
        }

        $diffDays = now()->startOfDay()->diffInDays(Carbon::parse($neededByDate)->startOfDay(), false);

        if ($diffDays < 0) {
            return 'Em atraso';
        }

        if ($diffDays <= 3) {
            return 'Urgente';
        }

        if ($diffDays <= 10) {
            return 'Próximo';
        }

        return 'Planeado';
    }

    public function create()
    {
        $labId = $this->laboratoryAccess->activeLabId();

        return Inertia::render('VAPInventory/Needs/Create', [
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'labs' => VAPLab::query()->whereKey($labId)->select('id', 'name', 'department_id')->get(),
            'items' => InventoryItem::forLaboratory($labId)->whereNotNull('unit_id')->with('unit:id,code,description')->select('id', 'name', 'code', 'unit_id')->orderBy('name')->get(),
            'warehouses' => InventoryItemWarehouse::query()->where('lab_id', $labId)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(InventoryNeedRequest $request, InventoryNeedWorkflowNotifier $notifier)
    {
        $need = DB::transaction(function () use ($request): InventoryNeed {
            $need = InventoryNeed::query()->create([
                'reference' => 'NEED-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'department_id' => $request->integer('department_id'),
                'lab_id' => $this->laboratoryAccess->activeLabId(),
                'requested_by_id' => auth()->id(),
                'status' => 'submitted',
                'needed_by_date' => $request->date('needed_by_date'),
                'justification' => $request->input('justification'),
                'submitted_at' => now(),
            ]);

            foreach ($request->validated('items') as $item) {
                $need->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'warehouse_id' => $item['warehouse_id'] ?? null,
                    'quantity_requested' => $item['quantity_requested'],
                    'estimated_unit_price' => $item['estimated_unit_price'] ?? null,
                    'status' => 'requested',
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $need;
        });

        $need->load(['requestedBy:id,name,email']);
        $notifier->submitted($need);

        return redirect()->route('vap-inventory.needs.show', $need)
            ->with('success', 'Necessidade registada e submetida para aprovação.');
    }

    public function show(InventoryNeed $need)
    {
        $this->ensureOwnedNeed($need);
        $need->load([
            'department:id,name',
            'lab:id,name',
            'requestedBy:id,name',
            'approvedBy:id,name',
            'inventoryOrder:id,reference,status',
            'items.inventoryItem:id,name,code,unit_id',
            'items.inventoryItem.unit:id,code,description',
            'items.warehouse:id,name',
        ]);

        $requestedLineCount = $need->items->count();
        $approvedLineCount = $need->items->filter(fn (InventoryNeedItem $item): bool => $item->quantity_approved !== null && $item->quantity_approved > 0)->count();
        $estimatedApprovedAmount = (float) $need->items->sum(
            fn (InventoryNeedItem $item) => ((float) ($item->estimated_unit_price ?? 0)) * ($item->quantity_approved ?: $item->quantity_requested)
        );
        $daysUntilNeedDate = $need->needed_by_date
            ? (int) now()->startOfDay()->diffInDays($need->needed_by_date->startOfDay(), false)
            : 0;

        return Inertia::render('VAPInventory/Needs/Show', [
            'need' => $need,
            'canApprove' => $need->status === 'submitted' && $need->inventory_order_id === null
                && auth()->user()->can('edit_iorders') && ! session()->has('impersonate'),
            'canConvertToOrder' => $need->status === 'approved' && $need->inventory_order_id === null
                && auth()->user()->can('add_iorders') && ! session()->has('impersonate'),
            'suppliers' => $this->supplierOptions(),
            'charts' => [
                'quantity_scope' => [
                    'labels' => ['Solicitadas', 'Aprovadas', 'Pendentes'],
                    'series' => [
                        $requestedLineCount,
                        $approvedLineCount,
                        $requestedLineCount - $approvedLineCount,
                    ],
                ],
                'item_value_mix' => [
                    'labels' => $need->items->map(fn (InventoryNeedItem $item) => $item->inventoryItem?->code ?: ($item->inventoryItem?->name ?? 'Item'))->values(),
                    'series' => $need->items->map(
                        fn (InventoryNeedItem $item) => ((float) ($item->estimated_unit_price ?? 0)) * ($item->quantity_approved ?: $item->quantity_requested)
                    )->values(),
                ],
                'governance_pulse' => [
                    'labels' => ['Itens', 'Dias até necessidade', 'Tem pedido', 'Valor estimado'],
                    'series' => [
                        (int) $need->items->count(),
                        max($daysUntilNeedDate, 0),
                        $need->inventory_order_id ? 1 : 0,
                        round($estimatedApprovedAmount, 2),
                    ],
                ],
            ],
        ]);
    }

    public function exportPdf(InventoryNeed $need)
    {
        $this->ensureOwnedNeed($need);
        $need->load([
            'department:id,name',
            'lab:id,name',
            'requestedBy:id,name,email',
            'approvedBy:id,name,email',
            'inventoryOrder:id,reference,status',
            'items.inventoryItem:id,name,code,unit_id',
            'items.inventoryItem.unit:id,code,description',
            'items.warehouse:id,name',
        ]);

        $filename = 'Necessidade_'.$need->reference.'_'.now()->format('Ymd_His').'.pdf';

        $pdf = PDF::loadView('exports.inventory-need', [
            'need' => $need,
            'companyName' => config('app.name', 'LIMS System'),
            'printedDate' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()->name ?? 'System',
            'statusLabel' => $this->statusLabel($need->status),
            'requestedLineCount' => $need->items->count(),
            'approvedLineCount' => $need->items->filter(fn (InventoryNeedItem $item): bool => $item->quantity_approved !== null && $item->quantity_approved > 0)->count(),
            'estimatedTotalAmount' => $need->items->sum(
                fn (InventoryNeedItem $item) => ((float) ($item->estimated_unit_price ?? 0)) * ($item->quantity_approved ?: $item->quantity_requested)
            ),
        ]);

        return PdfResponse::inline($pdf, $filename);
    }

    public function approve(ApproveInventoryNeedRequest $request, InventoryNeed $need, ApproveInventoryNeed $approve, InventoryNeedWorkflowNotifier $notifier): RedirectResponse
    {
        $need = $approve->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $need->id, $request->validated());
        $notifier->approved($need->load('requestedBy:id,name,email'));

        return back()->with('success', 'Necessidade aprovada e pronta para aquisição.');
    }

    public function reject(RejectInventoryNeedRequest $request, InventoryNeed $need, RejectInventoryNeed $reject, InventoryNeedWorkflowNotifier $notifier): RedirectResponse
    {
        $need = $reject->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $need->id, $request->validated('approval_notes'));
        $notifier->rejected($need->load('requestedBy:id,name,email'));

        return back()->with('success', 'Necessidade rejeitada com registo do motivo.');
    }

    public function convertToOrder(ConvertInventoryNeedToOrderRequest $request, InventoryNeed $need, ConvertInventoryNeedToOrder $convert, InventoryNeedWorkflowNotifier $notifier): RedirectResponse
    {
        $order = $convert->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $need->id, $request->validated());
        $notifier->convertedToOrder($need->refresh()->load(['requestedBy:id,name,email', 'approvedBy:id,name,email']), $order);
        $response = redirect()->route('vap-inventory.orders.show', $order)
            ->with('success', 'Necessidade convertida em pedido de compra.');
        if ($warning = $this->supplierAssessmentWarning($order->supplier)) {
            $response->with('warning', $warning);
        }

        return $response;
    }

    private function ensureOwnedNeed(InventoryNeed $need): void
    {
        abort_unless($need->lab_id === $this->laboratoryAccess->activeLabId(), 404);
    }

    private function supplierOptions()
    {
        $latestAssessments = InventorySupplierAssessment::query()
            ->where('inventory_supplier_assessments.lab_id', $this->laboratoryAccess->activeLabId())
            ->select('inventory_supplier_assessments.*')
            ->joinSub(
                InventorySupplierAssessment::query()
                    ->where('lab_id', $this->laboratoryAccess->activeLabId())
                    ->selectRaw('inventory_item_supplier_id, MAX(assessment_date) as latest_assessment_date')
                    ->groupBy('inventory_item_supplier_id'),
                'latest_assessments',
                function ($join) {
                    $join->on('inventory_supplier_assessments.inventory_item_supplier_id', '=', 'latest_assessments.inventory_item_supplier_id')
                        ->on('inventory_supplier_assessments.assessment_date', '=', 'latest_assessments.latest_assessment_date');
                }
            )
            ->get()
            ->keyBy('inventory_item_supplier_id');

        return InventoryItemSupplier::query()
            ->select('id', 'name', 'currency')
            ->orderBy('name')
            ->get()
            ->map(function (InventoryItemSupplier $supplier) use ($latestAssessments) {
                $assessment = $latestAssessments->get($supplier->id);

                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'currency' => $supplier->currency,
                    'latest_assessment' => $assessment ? [
                        'status' => $assessment->status,
                        'risk_level' => $assessment->risk_level,
                        'next_review_at' => $assessment->next_review_at?->toDateString(),
                        'approved_supplier' => (bool) $assessment->approved_supplier,
                        'total_score' => $assessment->total_score,
                    ] : null,
                ];
            });
    }

    private function supplierAssessmentWarning(?InventoryItemSupplier $supplier): ?string
    {
        if ($supplier === null) {
            return null;
        }

        $assessment = InventorySupplierAssessment::query()
            ->where('lab_id', $this->laboratoryAccess->activeLabId())
            ->where('inventory_item_supplier_id', $supplier->id)
            ->latest('assessment_date')
            ->first();

        if ($assessment === null) {
            return 'Este fornecedor ainda não tem avaliação registada. Recomenda-se revisão antes do seguimento da compra.';
        }

        if ($assessment->next_review_at !== null && $assessment->next_review_at->isPast()) {
            return 'A avaliação deste fornecedor está vencida. Recomenda-se revisão imediata.';
        }

        if ($assessment->status === 'conditional') {
            return 'Este fornecedor está condicionado. Acompanhe as acções de seguimento antes de concluir a compra.';
        }

        if ($assessment->risk_level === 'high') {
            return 'Este fornecedor está classificado com risco alto. Reforce o acompanhamento desta compra.';
        }

        return null;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Rascunho',
            'submitted' => 'Submetida',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
            'ordered' => 'Convertida em pedido',
            'partially_fulfilled' => 'Parcialmente satisfeita',
            'fulfilled' => 'Satisfeita',
            default => $status,
        };
    }
}
