<?php

namespace App\Http\Controllers;

use App\Actions\SaveOccurrence;
use App\Actions\SetOccurrenceArchived;
use App\Http\Requests\FilterOccurrencesRequest;
use App\Http\Requests\OccurrenceRequest;
use App\Http\Requests\SetOccurrenceArchivedRequest;
use App\Http\Resources\OccurrenceResource;
use App\Models\Occurrence;
use App\Services\SampleLaboratoryAccess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OccurrenceController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(FilterOccurrencesRequest $request): Response
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $filters = $request->validated();

        return Inertia::render('Occurrences/Index', [
            'record' => OccurrenceResource::collection(
                Occurrence::query()->where('lab_id', $labId)
                    ->with(['category', 'department', 'origin', 'user', 'status'])
                    ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                        fn ($query) => $query->where('issue_description', 'like', "%{$search}%")
                            ->orWhere('occurrence_no', 'like', "%{$search}%")
                    ))
                    ->when(data_get($filters, 'date.start'), fn ($query, $date) => $query->whereBetween('date_reported', [
                        Carbon::parse($date)->startOfDay(),
                        Carbon::parse(data_get($filters, 'date.end'))->endOfDay(),
                    ]))
                    ->when(($filters['filter'] ?? null) === 'trashed', fn ($query) => $query->withTrashed())
                    ->latest('id')->paginate(10)->withQueryString()
            ),
            'slideOverEdit' => false,
            'fields' => collect(['occurrence_no', 'issue_description', 'date_reported', 'corrective_action', 'date_resolved'])
                ->map(fn (string $field): array => [
                    'name' => trans('gestlab.general.labels.occurrences.'.$field),
                    'value' => $field,
                ])->all(),
            'model' => Occurrence::MENU_NAME,
            'abilities' => collect(config('gestlab.default_abilities'))
                ->map(fn (string $ability): string => $ability.'_'.Occurrence::MENU_NAME),
            'query' => $request->only(['search', 'filter', 'date']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('add_occurrences'), 403);
        $this->laboratoryAccess->activeLabId();

        return Inertia::render('Occurrences/Create');
    }

    public function store(OccurrenceRequest $request, SaveOccurrence $save): RedirectResponse
    {
        $occurrence = $save->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated());

        return redirect()->route('occurrences.show', $occurrence)->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_created'),
        ]);
    }

    public function show(Request $request, Occurrence $occurrence): Response
    {
        $this->authorizeRecord($request, $occurrence, 'view_occurrences');

        return Inertia::render('Occurrences/Show', [
            'record' => OccurrenceResource::make($occurrence->load(['category', 'department', 'origin', 'user', 'status'])),
        ]);
    }

    public function edit(Request $request, Occurrence $occurrence): Response
    {
        $this->authorizeRecord($request, $occurrence, 'edit_occurrences');

        return Inertia::render('Occurrences/Edit', [
            'record' => OccurrenceResource::make($occurrence->load(['category', 'department', 'origin', 'user', 'status'])),
        ]);
    }

    public function update(OccurrenceRequest $request, Occurrence $occurrence, SaveOccurrence $save): RedirectResponse
    {
        $save->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated(), $occurrence->id);

        return redirect()->route('occurrences.show', $occurrence)->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.record_successfully_updated'),
        ]);
    }

    public function destroy(SetOccurrenceArchivedRequest $request, SetOccurrenceArchived $archive): RedirectResponse
    {
        $archive->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated('recordIds'), true);

        return $this->archiveResponse('record_successfully_deleted');
    }

    public function restore(SetOccurrenceArchivedRequest $request, SetOccurrenceArchived $archive): RedirectResponse
    {
        $archive->execute($this->laboratoryAccess->activeLabId(), $request->user()->id, $request->validated('recordIds'), false);

        return $this->archiveResponse('record_successfully_restored');
    }

    private function authorizeRecord(Request $request, Occurrence $occurrence, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
        abort_unless((int) $occurrence->lab_id === $this->laboratoryAccess->activeLabId(), 404);
    }

    private function archiveResponse(string $message): RedirectResponse
    {
        return redirect()->back()->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => trans('gestlab.toasts.'.$message),
        ]);
    }
}
