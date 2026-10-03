<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehousePasswordRequest;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_warehouses'), 403, '');

        return Inertia::render('Warehouses/Index', [
            'record' => WarehouseResource::collection(
                Warehouse::query()->with('customer.category')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('email', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.warehouses.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.primary_phone'),
                    'value' => 'primary_phone',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.email'),
                    'value' => 'email',
                ],
                [
                    'name' => trans('gestlab.general.labels.customers.category_id'),
                    'value' => 'customer_category',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point'),
                    'value' => 'focal_point',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_contact'),
                    'value' => 'focal_point_contact',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_email'),
                    'value' => 'focal_point_email',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.address'),
                    'value' => 'address',
                ],

            ],
            'model' => Warehouse::MENU_NAME,
            'abilities' => method_exists(Warehouse::class, 'getAbilities') ? collect(Warehouse::ABILITIES)->map(function ($item) {
                return $item.'_'.Warehouse::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Warehouse::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_warehouses'), 403, '');

        return Inertia::render('Warehouses/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WarehouseRequest $request)
    {
        abort_if(! auth()->user()->can('add_warehouses'), 403, '');

        DB::transaction(function () use ($request): void {
            $warehouse = Warehouse::create($request->validated());
        });

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
    public function show(string $id, SampleLaboratoryAccess $laboratory): Response
    {
        abort_if(! auth()->user()->can('view_warehouses'), 403, '');

        $warehouseId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($warehouseId === false, 404);

        $labId = $laboratory->activeLabId();
        $warehouse = Warehouse::query()
            ->with('customer.category')
            ->findOrFail($warehouseId);
        $samples = VAPSampleEntry::query()
            ->where('lab_id', $labId)
            ->where('warehouse_id', $warehouse->id)
            ->where('customer_id', $warehouse->customer_id);

        return Inertia::render('Warehouses/Show', [
            'record' => WarehouseResource::make($warehouse),
            'siteState' => [
                'summary' => [
                    'total_samples' => (clone $samples)->count(),
                    'samples_in_progress' => (clone $samples)
                        ->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA'])
                        ->count(),
                    'completed_samples' => (clone $samples)->where('status', 'COMPLETADO')->count(),
                ],
                'recent_samples' => (clone $samples)
                    ->orderByDesc('received_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['id', 'code', 'name', 'status', 'received_at', 'analysis_end_date'])
                    ->map(fn (VAPSampleEntry $sample): array => [
                        'id' => $sample->id,
                        'code' => $sample->code,
                        'name' => $sample->name,
                        'status' => $sample->status,
                        'received_at' => optional($sample->received_at)?->toIso8601String(),
                        'analysis_end_date' => optional($sample->analysis_end_date)?->toIso8601String(),
                    ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_warehouses'), 403, '');

        // Find the record
        $record = Warehouse::with('customer.category')->findOrFail($id);
        $relatedWarehouses = Warehouse::query()
            ->where('customer_id', $record->customer_id)
            ->latest('updated_at')
            ->get();

        // Return Inertia View with record data
        return Inertia::render('Warehouses/Edit', [
            // 'record' => WarehouseResource::make($record)
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'description' => $record->description,
                'category_id' => [
                    'value' => $record->customer?->category?->id,
                    'label' => $record->customer?->category?->code ?? $record->customer?->category?->name,
                ],
                'warehouses' => $relatedWarehouses->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'email' => $item->email,
                        'invoicing_email' => $item->invoicing_email,
                        'primary_phone' => $item->primary_phone,
                        'alternative_phone' => $item->alternative_phone,
                        'nif' => $item->nif,
                        'address' => $item->address,
                        'municipality' => $item->municipality,
                        'province' => $item->province,
                        'description' => $item->description,
                        'code' => $item->code,
                        'name' => $item->name,
                        'focal_point' => $item->focal_point,
                        'focal_point_email' => $item->focal_point_email,
                        'focal_point_contact' => $item->focal_point_contact,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WarehouseRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_warehouses'), 403, '');

        // dd($request->all());

        DB::transaction(function () use ($request, $id): void {

            Warehouse::findOrFail($id)->update($request->validated());

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function setPass(WarehousePasswordRequest $request, string $id, SampleLaboratoryAccess $laboratory)
    {
        abort_unless($request->user()?->can('edit_warehouses'), 403);
        $laboratory->activeLabId();
        $warehouseId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($warehouseId === false, 404);

        DB::transaction(function () use ($request, $warehouseId): void {

            Warehouse::findOrFail($warehouseId)->forceFill([
                'password' => Hash::make($request->password),
            ])->save();

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function sendPasswordReset(Warehouse $warehouse, SampleLaboratoryAccess $laboratory)
    {
        abort_if(! auth()->user()->can('edit_warehouses'), 403, '');
        $laboratory->activeLabId();

        if (! $warehouse->email) {
            return redirect()->back()->withErrors([
                'email' => trans('gestlab.general.labels.warehouses.email_required_for_password_reset'),
            ]);
        }

        $status = Password::broker('warehouses')->sendResetLink([
            'email' => $warehouse->email,
        ]);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans($status),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        abort_if(! auth()->user()->can('delete_warehouses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Warehouse::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

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
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_warehouses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Warehouse::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getWarehouse()
    {
        $data = [];

        if (! is_null(request()->q)) {
            $search = request()->q;
            $customer_id = request()->customer_id;

            $data = DB::table('warehouses')
                ->select('warehouses.*')
                ->where('customer_id', '=', $customer_id)
                ->where(function ($query) use ($search) {
                    $query->where('address', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                })
                ->limit(5)
                ->get();
        } else {

            $data = DB::table('warehouses')
                ->select('warehouses.*')
                ->where('customer_id', '=', request()->customer_id)
                // ->where('address','LIKE',"%$search%")
                // ->where('name','LIKE',"%$search%")
                ->limit(5)
                ->get();

        }

        return response()->json($data);
    }
}
