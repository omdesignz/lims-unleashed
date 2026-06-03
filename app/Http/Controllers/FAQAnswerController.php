<?php

namespace App\Http\Controllers;

use App\Http\Requests\FAQAnswerRequest;
use App\Http\Resources\FAQAnswerResource;
use App\Models\FAQAnswer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FAQAnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_faq_answers'), 403, '');

        return Inertia::render('FAQAnswers/Index', [
            'record' => FAQAnswerResource::collection(
                FAQAnswer::query()
                    ->with('faq')
                    ->when(request()->input('search'), function ($query, $search) {
                        $query->where('description', 'like', "%{$search}%");
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
                    'name' => trans('gestlab.general.labels.faq_answers.description'),
                    'value' => 'description',
                ],
                [
                    'faq' => trans('gestlab.general.labels.faq_answers.faq_id'),
                    'value' => 'faq',
                ],
            ],
            'model' => FAQAnswer::MENU_NAME,
            'abilities' => method_exists(FAQAnswer::class, 'getAbilities') ? collect(FAQAnswer::ABILITIES)->map(function ($item) {
                return $item.'_'.FAQAnswer::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.FAQAnswer::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_faq_answers'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('FAQAnswers/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FAQAnswerRequest $request)
    {
        abort_if(! auth()->user()->can('add_faq_answers'), 403, '');

        // Persiste data to DB
        FAQAnswer::create($request->validated());

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
        abort_if(! auth()->user()->can('edit_faq_answers'), 403, '');

        // Find the record
        $record = FAQAnswer::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('FAQAnswers/Edit', [
            'record' => FAQAnswerResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FAQAnswerRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_faq_answers'), 403, '');

        // Find the record
        $record = FAQAnswer::findOrFail($id);

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
        abort_if(! auth()->user()->can('delete_faq_answers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (FAQAnswer::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_faq_answers'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (FAQAnswer::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getFAQAnswer()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('faq_answers')
                ->select('faq_answers.*')
                ->orWhere('description', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
