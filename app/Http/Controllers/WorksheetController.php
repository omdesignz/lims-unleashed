<?php

namespace App\Http\Controllers;

use App\Actions\CreateAnalysisWorksheet;
use App\Actions\SaveWorksheet;
use App\Actions\SetWorksheetArchived;
use App\Http\Requests\ArchiveWorksheetsRequest;
use App\Http\Requests\SaveWorksheetRequest;
use App\Http\Resources\WorksheetResource;
use App\Services\LaboratoryWorksheetAccess;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorksheetController extends Controller
{
    public function __construct(
        private readonly SampleLaboratoryAccess $laboratory,
        private readonly LaboratoryWorksheetAccess $worksheets,
    ) {}

    public function index(Request $request): Response
    {
        $labId = $this->laboratory->activeLabId();
        $operator = $this->worksheets->operator($labId, (int) $request->user()->id, 'view_worksheets');
        $validated = $request->validate(['trashed' => ['nullable', 'in:only,with']]);
        $records = $this->worksheets->records($labId)
            ->when(($validated['trashed'] ?? null) === 'only', fn ($query) => $query->onlyTrashed())
            ->when(($validated['trashed'] ?? null) === 'with', fn ($query) => $query->withTrashed())
            ->latest('updated_at')->latest('id')->get();

        return Inertia::render('Worksheets/Index', [
            'worksheets' => WorksheetResource::collection($records)->resolve($request),
            'trashed' => $validated['trashed'] ?? '',
            'can_restore' => $operator->can('restore_worksheets'),
        ]);
    }

    public function getWorksheet(Request $request): JsonResponse
    {
        $labId = $this->laboratory->activeLabId();
        $this->worksheets->operator($labId, (int) $request->user()->id, 'view_worksheets');
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $search = trim($validated['q'] ?? '');
        $data = $this->worksheets->records($labId)->select(['id', 'name', 'updated_at'])
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"))
            ->latest('updated_at')->latest('id')->limit(25)->get()
            ->map(fn ($worksheet): array => [
                'id' => $worksheet->id, 'value' => $worksheet->id,
                'label' => $worksheet->name, 'name' => $worksheet->name,
                'updated_at' => $worksheet->updated_at?->toIso8601String(),
            ]);

        return response()->json($data);
    }

    public function store(SaveWorksheetRequest $request, SaveWorksheet $save): RedirectResponse
    {
        $worksheet = $save->execute($this->laboratory->activeLabId(), (int) $request->user()->id, $request->validated());

        return to_route('worksheets.show', $worksheet)->with('toast', $this->toast('record_successfully_created'));
    }

    public function show(Request $request, string $worksheet): Response
    {
        $labId = $this->laboratory->activeLabId();
        $operator = $this->worksheets->operator($labId, (int) $request->user()->id, 'view_worksheets');
        $record = $this->worksheets->records($labId)->findOrFail($this->recordId($worksheet));

        return Inertia::render('Worksheets/Edit', [
            'worksheet' => WorksheetResource::make($record)->resolve($request),
            'can_edit' => $operator->can('edit_worksheets'),
        ]);
    }

    public function update(SaveWorksheetRequest $request, string $worksheet, SaveWorksheet $save): RedirectResponse
    {
        $save->execute($this->laboratory->activeLabId(), (int) $request->user()->id, $request->validated(), $this->recordId($worksheet));

        return to_route('worksheets.show', $worksheet)->with('toast', $this->toast('record_successfully_updated'));
    }

    public function destroy(ArchiveWorksheetsRequest $request, SetWorksheetArchived $archive): RedirectResponse
    {
        $archive->execute($this->laboratory->activeLabId(), (int) $request->user()->id,
            array_map('intval', $request->validated('recordIds')), archived: true);

        return back()->with('toast', $this->toast('record_successfully_deleted'));
    }

    public function restore(ArchiveWorksheetsRequest $request, SetWorksheetArchived $archive): RedirectResponse
    {
        $archive->execute($this->laboratory->activeLabId(), (int) $request->user()->id,
            array_map('intval', $request->validated('recordIds')), archived: false);

        return back()->with('toast', $this->toast('record_successfully_restored'));
    }

    public function storeAnalysisDraft(Request $request, string $analysis, CreateAnalysisWorksheet $create): RedirectResponse
    {
        $worksheet = $create->execute($this->laboratory->activeLabId(), (int) $request->user()->id, $this->recordId($analysis));

        return to_route('worksheets.show', $worksheet)->with('toast', [
            'title' => trans('gestlab.toasts.notification'),
            'message' => 'Folha de trabalho analítica disponível com base no âmbito controlado da análise.',
        ]);
    }

    private function recordId(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404);

        return $id;
    }

    /** @return array{title: string, message: string} */
    private function toast(string $message): array
    {
        return ['title' => trans('gestlab.toasts.notification'), 'message' => trans('gestlab.toasts.'.$message)];
    }
}
