<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PackagingCategoryRequest;
use App\Http\Resources\PackagingCategoryResource;
use Illuminate\Support\Facades\DB;
use App\Models\PackagingCategory;
use Inertia\Inertia;

class PackagingCategoryController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_packaging_types'), 403, '');

        return Inertia::render('PackagingCategories/Index', [
            'record' => PackagingCategoryResource::collection(
                PackagingCategory::query()
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
                    'name' => trans('gestlab.general.labels.packaging_categories.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.packaging_categories.description'),
                    'value' => 'description'
                ],
            ],
            'model' => PackagingCategory::MENU_NAME,
            'abilities' => method_exists(PackagingCategory::class, 'getAbilities') ? collect(PackagingCategory::ABILITIES)->map(function($item){
                return $item . '_' . PackagingCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . PackagingCategory::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_packaging_types'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('PackagingCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(PackagingCategoryRequest $request)
    {
        abort_if( !auth()->user()->can('add_packaging_types'), 403, '');

        // Persiste data to DB
        PackagingCategory::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_packaging_types'), 403, '');

        // Find the record
        $record = PackagingCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('PackagingCategories/Edit', [
            'record' => PackagingCategoryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(PackagingCategoryRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_packaging_types'), 403, '');

        // Find the record
        $record = PackagingCategory::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_packaging_types'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (PackagingCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_packaging_types'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (PackagingCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function getPackagingCategory() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("packaging_categories")
                ->select('packaging_categories.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
