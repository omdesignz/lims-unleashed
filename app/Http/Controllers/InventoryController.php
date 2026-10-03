<?php

namespace App\Http\Controllers;

use App\Actions\AdjustInventoryItemStock;
use App\Actions\CreateInventoryPosition;
use App\Actions\MutateInventoryPositions;
use App\Http\Requests\InventoryIndexRequest;
use App\Http\Requests\InventoryPositionLifecycleRequest;
use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Services\SampleLaboratoryAccess;
use App\Support\NotificationTemplateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    /**
     * Display a listing of the resource.
     */
    public function index(InventoryIndexRequest $request): Response
    {
        $canView = $request->user()->can('view_inventory');
        $labId = $this->laboratoryAccess->activeLabId();

        return Inertia::render('Inventory/Index', [
            'record' => InventoryResource::collection(
                $this->positionsForLaboratory($labId)
                    ->when(! $canView, fn (Builder $query): Builder => $query->whereRaw('false'))
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(function ($searchQuery) use ($search) {
                            $searchQuery->whereRaw('CAST(inventory.qty_available AS TEXT) ILIKE ?', ['%'.$search.'%'])
                                ->orWhereHas('item', function (Builder $item) use ($search): void {
                                    $item->withTrashed()->whereHas('category', fn (Builder $category): Builder => $category
                                        ->withTrashed()->where('name', 'like', "%{$search}%"));
                                })
                                ->orWhereRelation('warehouse', 'name', 'like', "%{$search}%");
                        });
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'canView' => $canView,
            'openCreate' => (bool) $request->validated('create', false),
            'initialRecord' => $request->validated('edit') !== null
                ? InventoryResource::make($this->positionsForLaboratory($labId)->findOrFail((int) $request->validated('edit')))
                : null,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.inventory.item_id'),
                    'value' => 'item',
                ],
                [
                    'name' => trans('gestlab.general.labels.inventory.category_id'),
                    'value' => 'category',
                ],
                [
                    'name' => trans('gestlab.general.labels.inventory.qty_available'),
                    'value' => 'qty_available',
                ],
                // [
                //     'name' => trans('gestlab.general.labels.inventory.min_stock_level'),
                //     'value' => 'min_stock_level'
                // ],
                [
                    'name' => trans('gestlab.general.labels.inventory.warehouse_id'),
                    'value' => 'warehouse',
                ],
                // [
                //     'name' => trans('gestlab.general.labels.inventory.reorder_point'),
                //     'value' => 'reorder_point'
                // ],
            ],
            'model' => Inventory::MENU_NAME,
            'abilities' => method_exists(Inventory::class, 'getAbilities') ? collect(Inventory::ABILITIES)->map(function ($item) {
                return $item.'_'.Inventory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Inventory::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(InventoryIndexRequest $request): RedirectResponse
    {
        $this->laboratoryAccess->activeLabId();

        return to_route('inventory.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InventoryRequest $request, CreateInventoryPosition $createPosition)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $createPosition->execute($labId, $request->user()->id, $request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): Response
    {
        abort_unless(auth()->user()?->can('view_inventory'), 403);

        return Inertia::render('Inventory/Show', [
            'record' => InventoryResource::make(
                $this->positionsForLaboratory($this->laboratoryAccess->activeLabId())
                    ->findOrFail($id)
            ),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryIndexRequest $request, string $id): RedirectResponse
    {
        $record = $this->positionsForLaboratory($this->laboratoryAccess->activeLabId())
            ->findOrFail((int) $request->validated('edit'));

        return to_route('inventory.index', ['edit' => $record->id]);
    }

    /** @return Builder<Inventory> */
    private function positionsForLaboratory(int $labId): Builder
    {
        return Inventory::query()->forLaboratory($labId)
            ->whereHas('warehouse', fn (Builder $query): Builder => $query->where('lab_id', $labId))
            ->with([
                'item' => function (BelongsTo $item) use ($labId): void {
                    $item->withTrashed()->where('lab_id', $labId)
                        ->select(['id', 'lab_id', 'name', 'category_id', 'deleted_at']);
                },
                'item.category' => function (BelongsTo $category): void {
                    $category->withTrashed()->select(['id', 'name', 'inventory_type', 'deleted_at']);
                },
                'warehouse:id,name,lab_id',
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryRequest $request, string $id, MutateInventoryPositions $mutatePositions): RedirectResponse
    {
        $mutatePositions->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, [(int) $id], 'update', $request->validated());

        return redirect()->back()->with(['toast' => [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_updated'),
        ]]);
    }

    public function destroy(InventoryPositionLifecycleRequest $request, MutateInventoryPositions $mutatePositions): RedirectResponse
    {
        $mutatePositions->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated('recordIds'), 'archive');

        return redirect()->back()->with(['toast' => [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_deleted'),
        ]]);
    }

    public function restore(InventoryPositionLifecycleRequest $request, MutateInventoryPositions $mutatePositions): RedirectResponse
    {
        $mutatePositions->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated('recordIds'), 'restore');

        return redirect()->back()->with(['toast' => [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_restored'),
        ]]);
    }

    public function getInventory()
    {
        abort_unless(auth()->user()?->can('view_inventory'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        $search = request()->string('q')->trim()->toString();

        $data = Inventory::query()
            ->whereHas('warehouse', fn ($query) => $query->where('lab_id', $labId))
            ->with(['item:id,name,category_id', 'warehouse:id,name'])
            ->select(['id', 'item_id', 'warehouse_id', 'qty_available', 'status'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereRelation('item', 'name', 'like', "%{$search}%")
                        ->orWhereRelation('warehouse', 'name', 'like', "%{$search}%");

                    if (preg_match('/^\d{1,14}(?:\.\d{1,4})?$/', $search)) {
                        $query->orWhere('qty_available', $search);
                    }
                });
            })
            ->latest('id')
            ->limit(25)
            ->get()
            ->each(function (Inventory $inventory): void {
                $inventory->setAttribute('name', $inventory->item?->name);
                $inventory->setAttribute('category_id', $inventory->item?->category_id);
            });

        return response()->json($data);
    }

    public function getInventoryReagentItem()
    {
        abort_unless(auth()->user()?->can('view_inventory'), 403);
        $labId = $this->laboratoryAccess->activeLabId();
        $data = collect();

        if (request()->filled('q')) {
            $search = request('q');

            $data = Inventory::query()
                ->whereHas('warehouse', fn ($query) => $query->where('lab_id', $labId))
                ->with('item:id,name,category_id')
                ->select(['id', 'item_id', 'warehouse_id', 'qty_available', 'status'])
                ->whereHas('item', fn ($query) => $query->reagents()->where('name', 'like', "%{$search}%"))
                ->limit(25)
                ->get()
                ->each(function (Inventory $inventory): void {
                    $inventory->setAttribute('name', $inventory->item?->name);
                    $inventory->setAttribute('category_id', $inventory->item?->category_id);
                });
        }

        return response()->json($data);
    }

    // Increment Inventory Quantity

    public function increment(Request $request, $id, AdjustInventoryItemStock $adjustStock)
    {
        abort_if(! auth()->user()->can('edit_inventory'), 403, '');

        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001'],
        ]);
        $data = Inventory::query()
            ->whereHas('warehouse', fn ($query) => $query->where('lab_id', $this->laboratoryAccess->activeLabId()))
            ->with(['item' => fn ($query) => $query->withTrashed()])->findOrFail($id);

        $adjustStock->execute($this->laboratoryAccess->activeLabId(), $request->user(), $data->item, [
            'warehouse_id' => $data->warehouse_id,
            'adjustment_type' => 'add',
            'quantity' => $validated['qty'],
            'reason' => 'Ajuste rápido de entrada',
        ], expectedInventoryId: $data->id);
        $data->refresh();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated').'. Quantidade Disponível: '.$data->qty_available.'.',
            ],
        ]);
    }

    // Decrement Inventory Quantity

    public function decrement(Request $request, $id, NotificationTemplateService $templates, AdjustInventoryItemStock $adjustStock)
    {
        abort_if(! auth()->user()->can('edit_inventory'), 403, '');

        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001'],
        ]);
        $data = Inventory::query()->with(['item' => fn ($query) => $query->withTrashed()])
            ->whereHas('warehouse', fn ($query) => $query->where('lab_id', $this->laboratoryAccess->activeLabId()))
            ->findOrFail($id);
        try {
            $adjustStock->execute($this->laboratoryAccess->activeLabId(), $request->user(), $data->item, [
                'warehouse_id' => $data->warehouse_id,
                'adjustment_type' => 'remove',
                'quantity' => $validated['qty'],
                'reason' => 'Ajuste rápido de saída',
            ], expectedInventoryId: $data->id);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['qty' => $exception->errors()['quantity'][0] ?? 'Não foi possível retirar estas existências.']);
        }
        $data->refresh();

        // Notify when stock is low
        if ($data->qty_available < $data->min_stock_level) {
            $templates->notifyPermission('inventory.low_stock', [
                'lab_id' => $this->laboratoryAccess->activeLabId(),
                'item_name' => $data->item?->name ?? 'Item',
                'quantity' => $data->qty_available,
                'minimum' => $data->min_stock_level,
                'document_url' => route('inventory.index'),
            ], auth()->id());
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated').'. Quantidade Disponível: '.$data->qty_available.'.',
            ],
        ]);

        // return response()->json($data);
    }
}
