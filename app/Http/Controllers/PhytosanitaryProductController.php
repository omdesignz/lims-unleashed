<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PhytosanitaryProductRequest;
use App\Http\Resources\PhytosanitaryProductResource;
use App\Models\PhytosanitaryProduct;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PhytosanitaryProductController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function indexPayload(bool $openCreate = false): array
    {
        return [
            'record' => PhytosanitaryProductResource::collection(
                PhytosanitaryProduct::query()
                    ->when(request()->input('search'), function ($query, $search) {
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
            'slideOverEdit' => true,
            'openCreate' => $openCreate,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.phytosanitary_products.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.phytosanitary_products.description'),
                    'value' => 'description',
                ],
            ],
            'model' => PhytosanitaryProduct::MENU_NAME,
            'abilities' => method_exists(PhytosanitaryProduct::class, 'getAbilities') ? collect(PhytosanitaryProduct::ABILITIES)->map(function ($item) {
                return $item . '_' . PhytosanitaryProduct::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . PhytosanitaryProduct::MENU_NAME;
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
        abort_if( !auth()->user()->can('view_phytosanitary_products'), 403, '');

        return Inertia::render('PhytosanitaryProducts/Index', $this->indexPayload());
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_phytosanitary_products'), 403, '');

        return Inertia::render('PhytosanitaryProducts/Index', $this->indexPayload(true));
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(PhytosanitaryProductRequest $request)
    {
        abort_if( !auth()->user()->can('add_phytosanitary_products'), 403, '');

        // Persiste data to DB
        PhytosanitaryProduct::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_phytosanitary_products'), 403, '');

        // Find the record
        $record = PhytosanitaryProduct::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('PhytosanitaryProducts/Edit', [
            'record' => PhytosanitaryProductResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(PhytosanitaryProductRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_phytosanitary_products'), 403, '');

        // Find the record
        $record = PhytosanitaryProduct::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_phytosanitary_products'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (PhytosanitaryProduct::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_phytosanitary_products'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (PhytosanitaryProduct::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getPhytosanitaryProduct() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("phytosanitary_products")
                ->select('phytosanitary_products.*')
                ->where('description','LIKE',"%$search%")
                ->orWhere('name','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
