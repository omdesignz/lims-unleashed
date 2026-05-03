<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CounterAnalysisRequest;
use App\Http\Resources\CounterAnalysisResource;
use App\Jobs\RegisterCounterAnalysis;
use App\Models\Analysis;
use App\Models\CounterAnalysis;
use App\Models\ReportStudioTemplate;
use App\Models\Result;
use App\Support\DuplicateSubmissionGuard;
use Inertia\Inertia;

class CounterAnalysisController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_counter_analysis'), 403, '');

        return Inertia::render('CounterAnalysis/Index', [
            'record' => CounterAnalysisResource::collection(
                CounterAnalysis::query()
                            ->with('department', 'sample', 'profile','parameter', 'type', 'code')
                            ->when(request()->input('search'), function($query, $search){
                                $query->whereRelation('code', 'code', 'like', "%{$search}%");
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
                    'name' => 'Código',
                    'value' => 'cl'
                ],
                [
                    'name' => 'Colheita',
                    'value' => 'col_date'
                ],
                [
                    'name' => 'Perfil',
                    'value' => 'profile'
                ],
                [
                    'name' => 'Departamento',
                    'value' => 'department'
                ],
                [
                    'name' => 'Estado',
                    'value' => 'status'
                ],
            ],
            'model' => CounterAnalysis::MENU_NAME,
            'abilities' => method_exists(CounterAnalysis::class, 'getAbilities') ? collect(CounterAnalysis::ABILITIES)->map(function($item){
                return $item . '_' . CounterAnalysis::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . CounterAnalysis::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_counter_analysis'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('CounterAnalysis/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request, DuplicateSubmissionGuard $duplicateSubmissionGuard)
    {
        abort_if( !auth()->user()->can('add_counter_analysis'), 403, '');

        $validated = $request->validate([
            'result_id' => ['required', 'exists:results,id'],
        ]);

        $result = Result::query()->with('counter_analysis')->findOrFail($validated['result_id']);

        if ($result->counter_analysis()->exists() || $result->requested_counter_analysis) {
            return to_route('analysis.index')->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'A contra-análise para este resultado já foi solicitada.',
                ],
            ]);
        }

        if (! $duplicateSubmissionGuard->acquireFromRequest($request, 'counter-analysis-request', $validated, 60)) {
            return back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => 'O mesmo pedido de contra-análise já está a ser processado.',
                ],
            ]);
        }

        dispatch(new RegisterCounterAnalysis(
            $validated['result_id'],
            auth()->id()
        ));

        return to_route('analysis.index')->with([
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
        abort_if( !auth()->user()->can('edit_counter_analysis'), 403, '');

        // Find the record
        $record = CounterAnalysis::with('department', 'sample', 'profile', 'parameter', 'type', 'code')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('CounterAnalysis/Edit', [
            'action' => ($record->sample->results->count() < 1 ? 'analyze' : ($record->sample->results()->whereNotNull('inserted_date')->whereNull('verified_date')->count() > 1 ? 'verify' : 'approve')),
            'record' => [
                'id' => $record->id,
                'code' => $record->code->code,
                'cl_id' => [
                    'value' => $record?->cl_id,
                    'label' => $record?->code?->code
                ],
                'profile_id' => [
                    'value' => $record?->profile_id,
                    'label' => $record?->profile?->name
                ],
                'parameter_id' => [
                    'value' => $record?->parameter_id,
                    'label' => $record?->parameter?->name
                ],
                'result_id' => [
                    'value' => $record?->result_id,
                    'label' => $record?->result?->approved_value
                ],
                'type_id' => [
                    'value' => $record?->type_id,
                    'label' => $record?->type?->name
                ],
                'sample_id' => [
                    'value' => $record?->sample_id,
                    'label' => $record?->sample?->code
                ],
                'department_id' => [
                    'value' => $record?->department_id,
                    'label' => $record?->department?->name
                ]
            ],
            'report_studio' => $this->resolveAnalysisReportStudio(),
        ]);
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
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(CounterAnalysisRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_counter_analysis'), 403, '');

        // Find the record
        $record = CounterAnalysis::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_counter_analysis'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (CounterAnalysis::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_counter_analysis'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (CounterAnalysis::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
            
            $data = DB::table("counter_analysis")
                ->select('counter_analysis.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
