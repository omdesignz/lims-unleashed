<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ArchivedDocumentRequest;
use App\Http\Resources\ArchivedDocumentResource;
use App\Models\ArchivedDocument;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ArchivedDocumentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_' . ArchivedDocument::MENU_NAME), 403, '');

        return Inertia::render('ArchivedDocument/Index', [
            'record' => ArchivedDocumentResource::collection(
                ArchivedDocument::query()
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('title', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.archived_documents.title'),
                    'value' => 'title'
                ],
                [
                    'name' => trans('gestlab.general.labels.archived_documents.file_path'),
                    'value' => 'file_path'
                ],
                [
                    'name' => trans('gestlab.general.labels.archived_documents.description'),
                    'value' => 'description'
                ],
            ],
            'model' => ArchivedDocument::MENU_NAME,
            'abilities' => method_exists(ArchivedDocument::class, 'getAbilities') ? collect(ArchivedDocument::ABILITIES)->map(function ($item) {
                return $item . '_' . ArchivedDocument::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . ArchivedDocument::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_' . ArchivedDocument::MENU_NAME), 403, '');

        return Inertia::render('ArchivedDocument/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ArchivedDocumentRequest $request)
    {
        abort_if(!auth()->user()->can('add_' . ArchivedDocument::MENU_NAME), 403, '');

        $path = $request->file('file')?->store('archived_documents');

        ArchivedDocument::create($request->validated() + ['file_path' => $path]);

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
        abort_if(!auth()->user()->can('edit_' . ArchivedDocument::MENU_NAME), 403, '');

        // Find the record
        $record = ArchivedDocument::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('ArchivedDocument/Edit', [
            'record' => ArchivedDocumentResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ArchivedDocumentRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_' . ArchivedDocument::MENU_NAME), 403, '');

        // Find the record
        $record = ArchivedDocument::findOrFail($id);

        $path = $record->file_path;

        if ($request->hasFile('file')) {
            // Delete old file
            Storage::delete($record->file_path);

            // Store new file
            $path = $request->file('file')->store('archived_documents');
        }

        $record->update($request->validated() + ['file_path' => $path]);

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
        abort_if(!auth()->user()->can('delete_' . ArchivedDocument::MENU_NAME), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (ArchivedDocument::withTrashed()->findOrFail(request('recordIds')) as $record) {

            // Delete file from storage
            Storage::delete($record->file_path);

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
        abort_if(!auth()->user()->can('restore_' . ArchivedDocument::MENU_NAME), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (ArchivedDocument::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
        ]);
    }
}
