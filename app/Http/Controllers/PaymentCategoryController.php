<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PaymentCategoryRequest;
use App\Http\Resources\PaymentCategoryResource;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentCategory;
use Inertia\Inertia;

class PaymentCategoryController extends Controller
{
    
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_payment_categories'), 403, '');

        return Inertia::render('PaymentCategories/Index', [
            'record' => PaymentCategoryResource::collection(
                PaymentCategory::query()
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('name', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter === 'trashed'){
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
                    'name' => trans('gestlab.general.labels.payment_categories.name'),
                    'value' => 'name'
                ],
                [
                    'name' => trans('gestlab.general.labels.payment_categories.code'),
                    'value' => 'code'
                ],
                [
                    'name' => trans('gestlab.general.labels.payment_categories.description'),
                    'value' => 'description'
                ],
            ],
            'model' => PaymentCategory::MENU_NAME,
            'abilities' => method_exists(PaymentCategory::class, 'getAbilities') ? collect(PaymentCategory::ABILITIES)->map(function($item){
                return $item . '_' . PaymentCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . PaymentCategory::MENU_NAME;
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
        abort_if( !auth()->user()->can('add_payment_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('PaymentCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(PaymentCategoryRequest $request)
    {
        abort_if( !auth()->user()->can('add_payment_categories'), 403, '');

        // Persiste data to DB
        PaymentCategory::create($request->validated());

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
        abort_if( !auth()->user()->can('edit_payment_categories'), 403, '');

        // Find the record
        $record = PaymentCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('PaymentCategories/Edit', [
            'record' => PaymentCategoryResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(PaymentCategoryRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_payment_categories'), 403, '');

        // Find the record
        $record = PaymentCategory::findOrFail($id);

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
        abort_if( !auth()->user()->can('delete_payment_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (PaymentCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if( !auth()->user()->can('restore_payment_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (PaymentCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getPaymentCategory() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("payment_categories")
                ->select('payment_categories.*')
                ->where('name','LIKE',"%$search%")
                ->orWhere('code','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

}
