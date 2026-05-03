<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AnalysisRequest;
use App\Http\Resources\AnalysisResource;
use Illuminate\Support\Facades\DB;
use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\Department;
use App\Models\ReportStudioTemplate;
use App\Models\Worksheet;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class AnalysisController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */

     public function index()
{
    abort_if(!auth()->user()->can('view_analysis'), 403, '');

    // Get the base query - DON'T apply category scope here
    $baseQuery = Analysis::query()
        ->{ucfirst(request()->category ?? 'Insert')}()
        ->with('department', 'sample', 'profile', 'type', 'code', 'product')
        ->when(!is_null(request()->department), function($query){
            $query->where('department_id', request()->input('department.value'));
        });

    // Let QueryBuilder apply the category filter from request
    $records = QueryBuilder::for($baseQuery)
        ->allowedFilters(Analysis::getAllowedFilters())
        ->allowedSorts(Analysis::getAllowedSorts())
        ->paginate(request()->query('per_page', 10));

    // Debug
    // Log::info('SQL: ' . $records->toArray()['sql'] ?? 'N/A');
    // Log::info('Results count: ' . $records->total());

    return Inertia::render('Analysis/Index', [
        'record' => AnalysisResource::collection($records),
        'departments' => Department::all()->map(fn($item) => [
            'value' => $item->id,
            'label' => $item->name,
        ]),
        'initialFilters' => request()->query('filter', [
            'department.name' => '', 
            'product.name' => '', 
            'code.code' => '', 
            'created_at' => '', 
            'globalFilter' => '', 
            // 'category' => 'insert',  // Default category
            'col_date' => ['start' => null, 'end' => null]
        ]),
        'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
        'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
        'initialIncludes' => request()->query('includes', []),
        'initialGlobalFilter' => request()->query('globalFilter', ''),
        'per_page' => request()->query('per_page', 10),
        'slideOverEdit' => false,
        'trashedFilter' => true,
        'trashedOptions' => Analysis::getTrashedOptions(),
        'fields' => Analysis::getColumns(),
        'model' => Analysis::MENU_NAME,
        'abilities' => method_exists(Analysis::class, 'getAbilities') 
            ? collect(Analysis::ABILITIES)->map(fn($item) => $item . '_' . Analysis::MENU_NAME)
            : collect(config('gestlab.default_abilities'))->map(fn($item) => $item . '_' . Analysis::MENU_NAME),
        'query' => request()->only(['search', 'trashed', 'date', 'orderBy', 'category', 'department'])
    ]);
}
    // public function index()
    // {
    //     // dd(Analysis::byParameters([2])->paginate());
    //     // dd(request()->input('department.value'));

    //     abort_if( !auth()->user()->can('view_analysis'), 403, '');

    //     // Build the query
    // $query = Analysis::query()
    //     ->{ucfirst(request()->category ?? 'Insert')}() // Apply default scope
    //     ->with('department', 'sample', 'profile', 'type', 'code', 'product')
    //     ->when(!is_null(request()->department), function($query){
    //         $query->where('department_id', request()->input('department.value'));
    //     });

    //     // DEBUG: Log the SQL before pagination
    // Log::info('Category: ' . request()->category);
    // Log::info('SQL: ' . $query->toSql());
    // Log::info('Bindings: ', $query->getBindings());

    // $records = QueryBuilder::for($query)
    //     ->allowedFilters(Analysis::getAllowedFilters())
    //     ->allowedSorts(Analysis::getAllowedSorts())
    //     ->paginate(request()->query('per_page', 10));

    // // DEBUG: Check if we got results
    // Log::info('Results count: ' . $records->total());

    //     // $records = QueryBuilder::for(Analysis::query()->{ucfirst(request()->category ?? 'Insert') }()) # Analysis::Insert(), Analysis::Verify(), Analysis::Approve()
    //     //                         ->with('department', 'sample', 'profile', 'type', 'code', 'product')
    //     //                         ->allowedFilters(Analysis::getAllowedFilters())
    //     //                         ->allowedSorts(Analysis::getAllowedSorts())
    //     //                         ->when(!is_null(request()->department), function($query){
    //     //                             $query->where('department_id', request()->input('department.value'));
    //     //                         })
    //     //                         ->paginate(request()->query('per_page', 10));

    //     return Inertia::render('Analysis/Index', [
    //         'record' => AnalysisResource::collection($records),
    //         'departments' => Department::all()->map(function($item){
    //             return [
    //                 'value' => $item->id,
    //                 'label' => $item->name,
    //             ];
    //         }),
    //         'initialFilters' => request()->query('filter', ['department.name' => '', 'product.name' => '', 'code.code' => '', 'created_at' => '', 'globalFilter' => '', 'category' => 'insert', 'col_date' => ['start' => null, 'end' => null]]),
    //         'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
    //         'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
    //         'initialIncludes' => request()->query('includes', []),
    //         'initialGlobalFilter' => request()->query('globalFilter', ''),
    //         'per_page' => request()->query('per_page', 2),
    //         'slideOverEdit' => false,
    //         'trashedFilter' => true,
    //         'trashedOptions' => Analysis::getTrashedOptions(),
    //         'fields' => Analysis::getColumns(),
    //         'model' => Analysis::MENU_NAME,
    //         'abilities' => method_exists(Analysis::class, 'getAbilities') ? collect(Analysis::ABILITIES)->map(function($item){
    //             return $item . '_' . Analysis::MENU_NAME;
    //         }) : collect(config('gestlab.default_abilities'))->map(function($item){
    //             return $item . '_' . Analysis::MENU_NAME;
    //         }),                           
    //         'query' => request()->only(['search', 'trashed', 'date', 'orderBy', 'category', 'department'])
    //     ]);

    //     // return Inertia::render('Analysis/Index', [
    //     //     'record' => AnalysisResource::collection(
    //     //         Analysis::query()
    //     //                     ->when(request()->input('category'), function($query, $category){
    //     //                         if(request()->category){
    //     //                             return $query->{ucfirst($category)}();
    //     //                         }

    //     //                         return $query->Insert();
                                
    //     //                     })
    //     //                     ->whereNull('end_date')
    //     //                     ->with('department', 'sample', 'profile', 'type', 'code')
    //     //                     ->when(request()->input('search'), function($query, $search){ 
    //     //                         $query->whereRelation('code', 'code', 'like', "%{$search}%")
    //     //                         ->orWhere('col_date', 'like', "%{$search}%")
    //     //                         ->orWhereRelation('profile', 'name', 'like', "%{$search}%")
    //     //                         ->orWhereRelation('department', 'name', 'like', "%{$search}%");
    //     //                     })
    //     //                     ->when(request()->input('filter'), function($query, $filter){
    //     //                         if($filter = 'trashed'){
    //     //                             $query->withTrashed();
    //     //                         }
    //     //                     })
    //     //                     ->latest()
    //     //                     ->paginate(10)
    //     //                     ->withQueryString()
    //     //                 ),
    //     //     'slideOverEdit' => false,            
    //     //     'fields' => [
    //     //         [
    //     //             'name' => 'Código',
    //     //             'value' => 'cl'
    //     //         ],
    //     //         [
    //     //             'name' => 'Colheita',
    //     //             'value' => 'col_date'
    //     //         ],
    //     //         [
    //     //             'name' => 'Perfil',
    //     //             'value' => 'profile'
    //     //         ],
    //     //         [
    //     //             'name' => 'Departamento',
    //     //             'value' => 'department'
    //     //         ],
    //     //         [
    //     //             'name' => 'Estado',
    //     //             'value' => 'status'
    //     //         ],
    //     //     ],
    //     //     'model' => Analysis::MENU_NAME,
    //     //     'abilities' => method_exists(Analysis::class, 'getAbilities') ? collect(Analysis::ABILITIES)->map(function($item){
    //     //         return $item . '_' . Analysis::MENU_NAME;
    //     //     }) : collect(config('gestlab.default_abilities'))->map(function($item){
    //     //         return $item . '_' . Analysis::MENU_NAME;
    //     //     }),                           
    //     //     'query' => request()->only(['search', 'trashed', 'category'])
    //     // ]);
    // }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_analysis'), 403, '');
        // Get any required data

        // Load form

        return Inertia::render('Analysis/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(AnalysisRequest $request)
    {
        abort_if( !auth()->user()->can('add_analysis'), 403, '');

        // Persiste data to DB
        Analysis::create($request->validated());

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
    // public function edit($id)
    // {
    //     abort_if( !auth()->user()->can('edit_analysis'), 403, '');

    //     // Find the record
    //     $record = Analysis::with('department', 'sample', 'profile', 'type', 'code')->findOrFail($id);

    //     // dd($record);

    //     // Return Inertia View with record data
    //     return Inertia::render('Analysis/ResultsWorkflow', [
    //         'action' => ($record->sample->results->count() < 1 ? 'analyze' : ($record->sample->results()->whereNotNull('inserted_date')->whereNull('verified_date')->count() > 1 ? 'verify' : 'approve')),
    //         'record' => [
    //             'id' => $record->id,
    //             'code' => $record->code->code,
    //             'cl_id' => [
    //                 'value' => $record?->cl_id,
    //                 'label' => $record?->code?->code
    //             ],
    //             'profile_id' => [
    //                 'value' => $record?->profile_id,
    //                 'label' => $record?->profile?->name
    //             ],
    //             'type_id' => [
    //                 'value' => $record?->type_id,
    //                 'label' => $record?->type?->name
    //             ],
    //             'sample_id' => [
    //                 'value' => $record?->sample_id,
    //                 'label' => $record?->sample?->code
    //             ],
    //             'department_id' => [
    //                 'value' => $record?->department_id,
    //                 'label' => $record?->department?->name
    //             ]
    //         ],
    //     ]);
    // }

    public function edit($id)
{
    abort_if(!auth()->user()->can('edit_analysis') || !auth()->user()->can('add_results'), 403, '');

    // Eager load all necessary relationships
    $record = Analysis::with([
        'sample.results',
        'sample.results.parameter', // If you need parameter details
        'sample.collection.collection',
        'department',
        'profile',
        'profile.parameters', // To know expected result count
        'type',
        'code',
        'product'
    ])->findOrFail($id);

    // Check if analysis is archived/completed
    if ($record->end_date !== null) {
        return redirect()->route('analysis.index', ['category' => 'archived'])
            ->with('toast', [
                'title' => trans('gestlab.toasts.notification'),
                'message' => 'Esta análise está concluída (resultados validados) e não pode ser modificada.',
            ]);
    }

    $sample = $record->sample;
    $results = $sample->results;
    
    // Handle case where no results exist yet
    if ($results->isEmpty()) {
        return Inertia::render('Analysis/ResultsWorkflow', [
            'action' => 'analyze',
            'record' => $this->formatRecord($record),
            'expected_parameters_count' => $record->profile?->parameters?->count() ?? 0,
            'actual_results_count' => 0,
            'scope_audit' => $this->buildScopeAudit($record),
            'worksheet_brief' => $this->buildWorksheetBrief($record),
            'report_studio' => $this->resolveAnalysisReportStudio(),
        ]);
    }

    // Calculate status counts
    $totalResults = $results->count();
    $insertedCount = $results->whereNotNull('inserted_date')->count();
    $verifiedCount = $results->whereNotNull('verified_date')->count();
    $approvedCount = $results->whereNotNull('approved_date')->count();
    $pendingInsertion = $results->whereNull('inserted_date')->count();
    $pendingVerification = $results->whereNotNull('inserted_date')->whereNull('verified_date')->count();
    $pendingApproval = $results->whereNotNull('verified_date')->whereNull('approved_date')->count();

    // Determine workflow stage based on ALL results status
    $action = match(true) {
        $pendingInsertion > 0 => 'analyze',      // Something still needs insertion
        $pendingVerification > 0 => 'verify',    // All inserted, something needs verification
        $pendingApproval > 0 => 'approve',       // All verified, something needs approval
        $approvedCount === $totalResults => 'completed', // Everything done
        default => 'unknown'                     // Shouldn't happen, but safe fallback
    };

    // Optional: Auto-set end_date if completed and not already set
    if ($action === 'completed' && $record->end_date === null) {
        $record->update(['end_date' => now()]);
    }

    return Inertia::render('Analysis/ResultsWorkflow', [
        'action' => $action,
        'record' => $this->formatRecord($record),
        'results_summary' => [
            'total' => $totalResults,
            'pending_insertion' => $pendingInsertion,
            'pending_verification' => $pendingVerification,
            'pending_approval' => $pendingApproval,
            'approved' => $approvedCount,
        ],
        'can_insert' => auth()->user()->can('insert_results'),
        'can_verify' => auth()->user()->can('verify_results'),
        'can_approve' => auth()->user()->can('approve_results'),
        'scope_audit' => $this->buildScopeAudit($record),
        'worksheet_brief' => $this->buildWorksheetBrief($record),
        'report_studio' => $this->resolveAnalysisReportStudio(),
    ]);
}

// Extract record formatting to reduce duplication
private function formatRecord(Analysis $record): array
{
    return [
        'id' => $record->id,
        'code' => $record->code?->code,
        'cl_id' => [
            'value' => $record->cl_id,
            'label' => $record->code?->code
        ],
        'profile_id' => [
            'value' => $record->profile_id,
            'label' => $record->profile?->name
        ],
        'type_id' => [
            'value' => $record->type_id,
            'label' => $record->type?->name
        ],
        'sample_id' => [
            'value' => $record->sample_id,
            'label' => $record->sample?->code
        ],
        'department_id' => [
            'value' => $record->department_id,
            'label' => $record->department?->name
        ],
        'product_id' => [
            'value' => $record->product_id,
            'label' => $record->product?->name
        ],
        'collection_product_id' => $record->code?->collection_id,
    ];
}

private function buildScopeAudit(Analysis $record): array
{
    $expectedParameters = collect($record->profile?->parameters ?? [])
        ->map(fn ($parameter) => [
            'id' => $parameter->id,
            'code' => $parameter->code,
            'name' => $parameter->name,
        ])
        ->unique('id')
        ->values();

    /** @var CollectionProduct|null $collectionProduct */
    $collectionProduct = $record->sample?->collection?->collection;

    $receptionParameters = collect(data_get($collectionProduct?->extra_data, 'submitted_payload.required_parameters', []))
        ->map(fn ($parameter) => [
            'id' => data_get($parameter, 'id'),
            'code' => data_get($parameter, 'code'),
            'name' => data_get($parameter, 'name'),
            'profiles' => data_get($parameter, 'profiles', []),
        ])
        ->filter(fn (array $parameter) => ! empty($parameter['id']))
        ->unique('id')
        ->values();

    $existingResults = collect($record->sample?->results ?? [])
        ->map(fn ($result) => [
            'id' => $result->parameter_id,
            'code' => $result->parameter?->code,
            'name' => $result->parameter_label,
        ])
        ->filter(fn (array $parameter) => ! empty($parameter['id']))
        ->unique('id')
        ->values();

    $expectedIds = $expectedParameters->pluck('id')->filter()->map(fn ($id) => (int) $id);
    $receptionIds = $receptionParameters->pluck('id')->filter()->map(fn ($id) => (int) $id);
    $resultIds = $existingResults->pluck('id')->filter()->map(fn ($id) => (int) $id);

    return [
        'collection_product_id' => $collectionProduct?->id,
        'conditioning_status' => data_get($collectionProduct?->extra_data, 'submitted_payload.conditioning_status'),
        'packaging_condition' => data_get($collectionProduct?->extra_data, 'submitted_payload.packaging_condition'),
        'temperature_condition' => data_get($collectionProduct?->extra_data, 'submitted_payload.temperature_condition'),
        'integrity_observations' => data_get($collectionProduct?->extra_data, 'submitted_payload.integrity_observations'),
        'chain_of_custody_notes' => data_get($collectionProduct?->extra_data, 'submitted_payload.chain_of_custody_notes'),
        'resolved_profiles' => data_get($collectionProduct?->extra_data, 'submitted_payload.resolved_profiles', []),
        'expected_parameters' => $expectedParameters->all(),
        'reception_parameters' => $receptionParameters->all(),
        'existing_results' => $existingResults->all(),
        'expected_count' => $expectedParameters->count(),
        'reception_count' => $receptionParameters->count(),
        'results_count' => $existingResults->count(),
        'missing_from_results' => $expectedParameters
            ->whereIn('id', $expectedIds->diff($resultIds))
            ->values()
            ->all(),
        'outside_profile_scope' => $existingResults
            ->whereIn('id', $resultIds->diff($expectedIds))
            ->values()
            ->all(),
        'scope_drift' => [
            'reception_only' => $receptionParameters
                ->whereIn('id', $receptionIds->diff($expectedIds))
                ->values()
                ->all(),
            'profile_only' => $expectedParameters
                ->whereIn('id', $expectedIds->diff($receptionIds))
                ->values()
                ->all(),
        ],
    ];
}

private function buildWorksheetBrief(Analysis $record): ?array
{
    $collectionProductId = $record->code?->collection_id;

    if (! $collectionProductId) {
        return null;
    }

    $worksheet = Worksheet::query()
        ->where('worksheets->analysis_id', $record->id)
        ->orWhere('worksheets->collection_product_id', $collectionProductId)
        ->latest('updated_at')
        ->first();

    if (! $worksheet) {
        return [
            'exists' => false,
            'analysis_id' => $record->id,
            'collection_product_id' => $collectionProductId,
        ];
    }

    return [
        'exists' => true,
        'id' => $worksheet->id,
        'name' => $worksheet->name,
        'updated_at' => optional($worksheet->updated_at)?->toIso8601String(),
    ];
}

private function resolveAnalysisReportStudio(): ?array
{
    $template = ReportStudioTemplate::resolveDefaultFor('analysis');

    if (! $template) {
        return null;
    }

    return [
        'id' => $template->id,
        'name' => $template->name,
        'renderer' => $template->renderer,
        'theme_preset' => $template->theme_preset,
        'canva_design_url' => $template->canva_design_url,
        'description' => $template->description,
        'layout_schema' => $template->layout_schema ?? [],
    ];
}

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(AnalysisRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_analysis'), 403, '');

        // Find the record
        $record = Analysis::findOrFail($id);

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
        abort_if( !auth()->user()->can('restore_delete'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Analysis::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_analysis'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Analysis::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getAnalysis() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("analysis")
                ->select('analysis.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
