<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\DocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Http\Resources\CurrencyResource;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_documents'), 403, '');

        return Inertia::render('Document/Index', [
            'record' => DocumentResource::collection(
                Document::query()
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
                    'name' => trans('gestlab.general.labels.documents.title'),
                    'value' => 'title'
                ],
                [
                    'name' => trans('gestlab.general.labels.documents.file_path'),
                    'value' => 'file_path'
                ],
                [
                    'name' => trans('gestlab.general.labels.documents.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.documents.version'),
                    'value' => 'version'
                ],
            ],
            'model' => Document::MENU_NAME,
            'abilities' => method_exists(Document::class, 'getAbilities') ? collect(Document::ABILITIES)->map(function ($item) {
                return $item . '_' . Document::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Document::MENU_NAME;
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
        abort_if(!auth()->user()->can('add_documents'), 403, '');

        return to_route('documents.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(DocumentRequest $request)
    {
        abort_if(!auth()->user()->can('add_documents'), 403, '');

        // Persiste data to DB
        $path = $request->file('file')->store('documents');

        Document::create($request->validated() + ['file_path' => $path]);

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
        abort_if(!auth()->user()->can('edit_documents'), 403, '');

        return to_route('documents.index');
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(DocumentRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_documents'), 403, '');

        // Find the record
        $record = Document::findOrFail($id);

        if ($request->hasFile('file')) {
            // Delete old file
            Storage::delete($record->file_path);

            // Store new file
            $path = $request->file('file')->store('documents');
            $record->file_path = $path;
        }

        $record->update($request->validated() + ['file_path' => $record->file_path]);

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
        abort_if(!auth()->user()->can('delete_documents'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Document::withTrashed()->findOrFail(request('recordIds')) as $record) {

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
        abort_if(!auth()->user()->can('restore_documents'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Document::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
