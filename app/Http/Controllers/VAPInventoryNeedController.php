<?php

namespace App\Http\Controllers;

use App\Enums\Orders\InventoryOrderItemStatus;
use App\Enums\Orders\InventoryOrderTrackingStatus;
use App\Http\Requests\InventoryNeedRequest;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryNeed;
use App\Models\InventoryNeedItem;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderDetail;
use App\Models\InventorySupplierAssessment;
use App\Models\VAPLab;
use App\Support\InventoryNeedWorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;
use PDF;

class VAPInventoryNeedController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryNeed::query()
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
                'total' => InventoryNeed::query()->count(),
                'submitted' => InventoryNeed::query()->where('status', 'submitted')->count(),
                'approved' => InventoryNeed::query()->where('status', 'approved')->count(),
                'ordered' => InventoryNeed::query()->whereIn('status', ['ordered', 'partially_fulfilled', 'fulfilled'])->count(),
                'awaiting_order' => InventoryNeed::query()->where('status', 'approved')->whereNull('inventory_order_id')->count(),
                'overdue_procurement' => InventoryNeed::query()
                    ->where('status', 'approved')
                    ->whereNull('inventory_order_id')
                    ->whereDate('needed_by_date', '<', now()->toDateString())
                    ->count(),
            ],
            'procurementQueue' => $procurementQueue,
            'charts' => [
                'status_overview' => [
                    'labels' => ['Submetidas', 'Aprovadas', 'Em aquisição', 'Aguardam pedido'],
                    'series' => [
                        InventoryNeed::query()->where('status', 'submitted')->count(),
                        InventoryNeed::query()->where('status', 'approved')->count(),
                        InventoryNeed::query()->whereIn('status', ['ordered', 'partially_fulfilled', 'fulfilled'])->count(),
                        InventoryNeed::query()->where('status', 'approved')->whereNull('inventory_order_id')->count(),
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

        $diffDays = now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($neededByDate)->startOfDay(), false);

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
        return Inertia::render('VAPInventory/Needs/Create', [
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'labs' => VAPLab::query()->select('id', 'name', 'department_id')->orderBy('name')->get(),
            'items' => InventoryItem::query()->select('id', 'name', 'code')->orderBy('name')->get(),
            'warehouses' => InventoryItemWarehouse::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(InventoryNeedRequest $request, InventoryNeedWorkflowNotifier $notifier)
    {
        $need = DB::transaction(function () use ($request): InventoryNeed {
            $need = InventoryNeed::query()->create([
                'reference' => 'NEED-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'department_id' => $request->integer('department_id'),
                'lab_id' => $request->integer('lab_id') ?: null,
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
        $need->load([
            'department:id,name',
            'lab:id,name',
            'requestedBy:id,name',
            'approvedBy:id,name',
            'inventoryOrder:id,reference,status',
            'items.inventoryItem:id,name,code',
            'items.warehouse:id,name',
        ]);

        $totalRequestedQuantity = (int) $need->items->sum('quantity_requested');
        $totalApprovedQuantity = (int) $need->items->sum(fn (InventoryNeedItem $item) => $item->quantity_approved ?: 0);
        $estimatedApprovedAmount = (float) $need->items->sum(
            fn (InventoryNeedItem $item) => ((float) ($item->estimated_unit_price ?? 0)) * ($item->quantity_approved ?: $item->quantity_requested)
        );
        $daysUntilNeedDate = $need->needed_by_date
            ? (int) now()->startOfDay()->diffInDays($need->needed_by_date->startOfDay(), false)
            : 0;

        return Inertia::render('VAPInventory/Needs/Show', [
            'need' => $need,
            'canApprove' => in_array($need->status, ['submitted'], true),
            'canConvertToOrder' => $need->status === 'approved' && $need->inventory_order_id === null,
            'suppliers' => $this->supplierOptions(),
            'charts' => [
                'quantity_scope' => [
                    'labels' => ['Solicitado', 'Aprovado', 'Pendente'],
                    'series' => [
                        $totalRequestedQuantity,
                        $totalApprovedQuantity,
                        max($totalRequestedQuantity - $totalApprovedQuantity, 0),
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
        $need->load([
            'department:id,name',
            'lab:id,name',
            'requestedBy:id,name,email',
            'approvedBy:id,name,email',
            'inventoryOrder:id,reference,status',
            'items.inventoryItem:id,name,code',
            'items.warehouse:id,name',
        ]);

        $filename = 'Necessidade_' . $need->reference . '_' . now()->format('Ymd_His') . '.pdf';

        return PDF::loadView('exports.inventory-need', [
            'need' => $need,
            'companyName' => config('app.name', 'LIMS System'),
            'printedDate' => now()->format('d/m/Y H:i'),
            'printedBy' => auth()->user()->name ?? 'System',
            'statusLabel' => $this->statusLabel($need->status),
            'totalRequestedQuantity' => $need->items->sum('quantity_requested'),
            'totalApprovedQuantity' => $need->items->sum(fn (InventoryNeedItem $item) => $item->quantity_approved ?: 0),
            'estimatedTotalAmount' => $need->items->sum(
                fn (InventoryNeedItem $item) => ((float) ($item->estimated_unit_price ?? 0)) * ($item->quantity_approved ?: $item->quantity_requested)
            ),
        ])->stream($filename);
    }

    public function approve(Request $request, InventoryNeed $need, InventoryNeedWorkflowNotifier $notifier)
    {
        abort_if(! auth()->user()->hasRole('admin') && ! auth()->user()->can('edit_iorders'), 403);

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:inventory_need_items,id'],
            'items.*.quantity_approved' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($need, $validated): void {
            foreach ($validated['items'] as $itemPayload) {
                /** @var InventoryNeedItem $item */
                $item = $need->items()->findOrFail($itemPayload['id']);
                $item->update([
                    'quantity_approved' => min($itemPayload['quantity_approved'], $item->quantity_requested),
                    'status' => 'approved',
                ]);
            }

            $need->update([
                'status' => 'approved',
                'approval_notes' => $validated['approval_notes'] ?? null,
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
                'rejected_at' => null,
            ]);
        });

        $need->refresh();
        $need->load(['requestedBy:id,name,email']);
        $notifier->approved($need);

        return back()->with('success', 'Necessidade aprovada e pronta para aquisição.');
    }

    public function reject(Request $request, InventoryNeed $need, InventoryNeedWorkflowNotifier $notifier)
    {
        abort_if(! auth()->user()->hasRole('admin') && ! auth()->user()->can('edit_iorders'), 403);

        $validated = $request->validate([
            'approval_notes' => ['required', 'string', 'max:5000'],
        ]);

        $need->update([
            'status' => 'rejected',
            'approval_notes' => $validated['approval_notes'],
            'approved_by_id' => auth()->id(),
            'rejected_at' => now(),
        ]);

        $need->refresh();
        $need->load(['requestedBy:id,name,email']);
        $notifier->rejected($need);

        return back()->with('success', 'Necessidade rejeitada com registo do motivo.');
    }

    public function convertToOrder(Request $request, InventoryNeed $need, InventoryNeedWorkflowNotifier $notifier)
    {
        abort_if(! auth()->user()->hasRole('admin') && ! auth()->user()->can('add_iorders'), 403);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:i_suppliers,id'],
            'date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'obs' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if($need->status !== 'approved', 422, 'Only approved needs can be converted into an order.');
        abort_if($need->inventory_order_id !== null, 422, 'This need has already been converted into an order.');

        $supplier = InventoryItemSupplier::query()->findOrFail($validated['supplier_id']);
        $supplierAssessmentBlocker = $this->supplierAssessmentBlocker($supplier);

        if ($supplierAssessmentBlocker !== null) {
            return redirect()->back()
                ->withInput()
                ->with('error', $supplierAssessmentBlocker);
        }

        $order = DB::transaction(function () use ($need, $validated, $supplier): InventoryOrder {
            $order = InventoryOrder::query()->create([
                'date' => $validated['date'],
                'user_id' => auth()->id(),
                'supplier_id' => $supplier->id,
                'order_year' => now()->format('Y'),
                'reference' => $validated['reference'] ?? null,
                'obs' => trim(($validated['obs'] ?? '') . "\nOrigem: necessidade {$need->reference}"),
                'status' => InventoryOrderTrackingStatus::PENDING,
                'currency' => $supplier->currency ?? 'USD',
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            foreach ($need->items as $needItem) {
                $quantity = $needItem->quantity_approved ?: $needItem->quantity_requested;
                $unitPrice = (float) ($needItem->estimated_unit_price ?? 0);

                InventoryOrderDetail::query()->create([
                    'order_id' => $order->id,
                    'item_id' => $needItem->inventory_item_id,
                    'qty' => $quantity,
                    'received_qty' => 0,
                    'unit_price' => $unitPrice,
                    'warehouse_id' => $needItem->warehouse_id,
                    'expected_date' => $validated['expected_date'] ?? $need->needed_by_date,
                    'status' => InventoryOrderItemStatus::PENDING,
                    'currency' => $supplier->currency ?? 'USD',
                ]);

                $needItem->update(['status' => 'ordered']);
                $totalAmount += $quantity * $unitPrice;
            }

            $order->update(['total_amount' => $totalAmount]);

            $need->update([
                'status' => 'ordered',
                'inventory_order_id' => $order->id,
            ]);

            return $order;
        });

        $response = redirect()->route('vap-inventory.orders.show', $order)
            ->with('success', 'Necessidade convertida em pedido de compra.');

        $need->refresh();
        $need->load(['requestedBy:id,name,email', 'approvedBy:id,name,email']);
        $notifier->convertedToOrder($need, $order);

        if ($warning = $this->supplierAssessmentWarning($supplier)) {
            $response->with('warning', $warning);
        }

        return $response;
    }

    private function supplierOptions()
    {
        $latestAssessments = InventorySupplierAssessment::query()
            ->select('inventory_supplier_assessments.*')
            ->joinSub(
                InventorySupplierAssessment::query()
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

    private function supplierAssessmentBlocker(?InventoryItemSupplier $supplier): ?string
    {
        if ($supplier === null) {
            return 'O fornecedor seleccionado não está disponível.';
        }

        $assessment = InventorySupplierAssessment::query()
            ->where('inventory_item_supplier_id', $supplier->id)
            ->latest('assessment_date')
            ->first();

        if ($assessment === null) {
            return null;
        }

        if (in_array($assessment->status, ['rejected', 'suspended'], true)) {
            return 'Este fornecedor está bloqueado pela avaliação mais recente e não pode ser usado nesta aquisição.';
        }

        if ($assessment->risk_level === 'critical' && ! $assessment->approved_supplier) {
            return 'Este fornecedor apresenta risco crítico sem aprovação activa e não pode ser usado nesta aquisição.';
        }

        return null;
    }

    private function supplierAssessmentWarning(?InventoryItemSupplier $supplier): ?string
    {
        if ($supplier === null) {
            return null;
        }

        $assessment = InventorySupplierAssessment::query()
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
