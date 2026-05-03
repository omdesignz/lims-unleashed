<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CollectionEndResultRequest;
use App\Http\Resources\CollectionEndResultResource;
use Illuminate\Support\Facades\DB;
use App\Models\CollectionEndResult;
use Inertia\Inertia;

class CollectionEndResultController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_collection_end_results'), 403, '');

        return Inertia::render('CollectionEndResults/Index', [
            'record' => CollectionEndResultResource::collection(
                CollectionEndResult::query()
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
                    'name' => trans('gestlab.general.labels.collection_end_results.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.collection_end_results.description'),
                    'value' => 'description'
                ],
            ],
            'model' => CollectionEndResult::MENU_NAME,
            'abilities' => method_exists(CollectionEndResult::class, 'getAbilities') ? collect(CollectionEndResult::ABILITIES)->map(function($item){
                return $item . '_' . CollectionEndResult::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . CollectionEndResult::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_collection_end_results'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('CollectionEndResults/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(CollectionEndResultRequest $request)
    {
        abort_if( !auth()->user()->can('add_collection_end_results'), 403, '');


        // Persiste data to DB
        CollectionEndResult::create($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => 'Notificção',
                'message' => 'Registro armazenado com êxito'
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
        abort_if( !auth()->user()->can('edit_collection_end_results'), 403, '');

        // Find the record
        $record = CollectionEndResult::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('CollectionEndResults/Edit', [
            'record' => CollectionEndResultResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(CollectionEndResultRequest $request, $id)
    {
        abort_if( !auth()->user()->can('add_collection_end_results'), 403, '');

        // Find the record
        $record = CollectionEndResult::findOrFail($id);

        $record->update($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => 'Notificção',
                'message' => 'Registro actualizado com êxito'
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        abort_if( !auth()->user()->can('delete_collection_end_results'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (CollectionEndResult::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => '',
                'message' => 'Registro removido com sucesso'
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if( !auth()->user()->can('restore_collection_end_results'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (CollectionEndResult::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => '',
                'message' => 'Registro restaurado com sucesso'
            ]
       ]);
    }


    public function getCollectionEndResult() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("collection_end_results")
                ->select('collection_end_results.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
