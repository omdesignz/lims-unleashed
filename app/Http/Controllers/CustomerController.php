<?php

namespace App\Http\Controllers;

use App\Enums\Proposals\ProposalTrackingStatus;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\VAPProposal;
use App\Models\VAPSampleEntry;
use App\Services\NIFIdentificationService;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_customers'), 403, '');

        return Inertia::render('Customers/Index', [
            'record' => CustomerResource::collection(
                Customer::query()
                    ->with('category', 'main_warehouse')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
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
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.customers.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.customers.description'),
                    'value' => 'description',
                ],
                [
                    'name' => trans('gestlab.general.labels.customers.category_id'),
                    'value' => 'category',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point'),
                    'value' => 'warehouse.focal_point',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_contact'),
                    'value' => 'warehouse.focal_point_contact',
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_email'),
                    'value' => 'warehouse.focal_point_email',
                ],
            ],
            'model' => Customer::MENU_NAME,
            'abilities' => method_exists(Customer::class, 'getAbilities') ? collect(Customer::ABILITIES)->map(function ($item) {
                return $item.'_'.Customer::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Customer::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_customers'), 403, '');

        return Inertia::render('Customers/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerRequest $request)
    {
        abort_if(! auth()->user()->can('add_customers'), 403, '');

        $record = DB::transaction(fn () => Customer::create($request->validated()));

        return to_route('customers.edit', ['customer' => $record->id])->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, SampleLaboratoryAccess $laboratory): Response
    {
        abort_if(! auth()->user()->can('view_customers'), 403, '');

        $labId = $laboratory->activeLabId();
        $customer = Customer::query()
            ->with('category', 'main_warehouse', 'warehouses')
            ->findOrFail($id);
        $samples = VAPSampleEntry::query()->where('lab_id', $labId)->where('customer_id', $customer->id);
        $summary = [
            'accepted_proposals' => VAPProposal::query()
                ->where('lab_id', $labId)
                ->where('customer_id', $customer->id)
                ->where('status', ProposalTrackingStatus::ACCEPTED->value)
                ->count(),
            'samples_in_progress' => (clone $samples)
                ->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA'])
                ->count(),
            'completed_samples' => (clone $samples)->where('status', 'COMPLETADO')->count(),
        ];

        return Inertia::render('Customers/Show', [
            'record' => CustomerResource::make($customer),
            'customerState' => [
                'summary' => $summary,
                'recent_samples' => (clone $samples)
                    ->orderByDesc('received_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['id', 'code', 'name', 'status', 'received_at', 'analysis_end_date'])
                    ->map(fn ($sample) => [
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
        abort_if(! auth()->user()->can('edit_customers'), 403, '');

        // Find the record
        $record = Customer::with('warehouses', 'category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Customers/Edit', [
            // 'record' => CustomerResource::make($record)
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'description' => $record->description,
                'warehouse_id' => $record->warehouse_id,
                'category_id' => $record->category ? [
                    'value' => $record->category->id,
                    'label' => $record->category->name,
                ] : null,
                'warehouses' => collect($record->warehouses)->map(function ($item) use ($record) {
                    return [
                        'id' => $item->id,
                        'customer_id' => [
                            'value' => $item->customer_id,
                            'label' => $record->name,
                        ],
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
    public function update(CustomerRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_customers'), 403, '');

        // dd($request->all());

        DB::transaction(function () use ($request, $id): void {

            $record = tap(Customer::findOrFail($id), function ($record) use ($request) {

                $record->update($request->validated());

            });

        });

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
    public function destroy()
    {
        abort_if(! auth()->user()->can('delete_customers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Customer::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_customers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Customer::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function changePrimaryWarehouse(Request $request, $id)
    {
        abort_if(! auth()->user()->can('edit_customers'), 403, '');

        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);
        $customer = Customer::findOrFail($id);
        abort_unless($customer->warehouses()->whereKey($validated['warehouse_id'])->exists(), 422, '');

        DB::transaction(fn () => $customer->update([
            'warehouse_id' => $validated['warehouse_id'],
        ]));

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function getCustomer()
    {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = Customer::query()
                ->select(['id', 'name', 'code', 'description'])
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }

    public function getTaxData()
    {
        return response()->json((new NIFIdentificationService)->getCustomerData(request()->tax_number));
    }

    public function taxIdentification()
    {
        abort_if(! auth()->user()->can('edit_customers'), 403, '');

        // Return Inertia View with record data
        return Inertia::render('Customers/TaxIdentification', []);
    }
}
