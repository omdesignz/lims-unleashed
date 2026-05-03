<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\OccurrenceRequest;
use App\Http\Resources\OccurrenceResource;
use Illuminate\Support\Facades\DB;
use App\Models\Occurrence;
use Carbon\Carbon;
use Inertia\Inertia;

class OccurrenceController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(!auth()->user()->can('view_occurrences'), 403, '');

        return Inertia::render('Occurrences/Index', [
            'record' => OccurrenceResource::collection(
                Occurrence::query()
                    ->with('category', 'department', 'origin', 'user')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('issue_description', 'like', "%{$search}%")
                                ->orWhere('occurrence_no', 'like', "%{$search}%");
                    })
                    ->when(request()->input('date.start'), function ($query, $date) {
                        $query->whereBetween('date_reported', [Carbon::parse(request()->input('date.start'))->startOfDay(), Carbon::parse(request()->input('date.end'))->endOfDay()]);
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->orderBy('occurrence_no', 'desc')
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.occurrences.occurrence_no'),
                    'value' => 'occurrence_no'
                ],
                [
                    'name' => trans('gestlab.general.labels.occurrences.issue_description'),
                    'value' => 'issue_description'
                ],
                [
                    'name' => trans('gestlab.general.labels.occurrences.date_reported'),
                    'value' => 'date_reported'
                ],
                [
                    'name' => trans('gestlab.general.labels.occurrences.corrective_action'),
                    'value' => 'corrective_action'
                ],
                [
                    'name' => trans('gestlab.general.labels.occurrences.date_resolved'),
                    'value' => 'date_resolved'
                ],
            ],
            'model' => Occurrence::MENU_NAME,
            'abilities' => method_exists(Occurrence::class, 'getAbilities') ? collect(Occurrence::ABILITIES)->map(function ($item) {
                return $item . '_' . Occurrence::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . Occurrence::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed', 'date'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(!auth()->user()->can('add_occurrences'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('Occurrences/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(OccurrenceRequest $request)
    {
        abort_if(!auth()->user()->can('add_occurrences'), 403, '');

        // Persiste data to DB
        Occurrence::create($request->validated());

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
        return Inertia::render('Occurrences/Show', [
            'record' => OccurrenceResource::make(
                Occurrence::query()
                                 ->with('category', 'department', 'origin', 'user')
                                 ->find($id)
            )
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if(!auth()->user()->can('edit_occurrences'), 403, '');

        // Find the record
        $record = Occurrence::with('category', 'origin', 'user', 'department')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Occurrences/Edit', [
            'record' => OccurrenceResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(OccurrenceRequest $request, $id)
    {
        abort_if(!auth()->user()->can('edit_occurrences'), 403, '');

        // Find the record
        $record = Occurrence::findOrFail($id);

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
        abort_if(!auth()->user()->can('delete_occurrences'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Occurrence::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(!auth()->user()->can('restore_occurrences'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Occurrence::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
