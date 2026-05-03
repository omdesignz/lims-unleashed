<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\ParameterResource;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;
use App\Models\AnalysisCategory;
use App\Models\Parameter;
use App\Models\ParameterProfile;
use Inertia\Inertia;

class ProfileController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_profiles'), 403, '');

        return Inertia::render('Profiles/Index', [
            'record' => ProfileResource::collection(
                Profile::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter === 'trashed'){
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
                    'name' => trans('gestlab.general.labels.profiles.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.profiles.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.profiles.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.profiles.category_id_1'),
                    'value' => 'category'
                ],
            ],
            'model' => Profile::MENU_NAME,
            'abilities' => method_exists(Profile::class, 'getAbilities') ? collect(Profile::ABILITIES)->map(function($item){
                return $item . '_' . Profile::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Profile::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_profiles'), 403, '');

        return Inertia::render('Profiles/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ProfileRequest $request)
    {
        abort_if( !auth()->user()->can('add_profiles'), 403, '');

        // dd($request->all());


        DB::transaction(function () use ($request): void {
            $profile = Profile::create($request->safe()->except(['parameters']));

            // Structure parameters
            $keyed = collect($request->validated('parameters'))->mapWithKeys(function (array $item, int $key) {
                return [$item['parameter_id'] => $item];
            });

            $profile->parameters()->attach($keyed);
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
        $record = Profile::with('parameters.pivot', 'type')->findOrFail($id);

        return Inertia::render('Profiles/Show', [
            'record' => ProfileResource::make($record)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_profiles'), 403, '');

        // Find the record
        $record = Profile::with(['parameters', 'type.department'])->findOrFail($id);

        // dd($record->price_based_on_parameters);

        // Return Inertia View with record data
        return Inertia::render('Profiles/Edit', [
            // 'record' => ProfileResource::make($record)
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'code' => $record->code,
                'description' => $record->description,
                'price' => $record->price_based_on_parameters ?? 0,
                'category_id' => [
                    'value' => $record->type->id,
                    'label' => $record->type->code,
                    'name' => $record->type->name,
                    'department_id' => $record->type->department_id,
                    'department_name' => $record->type?->department?->name,
                ],
                'parameters' => collect($record->parameters)->map(function($item) { 
                    return [
                        'parameter' => $item->name,
                        'parameter_id' => [
                            'value' => $item->id,
                            'label' => $item->name,
                            'code' => $item->code,
                            'active' => (bool) $item->active,
                        ],
                        'min_ref_value' => $item->pivot->min_ref_value,
                        'max_ref_value' => $item->pivot->max_ref_value,
                        'extra_data' => json_decode($item->pivot->extra_data ?? '[]', true),
                        'optimal_analysis_time' => $item->pivot->optimal_analysis_time ?? null,
                        'dilutions' => $item->pivot->dilutions,
                        'count' => $item->pivot->count,
                        'ref_val_origin' => $item->pivot->ref_val_origin,
                        'unit_label' => $item->pivot->unit_label,
                        'unit_id' => [
                            'value' => $item->pivot->unit_id,
                            'label' => $item->pivot->unit_label,
                        ],
                        'protocol_label' => $item->pivot->protocol_label,
                        'protocol_id' => [
                            'value' => $item->pivot->protocol_id,
                            'label' => $item->pivot->protocol_label,
                        ],
                        'standard_label' => $item->pivot->standard_label,
                        'standard_id' => [
                            'value' => $item->pivot->standard_id,
                            'label' => $item->pivot->standard_label,
                        ],
                        'nwp_label' => $item->pivot->nwp_label,
                        'nwp_id' => [
                            'value' => $item->pivot->nwp_id,
                            'label' => $item->pivot->nwp_label,
                        ],
                        'category_label' => $item->pivot->category_label,
                        'category_id' => [
                            'value' => $item->pivot->category_id,
                            'label' => $item->pivot->category_label,
                        ],
                        'formula_label' => $item->pivot->formula_label,
                        'formula_id' => [
                            'value' => $item->pivot->formula_id,
                            'label' => $item->pivot->formula_label,
                        ],
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
    public function update(ProfileRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_profiles'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            $record = tap(Profile::findOrFail($id), function($record) use($request) {

                $record->update($request->safe()->except(['parameters']));
    
            });

            if($request->parameters) {
                ParameterProfile::where('profile_id', $id)->whereNotIn('parameter_id', collect($request->parameters)->pluck('parameter_id')->toArray() )->delete();
            }    
            // Structure parameters to Update
            $keyed = collect($request->parameters)->mapWithKeys(function (array $item, int $key) {
                return [$item['parameter_id'] => $item];
            });

            // $record->parameters()->updateExistingPivot($soft);
            $record->parameters()->syncWithoutDetaching($keyed);

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
        abort_if( !auth()->user()->can('delete_profiles'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Profile::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_profiles'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Profile::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    // public function getProfile() {
    //     $data = [];

    //     if(request()->has('q')){
    //         $search = request()->q;
            
    //         $data = DB::table("profiles")
    //             ->select('profiles.*')
    //             ->where('name','LIKE',"%$search%")
    //             ->orWhere('code','LIKE',"%$search%")
    //             ->get();
    //     }

    //     return response()->json($data); 
    // }

    public function getProfile() 
    {
        // Subquery to calculate total price per profile
        $parameterPrices = DB::table('parameter_profile')
            ->select('parameter_profile.profile_id')
            ->selectRaw('SUM(parameters.price * parameter_profile.count) as total_price')
            ->join('parameters', function($join) {
                $join->on('parameter_profile.parameter_id', '=', 'parameters.id')
                    ->whereNull('parameters.deleted_at')
                    ->where('parameters.active', 1);  // Only active parameters
            })
            ->whereNull('parameter_profile.deleted_at')
            ->groupBy('parameter_profile.profile_id');

        $parameterCounts = DB::table('parameter_profile')
            ->select('parameter_profile.profile_id')
            ->selectRaw('COUNT(DISTINCT parameter_profile.parameter_id) as total_parameter_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN parameters.active = 1 AND parameters.deleted_at IS NULL THEN parameter_profile.parameter_id END) as active_parameter_count')
            ->leftJoin('parameters', 'parameters.id', '=', 'parameter_profile.parameter_id')
            ->whereNull('parameter_profile.deleted_at')
            ->groupBy('parameter_profile.profile_id');

        // Main query
        $query = DB::table('profiles')
            ->select('profiles.*')
            ->selectRaw('COALESCE(param_prices.total_price, 0.00) as parameters_price')
            ->selectRaw('COALESCE(param_counts.total_parameter_count, 0) as total_parameter_count')
            ->selectRaw('COALESCE(param_counts.active_parameter_count, 0) as active_parameter_count')
            ->addSelect([
                'analysis_categories.department_id',
                'analysis_categories.name as category_name',
                'analysis_categories.code as category_code',
                'departments.name as department_name',
            ])
            ->leftJoinSub($parameterPrices, 'param_prices', function($join) {
                $join->on('profiles.id', '=', 'param_prices.profile_id');
            })
            ->leftJoinSub($parameterCounts, 'param_counts', function($join) {
                $join->on('profiles.id', '=', 'param_counts.profile_id');
            });

        $query
            ->leftJoin('analysis_categories', 'analysis_categories.id', '=', 'profiles.category_id')
            ->leftJoin('departments', 'departments.id', '=', 'analysis_categories.department_id');

        // Search functionality
        if(request()->has('q')) {
            $search = request()->q;
            $query->where(function($q) use ($search) {
                $q->where('profiles.name', 'LIKE', "%{$search}%")
                ->orWhere('profiles.code', 'LIKE', "%{$search}%");
            });
        }

        // Optional: exclude soft deleted profiles
        $query->whereNull('profiles.deleted_at');

        return response()->json($query->get());
    }
}
