<?php

namespace App\Http\Controllers;

use App\Actions\ListLaboratorySamples;
use App\Http\Requests\ListLaboratorySamplesRequest;
use App\Http\Resources\LaboratorySampleResource;
use App\Models\VAPSampleEntry;
use App\Services\LabNetworkAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LaboratorySampleQueueController extends Controller
{
    public function __construct(private readonly LabNetworkAccess $access) {}

    public function index(ListLaboratorySamplesRequest $request, ListLaboratorySamples $list): Response
    {
        $lab = $this->activeLab($request);

        return Inertia::render('VAPSamples/Queue', [
            'lab' => $lab,
            'filters' => $request->validated(),
            'samples' => LaboratorySampleResource::collection($list->execute($lab['id'], $request->validated())),
        ]);
    }

    public function show(Request $request, int $sampleEntry): LaboratorySampleResource
    {
        Gate::authorize('view_samples');
        $lab = $this->activeLab($request);
        $sample = VAPSampleEntry::query()->where('lab_id', $lab['id'])
            ->with('customer:id,name')->findOrFail($sampleEntry);

        return LaboratorySampleResource::make($sample);
    }

    /** @return array<string, mixed> */
    private function activeLab(Request $request): array
    {
        $lab = $this->access->context($request->user(), $request->session()->get('active_lab_id'))['active_lab'];
        abort_if($lab === null, 403, 'Nenhum laboratório associado.');

        return $lab;
    }
}
