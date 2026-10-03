<?php

namespace App\Services;

use App\Models\VAPSampleDiscard;
use App\Models\VAPSampleEntry;
use Illuminate\Database\Eloquent\Builder;

class SampleLaboratoryAccess
{
    public function __construct(private readonly LabNetworkAccess $access) {}

    public function activeLabId(): int
    {
        $request = request();
        if (! $request->attributes->has('sample_laboratory_id')) {
            abort_unless($request->user(), 403);
            $labs = $this->access->memberships($request->user());
            $lab = $labs->firstWhere('id', $request->session()->get('active_lab_id')) ?? $labs->first();
            abort_unless($lab, 403, 'É necessária uma associação directa ao laboratório.');
            $request->attributes->set('sample_laboratory_id', (int) $lab->id);
        }

        return $request->attributes->get('sample_laboratory_id');
    }

    /** @return Builder<VAPSampleEntry> */
    public function samples(): Builder
    {
        return VAPSampleEntry::query()->where('lab_id', $this->activeLabId());
    }

    /** @return Builder<VAPSampleDiscard> */
    public function discards(): Builder
    {
        $labId = $this->activeLabId();

        return VAPSampleDiscard::query()
            ->where(fn (Builder $query) => $query->whereNull('lab_id')->orWhere('lab_id', $labId))
            ->whereHas('sample', fn (Builder $query) => $query->withTrashed()->where('lab_id', $labId));
    }
}
