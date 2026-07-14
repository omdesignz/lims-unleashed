<?php

namespace App\Support;

use App\Models\CollectionProduct;
use App\Models\QualityCertificate;
use App\Models\VAPProposal;
use Illuminate\Support\Collection;

class AnalysisReportService
{
    public function ensureForCollectionProduct(CollectionProduct $collectionProduct, int $userId): QualityCertificate
    {
        $collectionProduct->loadMissing('code');

        return QualityCertificate::query()->firstOrCreate([
            'collection_id' => $collectionProduct->id,
        ], [
            'user_id' => $userId,
            'customer_id' => $collectionProduct->customer_id,
            'warehouse_id' => $collectionProduct->warehouse_id,
            'product_id' => $collectionProduct->product_id,
            'cl_id' => $collectionProduct->code?->id,
            'code' => now('Africa/Luanda')->format('YmdHis').'-'.$collectionProduct->id,
            'obs' => $collectionProduct->obs,
            'status' => (bool) $collectionProduct->status,
            'file_path' => null,
        ]);
    }

    /**
     * @return Collection<int, QualityCertificate>
     */
    public function ensureForProposal(VAPProposal $proposal, int $userId): Collection
    {
        $proposal->loadMissing([
            'sampleEntries.collectionProduct.code.samples.analysis',
            'sampleEntries.collectionProduct.code.samples.results',
        ]);

        return $proposal->sampleEntries
            ->pluck('collectionProduct')
            ->filter()
            ->filter(function (CollectionProduct $collectionProduct): bool {
                $laboratorySamples = $collectionProduct->code?->samples ?? collect();
                $analyses = $laboratorySamples->pluck('analysis')->filter();
                $results = $laboratorySamples->flatMap->results;

                return $analyses->isNotEmpty()
                    && $results->isNotEmpty()
                    && $results->every(fn ($result): bool => filled($result->approved_date));
            })
            ->map(fn (CollectionProduct $collectionProduct): QualityCertificate => $this->ensureForCollectionProduct(
                $collectionProduct,
                $userId
            ))
            ->values();
    }
}
