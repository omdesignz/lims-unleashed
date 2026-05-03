<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehousePasswordRequest;
use Illuminate\Http\Request;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use Illuminate\Support\Facades\DB;
use App\Models\CollectionProduct;
use App\Models\ContractGuide;
use App\Models\CreditNote;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\ExportCertificate;
use App\Models\ImportCertificate;
use App\Models\Invoice;
use App\Models\QualityCertificate;
use App\Models\Quote;
use App\Models\Receipt;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    private function buildStats(Warehouse $warehouse): array
    {
        $requestCounts = CustomerRequest::query()
            ->where('warehouse_id', $warehouse->id)
            ->get(['status', 'answered'])
            ->reduce(function (array $carry, CustomerRequest $request) {
                $status = $request->portal_status;

                $carry['total']++;

                if (in_array($status, ['completed', 'resolved'], true)) {
                    $carry['answered']++;
                } else {
                    $carry['pending']++;
                }

                return $carry;
            }, ['total' => 0, 'answered' => 0, 'pending' => 0]);

        return [
            'invoices' => [
                'total' => Invoice::query()->where('warehouse_id', $warehouse->id)->count(),
                'paid' => Invoice::query()->where('warehouse_id', $warehouse->id)->where('amount_due', '<=', 0)->count(),
                'pending' => Invoice::query()->where('warehouse_id', $warehouse->id)->where('amount_due', '>', 0)->count(),
            ],
            'collections' => [
                'total' => CollectionProduct::query()->where('warehouse_id', $warehouse->id)->count(),
                'processed' => CollectionProduct::query()->where('warehouse_id', $warehouse->id)->where('processed', true)->count(),
                'pending' => CollectionProduct::query()->where('warehouse_id', $warehouse->id)->where('processed', false)->count(),
            ],
            'quality_certificates' => [
                'total' => QualityCertificate::query()->where('warehouse_id', $warehouse->id)->count(),
                'validated' => QualityCertificate::query()->where('warehouse_id', $warehouse->id)->where('status', true)->count(),
            ],
            'requests' => $requestCounts,
            'quotes' => [
                'total' => Quote::query()->where('warehouse_id', $warehouse->id)->count(),
            ],
            'receipts' => [
                'total' => Receipt::query()->where('warehouse_id', $warehouse->id)->count(),
            ],
            'credit_notes' => [
                'total' => CreditNote::query()->where('warehouse_id', $warehouse->id)->count(),
            ],
            'contract_guides' => [
                'total' => ContractGuide::query()->where('warehouse_id', $warehouse->id)->count(),
            ],
            'imports' => [
                'total' => ImportCertificate::query()->where('importer_warehouse_id', $warehouse->id)->count(),
                'invoiced' => ImportCertificate::query()->where('importer_warehouse_id', $warehouse->id)->whereNotNull('invoice_id')->count(),
                'value' => (float) ImportCertificate::query()->where('importer_warehouse_id', $warehouse->id)->sum('cost_final'),
            ],
            'exports' => [
                'total' => ExportCertificate::query()->where('exporter_warehouse_id', $warehouse->id)->count(),
                'invoiced' => ExportCertificate::query()->where('exporter_warehouse_id', $warehouse->id)->whereNotNull('invoice_id')->count(),
                'value' => 0.0,
            ],
            'financial' => [
                'total_revenue' => (float) Invoice::query()->where('warehouse_id', $warehouse->id)->sum('total'),
                'paid' => (float) Invoice::query()->where('warehouse_id', $warehouse->id)->sum(DB::raw('GREATEST(total - amount_due, 0)')),
                'pending' => (float) Invoice::query()->where('warehouse_id', $warehouse->id)->sum('amount_due'),
                'credit_notes' => (float) CreditNote::query()->where('warehouse_id', $warehouse->id)->sum('total'),
            ],
        ];
    }

    private function buildCharts(array $stats): array
    {
        return [
            'account_health' => [
                'labels' => ['Faturas pagas', 'Faturas pendentes', 'Pedidos respondidos', 'Pedidos pendentes'],
                'series' => [
                    (int) data_get($stats, 'invoices.paid', 0),
                    (int) data_get($stats, 'invoices.pending', 0),
                    (int) data_get($stats, 'requests.answered', 0),
                    (int) data_get($stats, 'requests.pending', 0),
                ],
            ],
            'operations' => [
                'labels' => ['Colheitas processadas', 'Colheitas pendentes', 'Certificados validados', 'Certificados totais'],
                'series' => [
                    (int) data_get($stats, 'collections.processed', 0),
                    (int) data_get($stats, 'collections.pending', 0),
                    (int) data_get($stats, 'quality_certificates.validated', 0),
                    (int) data_get($stats, 'quality_certificates.total', 0),
                ],
            ],
            'documents' => [
                'labels' => ['Proformas', 'Recibos', 'Notas crédito', 'Guias', 'Importações', 'Exportações'],
                'series' => [
                    (int) data_get($stats, 'quotes.total', 0),
                    (int) data_get($stats, 'receipts.total', 0),
                    (int) data_get($stats, 'credit_notes.total', 0),
                    (int) data_get($stats, 'contract_guides.total', 0),
                    (int) data_get($stats, 'imports.total', 0),
                    (int) data_get($stats, 'exports.total', 0),
                ],
            ],
        ];
    }

    private function buildRecentActivity(Warehouse $warehouse): array
    {
        return collect([
            Invoice::query()->where('warehouse_id', $warehouse->id)->latest('date')->first()?->toArray()
                ? [
                    'id' => 'invoice-' . Invoice::query()->where('warehouse_id', $warehouse->id)->latest('date')->first()->id,
                    'type' => 'invoice',
                    'icon' => 'invoice',
                    'title' => 'Fatura atualizada',
                    'description' => 'Última fatura emitida para este armazém.',
                    'time' => optional(Invoice::query()->where('warehouse_id', $warehouse->id)->latest('date')->first()->date)->diffForHumans(),
                    'sort_at' => optional(Invoice::query()->where('warehouse_id', $warehouse->id)->latest('date')->first()->date)?->timestamp,
                ]
                : null,
            CollectionProduct::query()->where('warehouse_id', $warehouse->id)->latest('collection_date')->first()
                ? [
                    'id' => 'collection-' . CollectionProduct::query()->where('warehouse_id', $warehouse->id)->latest('collection_date')->first()->id,
                    'type' => 'collection',
                    'icon' => 'collection',
                    'title' => 'Colheita movimentada',
                    'description' => 'Registo mais recente de colheita associado ao armazém.',
                    'time' => optional(CollectionProduct::query()->where('warehouse_id', $warehouse->id)->latest('collection_date')->first()->collection_date)->diffForHumans(),
                    'sort_at' => optional(CollectionProduct::query()->where('warehouse_id', $warehouse->id)->latest('collection_date')->first()->collection_date)?->timestamp,
                ]
                : null,
            QualityCertificate::query()->where('warehouse_id', $warehouse->id)->latest('validated_at')->first()
                ? [
                    'id' => 'certificate-' . QualityCertificate::query()->where('warehouse_id', $warehouse->id)->latest('validated_at')->first()->id,
                    'type' => 'certificate',
                    'icon' => 'certificate',
                    'title' => 'Certificado emitido',
                    'description' => 'Último certificado ligado a este armazém.',
                    'time' => optional(QualityCertificate::query()->where('warehouse_id', $warehouse->id)->latest('validated_at')->first()->validated_at ?? QualityCertificate::query()->where('warehouse_id', $warehouse->id)->latest('created_at')->first()?->created_at)?->diffForHumans(),
                    'sort_at' => optional(QualityCertificate::query()->where('warehouse_id', $warehouse->id)->latest('validated_at')->first()->validated_at ?? QualityCertificate::query()->where('warehouse_id', $warehouse->id)->latest('created_at')->first()?->created_at)?->timestamp,
                ]
                : null,
            CustomerRequest::query()->where('warehouse_id', $warehouse->id)->latest('submitted_at')->first()
                ? [
                    'id' => 'request-' . CustomerRequest::query()->where('warehouse_id', $warehouse->id)->latest('submitted_at')->first()->id,
                    'type' => 'request',
                    'icon' => 'request',
                    'title' => 'Pedido do portal atualizado',
                    'description' => 'Interação mais recente do portal do cliente para este armazém.',
                    'time' => optional(CustomerRequest::query()->where('warehouse_id', $warehouse->id)->latest('submitted_at')->first()->submitted_at ?? CustomerRequest::query()->where('warehouse_id', $warehouse->id)->latest('created_at')->first()?->created_at)?->diffForHumans(),
                    'sort_at' => optional(CustomerRequest::query()->where('warehouse_id', $warehouse->id)->latest('submitted_at')->first()->submitted_at ?? CustomerRequest::query()->where('warehouse_id', $warehouse->id)->latest('created_at')->first()?->created_at)?->timestamp,
                ]
                : null,
        ])
            ->filter()
            ->sortByDesc('sort_at')
            ->take(6)
            ->map(fn (array $activity) => collect($activity)->except('sort_at')->all())
            ->values()
            ->all();
    }

     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_warehouses'), 403, '');

        return Inertia::render('Warehouses/Index', [
            'record' => WarehouseResource::collection(
                Warehouse::query()->with('customer.category',)
                            ->when(request()->input('search'), function($query, $search){
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
                    'value' => 'customer'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.primary_phone'),
                    'value' => 'primary_phone'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.email'),
                    'value' => 'email'
                ],
                [
                    'name' => trans('gestlab.general.labels.customers.category_id'),
                    'value' => 'customer_category'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point'),
                    'value' => 'focal_point'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_contact'),
                    'value' => 'focal_point_contact'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.focal_point_email'),
                    'value' => 'focal_point_email'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.warehouses.address'),
                    'value' => 'address'
                ],
                
                
            ],
            'model' => Warehouse::MENU_NAME,
            'abilities' => method_exists(Warehouse::class, 'getAbilities') ? collect(Warehouse::ABILITIES)->map(function($item){
                return $item . '_' . Warehouse::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Warehouse::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_warehouses'), 403, '');

        return Inertia::render('Warehouses/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(WarehouseRequest $request)
    {
        abort_if( !auth()->user()->can('add_warehouses'), 403, '');


        DB::transaction(function () use ($request): void {
            $warehouse = Warehouse::create($request->validated());
        });

        

        return redirect()->back()->with([
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
        $warehouse = Warehouse::query()
            ->with('customer.category')
            ->findOrFail($id);
        $stats = $this->buildStats($warehouse);

        return Inertia::render('Warehouses/Show', [
            'record' => WarehouseResource::make($warehouse),
            'stats' => $stats,
            'charts' => $this->buildCharts($stats),
            'recentActivity' => $this->buildRecentActivity($warehouse),
            'auth' => [
                'user' => auth()->user(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_warehouses'), 403, '');

        // Find the record
        $record = Warehouse::with('warehouses', 'category')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Warehouses/Edit', [
            // 'record' => WarehouseResource::make($record)
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'description' => $record->description,
                'category_id' => [
                    'value' => $record->category->id,
                    'label' => $record->category->code,
                ],
                'warehouses' => collect($record->warehouses)->map(function($item) {
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
                })
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(WarehouseRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_warehouses'), 403, '');


        // dd($request->all());

        DB::transaction(function () use ($request, $id): void {

            Warehouse::findOrFail($id)->update($request->validated());

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function setPass(WarehousePasswordRequest $request, $id)
    {

        DB::transaction(function () use ($request, $id): void {

            Warehouse::findOrFail($id)->forceFill([
                'password' => Hash::make($request->password)
            ])->save();

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
        abort_if( !auth()->user()->can('delete_warehouses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Warehouse::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if( !auth()->user()->can('restore_warehouses'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Warehouse::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getWarehouse() {
        $data = [];

        if( !is_null(request()->q)){
            $search = request()->q;
            $customer_id = request()->customer_id;
            
            $data = DB::table("warehouses")
                ->select('warehouses.*')
                ->where('customer_id', '=', $customer_id)
                ->where('address','LIKE',"%$search%")
                ->where('name','LIKE',"%$search%")
                ->limit(5)
                ->get();
        } else {

            $data = DB::table("warehouses")
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
