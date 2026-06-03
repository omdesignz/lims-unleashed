<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProtocolRequest;
use App\Http\Resources\ProtocolResource;
use App\Models\Protocol;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProtocolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_protocols'), 403, '');

        return Inertia::render('Protocols/Index', [
            'record' => ProtocolResource::collection(
                Protocol::query()
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
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.protocols.code'),
                    'value' => 'code',
                ],
                [
                    'name' => trans('gestlab.general.labels.protocols.description'),
                    'value' => 'description',
                ],
            ],
            'model' => Protocol::MENU_NAME,
            'abilities' => method_exists(Protocol::class, 'getAbilities') ? collect(Protocol::ABILITIES)->map(function ($item) {
                return $item.'_'.Protocol::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Protocol::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_protocols'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Protocols/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProtocolRequest $request)
    {
        abort_if(! auth()->user()->can('add_protocols'), 403, '');

        // Persiste data to DB
        Protocol::create($request->validated());

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
        abort_if(! auth()->user()->can('edit_protocols'), 403, '');

        // Find the record
        $record = Protocol::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Protocols/Edit', [
            'record' => ProtocolResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProtocolRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_protocols'), 403, '');

        // Find the record
        $record = Protocol::findOrFail($id);

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
        abort_if(! auth()->user()->can('delete_protocols'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (Protocol::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_protocols'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (Protocol::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getProtocol()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('protocols')
                ->select('protocols.*')
                ->where('description', 'LIKE', "%$search%")
                ->orWhere('code', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
