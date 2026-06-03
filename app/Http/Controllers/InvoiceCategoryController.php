<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceCategoryRequest;
use App\Http\Resources\InvoiceCategoryResource;
use App\Models\InvoiceCategory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InvoiceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_invoice_categories'), 403, '');

        return Inertia::render('InvoiceCategories/Index', [
            'record' => InvoiceCategoryResource::collection(
                InvoiceCategory::query()
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
                    'name' => trans('gestlab.general.labels.invoice_categories.code'),
                    'value' => 'code',
                ],
                [
                    'name' => trans('gestlab.general.labels.invoice_categories.description'),
                    'value' => 'description',
                ],
            ],
            'model' => InvoiceCategory::MENU_NAME,
            'abilities' => method_exists(InvoiceCategory::class, 'getAbilities') ? collect(InvoiceCategory::ABILITIES)->map(function ($item) {
                return $item.'_'.InvoiceCategory::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.InvoiceCategory::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_invoice_categories'), 403, '');

        // Get any required data

        // Load form

        return Inertia::render('InvoiceCategories/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InvoiceCategoryRequest $request)
    {
        abort_if(! auth()->user()->can('add_invoice_categories'), 403, '');

        // Persiste data to DB
        InvoiceCategory::create($request->validated());

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
        abort_if(! auth()->user()->can('edit_invoice_categories'), 403, '');

        // Find the record
        $record = InvoiceCategory::findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('InvoiceCategories/Edit', [
            'record' => InvoiceCategoryResource::make($record),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InvoiceCategoryRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_invoice_categories'), 403, '');

        // Find the record
        $record = InvoiceCategory::findOrFail($id);

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
        abort_if(! auth()->user()->can('delete_invoice_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (InvoiceCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
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
        abort_if(! auth()->user()->can('restore_invoice_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (InvoiceCategory::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getInvoiceCategory()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('invoice_categories')
                ->select('invoice_categories.*')
                ->where('code', 'LIKE', "%$search%")
                ->orWhere('description', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
