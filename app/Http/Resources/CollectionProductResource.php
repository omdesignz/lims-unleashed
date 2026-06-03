<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $code = $this->resource->relationLoaded('code') ? $this->code : null;
        $collection = $this->resource->relationLoaded('collection') ? $this->collection : null;
        $collectionType = $collection?->collectionable_type ?: data_get($this->extra_data, 'collection_type', 'direct');
        $collectionType = in_array($collectionType, ['direct', 'programmed'], true) ? $collectionType : 'direct';
        $totalSamples = $code?->relationLoaded('samples') ? $code->samples->count() : 0;
        $completedAnalysis = $code?->relationLoaded('completed_analysis') ? $code->completed_analysis->count() : 0;
        $pendingAnalysis = $code?->relationLoaded('pending_analysis') ? $code->pending_analysis->count() : 0;
        $inProgressAnalysis = $code?->relationLoaded('in_progress_analysis') ? $code->in_progress_analysis->count() : 0;
        $certificateAvailable = $this->resource->relationLoaded('quality_certificate') && (bool) $this->quality_certificate;
        $submittedPayload = data_get($this->extra_data, 'submitted_payload', []);
        $sampleEntry = $this->resource->relationLoaded('sampleEntry') ? $this->sampleEntry : null;
        $sampleEntryId = $sampleEntry?->id ?? data_get($this->extra_data, 'sample_entry_id');
        $entryLineage = $sampleEntryId
            ? collect([$sampleEntry?->code, $sampleEntry?->name])->filter()->join(' / ')
            : 'Registo legado de colheita';
        $trackingStatus = 'awaiting_collection';
        $trackingLabel = 'Aguarda colheita';

        if ($certificateAvailable) {
            $trackingStatus = 'certificate_ready';
            $trackingLabel = 'Certificado emitido';
        } elseif ($completedAnalysis > 0 && $pendingAnalysis === 0 && $inProgressAnalysis === 0) {
            $trackingStatus = 'analysis_completed';
            $trackingLabel = 'Análises concluídas';
        } elseif ($inProgressAnalysis > 0) {
            $trackingStatus = 'analysis_in_progress';
            $trackingLabel = 'Em análise';
        } elseif (($collection?->collectionable?->placed_analysis ?? false) || $pendingAnalysis > 0) {
            $trackingStatus = 'analysis_queued';
            $trackingLabel = 'Aguarda análise';
        } elseif ($this->processed) {
            $trackingStatus = 'sample_registered';
            $trackingLabel = 'Amostra registada';
        }

        return [
            'id' => $this->id,
            'cl' => LabCodeResource::make($code)?->code ?? null,
            'customer_id' => $this->customer_id,
            'customer' => CustomerResource::make($this->whenLoaded('customer'))?->name ?? null,
            'invoice_id' => $this->invoice_id,
            'quote_id' => $this->quote_id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => WarehouseResource::make($this->whenLoaded('warehouse'))?->address ?? null,
            'owner_id' => $this->owner_id,
            'owner' => UserResource::make($this->owner)?->name ?? null,
            'temperature_id' => $this->temperature_id,
            'temperature' => TemperatureResource::make($this->whenLoaded('temperature'))?->name ?? null,
            'temperature_value' => $this->temperature_value,
            'product_id' => $this->product_id,
            'product' => ProductResource::make($this->whenLoaded('product'))?->name ?? null,
            'qty' => $this->qty,
            'lot' => $this->lot,
            'bl' => $this->bl,
            'type' => $collectionType,
            'origin' => $this->origin,
            'location' => $this->location,
            'term_no' => $this->term_no,
            'processed' => $this->processed,
            'collected_by_lab' => $this->collected_by_lab,
            'placed_analysis' => $collection?->collectionable?->placed_analysis ?? true,
            'invoiced' => $this->invoiced,
            'quoted' => $this->quoted,
            'status' => $this->processed,
            'recollection' => $this->recollection,
            'du_no' => $this->du_no,
            'container_no' => $this->container_no,
            'collection_date' => $this->collection_date ?? $this->created_at,
            'expiry_date' => $this->expiry_date,
            'comercial_brand' => $this->comercial_brand,
            'obs' => $this->obs,
            'qr' => $this->Qr,
            'pack_id' => $this->pack_id,
            'sample_status' => $this->sample_status,
            'sampling_plan_ref' => $this->sampling_plan_ref,
            'customer_submitted_info' => $this->customer_submitted_info,
            'analysis_start_date' => $this->analysis_start_date,
            'analysis_end_date' => $this->analysis_end_date,
            'pack' => PackagingCategoryResource::make($this->whenLoaded('packaging'))?->name ?? null,
            'result_id' => $this->result_id,
            'result' => CollectionEndResultResource::make($this->whenLoaded('end_result'))?->name ?? null,
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle'))?->name ?? null,
            'invoice_id' => $this->invoice_id,
            'deleted' => $this->deleted_at ? true : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at ?? null,
            'total_samples' => $totalSamples,
            'completed_analysis' => $completedAnalysis,
            'pending_analysis' => $pendingAnalysis,
            'in_progress_analysis' => $inProgressAnalysis,
            'entry_lineage' => $entryLineage,
            'tracking_label' => $trackingLabel,
            'verified_date' => $code?->relationLoaded('results') && $code->results->count() > 0 && $code->relationLoaded('latest_verified_result') ? $code->latest_verified_result->first()?->verified_date : null,
            'approved_date' => $code?->relationLoaded('results') && $code->results->count() > 0 && $code->relationLoaded('latest_approved_result') ? $code->latest_approved_result->first()?->approved_date : null,
            'analysis_results' => $code?->relationLoaded('results') && $code->results->count() > 0 ? $code->results : [],
            'quality_certificate' => $this->resource->relationLoaded('quality_certificate') ? $this->quality_certificate : null,
            'samples' => $this->resource->relationLoaded('samples') ? $this->samples : [],
            'sample_entry' => $sampleEntryId ? [
                'id' => $sampleEntryId,
                'code' => $sampleEntry?->code,
                'name' => $sampleEntry?->name,
                'status' => $sampleEntry?->status,
                'sample_type' => $sampleEntry?->sample_type,
                'request_origin' => data_get($submittedPayload, 'request_origin'),
                'show_url' => route('vap_samples.show', $sampleEntryId),
            ] : null,
            'entry_origin' => [
                'source' => $sampleEntryId ? 'sample_entry' : 'legacy_collection',
                'label' => $sampleEntryId ? 'Entrada de amostra' : 'Registo legado de colheita',
                'is_sample_entry_first' => (bool) $sampleEntryId,
                'collection_type' => $collectionType,
            ],
            'scope_control' => [
                'conditioning_status' => data_get($submittedPayload, 'conditioning_status'),
                'packaging_condition' => data_get($submittedPayload, 'packaging_condition'),
                'temperature_condition' => data_get($submittedPayload, 'temperature_condition'),
                'integrity_observations' => data_get($submittedPayload, 'integrity_observations'),
                'chain_of_custody_notes' => data_get($submittedPayload, 'chain_of_custody_notes'),
                'resolved_profiles' => data_get($submittedPayload, 'resolved_profiles', []),
                'required_parameter_count' => data_get($submittedPayload, 'required_parameter_count', 0),
                'required_parameters' => data_get($submittedPayload, 'required_parameters', []),
            ],
            'tracking' => [
                'status' => $trackingStatus,
                'label' => $trackingLabel,
                'certificate_available' => $certificateAvailable,
                'report_available' => $completedAnalysis > 0,
                'total_samples' => $totalSamples,
                'completed_analysis' => $completedAnalysis,
                'pending_analysis' => $pendingAnalysis,
                'in_progress_analysis' => $inProgressAnalysis,
            ],
            'links' => [
                'show_path' => route("{$collectionType}collections.show", $this->id),
                'edit_path' => route("{$collectionType}collections.edit", $this->id),
                'collection_type' => $collectionType,
                'sample_entry_show_path' => $sampleEntryId ? route('vap_samples.show', $sampleEntryId) : null,
                'place_analysis_path' => $collectionType == 'programmed' ? route('programmedcollections.PlaceProductsInAnalysis', [
                    'collection_product_id' => $this->id,
                ]) : null,
                'pdf_quality_certificate' => $this->quality_certificate ? route('qualitycertificates.getPDF', ['id' => $this->quality_certificate->id]) : null,
                'pdf_path' => route("{$collectionType}collections.getParametersToAnalyzePDF", ['id' => $this->id]),
                'xlsx_path' => route("{$collectionType}collections.exportParametersToAnalyzeSheet", ['recordIds' => [$this->id]]),
                'pdf_collection_term' => route("{$collectionType}collections.getCollectionTermPDF", ['id' => $this->id]),
                'pdf_collection_labels' => route("{$collectionType}collections.getCollectionLabels", ['id' => $this->id]),
                'delete_path' => route("{$collectionType}collections.destroy", [
                    'recordIds' => [$this->id],
                ]),
                'restore_path' => route("{$collectionType}collections.restore", [
                    'recordIds' => [$this->id],
                ]),
            ],
            // 'links' => function(){
            //     $collection_type = $this->collection->collectionable_type;

            //     if($collection_type == 'programmed') {
            //         return collect([
            //             'edit_path' => route('programmedcollections.edit', $this->id),
            //             'place_analysis_path' => route('programmedcollections.place_analysis', $this->id),
            //             'pdf_path' => route('programmedcollections.getParametersToAnalyzePDF', ['id' => $this->id]),
            //             'delete_path' => route('programmedcollections.destroy', [
            //                 'recordIds' => [$this->id]
            //             ]),
            //             'restore_path' => route('programmedcollections.restore', [
            //                 'recordIds' => [$this->id]
            //             ]),
            //         ]);
            //     }

            //     switch ($collection_type) {
            //         case 'direct':
            //             # code...
            //             [
            //                 'edit_path' => route('directcollections.edit', $this->id),
            //                 'place_analysis_path' => route('directcollections.place_analysis', $this->id),
            //                 'pdf_path' => route('directcollections.getParametersToAnalyzePDF', ['id' => $this->id]),
            //                 'delete_path' => route('directcollections.destroy', [
            //                     'recordIds' => [$this->id]
            //                 ]),
            //                 'restore_path' => route('directcollections.restore', [
            //                     'recordIds' => [$this->id]
            //                 ]),
            //             ];

            //             break;

            //         case 'programmed':
            //             # code ...
            //             return [
            //                 'edit_path' => route('programmedcollections.edit', $this->id),
            //                 'place_analysis_path' => route('programmedcollections.place_analysis', $this->id),
            //                 'pdf_path' => route('programmedcollections.getParametersToAnalyzePDF', ['id' => $this->id]),
            //                 'delete_path' => route('programmedcollections.destroy', [
            //                     'recordIds' => [$this->id]
            //                 ]),
            //                 'restore_path' => route('programmedcollections.restore', [
            //                     'recordIds' => [$this->id]
            //                 ]),
            //             ];

            //         default:
            //             # code...
            //             break;
            //     }
            // }

        ];
    }
}
