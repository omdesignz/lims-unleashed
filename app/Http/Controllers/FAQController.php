<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\FAQRequest;
use App\Http\Resources\FAQResource;
use Illuminate\Support\Facades\DB;
use App\Models\FAQ;
use Inertia\Inertia;

class FAQController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_faqs'), 403, '');

        return Inertia::render('FAQs/Index', [
            'record' => FAQResource::collection(
                FAQ::query()
                            ->with('category')
                            ->when(request()->input('search'), function($query, $search){
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
                    'name' => trans('gestlab.general.labels.faqs.description'),
                    'value' => 'description'
                ],
                [
                    'name' => trans('gestlab.general.labels.faqs.category_id'),
                    'value' => 'category'
                ],
            ],
            'model' => FAQ::MENU_NAME,
            'abilities' => method_exists(FAQ::class, 'getAbilities') ? collect(FAQ::ABILITIES)->map(function($item){
                return $item . '_' . FAQ::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . FAQ::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_faqs'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('FAQs/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(FAQRequest $request)
    {
        abort_if( !auth()->user()->can('add_faqs'), 403, '');

        // Persiste data to DB
        FAQ::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_faqs'), 403, '');

        // Find the record
        $record = FAQ::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('FAQs/Edit', [
            'record' => FAQResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(FAQRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_faqs'), 403, '');

        // Find the record
        $record = FAQ::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_faqs'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (FAQ::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_faqs'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (FAQ::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getFAQ() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("faqs")
                ->select('faqs.*')
                ->orWhere('description','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
