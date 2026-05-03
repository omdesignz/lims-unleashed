<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\VariableRequest;
use App\Http\Resources\VariableResource;
use Illuminate\Support\Facades\DB;
use App\Models\Formula;
use App\Models\Variable;
use Inertia\Inertia;

class VariableController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_variables'), 403, '');

        return Inertia::render('Variables/Index', [
            'record' => VariableResource::collection(
                Variable::query()
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('value', 'like', "%{$search}%");
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->with('formula:id,name')
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.countries.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.variables.value'),
                    'value' => 'value'
                ],
                [
                    'name' => trans('gestlab.general.labels.variables.formula_id'),
                    'value' => 'formula'
                ],
            ],
            'model' => Variable::MENU_NAME,
            'abilities' => method_exists(Variable::class, 'getAbilities') ? collect(Variable::ABILITIES)->map(function ($item) {
                return $item . '_' . Variable::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Variable::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter']),
            'formulas' => Formula::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Formula $formula) => [
                    'value' => $formula->id,
                    'label' => $formula->name,
                ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_variables'), 403, '');

        return to_route('variables.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(VariableRequest $request)
    {
        abort_if(!auth()->user()->can('add_variables'), 403, '');

        // Persiste data to DB
        Variable::create($request->validated());

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
        abort_if(!auth()->user()->can('edit_variables'), 403, '');

        return to_route('variables.index');
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(VariableRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_variables'), 403, '');

        // Find the record
        $record = Variable::findOrFail($id);

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
        abort_if(!auth()->user()->can('delete_variables'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Variable::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(!auth()->user()->can('restore_variables'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Variable::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }


    public function getVariable()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('variables')
                ->select('variables.*')
                ->where('name', 'LIKE', "%{$search}%")
                ->get();
        }

        return response()->json($data);
    }
}
