<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounterAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sample = $this->resource->relationLoaded('sample') ? $this->sample : null;
        $labCode = $sample?->relationLoaded('collection') ? $sample->collection : null;
        $collectionProduct = $labCode?->relationLoaded('collection') ? $labCode->collection : null;
        $collection = $collectionProduct?->relationLoaded('collection') ? $collectionProduct->collection : null;
        $sampleEntry = $collectionProduct?->relationLoaded('sampleEntry') ? $collectionProduct->sampleEntry : null;
        $sampleEntryId = $sampleEntry?->id ?? data_get($collectionProduct?->extra_data, 'sample_entry_id');
        $collectionType = data_get($collectionProduct?->extra_data, 'collection_type') ?: $collection?->collectionable_type;
        $collectionType = in_array($collectionType, ['direct', 'programmed'], true) ? $collectionType : null;
        $sourceResult = $this->resource->relationLoaded('requested_result') ? $this->requested_result : null;
        $sourceResultValue = $sourceResult?->approved_value
            ?? $sourceResult?->verified_value
            ?? $sourceResult?->inserted_value
            ?? 'N/D';
        $sourceResultSummary = trim(($sourceResult?->parameter?->code ?? $sourceResult?->parameter?->name ?? trans('gestlab.general.labels.analysis.source_result')).': '.$sourceResultValue);
        $entryLineage = $sampleEntry
            ? trim(($sampleEntry->code ?: $sampleEntry->name).' / '.($sample?->code ?: ''))
            : trans('gestlab.general.labels.analysis.legacy_record');

        return [
            'id' => $this->id,
            'cl' => LabCodeResource::make($this->whenLoaded('code'))?->code ?? null,
            'col_date' => $this->col_date,
            'entry_date' => $this->entry_date,
            'init_date' => $this->init_date,
            'status' => $this->status,
            'type_id' => $this->type_id,
            'type' => AnalysisCategoryResource::make($this->whenLoaded('type'))?->code ?? null,
            'profile_id' => $this->profile_id,
            'profile' => ProfileResource::make($this->whenLoaded('profile'))?->name ?? null,
            'parameter_id' => $this->parameter_id,
            'parameter' => ParameterResource::make($this->whenLoaded('parameter'))?->name ?? null,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->whenLoaded('department'))?->name ?? null,
            'sample_id' => $this->sample_id,
            'sample' => $sample ? [
                'id' => $sample->id,
                'code' => $sample->code,
            ] : null,
            'sample_entry' => $sampleEntryId ? [
                'id' => $sampleEntryId,
                'code' => $sampleEntry?->code,
                'name' => $sampleEntry?->name,
                'status' => $sampleEntry?->status,
                'sample_type' => $sampleEntry?->sample_type,
                'show_url' => route('vap_samples.show', $sampleEntryId),
            ] : null,
            'entry_lineage' => $entryLineage,
            'source_result_summary' => $sourceResultSummary,
            'source_result' => $sourceResult ? [
                'id' => $sourceResult->id,
                'parameter' => $sourceResult?->parameter?->code ?? $sourceResult?->parameter?->name,
                'value' => $sourceResultValue,
                'lab_code' => $sourceResult?->code?->code,
                'sample_id' => $sourceResult->sample_id,
            ] : null,
            'entry_origin' => [
                'source' => $sampleEntryId ? 'sample_entry' : 'legacy_counter_analysis',
                'label' => $sampleEntryId
                    ? trans('gestlab.general.labels.analysis.sample_entry')
                    : trans('gestlab.general.labels.analysis.legacy_record'),
                'is_sample_entry_first' => (bool) $sampleEntryId,
                'collection_product_id' => $collectionProduct?->id,
                'collection_type' => $collectionType,
                'source_sample_id' => data_get($this->extra_data, 'source_sample_id'),
            ],
            'deleted' => $this->deleted_at ? true : false,
            'links' => [
                'edit_path' => route('counteranalysis.edit', $this->id),
                'original_analysis_path' => $this->analysis_id ? route('analysis.edit', $this->analysis_id) : null,
                'collection_show_path' => $collectionProduct && $collectionType
                    ? route("{$collectionType}collections.show", $collectionProduct)
                    : null,
                'sample_entry_show_path' => $sampleEntryId ? route('vap_samples.show', $sampleEntryId) : null,
                'delete_path' => route('counteranalysis.destroy', [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route('counteranalysis.restore', [
                    'recordIds' => [$this->id],
                ]),
            ],
        ];
    }
}
