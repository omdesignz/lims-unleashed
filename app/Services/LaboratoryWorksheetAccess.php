<?php

namespace App\Services;

use App\Models\Analysis;
use App\Models\CollectionProduct;
use App\Models\LabCode;
use App\Models\Sample;
use App\Models\User;
use App\Models\VAPLab;
use App\Models\VAPSampleEntry;
use App\Models\Worksheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class LaboratoryWorksheetAccess
{
    public function __construct(private readonly LaboratoryWorkflowOwnership $ownership) {}

    /** @return Builder<Worksheet> */
    public function records(int $labId): Builder
    {
        return Worksheet::query()->where('worksheets.lab_id', $labId)->whereHas('lab')
            ->where(function (Builder $visibility) use ($labId): void {
                $visibility->where(function (Builder $manual): void {
                    $manual->whereNull('worksheets.analysis_id');
                    foreach (['analysis_id', 'collection_product_id', 'sample_id', 'profile_id', 'generated_from', 'scope_control'] as $key) {
                        $manual->whereJsonDoesntContainKey('worksheets->'.$key);
                    }
                })->orWhere(function (Builder $analytical) use ($labId): void {
                    $analytical->where('worksheets->generated_from', 'analysis_scope')
                        ->whereRaw("(worksheets.worksheets ->> 'analysis_id') = CAST(worksheets.analysis_id AS TEXT)")
                        ->whereIn('worksheets.analysis_id', $this->ownership->analysesForLaboratory($labId)
                            ->whereRaw("(worksheets.worksheets ->> 'sample_id') = CAST(analysis.sample_id AS TEXT)")
                            ->whereRaw("(worksheets.worksheets ->> 'profile_id') = CAST(analysis.profile_id AS TEXT)")
                            ->whereHas('code', fn (Builder $code): Builder => $code
                                ->whereRaw("(worksheets.worksheets ->> 'collection_product_id') = CAST(lab_codes.collection_id AS TEXT)"))
                            ->select('analysis.id'));
                });
            });
    }

    public function operator(int $labId, int $userId, string $permission): User
    {
        $operator = $this->ownership->eligibleUsers($labId)->find($userId);
        abort_unless(VAPLab::query()->whereKey($labId)->exists() && $operator?->can($permission), 403);

        return $operator;
    }

    public function constrainActivity(QueryBuilder $query, string $table = 'activity_log'): QueryBuilder
    {
        $types = ['worksheet', Worksheet::class];
        $labId = (int) request()->attributes->get('proposal_laboratory_id', 0);
        $operator = $this->ownership->eligibleUsers($labId)->find(request()->user('web')?->id);
        $canView = $operator?->can('view_worksheets') && VAPLab::query()->whereKey($labId)->exists();

        return $query->where(function (QueryBuilder $visibility) use ($table, $types, $labId, $canView): void {
            $visibility->whereNull("{$table}.subject_type")->orWhereNotIn("{$table}.subject_type", $types);
            if ($canView) {
                $visibility->orWhere(function (QueryBuilder $worksheets) use ($table, $types, $labId): void {
                    $worksheets->whereIn("{$table}.subject_type", $types)
                        ->whereIn("{$table}.subject_id", Worksheet::withTrashed()->where('lab_id', $labId)->select('id')->toBase());
                });
            }
        });
    }

    public function lockAnalysis(int $labId, int $analysisId): Analysis
    {
        $analysis = $this->ownership->analysesForLaboratory($labId)->lockForUpdate()->findOrFail($analysisId);
        $sample = Sample::query()->lockForUpdate()->findOrFail($analysis->sample_id);
        $code = LabCode::query()->lockForUpdate()->findOrFail($sample->cl_id);
        $accession = CollectionProduct::query()->lockForUpdate()->findOrFail($code->collection_id);
        VAPSampleEntry::query()->where('lab_id', $labId)->where('collection_product_id', $accession->id)
            ->lockForUpdate()->firstOrFail();
        abort_unless($this->ownership->analysesForLaboratory($labId)->whereKey($analysisId)->exists(), 404);

        return $analysis;
    }

    public function lockWorksheet(int $labId, int $worksheetId, bool $withTrashed = false): Worksheet
    {
        $worksheet = $this->records($labId)->when($withTrashed, fn (Builder $query): Builder => $query->withTrashed())
            ->lockForUpdate()->findOrFail($worksheetId);

        if ($worksheet->analysis_id !== null) {
            $this->lockAnalysis($labId, $worksheet->analysis_id);
            abort_unless($this->records($labId)->withTrashed()->whereKey($worksheetId)->exists(), 404);
        }

        return $worksheet;
    }
}
