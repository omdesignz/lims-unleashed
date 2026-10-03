<?php

namespace App\Http\Controllers;

use App\Actions\SetProposalComplianceAgreementArchived;
use App\Http\Requests\SetProposalRecordsArchivedRequest;
use App\Http\Resources\ProposalComplianceAgreementResource;
use App\Models\Proposal;
use App\Models\ProposalComplianceAgreement;
use Illuminate\Http\RedirectResponse;
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
            ->with(['proposal' => fn ($query) => $query->withTrashed()])
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
            'trashedFilter' => true,
            'trashedOptions' => Proposal::getTrashedOptions(),
            'fields' => ProposalComplianceAgreement::getColumns(),
            'model' => Proposal::MENU_NAME,
            'abilities' => collect(['view', 'delete', 'restore'])->map(fn (string $ability): string => $ability.'_'.Proposal::MENU_NAME),
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy']),
        ]);
    }

    public function destroy(SetProposalRecordsArchivedRequest $request, SetProposalComplianceAgreementArchived $archive): RedirectResponse
    {
        $archive->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $request->validated('recordIds'), true);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    public function restore(SetProposalRecordsArchivedRequest $request, SetProposalComplianceAgreementArchived $archive): RedirectResponse
    {
        $archive->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $request->validated('recordIds'), false);

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

            $data = ProposalComplianceAgreement::query()
                ->whereRaw('CAST(proposal_id AS TEXT) LIKE ?', ["%$search%"])
                ->limit(50)
                ->get();
        }

        return response()->json($data);
    }
}
