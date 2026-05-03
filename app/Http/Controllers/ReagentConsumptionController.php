<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReagentConsumptionRequest;
use App\Http\Resources\ReagentConsumptionResource;
use App\Models\Inventory;
use App\Models\ReagentConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Activitylog\Facades\Activity;
use Spatie\QueryBuilder\QueryBuilder;

class ReagentConsumptionController extends Controller
{
         /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_reagent_consumption'), 403, '');

        $records = QueryBuilder::for(ReagentConsumption::class)
                                ->with('reagent', 'user')
                                ->allowedFilters(ReagentConsumption::getAllowedFilters())
                                ->allowedSorts(ReagentConsumption::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10));


        return Inertia::render('ReagentConsumption/Index', [
            'record' => ReagentConsumptionResource::collection($records),
            'initialFilters' => request()->query('filter', ['reagent_name' => '', 'quantity_used' => '', 'remarks' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => false,  
            'trashedFilter' => false,
            // 'trashedOptions' => ReagentConsumption::getTrashedOptions(),
            'fields' => ReagentConsumption::getColumns(),
            'model' => ReagentConsumption::MENU_NAME,
            'abilities' => method_exists(ReagentConsumption::class, 'getAbilities') ? collect(ReagentConsumption::ABILITIES)->map(function($item){
                return $item . '_' . ReagentConsumption::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . ReagentConsumption::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy'])
        ]);
    }

    public function store(ReagentConsumptionRequest $request)
    {
        // $validated = $request->validate([
        //     'reagent_id'   => 'required|exists:inventory,id',
        //     'quantity_used' => 'required|numeric|min:0.01',
        //     'used_by'      => 'nullable|string',
        //     'used_at'      => 'required|date',
        //     'remarks'      => 'nullable|string',
        // ]);

        DB::transaction(function () use ($request) {
            $reagent = Inventory::findOrFail($request['reagent_id']);
            $reagent->deductStock($request['quantity_used']);

            ReagentConsumption::create($request->validated());
        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'consumptions' => 'required|array|min:1',
            'consumptions.*.reagent_id'   => 'required|exists:inventory,id',
            'consumptions.*.quantity_used' => 'required|numeric|min:0.01',
            'consumptions.*.used_by'      => 'nullable|string',
            'consumptions.*.used_at'      => 'required|date',
            'consumptions.*.remarks'      => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['consumptions'] as $consumption) {
                $reagent = Inventory::findOrFail($consumption['reagent_id']);
                $reagent->deductStock($consumption['quantity_used']);

                ReagentConsumption::create($consumption);
            }
        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function consumptionLogs($reagentId)
    {
        $logs = Activity::where('log_name', 'reagent_consumption')
            ->where('properties->reagent_id', $reagentId)
            ->latest()
            ->get();

        return response()->json($logs);
    }
}
