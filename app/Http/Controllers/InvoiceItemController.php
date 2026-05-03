<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InvoiceItemRequest;
use App\Http\Resources\InvoiceItemResource;
use App\Models\CollectionProduct;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceItem;
use App\Models\LabCode;
use App\Models\Parameter;
use Inertia\Inertia;

class InvoiceItemController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(InvoiceItemRequest $request)
    {

        DB::transaction(function () use ($request): void {
            
        });

        
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
        
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(InvoiceItemRequest $request, $id)
    {
        // dd($request->all());

        DB::transaction(function () use ($request, $id): void {

            $record = tap(InvoiceItem::findOrFail($id), function($record) use($request) {

                $record->update($request->validated());
    
            });

            if($record->itemable_id) {

                $cp = tap(CollectionProduct::findOrFail(LabCode::findOrFail($record->itemable_id)->collection_id), function($cp) use($record) {

                    $cp->invoice_item()->save($record);

                    $cp->whereIn('id', Invoice::findOrFail($record->invoice_id)->items()->pluck('itemable_id')->toArray() )->update([
                        'invoiced' => true,
                        'invoice_id' => $record->invoice_id,
                    ]);
        
                });

            }

        });

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
        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (InvoiceItem::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (InvoiceItem::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
