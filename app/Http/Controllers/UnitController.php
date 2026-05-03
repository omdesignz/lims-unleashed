<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitResource;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;
use Inertia\Inertia;

class UnitController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function indexPayload(bool $openCreate = false): array
    {
        return [
            'record' => UnitResource::collection(
                Unit::query()
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
                    'name' => trans('gestlab.general.labels.units.code'),
                    'value' => 'code',
                ],
                [
                    'name' => trans('gestlab.general.labels.units.description'),
                    'value' => 'description',
                ],
            ],
            'model' => Unit::MENU_NAME,
            'abilities' => method_exists(Unit::class, 'getAbilities') ? collect(Unit::ABILITIES)->map(function ($item) {
                return $item . '_' . Unit::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Unit::MENU_NAME;
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
        abort_if( !auth()->user()->can('view_units'), 403, '');

        return Inertia::render('Units/Index', $this->indexPayload());
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_units'), 403, '');

        return Inertia::render('Units/Index', $this->indexPayload(true));
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(UnitRequest $request)
    {
        abort_if( !auth()->user()->can('add_units'), 403, '');

        // Persiste data to DB
        Unit::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_units'), 403, '');

        // Find the record
        $record = Unit::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Units/Edit', [
            'record' => UnitResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(UnitRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_units'), 403, '');

        // Find the record
        $record = Unit::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_units'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Unit::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_units'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Unit::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getUnit() {
        $data = [];

        if (request()->filled('q')) {
            $search = request('q');

            $data = Unit::query()
                ->select(['id', 'code', 'description'])
                ->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                })
                ->limit(25)
                ->get();
        }

        return response()->json($data);
    }

}
