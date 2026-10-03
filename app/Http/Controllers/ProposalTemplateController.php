<?php

namespace App\Http\Controllers;

use App\Actions\SetProposalTemplatesArchived;
use App\Http\Requests\SetProposalTemplatesArchivedRequest;
use App\Http\Resources\ProposalTemplateResource;
use App\Models\ProposalTemplate;
use App\Models\VAPProposalTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ProposalTemplateController extends Controller
{
    public function index()
    {
        $records = QueryBuilder::for(ProposalTemplate::class)
            ->with('user:id,name')
            ->withExists('proposals')
            ->allowedFilters(ProposalTemplate::getAllowedFilters())
            ->allowedSorts(ProposalTemplate::getAllowedSorts())
            ->paginate(request()->query('per_page', 10));

        return Inertia::render('ProposalTemplates/Index', [
            'record' => ProposalTemplateResource::collection($records),
            'initialFilters' => request()->query('filter', ['name' => '', 'user_id' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => false,
            'trashedFilter' => true,
            'trashedOptions' => ProposalTemplate::getTrashedOptions(),
            'fields' => ProposalTemplate::getColumns(),
            'model' => ProposalTemplate::MENU_NAME,
            'abilities' => method_exists(ProposalTemplate::class, 'getAbilities') ? collect(ProposalTemplate::ABILITIES)->map(function ($item) {
                return $item.'_'.ProposalTemplate::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.ProposalTemplate::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy']),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('vap-proposals.templates.create');
    }

    public function edit(int $template): RedirectResponse
    {
        $record = VAPProposalTemplate::query()->findOrFail($template);

        return redirect()->route('vap-proposals.templates.edit', $record);
    }

    public function destroy(SetProposalTemplatesArchivedRequest $request, SetProposalTemplatesArchived $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $request->validated('recordIds'), true);

        return back()->with('success', 'Modelos de proposta arquivados.');
    }

    public function restore(SetProposalTemplatesArchivedRequest $request, SetProposalTemplatesArchived $archive): RedirectResponse
    {
        $archive->execute($request->user()->id, $request->validated('recordIds'), false);

        return back()->with('success', 'Modelos de proposta restaurados.');
    }

    public function getProposalTemplate(Request $request): JsonResponse
    {
        $search = trim($request->string('q')->value());
        $records = filled($search) ? VAPProposalTemplate::query()->where('name', 'like', "%{$search}%")
            ->orderBy('name')->limit(50)->get(['id', 'name']) : collect();

        return response()->json($records);
    }
}
