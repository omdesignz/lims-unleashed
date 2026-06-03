<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountCategoryRequest;
use App\Http\Resources\DiscountCategoryResource;
use App\Models\DiscountCategory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DiscountCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_discount_categories'), 403, '');

        return Inertia::render('DiscountCategories/Index', [
            'record' => DiscountCategoryResource::collection(
                DiscountCategory::query()
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
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.discount_categories.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.discount_categories.symbol'),
                    'value' => 'symbol',
                ],
                [
                    'name' => trans('gestlab.general.labels.discount_categories.description'),
                    'value' => 'description',
                ],
            ],
            'model' => DiscountCategory::MENU_NAME,
            'abilities' => method_exists(DiscountCategory::class, 'getAbilities') ? collect(DiscountCategory::ABILITIES)->map(function ($item) {
                return $item.'_'.DiscountCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.DiscountCategory::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_discount_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('DiscountCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DiscountCategoryRequest $request)
    {
        abort_if(! auth()->user()->can('add_discount_categories'), 403, '');

        // Persiste data to DB
        DiscountCategory::create($request->validated());

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
        abort_if(! auth()->user()->can('edit_discount_categories'), 403, '');

        // Find the record
        $record = DiscountCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('DiscountCategories/Edit', [
            'record' => DiscountCategoryResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DiscountCategoryRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_discount_categories'), 403, '');

        // Find the record
        $record = DiscountCategory::findOrFail($id);

        $record->update($request->validated());

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
        abort_if(! auth()->user()->can('delete_discount_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (DiscountCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_discount_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (DiscountCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }
}
