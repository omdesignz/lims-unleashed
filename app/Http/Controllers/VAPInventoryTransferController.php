<?php

namespace App\Http\Controllers;

use App\Actions\ManageInventoryTransfers;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\InventoryItemTransfer;
use App\Models\InventoryItemWarehouse;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VAPInventoryTransferController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly ManageInventoryTransfers $transfers,
    ) {}

    public function index(Request $request): Response
    {
        $labId = $this->authorizeLab($request, 'view_itransfers');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'sent', 'received'])],
            'source_id' => ['nullable', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)],
            'destination_id' => ['nullable', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)],
            'sort_by' => ['nullable', Rule::in(['created_at', 'sent_date', 'received_date', 'qty'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = InventoryItemTransfer::query()->where('lab_id', $labId)
            ->with(['item.category', 'item.unit', 'source', 'destination'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas('item',
                fn ($itemQuery) => $itemQuery->where('name', 'ILIKE', '%'.$search.'%')->orWhere('code', 'ILIKE', '%'.$search.'%')))
            ->when($filters['status'] ?? null, function ($query, $status): void {
                if ($status === 'received') {
                    $query->whereNotNull('received_date');
                } elseif ($status === 'sent') {
                    $query->whereNotNull('sent_date')->whereNull('received_date');
                } else {
                    $query->whereNull('received_date');
                }
            })
            ->when($filters['source_id'] ?? null, fn ($query, $id) => $query->where('source_id', $id))
            ->when($filters['destination_id'] ?? null, fn ($query, $id) => $query->where('destination_id', $id))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_direction'] ?? 'desc');

        $counts = InventoryItemTransfer::query()->where('lab_id', $labId);

        return Inertia::render('VAPInventory/Transfers/Index', [
            'transfers' => $query->paginate($filters['per_page'] ?? 20)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'source_id', 'destination_id', 'sort_by', 'sort_direction']),
            'warehouses' => InventoryItemWarehouse::query()->where('lab_id', $labId)->with('location')->orderBy('name')->get(),
            'stats' => [
                'pending_transfers' => (clone $counts)->whereNull('received_date')->count(),
                'in_transit' => (clone $counts)->whereNotNull('sent_date')->whereNull('received_date')->count(),
                'received' => (clone $counts)->whereNotNull('received_date')->count(),
                'sent_today' => (clone $counts)->whereDate('sent_date', today())->count(),
                'received_today' => (clone $counts)->whereDate('received_date', today())->count(),
                'total_transfers' => $counts->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $labId = $this->authorizeLab($request, 'add_itransfers');
        $warehouses = InventoryItemWarehouse::query()->where('lab_id', $labId)->with('location')->orderBy('name')->get();
        $items = InventoryItem::forLaboratory($labId)->active()->orderBy('name')->get();
        $stockInfo = Inventory::query()->whereIn('warehouse_id', $warehouses->modelKeys())
            ->get(['item_id', 'warehouse_id', 'qty_available'])
            ->mapWithKeys(fn (Inventory $stock): array => [$stock->item_id.'_'.$stock->warehouse_id => $stock->qty_available]);

        return Inertia::render('VAPInventory/Transfers/Create', [
            'items' => $items,
            'warehouses' => $warehouses,
            'defaultSource' => $request->query('source_id'),
            'defaultItem' => $request->query('item_id'),
            'initialStockInfo' => $stockInfo,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $labId = $this->authorizeLab($request, 'add_itransfers');
        $data = $request->validate($this->transferRules($labId));
        $transfer = $this->transfers->create($labId, $request->user(), $data);

        return redirect()->route('vap-inventory.transfers.show', $transfer)
            ->with('success', 'Transferência criada. As existências ficaram reservadas na origem.');
    }

    public function show(Request $request, InventoryItemTransfer $transfer): Response
    {
        $labId = $this->authorizeLab($request, 'view_itransfers');
        abort_unless($transfer->lab_id === $labId, 404);
        $transfer->load(['item.category', 'item.unit', 'source.location', 'destination.location']);
        $sourceStock = Inventory::query()->where('item_id', $transfer->item_id)->where('warehouse_id', $transfer->source_id)->first();
        $destinationStock = Inventory::query()->where('item_id', $transfer->item_id)->where('warehouse_id', $transfer->destination_id)->first();
        $canReceive = $transfer->received_date === null && $transfer->sent_date !== null;
        $canCancel = $transfer->received_date === null;

        return Inertia::render('VAPInventory/Transfers/Show', [
            'transfer' => $transfer,
            'sourceStock' => $sourceStock,
            'destinationStock' => $destinationStock,
            'canReceive' => $canReceive && $request->user()->can('edit_itransfers'),
            'canCancel' => $canCancel && $request->user()->can('delete_itransfers'),
        ]);
    }

    public function receive(Request $request, InventoryItemTransfer $transfer): RedirectResponse
    {
        $labId = $this->authorizeLab($request, 'edit_itransfers');
        abort_unless($transfer->lab_id === $labId, 404);
        $data = $request->validate([
            'actual_qty' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:'.$transfer->qty],
            'received_date' => ['required', 'date', 'after_or_equal:'.$transfer->sent_date?->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->transfers->receive($labId, $request->user(), $transfer, $data['actual_qty'], $data['received_date'], $data['notes'] ?? null);

        return redirect()->route('vap-inventory.transfers.show', $transfer)
            ->with('success', 'Transferência recepcionada. As existências foram actualizadas.');
    }

    public function cancel(Request $request, InventoryItemTransfer $transfer): RedirectResponse
    {
        $labId = $this->authorizeLab($request, 'delete_itransfers');
        abort_unless($transfer->lab_id === $labId, 404);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $this->transfers->cancel($labId, $request->user(), $transfer, $data['notes'] ?? null);

        return redirect()->route('vap-inventory.transfers.index')
            ->with('success', 'Transferência cancelada. As existências regressaram à origem.');
    }

    public function bulkTransfer(Request $request): JsonResponse
    {
        $labId = $this->authorizeLab($request, 'add_itransfers');
        $rules = $this->transferRules($labId);
        $data = $request->validate([
            'transfers' => ['required', 'array', 'min:1', 'max:100'],
            'transfers.*.item_id' => $rules['item_id'],
            'transfers.*.source_id' => $rules['source_id'],
            'transfers.*.destination_id' => $rules['destination_id'],
            'transfers.*.qty' => $rules['qty'],
            'sent_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:sent_date'],
        ]);

        $created = $this->transfers->createMany($labId, $request->user(), $data['transfers'],
            $data['sent_date'] ?? null, $data['expected_date'] ?? null);

        return response()->json(['success' => true, 'message' => count($created).' transferências criadas.', 'transfers' => $created]);
    }

    public function getItemStock(Request $request): JsonResponse
    {
        $labId = $this->authorizeLab($request, 'view_itransfers');
        $data = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'warehouse_id' => ['required', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
        ]);
        $stock = Inventory::query()->where('item_id', $data['item_id'])->where('warehouse_id', $data['warehouse_id'])->first();

        return response()->json([
            'available' => $stock?->qty_available ?? 0,
            'item' => InventoryItem::forLaboratory($labId)->findOrFail($data['item_id']),
            'warehouse' => InventoryItemWarehouse::findOrFail($data['warehouse_id']),
        ]);
    }

    public function getAllStockInfo(Request $request): JsonResponse
    {
        $labId = $this->authorizeLab($request, 'view_itransfers');
        $items = InventoryItem::forLaboratory($labId)->active()->get(['id', 'name', 'code']);
        $warehouses = InventoryItemWarehouse::query()->where('lab_id', $labId)->get(['id', 'name']);
        $stockInfo = Inventory::query()->whereIn('warehouse_id', $warehouses->modelKeys())
            ->get(['item_id', 'warehouse_id', 'qty_available'])
            ->mapWithKeys(fn (Inventory $stock): array => [$stock->item_id.'_'.$stock->warehouse_id => $stock->qty_available]);

        return response()->json(['stock_info' => $stockInfo, 'items' => $items, 'warehouses' => $warehouses]);
    }

    public function getItemStockAllWarehouses(Request $request): JsonResponse
    {
        $labId = $this->authorizeLab($request, 'view_itransfers');
        $data = $request->validate(['item_id' => ['required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')]]);
        $warehouses = InventoryItemWarehouse::query()->where('lab_id', $labId)->with('location')->get();
        $stock = Inventory::query()->where('item_id', $data['item_id'])
            ->whereIn('warehouse_id', $warehouses->modelKeys())->pluck('qty_available', 'warehouse_id');

        return response()->json([
            'item_id' => $data['item_id'],
            'stocks' => $warehouses->mapWithKeys(fn (InventoryItemWarehouse $warehouse): array => [$warehouse->id => $stock[$warehouse->id] ?? 0]),
            'warehouses' => $warehouses,
        ]);
    }

    private function authorizeLab(Request $request, string $permission): int
    {
        abort_unless($request->user()?->can($permission), 403);

        return $this->laboratoryAccess->activeLabId();
    }

    /** @return array<string, mixed> */
    private function transferRules(int $labId): array
    {
        return [
            'item_id' => ['required', 'integer', Rule::exists('i_items', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'source_id' => ['required', 'integer', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'destination_id' => ['required', 'integer', 'different:source_id', Rule::exists('i_warehouses', 'id')->where('lab_id', $labId)->whereNull('deleted_at')],
            'qty' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001'],
            'sent_date' => ['nullable', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:sent_date'],
            'obs' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
