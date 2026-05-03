<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormulaRequest;
use App\Http\Resources\FormulaResource;
use Illuminate\Support\Facades\DB;
use App\Models\Formula;
use Inertia\Inertia;

class FormulaController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_formulas'), 403, '');

        return Inertia::render('Formulas/Index', [
            'record' => FormulaResource::collection(
                Formula::query()
                    ->withCount('parameters')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(function ($searchQuery) use ($search) {
                            $searchQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('category', 'like', "%{$search}%");
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
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.formulas.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.formulas.expression'),
                    'value' => 'expression'
                ],
            ],
            'model' => Formula::MENU_NAME,
            'abilities' => method_exists(Formula::class, 'getAbilities') ? collect(Formula::ABILITIES)->map(function ($item) {
                return $item . '_' . Formula::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Formula::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter'])
        ]);
    }

    public function mb()
    {
        return Inertia::render('Formulas/IndexMB', []);
    }    

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_formulas'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Formulas/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    // public function store(FormulaRequest $request)
    // {
    //     abort_if(!auth()->user()->can('add_countries'), 403, '');

    //     // Persiste data to DB

    //     DB::transaction(function () use ($request): void {
    //         $record = Formula::create($request->safe()->except(['variables']));

    //         foreach (collect($request->safe()->only(['variables']))->first() as $item) {

    //             $obj = new Variable;

    //             $obj->formula_id = $record->id;
    //             $obj->name = $item['name'];
    //             $obj->value = $item['value'];

    //             $obj->save();
    //         }
    //     });

    //     return redirect()->back()->with([
    //         'toast' => [
    //             'title' => trans('gestlab.toasts.notification'),
    //             'message' => trans('gestlab.toasts.record_successfully_created'),
    //         ]
    //     ]);
    // }

    public function store(FormulaRequest $request)
    {
        abort_if(!auth()->user()->can('add_formulas'), 403, '');

        DB::transaction(function () use ($request): void {
            Formula::query()->create(array_merge(
                $request->validated(),
                ['created_by' => auth()->id()]
            ));
        });

        return to_route('formulas.index')->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
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
    public function edit(Formula $formula)
    {
        abort_if(!auth()->user()->can('edit_formulas'), 403, '');

        return Inertia::render('Formulas/Edit', [
            'record' => FormulaResource::make($formula->load('parameters')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(FormulaRequest $request, Formula $formula)
    {
        abort_if(!auth()->user()->can('edit_formulas'), 403, '');

        DB::transaction(function () use ($request, $formula): void {
            $formula->update($request->validated());
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
        abort_if(!auth()->user()->can('delete_formulas'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);

        // Find and delete the record
        foreach (Formula::findOrFail(request('recordIds')) as $record) {
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
        abort_if(!auth()->user()->can('restore_formulas'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Formula::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }


    public function getFormula()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table("formulas")
                ->select('formulas.*')
                ->where('name', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function validateFormula(Request $request, AdvancedCalculationEngine $calculator)
    {
        $request->validate([
            'formula' => 'required|string'
        ]);

        $validation = $calculator->validateFormula($request->formula);

        return response()->json($validation);
    }

    public function testFormula(Request $request, AdvancedCalculationEngine $calculator)
    {
        $request->validate([
            'formula' => 'required|string',
            'variables' => 'required|array'
        ]);

        try {
            $result = $calculator->evaluateFormula(
                $request->formula,
                $request->variables
            );

            return response()->json([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
