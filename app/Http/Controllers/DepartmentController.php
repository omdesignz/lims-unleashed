<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_departments'), 403, '');

        return Inertia::render('Departments/Index', [
            'record' => DepartmentResource::collection(
                Department::query()
                            ->with('supervisor')
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
                    'name' => trans('gestlab.general.labels.departments.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.departments.supervisor_id'),
                    'value' => 'supervisor'
                ],
                [
                    'name' => trans('gestlab.general.labels.departments.contact'),
                    'value' => 'contact'
                ],
                [
                    'name' => trans('gestlab.general.labels.departments.email'),
                    'value' => 'email'
                ],
                [
                    'name' => trans('gestlab.general.labels.departments.extension'),
                    'value' => 'extension'
                ]
            ],
            'model' => Department::MENU_NAME,
            'abilities' => method_exists(Department::class, 'getAbilities') ? collect(Department::ABILITIES)->map(function($item){
                return $item . '_' . Department::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Department::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_departments'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Departments/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(DepartmentRequest $request)
    {
        abort_if( !auth()->user()->can('add_departments'), 403, '');

        // Persiste data to DB
        Department::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_departments'), 403, '');

        // Find the record
        $record = Department::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Departments/Edit', [
            'record' => DepartmentResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(DepartmentRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_departments'), 403, '');

        // Find the record
        $record = Department::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_departments'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Department::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_departments'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Department::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function getDepartment() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("departments")
                ->select('departments.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
