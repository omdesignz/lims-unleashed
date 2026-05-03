<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ResultCategoryRequest;
use App\Http\Resources\ResultCategoryResource;
use DB;
use App\Models\ResultCategory;
use Inertia\Inertia;

class ResultCategoryController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_result_categories'), 403, '');

        return Inertia::render('ResultCategories/Index', [
            'record' => ResultCategoryResource::collection(
                        ResultCategory::query()
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
            'slideOverEdit' => true,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.result_categories.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.result_categories.description'),
                    'value' => 'description'
                ],
            ],
            'model' => ResultCategory::MENU_NAME,
            'abilities' => method_exists(ResultCategory::class, 'getAbilities') ? collect(ResultCategory::ABILITIES)->map(function($item){
                return $item . '_' . ResultCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . ResultCategory::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_result_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('ResultCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ResultCategoryRequest $request)
    {
        abort_if( !auth()->user()->can('add_result_categories'), 403, '');

        // Persiste data to DB
        ResultCategory::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_result_categories'), 403, '');

        // Find the record
        $record = ResultCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ResultCategories/Edit', [
            'record' => ResultCategoryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ResultCategoryRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_result_categories'), 403, '');

        // Find the record
        $record = ResultCategory::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_result_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (ResultCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_result_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (ResultCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getResultCategory() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("result_categories")
                ->select('result_categories.*')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($search) {
                    $query->where('description','LIKE',"%$search%")
                        ->orWhere('name','LIKE',"%$search%");
                })
                ->get();
        }

        return response()->json($data);
    }

}
