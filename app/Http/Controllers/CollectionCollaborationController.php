<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CollectionCollaborationRequest;
use App\Http\Resources\CollectionCollaborationResource;
use Illuminate\Support\Facades\DB;
use App\Models\CollectionCollaboration;
use Inertia\Inertia;

class CollectionCollaborationController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_collaboration_categories'), 403, '');

        return Inertia::render('CollectionCollaborations/Index', [
            'record' => CollectionCollaborationResource::collection(
                CollectionCollaboration::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter = 'trashed'){
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
                    'name' => trans('gestlab.general.labels.collection_collaborations.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.collection_collaborations.description'),
                    'value' => 'description'
                ],
            ],
            'model' => CollectionCollaboration::MENU_NAME,
            'abilities' => method_exists(CollectionCollaboration::class, 'getAbilities') ? collect(CollectionCollaboration::ABILITIES)->map(function($item){
                return $item . '_' . CollectionCollaboration::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . CollectionCollaboration::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_collaboration_categories'), 403, '');
        // Get any required data

        // Load form

        return Inertia::render('CollectionCollaborations/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(CollectionCollaborationRequest $request)
    {
        abort_if( !auth()->user()->can('add_collaboration_categories'), 403, '');

        // Persiste data to DB
        CollectionCollaboration::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_collaboration_categories'), 403, '');

        // Find the record
        $record = CollectionCollaboration::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('CollectionCollaborations/Edit', [
            'record' => CollectionCollaborationResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(CollectionCollaborationRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_collaboration_categories'), 403, '');

        // Find the record
        $record = CollectionCollaboration::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_collaboration_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (CollectionCollaboration::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_collaboration_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (CollectionCollaboration::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getCollectionCollaboration() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("collection_collaborations")
                ->select('collection_collaborations.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
