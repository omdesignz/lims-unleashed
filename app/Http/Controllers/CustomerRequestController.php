<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequestRequest;
use App\Http\Resources\CustomerRequestResource;
use App\Models\CustomerRequest;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerRequestController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $access, private readonly LaboratoryWorkflowMutationAccess $mutationAccess) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_customer_requests'), 403, '');

        return Inertia::render('CustomerRequests/Index', [
            'record' => CustomerRequestResource::collection(
                CustomerRequest::query()->forLaboratory($this->access->activeLabId())
                    ->with('category', 'customer', 'warehouse')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('description', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.customer_requests.description'),
                    'value' => 'description',
                ],
                [
                    'name' => trans('gestlab.general.labels.customer_requests.category_id'),
                    'value' => 'category',
                ],
                [
                    'name' => trans('gestlab.general.labels.customer_requests.customer_id'),
                    'value' => 'customer',
                ],
                [
                    'name' => trans('gestlab.general.labels.customer_requests.warehouse_id'),
                    'value' => 'warehouse',
                ],
            ],
            'model' => CustomerRequest::MENU_NAME,
            'abilities' => collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.CustomerRequest::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_customer_requests'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('CustomerRequests/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerRequestRequest $request)
    {
        abort_if(! auth()->user()->can('add_customer_requests'), 403, '');

        DB::transaction(function () use ($request): void {
            $labId = $this->access->activeLabId();
            $this->mutationAccess->operator($request->user()->id, $labId, 'add_customer_requests');
            $record = new CustomerRequest([...$request->validated(), 'lab_id' => $labId]);
            abort_unless($record->save(), 409);
        }, 3);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Registo guardado com êxito',
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
        abort_if(! auth()->user()->can('edit_customer_requests'), 403, '');

        // Find the record
        $record = CustomerRequest::query()->forLaboratory($this->access->activeLabId())->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('CustomerRequests/Edit', [
            'record' => CustomerRequestResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequestRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_customer_requests'), 403, '');

        DB::transaction(function () use ($request, $id): void {
            $labId = $this->access->activeLabId();
            $this->mutationAccess->operator($request->user()->id, $labId, 'edit_customer_requests');
            $record = CustomerRequest::query()->forLaboratory($labId)->lockForUpdate()->findOrFail($id);
            abort_unless($record->update($request->validated()), 409);
        }, 3);

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
        abort_if(! auth()->user()->can('delete_customer_requests'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'distinct'],
        ]);
        // Find and delete the record
        abort_if(request()->session()->has('impersonate'), 403);
        DB::transaction(function (): void {
            $labId = $this->access->activeLabId();
            $this->mutationAccess->operator(auth()->id(), $labId, 'delete_customer_requests');
            $records = CustomerRequest::query()->forLaboratory($labId)->withTrashed()->lockForUpdate()->findOrFail(request('recordIds'));
            foreach ($records as $record) {
                abort_unless($record->delete(), 409);
            }
        }, 3);

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
        abort_if(! auth()->user()->can('restore_customer_requests'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array', 'min:1', 'max:100'],
            'recordIds.*' => ['required', 'integer', 'distinct'],
        ]);
        // Find and restore the record
        abort_if(request()->session()->has('impersonate'), 403);
        DB::transaction(function (): void {
            $labId = $this->access->activeLabId();
            $this->mutationAccess->operator(auth()->id(), $labId, 'restore_customer_requests');
            $records = CustomerRequest::query()->forLaboratory($labId)->withTrashed()->lockForUpdate()->findOrFail(request('recordIds'));
            foreach ($records as $record) {
                abort_unless($record->restore(), 409);
            }
        }, 3);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getCustomerRequest()
    {
        abort_unless(auth()->user()->can('view_customer_requests'), 403);
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = CustomerRequest::query()->forLaboratory($this->access->activeLabId())
                ->select(['id', 'description'])
                ->where('description', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
