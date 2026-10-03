<?php

namespace App\Http\Controllers;

use App\Actions\SaveInventoryWarehouse;
use App\Actions\SetInventoryWarehousesArchived;
use App\Http\Requests\InventoryItemWarehouseRequest;
use App\Http\Requests\SetInventoryWarehousesArchivedRequest;
use App\Http\Resources\InventoryItemWarehouseResource;
use App\Models\InventoryItemWarehouse;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryItemWarehouseController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_iwarehouses'), 403, '');
        $labId = $this->laboratoryAccess->activeLabId();

        return Inertia::render('InventoryItemWarehouses/Index', [
            'record' => InventoryItemWarehouseResource::collection(
                InventoryItemWarehouse::query()
                    ->where('lab_id', $labId)
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%");
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
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.is_ventilated'),
                    'value' => 'is_ventilated',
                ],
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.is_refrigerated'),
                    'value' => 'is_refrigerated',
                ],
                [
                    'name' => trans('gestlab.general.labels.iwarehouses.has_air_exhaustion'),
                    'value' => 'has_air_exhaustion',
                ],
            ],
            'model' => InventoryItemWarehouse::MENU_NAME,
            'abilities' => method_exists(InventoryItemWarehouse::class, 'getAbilities') ? collect(InventoryItemWarehouse::ABILITIES)->map(function ($item) {
                return $item.'_'.InventoryItemWarehouse::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.InventoryItemWarehouse::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_iwarehouses'), 403, '');
        $this->laboratoryAccess->activeLabId();

        // Get any required data

        // Load form

        return Inertia::render('InventoryItemWarehouses/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InventoryItemWarehouseRequest $request, SaveInventoryWarehouse $action): RedirectResponse
    {
        $action->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated());

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
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_iwarehouses'), 403, '');

        // Find the record
        $record = InventoryItemWarehouse::query()
            ->where('lab_id', $this->laboratoryAccess->activeLabId())->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItemWarehouses/Edit', [
            'record' => InventoryItemWarehouseResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryItemWarehouseRequest $request, int $id, SaveInventoryWarehouse $action): RedirectResponse
    {
        $action->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated(), $id);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SetInventoryWarehousesArchivedRequest $request, SetInventoryWarehousesArchived $action): RedirectResponse
    {
        $action->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('recordIds'), true);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore(SetInventoryWarehousesArchivedRequest $request, SetInventoryWarehousesArchived $action): RedirectResponse
    {
        $action->execute($request->user()->id, $this->laboratoryAccess->activeLabId(), $request->validated('recordIds'), false);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getInventoryItemWarehouse(Request $request)
    {
        abort_unless($request->user()?->can('view_iwarehouses'), 403);
        $search = $request->string('q')->toString();
        $data = InventoryItemWarehouse::query()
            ->where('lab_id', $this->laboratoryAccess->activeLabId())
            ->when($search !== '', fn ($query) => $query->where('name', 'ILIKE', '%'.$search.'%'))
            ->orderBy('name')->limit(50)->get();

        return response()->json($data);
    }
}
