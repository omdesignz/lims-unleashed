<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sample;
use App\Http\Resources\SampleResource;
use App\Models\Parameter;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class SampleController extends Controller
{

         /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_samples'), 403, '');

        // dd(request()->parameters);

        $records = QueryBuilder::for(Sample::class)
                                ->with('collection', 'analysis.profile.parameters')
                                ->when(request()->parameters, function($query) {
                                    $query->whereHas('analysis', function($q)  {
                                        $q->whereHas('profile', function($q)  {
                                            $q->whereHas('parameters', function($q)  {
                                                $q->whereIn('parameter_id', request()->parameters);
                                            });
                                        });
                                    });
                                })
                                ->allowedFilters(Sample::getAllowedFilters())
                                ->allowedSorts(Sample::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10));


        return Inertia::render('Samples/Index', [
            'record' => SampleResource::collection($records),
            'parameters' => [],
            'initialFilters' => request()->query('filter', ['collection.code' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => true,  
            'trashedFilter' => true,
            'trashedOptions' => Sample::getTrashedOptions(),
            'fields' => Sample::getColumns(),
            'model' => Sample::MENU_NAME,
            'abilities' => method_exists(Sample::class, 'getAbilities') ? collect(Sample::ABILITIES)->map(function($item){
                return $item . '_' . Sample::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Sample::MENU_NAME;
            }),                           
            // 'query' => request()->only(['search', 'trashed', 'date', 'orderBy'])
        ]);
    }

    public function getCode() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("samples")
                ->select('samples.*')
                ->where('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
