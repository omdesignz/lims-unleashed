<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryItemTransferRequest;
use App\Http\Resources\InventoryItemTransferResource;
use App\Models\InventoryItemTransfer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryItemTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_itransfers'), 403, '');

        return Inertia::render('InventoryItemTransfers/Index', [
            'record' => InventoryItemTransferResource::collection(
                InventoryItemTransfer::query()
                    ->with('item', 'from', 'to')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('qty', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.itransfers.item_id'),
                    'value' => 'item',
                ],
                [
                    'name' => trans('gestlab.general.labels.itransfers.qty'),
                    'value' => 'qty',
                ],
                [
                    'name' => trans('gestlab.general.labels.itransfers.source_id'),
                    'value' => 'source',
                ],
                [
                    'name' => trans('gestlab.general.labels.itransfers.sent_date'),
                    'value' => 'sent_date',
                ],
                [
                    'name' => trans('gestlab.general.labels.itransfers.destination_id'),
                    'value' => 'destination',
                ],
                [
                    'name' => trans('gestlab.general.labels.itransfers.received_date'),
                    'value' => 'received_date',
                ],
            ],
            'model' => InventoryItemTransfer::MENU_NAME,
            'abilities' => method_exists(InventoryItemTransfer::class, 'getAbilities') ? collect(InventoryItemTransfer::ABILITIES)->map(function ($item) {
                return $item.'_'.InventoryItemTransfer::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.InventoryItemTransfer::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_itransfers'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InventoryItemTransfers/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InventoryItemTransferRequest $request)
    {
        abort_if(! auth()->user()->can('add_itransfers'), 403, '');

        // Persiste data to DB
        InventoryItemTransfer::create($request->validated());

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
        abort_if(! auth()->user()->can('edit_itransfers'), 403, '');

        // Find the record
        $record = InventoryItemTransfer::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryItemTransfers/Edit', [
            'record' => InventoryItemTransferResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryItemTransferRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_itransfers'), 403, '');

        // Find the record
        $record = InventoryItemTransfer::findOrFail($id);

        $record->update($request->validated());

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
        abort_if(! auth()->user()->can('delete_itransfers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (InventoryItemTransfer::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_itransfers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (InventoryItemTransfer::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getInventoryItemTransfer()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('i_transfers')
                ->select('i_transfers.*')
                ->where('qty', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
