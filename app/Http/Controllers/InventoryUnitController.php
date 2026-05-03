<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InventoryUnitRequest;
use App\Http\Resources\InventoryUnitResource;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryUnit;
use Inertia\Inertia;

class InventoryUnitController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function indexPayload(bool $openCreate = false): array
    {
        return [
            'record' => InventoryUnitResource::collection(
                InventoryUnit::query()
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('code', 'like', "%{$search}%");
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
            'openCreate' => $openCreate,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.iunits.code'),
                    'value' => 'code',
                ],
                [
                    'name' => trans('gestlab.general.labels.iunits.description'),
                    'value' => 'description',
                ],
            ],
            'model' => InventoryUnit::MENU_NAME,
            'abilities' => method_exists(InventoryUnit::class, 'getAbilities') ? collect(InventoryUnit::ABILITIES)->map(function ($item) {
                return $item . '_' . InventoryUnit::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . InventoryUnit::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ];
    }
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_iunits'), 403, '');

        return Inertia::render('InventoryUnits/Index', $this->indexPayload());
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_iunits'), 403, '');

        return Inertia::render('InventoryUnits/Index', $this->indexPayload(true));
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InventoryUnitRequest $request)
    {
        abort_if( !auth()->user()->can('add_iunits'), 403, '');

        // Persiste data to DB
        InventoryUnit::create($request->validated());

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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_iunits'), 403, '');

        // Find the record
        $record = InventoryUnit::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InventoryUnits/Edit', [
            'record' => InventoryUnitResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InventoryUnitRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_iunits'), 403, '');

        // Find the record
        $record = InventoryUnit::findOrFail($id);

        $record->update($request->validated());

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
        abort_if( !auth()->user()->can('delete_iunits'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InventoryUnit::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_iunits'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InventoryUnit::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInventoryUnit() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("i_units")
                ->select('i_units.*')
                ->where('description','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
