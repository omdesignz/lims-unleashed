<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Support\Facades\DB;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerRequest as PortalCustomerRequest;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\QualityCertificate;
use App\Models\Receipt;
use App\Models\VAPSampleEntry;
use App\Models\Warehouse;
use App\Services\NIFIdentificationService;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class CustomerController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_customers'), 403, '');

        return Inertia::render('Customers/Index', [
            'record' => CustomerResource::collection(
                Customer::query()
                            ->with('category', 'main_warehouse')
                            ->when(request()->input('search'), function($query, $search){
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
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.customers.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.customers.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.customers.category_id'),
                    'value' => 'category'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point'),
                    'value' => 'warehouse.focal_point'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_contact'),
                    'value' => 'warehouse.focal_point_contact'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_email'),
                    'value' => 'warehouse.focal_point_email'
                ],
            ],
            'model' => Customer::MENU_NAME,
            'abilities' => method_exists(Customer::class, 'getAbilities') ? collect(Customer::ABILITIES)->map(function($item){
                return $item . '_' . Customer::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Customer::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_customers'), 403, '');

        return Inertia::render('Customers/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(CustomerRequest $request)
    {
        abort_if( !auth()->user()->can('add_customers'), 403, '');

        $record = collect();

        DB::transaction(function () use ($request, $record): void {
            $data = Customer::create($request->validated());

            $record->push($data);
        });

        
        return to_route('customers.edit', ['customer' => $record->first()->id])->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);


    }

    /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        $customer = Customer::query()
            ->with('category', 'main_warehouse', 'warehouses')
            ->findOrFail($id);
        $summary = [
            'accepted_proposals' => Proposal::query()->where('customer_id', $customer->id)->accepted()->count(),
            'open_invoices' => Invoice::query()->where('customer_id', $customer->id)->where('amount_due', '>', 0)->count(),
            'open_amount_due' => (float) Invoice::query()->where('customer_id', $customer->id)->sum('amount_due'),
            'samples_in_progress' => VAPSampleEntry::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['POR_INICIAR', 'EN_PROGRESO', 'EN_PAUSA'])
                ->count(),
            'completed_samples' => VAPSampleEntry::query()->where('customer_id', $customer->id)->where('status', 'COMPLETADO')->count(),
            'open_requests' => PortalCustomerRequest::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
            'certificates' => QualityCertificate::query()->where('customer_id', $customer->id)->count(),
            'receipts' => Receipt::query()->where('customer_id', $customer->id)->count(),
            'credit_notes' => CreditNote::query()->where('customer_id', $customer->id)->count(),
        ];
        $invoiceColumns = ['id', 'inv_no', 'total', 'amount_due', 'date'];

        if (Schema::hasColumn('invoices', 'due_date')) {
            $invoiceColumns[] = 'due_date';
        }

        return Inertia::render('Customers/Show', [
            'record' => CustomerResource::make($customer),
            'charts' => [
                'commercial_health' => [
                    'labels' => ['Propostas aceites', 'Faturas em aberto', 'Pedidos do portal', 'Notas de crédito'],
                    'series' => [
                        (int) ($summary['accepted_proposals'] ?? 0),
                        (int) ($summary['open_invoices'] ?? 0),
                        (int) ($summary['open_requests'] ?? 0),
                        (int) ($summary['credit_notes'] ?? 0),
                    ],
                ],
                'execution_mix' => [
                    'labels' => ['Amostras em curso', 'Amostras concluídas', 'Certificados', 'Recibos'],
                    'series' => [
                        (int) ($summary['samples_in_progress'] ?? 0),
                        (int) ($summary['completed_samples'] ?? 0),
                        (int) ($summary['certificates'] ?? 0),
                        (int) ($summary['receipts'] ?? 0),
                    ],
                ],
                'financial_pressure' => [
                    'labels' => ['Montante em aberto', 'Notas de crédito'],
                    'series' => [
                        (float) ($summary['open_amount_due'] ?? 0),
                        (float) CreditNote::query()->where('customer_id', $customer->id)->sum('total'),
                    ],
                ],
            ],
            'customerState' => [
                'summary' => $summary,
                'recent_samples' => VAPSampleEntry::query()
                    ->where('customer_id', $customer->id)
                    ->latest('received_at')
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
                'open_finance' => Invoice::query()
                    ->where('customer_id', $customer->id)
                    ->where('amount_due', '>', 0)
                    ->latest('date')
                    ->limit(5)
                    ->get($invoiceColumns)
                    ->map(fn ($invoice) => [
                        'id' => $invoice->id,
                        'reference' => $invoice->inv_no,
                        'total' => (float) $invoice->total,
                        'amount_due' => (float) $invoice->amount_due,
                        'date' => optional($invoice->date)?->toDateString(),
                        'due_date' => optional($invoice->due_date)?->toDateString(),
                    ]),
                'recent_requests' => PortalCustomerRequest::query()
                    ->where('customer_id', $customer->id)
                    ->latest('submitted_at')
                    ->limit(5)
                    ->get(['id', 'reference', 'title', 'request_type', 'status', 'submitted_at'])
                    ->map(fn ($request) => [
                        'id' => $request->id,
                        'reference' => $request->reference,
                        'title' => $request->title,
                        'request_type' => $request->request_type,
                        'status' => $request->portal_status,
                        'submitted_at' => optional($request->submitted_at)?->toIso8601String(),
                    ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_customers'), 403, '');

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
                'category_id' => [
                    'value' => $record->category->id,
                    'label' => $record->category->name,
                ],
                'warehouses' => collect($record->warehouses)->map(function($item) use($record) {
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
                })
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(CustomerRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_customers'), 403, '');

        // dd($request->all());

        DB::transaction(function () use ($request, $id): void {

            $record = tap(Customer::findOrFail($id), function($record) use($request) {

                $record->update($request->validated());
    
            });

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        abort_if( !auth()->user()->can('delete_customers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Customer::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if( !auth()->user()->can('restore_customers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Customer::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function changePrimaryWarehouse(Request $request, $id)
    {
        // dd($request->warehouse_id);
        DB::transaction(function () use ($request, $id): void {

            $record = tap(Customer::findOrFail($id), function($record) use($request) {

                $record->update([
                    'warehouse_id' => $request->warehouse_id ?? null,
                ]);
        
                });

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }


    public function getCustomer() {
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

    public function getTaxData(){
        return response()->json((new NIFIdentificationService)->getCustomerData(request()->tax_number));
    }

    public function taxIdentification()
    {
        abort_if( !auth()->user()->can('edit_customers'), 403, '');


        // Return Inertia View with record data
        return Inertia::render('Customers/TaxIdentification',[]);
    }
}
