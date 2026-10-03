<?php

namespace App\Http\Controllers;

use App\Actions\DownloadStaffProposalPdf;
use App\Actions\SetProposalArchived;
use App\Http\Requests\SetProposalRecordsArchivedRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Models\VAPProposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ProposalController extends Controller
{
    public function index()
    {
        // abort_if( !auth()->user()->can('view_proposals'), 403, '');

        $records = QueryBuilder::for(Proposal::class)
            ->with('complianceAgreement', 'customer', 'warehouse')
            ->withCount('activities as revision_count')
            ->withMax('activities as last_revision_at', 'created_at')
            ->allowedFilters(Proposal::getAllowedFilters())
            ->allowedSorts(Proposal::getAllowedSorts())
            ->paginate(request()->query('per_page', 10));

        return Inertia::render('Proposals/Index', [
            'record' => ProposalResource::collection($records),
            'initialFilters' => request()->query('filter', ['proposal_no' => '', 'service_location' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => false,
            'trashedFilter' => true,
            'trashedOptions' => Proposal::getTrashedOptions(),
            'fields' => Proposal::getColumns(),
            'model' => Proposal::MENU_NAME,
            'abilities' => method_exists(Proposal::class, 'getAbilities') ? collect(Proposal::ABILITIES)->map(function ($item) {
                return $item.'_'.Proposal::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.Proposal::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy']),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('vap-proposals.create');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $proposal = Proposal::query()
            ->with('complianceAgreement', 'customer', 'warehouse', 'template', 'items', 'user', 'discount_category', 'department')
            ->withCount('activities as revision_count')
            ->withMax('activities as last_revision_at', 'created_at')
            ->findOrFail($id);

        $items = collect($proposal?->items ?? []);
        $taxableItems = $items->filter(fn ($item) => (float) ($item->tax_amount ?? 0) > 0)->count();
        $discountedItems = $items->filter(fn ($item) => (float) ($item->discount_amount ?? 0) > 0)->count();
        $withholdingItems = $items->filter(fn ($item) => (bool) ($item->withhold_tax ?? false))->count();
        $plainItems = $items->filter(fn ($item) => (float) ($item->tax_amount ?? 0) <= 0
            && (float) ($item->discount_amount ?? 0) <= 0
            && ! (bool) ($item->withhold_tax ?? false))->count();
        $daysUntilExpiry = $proposal?->expiry_date
            ? max((int) now()->diffInDays($proposal->expiry_date, false), 0)
            : 0;

        return Inertia::render('Proposals/Show', [
            'record' => ProposalResource::make($proposal),
            'charts' => [
                'financial_breakdown' => [
                    'labels' => ['Subtotal', 'Desconto global', 'Imposto', 'Retenção', 'Total'],
                    'series' => [
                        (float) ($proposal?->sub_total ?? 0),
                        (float) ($proposal?->global_discount_amount ?? 0),
                        (float) $items->sum(fn ($item) => (float) ($item->tax_amount ?? 0)),
                        (float) ($proposal?->withholding_tax_amount ?? 0),
                        (float) ($proposal?->total ?? 0),
                    ],
                ],
                'item_composition' => [
                    'labels' => ['Itens tributáveis', 'Itens com desconto', 'Itens com retenção', 'Itens sem ajuste'],
                    'series' => [
                        $taxableItems,
                        $discountedItems,
                        $withholdingItems,
                        $plainItems,
                    ],
                ],
                'workflow_summary' => [
                    'labels' => ['Revisões', 'Dias tolerância', 'Itens', 'Dias até expirar'],
                    'series' => [
                        (int) ($proposal?->revision_count ?? 0),
                        (int) ($proposal?->tolerance_days ?? 0),
                        (int) $items->count(),
                        $daysUntilExpiry,
                    ],
                ],
            ],
        ]);
    }

    public function edit(int $id): RedirectResponse
    {
        $proposal = VAPProposal::query()->findOrFail($id);

        return redirect()->route('vap-proposals.edit', $proposal->id);
    }

    public function destroy(SetProposalRecordsArchivedRequest $request, SetProposalArchived $archive): RedirectResponse
    {
        $archive->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $request->validated('recordIds'), true);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    public function restore(SetProposalRecordsArchivedRequest $request, SetProposalArchived $archive): RedirectResponse
    {
        $archive->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $request->validated('recordIds'), false);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function getProposal()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = Proposal::query()
                ->where(fn ($query) => $query->where('proposal_no', 'LIKE', "%$search%")
                    ->orWhere('service_location', 'LIKE', "%$search%"))
                ->limit(50)
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF(Request $request, DownloadStaffProposalPdf $download): Response
    {
        $proposal = VAPProposal::query()->findOrFail($request->integer('id'));
        $rendered = $download->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $proposal);
        $attachment = $request->boolean('q');
        activity()->causedBy($request->user())->performedOn($proposal)
            ->log(($attachment ? 'baixou' : 'visualizou').' a Proposta Nº '.$proposal->proposal_number);

        return response($rendered['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($attachment ? 'attachment' : 'inline').'; filename="'.$rendered['filename'].'"',
            'X-Report-Studio-Renderer' => $rendered['renderer'],
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
