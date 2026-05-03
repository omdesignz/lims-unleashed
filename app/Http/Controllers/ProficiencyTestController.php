<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProficiencyTestRequest;
use App\Http\Resources\ProficiencyTestResource;
use App\Models\ProficiencyTest;
use Inertia\Inertia;

class ProficiencyTestController extends Controller
{
    private function can(string $permission): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can($permission);
    }

    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if(! $this->can('view_proficiency_tests'), 403, '');

        return Inertia::render('ProficiencyTest/Index', [
            'record' => ProficiencyTestResource::collection(
                ProficiencyTest::query()
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where(function ($searchQuery) use ($search) {
                            $searchQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('provider_name', 'like', "%{$search}%")
                                ->orWhere('round_reference', 'like', "%{$search}%");
                        });
                    })
                    ->when(request()->input('filter'), function ($query, $filter) {
                        if ($filter === 'trashed') {
                            $query->withTrashed();
                        }
                    })
                    ->when(request()->input('status'), function ($query, $status) {
                        $query->where('status', $status);
                    })
                    ->when(request()->input('scheme_type'), function ($query, $schemeType) {
                        $query->where('scheme_type', $schemeType);
                    })
                    ->latest()
                    ->paginate(10)
                    ->withQueryString()
            ),
            'slideOverEdit' => true,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.proficiency_tests.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.proficiency_tests.date'),
                    'value' => 'date'
                ],
                [
                    'name' => 'Estado',
                    'value' => 'status'
                ],
            ],
            'model' => ProficiencyTest::MENU_NAME,
            'abilities' => method_exists(ProficiencyTest::class, 'getAbilities') ? collect(ProficiencyTest::ABILITIES)->map(function ($item) {
                return $item . '_' . ProficiencyTest::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item . '_' . ProficiencyTest::MENU_NAME;
            }),
            'query' => request()->only(['search', 'filter', 'status', 'scheme_type']),
            'statusOptions' => ['planned', 'in_progress', 'completed', 'reviewed', 'closed'],
            'schemeOptions' => ['proficiency', 'interlaboratory'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if(! $this->can('add_proficiency_tests'), 403, '');

        return redirect()->route('proficiency_tests.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ProficiencyTestRequest $request)
    {
        abort_if(! $this->can('add_proficiency_tests'), 403, '');

        ProficiencyTest::create($request->validated());

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
        abort_if(! $this->can('edit_proficiency_tests'), 403, '');

        return redirect()->route('proficiency_tests.index');
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ProficiencyTestRequest $request, $id)
    {
        abort_if(! $this->can('edit_proficiency_tests'), 403, '');

        $record = ProficiencyTest::findOrFail($id);
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
        abort_if(! $this->can('delete_proficiency_tests'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (ProficiencyTest::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! $this->can('restore_proficiency_tests'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (ProficiencyTest::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
