<?php

namespace App\Http\Controllers;

use App\Actions\SaveInventoryCategory;
use App\Http\Requests\InventoryCategoryRequest;
use App\Http\Resources\ItemCategoryResource;
use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ItemCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $canView = auth()->user()->can('view_item_categories');
        abort_unless($canView
            || (request()->boolean('create') && auth()->user()->can('add_item_categories'))
            || (request()->filled('edit') && auth()->user()->can('edit_item_categories')), 403);

        return Inertia::render('ItemCategories/Index', [
            'record' => ItemCategoryResource::collection(
                ItemCategory::query()
                    ->withTypeLock()
                    ->when(! $canView, fn ($query) => $query->whereRaw('false'))
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'openCreate' => request()->boolean('create') && auth()->user()->can('add_item_categories'),
            'initialRecord' => request()->filled('edit') && auth()->user()->can('edit_item_categories')
                ? ItemCategoryResource::make(ItemCategory::query()->withTypeLock()->findOrFail(request()->integer('edit'))) : null,
            'fields' => [
                [
                    'name' => 'Tipo de inventário',
                    'value' => 'inventory_type_label',
                ],
                [
                    'name' => trans('gestlab.general.labels.item_categories.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.item_categories.description'),
                    'value' => 'description',
                ],
            ],
            'model' => ItemCategory::MENU_NAME,
            'abilities' => method_exists(ItemCategory::class, 'getAbilities') ? collect(ItemCategory::ABILITIES)->map(function ($item) {
                return $item.'_'.ItemCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ItemCategory::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_item_categories'), 403, '');

        // Get any required data

        // Load form

        return redirect()->route('itemcategories.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InventoryCategoryRequest $request, SaveInventoryCategory $saveCategory)
    {
        abort_if(! auth()->user()->can('add_item_categories'), 403, '');

        // Persiste data to DB
        $saveCategory->execute($request->user()->id, $request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_item_categories'), 403, '');

        // Find the record
        $record = ItemCategory::findOrFail($id);

        // Return Inertia View with record data
        return redirect()->route('itemcategories.index', ['edit' => $record->id]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InventoryCategoryRequest $request, int $id, SaveInventoryCategory $saveCategory)
    {
        abort_if(! auth()->user()->can('edit_item_categories'), 403, '');

        // Find the record
        $saveCategory->execute($request->user()->id, $request->validated(), $id);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        abort_if(! auth()->user()->can('delete_item_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (ItemCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_item_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (ItemCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getItemCategory()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('item_categories')
                ->select('item_categories.*')
                ->where('name', 'LIKE', "%$search%")
                ->orWhere('code', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
