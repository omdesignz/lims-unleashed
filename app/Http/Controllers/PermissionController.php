<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;
use Inertia\Inertia;

class PermissionController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_permissions'), 403, '');

        return Inertia::render('Permissions/Index', [
            'record' => PermissionResource::collection(
                Permission::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->latest()
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => true,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.permissions.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.permissions.label'),
                    'value' => 'label'
                ],
                [
                    'name' => trans('gestlab.general.labels.permissions.guard_name'),
                    'value' => 'guard_name'
                ],
            ],
            'model' => Permission::MENU_NAME,
            'abilities' => method_exists(Permission::class, 'getAbilities') ? collect(Permission::ABILITIES)->map(function($item){
                return $item . '_' . Permission::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Permission::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_permissions'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Permissions/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(PermissionRequest $request)
    {
        abort_if( !auth()->user()->can('add_permissions'), 403, '');

        // Persiste data to DB
        $record = Permission::create($request->validated());

        // dd($record);

        // sync permission for admin
        if( $role = Role::where('name', 'Admin')->first() ) {
            $role->givePermissionTo($record->name);
        }

        return to_route('permissions.index')->with([
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
        abort_if( !auth()->user()->can('edit_permissions'), 403, '');

        // Find the record
        $record = Permission::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Permissions/Edit', [
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'label' => $record->label,
                'guard_name' => $record->guard_name,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(PermissionRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_permissions'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Permission::findOrFail($id), function($record) use($request) {

                $record->update($request->validated());
    
            });

        });

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
        abort_if( !auth()->user()->can('delete_permissions'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Permission::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_permissions'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Permission::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getPermission() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("permissions")
                ->select('permissions.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('guard_name','LIKE',"%$search%")
                ->orWhere('label','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
