<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposalTemplateRequest;
use App\Http\Resources\ProposalTemplateResource;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ProposalTemplateController extends Controller
{
    //
    public function index()
    {
        $records = QueryBuilder::for(ProposalTemplate::class)
                                ->with('user')
                                ->allowedFilters(ProposalTemplate::getAllowedFilters())
                                ->allowedSorts(ProposalTemplate::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10)); 


        return Inertia::render('ProposalTemplates/Index', [
            'record' => ProposalTemplateResource::collection($records),
            'initialFilters' => request()->query('filter', ['name' => '', 'user_id' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => false,  
            'trashedFilter' => true,
            'trashedOptions' => ProposalTemplate::getTrashedOptions(),
            'fields' => ProposalTemplate::getColumns(),
            'model' => ProposalTemplate::MENU_NAME,
            'abilities' => method_exists(ProposalTemplate::class, 'getAbilities') ? collect(ProposalTemplate::ABILITIES)->map(function($item){
                return $item . '_' . ProposalTemplate::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . ProposalTemplate::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy'])
        ]);
    }

    public function create()
    {
        return Inertia::render('ProposalTemplates/Create');
    }

    public function store(ProposalTemplateRequest $request)
    {
        // abort_if(!auth()->user()->can('add_maintenance_categories'), 403, '');

        // Persiste data to DB
        ProposalTemplate::create($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function edit($id)
    {
        // Find the record
        $record = ProposalTemplate::with('user')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ProposalTemplates/Edit', [
            'record' => ProposalTemplateResource::make($record)
        ]);
    }

    public function update(ProposalTemplateRequest $request, $id)
    {
         // Find the record
         $record = ProposalTemplate::findOrFail($id);

         $record->update($request->validated());
 
         return redirect()->back()->with([
             'toast' => [
                 'title' => trans('gestlab.toasts.notification'),
                 'message' => trans('gestlab.toasts.record_successfully_updated'),
             ]
         ]);
    }

    public function destroy()
    {
        // abort_if( !auth()->user()->can('delete_proposals'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (ProposalTemplate::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    public function restore()
    {
        // abort_if( !auth()->user()->can('restore_proposals'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (ProposalTemplate::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function getProposalTemplate() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("proposal_templates")
                ->select('proposal_templates.*')
                ->where('name','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
