<?php

namespace App\Support;

use App\Models\Analysis;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\DirectCollection;
use App\Models\LabCode;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Sample;
use App\Models\VAPSampleEntry;
use Illuminate\Support\Carbon;

class SampleEntryCollectionFlowService
{
    public function sync(VAPSampleEntry $sampleEntry): ?CollectionProduct
    {
        if ($sampleEntry->collectionProduct) {
            return $sampleEntry->collectionProduct;
        }

        $productId = data_get($sampleEntry->client_submitted_info, 'product_id');

        if (! $productId) {
            return null;
        }

        $product = Product::query()->with('matrix.profiles.type')->find($productId);

        if (! $product) {
            return null;
        }

        $availableProfiles = ($product->matrix?->profiles ?? collect())
            ->when($sampleEntry->department_id, function ($profiles) use ($sampleEntry) {
                return $profiles->filter(
                    fn (Profile $profile) => (int) $profile->type?->department_id === (int) $sampleEntry->department_id
                );
            })
            ->values();

        $profileIds = collect(
            data_get($sampleEntry->client_submitted_info, 'resolved_profile_ids', data_get($sampleEntry->client_submitted_info, 'requested_profile_ids', []))
        )
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($profileIds->isEmpty()) {
            $profileIds = $availableProfiles->pluck('id')->values();
        }

        if ($profileIds->isEmpty()) {
            return null;
        }

        $directCollection = DirectCollection::query()->create([
            'description' => 'Fluxo originado pela validação da amostra ' . ($sampleEntry->code ?: $sampleEntry->name),
            'col_date' => optional($sampleEntry->received_at)->toDateString() ?? now()->toDateString(),
        ]);

        $collection = $directCollection->collection()->save(new Collection([
            'customer_id' => $sampleEntry->customer_id,
            'warehouse_id' => $sampleEntry->warehouse_id,
        ]));

        $collectionProduct = CollectionProduct::query()->create([
            'collection_id' => $collection->id,
            'customer_id' => $sampleEntry->customer_id,
            'warehouse_id' => $sampleEntry->warehouse_id,
            'product_id' => $product->id,
            'pack_id' => $sampleEntry->packaging_id,
            'obs' => $sampleEntry->obs,
            'qty' => data_get($sampleEntry->client_submitted_info, 'quantity', 1),
            'lot' => data_get($sampleEntry->client_submitted_info, 'lot'),
            'origin' => data_get($sampleEntry->client_submitted_info, 'origin'),
            'location' => data_get($sampleEntry->client_submitted_info, 'location'),
            'comercial_brand' => data_get($sampleEntry->client_submitted_info, 'product_name', $sampleEntry->name),
            'processed' => true,
            'collected_by_lab' => (bool) $sampleEntry->collected_by_lab,
            'collection_date' => optional($sampleEntry->received_at)->toDateString(),
            'progress' => $this->resolveTrackingProgress($sampleEntry),
            'extra_data' => [
                'sample_entry_id' => $sampleEntry->id,
                'analysis_start_date' => optional($sampleEntry->analysis_start_date)?->toDateString(),
                'sample_status' => $sampleEntry->status,
                'request_reference' => data_get($sampleEntry->client_submitted_info, 'request_reference'),
                'request_title' => data_get($sampleEntry->client_submitted_info, 'request_title'),
                'request_origin' => data_get($sampleEntry->client_submitted_info, 'request_origin', 'client'),
                'submitted_payload' => $sampleEntry->client_submitted_info,
            ],
        ]);

        $code = LabCode::query()->create([
            'code' => '',
            'codeable_type' => 'analysis',
            'cl_month' => Carbon::now()->format('y/m'),
            'collection_id' => $collectionProduct->id,
        ]);

        $samples = [];

        foreach (Profile::query()->with('type')->whereIn('id', $profileIds)->get() as $profile) {
            $sample = Sample::query()->create([
                'code' => '',
                'sample_month' => Carbon::now()->format('y/m'),
                'cl_id' => $code->id,
            ]);

            $analysis = Analysis::query()->create([
                'department_id' => $profile->type?->department_id,
                'sample_id' => $sample->id,
                'profile_id' => $profile->id,
                'col_date' => optional($sampleEntry->received_at)->toDateString() ?? now()->toDateString(),
                'entry_date' => now()->toDateString(),
                'type_id' => $profile->category_id,
                'cl_id' => $code->id,
                'product_id' => $product->id,
            ]);

            $analysis->codeable()->save($code);
            $samples[] = $sample->id;
        }

        $payload = collect($sampleEntry->client_submitted_info ?? [])
            ->merge([
                'product_id' => $product->id,
                'matrix_id' => $product->matrix_id,
                'requested_profile_ids' => $profileIds->all(),
                'linked_lab_code_id' => $code->id,
                'linked_sample_ids' => $samples,
                'linked_collection_type' => 'direct',
            ])
            ->all();

        $sampleEntry->forceFill([
            'collection_product_id' => $collectionProduct->id,
            'client_submitted_info' => $payload,
        ])->save();

        return $collectionProduct->fresh(['code', 'product']);
    }

    private function resolveTrackingProgress(VAPSampleEntry $sampleEntry): string
    {
        return match ($sampleEntry->status) {
            'EN_PROGRESO' => 'Analysis in progress',
            'COMPLETADO' => 'Analysis completed',
            default => 'Pending analysis',
        };
    }
}
