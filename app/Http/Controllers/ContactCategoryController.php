<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactCategoryRequest;
use App\Http\Resources\ContactCategoryResource;
use App\Models\ContactCategory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ContactCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_contact_categories'), 403, '');

        return Inertia::render('ContactCategories/Index', [
            'record' => ContactCategoryResource::collection(
                ContactCategory::query()
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
                    'name' => trans('gestlab.general.labels.contact_categories.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.contact_categories.code'),
                    'value' => 'code',
                ],
                [
                    'name' => trans('gestlab.general.labels.contact_categories.description'),
                    'value' => 'description',
                ],
            ],
            'model' => ContactCategory::MENU_NAME,
            'abilities' => method_exists(ContactCategory::class, 'getAbilities') ? collect(ContactCategory::ABILITIES)->map(function ($item) {
                return $item.'_'.ContactCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ContactCategory::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_contact_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('ContactCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContactCategoryRequest $request)
    {
        abort_if(! auth()->user()->can('add_contact_categories'), 403, '');

        // Persiste data to DB
        ContactCategory::create($request->validated());

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
        abort_if(! auth()->user()->can('edit_contact_categories'), 403, '');

        // Find the record
        $record = ContactCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ContactCategories/Edit', [
            'record' => ContactCategoryResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContactCategoryRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_contact_categories'), 403, '');

        // Find the record
        $record = ContactCategory::findOrFail($id);

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
        abort_if(! auth()->user()->can('delete_contact_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (ContactCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_contact_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (ContactCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
