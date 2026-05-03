<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Inertia\Inertia;

class RoleController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_roles'), 403, '');

        return Inertia::render('Roles/Index', [
            'record' => RoleResource::collection(
                Role::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->latest()
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.roles.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.roles.label'),
                    'value' => 'label'
                ],
                [
                    'name' => trans('gestlab.general.labels.roles.guard_name'),
                    'value' => 'guard_name'
                ],
            ],
            'model' => Role::MENU_NAME,
            'abilities' => method_exists(Role::class, 'getAbilities') ? collect(Role::ABILITIES)->map(function($item){
                return $item . '_' . Role::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Role::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_roles'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Roles/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(RoleRequest $request)
    {
        abort_if( !auth()->user()->can('add_roles'), 403, '');


        // Persiste data to DB
        $record = Role::create($request->safe()->except(['permissions']));

        $record->permissions()->sync(collect($request->permissions)->pluck('permission_id')->unique()->toArray());


        return to_route('roles.edit', $record->id)->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
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
        abort_if( !auth()->user()->can('edit_roles'), 403, '');

        // Find the record
        $record = Role::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Roles/Edit', [
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'label' => $record->label,
                'guard_name' => $record->guard_name,
                'permissions' => collect($record->permissions)->map(function($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['label']
                    ];
                })->toArray(),
            ],
            'permissions' => collect(Permission::all())->map(function($item) {
                return [
                    'value' => $item['id'],
                    'label' => $item['label']
                ];
            })->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(RoleRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_roles'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Role::findOrFail($id), function($record) use($request) {

                $record->update($request->safe()->except(['permissions']));

                $record->permissions()->sync(collect($request->permissions)->unique()->toArray());
    
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
        abort_if( !auth()->user()->can('delete_roles'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Role::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_roles'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Role::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getRole() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("roles")
                ->select('roles.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('guard_name','LIKE',"%$search%")
                ->orWhere('label','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
