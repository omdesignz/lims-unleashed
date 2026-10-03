<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryTransaction;
use App\Services\InventoryCatalogueAccess;
use App\Services\InventoryCatalogueRead;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryTransactionController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly InventoryCatalogueRead $catalogueRead,
        private readonly InventoryCatalogueAccess $catalogueAccess,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('view_itransactions'), 403);
        abort_unless($this->catalogueAccess->any($request->user(), 'view'), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'in:trashed'],
        ]);

        $transactions = $this->catalogueRead->transactions($this->laboratoryAccess->activeLabId(), $request->user())
            ->with(['inventory', 'type' => fn ($types) => $types->withTrashed(), 'item' => fn ($items) => $items->withTrashed(), 'warehouse' => fn ($warehouses) => $warehouses->withTrashed(), 'user' => fn ($users) => $users->withTrashed()->select('id', 'name')])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($search)).'%';
                $query->where(function (Builder $searchQuery) use ($pattern): void {
                    $searchQuery->whereLike($searchQuery->qualifyColumn('qty'), $pattern)
                        ->orWhereHas('type', fn (Builder $types): Builder => $types->withTrashed()->whereLike('name', $pattern))
                        ->orWhereHas('warehouse', fn (Builder $warehouses): Builder => $warehouses->withTrashed()->whereLike('name', $pattern))
                        ->orWhereHas('item', fn (Builder $items): Builder => $items->withTrashed()->whereLike('name', $pattern));
                });
            })
            ->when(($filters['filter'] ?? null) === 'trashed', fn (Builder $query) => $query->withTrashed())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('InventoryTransactions/Index', [
            'record' => InventoryTransactionResource::collection($transactions),
            'slideOverEdit' => false,
            'fields' => [
                ['name' => trans('gestlab.general.labels.itransactions.item_id'), 'value' => 'item'],
                ['name' => trans('gestlab.general.labels.itransactions.type_id'), 'value' => 'type'],
                ['name' => trans('gestlab.general.labels.itransactions.qty'), 'value' => 'qty'],
                ['name' => trans('gestlab.general.labels.itransactions.warehouse_id'), 'value' => 'warehouse'],
            ],
            'model' => InventoryTransaction::MENU_NAME,
            'query' => $filters,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        abort_unless($request->user()->can('view_itransactions'), 403);
        abort_unless($this->catalogueAccess->any($request->user(), 'view'), 403);

        return Inertia::render('InventoryTransactions/Show', [
            'record' => InventoryTransactionResource::make(
                $this->catalogueRead->transactions($this->laboratoryAccess->activeLabId(), $request->user())
                    ->with(['inventory', 'type' => fn ($types) => $types->withTrashed(), 'item' => fn ($items) => $items->withTrashed(), 'warehouse' => fn ($warehouses) => $warehouses->withTrashed(), 'user' => fn ($users) => $users->withTrashed()->select('id', 'name')])
                    ->findOrFail($id)
            ),
        ]);
    }

    public function create(): never
    {
        abort(410, 'Use the controlled stock adjustment workflow.');
    }

    public function store(): never
    {
        abort(410, 'Use the controlled stock adjustment workflow.');
    }

    public function edit(int $id): never
    {
        abort(410, 'Ledger entries cannot be edited directly.');
    }

    public function update(int $id): never
    {
        abort(410, 'Ledger entries cannot be edited directly.');
    }

    public function destroy(): never
    {
        abort(410, 'Ledger entries cannot be deleted directly.');
    }

    public function restore(): never
    {
        abort(410, 'Ledger entries cannot be restored directly.');
    }
}
