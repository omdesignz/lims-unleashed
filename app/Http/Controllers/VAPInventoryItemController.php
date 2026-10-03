<?php

namespace App\Http\Controllers;

use App\Actions\AdjustInventoryItemStock;
use App\Actions\ConsumeInventoryReagent;
use App\Actions\CreateInventoryItem;
use App\Actions\ReverseInventoryReagentConsumption;
use App\Actions\SetInventoryDocumentArchived;
use App\Actions\SetInventoryItemsArchived;
use App\Actions\UpdateInventoryItem;
use App\Enums\InventoryCategoryType;
use App\Exports\InventoryItemsExport;
use App\Http\Requests\AdjustInventoryItemStockRequest;
use App\Http\Requests\ConsumeInventoryReagentRequest;
use App\Http\Requests\CreateInventoryItemRequest;
use App\Http\Requests\InventoryCatalogueExportRequest;
use App\Http\Requests\InventoryCatalogueLookupRequest;
use App\Http\Requests\InventoryCatalogueReportRequest;
use App\Http\Requests\InventoryOperationalReportRequest;
use App\Http\Requests\SetInventoryItemsArchivedRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryCatalogueItemResource;
use App\Http\Resources\InventoryCatalogueOptionResource;
use App\Http\Resources\InventoryLowStockResource;
use App\Http\Resources\InventoryReagentChoiceResource;
use App\Http\Resources\InventoryReagentConsumptionResource;
use App\Models\Department;
use App\Models\EquipmentCategory;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemDocumentMedia;
use App\Models\InventoryItemSupplier;
use App\Models\InventoryItemType;
use App\Models\InventoryItemWarehouse;
use App\Models\InventoryUnit;
use App\Models\ItemStatus;
use App\Models\PackagingCategory;
use App\Models\ReagentConsumption;
use App\Models\User;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryCatalogueLookup;
use App\Services\InventoryCatalogueRead;
use App\Services\SampleLaboratoryAccess;
use App\Support\DuplicateSubmissionGuard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\MediaStream;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class VAPInventoryItemController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly InventoryCatalogueAccess $catalogueAccess,
        private readonly InventoryCatalogueRead $catalogueRead,
    ) {}

    /** @return Builder<InventoryItem> */
    private function readableItems(int $labId): Builder
    {
        return $this->catalogueRead->items($labId, request()->user());
    }

    /** @return Builder<Inventory> */
    private function readableStock(int $labId): Builder
    {
        return $this->catalogueRead->stock($labId, request()->user());
    }

    public function lookup(InventoryCatalogueLookupRequest $request, InventoryCatalogueLookup $lookup): JsonResponse
    {
        $data = $request->validated();
        $type = isset($data['inventory_type']) ? InventoryCategoryType::from($data['inventory_type']) : null;
        $items = $lookup->search($this->laboratoryAccess->activeLabId(), $request->user(), $data['q'] ?? '', $type);

        return response()->json(InventoryCatalogueOptionResource::collection($items)->resolve($request));
    }

    public function index(Request $request): InertiaResponse
    {
        $request->validate(['inventory_type' => ['nullable', Rule::enum(InventoryCategoryType::class)],
            'archive_state' => ['nullable', Rule::in(['active', 'archived'])]]);
        $selectedType = $request->filled('inventory_type') ? $this->catalogueAccess->type($request->input('inventory_type')) : null;
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless($this->catalogueAccess->any($request->user(), 'view'), 403);
        $query = $this->readableItems($labId)->with(['category' => fn ($category) => $category->withTrashed(), 'unit', 'type', 'supplier', 'status'])
            ->when($request->input('archive_state') === 'archived', fn (Builder $query): Builder => $query->onlyTrashed())
            ->when($request->input('inventory_type'), fn (Builder $query, string $type): Builder => $query
                ->whereHas('category', fn (Builder $category): Builder => $category->withTrashed()->where('inventory_type', $type)))
            ->withSum(['inventory' => fn ($query) => $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('lab_id', $labId))], 'qty_available')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('internal_code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->type_id, function ($query, $typeId) {
                $query->where('type_id', $typeId);
            })
            ->when($request->status_id, function ($query, $statusId) {
                $query->where('status_id', $statusId);
            })
            ->when($request->supplier_id, function ($query, $supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc');

        return Inertia::render('VAPInventory/Items/Index', [
            'items' => $query->paginate($request->per_page ?? 20)->withQueryString()
                ->through(fn (InventoryItem $item): array => (new InventoryCatalogueItemResource($item))->resolve($request)),
            'canCreate' => ! $request->session()->has('impersonate') && ($selectedType
                ? $request->user()->can($this->catalogueAccess->permission('add', $selectedType)) : $this->catalogueAccess->any($request->user(), 'add')),
            'canExport' => $selectedType ? $request->user()->can($this->catalogueAccess->permission('export', $selectedType)) : $this->catalogueAccess->any($request->user(), 'export'),
            'filters' => $request->only(['search', 'inventory_type', 'archive_state', 'category_id', 'type_id', 'status_id', 'supplier_id', 'sort_by', 'sort_direction']),
            'categories' => $this->catalogueAccess->categories($request->user(), 'view')->get(),
            'types' => InventoryItemType::active()->get(),
            'statuses' => ItemStatus::active()->get(),
            'suppliers' => InventoryItemSupplier::active()->get(),
            'units' => InventoryUnit::active()->get(),
            'stats' => [
                'total_items' => $this->readableItems($labId)->count(),
                'equipment_count' => $this->readableItems($labId)->equipment()->count(),
                'reagents_count' => $this->readableItems($labId)->reagents()->count(),
                'consumables_count' => $this->readableItems($labId)->consumables()->count(),
                'items_needing_calibration' => $this->readableItems($labId)->whereNotNull('next_calibration_date')
                    ->where('next_calibration_date', '<', now()->addDays(30))
                    ->count(),
                'items_on_metrology_hold' => $this->readableItems($labId)
                    ->get()
                    ->where('metrology_status', 'hold')
                    ->count(),
                'expired_reagents' => $this->readableItems($labId)->reagents()
                    ->whereNotNull('reagent_expiry_date')
                    ->where('reagent_expiry_date', '<', now())
                    ->count(),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        abort_unless(! request()->session()->has('impersonate') && $this->catalogueAccess->any(request()->user(), 'add'), 403);
        request()->validate(['inventory_type' => ['nullable', Rule::enum(InventoryCategoryType::class)]]);
        if (request()->filled('inventory_type')) {
            abort_unless(request()->user()->can($this->catalogueAccess->permission('add',
                $this->catalogueAccess->type(request()->input('inventory_type')))), 403);
        }
        $labId = $this->laboratoryAccess->activeLabId();

        return Inertia::render('VAPInventory/Items/Create', [
            'categories' => $this->catalogueAccess->categories(request()->user(), 'add')
                ->when(request()->input('inventory_type'), fn (Builder $query, string $type): Builder => $query->where('inventory_type', $type))->get(),
            'types' => InventoryItemType::active()->get(),
            'statuses' => ItemStatus::active()->get(),
            'allStatuses' => ItemStatus::active()->get(),
            'suppliers' => InventoryItemSupplier::active()->get(),
            'units' => InventoryUnit::active()->get(),
            'warehouses' => InventoryItemWarehouse::with('location')->where('lab_id', $labId)->active()->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'equipmentCategories' => EquipmentCategory::query()->orderBy('name')->get(),
            'packagingCategories' => PackagingCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CreateInventoryItemRequest $request, CreateInventoryItem $createItem): RedirectResponse
    {
        $item = $createItem->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated());

        return redirect()->route($request->user()->can($this->catalogueAccess->permission('view', $this->catalogueAccess->itemType($item)))
            ? 'vap-inventory.items.index' : 'vap-inventory.items.create')
            ->with('success', 'Item de inventário criado com sucesso.');
    }

    public function show(InventoryItem $item): InertiaResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless((int) $item->lab_id === $labId, 404);
        $this->catalogueAccess->authorize(request()->user(), $item, 'view');
        $item->load([
            'category' => fn (BelongsTo $category): BelongsTo => $category->withTrashed(),
            'unit',
            'type',
            'supplier',
            'status',
            'inventory' => fn ($query) => $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('lab_id', $labId)),
            'inventory.warehouse.location',
            'transactions' => function ($query) use ($labId) {
                $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('lab_id', $labId))->latest()->limit(50);
            },
            'transactions.type',
            'transactions.user',
            'transactions.warehouse',
            'orders' => function ($query) use ($labId) {
                $query->whereIn('i_order_details.warehouse_id', InventoryItemWarehouse::query()->where('lab_id', $labId)->select('id'))
                    ->latest('i_orders.created_at')->limit(20);
            },
            'orders.supplier',
            'transfers' => function ($query) use ($labId) {
                $query->where('lab_id', $labId)->latest()->limit(20);
            },
            'transfers.source',
            'transfers.destination',
            'reagentConsumptions' => function ($query) use ($labId) {
                $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('lab_id', $labId))->with('reversal')->latest()->limit(20);
            },
        ]);

        $warehouseStockSeries = $item->inventory
            ->map(fn ($inventory) => (float) $inventory->qty_available)
            ->values();

        $warehouseStockLabels = $item->inventory
            ->map(fn ($inventory) => $inventory->warehouse?->name ?? 'Armazém')
            ->values();

        $lowStockWarehouses = $item->inventory->filter(function ($inventory) {
            return (float) $inventory->qty_available <= (float) ($inventory->reorder_point ?? 0);
        })->count();

        return Inertia::render('VAPInventory/Items/Show', [
            'item' => $item,
            'canEdit' => ! request()->session()->has('impersonate') && request()->user()->can(
                $this->catalogueAccess->permission('edit', $this->catalogueAccess->itemType($item))),
            'inventory' => $item->inventory,
            'recentTransactions' => $item->transactions,
            'recentOrders' => $item->orders,
            'recentTransfers' => $item->transfers,
            'recentConsumptions' => $item->reagentConsumptions,
            'totalStock' => $item->inventory->sum('qty_available'),
            'isReagent' => $item->is_reagent,
            'isExpired' => $item->is_expired,
            'daysToExpiry' => $item->days_to_expiry,
            'needsCalibration' => $item->needs_calibration,
            'calibrationStatus' => $item->calibration_status,
            'metrologyStatus' => $item->metrology_status,
            'isMetrologicallyReady' => $item->is_metrologically_ready,
            'documents' => $item->getInventoryItemDocuments(true)->map(function ($file) {
                return [
                    'id' => $file->id,
                    'archived' => $file->trashed(),
                    'uuid' => $file->uuid,
                    'name' => $file->name,
                    'file_name' => $file->file_name,
                    'original_url' => $file->getUrl(), // Optional: Add a thumbnail if applicable
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'extension' => $file->extension,
                    'order' => $file->order,
                ];
            }),
            'charts' => [
                'stock_distribution' => [
                    'labels' => $warehouseStockLabels,
                    'series' => $warehouseStockSeries,
                ],
                'activity_mix' => [
                    'labels' => ['Transacções', 'Pedidos', 'Transferências', 'Consumos'],
                    'series' => [
                        $item->transactions->count(),
                        $item->orders->count(),
                        $item->transfers->count(),
                        $item->reagentConsumptions->count(),
                    ],
                ],
                'compliance_pulse' => [
                    'labels' => ['Existências totais', 'Armazéns críticos', 'Dias até caducar', 'Prontidão metrológica'],
                    'series' => [
                        (float) $item->inventory->sum('qty_available'),
                        $lowStockWarehouses,
                        max((int) ($item->days_to_expiry ?? 0), 0),
                        $item->is_metrologically_ready ? 1 : 0,
                    ],
                ],
            ],
        ]);
    }

    public function edit(InventoryItem $item): InertiaResponse
    {
        abort_unless(! request()->session()->has('impersonate'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless((int) $item->lab_id === $labId, 404);
        $this->catalogueAccess->authorize(request()->user(), $item, 'edit');
        $item->load([
            'inventory' => fn ($query) => $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('lab_id', $labId)),
            'inventory.warehouse',
        ]);

        return Inertia::render('VAPInventory/Items/Edit', [
            'item' => $item,
            'categories' => $this->catalogueAccess->categories(request()->user(), 'edit')->withTrashed()
                ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->category_id))->get(),
            'types' => InventoryItemType::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->type_id))->get(),
            'statuses' => ItemStatus::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->status_id))->get(),
            'allStatuses' => ItemStatus::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->status_id))->get(),
            'suppliers' => InventoryItemSupplier::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->supplier_id))->get(),
            'units' => InventoryUnit::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->unit_id))->get(),
            'departments' => Department::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->department_id))->orderBy('name')->get(),
            'equipmentCategories' => EquipmentCategory::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->eq_cat_id))->orderBy('name')->get(),
            'packagingCategories' => PackagingCategory::withTrashed()->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $item->packaging_type_id))->orderBy('name')->get(),
            'identityLocks' => ['category' => true, 'unit' => $item->unit_id !== null && $item->inventory()->withTrashed()->exists()],
            'warehouses' => InventoryItemWarehouse::with('location')->where('lab_id', $labId)->active()->get(),
            'documents' => $item->getInventoryItemDocuments()->map(function ($file) {
                return [
                    'id' => $file->id,
                    'uuid' => $file->uuid,
                    'name' => $file->name,
                    'file_name' => $file->file_name,
                    'original_url' => $file->getUrl(), // Optional: Add a thumbnail if applicable
                    'size' => $file->size,
                    'mime_type' => $file->mime_type,
                    'extension' => $file->extension,
                    'order' => $file->order,
                ];
            }),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $item, UpdateInventoryItem $updateItem): RedirectResponse
    {
        $updateItem->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $item->id, $request->validated());

        return redirect()->route($request->user()->can($this->catalogueAccess->permission('view', $this->catalogueAccess->itemType($item)))
            ? 'vap-inventory.items.show' : 'vap-inventory.items.edit', $item)
            ->with('success', 'Item de inventário actualizado com sucesso.');
    }

    public function destroy(SetInventoryItemsArchivedRequest $request, InventoryItem $item, SetInventoryItemsArchived $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('recordIds'), true);

        return $this->catalogueReturn($request)->with('success', 'Item de inventário arquivado com sucesso.');
    }

    public function restore(SetInventoryItemsArchivedRequest $request, InventoryItem $item, SetInventoryItemsArchived $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('recordIds'), false);

        return $this->catalogueReturn($request)->with('success', 'Item de inventário restaurado com sucesso.');
    }

    private function catalogueReturn(SetInventoryItemsArchivedRequest $request): RedirectResponse
    {
        $index = route('vap-inventory.items.index');
        $referer = (string) $request->headers->get('referer', '');
        $isCatalogue = $referer === $index || str_starts_with($referer, $index.'?');

        return redirect()->to($isCatalogue && ! preg_match('/[\x00-\x20\x7f]/', $referer) ? $referer : $index);
    }

    public function adjustStock(AdjustInventoryItemStockRequest $request, InventoryItem $item, DuplicateSubmissionGuard $duplicateSubmissionGuard, AdjustInventoryItemStock $adjustStock)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'vap-inventory-adjust-stock', array_merge($validated, [
            'item_id' => $item->id,
        ]), 30)) {
            return response()->json([
                'error' => 'Um pedido idêntico de ajuste de existências já está a ser processado.',
            ], 429);
        }

        try {
            $result = $adjustStock->execute($labId, $request->user(), $item, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Existências ajustadas com êxito',
                ...$result,
            ]);
        } catch (ValidationException $exception) {
            return response()->json(['errors' => $exception->errors()], 422);
        }
    }

    public function calibrationSchedule(InventoryCatalogueReportRequest $request): InertiaResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $today = now()->startOfDay();
        $todayDate = $today->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in31Days = now()->addDays(31)->toDateString();
        $in90Days = now()->addDays(90)->toDateString();
        $status = in_array($request->input('status'), ['overdue', 'due_soon', 'upcoming'], true)
            ? $request->input('status')
            : '';
        $sortBy = in_array($request->input('sort_by'), ['next_calibration_date', 'last_calibration_date', 'name'], true)
            ? $request->input('sort_by')
            : 'next_calibration_date';
        $sortDirection = $request->input('sort_direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->input('search', ''));

        $query = $this->readableItems($labId)->with([
            'category' => fn ($category) => $category->withTrashed()->select('id', 'name'),
            'type:id,name',
        ])
            ->whereNotNull('next_calibration_date')
            ->when($status, function ($query, $status) use ($todayDate, $in30Days) {
                if ($status === 'overdue') {
                    $query->whereDate('next_calibration_date', '<', $todayDate);
                } elseif ($status === 'due_soon') {
                    $query->whereBetween('next_calibration_date', [$todayDate, $in30Days]);
                } elseif ($status === 'upcoming') {
                    $query->whereDate('next_calibration_date', '>', $in30Days);
                }
            })
            ->when($request->input('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->input('type_id'), fn ($query, $typeId) => $query->where('type_id', $typeId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('internal_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('name');

        $items = $query
            ->paginate($request->validated('per_page') ?? 20)
            ->withQueryString()
            ->through(fn (InventoryItem $item) => $this->formatCalibrationScheduleRow($item, $today));

        $stats = $this->readableItems($labId)
            ->whereNotNull('next_calibration_date')
            ->selectRaw('count(*) as total_scheduled')
            ->selectRaw('count(case when next_calibration_date < ? then 1 end) as total_due', [$todayDate])
            ->selectRaw('count(case when next_calibration_date between ? and ? then 1 end) as due_soon', [$todayDate, $in30Days])
            ->selectRaw('count(case when next_calibration_date between ? and ? then 1 end) as due_31_90', [$in31Days, $in90Days])
            ->first();

        return Inertia::render('VAPInventory/Calibration/Schedule', [
            'items' => $items,
            'filters' => [
                'status' => $status,
                'type_id' => $request->input('type_id', ''),
                'category_id' => $request->input('category_id', ''),
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'categories' => $this->catalogueAccess->categories($request->user(), 'view')->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => InventoryItemType::active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'total_due' => (int) ($stats->total_due ?? 0),
                'due_soon' => (int) ($stats->due_soon ?? 0),
                'due_31_90' => (int) ($stats->due_31_90 ?? 0),
                'total_scheduled' => (int) ($stats->total_scheduled ?? 0),
            ],
        ]);
    }

    private function formatCalibrationScheduleRow(InventoryItem $item, Carbon $today): array
    {
        $nextCalibrationDate = $item->next_calibration_date?->copy()->startOfDay();
        $daysToCalibration = $nextCalibrationDate
            ? (int) $today->diffInDays($nextCalibrationDate, false)
            : null;

        return [
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'internal_code' => $item->internal_code,
            'serial_number' => $item->serial_number,
            'brand' => $item->brand,
            'model' => $item->model,
            'location' => $item->location,
            'software' => $item->software,
            'firmware' => $item->firmware,
            'last_calibration_date' => $item->last_calibration_date?->toDateString(),
            'next_calibration_date' => $item->next_calibration_date?->toDateString(),
            'metrology_review_due_at' => $item->metrology_review_due_at?->toDateString(),
            'metrological_uncertainty_value' => $item->metrological_uncertainty_value,
            'metrological_uncertainty_unit' => $item->metrological_uncertainty_unit,
            'metrological_traceability_reference' => $item->metrological_traceability_reference,
            'metrology_notes' => $item->metrology_notes,
            'has_safety_documentation' => (bool) $item->has_safety_documentation,
            'days_to_calibration' => $daysToCalibration,
            'needs_calibration' => $item->needs_calibration,
            'calibration_status' => $item->calibration_status,
            'metrology_status' => $item->metrology_status,
            'is_metrologically_ready' => $item->is_metrologically_ready,
            'category' => $item->category ? [
                'id' => $item->category->id,
                'name' => $item->category->name,
            ] : null,
            'type' => $item->type ? [
                'id' => $item->type->id,
                'name' => $item->type->name,
            ] : null,
        ];
    }

    public function reagentExpiryReport(InventoryCatalogueReportRequest $request): InertiaResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $today = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in31Days = now()->addDays(31)->toDateString();
        $in60Days = now()->addDays(60)->toDateString();
        $in61Days = now()->addDays(61)->toDateString();
        $in90Days = now()->addDays(90)->toDateString();
        $status = in_array($request->input('status'), ['expired', 'expiring_soon', 'good'], true)
            ? $request->input('status')
            : '';
        $sortBy = in_array($request->input('sort_by'), ['expiry_date', 'name', 'current_stock'], true)
            ? $request->input('sort_by')
            : 'expiry_date';
        $sortDirection = $request->input('sort_direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->input('search', ''));

        $stockScope = fn (Builder|HasMany $stock): Builder|HasMany => $stock->where('inventory.lab_id', $labId)
            ->whereHas('warehouse', fn (Builder $warehouses): Builder => $warehouses->withTrashed()->where('lab_id', $labId));
        $query = $this->readableItems($labId)->with([
            'category:id,name',
            'supplier:id,name',
            'inventory' => fn (HasMany $query): HasMany => $stockScope($query)->select('id', 'item_id', 'warehouse_id', 'qty_available'),
            'inventory.warehouse' => fn ($warehouses) => $warehouses->withTrashed()->select('id', 'name'),
        ])
            ->withSum(['inventory' => $stockScope], 'qty_available')
            ->withCount(['inventory' => $stockScope])
            ->reagents()
            ->whereNotNull('reagent_expiry_date')
            ->when($status, function ($query, $status) use ($today, $in60Days) {
                if ($status === 'expired') {
                    $query->whereDate('reagent_expiry_date', '<', $today);
                } elseif ($status === 'expiring_soon') {
                    $query->whereBetween('reagent_expiry_date', [$today, $in60Days]);
                } elseif ($status === 'good') {
                    $query->whereDate('reagent_expiry_date', '>', $in60Days);
                }
            })
            ->when($request->input('category_id'), fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->input('warehouse_id'), fn ($query, $warehouseId) => $query->whereHas(
                'inventory',
                fn (Builder $inventoryQuery): Builder => $stockScope($inventoryQuery)->where('warehouse_id', $warehouseId)
            ))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('internal_code', 'like', "%{$search}%")
                        ->orWhere('lot', 'like', "%{$search}%");
                });
            });

        if ($sortBy === 'current_stock') {
            $query->orderBy('inventory_sum_qty_available', $sortDirection);
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', $sortDirection);
        } else {
            $query->orderBy('reagent_expiry_date', $sortDirection);
        }

        $query->orderBy('name');

        $reagents = $query
            ->paginate($request->validated('per_page') ?? 20)
            ->withQueryString()
            ->through(fn (InventoryItem $reagent) => $this->formatReagentExpiryReportRow($reagent));

        $stats = $this->readableItems($labId)
            ->reagents()
            ->whereNotNull('reagent_expiry_date')
            ->selectRaw('count(*) as total_reagents')
            ->selectRaw('count(case when reagent_expiry_date < ? then 1 end) as expired', [$today])
            ->selectRaw('count(case when reagent_expiry_date between ? and ? then 1 end) as expiring_30', [$today, $in30Days])
            ->selectRaw('count(case when reagent_expiry_date between ? and ? then 1 end) as expiring_31_60', [$in31Days, $in60Days])
            ->selectRaw('count(case when reagent_expiry_date between ? and ? then 1 end) as expiring_61_90', [$in61Days, $in90Days])
            ->first();

        return Inertia::render('VAPInventory/Reagents/ExpiryReport', [
            'reagents' => $reagents,
            'filters' => [
                'status' => $status,
                'category_id' => $request->input('category_id', ''),
                'warehouse_id' => $request->input('warehouse_id', ''),
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'categories' => $this->catalogueAccess->categories($request->user(), 'view')->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'stats' => [
                'expired' => (int) ($stats->expired ?? 0),
                'expiring_soon' => (int) ($stats->expiring_30 ?? 0) + (int) ($stats->expiring_31_60 ?? 0),
                'expiring_30' => (int) ($stats->expiring_30 ?? 0),
                'expiring_31_60' => (int) ($stats->expiring_31_60 ?? 0),
                'expiring_61_90' => (int) ($stats->expiring_61_90 ?? 0),
                'total_reagents' => (int) ($stats->total_reagents ?? 0),
            ],
        ]);
    }

    private function formatReagentExpiryReportRow(InventoryItem $reagent): array
    {
        return [
            'id' => $reagent->id,
            'name' => $reagent->name,
            'code' => $reagent->code,
            'internal_code' => $reagent->internal_code,
            'lot' => $reagent->lot,
            'refrigerated' => (bool) $reagent->refrigerated,
            'reagent_open_date' => $reagent->reagent_open_date?->toDateString(),
            'reagent_expiry_date' => $reagent->reagent_expiry_date?->toDateString(),
            'total_stock' => (float) ($reagent->inventory_sum_qty_available ?? 0),
            'warehouse_count' => (int) ($reagent->inventory_count ?? 0),
            'is_expired' => $reagent->is_expired,
            'days_to_expiry' => $reagent->days_to_expiry,
            'category' => $reagent->category ? [
                'id' => $reagent->category->id,
                'name' => $reagent->category->name,
            ] : null,
            'supplier' => $reagent->supplier ? [
                'id' => $reagent->supplier->id,
                'name' => $reagent->supplier->name,
            ] : null,
            'inventory' => $reagent->inventory
                ->map(fn (Inventory $inventory) => [
                    'id' => $inventory->id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'qty_available' => $inventory->qty_available,
                    'warehouse' => $inventory->warehouse ? [
                        'id' => $inventory->warehouse->id,
                        'name' => $inventory->warehouse->name,
                    ] : null,
                ])
                ->values(),
        ];
    }

    public function lowStockReport(InventoryCatalogueReportRequest $request): InertiaResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $query = $this->readableStock($labId)->with([
            'item' => fn ($items) => $items->withTrashed(),
            'item.category' => fn ($category) => $category->withTrashed()->select('id', 'name'),
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed()->select('id', 'name', 'location_id'),
            'warehouse.location:id,name',
        ])
            ->lowStock()
            ->where('qty_available', '>', 0)
            ->when($request->warehouse_id, function ($query, $warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->whereHas('item', fn ($item) => $item->withTrashed()->where('category_id', $categoryId));
            })
            ->when($request->severity === 'critical', function ($query) {
                $query->whereColumn('qty_available', '<=', 'min_stock_level');
            })
            ->when($request->severity === 'low', function ($query) {
                $query->whereColumn('qty_available', '>', 'min_stock_level');
            });

        $sortBy = match ($request->sort_by) {
            'current_stock' => 'qty_available',
            'reorder_point' => 'reorder_point',
            'item_name' => 'item_id',
            default => null,
        };

        if ($request->sort_by === 'severity' || $request->sort_by === null) {
            $query->orderByRaw(
                'CASE
                    WHEN qty_available <= min_stock_level THEN 0
                    WHEN qty_available <= reorder_point THEN 1
                    ELSE 2
                END'
            )->orderByRaw('qty_available / NULLIF(reorder_point, 0)');
        } elseif ($sortBy !== null) {
            $query->orderBy($sortBy);
        } else {
            $query->orderByRaw('qty_available / NULLIF(reorder_point, 0)');
        }

        $inventory = $query->get();

        $severityLabels = ['Sem existências', 'Crítico', 'Baixo'];
        $severitySeries = [
            $this->readableStock($labId)
                ->when($request->warehouse_id, fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
                ->when($request->category_id, fn ($query, $categoryId) => $query->whereHas('item', fn ($item) => $item->withTrashed()->where('category_id', $categoryId)))
                ->where('qty_available', '<=', 0)
                ->count(),
            $inventory->filter(fn ($item) => $item->qty_available <= $item->min_stock_level)->count(),
            $inventory->filter(fn ($item) => $item->qty_available > $item->min_stock_level)->count(),
        ];

        $warehouseExposure = $inventory
            ->groupBy(fn ($item) => $item->warehouse?->name ?: 'Sem armazém')
            ->map(fn ($items, $warehouse) => [
                'label' => $warehouse,
                'count' => $items->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $replenishmentGap = $inventory
            ->map(fn ($item) => [
                'label' => $item->item?->code ?: $item->item?->name ?: "Item #{$item->item_id}",
                'gap' => max((float) $item->reorder_point - (float) $item->qty_available, 0),
            ])
            ->sortByDesc('gap')
            ->take(8)
            ->values();

        return Inertia::render('VAPInventory/Reports/LowStock', [
            'inventory' => $query->paginate($request->validated('per_page') ?? 20)->withQueryString()
                ->through(fn (Inventory $stock): array => (new InventoryLowStockResource($stock))->resolve($request)),
            'filters' => $request->only(['warehouse_id', 'category_id', 'severity', 'sort_by']),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()->get(['id', 'name']),
            'categories' => $this->catalogueAccess->categories($request->user(), 'view')->active()->get(['id', 'name']),
            'charts' => [
                'severity_mix' => [
                    'labels' => $severityLabels,
                    'series' => $severitySeries,
                ],
                'warehouse_exposure' => [
                    'labels' => $warehouseExposure->pluck('label')->all(),
                    'series' => $warehouseExposure->pluck('count')->map(fn ($value) => (int) $value)->all(),
                ],
                'replenishment_gap' => [
                    'labels' => $replenishmentGap->pluck('label')->all(),
                    'series' => [
                        [
                            'name' => 'Gap para reabastecimento',
                            'data' => $replenishmentGap->pluck('gap')->map(fn ($value) => (float) $value)->all(),
                        ],
                    ],
                ],
            ],
            'stats' => [
                'total_low_stock' => $inventory->count(),
                'out_of_stock' => $severitySeries[0],
                'critical_stock' => $severitySeries[1],
                'total_items' => $this->readableStock($labId)
                    ->when($request->warehouse_id, fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
                    ->when($request->category_id, fn ($query, $categoryId) => $query->whereHas('item', fn ($item) => $item->withTrashed()->where('category_id', $categoryId)))
                    ->count(),
            ],
        ]);
    }

    public function reagentConsumption(InventoryOperationalReportRequest $request): InertiaResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $filters = $request->validated();
        $query = $this->catalogueRead->filter($this->catalogueRead->consumptions($labId, $request->user()), $filters, 'consumption');
        $net = (clone $query)->unreversed();
        $summaryByItem = (clone $net)->select('reagent_id', 'reagent_name')
            ->selectRaw('SUM(quantity_used) as total_consumption, COUNT(*) as usage_count, AVG(quantity_used) as avg_per_use')
            ->groupBy('reagent_id', 'reagent_name')->orderByDesc('total_consumption')->get();
        $summaryByDate = (clone $net)->selectRaw('DATE(date) as date, SUM(quantity_used) as total_consumption, COUNT(*) as usage_count')
            ->groupBy(DB::raw('DATE(date)'))->orderByDesc('date')->get();
        $summaryByUser = (clone $net)->select('used_by')
            ->selectRaw('SUM(quantity_used) as total_consumption, COUNT(*) as usage_count')
            ->whereNotNull('used_by')->groupBy('used_by')->orderByDesc('total_consumption')->get();

        return Inertia::render('VAPInventory/Reagents/Consumption', [
            'consumptions' => (clone $query)->with($this->consumptionRelations())
                ->orderBy($filters['sort_by'] ?? 'date', $filters['sort_direction'] ?? 'desc')
                ->orderByDesc('id')->paginate($filters['per_page'] ?? 50)->withQueryString()
                ->through(fn (ReagentConsumption $row): array => (new InventoryReagentConsumptionResource($row))->resolve($request)),
            'summaryByItem' => $summaryByItem,
            'summaryByDate' => $summaryByDate,
            'summaryByUser' => $summaryByUser,
            'filters' => $filters,
            'items' => $this->readableItems($labId)->reagents()->active()->get(['id', 'name', 'code']),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()->get(['id', 'name']),
            'users' => User::withTrashed()->whereIn('id', (clone $query)->select('user_id'))->get(['id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name]),
            'stats' => [
                'total_consumption' => $summaryByItem->sum('total_consumption'),
                'total_uses' => $summaryByItem->sum('usage_count'),
                'avg_daily_consumption' => $this->catalogueRead->dailyAverage($net, $filters, 'date', 'quantity_used'),
                'most_consumed_item' => $summaryByItem->first(),
                'most_active_user' => $summaryByUser->first(),
                'peak_consumption_day' => $summaryByDate->sortByDesc('total_consumption')->first(),
            ],
        ]);
    }

    /** @return array<int|string,mixed> */
    private function consumptionRelations(): array
    {
        return [
            'item' => fn ($items) => $items->withTrashed()->select(['id', 'name', 'code', 'internal_code', 'category_id', 'unit_id', 'supplier_id', 'reagent_expiry_date', 'brand', 'model', 'serial_number', 'deleted_at']),
            'item.category' => fn ($categories) => $categories->withTrashed()->select('id', 'name'),
            'item.unit' => fn ($units) => $units->withTrashed()->select('id', 'code'),
            'warehouse' => fn ($warehouses) => $warehouses->withTrashed()->select('id', 'name', 'location_id'),
            'user' => fn ($users) => $users->withTrashed()->select('id', 'name'),
            'reversal.user' => fn ($users) => $users->withTrashed()->select('id', 'name'),
        ];
    }

    public function createConsumption(Request $request): InertiaResponse
    {
        abort_unless($request->user()->can('add_reagent_consumption') && ! $request->session()->has('impersonate'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        $reagents = InventoryItem::forLaboratory($labId)->reagents()->active()
            ->whereHas('category', fn (Builder $categories): Builder => $categories->whereIn('inventory_type', ['material', 'equipment']))
            ->with([
                'category:id,name', 'unit' => fn ($units) => $units->withTrashed()->select('id', 'code'),
                'inventory' => fn ($stock) => $stock->forLaboratory($labId)
                    ->whereHas('warehouse', fn ($warehouses) => $warehouses->where('lab_id', $labId)->active())
                    ->select(['id', 'item_id', 'warehouse_id', 'qty_available']),
            ])->get(['id', 'name', 'code', 'category_id', 'unit_id', 'reagent_expiry_date']);

        return Inertia::render('VAPInventory/Reagents/CreateConsumption', [
            'reagents' => InventoryReagentChoiceResource::collection($reagents)->resolve($request),
            'warehouses' => InventoryItemWarehouse::where('lab_id', $labId)->active()->get(['id', 'name']),
            'users' => [['id' => $request->user()->id, 'name' => $request->user()->name]],
            'backUrl' => route($request->user()->can('view_inventory') && $this->catalogueAccess->any($request->user(), 'view')
                ? 'vap-inventory.reagents.consumption.index' : 'dashboard'),
        ]);
    }

    public function storeConsumption(ConsumeInventoryReagentRequest $request, DuplicateSubmissionGuard $duplicateSubmissionGuard, ConsumeInventoryReagent $consumeReagent): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'vap-inventory-store-consumption', $validated, 30)) {
            return redirect()->back()
                ->with('error', 'Já existe um registo idêntico de consumo em processamento.')
                ->withInput();
        }

        try {
            $reagent = InventoryItem::forLaboratory($labId)->findOrFail($validated['reagent_id']);
            $consumeReagent->execute($labId, $request->user(), $reagent, $validated);

            return $this->consumptionDestination($request->user())
                ->with('success', 'Consumo de reagente registado com sucesso.');
        } catch (ValidationException $exception) {
            return redirect()->back()
                ->withErrors($exception->errors())
                ->withInput();
        }
    }

    public function consume(ConsumeInventoryReagentRequest $request, InventoryItem $item, DuplicateSubmissionGuard $duplicateSubmissionGuard, ConsumeInventoryReagent $consumeReagent)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless((int) $item->lab_id === $labId, 404);
        $validated = $request->validated();

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'vap-inventory-consume', array_merge($validated, [
            'item_id' => $item->id,
        ]), 30)) {
            return response()->json([
                'error' => 'Um consumo idêntico deste reagente já está a ser processado.',
            ], 429);
        }

        try {
            $result = $consumeReagent->execute($labId, $request->user(), $item, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Consumo de reagente registado com êxito',
                'consumption' => $result['consumption'],
                'new_quantity' => $result['new_quantity'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json(['errors' => $exception->errors()], 422);
        }
    }

    public function showConsumption(Request $request, ReagentConsumption $consumption): InertiaResponse
    {
        abort_unless($request->user()->can('view_inventory') && $this->catalogueAccess->any($request->user(), 'view'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        $consumption = $this->catalogueRead->consumptions($labId, $request->user())
            ->with([
                ...$this->consumptionRelations(),
                'warehouse.location:id,name', 'item.supplier' => fn ($suppliers) => $suppliers->withTrashed()->select('id', 'name'),
                'item.inventory' => fn ($stock) => $stock->forLaboratory($labId)
                    ->whereHas('warehouse', fn ($warehouses) => $warehouses->withTrashed()->where('lab_id', $labId))
                    ->select(['id', 'item_id', 'warehouse_id', 'qty_available']),
            ])->findOrFail($consumption->id);

        return Inertia::render('VAPInventory/Reagents/ShowConsumption', [
            'consumption' => (new InventoryReagentConsumptionResource($consumption))->resolve($request),
        ]);
    }

    public function reverseConsumption(Request $request, ReagentConsumption $consumption, ReverseInventoryReagentConsumption $reverseConsumption): RedirectResponse
    {
        $labId = $this->laboratoryAccess->activeLabId();
        abort_unless(ReagentConsumption::forLaboratory($labId)->whereKey($consumption->id)->exists(), 404);
        try {
            $reverseConsumption->execute($labId, $request->user()->id, $consumption->id);

            return $this->consumptionDestination($request->user())
                ->with('success', 'Consumo revertido. As existências foram repostas e o histórico foi preservado.');
        } catch (ValidationException $exception) {
            return redirect()->back()
                ->withErrors($exception->errors());
        }
    }

    private function consumptionDestination(User $user): RedirectResponse
    {
        if ($user->can('view_inventory') && $this->catalogueAccess->any($user, 'view')) {
            return redirect()->route('vap-inventory.reagents.consumption.index');
        }

        return redirect()->route($user->can('add_reagent_consumption') ? 'vap-inventory.reagents.consumption.create' : 'dashboard');
    }

    public function downloadallattachments(): MediaStream
    {
        $item = InventoryItem::forLaboratory($this->laboratoryAccess->activeLabId())
            ->findOrFail(request()->integer('model_id'));
        $this->catalogueAccess->authorize(request()->user(), $item, 'view');
        $documents = $item->getMedia('documents');

        return MediaStream::create('documents.zip')->addMedia($documents);
    }

    public function downloadsingleattachment(): Media
    {
        $media = InventoryItemDocumentMedia::withTrashed()->findOrFail(request()->integer('model_id'));
        abort_unless($media->model_type === (new InventoryItem)->getMorphClass() && $media->collection_name === 'documents', 404);
        $item = InventoryItem::withTrashed()->forLaboratory($this->laboratoryAccess->activeLabId())->findOrFail($media->model_id);
        $this->catalogueAccess->authorize(request()->user(), $item, 'view');

        return $media;
    }

    public function deleteattachment(int $id, SetInventoryDocumentArchived $archiveDocument): RedirectResponse
    {
        $archiveDocument->execute($this->laboratoryAccess->activeLabId(), request()->user()->id, request()->integer('model_id'), $id, true);

        return redirect()->back();
    }

    public function restoreAttachment(int $id, SetInventoryDocumentArchived $archiveDocument): RedirectResponse
    {
        $archiveDocument->execute($this->laboratoryAccess->activeLabId(), request()->user()->id, request()->integer('model_id'), $id, false);

        return redirect()->back();
    }

    /**
     * Export all inventory items to an Excel file.
     */
    public function exportInventory(InventoryCatalogueExportRequest $request): HttpResponse
    {
        $data = $request->validated();
        $types = $this->catalogueAccess->allowedTypes($request->user(), 'export');
        if (isset($data['inventory_type'])) {
            $types = array_values(array_intersect($types, [$data['inventory_type']]));
        }

        return Excel::download(new InventoryItemsExport($this->laboratoryAccess->activeLabId(), $types,
            $data['start'] ?? null, $data['end'] ?? null, isset($data['category_id']) ? (int) $data['category_id'] : null), 'inventory_items.xlsx');
    }
}
