<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposalComplianceAgreementRequest;
use App\Http\Resources\ProposalComplianceAgreementResource;
use App\Models\ProposalComplianceAgreement;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ProposalComplianceAgreementController extends Controller
{
    //
    public function index()
    {
        //
        // abort_if( !auth()->user()->can('view_maintenance_categories'), 403, '');

        $records = QueryBuilder::for(ProposalComplianceAgreement::class)
            ->with('proposal')
            ->allowedFilters(ProposalComplianceAgreement::getAllowedFilters())
            ->allowedSorts(ProposalComplianceAgreement::getAllowedSorts())
            ->paginate(request()->query('per_page', 10));

        return Inertia::render('ProposalComplianceAgreements/Index', [
            'record' => ProposalComplianceAgreementResource::collection($records),
            'initialFilters' => request()->query('filter', ['proposal_id' => '', 'confidentiality' => '', 'impartiality' => '', 'nondisclosure' => '', 'acknowledged_at' => '', 'client_ip' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => true,
            'trashedFilter' => true,
            'trashedOptions' => ProposalComplianceAgreement::getTrashedOptions(),
            'fields' => ProposalComplianceAgreement::getColumns(),
            'model' => ProposalComplianceAgreement::MENU_NAME,
            'abilities' => method_exists(ProposalComplianceAgreement::class, 'getAbilities') ? collect(ProposalComplianceAgreement::ABILITIES)->map(function ($item) {
                return $item.'_'.ProposalComplianceAgreement::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ProposalComplianceAgreement::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy']),
        ]);
    }

    public function create()
    {

        // abort_if( !auth()->user()->can('add_maintenance_categories'), 403, '');

        return [];
    }

    public function store(ProposalComplianceAgreementRequest $request)
    {
        // abort_if( !auth()->user()->can('add_maintenance_categories'), 403, '');

        // Persiste data to DB
        ProposalComplianceAgreement::create($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);
    }

    public function edit($id)
    {
        return [];
    }

    public function update(ProposalComplianceAgreementRequest $request, $id)
    {
        // Find the record
        $record = ProposalComplianceAgreement::findOrFail($id);

        $record->update($request->validated());

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    public function destroy()
    {
        // abort_if( !auth()->user()->can('delete_maintenance_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (ProposalComplianceAgreement::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    public function restore()
    {
        // abort_if( !auth()->user()->can('restore_maintenance_categories'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (ProposalComplianceAgreement::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getProposalComplianceAgreement()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('proposal_compliance_agreements')
                ->select('proposal_compliance_agreements.*')
                ->where('proposal_id', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}
