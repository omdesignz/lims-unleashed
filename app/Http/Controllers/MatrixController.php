<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\MatrixRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\MatrixResource;
use Illuminate\Support\Facades\DB;
use App\Models\Matrix;
use App\Models\AnalysisCategory;
use App\Models\Profile;
use App\Models\MatrixProfile;
use Inertia\Inertia;

class MatrixController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_matrixes'), 403, '');

        return Inertia::render('Matrixes/Index', [
            'record' => MatrixResource::collection(
                Matrix::query()
                            ->when(request()->input('search'), function($query, $search){
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
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.matrixes.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.matrixes.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.matrixes.price'),
                    'value' => 'price'
                ],
                // [
                //     'name' => trans('gestlab.general.labels.matrixes.fixed_price'),
                //     'value' => 'fixed_price'
                // ],
            ],
            'model' => Matrix::MENU_NAME,
            'abilities' => method_exists(Matrix::class, 'getAbilities') ? collect(Matrix::ABILITIES)->map(function($item){
                return $item . '_' . Matrix::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Matrix::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_matrixes'), 403, '');

        return Inertia::render('Matrixes/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(MatrixRequest $request)
    {
        abort_if( !auth()->user()->can('add_matrixes'), 403, '');

        DB::transaction(function () use ($request): void {
            $matrix = Matrix::create($request->safe()->except(['profiles']));

            // Structure profiles
            $keyed = collect($request->profiles)->mapWithKeys(function (array $item, int $key) {
                return [$item['profile_id'] => $item];
            });

            $matrix->profiles()->attach($keyed);
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
        $record = Matrix::with('profiles')->findOrFail($id);

        return Inertia::render('Matrixes/Show', [
            'record' => MatrixResource::make($record)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_matrixes'), 403, '');

        // Find the record
        $record = Matrix::with([
            'profiles.type.department',
            'profiles.parameters',
            'exemption',
            'tax_category',
        ])->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Matrixes/Edit', [
            'record' => [
                'id' => $record->id,
                'code' => $record->code,
                'description' => $record->description,
                'price' => $record->price_based_on_profiles,
                'fixed_price' => $record->fixed_price,
                'tax_id' => [
                    'value' => $record->tax_id,
                    'percent' => $record->tax_percentage,
                    'label' => $record?->tax_category?->name,
                ],
                'exemption_id' => [
                    'value' => $record?->exemption_id,
                    'label' => $record?->exemption?->code,
                ],
                'exemption_code' => $record->exemption_code,
                'charge_tax' => $record->charge_tax,
                'tax_percentage' => $record->tax_percentage,
                'profiles' => collect($record->profiles)->map(function($item) { 
                    return [
                        'profile' => $item->name,
                        'profile_id' => [
                            'value' => $item->id,
                            'label' => $item->name,
                            'code' => $item->code,
                            'department_id' => $item->type?->department_id,
                            'department_name' => $item->type?->department?->name,
                            'category_name' => $item->type?->name,
                            'active_parameter_count' => $item->parameters->where('active', true)->count(),
                            'total_parameter_count' => $item->parameters->count(),
                        ],
                        'profile' => $item->pivot->profile,
                        'price' => $item->price,
                    ];
                })
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(MatrixRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_matrixes'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            $record = tap(Matrix::findOrFail($id), function($record) use($request) {

                $record->update($request->safe()->except(['profiles']));
    
            });

            if($request->profiles) {
                MatrixProfile::where('matrix_id', $id)->whereNotIn('profile_id', collect($request->profiles)->pluck('profile_id')->toArray() )->delete();
            }    
            // Structure profiles to Update
            $keyed = collect($request->profiles)->mapWithKeys(function (array $item, int $key) {
                return [$item['profile_id'] => $item];
            });

            // $record->profiles()->updateExistingPivot($soft);
            $record->profiles()->syncWithoutDetaching($keyed);

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
        abort_if( !auth()->user()->can('delete_matrixes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Matrix::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_matrixes'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Matrix::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getMatrix() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("matrixes")
                ->select('matrixes.*')
                ->where('description','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
